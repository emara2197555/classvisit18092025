<?php
// بدء التخزين المؤقت للمخرجات
ob_start();

// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// تعيين عنوان الصفحة
$page_title = 'تقرير مقارنة أداء المواد الدراسية';
$current_page = 'subject_performance_report.php';

// تضمين ملف رأس الصفحة
require_once 'includes/header.php';

// تحديد العام الدراسي
$academic_year_id = isset($_GET['academic_year_id']) ? (int)$_GET['academic_year_id'] : 0;
$academic_year_name = '';
$year = null;

// جلب العام الدراسي النشط إذا لم يتم تحديد عام
if (!$academic_year_id) {
    $active_year = get_active_academic_year();
    $academic_year_id = $active_year ? $active_year['id'] : 0;
    $academic_year_name = $active_year ? $active_year['name'] : '';
} else {
    $year = query_row("SELECT name, first_term_start, first_term_end, second_term_start, second_term_end FROM academic_years WHERE id = ?", [$academic_year_id]);
    $academic_year_name = $year ? $year['name'] : '';
}

// تحديد الفصل الدراسي
$selected_term = isset($_GET['term']) ? $_GET['term'] : 'all';

// تحديد معلومات الفلتر للفصل الدراسي
$date_filter = "";
$date_params = [];

if ($academic_year_id > 0 && $year) {
    if ($selected_term == 'first' && !empty($year['first_term_start']) && !empty($year['first_term_end'])) {
        $date_filter = " AND vs.visit_date BETWEEN ? AND ?";
        $date_params[] = $year['first_term_start'];
        $date_params[] = $year['first_term_end'];
    } elseif ($selected_term == 'second' && !empty($year['second_term_start']) && !empty($year['second_term_end'])) {
        $date_filter = " AND vs.visit_date BETWEEN ? AND ?";
        $date_params[] = $year['second_term_start'];
        $date_params[] = $year['second_term_end'];
    }
}

// تحديد نوع الزائر (اختياري)
$visitor_type_id = isset($_GET['visitor_type_id']) ? (int)$_GET['visitor_type_id'] : 0;

// تحديد المدرسة (اختياري)
$school_id = isset($_GET['school_id']) ? (int)$_GET['school_id'] : 0;

// جلب الأعوام الدراسية للاختيار
$academic_years = query("SELECT id, name, is_active FROM academic_years ORDER BY is_active DESC, name DESC");

// بناء استعلام لجلب المواد الدراسية مع متوسطات التقييم
$sql = "
    SELECT 
        s.id AS subject_id,
        s.name AS subject_name,
        COUNT(DISTINCT v.id) AS visits_count,
        COUNT(DISTINCT v.teacher_id) AS teachers_count,
        
        -- متوسط التخطيط (مجال رقم 1)
        (SELECT (SUM(ve.score) / (COUNT(ve.score) * 3)) * 100
         FROM visit_evaluations ve 
         JOIN visits vs ON ve.visit_id = vs.id
         JOIN evaluation_indicators ei ON ve.indicator_id = ei.id
         WHERE vs.subject_id = s.id 
           " . ($academic_year_id > 0 ? "AND vs.academic_year_id = ?" : "") . "
           " . ($visitor_type_id > 0 ? "AND vs.visitor_type_id = ?" : "") . "
           " . ($school_id > 0 ? "AND vs.school_id = ?" : "") . "
           " . $date_filter . "
           AND ei.domain_id = 1
           AND ve.score IS NOT NULL) AS planning_avg,
        
        -- متوسط تنفيذ الدرس (مجال رقم 2)
        (SELECT (SUM(ve.score) / (COUNT(ve.score) * 3)) * 100
         FROM visit_evaluations ve 
         JOIN visits vs ON ve.visit_id = vs.id
         JOIN evaluation_indicators ei ON ve.indicator_id = ei.id
         WHERE vs.subject_id = s.id 
           " . ($academic_year_id > 0 ? "AND vs.academic_year_id = ?" : "") . "
           " . ($visitor_type_id > 0 ? "AND vs.visitor_type_id = ?" : "") . "
           " . ($school_id > 0 ? "AND vs.school_id = ?" : "") . "
           " . $date_filter . "
           AND ei.domain_id = 2
           AND ve.score IS NOT NULL) AS lesson_execution_avg,
           
        -- متوسط الإدارة الصفية (مجال رقم 3)
        (SELECT (SUM(ve.score) / (COUNT(ve.score) * 3)) * 100
         FROM visit_evaluations ve 
         JOIN visits vs ON ve.visit_id = vs.id
         JOIN evaluation_indicators ei ON ve.indicator_id = ei.id
         WHERE vs.subject_id = s.id 
           " . ($academic_year_id > 0 ? "AND vs.academic_year_id = ?" : "") . "
           " . ($visitor_type_id > 0 ? "AND vs.visitor_type_id = ?" : "") . "
           " . ($school_id > 0 ? "AND vs.school_id = ?" : "") . "
           " . $date_filter . "
           AND ei.domain_id = 3
           AND ve.score IS NOT NULL) AS classroom_management_avg,
           
        -- متوسط التقويم (مجال رقم 4)
        (SELECT (SUM(ve.score) / (COUNT(ve.score) * 3)) * 100
         FROM visit_evaluations ve 
         JOIN visits vs ON ve.visit_id = vs.id
         JOIN evaluation_indicators ei ON ve.indicator_id = ei.id
         WHERE vs.subject_id = s.id 
           " . ($academic_year_id > 0 ? "AND vs.academic_year_id = ?" : "") . "
           " . ($visitor_type_id > 0 ? "AND vs.visitor_type_id = ?" : "") . "
           " . ($school_id > 0 ? "AND vs.school_id = ?" : "") . "
           " . $date_filter . "
           AND ei.domain_id = 4
           AND ve.score IS NOT NULL) AS evaluation_avg,
           
        -- متوسط النشاط العملي (مجال رقم 5)
        (SELECT (SUM(ve.score) / (COUNT(ve.score) * 3)) * 100
         FROM visit_evaluations ve 
         JOIN visits vs ON ve.visit_id = vs.id
         JOIN evaluation_indicators ei ON ve.indicator_id = ei.id
         WHERE vs.subject_id = s.id 
           " . ($academic_year_id > 0 ? "AND vs.academic_year_id = ?" : "") . "
           " . ($visitor_type_id > 0 ? "AND vs.visitor_type_id = ?" : "") . "
           " . ($school_id > 0 ? "AND vs.school_id = ?" : "") . "
           " . $date_filter . "
           AND ei.domain_id = 5
           AND ve.score IS NOT NULL) AS practical_avg,
           
        -- المتوسط العام
        (SELECT (SUM(ve.score) / (COUNT(ve.score) * 3)) * 100
         FROM visit_evaluations ve 
         JOIN visits vs ON ve.visit_id = vs.id
         WHERE vs.subject_id = s.id 
           " . ($academic_year_id > 0 ? "AND vs.academic_year_id = ?" : "") . "
           " . ($visitor_type_id > 0 ? "AND vs.visitor_type_id = ?" : "") . "
           " . ($school_id > 0 ? "AND vs.school_id = ?" : "") . "
           " . $date_filter . "
           AND ve.score IS NOT NULL) AS overall_avg
    FROM 
        subjects s
    LEFT JOIN 
        visits v ON s.id = v.subject_id
        " . ($academic_year_id > 0 ? "AND v.academic_year_id = ?" : "") . "
        " . ($visitor_type_id > 0 ? "AND v.visitor_type_id = ?" : "") . "
        " . ($school_id > 0 ? "AND v.school_id = ?" : "") . "
        " . (!empty($date_filter) ? str_replace('vs.', 'v.', $date_filter) : "") . "
    GROUP BY 
        s.id, s.name
    ORDER BY 
        overall_avg DESC, s.name
";

// تحضير المعلمات للاستعلام
$query_params = [];

// المعلمات للاستعلام الفرعي الأول (التخطيط)
if ($academic_year_id > 0) {
    $query_params[] = $academic_year_id;
}
if ($visitor_type_id > 0) {
    $query_params[] = $visitor_type_id;
}
if ($school_id > 0) {
    $query_params[] = $school_id;
}
if (!empty($date_filter)) {
    $query_params = array_merge($query_params, $date_params);
}

// المعلمات للاستعلام الفرعي الثاني (تنفيذ الدرس)
if ($academic_year_id > 0) {
    $query_params[] = $academic_year_id;
}
if ($visitor_type_id > 0) {
    $query_params[] = $visitor_type_id;
}
if ($school_id > 0) {
    $query_params[] = $school_id;
}
if (!empty($date_filter)) {
    $query_params = array_merge($query_params, $date_params);
}

// المعلمات للاستعلام الفرعي الثالث (الإدارة الصفية)
if ($academic_year_id > 0) {
    $query_params[] = $academic_year_id;
}
if ($visitor_type_id > 0) {
    $query_params[] = $visitor_type_id;
}
if ($school_id > 0) {
    $query_params[] = $school_id;
}
if (!empty($date_filter)) {
    $query_params = array_merge($query_params, $date_params);
}

// المعلمات للاستعلام الفرعي الرابع (التقويم)
if ($academic_year_id > 0) {
    $query_params[] = $academic_year_id;
}
if ($visitor_type_id > 0) {
    $query_params[] = $visitor_type_id;
}
if ($school_id > 0) {
    $query_params[] = $school_id;
}
if (!empty($date_filter)) {
    $query_params = array_merge($query_params, $date_params);
}

// المعلمات للاستعلام الفرعي الخامس (النشاط العملي)
if ($academic_year_id > 0) {
    $query_params[] = $academic_year_id;
}
if ($visitor_type_id > 0) {
    $query_params[] = $visitor_type_id;
}
if ($school_id > 0) {
    $query_params[] = $school_id;
}
if (!empty($date_filter)) {
    $query_params = array_merge($query_params, $date_params);
}

// المعلمات للاستعلام الفرعي السادس (المتوسط العام)
if ($academic_year_id > 0) {
    $query_params[] = $academic_year_id;
}
if ($visitor_type_id > 0) {
    $query_params[] = $visitor_type_id;
}
if ($school_id > 0) {
    $query_params[] = $school_id;
}
if (!empty($date_filter)) {
    $query_params = array_merge($query_params, $date_params);
}

// المعلمات للاستعلام الرئيسي LEFT JOIN
if ($academic_year_id > 0) {
    $query_params[] = $academic_year_id;
}
if ($visitor_type_id > 0) {
    $query_params[] = $visitor_type_id;
}
if ($school_id > 0) {
    $query_params[] = $school_id;
}
if (!empty($date_filter)) {
    $query_params = array_merge($query_params, $date_params);
}

$subjects_data = query($sql, $query_params);

// حساب المتوسطات العامة لجميع المواد
$total_planning = 0;
$total_lesson_execution = 0;
$total_classroom_management = 0;
$total_evaluation = 0;
$total_practical = 0;
$total_overall = 0;

$valid_subjects_count = 0;
foreach ($subjects_data as $subject) {
    if ($subject['overall_avg'] !== null) {
        $valid_subjects_count++;
        $total_planning += $subject['planning_avg'] ?? 0;
        $total_lesson_execution += $subject['lesson_execution_avg'] ?? 0;
        $total_classroom_management += $subject['classroom_management_avg'] ?? 0;
        $total_evaluation += $subject['evaluation_avg'] ?? 0;
        $total_practical += $subject['practical_avg'] ?? 0;
        $total_overall += $subject['overall_avg'] ?? 0;
    }
}

$avg_planning = $valid_subjects_count > 0 ? ($total_planning / $valid_subjects_count) : 0;
$avg_lesson_execution = $valid_subjects_count > 0 ? ($total_lesson_execution / $valid_subjects_count) : 0;
$avg_classroom_management = $valid_subjects_count > 0 ? ($total_classroom_management / $valid_subjects_count) : 0;
$avg_evaluation = $valid_subjects_count > 0 ? ($total_evaluation / $valid_subjects_count) : 0;
$avg_practical = $valid_subjects_count > 0 ? ($total_practical / $valid_subjects_count) : 0;
$avg_overall = $valid_subjects_count > 0 ? ($total_overall / $valid_subjects_count) : 0;

// جلب قائمة المدارس وأنواع الزائرين للفلترة
$schools = query("SELECT id, name FROM schools ORDER BY name");
$visitor_types = query("SELECT id, name FROM visitor_types ORDER BY id");

?>

<div class="mb-6">
    <h1 class="text-2xl font-bold mb-4">تقرير مقارنة أداء المواد الدراسية</h1>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <!-- نموذج تحديد الفلاتر -->
        <form action="" method="get" class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="academic_year_id" class="block mb-1">العام الدراسي</label>
                    <select id="academic_year_id" name="academic_year_id" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200">
                        <option value="0">اختر العام الدراسي...</option>
                        <?php foreach ($academic_years as $year): ?>
                            <option value="<?= $year['id'] ?>" <?= $academic_year_id == $year['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($year['name']) ?> <?= $year['is_active'] ? '(نشط)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="term" class="block mb-1">الفصل الدراسي</label>
                    <select id="term" name="term" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200">
                        <option value="all" <?= $selected_term == 'all' ? 'selected' : '' ?>>الكل</option>
                        <option value="first" <?= $selected_term == 'first' ? 'selected' : '' ?>>الفصل الأول</option>
                        <option value="second" <?= $selected_term == 'second' ? 'selected' : '' ?>>الفصل الثاني</option>
                    </select>
                </div>
                
                <div>
                    <label for="visitor_type_id" class="block mb-1">نوع الزائر</label>
                    <select id="visitor_type_id" name="visitor_type_id" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200">
                        <option value="0" <?= $visitor_type_id == 0 ? 'selected' : '' ?>>الكل</option>
                        <?php foreach ($visitor_types as $type): ?>
                            <option value="<?= $type['id'] ?>" <?= $visitor_type_id == $type['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="school_id" class="block mb-1">المدرسة</label>
                    <select id="school_id" name="school_id" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200">
                        <option value="0" <?= $school_id == 0 ? 'selected' : '' ?>>الكل</option>
                        <?php foreach ($schools as $school): ?>
                            <option value="<?= $school['id'] ?>" <?= $school_id == $school['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($school['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="bg-primary-600 text-white px-4 py-2 rounded-md hover:bg-primary-700 transition-colors">
                        عرض التقرير
                    </button>
                </div>
            </div>
        </form>
        
        <h2 class="text-xl font-semibold mb-4 text-center">
            تقرير مقارنة أداء المواد الدراسية للعام الأكاديمي <?= htmlspecialchars($academic_year_name ?: 'الكل') ?>
            <?php if ($selected_term != 'all'): ?>
                (<?= $selected_term == 'first' ? 'الفصل الأول' : 'الفصل الثاني' ?>)
            <?php endif; ?>
            <?php if ($school_id > 0): ?>
                <?php 
                $school_name = '';
                foreach ($schools as $school) { 
                    if ($school['id'] == $school_id) { 
                        $school_name = $school['name']; 
                        break; 
                    } 
                } 
                ?>
                - مدرسة: <?= htmlspecialchars($school_name) ?>
            <?php endif; ?>
            <?php if ($visitor_type_id > 0): ?>
                <?php 
                $visitor_type_name = '';
                foreach ($visitor_types as $type) { 
                    if ($type['id'] == $visitor_type_id) { 
                        $visitor_type_name = $type['name']; 
                        break; 
                    } 
                } 
                ?>
                - نوع الزائر: <?= htmlspecialchars($visitor_type_name) ?>
            <?php endif; ?>
        </h2>
        
        <?php if (empty($subjects_data)): ?>
            <div class="bg-yellow-100 text-yellow-800 p-4 rounded-md mb-4">
                لم يتم العثور على بيانات للمواد الدراسية بناءً على المعايير المحددة.
            </div>
        <?php else: ?>
            <!-- جدول التقرير -->
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-3 px-4 border text-center font-semibold">المادة الدراسية</th>
                            <th class="py-3 px-4 border text-center font-semibold">عدد المعلمين</th>
                            <th class="py-3 px-4 border text-center font-semibold">عدد الزيارات</th>
                            <th class="py-3 px-4 border text-center font-semibold">التخطيط</th>
                            <th class="py-3 px-4 border text-center font-semibold">تنفيذ الدرس</th>
                            <th class="py-3 px-4 border text-center font-semibold">الإدارة الصفية</th>
                            <th class="py-3 px-4 border text-center font-semibold">التقويم</th>
                            <th class="py-3 px-4 border text-center font-semibold">النشاط العملي</th>
                            <th class="py-3 px-4 border text-center font-semibold">المتوسط العام</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects_data as $subject): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-2 px-4 border text-center font-semibold">
                                    <a href="subject_detailed_report.php?subject_id=<?= $subject['subject_id'] ?>" class="text-blue-600 hover:text-blue-800">
                                        <?= htmlspecialchars($subject['subject_name']) ?>
                                    </a>
                                </td>
                                <td class="py-2 px-4 border text-center"><?= $subject['teachers_count'] ?></td>
                                <td class="py-2 px-4 border text-center"><?= $subject['visits_count'] ?></td>
                                <td class="py-2 px-4 border text-center">
                                    <?php if ($subject['planning_avg'] !== null): ?>
                                        <?= number_format($subject['planning_avg'], 1) ?>%
                                    <?php endif; ?>
                                </td>
                                <td class="py-2 px-4 border text-center">
                                    <?php if ($subject['lesson_execution_avg'] !== null): ?>
                                        <?= number_format($subject['lesson_execution_avg'], 1) ?>%
                                    <?php endif; ?>
                                </td>
                                <td class="py-2 px-4 border text-center">
                                    <?php if ($subject['classroom_management_avg'] !== null): ?>
                                        <?= number_format($subject['classroom_management_avg'], 1) ?>%
                                    <?php endif; ?>
                                </td>
                                <td class="py-2 px-4 border text-center">
                                    <?php if ($subject['evaluation_avg'] !== null): ?>
                                        <?= number_format($subject['evaluation_avg'], 1) ?>%
                                    <?php endif; ?>
                                </td>
                                <td class="py-2 px-4 border text-center">
                                    <?php if ($subject['practical_avg'] !== null): ?>
                                        <?= number_format($subject['practical_avg'], 1) ?>%
                                    <?php endif; ?>
                                </td>
                                <td class="py-2 px-4 border text-center font-bold">
                                    <?php if ($subject['overall_avg'] !== null): ?>
                                        <?= number_format($subject['overall_avg'], 1) ?>%
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <!-- صف المتوسط العام -->
                        <tr class="bg-green-100">
                            <td class="py-2 px-4 border text-center font-bold" colspan="3">المتوسط العام</td>
                            <td class="py-2 px-4 border text-center font-bold"><?= number_format($avg_planning, 1) ?>%</td>
                            <td class="py-2 px-4 border text-center font-bold"><?= number_format($avg_lesson_execution, 1) ?>%</td>
                            <td class="py-2 px-4 border text-center font-bold"><?= number_format($avg_classroom_management, 1) ?>%</td>
                            <td class="py-2 px-4 border text-center font-bold"><?= number_format($avg_evaluation, 1) ?>%</td>
                            <td class="py-2 px-4 border text-center font-bold"><?= number_format($avg_practical, 1) ?>%</td>
                            <td class="py-2 px-4 border text-center font-bold"><?= number_format($avg_overall, 1) ?>%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- زر الطباعة -->
            <div class="mt-6 text-center">
                <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                    <i class="bi bi-printer ml-2"></i> طباعة التقرير
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<style media="print">
    @page {
        size: landscape;
    }
    
    header, nav, footer, form, button {
        display: none !important;
    }
    
    body {
        background-color: white;
    }
    
    h2 {
        margin-top: 0;
        margin-bottom: 20px;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    th, td {
        border: 1px solid #000;
        padding: 8px;
        text-align: center;
    }
    
    tr.bg-green-100 {
        background-color: #d1fae5 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    thead.bg-gray-100 {
        background-color: #f3f4f6 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
</style>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?>
