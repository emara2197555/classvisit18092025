<?php
// بدء التخزين المؤقت للمخرجات لمنع مشكلة "headers already sent"
ob_start();

// تضمين ملفات الاتصال بقاعدة البيانات والوظائف المشتركة
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// التحقق من وجود معرف الزيارة
$visit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($visit_id <= 0) {
    // إذا لم يتم تحديد معرف صحيح، توجيه المستخدم إلى صفحة الزيارات
    $_SESSION['alert_message'] = "يجب تحديد زيارة لتعديلها";
    $_SESSION['alert_type'] = "error";
    header('Location: visits.php');
    exit;
}

// استرجاع بيانات الزيارة
$visit_query = "
    SELECT 
        v.*,
        t.name AS teacher_name,
        s.name AS school_name,
        g.name AS grade_name,
        sec.name AS section_name,
        subj.name AS subject_name,
        vt.name AS visitor_type_name
    FROM 
        visits v
    JOIN 
        teachers t ON v.teacher_id = t.id
    JOIN 
        schools s ON v.school_id = s.id
    JOIN 
        grades g ON v.grade_id = g.id
    JOIN 
        sections sec ON v.section_id = sec.id
    JOIN 
        subjects subj ON v.subject_id = subj.id
    JOIN 
        visitor_types vt ON v.visitor_type_id = vt.id
    WHERE 
        v.id = ?
";

$visit = query_row($visit_query, [$visit_id]);

if (!$visit) {
    // إذا لم يتم العثور على الزيارة، توجيه المستخدم إلى صفحة الزيارات
    $_SESSION['alert_message'] = "لم يتم العثور على الزيارة المطلوبة";
    $_SESSION['alert_type'] = "error";
    header('Location: visits.php');
    exit;
}

// تحديد ما إذا كانت المادة إنجليزية
$subject_is_english = stripos($visit['subject_name'] ?? '', 'english') !== false
    || stripos($visit['subject_name'] ?? '', 'انج') !== false
    || stripos($visit['subject_name'] ?? '', 'الإنج') !== false
    || stripos($visit['subject_name'] ?? '', 'الغة الانجليزية') !== false;

// إضافة ترجمة النصوص
$texts = [
    'edit_visit' => $subject_is_english ? 'Edit Classroom Visit' : 'تعديل الزيارة الصفية',
    'visit_info' => $subject_is_english ? 'Visit Information' : 'معلومات الزيارة',
    'school' => $subject_is_english ? 'School' : 'المدرسة',
    'teacher' => $subject_is_english ? 'Teacher' : 'المعلم',
    'subject' => $subject_is_english ? 'Subject' : 'المادة',
    'grade' => $subject_is_english ? 'Grade' : 'الصف',
    'section' => $subject_is_english ? 'Section' : 'الشعبة',
    'visit_date' => $subject_is_english ? 'Visit Date' : 'تاريخ الزيارة',
    'topic' => $subject_is_english ? 'Lesson Topic' : 'موضوع الدرس',
    'visitor_type' => $subject_is_english ? 'Visitor Type' : 'نوع الزائر',
    'visitor_name' => $subject_is_english ? 'Visitor Name' : 'اسم الزائر',
    'visit_type' => $subject_is_english ? 'Visit Type' : 'نوع الزيارة',
    'attendance_type' => $subject_is_english ? 'Attendance Type' : 'نوع الحضور',
    'has_lab' => $subject_is_english ? 'Add laboratory evaluation (Science subject only)' : 'إضافة تقييم المعمل (خاص بمادة العلوم)',
    'full' => $subject_is_english ? 'Full' : 'كاملة',
    'partial' => $subject_is_english ? 'Partial' : 'جزئية',
    'physical' => $subject_is_english ? 'Physical' : 'حضوري',
    'remote' => $subject_is_english ? 'Remote' : 'عن بعد',
    'hybrid' => $subject_is_english ? 'Hybrid' : 'مختلط',
    'evaluation_domains' => $subject_is_english ? 'Evaluation Domains' : 'مجالات التقييم',
    'not_measured' => $subject_is_english ? 'Not Measured' : 'لم يتم قياسه',
    'evidence_limited' => $subject_is_english ? 'Evidence is not available or limited' : 'الأدلة غير متوفرة أو محدودة',
    'some_evidence' => $subject_is_english ? 'Some evidence is available' : 'تتوفر بعض الأدلة',
    'most_evidence' => $subject_is_english ? 'Most evidence is available' : 'تتوفر معظم الأدلة',
    'complete_evidence' => $subject_is_english ? 'Evidence is complete and effective' : 'الأدلة مستكملة وفاعلة',
    'select_recommendation' => $subject_is_english ? 'Select ready recommendation' : 'اختر توصية جاهزة',
    'no_recommendations' => $subject_is_english ? 'No ready recommendations for this indicator' : 'لا توجد توصيات جاهزة لهذا المؤشر',
    'custom_recommendation' => $subject_is_english ? 'Custom recommendation for this indicator' : 'توصية مخصصة لهذا المؤشر',
    'general_notes' => $subject_is_english ? 'General Notes' : 'ملاحظات عامة',
    'i_recommend' => $subject_is_english ? 'I recommend the teacher:' : 'أوصي المعلم بـ:',
    'i_thank' => $subject_is_english ? 'I thank the teacher for:' : 'أشكر المعلم على:',
    'save_changes' => $subject_is_english ? 'Save Changes' : 'حفظ التعديلات',
    'cancel' => $subject_is_english ? 'Cancel' : 'إلغاء',
    'back_to_visits' => $subject_is_english ? 'Back to Visits' : 'العودة للزيارات'
];


// استرجاع تقييمات الزيارة
$evaluations_query = "
    SELECT 
        ve.id,
        ve.visit_id,
        ve.indicator_id,
        ve.score,
        ve.recommendation_id,
        ve.custom_recommendation,
        ei.name AS indicator_name,
        ed.id AS domain_id,
        ed.name AS domain_name
    FROM 
        evaluation_indicators ei
    JOIN 
        evaluation_domains ed ON ei.domain_id = ed.id
    LEFT JOIN 
        visit_evaluations ve ON ve.indicator_id = ei.id AND ve.visit_id = ?
    ORDER BY 
        ed.id, ei.id
";

$evaluations = query($evaluations_query, [$visit_id]);

// خريطة ربط: evaluation_id => domain_id لتحديد مجال كل تقييم
$evaluation_domain_map = [];
foreach ($evaluations as $ev) {
    if (isset($ev['id']) && isset($ev['domain_id'])) {
        $evaluation_domain_map[(int)$ev['id']] = (int)$ev['domain_id'];
    }
}

// معالجة النموذج عند إرساله
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // بدء المعاملة
        global $pdo;
        $pdo->beginTransaction();

        // تحديث بيانات الزيارة
        $update_visit_query = "
            UPDATE visits 
            SET 
                teacher_id = ?,
                school_id = ?,
                grade_id = ?,
                section_id = ?,
                subject_id = ?,
                visitor_type_id = ?,
                visitor_person_id = ?,
                visit_date = ?,
                recommendation_notes = ?,
                appreciation_notes = ?,
                updated_at = NOW(),
                academic_year_id = ?
            WHERE 
                id = ?
        ";

        $visit_params = [
            $_POST['teacher_id'],
            $_POST['school_id'],
            $_POST['grade_id'],
            $_POST['section_id'],
            $_POST['subject_id'],
            $_POST['visitor_type_id'],
            $_POST['visitor_person_id'],
            $_POST['visit_date'],
            $_POST['recommendation_notes'],
            $_POST['appreciation_notes'],
            $_POST['academic_year_id'],
            $visit_id
        ];

        execute($update_visit_query, $visit_params);

        // تحديث تقييمات الزيارة
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'score_') === 0) {
                $evaluation_id = (int)substr($key, strlen('score_'));
                // السماح بقيمة فارغة لتعني NULL (لم يتم قياسه)
                $score = ($value === '' ? null : (int)$value);
                $notes = $_POST['notes_' . $evaluation_id] ?? '';
                $recommendation_id = $_POST['recommendation_' . $evaluation_id] ?? null;
                
                // إذا لم يكن هناك معمل، نتجاهل مؤشرات مجال المعمل (domain_id = 5)
                if ((int)($visit['has_lab'] ?? 0) === 0) {
                    $domain_for_eval = $evaluation_domain_map[$evaluation_id] ?? null;
                    if ($domain_for_eval === 5) {
                        continue;
                    }
                }
                
                // تحديث التقييم
                $update_evaluation_query = (
                    $score === null
                    ? "UPDATE visit_evaluations SET score = NULL, custom_recommendation = ?, recommendation_id = ?, updated_at = NOW() WHERE id = ?"
                    : "UPDATE visit_evaluations SET score = ?, custom_recommendation = ?, recommendation_id = ?, updated_at = NOW() WHERE id = ?"
                );

                $params = ($score === null)
                    ? [$notes, $recommendation_id ?: null, $evaluation_id]
                    : [$score, $notes, $recommendation_id ?: null, $evaluation_id];

                execute($update_evaluation_query, $params);
            }
        }

        // تأكيد المعاملة
        $pdo->commit();

        // تعيين رسالة نجاح وتوجيه المستخدم إلى صفحة معاينة الزيارة
        if ($subject_is_english) {
            $_SESSION['alert_message'] = "Visit updated successfully! You will be redirected to visit details.";
        } else {
            $_SESSION['alert_message'] = "تم تحديث الزيارة بنجاح! سيتم تحويلك إلى صفحة معاينة الزيارة.";
        }
        $_SESSION['alert_type'] = "success";
        header('Location: view_visit.php?id=' . $visit_id);
        exit;
    } catch (Exception $e) {
        // التراجع عن المعاملة في حالة حدوث خطأ
        $pdo->rollBack();
        $error_message = "حدث خطأ أثناء تحديث الزيارة: " . $e->getMessage();
    }
}

// جلب بيانات القوائم المنسدلة
$schools = query("SELECT id, name FROM schools ORDER BY name");
$visitor_types = query("SELECT id, name, name_en FROM visitor_types ORDER BY name");
$grades = query("SELECT id, name FROM grades ORDER BY name");
$sections = query("SELECT id, name FROM sections ORDER BY name");
$subjects = query("SELECT id, name FROM subjects ORDER BY name");
$teachers = query("SELECT id, name FROM teachers ORDER BY name");
$visitors = query("SELECT id, name FROM teachers ORDER BY name");
$academic_years = query("SELECT id, name FROM academic_years ORDER BY id DESC");

// جلب مجالات ومؤشرات التقييم
$domains_query = "SELECT id, name, name_en, description, description_en, weight, sort_order FROM evaluation_domains ORDER BY id";
$domains = query($domains_query);

$indicators_by_domain = [];
foreach ($domains as $domain) {
    $indicators_query = "SELECT id, domain_id, name, name_en, description, description_en, weight, sort_order FROM evaluation_indicators WHERE domain_id = ? ORDER BY id";
    $indicators_by_domain[$domain['id']] = query($indicators_query, [$domain['id']]);
}

// لم نعد بحاجة لجلب التوصيات هنا، سنقوم بجلبها مباشرة في كل مؤشر أداء

// تنظيم التقييمات حسب المؤشر (جلب الموجود فعلياً لهذه الزيارة لضمان المطابقة مع صفحة العرض)
$evaluations_by_indicator = [];
$existing_evals = query("SELECT ve.*, ei.domain_id FROM visit_evaluations ve JOIN evaluation_indicators ei ON ve.indicator_id = ei.id WHERE ve.visit_id = ? ORDER BY ve.id", [$visit_id]);
foreach ($existing_evals as $evaluation) {
    $evaluations_by_indicator[$evaluation['indicator_id']] = $evaluation;
}

// تعيين عنوان الصفحة
$page_title = $texts['edit_visit'];

// تضمين ملف رأس الصفحة
require_once 'includes/header.php';
?>

<div class="container mx-auto px-4 mt-6">
    <h1 class="text-2xl font-bold mb-6"><?= $texts['edit_visit'] ?></h1>
    
    <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border-r-4 border-red-500 text-red-700 p-4 mb-6 rounded-md">
            <?= $error_message ?>
        </div>
    <?php endif; ?>
    
    <form method="post" class="bg-white rounded-lg shadow-md p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- القسم الأول: معلومات الزيارة -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h2 class="text-lg font-semibold mb-4"><?= $texts['visit_info'] ?></h2>
                
                <!-- العام الدراسي -->
                <div class="mb-4">
                    <label for="academic_year_id" class="block mb-1"><?= $subject_is_english ? 'Academic Year' : 'العام الدراسي' ?></label>
                    <select id="academic_year_id" name="academic_year_id" class="w-full border border-gray-300 rounded-md">
                        <?php foreach ($academic_years as $year): ?>
                            <option value="<?= $year['id'] ?>" <?= $year['id'] == $visit['academic_year_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($year['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- المدرسة -->
                <div class="mb-4">
                    <label for="school_id" class="block mb-1"><?= $texts['school'] ?></label>
                    <select id="school_id" name="school_id" class="w-full border border-gray-300 rounded-md">
                        <?php foreach ($schools as $school): ?>
                            <option value="<?= $school['id'] ?>" <?= $school['id'] == $visit['school_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($school['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- الصف -->
                <div class="mb-4">
                    <label for="grade_id" class="block mb-1"><?= $texts['grade'] ?></label>
                    <select id="grade_id" name="grade_id" class="w-full border border-gray-300 rounded-md">
                        <?php foreach ($grades as $grade): ?>
                            <option value="<?= $grade['id'] ?>" <?= $grade['id'] == $visit['grade_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($grade['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- الشعبة -->
                <div class="mb-4">
                    <label for="section_id" class="block mb-1"><?= $texts['section'] ?></label>
                    <select id="section_id" name="section_id" class="w-full border border-gray-300 rounded-md">
                        <?php foreach ($sections as $section): ?>
                            <option value="<?= $section['id'] ?>" <?= $section['id'] == $visit['section_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($section['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- المادة -->
                <div class="mb-4">
                    <label for="subject_id" class="block mb-1"><?= $texts['subject'] ?></label>
                    <select id="subject_id" name="subject_id" class="w-full border border-gray-300 rounded-md">
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?= $subject['id'] ?>" <?= $subject['id'] == $visit['subject_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($subject['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- المعلم -->
                <div class="mb-4">
                    <label for="teacher_id" class="block mb-1"><?= $texts['teacher'] ?></label>
                    <select id="teacher_id" name="teacher_id" class="w-full border border-gray-300 rounded-md">
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?= $teacher['id'] ?>" <?= $teacher['id'] == $visit['teacher_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($teacher['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- القسم الثاني: معلومات الزائر وتفاصيل الزيارة -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h2 class="text-lg font-semibold mb-4">معلومات الزائر وتفاصيل الزيارة</h2>
                
                <!-- نوع الزائر -->
                <div class="mb-4">
                    <label for="visitor_type_id" class="block mb-1"><?= $texts['visitor_type'] ?></label>
                    <select id="visitor_type_id" name="visitor_type_id" class="w-full border border-gray-300 rounded-md">
                        <?php foreach ($visitor_types as $type): ?>
                            <option value="<?= $type['id'] ?>" <?= $type['id'] == $visit['visitor_type_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($subject_is_english && !empty($type['name_en']) ? $type['name_en'] : $type['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- الزائر -->
                <div class="mb-4">
                    <label for="visitor_person_id" class="block mb-1">الزائر</label>
                    <select id="visitor_person_id" name="visitor_person_id" class="w-full border border-gray-300 rounded-md">
                        <?php foreach ($visitors as $visitor): ?>
                            <option value="<?= $visitor['id'] ?>" <?= $visitor['id'] == $visit['visitor_person_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($visitor['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- تاريخ الزيارة -->
                <div class="mb-4">
                    <label for="visit_date" class="block mb-1"><?= $texts['visit_date'] ?></label>
                    <input type="date" id="visit_date" name="visit_date" class="w-full border border-gray-300 rounded-md" value="<?= $visit['visit_date'] ?>">
                </div>
                
                <!-- انصح المعلم -->
                <div class="mb-4">
                    <label for="recommendation_notes" class="block mb-1"><?= $texts['i_recommend'] ?></label>
                    <textarea id="recommendation_notes" name="recommendation_notes" rows="3" class="w-full border border-gray-300 rounded-md"><?= htmlspecialchars($visit['recommendation_notes'] ?? '') ?></textarea>
                </div>
                
                <!-- اشكر المعلم -->
                <div class="mb-4">
                    <label for="appreciation_notes" class="block mb-1"><?= $texts['i_thank'] ?></label>
                    <textarea id="appreciation_notes" name="appreciation_notes" rows="3" class="w-full border border-gray-300 rounded-md"><?= htmlspecialchars($visit['appreciation_notes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- قسم التقييم -->
        <div class="mb-6">
            <h2 class="text-xl font-semibold mb-4"><?= $texts['evaluation_domains'] ?></h2>
            
            <?php foreach ($domains as $domain): ?>
                <?php 
                // إذا لم يكن هناك معمل (has_lab = 0) نتخطى عرض مجال المعمل (id = 5)
                if (($visit['has_lab'] ?? 0) == 0 && (int)$domain['id'] === 5) { 
                    continue; 
                } 
                ?>
                <div class="mb-6">
                    <?php 
                    // ترجمة أسماء المجالات للإنجليزية
                    $domain_display_name = $subject_is_english && !empty($domain['name_en']) ? $domain['name_en'] : $domain['name'];
                    ?>
                    <h3 class="text-lg font-medium mb-3 bg-primary-100 p-2 rounded-md"><?= htmlspecialchars($domain_display_name) ?></h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-200">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="px-4 py-2 border text-right" width="50%">
                                        <?= $subject_is_english ? 'Indicator' : 'المؤشر' ?>
                                    </th>
                                    <th class="px-4 py-2 border text-center" width="15%">
                                        <?= $subject_is_english ? 'Evaluation' : 'التقييم' ?>
                                    </th>
                                    <th class="px-4 py-2 border text-right" width="15%">
                                        <?= $subject_is_english ? 'Recommendation' : 'التوصية' ?>
                                    </th>
                                    <th class="px-4 py-2 border text-right" width="20%">
                                        <?= $subject_is_english ? 'Notes' : 'ملاحظات' ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($indicators_by_domain[$domain['id']])): ?>
                                    <?php foreach ($indicators_by_domain[$domain['id']] as $indicator): ?>
                                        <?php 
                                        // الحصول على بيانات التقييم بشكل آمن
                                        $evaluation = $evaluations_by_indicator[$indicator['id']] ?? null;
                                        // في قاعدة البيانات: NULL = لم يتم قياسه، 0=ضعيف، 1=مقبول، 2=جيد، 3=ممتاز
                                        $score = ($evaluation && $evaluation['score'] !== null) ? (int)$evaluation['score'] : null;
                                        // عرض الملاحظات من الحقل الصحيح في قاعدة البيانات
                                        $notes = $evaluation && isset($evaluation['custom_recommendation']) ? $evaluation['custom_recommendation'] : '';
                                        $recommendation_id = $evaluation && isset($evaluation['recommendation_id']) ? $evaluation['recommendation_id'] : null;
                                        $evaluation_id = $evaluation ? $evaluation['id'] : 0;
                                        ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2 border">
                                                <?= htmlspecialchars($subject_is_english && !empty($indicator['name_en']) ? $indicator['name_en'] : $indicator['name']) ?>
                                            </td>
                                            <td class="px-4 py-2 border text-center">
                                                <select name="score_<?= $evaluation_id ?>" class="border border-gray-300 rounded-md <?= is_null($score) ? 'score-null' : ('score-' . (int)$score) ?>">
                                                    <option value="" <?= is_null($score) ? 'selected' : '' ?>><?= $texts['not_measured'] ?></option>
                                                    <option value="0" <?= $score === 0 ? 'selected' : '' ?>><?= $texts['evidence_limited'] ?></option>
                                                    <option value="1" <?= $score === 1 ? 'selected' : '' ?>><?= $texts['some_evidence'] ?></option>
                                                    <option value="2" <?= $score === 2 ? 'selected' : '' ?>><?= $texts['most_evidence'] ?></option>
                                                    <option value="3" <?= $score === 3 ? 'selected' : '' ?>><?= $texts['complete_evidence'] ?></option>
                                                </select>
                                            </td>
                                            <td class="px-4 py-2 border">
                                                <select name="recommendation_<?= $evaluation_id ?>" class="border border-gray-300 rounded-md w-full">
                                                    <option value=""><?= $subject_is_english ? 'No recommendation' : 'بدون توصية' ?></option>
                                                    <?php 
                                                    // جلب التوصيات المرتبطة بمؤشر الأداء الحالي
                                                    $indicator_recommendations = query("SELECT id, indicator_id, text, text_en FROM recommendations WHERE indicator_id = ? ORDER BY id", [$indicator['id']]);
                                                    foreach ($indicator_recommendations as $recommendation): 
                                                    ?>
                                                        <option value="<?= $recommendation['id'] ?>" <?= $recommendation_id == $recommendation['id'] ? 'selected' : '' ?>>
                                                            <?php 
                                                            $rec_text = $subject_is_english && !empty($recommendation['text_en']) ? $recommendation['text_en'] : $recommendation['text'];
                                                            echo htmlspecialchars(mb_substr($rec_text, 0, 40) . (mb_strlen($rec_text) > 40 ? '...' : ''));
                                                            ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td class="px-4 py-2 border">
                                                <input type="text" name="notes_<?= $evaluation_id ?>" class="border border-gray-300 rounded-md w-full" value="<?= htmlspecialchars($notes ?? '') ?>">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="px-4 py-2 border text-center">لا توجد مؤشرات لهذا المجال</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="flex justify-between">
            <button type="submit" class="bg-primary-600 text-white px-6 py-2 rounded-md hover:bg-primary-700 transition-colors"><?= $texts['save_changes'] ?></button>
            <a href="visits.php" class="bg-gray-300 text-gray-800 px-6 py-2 rounded-md hover:bg-gray-400 transition-colors"><?= $texts['cancel'] ?></a>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // تحديث ألوان حقول التقييم
        updateSelectColor();
        
        // تحديث ألوان حقول التقييم عند التغيير
        function updateSelectColor() {
            const selects = document.querySelectorAll('select[name^="score_"]');
            selects.forEach(select => {
                select.addEventListener('change', function() {
                    // إزالة جميع الكلاسات السابقة
                    this.classList.remove('score-0', 'score-1', 'score-2', 'score-3', 'score-4');
                    // إضافة الكلاس الجديد
                    this.classList.add(`score-${this.value}`);
                });
                
                // تطبيق الكلاس الحالي
                select.classList.add(`score-${select.value}`);
            });
        }
        
        // تحديث المواد عند تغيير المدرسة
        document.getElementById('school_id').addEventListener('change', function() {
            const schoolId = this.value;
            
            // تحديث قائمة المواد الدراسية
            fetch(`api/get_subjects_by_school.php?school_id=${schoolId}`)
                .then(response => response.json())
                .then(data => {
                    const subjectSelect = document.getElementById('subject_id');
                    const currentSubject = subjectSelect.value;
                    
                    // إعادة بناء قائمة المواد
                    subjectSelect.innerHTML = '';
                    data.forEach(subject => {
                        const option = document.createElement('option');
                        option.value = subject.id;
                        option.textContent = subject.name;
                        if (subject.id == currentSubject) {
                            option.selected = true;
                        }
                        subjectSelect.appendChild(option);
                    });
                    
                    // تحديث قائمة المعلمين
                    updateTeachersList(schoolId, subjectSelect.value);
                })
                .catch(error => console.error('خطأ في جلب المواد الدراسية:', error));
        });
        
        // تحديث المعلمين عند تغيير المادة
        document.getElementById('subject_id').addEventListener('change', function() {
            const schoolId = document.getElementById('school_id').value;
            const subjectId = this.value;
            
            // تحديث قائمة المعلمين
            updateTeachersList(schoolId, subjectId);
        });
        
        // دالة لتحديث قائمة المعلمين
        function updateTeachersList(schoolId, subjectId) {
            fetch(`api/get_teachers_by_school_subject.php?school_id=${schoolId}&subject_id=${subjectId}`)
                .then(response => response.json())
                .then(data => {
                    const teacherSelect = document.getElementById('teacher_id');
                    const currentTeacher = teacherSelect.value;
                    
                    // إعادة بناء قائمة المعلمين
                    teacherSelect.innerHTML = '';
                    data.forEach(teacher => {
                        const option = document.createElement('option');
                        option.value = teacher.id;
                        option.textContent = teacher.name;
                        if (teacher.id == currentTeacher) {
                            option.selected = true;
                        }
                        teacherSelect.appendChild(option);
                    });
                })
                .catch(error => console.error('خطأ في جلب المعلمين:', error));
        }
    });
</script>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?> 