<?php
// بدء التخزين المؤقت للمخرجات
ob_start();

// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';
require_once 'includes/auth_functions.php';

// حماية الصفحة
protect_page();

// تحديد البيانات بناءً على دور المستخدم
$user_role = $_SESSION['role_name'] ?? '';
$is_coordinator = ($user_role === 'Subject Coordinator');
$coordinator_subject_id = null;
$coordinator_school_id = null;

if ($is_coordinator) {
    // جلب معلومات المنسق
    $coordinator = query("
        SELECT cs.subject_id, u.school_id 
        FROM coordinator_supervisors cs
        JOIN users u ON cs.user_id = u.id
        WHERE cs.user_id = ?
    ", [$_SESSION['user_id']]);
    
    if (!empty($coordinator)) {
        $coordinator_subject_id = $coordinator[0]['subject_id'];
        $coordinator_school_id = $coordinator[0]['school_id'];
    }
}

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

// للمنسق: تحديد مدرسته تلقائياً
if ($is_coordinator && $coordinator_school_id) {
    $school_id = $coordinator_school_id;
} elseif (!$school_id && !$is_coordinator) {
    $default_school = query_row("SELECT id FROM schools LIMIT 1");
    $school_id = $default_school ? $default_school['id'] : 0;
}

// تحديد المادة الدراسية
$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

// للمنسق: تحديد مادته تلقائياً
if ($is_coordinator && $coordinator_subject_id) {
    $subject_id = $coordinator_subject_id;
}

// تحديد المعلم
$teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;

// جلب قوائم الخيارات للنماذج
$academic_years = query("SELECT id, name, is_active FROM academic_years ORDER BY is_active DESC, name DESC");

// للمديرين: جلب جميع المدارس، للمنسقين: لا حاجة للمدارس
if (!$is_coordinator) {
    $schools = query("SELECT id, name FROM schools ORDER BY name");
} else {
    $schools = [];
}

// للمنسقين: جلب مادتهم فقط، للمديرين: جلب جميع المواد
if ($is_coordinator && $coordinator_subject_id) {
    $subjects = query("SELECT id, name FROM subjects WHERE id = ? ORDER BY name", [$coordinator_subject_id]);
} else {
    $subjects = query("SELECT id, name FROM subjects ORDER BY name");
}

// للمنسقين: جلب معلمي مادتهم ومدرستهم فقط
if ($is_coordinator && $coordinator_subject_id && $coordinator_school_id) {
    $teachers = query("
        SELECT DISTINCT t.id, t.name 
        FROM teachers t
        JOIN teacher_subjects ts ON t.id = ts.teacher_id
        WHERE t.job_title = 'معلم' 
        AND ts.subject_id = ? 
        AND t.school_id = ?
        ORDER BY t.name
    ", [$coordinator_subject_id, $coordinator_school_id]);
} else {
    $teachers = query("SELECT id, name FROM teachers WHERE job_title = 'معلم' ORDER BY name");
}

$visitor_types = query("SELECT id, name FROM visitor_types ORDER BY id");

// تعديل استعلام جلب البيانات ليشمل جميع الفلاتر
$sql = "
    SELECT 
        g.id AS grade_id,
        s.id AS section_id,
        CONCAT(g.name, ' - ', s.name) AS class_name,
        COUNT(DISTINCT v.id) AS visits_count,
        
        -- متوسط تنفيذ الدرس (مجال رقم 2)
        (SELECT AVG(ve.score) * (100/3)
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
        (SELECT AVG(ve.score) * (100/3)
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
    
    <?php if ($is_coordinator): ?>
        <div class="mb-4 p-3 bg-blue-100 text-blue-800 rounded">
            <strong>مرحباً بك كمنسق مادة!</strong> 
            أنت تعرض تقرير أداء الصفوف في مادتك ومدرستك فقط.
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <!-- نموذج تحديد العام الدراسي ونوع الزائر -->
        <form action="" method="get" class="mb-6 no-print">
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
        
        <div class="bg-gradient-to-br from-white to-blue-50 rounded-xl shadow-lg border border-blue-100 mb-6">
            <!-- العنوان المحسن -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-t-xl p-6">
                <h2 class="text-xl font-bold flex items-center">
                    <i class="fas fa-chart-line text-blue-200 ml-3"></i>
                    تقرير مقارنة أداء الصفوف والشعب
                </h2>
                <div class="text-blue-100 text-sm mt-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="bg-blue-500 bg-opacity-30 px-2 py-1 rounded">للعام الأكاديمي <?= htmlspecialchars($academic_year) ?></span>
                        <?php if ($selected_term != 'all'): ?>
                            <span class="bg-blue-500 bg-opacity-30 px-2 py-1 rounded">(<?= $selected_term == 'first' ? 'الفصل الأول' : 'الفصل الثاني' ?>)</span>
                        <?php endif; ?>
                        <span class="bg-blue-500 bg-opacity-30 px-2 py-1 rounded"><?= htmlspecialchars($visitor_type_name) ?></span>
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
                            <span class="bg-green-500 bg-opacity-30 px-2 py-1 rounded">مادة: <?= htmlspecialchars($subject_name) ?></span>
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
                            <span class="bg-purple-500 bg-opacity-30 px-2 py-1 rounded">معلم: <?= htmlspecialchars($teacher_name) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- جدول التقرير المحسن -->
            <div class="p-6">
                <div class="overflow-x-auto rounded-lg shadow-sm">
                    <table class="min-w-full bg-white">
                        <thead>
                            <tr class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
                                <th class="py-4 px-6 text-right font-bold">
                                    <i class="fas fa-chalkboard-teacher ml-2"></i>
                                    الصف والشعبة
                                </th>
                                <th class="py-4 px-6 text-center font-bold">
                                    <i class="fas fa-eye ml-2"></i>
                                    عدد الزيارات
                                </th>
                                <th class="py-4 px-6 text-center font-bold">
                                    <i class="fas fa-tasks ml-2"></i>
                                    تنفيذ الدرس
                                </th>
                                <th class="py-4 px-6 text-center font-bold">
                                    <i class="fas fa-users-cog ml-2"></i>
                                    الإدارة الصفية
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($classes_data as $index => $class): ?>
                                <tr class="hover:bg-blue-50 transition-colors duration-200 <?= $index % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?>">
                                    <td class="py-4 px-6 font-semibold text-gray-800">
                                        <div class="flex items-center">
                                            <div class="bg-indigo-100 rounded-full p-2 ml-3">
                                                <i class="fas fa-graduation-cap text-indigo-600"></i>
                                            </div>
                                            <?= htmlspecialchars($class['class_name']) ?>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-semibold">
                                            <?= $class['visits_count'] ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <?php if ($class['lesson_execution_avg'] !== null): ?>
                                            <?php 
                                            $lesson_score = $class['lesson_execution_avg'];
                                            $lesson_color = $lesson_score >= 80 ? 'green' : ($lesson_score >= 60 ? 'yellow' : 'red');
                                            ?>
                                            <span class="bg-<?= $lesson_color ?>-100 text-<?= $lesson_color ?>-800 px-3 py-2 rounded-full font-bold text-sm">
                                                <?= number_format($lesson_score, 1) ?>%
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <?php if ($class['classroom_management_avg'] !== null): ?>
                                            <?php 
                                            $management_score = $class['classroom_management_avg'];
                                            $management_color = $management_score >= 80 ? 'green' : ($management_score >= 60 ? 'yellow' : 'red');
                                            ?>
                                            <span class="bg-<?= $management_color ?>-100 text-<?= $management_color ?>-800 px-3 py-2 rounded-full font-bold text-sm">
                                                <?= number_format($management_score, 1) ?>%
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <!-- معدل الأداء لجميع الصفوف -->
                            <tr class="bg-gradient-to-r from-green-100 to-emerald-100 border-t-2 border-green-300">
                                <td class="py-4 px-6 font-bold text-green-800">
                                    <div class="flex items-center">
                                        <div class="bg-green-500 rounded-full p-2 ml-3">
                                            <i class="fas fa-chart-bar text-white"></i>
                                        </div>
                                        معدل الأداء لجميع الصفوف
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span class="bg-green-600 text-white px-4 py-2 rounded-full font-bold">
                                        <?= array_sum(array_column($classes_data, 'visits_count')) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span class="bg-green-600 text-white px-4 py-2 rounded-full font-bold">
                                        <?= number_format($avg_lesson_execution, 1) ?>%
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span class="bg-green-600 text-white px-4 py-2 rounded-full font-bold">
                                        <?= number_format($avg_classroom_management, 1) ?>%
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- جدول المقارنة المحسن -->
        <div class="bg-gradient-to-br from-white to-purple-50 rounded-xl shadow-lg border border-purple-100 mb-6">
            <!-- عنوان جدول المقارنة -->
            <div class="bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-t-xl p-4">
                <h3 class="text-lg font-bold flex items-center">
                    <i class="fas fa-balance-scale text-purple-200 ml-3"></i>
                    جدول المقارنة - أفضل وأقل أداء
                </h3>
                <p class="text-purple-100 text-sm mt-1">مقارنة سريعة لأعلى وأقل الصفوف أداءً</p>
            </div>

            <div class="p-6">
                <div class="overflow-x-auto rounded-lg shadow-sm">
                    <table class="min-w-full bg-white">
                        <thead>
                            <tr class="bg-gradient-to-r from-purple-600 to-pink-600 text-white">
                                <th class="py-4 px-6 text-right font-bold">
                                    <i class="fas fa-list-alt ml-2"></i>
                                    جوانب المقارنة
                                </th>
                                <th class="py-4 px-6 text-center font-bold">
                                    <i class="fas fa-trophy ml-2"></i>
                                    أفضل أداء
                                </th>
                                <th class="py-4 px-6 text-center font-bold">
                                    <i class="fas fa-arrow-down ml-2"></i>
                                    أقل أداء
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr class="hover:bg-purple-50 transition-colors duration-200 bg-gray-50">
                                <td class="py-4 px-6 font-semibold text-gray-800">
                                    <div class="flex items-center">
                                        <div class="bg-blue-100 rounded-full p-2 ml-3">
                                            <i class="fas fa-tasks text-blue-600"></i>
                                        </div>
                                        تنفيذ الدرس
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <div class="bg-green-100 border border-green-300 rounded-lg p-3">
                                        <div class="flex items-center justify-center">
                                            <i class="fas fa-medal text-green-600 ml-2"></i>
                                            <span class="font-bold text-green-800"><?= htmlspecialchars($max_lesson_class) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <div class="bg-red-100 border border-red-300 rounded-lg p-3">
                                        <div class="flex items-center justify-center">
                                            <i class="fas fa-exclamation-triangle text-red-600 ml-2"></i>
                                            <span class="font-bold text-red-800"><?= htmlspecialchars($min_lesson_class) ?></span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr class="hover:bg-purple-50 transition-colors duration-200 bg-white">
                                <td class="py-4 px-6 font-semibold text-gray-800">
                                    <div class="flex items-center">
                                        <div class="bg-orange-100 rounded-full p-2 ml-3">
                                            <i class="fas fa-users-cog text-orange-600"></i>
                                        </div>
                                        الإدارة الصفية
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <div class="bg-green-100 border border-green-300 rounded-lg p-3">
                                        <div class="flex items-center justify-center">
                                            <i class="fas fa-medal text-green-600 ml-2"></i>
                                            <span class="font-bold text-green-800"><?= htmlspecialchars($max_management_class) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <div class="bg-red-100 border border-red-300 rounded-lg p-3">
                                        <div class="flex items-center justify-center">
                                            <i class="fas fa-exclamation-triangle text-red-600 ml-2"></i>
                                            <span class="font-bold text-red-800"><?= htmlspecialchars($min_management_class) ?></span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ملخص سريع -->
                <div class="mt-6 bg-gradient-to-r from-purple-100 to-pink-100 rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div>
                            <div class="text-2xl font-bold text-green-600">
                                <i class="fas fa-arrow-up"></i>
                            </div>
                            <div class="text-sm text-green-700 font-semibold">أفضل الصفوف</div>
                            <div class="text-xs text-gray-600">يستحقون التقدير</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-red-600">
                                <i class="fas fa-arrow-down"></i>
                            </div>
                            <div class="text-sm text-red-700 font-semibold">تحتاج تطوير</div>
                            <div class="text-xs text-gray-600">برامج دعم إضافية</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- زر الطباعة المحسن -->
        <div class="mt-8 text-center no-print">
            <div class="bg-gradient-to-r from-gray-100 to-gray-200 rounded-xl p-6 shadow-lg">
                <h4 class="text-lg font-semibold text-gray-700 mb-4 flex items-center justify-center">
                    <i class="fas fa-file-pdf text-red-500 ml-2"></i>
                    إجراءات التقرير
                </h4>
                <div class="flex flex-wrap justify-center gap-4">
                    <button onclick="printReport()" class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-6 py-3 rounded-lg font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-md hover:shadow-lg transform hover:scale-105">
                        <i class="fas fa-print ml-2"></i> 
                        طباعة التقرير
                    </button>
                    <button onclick="window.history.back()" class="bg-gradient-to-r from-gray-600 to-gray-700 text-white px-6 py-3 rounded-lg font-semibold hover:from-gray-700 hover:to-gray-800 transition-all duration-200 shadow-md hover:shadow-lg transform hover:scale-105">
                        <i class="fas fa-arrow-right ml-2"></i>
                        العودة للخلف
                    </button>
                </div>
                <div class="text-xs text-gray-600 mt-3">
                    <i class="fas fa-info-circle ml-1"></i>
                    يمكنك طباعة التقرير أو حفظه كملف PDF من خلال خيارات الطباعة
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function printReport() {
    // إضافة عنوان للطباعة
    const originalTitle = document.title;
    document.title = 'تقرير مقارنة أداء الصفوف والشعب - ' + new Date().toLocaleDateString('ar-SA');
    
    // تحسين إعداد الطباعة
    window.print();
    
    // إرجاع العنوان الأصلي
    document.title = originalTitle;
}

// إضافة تحسينات إضافية للطباعة
window.addEventListener('beforeprint', function() {
    // إضافة تاريخ الطباعة
    const printDate = document.createElement('div');
    printDate.id = 'print-date';
    printDate.style.cssText = 'position: fixed; top: 10px; left: 10px; font-size: 8px; color: #666;';
    printDate.textContent = 'تاريخ الطباعة: ' + new Date().toLocaleDateString('ar-SA') + ' ' + new Date().toLocaleTimeString('ar-SA');
    document.body.appendChild(printDate);
});

window.addEventListener('afterprint', function() {
    // إزالة تاريخ الطباعة بعد الطباعة
    const printDate = document.getElementById('print-date');
    if (printDate) {
        printDate.remove();
    }
});
</script>

<style media="print">
    @page {
        size: A4 landscape;
        margin: 0.5in;
    }
    
    /* إخفاء العناصر غير المطلوبة للطباعة */
    header, nav, footer, form, button, .no-print {
        display: none !important;
    }
    
    /* إعدادات الجسم الرئيسي */
    body {
        background-color: white !important;
        font-size: 10px !important;
        line-height: 1.2 !important;
        color: black !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    /* إعدادات الحاوي الرئيسي */
    .container, .main-content {
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    /* تحسين العناوين */
    h1, h2, h3 {
        margin-top: 0 !important;
        margin-bottom: 10px !important;
        font-size: 14px !important;
        font-weight: bold !important;
        text-align: center !important;
    }
    
    /* إعدادات البطاقات والحاويات */
    .bg-gradient-to-br, .bg-gradient-to-r, .rounded-xl, .shadow-lg {
        background: white !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    /* إعدادات الجداول */
    table {
        width: 100% !important;
        border-collapse: collapse !important;
        margin: 10px 0 !important;
        font-size: 9px !important;
        page-break-inside: avoid !important;
    }
    
    th, td {
        border: 1px solid #000 !important;
        padding: 4px 2px !important;
        text-align: center !important;
        background: white !important;
        font-size: 9px !important;
        line-height: 1.1 !important;
        vertical-align: middle !important;
    }
    
    th {
        background-color: #e5e7eb !important;
        font-weight: bold !important;
        font-size: 8px !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    /* الألوان للطباعة */
    .bg-green-100, .bg-gradient-to-r.from-green-100 {
        background-color: #d1fae5 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .bg-gradient-to-r.from-indigo-600, .bg-gradient-to-r.from-purple-600 {
        background-color: #4f46e5 !important;
        color: white !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    /* البطاقات الملونة */
    .bg-green-100.text-green-800, .bg-yellow-100.text-yellow-800, .bg-red-100.text-red-800 {
        background-color: #f3f4f6 !important;
        color: black !important;
        border: 1px solid #000 !important;
        border-radius: 0 !important;
        padding: 2px 4px !important;
        font-size: 8px !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .bg-green-600.text-white {
        background-color: #059669 !important;
        color: white !important;
        border-radius: 0 !important;
        padding: 2px 4px !important;
        font-size: 8px !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    /* إخفاء الأيقونات في الطباعة */
    .fas, .fa, i {
        display: none !important;
    }
    
    /* تحسين المساحات */
    .p-6, .p-4, .px-6, .py-4, .m-6, .mb-6, .mt-6 {
        padding: 0 !important;
        margin: 5px 0 !important;
    }
    
    /* تحسين العرض للجوال */
    .overflow-x-auto {
        overflow: visible !important;
    }
    
    /* منع كسر الصفحة داخل الجداول */
    tr {
        page-break-inside: avoid !important;
    }
    
    /* تحسين تقسيم الأعمدة */
    .grid {
        display: block !important;
    }
    
    /* عرض العناوين بشكل مناسب */
    .text-blue-100, .text-purple-100 {
        color: white !important;
    }
    
    /* إزالة التأثيرات البصرية */
    .hover\:bg-blue-50, .hover\:bg-purple-50, .transition-colors, .duration-200 {
        background: transparent !important;
        transition: none !important;
    }
    
    /* ضغط المحتوى ليناسب صفحة واحدة */
    body * {
        max-height: none !important;
        overflow: visible !important;
    }
    
    /* تحسين توزيع المساحة */
    .space-y-4 > * + *, .space-y-6 > * + *, .gap-4, .gap-6 {
        margin-top: 5px !important;
    }
</style>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?> 