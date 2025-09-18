<?php
/**
 * نموذج تقييم زيارة صفية - نسخة جديدة مبسطة
 * تم إنشاؤها لحل مشكلة نوع الزائر
 */

require_once 'includes/db_connection.php';
require_once 'includes/auth_functions.php';

// بدء الجلسة
session_start();

// حماية الصفحة - المسموح لهم بإنشاء الزيارات (منع المعلمين من إنشاء زيارات)
$allowed_roles = ['Admin', 'Director', 'Academic Deputy', 'Supervisor', 'Subject Coordinator'];
$current_user_role = $_SESSION['role_name'] ?? '';

if (!in_array($current_user_role, $allowed_roles)) {
    header('Location: index.php?error=' . urlencode('ليس لديك صلاحية لإنشاء زيارات صفية'));
    exit;
}

// الحصول على بيانات المستخدم الحالي
$current_user_school_id = $_SESSION['school_id'] ?? null;
$current_user_subject_id = $_SESSION['subject_id'] ?? null;
$current_user_id = $_SESSION['user_id'] ?? null;
$is_coordinator = ($current_user_role === 'Subject Coordinator');

$error_message = '';
$success_message = '';
$visit_id = null;

// معالجة إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // جلب البيانات من النموذج
        $school_id = $_POST['school_id'] ?? null;
        $teacher_id = $_POST['teacher_id'] ?? null;
        $subject_id = $_POST['subject_id'] ?? null;
        $grade_id = $_POST['grade_id'] ?? null;
        $section_id = $_POST['section_id'] ?? null;
        $visitor_type_id = $_POST['visitor_type_id'] ?? null;
        $visitor_person_id = $_POST['visitor_person_id'] ?? null;
        $visit_date = $_POST['visit_date'] ?? null;
        $visit_type = $_POST['visit_type'] ?? 'full';
        $attendance_type = $_POST['attendance_type'] ?? 'physical';
        $has_lab = isset($_POST['has_lab']) && $_POST['has_lab'] == '1' ? 1 : 0;
        $topic = $_POST['topic'] ?? '';
        
        // جلب بيانات التقييم
        $general_notes = $_POST['general_notes'] ?? '';
        $recommendation_notes = $_POST['recommendation_notes'] ?? '';
        $appreciation_notes = $_POST['appreciation_notes'] ?? '';
        $total_score = $_POST['total_score'] ?? 0;
        
        // جلب level_id من الصف المختار
        if ($grade_id) {
            $grade_info = query_row("SELECT level_id FROM grades WHERE id = ?", [$grade_id]);
            $level_id = $grade_info ? $grade_info['level_id'] : 1; // افتراضي 1 إذا لم يوجد
        } else {
            $level_id = 1; // قيمة افتراضية
        }
        
        // التحقق من البيانات الأساسية
        if (!$school_id || !$teacher_id || !$subject_id || !$visit_date || !$visitor_type_id || !$visitor_person_id) {
            throw new Exception("جميع الحقول الأساسية مطلوبة: المدرسة، المعلم، المادة، تاريخ الزيارة، نوع الزائر، اسم الزائر.");
        }
        
        // التأكد من أن المعرفات صحيحة
        if (!is_numeric($school_id) || !is_numeric($teacher_id) || !is_numeric($subject_id) || 
            !is_numeric($visitor_type_id) || !is_numeric($visitor_person_id)) {
            throw new Exception("قيم المعرفات يجب أن تكون أرقام صحيحة.");
        }
        
        // جلب العام الدراسي الحالي
        $current_academic_year = query_row("SELECT * FROM academic_years ORDER BY id DESC LIMIT 1");
        $academic_year_id = $current_academic_year ? $current_academic_year['id'] : 1;
        
        // إدراج الزيارة في جدول visits
        $visit_sql = "
            INSERT INTO visits (
                school_id, teacher_id, subject_id, grade_id, section_id, level_id, 
                visitor_type_id, visitor_person_id, visit_date, academic_year_id,
                visit_type, attendance_type, has_lab, topic, general_notes, 
                recommendation_notes, appreciation_notes, total_score,
                created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
            )
        ";
        
        execute($visit_sql, [
            $school_id, $teacher_id, $subject_id, $grade_id, $section_id, $level_id,
            $visitor_type_id, $visitor_person_id, $visit_date, $academic_year_id,
            $visit_type, $attendance_type, $has_lab, $topic, $general_notes,
            $recommendation_notes, $appreciation_notes, $total_score
        ]);
        
        $visit_id = last_insert_id();
        
        // حفظ تقييمات المؤشرات
        $indicators_saved = 0;
        if ($visit_id) {
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'indicator_') === 0) {
                    $indicator_id = str_replace('indicator_', '', $key);
                
                    // التعامل مع القيم الجديدة للتقييم
                    if ($value === '' || $value === null) {
                        $score = null; // NULL للمؤشرات غير المقاسة
                    } else {
                        $score = intval($value); // 0, 1, 2, 3
                        // التأكد من أن النقاط صحيحة
                        if ($score < 0 || $score > 3) {
                            continue; // تجاهل القيم غير الصحيحة
                        }
                    }
                    
                    // جلب التوصية المختارة والتوصية المخصصة
                    $recommendation_id = null;
                    $custom_recommendation = null;
                    
                    // التوصية الجاهزة
                    if (isset($_POST['recommend_' . $indicator_id]) && !empty($_POST['recommend_' . $indicator_id])) {
                        $recommendation_id = intval($_POST['recommend_' . $indicator_id]);
                    }
                    
                    // التوصية المخصصة
                    if (isset($_POST['custom_recommend_' . $indicator_id]) && !empty($_POST['custom_recommend_' . $indicator_id])) {
                        $custom_recommendation = trim($_POST['custom_recommend_' . $indicator_id]);
                    }
                    
                    $eval_sql = "
                        INSERT INTO visit_evaluations (visit_id, indicator_id, score, recommendation_id, custom_recommendation, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                    ";
                    
                    execute($eval_sql, [$visit_id, $indicator_id, $score, $recommendation_id, $custom_recommendation]);
                    $indicators_saved++;
                }
            }
        }
        
        if ($visit_id) {
            // تحديد ما إذا كانت المادة إنجليزية للرسالة
            $is_english_subject = false;
            if ($subject_id) {
                $subject_info = query_row("SELECT name FROM subjects WHERE id = ?", [$subject_id]);
                if ($subject_info) {
                    $subject_name = $subject_info['name'];
                    $is_english_subject = preg_match('/(english|انج|إنج|الإنج|الانجليزية|الإنجليزية)/i', $subject_name);
                }
            }
            
            if ($is_english_subject) {
                $success_message = "Evaluation saved successfully! (Visit ID: " . $visit_id . ", " . $indicators_saved . " indicators saved)<br>You will be redirected to the visit details page in 2 seconds... <a href='view_visit.php?id=" . $visit_id . "' class='underline text-blue-600 hover:text-blue-800'>Click here to go now</a>";
            } else {
                $success_message = "تم حفظ التقييم بنجاح! (معرف الزيارة: " . $visit_id . "، تم حفظ " . $indicators_saved . " مؤشر)<br>سيتم تحويلك إلى صفحة معاينة الزيارة خلال ثانيتين... <a href='view_visit.php?id=" . $visit_id . "' class='underline text-blue-600 hover:text-blue-800'>اضغط هنا للذهاب الآن</a>";
            }
            // التحويل إلى صفحة معاينة الزيارة بعد 2 ثانية
            header("refresh:2;url=view_visit.php?id=" . $visit_id);
        } else {
            $success_message = "تم حفظ التقييم بنجاح! (تم حفظ " . $indicators_saved . " مؤشر)";
        }
        
    } catch (Exception $e) {
        $error_message = "خطأ في حفظ التقييم: " . $e->getMessage();
        
        // إضافة معلومات إضافية للتشخيص
        if (strpos($e->getMessage(), 'level_id') !== false) {
            $error_message .= " (تأكد من اختيار الصف بشكل صحيح)";
        }
        if (strpos($e->getMessage(), 'Foreign key constraint') !== false) {
            $error_message .= " (تأكد من صحة البيانات المرجعية)";
        }
    }
}

try {
    // جلب البيانات الأساسية
    $schools = query("SELECT * FROM schools ORDER BY name");
    
    // جلب المواد حسب صلاحيات المستخدم
    if ($is_coordinator && $current_user_subject_id) {
        // منسق المادة يرى مادته فقط
        $subjects = query("SELECT * FROM subjects WHERE id = ? ORDER BY name", [$current_user_subject_id]);
    } else {
        // باقي المستخدمين يرون جميع المواد
        $subjects = query("SELECT * FROM subjects ORDER BY name");
    }
    
    $grades = query("SELECT * FROM grades ORDER BY level_id, id");
    
    // جلب أنواع الزوار حسب صلاحيات المستخدم
    if ($is_coordinator) {
        // منسق المادة يرى نفسه والموجه فقط
        $visitor_types = query("
            SELECT id, name, name_en 
            FROM visitor_types 
            WHERE name IN ('منسق المادة', 'موجه المادة') 
            ORDER BY name
        ");
    } else {
        // باقي المستخدمين يرون جميع أنواع الزوار
        $visitor_types = query("SELECT id, name, name_en FROM visitor_types ORDER BY name");
    }
    
    $academic_years = query("SELECT * FROM academic_years ORDER BY id DESC");
    
    // جلب معايير التقييم مع الترجمات
    $evaluation_domains = query("SELECT id, name, name_en, description, description_en, weight, sort_order FROM evaluation_domains ORDER BY id");
    $evaluation_indicators = query("SELECT id, domain_id, name, name_en, description, description_en, weight, sort_order FROM evaluation_indicators ORDER BY domain_id, id");
    
    // تنظيم المؤشرات حسب المعايير
    $indicators_by_domain = [];
    foreach ($evaluation_indicators as $indicator) {
        $indicators_by_domain[$indicator['domain_id']][] = $indicator;
    }
    
    // تحديد المدرسة الافتراضية (أول مدرسة)
    $default_school_id = !empty($schools) ? $schools[0]['id'] : null;
    
    // تحديد ما إذا كانت المادة المختارة إنجليزية
    $subject_is_english = false;
    $selected_subject_name = '';
    
    // فحص معامل اللغة في URL أولاً
    if (isset($_GET['lang']) && $_GET['lang'] === 'en') {
        $subject_is_english = true;
    }
    
    // أو فحص المادة المختارة
    if (isset($_POST['subject_id']) || isset($_GET['subject_id'])) {
        $selected_subject_id = $_POST['subject_id'] ?? $_GET['subject_id'];
        foreach ($subjects as $s) {
            if ((string)$s['id'] === (string)$selected_subject_id) {
                $selected_subject_name = $s['name'];
                // فحص ما إذا كانت المادة إنجليزية (إلا إذا تم فرض اللغة من URL)
                $is_english_subject = preg_match('/(english|انج|إنج|الإنج|الانجليزية|الإنجليزية)/i', $s['name']);
                
                // إذا لم يتم فرض اللغة من URL، استخدم نوع المادة
                if (!isset($_GET['lang'])) {
                    $subject_is_english = $is_english_subject;
                }
                break;
            }
        }
    } else {
        // إذا لم تكن هناك مادة مختارة، تحقق من وجود مادة إنجليزية
    $has_english_subject = false;
        foreach ($subjects as $s) {
            if (preg_match('/(english|انج|إنج|الإنج|الانجليزية|الإنجليزية)/i', $s['name'])) {
                $has_english_subject = true;
                break;
            }
        }
    }
    
    // الآن الترجمات محفوظة في قاعدة البيانات - لا حاجة للمصفوفات اليدوية
    
    // إضافة ترجمة النصوص
    $texts = [
        'form_title' => $subject_is_english ? 'Classroom Visit Evaluation Form' : 'نموذج تقييم زيارة صفية',
        'form_description' => $subject_is_english ? 'Enter visit details and evaluate teaching performance' : 'أدخل بيانات الزيارة وقم بتقييم الأداء التدريسي',
        'basic_data' => $subject_is_english ? 'Basic Information' : 'البيانات الأساسية',
        'school' => $subject_is_english ? 'School:' : 'المدرسة:',
        'subject' => $subject_is_english ? 'Subject:' : 'المادة:',
        'teacher' => $subject_is_english ? 'Teacher:' : 'المعلم:',
        'grade' => $subject_is_english ? 'Grade:' : 'الصف:',
        'section' => $subject_is_english ? 'Section:' : 'الشعبة:',
        'visit_date' => $subject_is_english ? 'Visit Date:' : 'تاريخ الزيارة:',
        'visitor_data' => $subject_is_english ? 'Visitor Information' : 'بيانات الزائر',
        'visitor_type' => $subject_is_english ? 'Visitor Type:' : 'نوع الزائر:',
        'visitor_name' => $subject_is_english ? 'Visitor Name:' : 'اسم الزائر:',
        'visit_settings' => $subject_is_english ? 'Visit Settings' : 'إعدادات الزيارة',
        'visit_type' => $subject_is_english ? 'Visit Type:' : 'نوع الزيارة:',
        'attendance_type' => $subject_is_english ? 'Attendance Method:' : 'طريقة الحضور:',
        'lesson_topic' => $subject_is_english ? 'Lesson Topic:' : 'موضوع الدرس:',
        'additional_settings' => $subject_is_english ? 'Additional Settings:' : 'إعدادات إضافية:',
        'add_lab_evaluation' => $subject_is_english ? 'Add laboratory evaluation (Science subjects)' : 'إضافة تقييم المعمل (خاص بمادة العلوم)',
        'evaluation_form' => $subject_is_english ? 'Evaluation Form' : 'نموذج التقييم',
        'instructions' => $subject_is_english ? 'Instructions: Choose the appropriate evaluation for each indicator:' : 'تعليمات: اختر التقييم المناسب لكل مؤشر:',
        'not_measured' => $subject_is_english ? 'Not measured' : 'لم يتم قياسه',
        'evidence_limited' => $subject_is_english ? 'Evidence is not available or limited' : 'الأدلة غير متوفرة أو محدودة',
        'some_evidence' => $subject_is_english ? 'Some evidence is available' : 'تتوفر بعض الأدلة',
        'most_evidence' => $subject_is_english ? 'Most evidence is available' : 'تتوفر معظم الأدلة',
        'complete_evidence' => $subject_is_english ? 'Evidence is complete and effective' : 'الأدلة مستكملة وفاعلة',
        'ready_recommendations' => $subject_is_english ? 'Ready recommendations:' : 'التوصيات الجاهزة:',
        'select_recommendation' => $subject_is_english ? 'Select ready recommendation...' : 'اختر توصية جاهزة...',
        'custom_recommendation' => $subject_is_english ? 'Custom recommendation:' : 'توصية مخصصة:',
        'write_custom_recommendation' => $subject_is_english ? 'Write a custom recommendation for this indicator...' : 'اكتب توصية مخصصة لهذا المؤشر...',
        'general_notes' => $subject_is_english ? 'General Notes:' : 'ملاحظات عامة:',
        'enter_general_notes' => $subject_is_english ? 'Enter your general notes here...' : 'أدخل ملاحظاتك العامة هنا...',
        'recommend_teacher' => $subject_is_english ? 'I recommend:' : 'أوصي بـ:',
        'enter_recommendations' => $subject_is_english ? 'Enter recommendations here...' : 'أدخل التوصيات هنا...',
        'thank_teacher' => $subject_is_english ? 'I thank the teacher for:' : 'أشكر المعلم على:',
        'enter_appreciation' => $subject_is_english ? 'Enter appreciation points here...' : 'أدخل نقاط الشكر والتقدير هنا...',
        'total_score' => $subject_is_english ? 'Total Score:' : 'إجمالي النقاط:',
        'percentage' => $subject_is_english ? 'Percentage:' : 'النسبة المئوية:',
        'save_evaluation' => $subject_is_english ? 'Save Evaluation' : 'حفظ التقييم',
        'select_school' => $subject_is_english ? 'Select school...' : 'اختر المدرسة...',
        'select_subject' => $subject_is_english ? 'Select subject...' : 'اختر المادة...',
        'select_teacher' => $subject_is_english ? 'Select teacher...' : 'اختر المعلم...',
        'select_grade' => $subject_is_english ? 'Select grade...' : 'اختر الصف...',
        'select_section' => $subject_is_english ? 'Select section...' : 'اختر الشعبة...',
        'select_visitor_type' => $subject_is_english ? 'Select visitor type...' : 'اختر نوع الزائر...',
        'select_visitor_name' => $subject_is_english ? 'Select visitor name...' : 'اختر اسم الزائر...',
        'full_visit' => $subject_is_english ? 'Full evaluation' : 'تقييم كامل',
        'partial_visit' => $subject_is_english ? 'Partial evaluation' : 'تقييم جزئي',
        'physical_attendance' => $subject_is_english ? 'In-person' : 'حضوري',
        'virtual_attendance' => $subject_is_english ? 'Virtual' : 'افتراضي',
        'enter_topic' => $subject_is_english ? 'Enter lesson topic...' : 'اكتب موضوع الدرس...'
    ];
    
} catch (Exception $e) {
    $error_message = "خطأ في جلب البيانات: " . $e->getMessage();
}
?>

<?php
// تضمين ملف رأس الصفحة
require_once 'includes/header.php';
?>
    <style>
        .loading-spinner {
            border: 2px solid #f3f4f6;
            border-top: 2px solid #3b82f6;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

<!-- المحتوى الرئيسي -->
<div class="main-content">
    <div class="container mx-auto p-6">
        
        
        <!-- العنوان -->
        <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">📋 <?= $texts['form_title'] ?></h1>
            <p class="text-gray-600"><?= $texts['form_description'] ?></p>
    </div>
    
        <!-- الرسائل -->
        <?php if ($error_message): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                <strong>خطأ:</strong> <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>
    
        <?php if ($success_message): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
                <strong>نجح:</strong> <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <!-- النموذج الرئيسي -->
        <form method="POST" id="evaluation-form" class="space-y-6">
            
            <!-- البيانات الأساسية -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-lg font-semibold mb-4">📋 <?= $texts['basic_data'] ?></h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    
                    <!-- المدرسة -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">🏫 <?= $texts['school'] ?></label>
                        <select id="school_id" name="school_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <?php foreach ($schools as $school): ?>
                                <option value="<?= $school['id'] ?>" <?= ($school['id'] == $default_school_id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($school['name']) ?>
                                </option>
                            <?php endforeach; ?>
                </select>
            </div>

            <!-- المادة -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">📚 <?= $texts['subject'] ?></label>
                        <?php if ($is_coordinator && $current_user_subject_id): ?>
                            <!-- منسق المادة: المادة محددة مسبقاً مع إمكانية القراءة للـ JavaScript -->
                            <select id="subject_id" name="subject_id" class="w-full border border-gray-300 rounded-md px-3 py-2 bg-blue-50 text-blue-800 font-medium cursor-not-allowed" onclick="return false;" onkeydown="return false;">
                                <option value="<?= $current_user_subject_id ?>" selected>
                                    <?= htmlspecialchars($subjects[0]['name'] ?? 'مادة غير محددة') ?>
                                    <span class="text-xs">(مادة المنسق)</span>
                                </option>
                            </select>
                            <p class="text-xs text-blue-600 mt-1">
                                <i class="fas fa-info-circle ml-1"></i>
                                هذه مادتك كمنسق - لا يمكن تغييرها
                            </p>
                        <?php else: ?>
                            <!-- باقي المستخدمين: يمكنهم اختيار المادة -->
                            <select id="subject_id" name="subject_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                <option value=""><?= $texts['select_subject'] ?></option>
                                <?php foreach ($subjects as $subject): ?>
                                    <?php 
                                    $is_selected = '';
                                    if (isset($_GET['subject_id']) && $_GET['subject_id'] == $subject['id']) {
                                        $is_selected = 'selected';
                                    }
                                    ?>
                                    <option value="<?= $subject['id'] ?>" <?= $is_selected ?>><?= htmlspecialchars($subject['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
            </div>

            <!-- المعلم -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">👨‍🏫 <?= $texts['teacher'] ?></label>
                        <select id="teacher_id" name="teacher_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value=""><?= $texts['select_teacher'] ?></option>
                </select>
            </div>

            <!-- الصف -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">📖 <?= $texts['grade'] ?></label>
                        <select id="grade_id" name="grade_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value=""><?= $texts['select_grade'] ?></option>
                    <?php foreach ($grades as $grade): ?>
                                <option value="<?= $grade['id'] ?>"><?= htmlspecialchars($grade['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- الشعبة -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">👥 <?= $texts['section'] ?></label>
                        <select id="section_id" name="section_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value=""><?= $texts['select_section'] ?></option>
                </select>
            </div>

            <!-- تاريخ الزيارة -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">📅 <?= $texts['visit_date'] ?></label>
                        <input type="date" id="visit_date" name="visit_date" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>

            </div>
            </div>

            <!-- بيانات الزائر -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-lg font-semibold mb-4">👤 <?= $texts['visitor_data'] ?></h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <!-- نوع الزائر -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            🧑‍💼 <?= $texts['visitor_type'] ?>
                            <?php if ($is_coordinator): ?>
                                <span class="text-xs text-blue-600">(منسق المادة أو موجه المادة فقط)</span>
                            <?php endif; ?>
                        </label>
                        <select id="visitor_type_id" name="visitor_type_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value=""><?= $texts['select_visitor_type'] ?></option>
                            <?php foreach ($visitor_types as $type): ?>
                                <option value="<?= $type['id'] ?>">
                                    <?= htmlspecialchars($subject_is_english && !empty($type['name_en']) ? $type['name_en'] : $type['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- اسم الزائر -->
                <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">👤 اسم الزائر:</label>
                        <div id="visitor-name-container" class="w-full border border-gray-300 rounded-md px-3 py-2 bg-gray-50 min-h-[42px] flex items-center">
                            <span class="text-gray-500 text-sm">اختر نوع الزائر أولاً</span>
        </div>
                        <input type="hidden" id="visitor_person_id" name="visitor_person_id" value="">
</div>

            </div>
    </div>

            <!-- إعدادات الزيارة -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-lg font-semibold mb-4">⚙️ إعدادات الزيارة</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <!-- نوع الزيارة -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">📝 نوع الزيارة:</label>
                        <select id="visit_type" name="visit_type" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="full">زيارة كاملة</option>
                            <option value="partial">زيارة جزئية</option>
                        </select>
                </div>

                    <!-- طريقة الحضور -->
                <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">🎯 طريقة الحضور:</label>
                        <select id="attendance_type" name="attendance_type" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="physical">حضوري</option>
                            <option value="virtual">افتراضي</option>
                        </select>
        </div>
        
                    <!-- موضوع الدرس -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">📖 <?= $texts['lesson_topic'] ?></label>
                        <input type="text" id="topic" name="topic" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="<?= $texts['enter_topic'] ?>">
        </div>
        
                    <!-- إعدادات إضافية -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">⚙️ <?= $texts['additional_settings'] ?></label>
                        <div class="flex items-center p-3 border border-gray-300 rounded-md bg-gray-50">
                            <input type="checkbox" id="has_lab" name="has_lab" value="1" class="mr-3 text-blue-500 focus:ring-blue-500">
                            <label for="has_lab" class="text-sm text-gray-700 cursor-pointer">🧪 <?= $texts['add_lab_evaluation'] ?></label>
                </div>
            </div>
            
                </div>
            </div>
            
            <!-- نموذج التقييم -->
            <div id="evaluation-section" class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-lg font-semibold mb-4">📋 <?= $texts['evaluation_form'] ?></h2>
                
                <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-sm text-blue-800">
                        <strong><?= $texts['instructions'] ?></strong>
                        <br><strong>• <?= $texts['not_measured'] ?></strong> | <strong>• <?= $texts['evidence_limited'] ?></strong> | <strong>• <?= $texts['some_evidence'] ?></strong> | <strong>• <?= $texts['most_evidence'] ?></strong> | <strong>• <?= $texts['complete_evidence'] ?></strong>
                    </p>
    </div>

    <?php 
                $domain_colors = [
                    1 => ['bg' => 'bg-blue-50', 'border' => 'border-blue-200', 'text' => 'text-blue-800', 'accent' => 'border-r-blue-500'],
                    2 => ['bg' => 'bg-green-50', 'border' => 'border-green-200', 'text' => 'text-green-800', 'accent' => 'border-r-green-500'],
                    3 => ['bg' => 'bg-purple-50', 'border' => 'border-purple-200', 'text' => 'text-purple-800', 'accent' => 'border-r-purple-500'],
                    4 => ['bg' => 'bg-orange-50', 'border' => 'border-orange-200', 'text' => 'text-orange-800', 'accent' => 'border-r-orange-500'],
                    5 => ['bg' => 'bg-red-50', 'border' => 'border-red-200', 'text' => 'text-red-800', 'accent' => 'border-r-red-500']
                ];
                ?>
                <?php foreach ($evaluation_domains as $domain): ?>
                    <?php 
                    // إخفاء جزء المعمل إذا لم يتم تفعيله
                    if ($domain['id'] == 5) { // جزء خاص بمادة العلوم (النشاط العملي)
                        echo '<div id="lab-evaluation-section" style="display: none;">'; // مخفي بشكل افتراضي
                    }
                    
                    $colors = $domain_colors[$domain['id']] ?? $domain_colors[1]; 
                    ?>
                    <div class="mb-6 p-4 border <?= $colors['border'] ?> <?= $colors['bg'] ?> border-r-4 <?= $colors['accent'] ?> rounded-lg shadow-sm">
                        <h3 class="text-md font-semibold <?= $colors['text'] ?> mb-3">
                            <?= htmlspecialchars($subject_is_english && !empty($domain['name_en']) ? $domain['name_en'] : $domain['name']) ?>
                        </h3>
                        
                        <?php if (isset($indicators_by_domain[$domain['id']])): ?>
                            <div class="space-y-3">
                                <?php foreach ($indicators_by_domain[$domain['id']] as $indicator): ?>
                                    <div class="p-3 bg-gray-50 rounded border">
                                        <!-- نص المؤشر -->
                                        <div class="mb-3">
                                            <label class="text-sm font-medium text-gray-800">
                                                <?= htmlspecialchars($subject_is_english && !empty($indicator['name_en']) ? $indicator['name_en'] : $indicator['name']) ?>
                                            </label>
            </div>
            
                                        <!-- خيارات التقييم -->
                                        <div class="flex gap-2 flex-wrap">
                <?php 
                                            $score_options = [
                                                '' => ['label' => $texts['not_measured'], 'color' => 'text-gray-500', 'bg' => 'bg-gray-100'],
                                                '0' => ['label' => $texts['evidence_limited'], 'color' => 'text-gray-700', 'bg' => 'bg-gray-50'],
                                                '1' => ['label' => $texts['some_evidence'], 'color' => 'text-red-700', 'bg' => 'bg-red-50'],
                                                '2' => ['label' => $texts['most_evidence'], 'color' => 'text-blue-700', 'bg' => 'bg-blue-50'],
                                                '3' => ['label' => $texts['complete_evidence'], 'color' => 'text-green-700', 'bg' => 'bg-green-50']
                                            ];
                                            ?>
                                            <?php foreach ($score_options as $value => $option): ?>
                                                <label class="flex flex-col items-center cursor-pointer p-2 border border-gray-200 rounded hover:shadow-sm transition-all min-w-[120px] <?= $option['bg'] ?>">
                                                    <input type="radio" 
                                                           name="indicator_<?= $indicator['id'] ?>" 
                                                           value="<?= $value ?>" 
                                                           class="mb-2 text-blue-500 focus:ring-blue-500">
                                                    <span class="text-xs text-center <?= $option['color'] ?> font-medium leading-tight"><?= $option['label'] ?></span>
                                        </label>
                                            <?php endforeach; ?>
                        </div>
                        
                                        <!-- التوصيات الجاهزة -->
                                        <div class="mt-3">
                                            <label class="text-xs font-medium text-gray-600 mb-2 block"><?= $texts['ready_recommendations'] ?></label>
                        <?php 
                                            // جلب التوصيات المتعلقة بهذا المؤشر
                                            $indicator_recommendations = query("SELECT * FROM recommendations WHERE indicator_id = ? ORDER BY text", [$indicator['id']]);
                                            ?>
                                            
                                            <?php if (!empty($indicator_recommendations)): ?>
                                                <select name="recommend_<?= $indicator['id'] ?>" 
                                                        class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                    <option value=""><?= $texts['select_recommendation'] ?></option>
                                                    <?php foreach ($indicator_recommendations as $rec): ?>
                                                        <option value="<?= $rec['id'] ?>">
                                                            <?= htmlspecialchars($subject_is_english && !empty($rec['text_en']) ? $rec['text_en'] : $rec['text']) ?>
                                                        </option>
                                    <?php endforeach; ?>
                                                </select>
                                            <?php else: ?>
                                                <p class="text-xs text-gray-500 italic">
                                                    <?= $subject_is_english ? 'No ready recommendations for this indicator' : 'لا توجد توصيات جاهزة لهذا المؤشر' ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <!-- حقل التوصية المخصصة -->
                                            <div class="mt-2">
                                                <label class="text-xs font-medium text-gray-600 block mb-1"><?= $texts['custom_recommendation'] ?></label>
                                                <textarea name="custom_recommend_<?= $indicator['id'] ?>" 
                                                          rows="2" 
                                                          class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                                          placeholder="<?= $texts['write_custom_recommendation'] ?>"></textarea>
                                </div>
                            </div>
                            </div>
                                <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php 
                    // إغلاق div المعمل
                    if ($domain['id'] == 5) {
                        echo '</div>'; // إغلاق lab-evaluation-section
                    }
                    ?>
    <?php endforeach; ?>

                <!-- ملاحظات وتوصيات عامة -->
                <div class="mt-6 space-y-4">
            <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">💭 <?= $texts['general_notes'] ?></label>
                        <textarea name="general_notes" id="general_notes" rows="3" 
                                  class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="<?= $texts['enter_general_notes'] ?>"></textarea>
            </div>
            
            <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">🎯 <?= $texts['recommend_teacher'] ?></label>
                        <textarea name="recommendation_notes" id="recommendation_notes" rows="4" 
                                  class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="<?= $texts['enter_recommendations'] ?>"></textarea>
        </div>
        
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">⭐ <?= $texts['thank_teacher'] ?></label>
                        <textarea name="appreciation_notes" id="appreciation_notes" rows="4" 
                                  class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="<?= $texts['enter_appreciation'] ?>"></textarea>
        </div>
    </div>

                <!-- إجمالي النقاط -->
                <div class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-semibold text-green-800"><?= $texts['total_score'] ?></span>
                        <span id="total-score" class="text-2xl font-bold text-green-600">0</span>
                        <input type="hidden" name="total_score" id="total_score_input" value="0">
                </div>
                    <div class="text-sm text-green-600 mt-1">
                        <?= $texts['percentage'] ?> <span id="percentage-score">0%</span>
                </div>
            </div>
            </div>
            
            <!-- أزرار التحكم -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex gap-4">
                    <button type="submit" onclick="return validateBeforeSubmit()" class="bg-blue-500 text-white px-6 py-2 rounded-md hover:bg-blue-600 transition-colors flex items-center gap-2">
                        💾 <?= $texts['save_evaluation'] ?>
                    </button>
            </div>
        </div>

        </form>

        </div>
    </div>
<!-- نهاية المحتوى الرئيسي -->

<script>
// متغيرات عامة
let languageUpdateInProgress = false;


// دالة تحديث اسم الزائر - نسخة مبسطة جداً
function updateVisitorName() {
    
    // الحصول على العناصر
    const visitorTypeSelect = document.getElementById('visitor_type_id');
    const visitorNameContainer = document.getElementById('visitor-name-container');
    const visitorPersonIdInput = document.getElementById('visitor_person_id');
    const subjectSelect = document.getElementById('subject_id');
    const schoolSelect = document.getElementById('school_id');
    
    // التحقق من وجود العناصر
    if (!visitorTypeSelect) {
        return;
    }
    
    if (!visitorNameContainer) {
        return;
    }
    
    
    // التحقق من اختيار نوع الزائر
    if (!visitorTypeSelect.value) {
        visitorNameContainer.innerHTML = '<span class="text-gray-500 text-sm">اختر نوع الزائر أولاً</span>';
        if (visitorPersonIdInput) visitorPersonIdInput.value = '';
        return;
    }
    
    
    // بناء رابط الـ API
    let apiUrl = `api/get_visitor_name.php?visitor_type_id=${visitorTypeSelect.value}`;
    
    if (subjectSelect && subjectSelect.value) {
        apiUrl += `&subject_id=${subjectSelect.value}`;
    }
    
    if (schoolSelect && schoolSelect.value) {
        apiUrl += `&school_id=${schoolSelect.value}`;
    }
    
    
    // إظهار رسالة التحميل
    visitorNameContainer.innerHTML = '<div class="flex items-center gap-2 text-blue-600"><div class="loading-spinner"></div><span class="text-sm">جاري التحميل...</span></div>';
    
    // إرسال الطلب
    
    fetch(apiUrl)
        .then(response => {
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return response.json();
        })
        .then(data => {
            
            if (data.success && data.visitors && data.visitors.length > 0) {
                
                // إنشاء قائمة منسدلة
                const select = document.createElement('select');
                select.id = 'visitor_person_select';
                select.name = 'visitor_person_select';
                select.className = 'w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500';
                select.required = true;
                
                // الخيار الافتراضي
                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = 'اختر اسم الزائر...';
                select.appendChild(defaultOption);
                
                // إضافة الزوار
                data.visitors.forEach(visitor => {
                    const option = document.createElement('option');
                    option.value = visitor.id;
                    option.textContent = visitor.name;
                    select.appendChild(option);
                });
                
                // تحديث الحاوي
                visitorNameContainer.innerHTML = '';
                visitorNameContainer.appendChild(select);
                
                
                // إضافة مستمع للاختيار
                select.addEventListener('change', function() {
                    if (visitorPersonIdInput) {
                        visitorPersonIdInput.value = this.value;
                    }
                });
                
            } else if (data.success && (!data.visitors || data.visitors.length === 0)) {
                visitorNameContainer.innerHTML = '<span class="text-amber-600 text-sm">لا توجد زوار متاحين لهذا النوع</span>';
                if (visitorPersonIdInput) visitorPersonIdInput.value = '';
                
            } else {
                console.log(`❌ خطأ من الخادم: ${data.message || 'خطأ غير معروف'}`);
                visitorNameContainer.innerHTML = `<span class="text-red-600 text-sm">خطأ: ${data.message || 'فشل في جلب البيانات'}</span>`;
                if (visitorPersonIdInput) visitorPersonIdInput.value = '';
            }
        })
        .catch(error => {
            console.log(`❌ خطأ في الشبكة: ${error.message}`);
            visitorNameContainer.innerHTML = '<span class="text-red-600 text-sm">خطأ في الاتصال بالخادم</span>';
            if (visitorPersonIdInput) visitorPersonIdInput.value = '';
        });
}

// دالة تحميل المعلمين
function loadTeachers() {
    const schoolId = document.getElementById('school_id').value;
    const subjectId = document.getElementById('subject_id').value;
    const teacherSelect = document.getElementById('teacher_id');
    
    console.log(`🔍 loadTeachers: schoolId=${schoolId}, subjectId=${subjectId}`);
    
    if (!schoolId || !subjectId) {
        console.log('❌ المدرسة أو المادة غير محددة');
        teacherSelect.innerHTML = '<option value="">اختر المعلم...</option>';
        return;
    }
    
    console.log(`🔄 تحميل المعلمين للمدرسة ${schoolId} والمادة ${subjectId}`);
    
    fetch(`api/get_teachers_by_school_subject.php?school_id=${schoolId}&subject_id=${subjectId}`)
        .then(response => {
            console.log(`📡 استجابة API: ${response.status} ${response.statusText}`);
            return response.json();
        })
        .then(data => {
            console.log('📊 بيانات API:', data);
            teacherSelect.innerHTML = '<option value="">اختر المعلم...</option>';
            
            if (data.success && data.teachers && data.teachers.length > 0) {
                data.teachers.forEach(teacher => {
                    const option = document.createElement('option');
                    option.value = teacher.id;
                    option.textContent = teacher.name;
                    teacherSelect.appendChild(option);
                });
                console.log(`✅ تم تحميل ${data.teachers.length} معلم بنجاح`);
            } else if (data.success && (!data.teachers || data.teachers.length === 0)) {
                console.log('⚠️ لا توجد معلمين متاحين لهذه المدرسة والمادة');
            } else {
                console.log(`❌ خطأ من API: ${data.message || 'خطأ غير معروف'}`);
            }
        })
        .catch(error => {
            console.log(`❌ خطأ في الشبكة أو تحليل JSON: ${error.message}`);
            teacherSelect.innerHTML = '<option value="">خطأ في تحميل المعلمين</option>';
        });
}

// دالة تحميل الشعب
function loadSections() {
    const schoolId = document.getElementById('school_id').value;
    const gradeId = document.getElementById('grade_id').value;
    const sectionSelect = document.getElementById('section_id');
    
    console.log(`🔍 loadSections: schoolId=${schoolId}, gradeId=${gradeId}`);
    
    if (!schoolId || !gradeId) {
        console.log('❌ المدرسة أو الصف غير محدد');
        sectionSelect.innerHTML = '<option value="">اختر الشعبة...</option>';
        return;
    }
    
    console.log(`🔄 تحميل الشعب للمدرسة ${schoolId} والصف ${gradeId}`);
    
    fetch(`api/get_sections_by_school_grade.php?school_id=${schoolId}&grade_id=${gradeId}`)
        .then(response => response.json())
        .then(data => {
            sectionSelect.innerHTML = '<option value="">اختر الشعبة...</option>';
            
            if (data.success && data.sections) {
                data.sections.forEach(section => {
                    const option = document.createElement('option');
                    option.value = section.id;
                    option.textContent = section.name;
                    sectionSelect.appendChild(option);
                });
                console.log(`✅ تم تحميل ${data.sections.length} شعبة`);
            }
        })
        .catch(error => {
            console.log(`❌ خطأ في تحميل الشعب: ${error.message}`);
        });
}


// دالة حساب إجمالي النقاط
function calculateTotal() {
    
    let totalScore = 0;
    let totalIndicators = 0;
    
    // جمع جميع المؤشرات المقيمة
    const radioGroups = document.querySelectorAll('input[type="radio"][name^="indicator_"]');
    const indicatorNames = new Set();
    
    radioGroups.forEach(radio => {
        indicatorNames.add(radio.name);
    });
    
    
    // حساب النقاط لكل مؤشر
    indicatorNames.forEach(indicatorName => {
        const selectedRadio = document.querySelector(`input[name="${indicatorName}"]:checked`);
        if (selectedRadio) {
            const value = selectedRadio.value;
            if (value === '' || value === null) {
                // لا نضيف للمجموع ولا للعداد
            } else {
                const score = parseInt(value);
                totalScore += score;
                totalIndicators++;
            }
        }
    });
    
    // حساب النسبة المئوية (بناءً على المؤشرات المقيمة فقط)
    const maxPossibleScore = totalIndicators * 3; // أقصى نقاط ممكنة للمؤشرات المقيمة فقط
    const percentage = totalIndicators > 0 ? Math.round((totalScore / maxPossibleScore) * 100) : 0;
    
    // تحديث العرض
    document.getElementById('total-score').textContent = totalScore;
    document.getElementById('total_score_input').value = totalScore;
    document.getElementById('percentage-score').textContent = percentage + '%';
    
    
    // تحديد مستوى الأداء
    let performanceLevel = '';
    let levelColor = '';
    
    if (percentage >= 90) {
        performanceLevel = 'ممتاز';
        levelColor = 'text-green-600';
    } else if (percentage >= 80) {
        performanceLevel = 'جيد جداً';
        levelColor = 'text-blue-600';
    } else if (percentage >= 70) {
        performanceLevel = 'جيد';
        levelColor = 'text-yellow-600';
    } else if (percentage >= 60) {
        performanceLevel = 'مقبول';
        levelColor = 'text-orange-600';
        } else {
        performanceLevel = 'يحتاج تطوير';
        levelColor = 'text-red-600';
    }
    
    console.log(`🎯 مستوى الأداء: ${performanceLevel}`);
    
    return { totalScore, percentage, performanceLevel };
}

// دالة التحقق قبل إرسال النموذج
function validateBeforeSubmit() {
    console.log('🔍 بدء التحقق من صحة البيانات قبل الإرسال...');
    
    // التحقق من البيانات الأساسية
    const requiredFields = {
        'school_id': 'المدرسة',
        'subject_id': 'المادة', 
        'teacher_id': 'المعلم',
        'grade_id': 'الصف',
        'visitor_type_id': 'نوع الزائر',
        'visitor_person_id': 'اسم الزائر',
        'visit_date': 'تاريخ الزيارة'
    };
    
    let missingFields = [];
    
    Object.entries(requiredFields).forEach(([fieldId, fieldName]) => {
        const field = document.getElementById(fieldId);
        if (!field || !field.value) {
            missingFields.push(fieldName);
            console.log(`❌ حقل مطلوب مفقود: ${fieldName}`);
        } else {
            console.log(`✅ ${fieldName}: ${field.value}`);
        }
    });
    
    if (missingFields.length > 0) {
        const message = `الحقول التالية مطلوبة:\n${missingFields.join('\n')}`;
        alert(message);
        console.log(`❌ فشل التحقق: ${missingFields.length} حقول مفقودة`);
        return false;
    }
    
    // حساب النقاط النهائية
    calculateTotal();
    
    console.log('✅ تم التحقق من جميع البيانات - جاري الإرسال...');
    return true;
}

// عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    
    // تعيين التاريخ الحالي
    document.getElementById('visit_date').value = new Date().toISOString().split('T')[0];
    
    // تحميل المعلمين للمدرسة الافتراضية إذا تم اختيار مادة
    const defaultSchoolId = document.getElementById('school_id').value;
    const subjectId = document.getElementById('subject_id').value;
    
    console.log(`🔄 تحميل الصفحة: مدرسة=${defaultSchoolId}, مادة=${subjectId}`);
    
    // تحميل المعلمين تلقائياً
    <?php if ($is_coordinator && $current_user_subject_id): ?>
        // للمنسق: تحميل معلمي مادته تلقائياً
        console.log('🔄 تحميل معلمي المادة للمنسق...');
        console.log('مادة المنسق: <?= $current_user_subject_id ?>');
        loadTeachers();
        loadSections(); // تحميل الشعب أيضاً
    <?php else: ?>
        // للمستخدمين الآخرين: تحميل حسب المدرسة والمادة المحددتين
        if (defaultSchoolId && subjectId) {
            console.log('🔄 تحميل المعلمين تلقائياً...');
            loadTeachers();
        } else if (defaultSchoolId) {
            console.log('⚠️ المدرسة محددة لكن المادة غير محددة');
        } else {
            console.log('⚠️ لا توجد مدرسة افتراضية');
        }
    <?php endif; ?>
    
    // ربط Event Listeners
    
    // نوع الزائر
    document.getElementById('visitor_type_id').addEventListener('change', function() {
        if (this.value) {
            updateVisitorName();
                    } else {
            document.getElementById('visitor-name-container').innerHTML = '<span class="text-gray-500 text-sm">اختر نوع الزائر أولاً</span>';
            document.getElementById('visitor_person_id').value = '';
        }
    });
    
    // المدرسة
    document.getElementById('school_id').addEventListener('change', function() {
        loadTeachers();
        loadSections();
        
        // تحديث قائمة الزوار إذا كان نوع الزائر محدد
        const visitorType = document.getElementById('visitor_type_id').value;
        if (visitorType) {
            updateVisitorName();
        }
    });
    
    // المادة
    document.getElementById('subject_id').addEventListener('change', function() {
        
        // تحديث اللغة (فقط عند تغيير المادة يدوياً)
        const selectedOption = this.options[this.selectedIndex];
        updateLanguage(); // إعادة تفعيل تحديث اللغة
        
        // تحميل المعلمين
        loadTeachers();
        
        // تحديث قائمة الزوار إذا كان نوع الزائر محدد
        const visitorType = document.getElementById('visitor_type_id').value;
        if (visitorType) {
            updateVisitorName();
        }
    });
    
    // الصف
    document.getElementById('grade_id').addEventListener('change', function() {
        loadSections();
    });
    
    // ربط Event Listeners لحساب النقاط التلقائي
    const evaluationRadios = document.querySelectorAll('input[type="radio"][name^="indicator_"]');
    evaluationRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            // حساب النقاط تلقائياً بعد ثانية واحدة
            setTimeout(calculateTotal, 500);
        });
    });
    
    // ربط checkbox المعمل
    const hasLabCheckbox = document.getElementById('has_lab');
    if (hasLabCheckbox) {
        hasLabCheckbox.addEventListener('change', function() {
            const labSection = document.getElementById('lab-evaluation-section');
            if (labSection) {
                if (this.checked) {
                    labSection.style.display = 'block';
                    } else {
                    labSection.style.display = 'none';
                    
                    // مسح جميع اختيارات المعمل
                    const labRadios = labSection.querySelectorAll('input[type="radio"]');
                    labRadios.forEach(radio => radio.checked = false);
                    
                    // مسح التوصيات المختارة
                    const labSelects = labSection.querySelectorAll('select');
                    labSelects.forEach(select => select.value = '');
                    
                    // مسح التوصيات المخصصة
                    const labTextareas = labSection.querySelectorAll('textarea');
                    labTextareas.forEach(textarea => textarea.value = '');
                    
                }
            }
        });
    }
    
    
    // عرض حالة اللغة الحالية
    const currentLang = new URLSearchParams(window.location.search).get('lang');
});

// دالة تحديث اللغة - مع منع الحلقة اللا نهائية
function updateLanguage() {
    // منع التنفيذ المتكرر
    if (languageUpdateInProgress) {
        console.log('🔄 تحديث اللغة في الانتظار...');
        return;
    }
    
    const subjectSelect = document.getElementById('subject_id');
    if (!subjectSelect || !subjectSelect.value) {
        console.log('❌ لم يتم اختيار مادة بعد');
        return;
    }
    
    const selectedOption = subjectSelect.options[subjectSelect.selectedIndex];
    const subjectName = selectedOption.text.toLowerCase();
    
    // فحص ما إذا كانت المادة إنجليزية باستخدام regex أقوى
    const isEnglish = /(english|انج|إنج|الإنج|الانجليزية|الإنجليزية)/i.test(subjectName);
    
    const currentLang = new URLSearchParams(window.location.search).get('lang');
    
    console.log(`🔍 فحص اللغة: المادة="${subjectName}", إنجليزية=${isEnglish}, اللغة الحالية=${currentLang || 'عربي'}`);
    
    // تحديث اللغة حسب نوع المادة
    if (isEnglish && currentLang !== 'en') {
        console.log('🔄 تغيير إلى الإنجليزية...');
        languageUpdateInProgress = true;
        
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('subject_id', subjectSelect.value);
        currentUrl.searchParams.set('lang', 'en');
        window.location.replace(currentUrl.toString());
        
    } else if (!isEnglish && currentLang === 'en') {
        console.log('🔄 تغيير إلى العربية...');
        languageUpdateInProgress = true;
        
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('subject_id', subjectSelect.value);
        currentUrl.searchParams.delete('lang');
        window.location.replace(currentUrl.toString());
    } else {
        console.log('✅ اللغة صحيحة بالفعل');
    }
}


</script>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?> 
