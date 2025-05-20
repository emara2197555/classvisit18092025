<?php
// بدء التخزين المؤقت للمخرجات
ob_start();

// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// تعيين عنوان الصفحة
$page_title = 'تقرير مقارنة أداء الصفوف والشعب';

// تضمين ملف رأس الصفحة
require_once 'includes/header.php';

// الحصول على معرف العام الدراسي المحدد من جلسة المستخدم
if (!isset($_SESSION['selected_academic_year'])) {
    // ابحث عن العام الأكاديمي النشط
    $active_year = get_active_academic_year();
    $_SESSION['selected_academic_year'] = $active_year['id'] ?? null;
    $_SESSION['selected_term'] = 'all';
}

// الحصول على معرف العام الدراسي المحدد من جلسة المستخدم أو من النموذج
$academic_year_id = isset($_GET['academic_year_id']) ? (int)$_GET['academic_year_id'] : ($_SESSION['selected_academic_year'] ?? 0);
$selected_term = isset($_GET['term']) ? $_GET['term'] : ($_SESSION['selected_term'] ?? 'all');

// الحصول على تفاصيل العام الأكاديمي المحدد
$current_year_query = "SELECT * FROM academic_years WHERE id = ?";
$current_year_data = query_row($current_year_query, [$academic_year_id]);
$academic_year = $current_year_data ? $current_year_data['name'] : '2024/2025';

// تحديد تواريخ الفصول الدراسية
$first_term_start = $current_year_data['first_term_start'] ?? null;
$first_term_end = $current_year_data['first_term_end'] ?? null;
$second_term_start = $current_year_data['second_term_start'] ?? null;
$second_term_end = $current_year_data['second_term_end'] ?? null;

// تحديد معلومات الفلتر للفصل الدراسي 
$date_filter = "";
$date_params = [];

if ($selected_term == 'first' && $first_term_start && $first_term_end) {
    $date_filter = " AND v.visit_date BETWEEN ? AND ?";
    $date_params[] = $first_term_start;
    $date_params[] = $first_term_end;
} elseif ($selected_term == 'second' && $second_term_start && $second_term_end) {
    $date_filter = " AND v.visit_date BETWEEN ? AND ?";
    $date_params[] = $second_term_start;
    $date_params[] = $second_term_end;
}

// تحديد نوع الزائر
$visitor_type_id = isset($_GET['visitor_type_id']) ? (int)$_GET['visitor_type_id'] : 0;

// تحديد المدرسة
$school_id = isset($_GET['school_id']) ? (int)$_GET['school_id'] : 0;
if (!$school_id) {
    $default_school = query_row("SELECT id FROM schools LIMIT 1");
    $school_id = $default_school ? $default_school['id'] : 0;
}

// تحديد المادة الدراسية
$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

// تحديد المعلم
$teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;

// جلب قوائم الخيارات للنماذج
$academic_years = query("SELECT id, name, is_active FROM academic_years ORDER BY is_active DESC, name DESC");
$schools = query("SELECT id, name FROM schools ORDER BY name");
$subjects = query("SELECT id, name FROM subjects ORDER BY name");
$teachers = query("SELECT id, name FROM teachers WHERE job_title = 'معلم' ORDER BY name");
$visitor_types = query("SELECT id, name FROM visitor_types ORDER BY id");

// تعديل استعلام جلب البيانات ليشمل جميع الفلاتر
$sql = "
    SELECT 
        g.id AS grade_id,
        s.id AS section_id,
        CONCAT(g.name, ' - ', s.name) AS class_name,
        COUNT(DISTINCT v.id) AS visits_count,
        
        -- متوسط تنفيذ الدرس (مجال رقم 2)
        (SELECT AVG(ve.score) * 25
         FROM visit_evaluations ve 
         JOIN visits vs ON ve.visit_id = vs.id
         JOIN evaluation_indicators ei ON ve.indicator_id = ei.id
         WHERE vs.grade_id = g.id 
           AND vs.section_id = s.id
           AND vs.school_id = ?
           " . ($visitor_type_id > 0 ? "AND vs.visitor_type_id = ?" : "") . "
           " . ($subject_id > 0 ? "AND vs.subject_id = ?" : "") . "
           " . ($teacher_id > 0 ? "AND vs.teacher_id = ?" : "") . "
           " . (!empty($date_filter) ? str_replace('v.', 'vs.', $date_filter) : "") . "
           AND ei.domain_id = 2
           AND ve.score > 0) AS lesson_execution_avg,
           
        -- متوسط الإدارة الصفية (مجال رقم 3)
        (SELECT AVG(ve.score) * 25
         FROM visit_evaluations ve 
         JOIN visits vs ON ve.visit_id = vs.id
         JOIN evaluation_indicators ei ON ve.indicator_id = ei.id
         WHERE vs.grade_id = g.id 
           AND vs.section_id = s.id
           AND vs.school_id = ?
           " . ($visitor_type_id > 0 ? "AND vs.visitor_type_id = ?" : "") . "
           " . ($subject_id > 0 ? "AND vs.subject_id = ?" : "") . "
           " . ($teacher_id > 0 ? "AND vs.teacher_id = ?" : "") . "
           " . (!empty($date_filter) ? str_replace('v.', 'vs.', $date_filter) : "") . "
           AND ei.domain_id = 3
           AND ve.score > 0) AS classroom_management_avg
    FROM 
        grades g
    CROSS JOIN
        sections s
    LEFT JOIN 
        visits v ON g.id = v.grade_id AND s.id = v.section_id 
            AND v.school_id = ?
            " . ($visitor_type_id > 0 ? "AND v.visitor_type_id = ?" : "") . "
            " . ($subject_id > 0 ? "AND v.subject_id = ?" : "") . "
            " . ($teacher_id > 0 ? "AND v.teacher_id = ?" : "") . "
            " . (!empty($date_filter) ? $date_filter : "") . "
    WHERE
        EXISTS (
            SELECT 1 
            FROM visits vs 
            WHERE vs.grade_id = g.id 
            AND vs.section_id = s.id
            AND vs.school_id = ?
            " . ($visitor_type_id > 0 ? "AND vs.visitor_type_id = ?" : "") . "
            " . ($subject_id > 0 ? "AND vs.subject_id = ?" : "") . "
            " . ($teacher_id > 0 ? "AND vs.teacher_id = ?" : "") . "
            " . (!empty($date_filter) ? str_replace('v.', 'vs.', $date_filter) : "") . "
        )
    GROUP BY 
        g.id, s.id, g.name, s.name
    ORDER BY 
        g.name, s.name
";

// تحضير المعلمات للاستعلام
$query_params = [$school_id];
if ($visitor_type_id > 0) {
    $query_params[] = $visitor_type_id;
}
if ($subject_id > 0) {
    $query_params[] = $subject_id;
}
if ($teacher_id > 0) {
    $query_params[] = $teacher_id;
}
if (!empty($date_filter)) {
    $query_params = array_merge($query_params, $date_params);
}

// تكرار المعلمات للاستعلام الفرعي الثاني
$query_params[] = $school_id;
if ($visitor_type_id > 0) {
    $query_params[] = $visitor_type_id;
}
if ($subject_id > 0) {
    $query_params[] = $subject_id;
}
if ($teacher_id > 0) {
    $query_params[] = $teacher_id;
}
if (!empty($date_filter)) {
    $query_params = array_merge($query_params, $date_params);
}

// تكرار المعلمات للاستعلام LEFT JOIN
$query_params[] = $school_id;
if ($visitor_type_id > 0) {
    $query_params[] = $visitor_type_id;
}
if ($subject_id > 0) {
    $query_params[] = $subject_id;
}
if ($teacher_id > 0) {
    $query_params[] = $teacher_id;
}
if (!empty($date_filter)) {
    $query_params = array_merge($query_params, $date_params);
}

// تكرار المعلمات للاستعلام EXISTS
$query_params[] = $school_id;
if ($visitor_type_id > 0) {
    $query_params[] = $visitor_type_id;
}
if ($subject_id > 0) {
    $query_params[] = $subject_id;
}
if ($teacher_id > 0) {
    $query_params[] = $teacher_id;
}
if (!empty($date_filter)) {
    $query_params = array_merge($query_params, $date_params);
}

$classes_data = query($sql, $query_params);

// حساب معدلات الأداء العامة
$total_lesson_execution = 0;
$total_classroom_management = 0;
$total_valid_classes_lesson = 0;
$total_valid_classes_management = 0;

// تحديد أفضل وأقل أداء
$max_lesson_execution = 0;
$min_lesson_execution = 100;
$max_classroom_management = 0;
$min_classroom_management = 100;
$max_lesson_class = '';
$min_lesson_class = '';
$max_management_class = '';
$min_management_class = '';

// حساب المتوسطات العامة وتحديد أفضل وأقل أداء
foreach ($classes_data as $class) {
    // حساب متوسط تنفيذ الدرس
    if ($class['lesson_execution_avg'] !== null) {
        $total_lesson_execution += $class['lesson_execution_avg'];
        $total_valid_classes_lesson++;
        
        // تحديد الأفضل والأقل
        if ($class['lesson_execution_avg'] > $max_lesson_execution) {
            $max_lesson_execution = $class['lesson_execution_avg'];
            $max_lesson_class = $class['class_name'];
        }
        if ($class['lesson_execution_avg'] < $min_lesson_execution && $class['lesson_execution_avg'] > 0) {
            $min_lesson_execution = $class['lesson_execution_avg'];
            $min_lesson_class = $class['class_name'];
        }
    }
    
    // حساب متوسط الإدارة الصفية
    if ($class['classroom_management_avg'] !== null) {
        $total_classroom_management += $class['classroom_management_avg'];
        $total_valid_classes_management++;
        
        // تحديد الأفضل والأقل
        if ($class['classroom_management_avg'] > $max_classroom_management) {
            $max_classroom_management = $class['classroom_management_avg'];
            $max_management_class = $class['class_name'];
        }
        if ($class['classroom_management_avg'] < $min_classroom_management && $class['classroom_management_avg'] > 0) {
            $min_classroom_management = $class['classroom_management_avg'];
            $min_management_class = $class['class_name'];
        }
    }
}

// حساب المتوسط العام
$avg_lesson_execution = $total_valid_classes_lesson > 0 ? $total_lesson_execution / $total_valid_classes_lesson : 0;
$avg_classroom_management = $total_valid_classes_management > 0 ? $total_classroom_management / $total_valid_classes_management : 0;

// في حالة عدم وجود بيانات كافية
if ($min_lesson_execution === 100) $min_lesson_execution = 0;
if ($min_classroom_management === 100) $min_classroom_management = 0;
if ($min_lesson_class === '') $min_lesson_class = '-';
if ($max_lesson_class === '') $max_lesson_class = '-';
if ($min_management_class === '') $min_management_class = '-';
if ($max_management_class === '') $max_management_class = '-';

// جلب معلومات نوع الزائر
$visitor_type_name = "جميع الزائرين";
if ($visitor_type_id > 0) {
    $visitor_type = query_row("SELECT name FROM visitor_types WHERE id = ?", [$visitor_type_id]);
    $visitor_type_name = $visitor_type ? $visitor_type['name'] : 'النائب الأكاديمي';
}
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold mb-4">تقرير مقارنة أداء الصفوف والشعب</h1>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <!-- نموذج تحديد العام الدراسي ونوع الزائر -->
        <form action="" method="get" class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="academic_year_id" class="block mb-1">العام الدراسي</label>
                    <select id="academic_year_id" name="academic_year_id" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200">
                        <?php foreach ($academic_years as $year): ?>
                            <option value="<?= $year['id'] ?>" <?= $academic_year_id == $year['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($year['name']) ?>
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
                
                <div>
                    <label for="subject_id" class="block mb-1">المادة</label>
                    <select id="subject_id" name="subject_id" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200">
                        <option value="0" <?= $subject_id == 0 ? 'selected' : '' ?>>الكل</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?= $subject['id'] ?>" <?= $subject_id == $subject['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($subject['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="teacher_id" class="block mb-1">المعلم</label>
                    <select id="teacher_id" name="teacher_id" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200">
                        <option value="0" <?= $teacher_id == 0 ? 'selected' : '' ?>>الكل</option>
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?= $teacher['id'] ?>" <?= $teacher_id == $teacher['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($teacher['name']) ?>
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
            تقرير مقارنة أداء الصفوف والشعب بناءً على المشاهدات الصفّية ل<?= htmlspecialchars($visitor_type_name) ?> للعام الأكاديمي <?= htmlspecialchars($academic_year) ?>
            <?php if ($selected_term != 'all'): ?>
                (<?= $selected_term == 'first' ? 'الفصل الأول' : 'الفصل الثاني' ?>)
            <?php endif; ?>
            <?php if ($subject_id > 0): ?>
                <?php 
                $subject_name = '';
                foreach ($subjects as $subject) { 
                    if ($subject['id'] == $subject_id) { 
                        $subject_name = $subject['name']; 
                        break; 
                    } 
                } 
                ?>
                - مادة: <?= htmlspecialchars($subject_name) ?>
            <?php endif; ?>
            <?php if ($teacher_id > 0): ?>
                <?php 
                $teacher_name = '';
                foreach ($teachers as $teacher) { 
                    if ($teacher['id'] == $teacher_id) { 
                        $teacher_name = $teacher['name']; 
                        break; 
                    } 
                } 
                ?>
                - معلم: <?= htmlspecialchars($teacher_name) ?>
            <?php endif; ?>
        </h2>
        
        <!-- جدول التقرير -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-3 px-4 border text-center font-semibold">الصف والشعبة</th>
                        <th class="py-3 px-4 border text-center font-semibold">عدد الزيارات</th>
                        <th class="py-3 px-4 border text-center font-semibold">تنفيذ الدرس</th>
                        <th class="py-3 px-4 border text-center font-semibold">الإدارة الصفية</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classes_data as $class): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-4 border text-center"><?= htmlspecialchars($class['class_name']) ?></td>
                            <td class="py-2 px-4 border text-center"><?= $class['visits_count'] ?></td>
                            <td class="py-2 px-4 border text-center">
                                <?php if ($class['lesson_execution_avg'] !== null): ?>
                                    <?= number_format($class['lesson_execution_avg'], 1) ?>%
                                <?php endif; ?>
                            </td>
                            <td class="py-2 px-4 border text-center">
                                <?php if ($class['classroom_management_avg'] !== null): ?>
                                    <?= number_format($class['classroom_management_avg'], 1) ?>%
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <!-- معدل الأداء لجميع الصفوف -->
                    <tr class="bg-green-100">
                        <td class="py-2 px-4 border text-center font-bold">معدل الأداء لجميع الصفوف</td>
                        <td class="py-2 px-4 border text-center">
                            <?= array_sum(array_column($classes_data, 'visits_count')) ?>
                        </td>
                        <td class="py-2 px-4 border text-center font-bold">
                            <?= number_format($avg_lesson_execution, 1) ?>%
                        </td>
                        <td class="py-2 px-4 border text-center font-bold">
                            <?= number_format($avg_classroom_management, 1) ?>%
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- جدول المقارنة -->
        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-3 px-4 border text-center font-semibold">جوانب المقارنة</th>
                        <th class="py-3 px-4 border text-center font-semibold">أفضل أداء</th>
                        <th class="py-3 px-4 border text-center font-semibold">أقل أداء</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 border text-center">تنفيذ الدرس</td>
                        <td class="py-2 px-4 border text-center"><?= htmlspecialchars($max_lesson_class) ?></td>
                        <td class="py-2 px-4 border text-center"><?= htmlspecialchars($min_lesson_class) ?></td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 border text-center">الإدارة الصفية</td>
                        <td class="py-2 px-4 border text-center"><?= htmlspecialchars($max_management_class) ?></td>
                        <td class="py-2 px-4 border text-center"><?= htmlspecialchars($min_management_class) ?></td>
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
    }
    
    thead.bg-gray-100 {
        background-color: #f3f4f6 !important;
        -webkit-print-color-adjust: exact;
    }
</style>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?> 