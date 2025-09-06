<?php
// بدء التخزين المؤقت للمخرجات - سيحل مشكلة Headers already sent
ob_start();

// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';
require_once 'includes/auth_functions.php';

// حماية الصفحة - المسموح لهم بإنشاء الزيارات
protect_page(['Admin', 'Director', 'Academic Deputy', 'Supervisor', 'Subject Coordinator']);

// الحصول على بيانات المستخدم الحالي
$current_user_role = $_SESSION['role_name'] ?? 'admin';
$current_user_school_id = $_SESSION['school_id'] ?? null;
$current_user_subject_id = $_SESSION['subject_id'] ?? null;
$current_user_id = $_SESSION['user_id'] ?? null;

// تعيين عنوان الصفحة
$page_title = 'نموذج تقييم زيارة صفية';

// معالجة النموذج إذا تم تقديمه
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_visit'])) {
    try {
        // استخراج البيانات من النموذج
        $school_id = $_POST['school_id'] ?? null;
        $teacher_id = $_POST['teacher_id'] ?? null;
        $subject_id = $_POST['subject_id'] ?? null;
        $grade_id = $_POST['grade_id'] ?? null;
        $section_id = $_POST['section_id'] ?? null;
        $level_id = $_POST['level_id'] ?? null;
        $visitor_type_id = $_POST['visitor_type_id'] ?? null;
        $visitor_person_id = $_POST['visitor_person_id'] ?? null;
        $visit_date = $_POST['visit_date'] ?? null;
        $visit_type = $_POST['visit_type'] ?? 'full';
        $attendance_type = $_POST['attendance_type'] ?? 'physical';
        $has_lab = isset($_POST['has_lab']) && $_POST['has_lab'] == '1' ? 1 : 0;
        $general_notes = $_POST['general_notes'] ?? '';
        $recommendation_notes = $_POST['recommendation_notes'] ?? '';
        $appreciation_notes = $_POST['appreciation_notes'] ?? '';
        $total_score = $_POST['total_score'] ?? 0;
        
        // التحقق من وجود البيانات الأساسية
        if (!$school_id || !$teacher_id || !$subject_id || !$visit_date || !$visitor_type_id || !$visitor_person_id) {
            throw new Exception("البيانات الأساسية غير مكتملة.");
        }
        
        // تطبيق قيود منسق المادة
        if ($current_user_role === 'Subject Coordinator') {
            // التحقق من أن منسق المادة لا يُنشئ زيارات إلا لمادته
            $coordinator_data = query_row("
                SELECT subject_id 
                FROM coordinator_supervisors 
                WHERE user_id = ?
            ", [$current_user_id]);
            
            if (!$coordinator_data) {
                throw new Exception("لا يوجد مادة مخصصة لمنسق المادة.");
            }
            
            if ($subject_id != $coordinator_data['subject_id']) {
                throw new Exception("لا يُسمح لمنسق المادة بإنشاء زيارات إلا لمادته المخصصة.");
            }
            
            // التحقق من أن الزائر والمعلم مناسبان لمنسق المادة
            $visitor_allowed = false;
            $teacher_allowed = false;
            
            // التحقق من أن المعلم يُدرس المادة التي يُنسقها المستخدم الحالي
            $teacher_check = query_row("
                SELECT t.id 
                FROM teachers t
                JOIN teacher_subjects ts ON t.id = ts.teacher_id
                WHERE t.id = ? AND ts.subject_id = ?
            ", [$teacher_id, $coordinator_data['subject_id']]);
            
            if ($teacher_check) {
                $teacher_allowed = true;
            }
            
            // التحقق من نوع الزائر
            $visitor_type = query_row("SELECT name FROM visitor_types WHERE id = ?", [$visitor_type_id]);
            
            if ($visitor_type) {
                if ($visitor_type['name'] === 'منسق المادة') {
                    // التحقق من أن المنسق الزائر يُدرس نفس المادة
                    $coordinator_visitor_check = query_row("
                        SELECT t.id 
                        FROM teachers t
                        JOIN teacher_subjects ts ON t.id = ts.teacher_id
                        WHERE t.id = ? AND t.job_title = 'منسق المادة' AND ts.subject_id = ?
                    ", [$visitor_person_id, $coordinator_data['subject_id']]);
                    
                    if ($coordinator_visitor_check) {
                        $visitor_allowed = true;
                    }
                } elseif ($visitor_type['name'] === 'موجه المادة') {
                    // التحقق من أن الموجه يُدرس نفس المادة
                    $supervisor_check = query_row("
                        SELECT t.id 
                        FROM teachers t
                        JOIN teacher_subjects ts ON t.id = ts.teacher_id
                        WHERE t.id = ? AND t.job_title = 'موجه المادة' AND ts.subject_id = ?
                    ", [$visitor_person_id, $coordinator_data['subject_id']]);
                    
                    if ($supervisor_check) {
                        $visitor_allowed = true;
                    }
                }
            }
            
            if (!$visitor_allowed) {
                throw new Exception("الزائر المختار غير مناسب. منسق المادة يُسمح له بإنشاء زيارات لمنسقي مادته أو لموجهي مادته فقط.");
            }
            
            if (!$teacher_allowed) {
                throw new Exception("المعلم المختار خارج نطاق مادتك. منسق المادة يُسمح له فقط بزيارة المعلمين في مادته.");
            }
        }
        
        // إضافة الزيارة الصفية إلى جدول الزيارات
        $sql = "
            INSERT INTO visits (
                school_id, teacher_id, subject_id, grade_id, section_id, level_id, 
                visitor_type_id, visitor_person_id, visit_date, academic_year_id, visit_type, attendance_type, has_lab, 
                general_notes, recommendation_notes, appreciation_notes, total_score, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
            )
        ";
        
        // جلب العام الدراسي
        $academic_year_id = $_POST['academic_year_id'] ?? null;
        if (!$academic_year_id) {
            // إذا لم يتم تحديد العام، نستخدم العام النشط
            $active_year = get_active_academic_year();
            $academic_year_id = $active_year ? $active_year['id'] : null;
        }
        
        execute($sql, [
            $school_id, $teacher_id, $subject_id, $grade_id, $section_id, $level_id,
            $visitor_type_id, $visitor_person_id, $visit_date, $academic_year_id, $visit_type, $attendance_type, $has_lab,
            $general_notes, $recommendation_notes, $appreciation_notes, $total_score
        ]);
        
        // الحصول على معرف الزيارة المضافة
        $visit_id = last_insert_id();
        
        // حفظ تفاصيل التقييم لكل مؤشر
        $sql = "
            INSERT INTO visit_evaluations (
                visit_id, indicator_id, score, recommendation_id, custom_recommendation, 
                created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, NOW(), NOW()
            )
        ";
        
        // البحث عن مؤشرات التقييم في النموذج المرسل
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'score_') === 0) {
                $indicator_id = substr($key, 6);
                
                // التعامل مع القيم الجديدة للتقييم
                // إرسال NULL للمؤشرات التي لم يتم قياسها
                if ($value === '' || $value === null) {
                    $score = null; // NULL للمؤشرات غير المقاسة
                } else {
                    $score = intval($value); // 0, 1, 2, 3
                }
                
                // حصول على التوصيات المختارة (إن وجدت)
                $recommendations = isset($_POST['recommend_' . $indicator_id]) ? $_POST['recommend_' . $indicator_id] : [];
                
                // التوصية المخصصة (إن وجدت)
                $custom_recommendation = $_POST['custom_recommend_' . $indicator_id] ?? '';
                
                // في حالة عدم وجود توصيات مختارة، نضيف سجل واحد مع التوصية المخصصة فقط
                if (empty($recommendations)) {
                    execute($sql, [
                        $visit_id, $indicator_id, $score, null, $custom_recommendation
                    ]);
                } else {
                    // إضافة كل توصية مختارة كسجل منفصل
                    foreach ($recommendations as $recommendation_id) {
                        execute($sql, [
                            $visit_id, $indicator_id, $score, $recommendation_id, $custom_recommendation
                        ]);
                    }
                }
            }
        }
        
        // توجيه المستخدم إلى الصفحة الرئيسية مع رسالة نجاح
        $_SESSION['success_message'] = "تم حفظ تقييم الزيارة الصفية بنجاح!";
        header('Location: index.php');
        exit;
        
    } catch (Exception $e) {
        // إلغاء المعاملة في حالة حدوث خطأ
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // تخزين رسالة الخطأ لعرضها للمستخدم
        $error_message = "حدث خطأ أثناء حفظ التقييم: " . $e->getMessage();
    }
}

// تضمين ملف رأس الصفحة
require_once 'includes/header.php';

// جلب البيانات الأساسية من قاعدة البيانات
try {
    // الحصول على معلومات المدرسة (نستخدم أول مدرسة)
    $school = query_row("SELECT * FROM schools LIMIT 1");
    if (!$school) {
        throw new Exception("لم يتم العثور على بيانات المدرسة. يرجى إعداد المدرسة أولاً.");
    }
    $school_id = $school['id'];
    
    // جلب الصفوف مباشرة بدون الاعتماد على المرحلة
    $grades = query("SELECT g.*, e.id as level_id FROM grades g JOIN educational_levels e ON g.level_id = e.id ORDER BY e.id, g.id");
    
    $domains = query("SELECT * FROM evaluation_domains ORDER BY id");
    
    // جلب أنواع الزوار مع تطبيق قيود منسق المادة
    if ($current_user_role === 'Subject Coordinator') {
        // منسق المادة يرى نفسه والموجه فقط
        $visitor_types = query("
            SELECT * FROM visitor_types 
            WHERE name IN ('منسق المادة', 'موجه المادة') 
            ORDER BY name
        ");
    } else {
        // باقي المستخدمين يرون جميع أنواع الزوار
        $visitor_types = query("SELECT * FROM visitor_types ORDER BY name");
    }
    
    // جلب المواد الدراسية للمدرسة مع تطبيق قيود منسق المادة
    if ($current_user_role === 'Subject Coordinator') {
        // منسق المادة يرى مادته فقط
        $coordinator_data = query_row("
            SELECT subject_id 
            FROM coordinator_supervisors 
            WHERE user_id = ?
        ", [$current_user_id]);
        
        if ($coordinator_data) {
            $subjects = query("
                SELECT * FROM subjects 
                WHERE id = ? AND (school_id = ? OR school_id IS NULL) 
                ORDER BY name
            ", [$coordinator_data['subject_id'], $school_id]);
        } else {
            $subjects = [];
        }
    } else {
        // المدراء والمشرفون يرون جميع المواد
        $subjects = query("SELECT * FROM subjects WHERE school_id = ? OR school_id IS NULL ORDER BY name", [$school_id]);
    }
} catch (PDOException $e) {
    // تعامل مع أي أخطاء في قاعدة البيانات
    $error_message = "حدث خطأ أثناء جلب البيانات. يرجى المحاولة مرة أخرى لاحقاً.";
}
?>

<!-- نموذج اختيار المدرسة والمادة والمعلم -->
<div id="selection-form" class="bg-gradient-to-br from-white to-slate-50 border border-slate-200 rounded-xl shadow-lg p-8 mb-8">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-700 to-purple-700 bg-clip-text text-transparent mb-2">نموذج تقييم زيارة صفية</h1>
        <p class="text-slate-600">أدخل بيانات الزيارة للبدء في عملية التقييم</p>
        <div class="mt-4 w-20 h-1 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full mx-auto"></div>
    </div>
    
    <?php if (isset($error_message)): ?>
        <div class="bg-red-50 border border-red-200 border-r-4 border-r-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-sm">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <?= $error_message ?>
            </div>
        </div>
    <?php endif; ?>
    
    <form action="evaluation_form.php" method="post" id="visit-form">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- نوع الزائر -->
            <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-all duration-200">
                <label class="block mb-3 font-bold text-gray-800 text-sm">نوع الزائر:</label>
                <select id="visitor-type" name="visitor_type_id" class="w-full border-2 border-slate-300 p-3 rounded-lg text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 hover:border-blue-400" required>
                    <option value="">اختر نوع الزائر...</option>
                    <?php foreach ($visitor_types as $type): ?>
                        <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div id="visitor-name" class="mt-2 text-sm text-blue-600 font-medium"></div>
                <input type="hidden" id="visitor-person-id" name="visitor_person_id" value="">
            </div>

            <!-- العام الدراسي -->
            <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-all duration-200">
                <label class="block mb-3 font-bold text-gray-800 text-sm">العام الدراسي:</label>
                <select id="academic-year" name="academic_year_id" class="w-full border-2 border-slate-300 p-3 rounded-lg text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 hover:border-blue-400" required>
                    <option value="">اختر العام الدراسي...</option>
                    <?php 
                    // جلب العام الدراسي النشط
                    $academic_years = query("SELECT id, name, is_active FROM academic_years ORDER BY is_active DESC, name DESC");
                    foreach ($academic_years as $year): 
                        $is_selected = $year['is_active'] ? 'selected' : '';
                    ?>
                        <option value="<?= $year['id'] ?>" <?= $is_selected ?>><?= htmlspecialchars($year['name']) ?> <?= $year['is_active'] ? '(نشط)' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- نوع الزيارة -->
            <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-all duration-200">
                <label class="block mb-3 font-bold text-gray-800 text-sm">نوع الزيارة:</label>
                <select id="visit-type" name="visit_type" class="w-full border-2 border-slate-300 p-3 rounded-lg text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 hover:border-blue-400" required>
                    <option value="full">تقييم كلي</option>
                    <option value="partial">تقييم جزئي</option>
                </select>
            </div>

            <!-- طريقة الحضور -->
            <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-all duration-200">
                <label class="block mb-3 font-bold text-gray-800 text-sm">طريقة الحضور:</label>
                <select id="attendance-type" name="attendance_type" class="w-full border-2 border-slate-300 p-3 rounded-lg text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 hover:border-blue-400" required>
                    <option value="physical">حضور</option>
                    <option value="remote">عن بعد</option>
                    <option value="hybrid">مدمج</option>
                </select>
            </div>

            <!-- المادة -->
            <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-all duration-200">
                <label class="block mb-3 font-bold text-gray-800 text-sm">المادة:</label>
                <select id="subject" name="subject_id" class="w-full border-2 border-slate-300 p-3 rounded-lg text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 hover:border-blue-400" required onchange="loadTeachers()">
                    <option value="">اختر المادة...</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- المعلم -->
            <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-all duration-200">
                <label class="block mb-3 font-bold text-gray-800 text-sm">المعلم:</label>
                <select id="teacher" name="teacher_id" class="w-full border-2 border-slate-300 p-3 rounded-lg text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 hover:border-blue-400" required>
                    <option value="">اختر المعلم...</option>
                    <!-- سيتم تحديث هذه القائمة ديناميكياً بناءً على المدرسة والمادة المختارة -->
                </select>
            </div>

            <!-- الصف -->
            <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-all duration-200">
                <label class="block mb-3 font-bold text-gray-800 text-sm">الصف:</label>
                <select id="grade" name="grade_id" class="w-full border-2 border-slate-300 p-3 rounded-lg text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 hover:border-blue-400" required onchange="loadSections(this.value)">
                    <option value="">اختر الصف...</option>
                    <?php foreach ($grades as $grade): ?>
                        <option value="<?= $grade['id'] ?>" data-level-id="<?= $grade['level_id'] ?>"><?= htmlspecialchars($grade['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- الشعبة -->
            <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-all duration-200">
                <label class="block mb-3 font-bold text-gray-800 text-sm">الشعبة:</label>
                <select id="section" name="section_id" class="w-full border-2 border-slate-300 p-3 rounded-lg text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 hover:border-blue-400" required>
                    <option value="">اختر الشعبة...</option>
                    <!-- سيتم تحديث هذه القائمة ديناميكياً بناءً على الصف المختار -->
                </select>
            </div>

            <!-- تقييم المعمل -->
            <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-all duration-200">
                <label class="block mb-3 font-bold text-gray-800 text-sm">إعدادات إضافية:</label>
                <div class="flex items-center">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="has-lab" name="has_lab" class="form-checkbox h-5 w-5 text-blue-600 rounded focus:ring-2 focus:ring-blue-200">
                        <span class="mr-3 text-sm font-medium text-gray-700">إضافة تقييم المعمل</span>
                    </label>
                </div>
            </div>

            <!-- تاريخ الزيارة -->
            <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-all duration-200">
                <label class="block mb-3 font-bold text-gray-800 text-sm">تاريخ الزيارة:</label>
                <input type="date" id="visit-date" name="visit_date" class="w-full border-2 border-slate-300 p-3 rounded-lg text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 hover:border-blue-400" required>
            </div>

            <!-- حذف حقل المدرسة - سنستخدم المدرسة الافتراضية -->
            <input type="hidden" id="school" name="school_id" value="<?= $school_id ?>">
        </div>

        <div class="text-center pt-6 border-t border-slate-200">
            <button type="button" id="start-evaluation-btn" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-8 py-3 rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all duration-200 shadow-md hover:shadow-lg font-semibold text-lg">
                بدء التقييم
            </button>
        </div>
    </form>
</div>

<!-- نموذج التقييم (يظهر بعد اختيار بيانات المعلم والمدرسة) -->
<div id="evaluation-form" class="bg-white rounded-lg shadow-md p-6" style="display: none;">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">تقييم الزيارة الصفية</h1>
        <button id="back-to-selection" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">تغيير المعلم/المدرسة</button>
    </div>

    <div id="evaluation-header" class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6"></div>
    
    <!-- معلومات الزيارات السابقة -->
    <div id="previous-visits-info" class="mb-8 bg-gradient-to-br from-slate-50 to-blue-50 border border-slate-200 rounded-xl p-6 shadow-lg">
        <div class="flex items-center mb-4 pb-4 border-b border-slate-300">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-3 rounded-lg mr-4 shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-bold bg-gradient-to-r from-blue-700 to-purple-700 bg-clip-text text-transparent">معلومات الزيارات السابقة</h2>
                <p class="text-slate-600 text-sm mt-1">رؤية شاملة عن تاريخ أداء المعلم والتوصيات السابقة</p>
            </div>
        </div>
        
        <div class="mb-6 p-4 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-lg shadow-inner">
            <div class="flex items-center text-white">
                <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-sm font-medium">هذه المعلومات تساعدك على متابعة تقدم المعلم ومعرفة التوصيات السابقة قبل إجراء التقييم الجديد</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- إحصائيات الزيارات -->
            <div class="bg-white border-l-4 border-blue-500 rounded-lg p-5 shadow-md hover:shadow-lg transition-all duration-300 hover:scale-105">
                <div class="flex items-center mb-3">
                    <div class="bg-blue-100 p-2 rounded-lg mr-3">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-blue-800">الزيارات السابقة</h3>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between items-center p-2 bg-blue-50 rounded-lg">
                        <span class="font-semibold text-gray-700 text-sm">عدد الزيارات:</span>
                        <span id="visits-count" class="text-xl font-bold text-blue-700 bg-white px-2 py-1 rounded shadow-sm">-</span>
                    </div>
                    <div class="flex justify-between items-center p-2 bg-green-50 rounded-lg">
                        <span class="font-semibold text-gray-700 text-sm">متوسط الأداء (كل الزائرين):</span>
                        <span id="average-performance-all" class="text-xl font-bold text-green-700 bg-white px-2 py-1 rounded shadow-sm">-</span>
                    </div>
                    <div class="flex justify-between items-center p-2 bg-purple-50 rounded-lg">
                        <span class="font-semibold text-gray-700 text-sm">متوسط الأداء (الزائر الحالي):</span>
                        <span id="average-performance-current" class="text-xl font-bold text-purple-700 bg-white px-2 py-1 rounded shadow-sm">-</span>
                    </div>
                </div>
            </div>
            
            <!-- تفاصيل آخر زيارة -->
            <div class="bg-white border-l-4 border-green-500 rounded-lg p-5 shadow-md hover:shadow-lg transition-all duration-300 hover:scale-105">
                <div class="flex items-center mb-3">
                    <div class="bg-green-100 p-2 rounded-lg mr-3">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-green-800">آخر زيارة</h3>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded-lg">
                        <span class="font-semibold text-gray-700 text-sm">التاريخ:</span>
                        <span id="last-visit-date" class="text-green-700 font-bold bg-white px-2 py-1 rounded shadow-sm">-</span>
                    </div>
                    <div class="flex justify-between items-center p-2 bg-blue-50 rounded-lg">
                        <span class="font-semibold text-gray-700 text-sm">نسبة التقييم (الزائر الحالي):</span>
                        <span id="last-visit-current-percentage" class="text-xl font-bold text-blue-700 bg-white px-2 py-1 rounded shadow-sm">-</span>
                    </div>
                    <div class="flex justify-between items-center p-2 bg-orange-50 rounded-lg">
                        <span class="font-semibold text-gray-700 text-sm">نسبة التقييم (آخر زائر):</span>
                        <span id="last-visit-any-percentage" class="text-xl font-bold text-orange-700 bg-white px-2 py-1 rounded shadow-sm">-</span>
                    </div>
                    <div class="flex justify-between items-center p-2 bg-purple-50 rounded-lg">
                        <span class="font-semibold text-gray-700 text-sm">الصف/الشعبة:</span>
                        <span id="last-visit-class" class="text-purple-700 font-bold bg-white px-2 py-1 rounded shadow-sm">-</span>
                    </div>
                </div>
            </div>
            
            <!-- توصيات آخر زيارة -->
            <div class="bg-white border-l-4 border-amber-500 rounded-lg p-5 shadow-md hover:shadow-lg transition-all duration-300 hover:scale-105">
                <div class="flex items-center mb-3">
                    <div class="bg-amber-100 p-2 rounded-lg mr-3">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-amber-800">توصيات آخر زيارة</h3>
                </div>
                <div class="space-y-4">
                    <div id="last-recommendation-notes" class="p-4 bg-gradient-to-r from-red-50 to-pink-50 rounded-lg border border-red-200 shadow-sm">
                        <div class="flex items-center mb-2">
                            <svg class="w-4 h-4 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            <span class="font-bold text-red-700 text-sm">أنصح المعلم:</span>
                        </div>
                        <p class="text-gray-800 text-sm leading-relaxed bg-white p-2 rounded border-r-2 border-red-300">لا توجد توصيات مسجلة</p>
                    </div>
                    <div id="last-appreciation-notes" class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg border border-green-200 shadow-sm">
                        <div class="flex items-center mb-2">
                            <svg class="w-4 h-4 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.01M15 10h1.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="font-bold text-green-700 text-sm">أشكر المعلم على:</span>
                        </div>
                        <p class="text-gray-800 text-sm leading-relaxed bg-white p-2 rounded border-r-2 border-green-300">لا توجد نقاط شكر مسجلة</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="evaluation-save-form" action="evaluation_form.php" method="post">
    <!-- أقسام التقييم -->
    <?php $step = 1; ?>
    <?php foreach ($domains as $domain): ?>
        <div id="step-<?= $step ?>" class="evaluation-section bg-gradient-to-br from-white to-slate-50 border border-slate-200 rounded-xl shadow-lg p-6 mb-6" style="display: <?= $step === 1 ? 'block' : 'none' ?>;">
            <div class="flex items-center mb-6 pb-4 border-b border-slate-300">
                <div>
                    <h2 class="text-2xl font-bold bg-gradient-to-r from-purple-700 to-blue-700 bg-clip-text text-transparent"><?= htmlspecialchars($domain['name']) ?></h2>
                    <p class="text-slate-600 text-sm mt-1">قيم أداء المعلم في هذا المجال بعناية</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <?php 
                try {
                    $indicators = get_indicators_by_domain($domain['id']);
                    foreach ($indicators as $indicator): 
                        // تحديد ما إذا كان المؤشر ينتمي إلى مجموعة مؤشرات المعمل (يبدأ من المؤشر 24 إلى 29)
                        $is_lab_indicator = ($indicator['id'] >= 24 && $indicator['id'] <= 29);
                        
                        // إضافة class جديد للتحكم بظهور مؤشرات المعمل
                        $lab_class = $is_lab_indicator ? 'lab-indicator' : '';
                        
                        // تحديد لون وأيقونة المؤشر حسب النوع
                        if ($is_lab_indicator) {
                            $indicator_color = 'from-amber-500 to-orange-500';
                            $bg_color = 'from-amber-50 to-orange-50';
                            $border_color = 'border-amber-500';
                            $text_color = 'text-amber-700';
                            $badge_text = 'مؤشر معملي';
                            $icon_path = 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z';
                        } else {
                            $indicator_color = 'from-indigo-500 to-purple-500';
                            $bg_color = 'from-slate-100 to-blue-100';
                            $border_color = 'border-indigo-500';
                            $text_color = 'text-indigo-600';
                            $badge_text = 'مؤشر عام';
                            $icon_path = 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
                        }
                ?>
                    <div class="indicator-block <?= $lab_class ?> bg-white border border-slate-200 rounded-xl p-5 shadow-md hover:shadow-lg transition-all duration-300 hover:scale-105">
                        <div class="flex items-start mb-4">
                            <div class="flex-1">
                                <div class="bg-gradient-to-r <?= $bg_color ?> border-r-4 <?= $border_color ?> p-4 rounded-lg shadow-sm">
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="block text-gray-900 font-bold text-base leading-relaxed"><?= htmlspecialchars($indicator['name']) ?></label>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold <?= $text_color ?> bg-white border <?= $border_color ?> shadow-sm">
                                            <?= $badge_text ?>
                                        </span>
                                    </div>
                                    <div class="text-xs <?= $text_color ?> font-medium">المؤشر رقم <?= $indicator['id'] ?> - قيم هذا العنصر بعناية</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <select name="score_<?= $indicator['id'] ?>" class="w-full border-2 border-slate-300 p-3 rounded-lg text-sm font-medium focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition-all duration-200 hover:border-purple-400">
                                <option value="">لم يتم قياسه (NULL)</option>
                                <option value="3">الأدلة مستكملة وفاعلة (3)</option>
                                <option value="2">تتوفر معظم الأدلة (2)</option>
                                <option value="1">تتوفر بعض الأدلة (1)</option>
                                <option value="0">الأدلة غير متوفرة أو محدودة (0)</option>
                            </select>
                        </div>
                        
                        <?php 
                        try {
                            $recommendations = get_recommendations_by_indicator($indicator['id']);
                            if (count($recommendations) > 0): 
                        ?>
                            <div class="mb-4 bg-gradient-to-r from-blue-50 to-indigo-50 p-4 border border-blue-200 rounded-lg shadow-sm">
                                <div class="flex items-center mb-3">
                                    <p class="text-sm font-bold text-blue-800">التوصيات المقترحة:</p>
                                </div>
                                <div class="space-y-2">
                                    <?php foreach ($recommendations as $rec): ?>
                                    <div class="flex items-start p-2 bg-white rounded-lg border border-blue-100 hover:bg-blue-50 transition-colors duration-200">
                                        <input type="checkbox" name="recommend_<?= $indicator['id'] ?>[]" value="<?= $rec['id'] ?>" id="rec_<?= $rec['id'] ?>_<?= $indicator['id'] ?>" class="form-checkbox h-4 w-4 text-blue-600 mt-1 flex-shrink-0">
                                        <label for="rec_<?= $rec['id'] ?>_<?= $indicator['id'] ?>" class="mr-3 text-sm text-gray-700 leading-relaxed cursor-pointer"><?= htmlspecialchars($rec['text']) ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php 
                            endif;
                        } catch (PDOException $e) {
                            // تعديل لعرض الخطأ ومعرفة المشكلة
                            echo '<p class="text-red-500 text-sm">خطأ في جلب التوصيات: ' . $e->getMessage() . '</p>';
                        }
                        ?>
                        
                        <div class="mt-4">
                            <div class="flex items-center mb-2">
                                <label class="text-sm font-semibold text-green-800">توصية مخصصة (اختياري):</label>
                            </div>
                            <input type="text" name="custom_recommend_<?= $indicator['id'] ?>" placeholder="أدخل توصية مخصصة..." class="w-full border-2 border-green-300 p-3 rounded-lg text-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-200 hover:border-green-400 bg-green-50">
                        </div>
                    </div>
                <?php 
                    endforeach;
                } catch (PDOException $e) {
                    echo '<p class="text-red-500">حدث خطأ أثناء جلب مؤشرات التقييم</p>';
                }
                ?>
            </div>
            
            <div class="flex justify-between items-center mt-8 pt-6 border-t border-slate-200">
                <?php if ($step > 1): ?>
                    <button type="button" class="prev-step bg-gradient-to-r from-gray-500 to-gray-600 text-white px-6 py-3 rounded-lg hover:from-gray-600 hover:to-gray-700 transition-all duration-200 shadow-md hover:shadow-lg" data-step="<?= $step ?>">
                        <span>السابق</span>
                    </button>
                <?php else: ?>
                    <div></div>
                <?php endif; ?>
                
                <button type="button" class="go-to-notes bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-3 rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all duration-200 shadow-md hover:shadow-lg" data-notes-step="<?= count($domains) + 1 ?>">
                    <span>ملاحظات وتوصيات</span>
                </button>
                
                <?php if ($step < count($domains)): ?>
                    <button type="button" class="next-step bg-gradient-to-r from-purple-600 to-blue-600 text-white px-6 py-3 rounded-lg hover:from-purple-700 hover:to-blue-700 transition-all duration-200 shadow-md hover:shadow-lg" data-step="<?= $step ?>">
                        <span>التالي</span>
                    </button>
                <?php else: ?>
                    <button type="button" class="notes-to-final-result bg-gradient-to-r from-green-600 to-emerald-600 text-white px-6 py-3 rounded-lg hover:from-green-700 hover:to-emerald-700 transition-all duration-200 shadow-md hover:shadow-lg">
                        <span>عرض النتيجة النهائية</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php $step++; ?>
    <?php endforeach; ?>

    <!-- قسم الملاحظات والتوصيات -->
    <div id="step-<?= $step ?>" class="evaluation-section bg-white border border-gray-200 border-r-4 border-r-primary-600 rounded-lg p-6 mb-4" style="display: none;">
        <h2 class="text-xl font-bold text-primary-700 mb-6 pb-2 border-b border-gray-200">ملاحظات وتوصيات عامة</h2>
        
        <div class="space-y-6">
            <div>
                <label class="block mb-3 font-semibold text-gray-700">أوصي بـ:</label>
                <textarea id="recommendation-notes" name="recommendation_notes" class="w-full border-2 border-gray-300 p-4 rounded-lg h-32 resize-none" placeholder="أدخل التوصيات هنا..."></textarea>
            </div>
            
            <div>
                <label class="block mb-3 font-semibold text-gray-700">أشكر المعلم على:</label>
                <textarea id="appreciation-notes" name="appreciation_notes" class="w-full border-2 border-gray-300 p-4 rounded-lg h-32 resize-none" placeholder="أدخل نقاط الشكر والتقدير هنا..."></textarea>
            </div>
        </div>
        
        <div class="flex justify-between mt-6">
            <button type="button" class="prev-step bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600" data-step="<?= $step ?>">السابق</button>
            <button type="button" class="notes-to-final-result bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">عرض النتيجة النهائية وحفظ التقييم</button>
        </div>
    </div>
    <?php $step++; ?>

    <!-- قسم النتيجة النهائية -->
    <div id="step-<?= $step ?>" class="evaluation-section bg-white border border-gray-200 border-r-4 border-r-primary-600 rounded-lg p-6 mb-4" style="display: none;">
        <h2 class="text-xl font-bold text-primary-700 mb-6 pb-2 border-b border-gray-200">نتيجة التقييم النهائية</h2>
        
        <div class="grid grid-cols-1 gap-6">
            <div id="total-score" class="text-xl font-bold p-4 bg-gray-50 rounded-lg border border-gray-200"></div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-3 text-gray-700">نقاط القوة:</h3>
                    <ul id="strengths" class="list-disc list-inside space-y-2 text-gray-600"></ul>
                </div>
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-3 text-gray-700">نقاط تحتاج إلى تحسين:</h3>
                    <ul id="improvements" class="list-disc list-inside space-y-2 text-gray-600"></ul>
                </div>
            </div>

            <div id="recommendation-notes-display" class="bg-white p-4 rounded-lg border border-gray-200" style="display: none;">
                <h3 class="text-lg font-semibold mb-3 text-gray-700">أوصي بـ:</h3>
                <p class="text-gray-600 whitespace-pre-line"></p>
            </div>
            
            <div id="appreciation-notes-display" class="bg-white p-4 rounded-lg border border-gray-200" style="display: none;">
                <h3 class="text-lg font-semibold mb-3 text-gray-700">أشكر المعلم على:</h3>
                <p class="text-gray-600 whitespace-pre-line"></p>
            </div>
        </div>

        <div class="flex justify-between mt-6">
            <button type="button" class="prev-step bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600" data-step="<?= $step ?>">العودة</button>
            <button type="submit" name="save_visit" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">حفظ التقييم</button>
        </div>
    </div>
    
    <!-- حقول مخفية لتخزين البيانات الضرورية -->
    <input type="hidden" name="school_id" id="hidden-school-id">
    <input type="hidden" name="level_id" id="hidden-level-id">
    <input type="hidden" name="grade_id" id="hidden-grade-id">
    <input type="hidden" name="section_id" id="hidden-section-id">
    <input type="hidden" name="subject_id" id="hidden-subject-id">
    <input type="hidden" name="teacher_id" id="hidden-teacher-id">
    <input type="hidden" name="visitor_type_id" id="hidden-visitor-type-id">
    <input type="hidden" name="visitor_person_id" id="hidden-visitor-person-id">
    <input type="hidden" name="visit_date" id="hidden-visit-date">
    <input type="hidden" name="visit_type" id="hidden-visit-type">
    <input type="hidden" name="attendance_type" id="hidden-attendance-type">
    <input type="hidden" name="has_lab" id="hidden-has-lab" value="0">
    <input type="hidden" name="total_score" id="hidden-total-score">
    <input type="hidden" name="average_score" id="hidden-average-score">
    <input type="hidden" name="grade" id="hidden-grade">
    <input type="hidden" name="academic_year_id" id="hidden-academic-year-id">
    </form>
</div>

<script>
// متغيرات عامة للتقييم
let indicators = <?= json_encode($indicators ?? []) ?>;
let currentStep = 1;
let isPartialEvaluation = false;

// إضافة متغير للتحكم بظهور مؤشرات المعمل
let hasLab = false;

// قائمة المؤشرات التفصيلية (لاستخدامها عند عرض النتائج)
const indicatorsDetails = [
  "خطة الدرس متوفرة وبنودها مستكملة ومناسبة.",
  "أهداف التعلم مناسبة ودقيقة الصياغة وقابلة للقياس.",
  "أنشطة الدرس الرئيسة واضحة ومتدرجة ومرتبطة بالأهداف.",
  "أهداف التعلم معروضة ويتم مناقشتها .",
  "أنشطة التمهيد مفعلة بشكل مناسب.",
  "محتوى الدرس واضح والعرض منظّم ومترابط.",
  "طرائق التدريس وإستراتيجياته متنوعه وتتمحور حول الطالب.",
  "مصادر التعلم الرئيسة والمساندة موظّفة بصورة واضحة وسليمة.",
  "الوسائل التعليميّة والتكنولوجيا موظّفة بصورة مناسبة.",
  "الأسئلة الصفية ذات صياغة سليمة ومتدرجة ومثيرة للتفكير .",
  "المادة العلمية دقيقة و مناسبة.",
  "الكفايات الأساسية متضمنة في السياق المعرفي للدرس.",
  "القيم الأساسية متضمنة في السياق المعرفي للدرس.",
  "التكامل بين محاور المادة ومع المواد الأخرى يتم بشكل مناسب.",
  "الفروق الفردية بين الطلبة يتم مراعاتها.",
  "غلق الدرس يتم بشكل مناسب.",
  "أساليب التقويم ( القبلي والبنائي والختامي ) مناسبة ومتنوعة.",
  "التغذية الراجعة متنوعة ومستمرة",
  "أعمال الطلبة متابعة ومصححة بدقة ورقيًا وإلكترونيًا .",
  "البيئة الصفية إيجابية وآمنة وداعمة للتعلّم.",
  "إدارة أنشطة التعلّم والمشاركات الصّفيّة تتم بصورة منظمة.",
  "قوانين إدارة الصف وإدارة السلوك مفعّلة.",
  "الاستثمار الأمثل لزمن الحصة",
  "مدى صلاحية وتوافر الأدوات اللازمة لتنفيذ النشاط العملي.",
  "شرح إجراءات الأمن والسلامة المناسبة للتجربة ومتابعة تفعيلها.",
  "إعطاء تعليمات واضحة وسليمة لأداء النشاط العملي قبل وأثناء التنفيذ.",
  "تسجيل الطلبة للملاحظات والنتائج أثناء تنفيذ النشاط العملي.",
  "تقويم مهارات الطلبة أثناء تنفيذ النشاط العملي.",
  "تنويع أساليب تقديم التغذية الراجعة للطلبة لتنمية مهاراتهم."
];

// دالة التحقق من صحة النموذج
function validateForm() {
    const requiredFields = ['visitor-type', 'grade', 'section', 'subject', 'teacher', 'visit-date'];
    let isValid = true;

    requiredFields.forEach(field => {
        const element = document.getElementById(field);
        if (!element.value) {
            element.classList.add('border-red-500');
            isValid = false;
        } else {
            element.classList.remove('border-red-500');
        }
    });
    
    // التحقق من اختيار الزائر الشخصي
    const visitorPersonId = document.getElementById('visitor-person-id').value;
    const visitorPersonElement = document.getElementById('visitor-person');
    
    if (visitorPersonElement && !visitorPersonId) {
        visitorPersonElement.classList.add('border-red-500');
        isValid = false;
    } else if (visitorPersonElement) {
        visitorPersonElement.classList.remove('border-red-500');
    }

    if (!isValid) {
        alert('يرجى ملء جميع الحقول المطلوبة');
        return false;
    }

    return isValid;
}

// عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    // وضع التاريخ الحالي كقيمة افتراضية
    document.getElementById('visit-date').value = new Date().toISOString().split('T')[0];
    
    // عرض إشعار تلميحي للمستخدم
    const infoElement = document.createElement('div');
    infoElement.className = 'bg-blue-50 text-blue-800 p-4 rounded-lg border border-blue-200 mb-4';
    infoElement.innerHTML = `
        <p class="mb-2 font-semibold">ملاحظة هامة:</p>
        <ul class="list-disc list-inside text-sm">
            <li>ستظهر معلومات الزيارات السابقة للمعلم بعد بدء التقييم.</li>
            <li>يمكنك الاطلاع على توصيات الزيارات السابقة ونقاط التقدير قبل إجراء التقييم الجديد.</li>
        </ul>
    `;
    
    const selectionForm = document.getElementById('selection-form');
    selectionForm.insertBefore(infoElement, selectionForm.firstChild.nextSibling);
    
    // أزرار التنقل بين خطوات التقييم
    document.querySelectorAll('.next-step').forEach(button => {
        button.addEventListener('click', function() {
            const step = parseInt(this.getAttribute('data-step'));
            showStep(step + 1);
        });
    });
    
    document.querySelectorAll('.prev-step').forEach(button => {
        button.addEventListener('click', function() {
            const step = parseInt(this.getAttribute('data-step'));
            showStep(step - 1);
        });
    });
    
    // زر العودة إلى اختيار المعلم والمدرسة
    document.getElementById('back-to-selection').addEventListener('click', function() {
        // عند العودة إلى نموذج الاختيار، نخفي نموذج التقييم ونظهر نموذج الاختيار
        document.getElementById('selection-form').style.display = 'block';
        document.getElementById('evaluation-form').style.display = 'none';
        
        // تفريغ معلومات الزيارات السابقة
        document.getElementById('visits-count').textContent = '-';
        document.getElementById('average-performance-all').textContent = '-';
        document.getElementById('average-performance-current').textContent = '-';
        document.getElementById('last-visit-date').textContent = '-';
        document.getElementById('last-visit-class').textContent = '-';
        document.getElementById('last-visit-current-percentage').textContent = '-';
        document.getElementById('last-visit-any-percentage').textContent = '-';
        
        const recommendationElement = document.querySelector('#last-recommendation-notes p');
        if (recommendationElement) {
            recommendationElement.textContent = '-';
        }
        
        const appreciationElement = document.querySelector('#last-appreciation-notes p');
        if (appreciationElement) {
            appreciationElement.textContent = '-';
        }
    });
    
    // عند ضغط زر بدء التقييم
    document.getElementById('start-evaluation-btn').addEventListener('click', function(e) {
        e.preventDefault();
        if (validateForm()) {
            // نقل المعلومات من نموذج الاختيار إلى نموذج التقييم
            const schoolId = document.getElementById('school').value;
            const gradeId = document.getElementById('grade').value;
            const sectionId = document.getElementById('section').value;
            const levelId = document.querySelector(`#grade option[value="${gradeId}"]`).getAttribute('data-level-id');
            const subjectId = document.getElementById('subject').value;
            const teacherId = document.getElementById('teacher').value;
            const visitorTypeId = document.getElementById('visitor-type').value;
            const visitorPersonId = document.getElementById('visitor-person-id').value;
            const visitDate = document.getElementById('visit-date').value;
            const visitType = document.getElementById('visit-type').value;
            const attendanceType = document.getElementById('attendance-type').value;
            const academicYearId = document.getElementById('academic-year').value;
            
            // نقل القيم إلى الحقول المخفية
            document.getElementById('hidden-school-id').value = schoolId;
            document.getElementById('hidden-level-id').value = levelId;
            document.getElementById('hidden-grade-id').value = gradeId;
            document.getElementById('hidden-section-id').value = sectionId;
            document.getElementById('hidden-subject-id').value = subjectId;
            document.getElementById('hidden-teacher-id').value = teacherId;
            document.getElementById('hidden-visitor-type-id').value = visitorTypeId;
            document.getElementById('hidden-visitor-person-id').value = visitorPersonId;
            document.getElementById('hidden-visit-date').value = visitDate;
            document.getElementById('hidden-visit-type').value = visitType;
            document.getElementById('hidden-attendance-type').value = attendanceType;
            document.getElementById('hidden-academic-year-id').value = academicYearId;
            
            // تحديث معلومات نوع التقييم
            isPartialEvaluation = (visitType === 'partial');

            // تحديث قيمة خيار المعمل
            hasLab = document.getElementById('has-lab').checked;
            document.getElementById('hidden-has-lab').value = hasLab ? '1' : '0';

            // التحكم بظهور مؤشرات المعمل
            toggleLabIndicators();
            
            // تحديث عنوان التقييم
            const schoolName = document.querySelector(`#school option[value="${schoolId}"]`)?.textContent || '';
            const gradeName = document.querySelector(`#grade option[value="${gradeId}"]`)?.textContent || '';
            const sectionName = document.querySelector(`#section option[value="${sectionId}"]`)?.textContent || '';
            const subjectName = document.querySelector(`#subject option[value="${subjectId}"]`)?.textContent || '';
            const teacherName = document.querySelector(`#teacher option[value="${teacherId}"]`)?.textContent || '';
            const visitTypeName = document.querySelector(`#visit-type option[value="${visitType}"]`)?.textContent || '';
            
            const headerHtml = `
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="font-semibold">المعلم: <span class="font-normal">${teacherName}</span></p>
                        <p class="font-semibold">المادة: <span class="font-normal">${subjectName}</span></p>
                    </div>
                    <div>
                        <p class="font-semibold">الصف: <span class="font-normal">${gradeName}</span></p>
                        <p class="font-semibold">الشعبة: <span class="font-normal">${sectionName}</span></p>
                    </div>
                    <div>
                        <p class="font-semibold">نوع التقييم: <span class="font-normal">${visitTypeName}</span></p>
                        <p class="font-semibold">تاريخ الزيارة: <span class="font-normal">${formatDate(visitDate)}</span></p>
                    </div>
                </div>
            `;
            document.getElementById('evaluation-header').innerHTML = headerHtml;
            
            // إخفاء نموذج الاختيار وإظهار نموذج التقييم
            document.getElementById('selection-form').style.display = 'none';
            document.getElementById('evaluation-form').style.display = 'block';
            
            // تحميل معلومات الزيارات السابقة
            loadPreviousVisitsInfo(teacherId, visitorPersonId);
            
            // إظهار الخطوة الأولى
            showStep(1);
            
            // تأكد من أن قسم معلومات الزيارات السابقة ظاهر
            const previousVisitsDiv = document.getElementById('previous-visits-info');
            if (previousVisitsDiv) {
                previousVisitsDiv.style.display = 'block';
            }
        }
    });
    
    // زر عرض النتيجة النهائية - تعديل ليعرض حقلي التوصيات والشكر قبل النتيجة النهائية
    document.querySelectorAll('.notes-to-final-result').forEach(button => {
        button.addEventListener('click', function() {
            // هنا نعرض صفحة الملاحظات والتوصيات أولاً (قبل الأخيرة)
            const totalSteps = document.querySelectorAll('.evaluation-section').length;
            const notesStep = totalSteps - 1; // الخطوة قبل الأخيرة (ملاحظات وتوصيات)
            showStep(notesStep);
        });
    });
    
    // إضافة زر لعرض النتيجة النهائية من صفحة الملاحظات والتوصيات
    const finalResultButtons = document.querySelectorAll('.notes-to-final-result');
    finalResultButtons.forEach(button => {
        button.addEventListener('click', function() {
            calculateAndShowFinalResult();
            const totalSteps = document.querySelectorAll('.evaluation-section').length;
            showStep(totalSteps); // الخطوة الأخيرة (النتيجة النهائية)
        });
    });
    
    // تعيين قيمة نوع التقييم
    document.getElementById('visit-type').addEventListener('change', function() {
        isPartialEvaluation = this.value === 'partial';
    });
    
    // تحديث اسم الزائر عند اختيار نوع الزائر
    document.getElementById('visitor-type').addEventListener('change', updateVisitorName);
    
    // تحديث قائمة الزوار عند تغيير المدرسة
    document.getElementById('school').addEventListener('change', function() {
        updateVisitorName(); // تحديث قائمة الزوار عند تغيير المدرسة
    });

    // عند تغيير حالة اختيار المعمل
    document.getElementById('has-lab').addEventListener('change', function() {
        hasLab = this.checked;
        document.getElementById('hidden-has-lab').value = hasLab ? '1' : '0';
        
        // التحكم بظهور مؤشرات المعمل
        toggleLabIndicators();
    });

    // تحميل المعلمين عند اختيار المادة
    document.getElementById('subject').addEventListener('change', function() {
        loadTeachers();
        updateVisitorName(); // تحديث قائمة الزوار عند تغيير المادة
    });

    // تحميل الشعب عند اختيار الصف
    document.getElementById('grade').addEventListener('change', function() {
        loadSections(this.value);
    });

    // إضافة مستمع أحداث للزر الملاحظات والتوصيات
    document.querySelectorAll('.go-to-notes').forEach(button => {
        button.addEventListener('click', function() {
            const notesStep = parseInt(this.getAttribute('data-notes-step'));
            showStep(notesStep);
        });
    });
});

// تحديث اسم الزائر عند اختيار نوع الزائر
function updateVisitorName() {
    const visitorTypeSelect = document.getElementById('visitor-type');
    const visitorNameDiv = document.getElementById('visitor-name');
    const visitorPersonIdInput = document.getElementById('visitor-person-id');
    const subjectSelect = document.getElementById('subject');
    const schoolSelect = document.getElementById('school');
    
    if (visitorTypeSelect.value) {
        // بناء URL مع المعلمات المطلوبة
        let apiUrl = `api/get_visitor_name.php?visitor_type_id=${visitorTypeSelect.value}`;
        
        if (subjectSelect.value) {
            apiUrl += `&subject_id=${subjectSelect.value}`;
        }
        
        if (schoolSelect.value) {
            apiUrl += `&school_id=${schoolSelect.value}`;
        }
        
        // إرسال طلب AJAX للحصول على قائمة الزوار حسب النوع المختار
        fetch(apiUrl)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.visitors.length > 0) {
                    // إنشاء قائمة منسدلة للزوار
                    let select = document.createElement('select');
                    select.id = 'visitor-person';
                    select.className = 'w-full border-2 border-slate-300 p-3 rounded-lg text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 hover:border-blue-400';
                    select.required = true;
                    
                    let defaultOption = document.createElement('option');
                    defaultOption.value = '';
                    defaultOption.textContent = 'اختر الزائر...';
                    select.appendChild(defaultOption);
                    
                    data.visitors.forEach(visitor => {
                        let option = document.createElement('option');
                        option.value = visitor.id;
                        option.textContent = visitor.name;
                        select.appendChild(option);
                    });
                    
                    // تحديث عنصر اسم الزائر
                    visitorNameDiv.innerHTML = '';
                    visitorNameDiv.appendChild(select);
                    
                    // تحديث معرف الزائر عند الاختيار
                    select.addEventListener('change', function() {
                        visitorPersonIdInput.value = this.value;
                    });
                } else {
                    visitorNameDiv.innerHTML = '<p class="text-red-500">لا يوجد زوار من هذا النوع</p>';
                    visitorPersonIdInput.value = '';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                visitorNameDiv.innerHTML = '<p class="text-red-500">حدث خطأ في تحميل بيانات الزوار</p>';
                visitorPersonIdInput.value = '';
            });
    } else {
        visitorNameDiv.innerHTML = '';
        visitorPersonIdInput.value = '';
    }
}

// تحميل المعلمين عند اختيار المادة
function loadTeachers() {
    const subjectSelect = document.getElementById('subject');
    const teacherSelect = document.getElementById('teacher');
    const schoolId = document.getElementById('school').value;
    const visitorTypeSelect = document.getElementById('visitor-type');
    const visitorPersonSelect = document.getElementById('visitor-person');
    
    if (!subjectSelect.value || !schoolId) {
        teacherSelect.innerHTML = '<option value="">اختر المعلم...</option>';
        return;
    }
    
    // تحديد نوع الزائر
    const visitorTypeName = visitorTypeSelect.options[visitorTypeSelect.selectedIndex]?.text || '';
    const isCoordinator = visitorTypeName === 'منسق المادة';
    const isSupervisor = visitorTypeName === 'موجه المادة';
    
    // إرسال طلب AJAX للحصول على قائمة المعلمين حسب المادة والمدرسة
    let url = `includes/get_teachers.php?subject_id=${subjectSelect.value}&school_id=${schoolId}`;
    
    // إذا كان الزائر منسق المادة أو موجه المادة، نضيف معلومات إضافية للتصفية
    if (isCoordinator || isSupervisor) {
        url += `&visitor_type=${encodeURIComponent(visitorTypeName)}`;
        if (visitorPersonSelect && visitorPersonSelect.value) {
            url += `&exclude_visitor=${visitorPersonSelect.value}`;
        }
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            // إعادة تعيين قائمة المعلمين
            teacherSelect.innerHTML = '<option value="">اختر المعلم...</option>';
            
            // إذا كان الزائر موجه، نضيف منسق المادة في بداية القائمة
            if (isSupervisor && subjectSelect.value) {
                // جلب منسق المادة
                fetch(`includes/get_subject_coordinator.php?subject_id=${subjectSelect.value}&school_id=${schoolId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(coordinators => {
                        // التحقق من وجود خطأ في الاستجابة
                        if (coordinators.error) {
                            console.error('Coordinator API error:', coordinators.error);
                            throw new Error(coordinators.error);
                        }
                        
                        // التحقق من أن coordinators مصفوفة
                        if (Array.isArray(coordinators) && coordinators.length > 0) {
                            // إضافة منسقي المادة إلى القائمة
                            coordinators.forEach(coord => {
                                let option = document.createElement('option');
                                option.value = coord.id;
                                option.textContent = coord.name + ' (منسق المادة)';
                                teacherSelect.appendChild(option);
                            });
                            
                            // إضافة فاصل بين منسقي المادة والمعلمين العاديين
                            if (data.length > 0) {
                                let separator = document.createElement('option');
                                separator.disabled = true;
                                separator.textContent = '---------------------';
                                teacherSelect.appendChild(separator);
                            }
                        } else {
                            console.log('No coordinators found for subject:', subjectSelect.value);
                        }
                        
                        // إضافة المعلمين إلى القائمة
                        data.forEach(teacher => {
                            let option = document.createElement('option');
                            option.value = teacher.id;
                            option.textContent = teacher.name;
                            teacherSelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error loading coordinators:', error);
                        // إضافة المعلمين فقط في حالة فشل جلب المنسقين
                        data.forEach(teacher => {
                            let option = document.createElement('option');
                            option.value = teacher.id;
                            option.textContent = teacher.name;
                            teacherSelect.appendChild(option);
                        });
                    });
            } else {
                // إضافة المعلمين إلى القائمة
                data.forEach(teacher => {
                    let option = document.createElement('option');
                    option.value = teacher.id;
                    option.textContent = teacher.name;
                    teacherSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            teacherSelect.innerHTML = '<option value="">حدث خطأ في تحميل المعلمين</option>';
        });
}

// تحميل الشعب عند اختيار الصف
function loadSections(gradeId) {
    const sectionSelect = document.getElementById('section');
    const schoolId = document.getElementById('school').value;
    
    if (gradeId && schoolId) {
        // إرسال طلب AJAX للحصول على قائمة الشعب حسب الصف والمدرسة
        fetch(`includes/get_sections.php?grade_id=${gradeId}&school_id=${schoolId}`)
            .then(response => response.json())
            .then(data => {
                // إعادة تعيين قائمة الشعب
                sectionSelect.innerHTML = '<option value="">اختر الشعبة...</option>';
                
                // إضافة الشعب إلى القائمة
                data.forEach(section => {
                    let option = document.createElement('option');
                    option.value = section.id;
                    option.textContent = section.name;
                    sectionSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                sectionSelect.innerHTML = '<option value="">حدث خطأ في تحميل الشعب</option>';
            });
    } else {
        sectionSelect.innerHTML = '<option value="">اختر الشعبة...</option>';
    }
}

// دالة عرض خطوة محددة من التقييم
function showStep(step) {
    const totalSteps = document.querySelectorAll('.evaluation-section').length;
    
    for (let i = 1; i <= totalSteps; i++) {
        const el = document.getElementById(`step-${i}`);
        if (el) {
            el.style.display = (i === step) ? 'block' : 'none';
        }
    }
    
    // تأكد من أن قسم معلومات الزيارات السابقة ظاهر دائمًا
    const previousVisitsDiv = document.getElementById('previous-visits-info');
    if (previousVisitsDiv) {
        previousVisitsDiv.style.display = 'block';
    }
    
    currentStep = step;
    window.scrollTo(0, 0);
}

// دالة حساب وعرض النتيجة النهائية
function calculateAndShowFinalResult() {
    // تحضير مصفوفات لتخزين النتائج
    const strengths = [];
    const improvements = [];
    let totalScore = 0;
    let totalItems = 0;
    
    try {
        // جمع نتائج التقييم
        document.querySelectorAll('.indicator-block').forEach((block, index) => {
            // لا نحسب المؤشرات المخفية (المعمل عندما يكون غير مُفعّل)
            if (block.style.display === 'none') {
                return;
            }
            
            const scoreSelect = block.querySelector('select[name^="score_"]');
            const scoreValue = scoreSelect.value;
            const score = scoreValue === '' ? null : parseInt(scoreValue);
            const indicatorLabel = block.querySelector('label').textContent;
            
            // إذا كان التقييم جزئياً، نحسب فقط العناصر التي تم تقييمها
            // ونستثني مؤشرات "لم يتم قياسه" (قيمة فارغة أو null) من الحساب
            if (scoreValue !== '' && score !== null) {
                // تصنيف نقاط القوة والتحسين
                if (score >= 2) {
                    strengths.push(indicatorLabel);
                } else if (score >= 0) {
                    improvements.push(indicatorLabel);
                }
                
                // إضافة النقاط للمجموع
                totalScore += score;
                totalItems++;
            }
        });
        
        // حساب المتوسط
        const average = totalItems > 0 ? (totalScore / totalItems).toFixed(2) : 0;
        
        // تحديد التقدير
        const grade = getGrade(average);
        
        // تحديث الحقول المخفية
        document.getElementById('hidden-total-score').value = totalScore;
        document.getElementById('hidden-average-score').value = average;
        document.getElementById('hidden-grade').value = grade;
        
        // حساب النسبة المئوية (من 3 إلى 100%)
        const percentage = totalItems > 0 ? ((totalScore / (totalItems * 3)) * 100).toFixed(2) : 0;
        
        // عرض النتيجة الإجمالية
        const evaluationType = isPartialEvaluation ? 'تقييم جزئي' : 'تقييم كلي';
        document.getElementById('total-score').textContent = 
            `${evaluationType}: النتيجة ${totalScore} من ${totalItems * 3} (المتوسط: ${average} - النسبة: ${percentage}%)`;
        
        // عرض نقاط القوة
        const strengthsList = document.getElementById('strengths');
        strengthsList.innerHTML = '';
        if (strengths.length > 0) {
            strengths.forEach(strength => {
                strengthsList.innerHTML += `<li>${strength}</li>`;
            });
        } else {
            strengthsList.innerHTML = '<li class="text-gray-500">لم يتم تحديد نقاط قوة</li>';
        }
        
        // عرض نقاط التحسين
        const improvementsList = document.getElementById('improvements');
        improvementsList.innerHTML = '';
        if (improvements.length > 0) {
            improvements.forEach(improvement => {
                improvementsList.innerHTML += `<li>${improvement}</li>`;
            });
        } else {
            improvementsList.innerHTML = '<li class="text-gray-500">لم يتم تحديد نقاط تحتاج إلى تحسين</li>';
        }
        
        // جمع التوصيات المختارة
        const recommendationBoxes = document.querySelectorAll('input[type="checkbox"][name^="recommend_"]:checked');
        let selectedRecommendations = [];
        recommendationBoxes.forEach(box => {
            const label = document.querySelector(`label[for="${box.id}"]`);
            if (label) {
                selectedRecommendations.push(label.textContent.trim());
            }
        });
        
        // إضافة التوصيات المخصصة
        const customRecommendInputs = document.querySelectorAll('input[name^="custom_recommend_"]');
        customRecommendInputs.forEach(input => {
            if (input.value.trim()) {
                selectedRecommendations.push(input.value.trim());
            }
        });
        
        // تحديث حقل التوصيات إذا كان فارغاً
        const recommendationNotes = document.getElementById('recommendation-notes');
        if (!recommendationNotes.value.trim() && selectedRecommendations.length > 0) {
            recommendationNotes.value = selectedRecommendations.join('\n\n');
        }
        
        // العثور على عناصر عرض التوصيات والشكر
        const recommendationNotesDisplay = document.getElementById('recommendation-notes-display');
        const appreciationNotesDisplay = document.getElementById('appreciation-notes-display');
        
        if (!recommendationNotesDisplay || !appreciationNotesDisplay) {
            console.error('لم يتم العثور على عناصر عرض التوصيات أو الشكر');
            return;
        }
        
        // عرض التوصيات - دائماً نظهرها حتى لو كانت فارغة
        const recommendationParagraph = recommendationNotesDisplay.querySelector('p');
        if (recommendationParagraph) {
            recommendationParagraph.textContent = recommendationNotes.value || 'لم يتم إضافة توصيات';
            recommendationNotesDisplay.style.display = 'block';
        } else {
            console.error('لم يتم العثور على عنصر الفقرة لعرض التوصيات');
        }
        
        // عرض نقاط الشكر - دائماً نظهرها حتى لو كانت فارغة
        const appreciationNotes = document.getElementById('appreciation-notes');
        const appreciationParagraph = appreciationNotesDisplay.querySelector('p');
        if (appreciationParagraph && appreciationNotes) {
            appreciationParagraph.textContent = appreciationNotes.value || 'لم يتم إضافة نقاط شكر';
            appreciationNotesDisplay.style.display = 'block';
        } else {
            console.error('لم يتم العثور على عنصر الفقرة لعرض نقاط الشكر أو عنصر الإدخال');
        }
    } catch (error) {
        console.error('حدث خطأ أثناء حساب وعرض النتيجة النهائية:', error);
    }
}

// دالة الحصول على التقدير بناءً على المتوسط
function getGrade(average) {
    // تحويل المتوسط إلى نسبة مئوية للمقارنة
    const percentage = (average / 3) * 100;
    
    if (percentage >= 90) return 'ممتاز';       // 2.7 من 3
    if (percentage >= 80) return 'جيد جداً';     // 2.4 من 3
    if (percentage >= 65) return 'جيد';         // 1.95 من 3
    if (percentage >= 50) return 'مقبول';       // 1.5 من 3
    return 'يحتاج إلى تحسين';                  // أقل من 1.5 من 3
}

// دالة تحميل معلومات الزيارات السابقة
function loadPreviousVisitsInfo(teacherId, visitorPersonId) {
    if (!teacherId || !visitorPersonId) return;
    
    console.log('جاري تحميل معلومات الزيارات السابقة للمعلم ' + teacherId + ' والزائر ' + visitorPersonId);
    
    // إظهار قسم معلومات الزيارات السابقة بشكل افتراضي
    const previousVisitsDiv = document.getElementById('previous-visits-info');
    if (previousVisitsDiv) {
        previousVisitsDiv.style.display = 'block';
    }
    
    // جلب معلومات الزيارات السابقة من خلال API
    const apiUrl = `api/get_previous_visits.php?teacher_id=${teacherId}&visitor_person_id=${visitorPersonId}`;
    console.log('🔥 استدعاء API مع URL:', apiUrl);
    
    fetch(apiUrl)
        .then(response => {
            console.log('تم استلام الرد من API');
            return response.json();
        })
        .then(data => {
            console.log('البيانات المستلمة من API:', data);
            
            if (data.success) {
                const visitsInfo = data.data;
                
                // تحديث عدد الزيارات
                const visitsCountElement = document.getElementById('visits-count');
                if (visitsCountElement) {
                    visitsCountElement.textContent = visitsInfo.visits_count || '0';
                }
                
                // تحديث متوسط الأداء العام لكل الزائرين
                const averagePerformanceAllElement = document.getElementById('average-performance-all');
                if (averagePerformanceAllElement) {
                    if (visitsInfo.average_performance_all !== undefined && visitsInfo.average_performance_all !== null) {
                        // المتوسط يأتي كنسبة من 0-1، نحوله لنسبة مئوية
                        const avgPercentage = parseFloat((visitsInfo.average_performance_all * 100).toFixed(2));
                        console.log("متوسط الأداء لكل الزائرين (قيمة خام):", visitsInfo.average_performance_all);
                        console.log("متوسط الأداء لكل الزائرين (نسبة مئوية):", avgPercentage);
                        
                        averagePerformanceAllElement.textContent = `${avgPercentage}%`;
                    } else {
                        averagePerformanceAllElement.textContent = 'غير متوفر';
                    }
                }
                
                // تحديث متوسط الأداء للزائر الحالي
                const averagePerformanceCurrentElement = document.getElementById('average-performance-current');
                if (averagePerformanceCurrentElement) {
                    if (visitsInfo.average_performance_current_visitor !== undefined && visitsInfo.average_performance_current_visitor !== null) {
                        // المتوسط يأتي كنسبة من 0-1، نحوله لنسبة مئوية
                        const avgPercentage = parseFloat((visitsInfo.average_performance_current_visitor * 100).toFixed(2));
                        console.log("متوسط الأداء للزائر الحالي (قيمة خام):", visitsInfo.average_performance_current_visitor);
                        console.log("متوسط الأداء للزائر الحالي (نسبة مئوية):", avgPercentage);
                        
                        averagePerformanceCurrentElement.textContent = `${avgPercentage}%`;
                    } else {
                        averagePerformanceCurrentElement.textContent = 'غير متوفر';
                    }
                }
                
                // إذا كان هناك زيارة سابقة للزائر الحالي، نعرض تفاصيلها
                // وإلا نعرض آخر زيارة لأي زائر
                const lastVisitCurrentVisitor = visitsInfo.last_visit_current_visitor;
                const lastVisitAnyVisitor = visitsInfo.last_visit_any_visitor;
                const lastVisitToShow = lastVisitCurrentVisitor || lastVisitAnyVisitor;
                
                console.log('آخر زيارة للزائر الحالي:', lastVisitCurrentVisitor);
                console.log('آخر زيارة لأي زائر:', lastVisitAnyVisitor);
                console.log('آخر زيارة لعرضها:', lastVisitToShow);
                
                // تحديث تاريخ آخر زيارة
                const lastVisitDateElement = document.getElementById('last-visit-date');
                if (lastVisitDateElement) {
                    if (lastVisitToShow && lastVisitToShow.date) {
                        const visitDate = new Date(lastVisitToShow.date).toLocaleDateString('ar-EG');
                        lastVisitDateElement.textContent = visitDate;
                    } else {
                        lastVisitDateElement.textContent = 'غير متوفر';
                    }
                }
                
                // تحديث الصف والشعبة
                const lastVisitClassElement = document.getElementById('last-visit-class');
                if (lastVisitClassElement) {
                    if (lastVisitToShow) {
                        lastVisitClassElement.textContent = 
                            `${lastVisitToShow.grade || '-'} / ${lastVisitToShow.section || '-'}`;
                    } else {
                        lastVisitClassElement.textContent = 'غير متوفر';
                    }
                }
                
                // تحديث نسبة تقييم آخر زيارة للزائر الحالي
                const lastVisitCurrentPercentageElement = document.getElementById('last-visit-current-percentage');
                if (lastVisitCurrentPercentageElement) {
                    if (lastVisitCurrentVisitor && lastVisitCurrentVisitor.average_score !== undefined && lastVisitCurrentVisitor.average_score !== null) {
                        // average_score يأتي كنسبة من 0-1، نحوله لنسبة مئوية
                        const percentage = parseFloat((lastVisitCurrentVisitor.average_score * 100).toFixed(2));
                        console.log("نسبة آخر زيارة للزائر الحالي (قيمة خام):", lastVisitCurrentVisitor.average_score);
                        console.log("نسبة آخر زيارة للزائر الحالي (نسبة مئوية):", percentage);
                        
                        lastVisitCurrentPercentageElement.textContent = `${percentage}%`;
                    } else {
                        lastVisitCurrentPercentageElement.textContent = 'غير متوفر';
                    }
                }
                
                // تحديث نسبة تقييم آخر زيارة لأي زائر
                const lastVisitAnyPercentageElement = document.getElementById('last-visit-any-percentage');
                if (lastVisitAnyPercentageElement) {
                    const lastVisitAnyVisitor = visitsInfo.last_visit_any_visitor;
                    if (lastVisitAnyVisitor && lastVisitAnyVisitor.average_score !== undefined && lastVisitAnyVisitor.average_score !== null) {
                        // average_score يأتي كنسبة من 0-1، نحوله لنسبة مئوية
                        const percentage = parseFloat((lastVisitAnyVisitor.average_score * 100).toFixed(2));
                        console.log("نسبة آخر زيارة لأي زائر (قيمة خام):", lastVisitAnyVisitor.average_score);
                        console.log("نسبة آخر زيارة لأي زائر (نسبة مئوية):", percentage);
                        
                        lastVisitAnyPercentageElement.textContent = `${percentage}%`;
                        
                        // إضافة معلومات الزائر في tooltip
                        lastVisitAnyPercentageElement.title = `بواسطة: الزائر رقم ${lastVisitAnyVisitor.visitor_person_id || '-'} (${lastVisitAnyVisitor.visitor_type || '-'})`;
                    } else {
                        lastVisitAnyPercentageElement.textContent = 'غير متوفر';
                    }
                }
                
                // عرض توصيات المعلم من الزيارة السابقة
                const recommendationElement = document.querySelector('#last-recommendation-notes p');
                if (recommendationElement) {
                    if (lastVisitToShow && lastVisitToShow.recommendation_notes) {
                        recommendationElement.textContent = lastVisitToShow.recommendation_notes;
                        console.log('تم عرض التوصيات من:', lastVisitCurrentVisitor ? 'الزائر الحالي' : 'آخر زائر');
                    } else {
                        recommendationElement.textContent = 'لا توجد توصيات مسجلة';
                    }
                }
                
                // عرض نقاط الشكر من الزيارة السابقة
                const appreciationElement = document.querySelector('#last-appreciation-notes p');
                if (appreciationElement) {
                    if (lastVisitToShow && lastVisitToShow.appreciation_notes) {
                        appreciationElement.textContent = lastVisitToShow.appreciation_notes;
                        console.log('تم عرض نقاط الشكر من:', lastVisitCurrentVisitor ? 'الزائر الحالي' : 'آخر زائر');
                    } else {
                        appreciationElement.textContent = 'لا توجد نقاط شكر مسجلة';
                    }
                }
                
            } else {
                console.error('خطأ في تحميل معلومات الزيارات السابقة:', data.message);
                
                // تعيين رسائل افتراضية في حالة حدوث خطأ
                document.getElementById('visits-count').textContent = '0';
                document.getElementById('average-performance-all').textContent = 'غير متوفر';
                document.getElementById('average-performance-current').textContent = 'غير متوفر';
                document.getElementById('last-visit-date').textContent = 'غير متوفر';
                document.getElementById('last-visit-class').textContent = 'غير متوفر';
                document.getElementById('last-visit-current-percentage').textContent = 'غير متوفر';
                document.getElementById('last-visit-any-percentage').textContent = 'غير متوفر';
                
                const recommendationElement = document.querySelector('#last-recommendation-notes p');
                if (recommendationElement) {
                    recommendationElement.textContent = 'لا توجد توصيات مسجلة';
                }
                
                const appreciationElement = document.querySelector('#last-appreciation-notes p');
                if (appreciationElement) {
                    appreciationElement.textContent = 'لا توجد نقاط شكر مسجلة';
                }
            }
        })
        .catch(error => {
            console.error('خطأ في تحميل معلومات الزيارات السابقة:', error);
            
            // تعيين رسائل افتراضية في حالة حدوث خطأ
            document.getElementById('visits-count').textContent = '0';
            document.getElementById('average-performance-all').textContent = 'غير متوفر';
            document.getElementById('average-performance-current').textContent = 'غير متوفر';
            document.getElementById('last-visit-date').textContent = 'غير متوفر';
            document.getElementById('last-visit-class').textContent = 'غير متوفر';
            document.getElementById('last-visit-current-percentage').textContent = 'غير متوفر';
            document.getElementById('last-visit-any-percentage').textContent = 'غير متوفر';
            
            const recommendationElement = document.querySelector('#last-recommendation-notes p');
            if (recommendationElement) {
                recommendationElement.textContent = 'لا توجد توصيات مسجلة';
            }
            
            const appreciationElement = document.querySelector('#last-appreciation-notes p');
            if (appreciationElement) {
                appreciationElement.textContent = 'لا توجد نقاط شكر مسجلة';
            }
        });
}

// وظيفة للتحكم بظهور/إخفاء مؤشرات المعمل
function toggleLabIndicators() {
    const labIndicators = document.querySelectorAll('.lab-indicator');
    labIndicators.forEach(indicator => {
        indicator.style.display = hasLab ? 'block' : 'none';
    });
}

// دالة تنسيق التاريخ بشكل صحيح
function formatDate(dateStr) {
    if (!dateStr) return '';
    
    const date = new Date(dateStr);
    // تنسيق التاريخ بالعربية (يوم/شهر/سنة)
    return date.toLocaleDateString('ar-EG');
}
</script>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?> 