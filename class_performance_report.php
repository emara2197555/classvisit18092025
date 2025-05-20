<?php
// بدء التخزين المؤقت للمخرجات
ob_start();

// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// تعيين عنوان الصفحة
$page_title = 'تقرير مقارنة أداء المعلمين';

// تضمين ملف رأس الصفحة
require_once 'includes/header.php';

// الحصول على معرف العام الدراسي المحدد من جلسة المستخدم
if (!isset($_SESSION['selected_academic_year'])) {
    // ابحث عن العام الأكاديمي النشط
    $active_year = get_active_academic_year();
    $_SESSION['selected_academic_year'] = $active_year['id'] ?? null;
    $_SESSION['selected_term'] = 'all';
}

// الحصول على معرف العام الدراسي المحدد من جلسة المستخدم
$academic_year_id = $_SESSION['selected_academic_year'];
$selected_term = $_SESSION['selected_term'] ?? 'all';

// الحصول على تفاصيل العام الأكاديمي المحدد
$current_year_query = "SELECT * FROM academic_years WHERE id = ?";
$current_year_data = query_row($current_year_query, [$academic_year_id]);

// تحديد تواريخ الفصول الدراسية
$first_term_start = $current_year_data['first_term_start'] ?? null;
$first_term_end = $current_year_data['first_term_end'] ?? null;
$second_term_start = $current_year_data['second_term_start'] ?? null;
$second_term_end = $current_year_data['second_term_end'] ?? null;

// تحديد معلومات الفلتر للفصل الدراسي 
$date_filter = "";
$date_params = [];

if ($selected_term == 'first' && $first_term_start && $first_term_end) {
    $date_filter = " AND visit_date BETWEEN ? AND ?";
    $date_params[] = $first_term_start;
    $date_params[] = $first_term_end;
} elseif ($selected_term == 'second' && $second_term_start && $second_term_end) {
    $date_filter = " AND visit_date BETWEEN ? AND ?";
    $date_params[] = $second_term_start;
    $date_params[] = $second_term_end;
}

// تضمين مكون فلترة العام الأكاديمي والفصل الدراسي
require_once 'includes/academic_filter.php';

// تحديد نوع الزائر (النائب الأكاديمي)
$visitor_type_id = isset($_GET['visitor_type_id']) ? (int)$_GET['visitor_type_id'] : 2; // النائب الأكاديمي هو 2

// تحديد المادة الدراسية (اختياري)
$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

// جلب قائمة المواد الدراسية
$subjects = query("SELECT * FROM subjects ORDER BY name");

// استعلام أبسط لجلب بيانات المعلمين والزيارات
$sql_basic = "
    SELECT 
        t.id AS teacher_id,
        t.name AS teacher_name,
        s.id AS subject_id,
        s.name AS subject_name,
        COUNT(DISTINCT v.id) AS visits_count
    FROM 
        teachers t
    JOIN 
        teacher_subjects ts ON t.id = ts.teacher_id
    JOIN 
        subjects s ON ts.subject_id = s.id
    LEFT JOIN 
        visits v ON t.id = v.teacher_id 
        AND v.academic_year_id = ?";

// إضافة شروط اختيارية        
if ($visitor_type_id > 0) {
    $sql_basic .= " AND v.visitor_type_id = ?";
}

if (!empty($date_filter)) {
    $sql_basic .= $date_filter;
}

$sql_basic .= "
    WHERE
        t.job_title = 'معلم'";

if ($subject_id > 0) {
    $sql_basic .= " AND s.id = ?";
}

$sql_basic .= "
    GROUP BY 
        t.id, t.name, s.id, s.name
    ORDER BY 
        s.name, t.name";

// تحضير المعلمات للاستعلام الأساسي
$basic_params = [$academic_year_id];

if ($visitor_type_id > 0) {
    $basic_params[] = $visitor_type_id;
}

if (!empty($date_filter)) {
    $basic_params = array_merge($basic_params, $date_params);
}

if ($subject_id > 0) {
    $basic_params[] = $subject_id;
}

// جلب المعلومات الأساسية للمعلمين
$teachers_basic = query($sql_basic, $basic_params);

// استعلام للحصول على متوسطات المجالات
$sql_domain = "
    SELECT 
        vs.teacher_id,
        ei.domain_id,
        AVG(ve.score) * 25 AS avg_score
    FROM 
        visit_evaluations ve 
    JOIN 
        visits vs ON ve.visit_id = vs.id
    JOIN 
        evaluation_indicators ei ON ve.indicator_id = ei.id
    WHERE 
        vs.academic_year_id = ?";

if ($visitor_type_id > 0) {
    $sql_domain .= " AND vs.visitor_type_id = ?";
}

if (!empty($date_filter)) {
    $sql_domain .= str_replace("visit_date", "vs.visit_date", $date_filter);
}

$sql_domain .= " 
    AND ve.score > 0
    GROUP BY 
        vs.teacher_id, ei.domain_id";

// تحضير المعلمات لاستعلام المجالات
$domain_params = [$academic_year_id];

if ($visitor_type_id > 0) {
    $domain_params[] = $visitor_type_id;
}

if (!empty($date_filter)) {
    $domain_params = array_merge($domain_params, $date_params);
}

// جلب متوسطات المجالات
$domain_averages = query($sql_domain, $domain_params);

// تنظيم البيانات في مصفوفة
$domain_data = [];
foreach ($domain_averages as $avg) {
    $domain_data[$avg['teacher_id']][$avg['domain_id']] = $avg['avg_score'];
}

// استعلام للحصول على المتوسط العام
$sql_overall = "
    SELECT 
        vs.teacher_id,
        AVG(ve.score) * 25 AS overall_avg
    FROM 
        visit_evaluations ve 
    JOIN 
        visits vs ON ve.visit_id = vs.id
    WHERE 
        vs.academic_year_id = ?";

if ($visitor_type_id > 0) {
    $sql_overall .= " AND vs.visitor_type_id = ?";
}

if (!empty($date_filter)) {
    $sql_overall .= str_replace("visit_date", "vs.visit_date", $date_filter);
}

$sql_overall .= " 
    AND ve.score > 0
    GROUP BY 
        vs.teacher_id";

// جلب المتوسطات العامة
$overall_averages = query($sql_overall, $domain_params);

// تنظيم بيانات المتوسط العام
$overall_data = [];
foreach ($overall_averages as $avg) {
    $overall_data[$avg['teacher_id']] = $avg['overall_avg'];
}

// دمج البيانات في مصفوفة واحدة
$teachers_data = [];
foreach ($teachers_basic as $teacher) {
    $teacher_id = $teacher['teacher_id'];
    
    $teachers_data[] = [
        'teacher_id' => $teacher_id,
        'teacher_name' => $teacher['teacher_name'],
        'subject_id' => $teacher['subject_id'],
        'subject_name' => $teacher['subject_name'],
        'visits_count' => $teacher['visits_count'],
        'planning_avg' => $domain_data[$teacher_id][1] ?? null,
        'lesson_execution_avg' => $domain_data[$teacher_id][2] ?? null,
        'classroom_management_avg' => $domain_data[$teacher_id][3] ?? null,
        'evaluation_avg' => $domain_data[$teacher_id][4] ?? null,
        'practical_avg' => $domain_data[$teacher_id][5] ?? null,
        'overall_avg' => $overall_data[$teacher_id] ?? null
    ];
}

// حساب معدلات الأداء العامة
$total_planning = 0;
$total_lesson_execution = 0;
$total_classroom_management = 0;
$total_evaluation = 0;
$total_practical = 0;
$total_overall = 0;

$total_valid_teachers_planning = 0;
$total_valid_teachers_lesson = 0;
$total_valid_teachers_management = 0;
$total_valid_teachers_evaluation = 0;
$total_valid_teachers_practical = 0;
$total_valid_teachers_overall = 0;

// تحديد أفضل وأقل أداء
$max_lesson_execution = 0;
$min_lesson_execution = 100;
$max_classroom_management = 0;
$min_classroom_management = 100;

// إضافة متغيرات جديدة لباقي المجالات
$max_planning = 0;
$min_planning = 100;
$max_evaluation = 0;
$min_evaluation = 100;
$max_practical = 0;
$min_practical = 100;
$max_overall = 0;
$min_overall = 100;

$max_lesson_teacher = '';
$min_lesson_teacher = '';
$max_management_teacher = '';
$min_management_teacher = '';

// إضافة متغيرات لأسماء المعلمين في بقية المجالات
$max_planning_teacher = '';
$min_planning_teacher = '';
$max_evaluation_teacher = '';
$min_evaluation_teacher = '';
$max_practical_teacher = '';
$min_practical_teacher = '';
$max_overall_teacher = '';
$min_overall_teacher = '';

// حساب المتوسطات العامة وتحديد أفضل وأقل أداء
foreach ($teachers_data as $teacher) {
    // حساب متوسط التخطيط
    if ($teacher['planning_avg'] !== null) {
        $total_planning += $teacher['planning_avg'];
        $total_valid_teachers_planning++;
        
        // تحديد الأفضل والأقل في التخطيط
        if ($teacher['planning_avg'] > $max_planning) {
            $max_planning = $teacher['planning_avg'];
            $max_planning_teacher = $teacher['teacher_name'];
        }
        if ($teacher['planning_avg'] < $min_planning && $teacher['planning_avg'] > 0) {
            $min_planning = $teacher['planning_avg'];
            $min_planning_teacher = $teacher['teacher_name'];
        }
    }
    
    // حساب متوسط تنفيذ الدرس
    if ($teacher['lesson_execution_avg'] !== null) {
        $total_lesson_execution += $teacher['lesson_execution_avg'];
        $total_valid_teachers_lesson++;
        
        // تحديد الأفضل والأقل
        if ($teacher['lesson_execution_avg'] > $max_lesson_execution) {
            $max_lesson_execution = $teacher['lesson_execution_avg'];
            $max_lesson_teacher = $teacher['teacher_name'];
        }
        if ($teacher['lesson_execution_avg'] < $min_lesson_execution && $teacher['lesson_execution_avg'] > 0) {
            $min_lesson_execution = $teacher['lesson_execution_avg'];
            $min_lesson_teacher = $teacher['teacher_name'];
        }
    }
    
    // حساب متوسط الإدارة الصفية
    if ($teacher['classroom_management_avg'] !== null) {
        $total_classroom_management += $teacher['classroom_management_avg'];
        $total_valid_teachers_management++;
        
        // تحديد الأفضل والأقل
        if ($teacher['classroom_management_avg'] > $max_classroom_management) {
            $max_classroom_management = $teacher['classroom_management_avg'];
            $max_management_teacher = $teacher['teacher_name'];
        }
        if ($teacher['classroom_management_avg'] < $min_classroom_management && $teacher['classroom_management_avg'] > 0) {
            $min_classroom_management = $teacher['classroom_management_avg'];
            $min_management_teacher = $teacher['teacher_name'];
        }
    }
    
    // حساب متوسط التقويم
    if ($teacher['evaluation_avg'] !== null) {
        $total_evaluation += $teacher['evaluation_avg'];
        $total_valid_teachers_evaluation++;
        
        // تحديد الأفضل والأقل في التقويم
        if ($teacher['evaluation_avg'] > $max_evaluation) {
            $max_evaluation = $teacher['evaluation_avg'];
            $max_evaluation_teacher = $teacher['teacher_name'];
        }
        if ($teacher['evaluation_avg'] < $min_evaluation && $teacher['evaluation_avg'] > 0) {
            $min_evaluation = $teacher['evaluation_avg'];
            $min_evaluation_teacher = $teacher['teacher_name'];
        }
    }
    
    // حساب متوسط النشاط العملي
    if ($teacher['practical_avg'] !== null) {
        $total_practical += $teacher['practical_avg'];
        $total_valid_teachers_practical++;
        
        // تحديد الأفضل والأقل في النشاط العملي
        if ($teacher['practical_avg'] > $max_practical) {
            $max_practical = $teacher['practical_avg'];
            $max_practical_teacher = $teacher['teacher_name'];
        }
        if ($teacher['practical_avg'] < $min_practical && $teacher['practical_avg'] > 0) {
            $min_practical = $teacher['practical_avg'];
            $min_practical_teacher = $teacher['teacher_name'];
        }
    }
    
    // حساب المتوسط العام
    if ($teacher['overall_avg'] !== null) {
        $total_overall += $teacher['overall_avg'];
        $total_valid_teachers_overall++;
        
        // تحديد الأفضل والأقل في المتوسط العام
        if ($teacher['overall_avg'] > $max_overall) {
            $max_overall = $teacher['overall_avg'];
            $max_overall_teacher = $teacher['teacher_name'];
        }
        if ($teacher['overall_avg'] < $min_overall && $teacher['overall_avg'] > 0) {
            $min_overall = $teacher['overall_avg'];
            $min_overall_teacher = $teacher['teacher_name'];
        }
    }
}

// حساب المتوسطات العامة
$avg_planning = $total_valid_teachers_planning > 0 ? $total_planning / $total_valid_teachers_planning : 0;
$avg_lesson_execution = $total_valid_teachers_lesson > 0 ? $total_lesson_execution / $total_valid_teachers_lesson : 0;
$avg_classroom_management = $total_valid_teachers_management > 0 ? $total_classroom_management / $total_valid_teachers_management : 0;
$avg_evaluation = $total_valid_teachers_evaluation > 0 ? $total_evaluation / $total_valid_teachers_evaluation : 0;
$avg_practical = $total_valid_teachers_practical > 0 ? $total_practical / $total_valid_teachers_practical : 0;
$avg_overall = $total_valid_teachers_overall > 0 ? $total_overall / $total_valid_teachers_overall : 0;

// في حالة عدم وجود بيانات كافية
if ($min_lesson_execution === 100) $min_lesson_execution = 0;
if ($min_classroom_management === 100) $min_classroom_management = 0;
if ($min_planning === 100) $min_planning = 0;
if ($min_evaluation === 100) $min_evaluation = 0;
if ($min_practical === 100) $min_practical = 0;
if ($min_overall === 100) $min_overall = 0;

if ($min_lesson_teacher === '') $min_lesson_teacher = '-';
if ($max_lesson_teacher === '') $max_lesson_teacher = '-';
if ($min_management_teacher === '') $min_management_teacher = '-';
if ($max_management_teacher === '') $max_management_teacher = '-';
if ($min_planning_teacher === '') $min_planning_teacher = '-';
if ($max_planning_teacher === '') $max_planning_teacher = '-';
if ($min_evaluation_teacher === '') $min_evaluation_teacher = '-';
if ($max_evaluation_teacher === '') $max_evaluation_teacher = '-';
if ($min_practical_teacher === '') $min_practical_teacher = '-';
if ($max_practical_teacher === '') $max_practical_teacher = '-';
if ($min_overall_teacher === '') $min_overall_teacher = '-';
if ($max_overall_teacher === '') $max_overall_teacher = '-';

// جلب معلومات نوع الزائر
$visitor_type_name = "جميع الزائرين";
if ($visitor_type_id > 0) {
    $visitor_type = query_row("SELECT name FROM visitor_types WHERE id = ?", [$visitor_type_id]);
    $visitor_type_name = $visitor_type ? $visitor_type['name'] : 'النائب الأكاديمي';
}

// جلب جميع أنواع الزائرين للاختيار
$visitor_types = query("SELECT id, name FROM visitor_types ORDER BY id");
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold mb-4">تقرير مقارنة أداء المعلمين</h1>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <!-- نموذج تحديد العام الدراسي ونوع الزائر -->
        <form action="" method="get" class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                    <label for="subject_id" class="block mb-1">المادة الدراسية</label>
                    <select id="subject_id" name="subject_id" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200">
                        <option value="0" <?= $subject_id == 0 ? 'selected' : '' ?>>الكل</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?= $subject['id'] ?>" <?= $subject_id == $subject['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($subject['name']) ?>
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
            تقرير مقارنة أداء المعلمين بناءً على المشاهدات الصفّية ل<?= htmlspecialchars($visitor_type_name) ?> للعام الأكاديمي <?= htmlspecialchars($current_year_data['name'] ?? '') ?>
            <?php if ($selected_term != 'all'): ?>
            (<?= $selected_term == 'first' ? 'الفصل الأول' : 'الفصل الثاني' ?>)
            <?php endif; ?>
        </h2>
        
        <!-- جدول التقرير -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-3 px-4 border text-center font-semibold">المعلم</th>
                        <th class="py-3 px-4 border text-center font-semibold">المادة</th>
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
                    <?php foreach ($teachers_data as $teacher): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-4 border text-center">
                                <a href="teacher_report.php?teacher_id=<?= $teacher['teacher_id'] ?>" class="text-primary-600 hover:underline">
                                    <?= htmlspecialchars($teacher['teacher_name']) ?>
                                </a>
                            </td>
                            <td class="py-2 px-4 border text-center"><?= htmlspecialchars($teacher['subject_name']) ?></td>
                            <td class="py-2 px-4 border text-center"><?= $teacher['visits_count'] ?></td>
                            <td class="py-2 px-4 border text-center">
                                <?php if ($teacher['planning_avg'] !== null): ?>
                                    <?= number_format($teacher['planning_avg'], 1) ?>%
                                <?php endif; ?>
                            </td>
                            <td class="py-2 px-4 border text-center">
                                <?php if ($teacher['lesson_execution_avg'] !== null): ?>
                                    <?= number_format($teacher['lesson_execution_avg'], 1) ?>%
                                <?php endif; ?>
                            </td>
                            <td class="py-2 px-4 border text-center">
                                <?php if ($teacher['classroom_management_avg'] !== null): ?>
                                    <?= number_format($teacher['classroom_management_avg'], 1) ?>%
                                <?php endif; ?>
                            </td>
                            <td class="py-2 px-4 border text-center">
                                <?php if ($teacher['evaluation_avg'] !== null): ?>
                                    <?= number_format($teacher['evaluation_avg'], 1) ?>%
                                <?php endif; ?>
                            </td>
                            <td class="py-2 px-4 border text-center">
                                <?php if ($teacher['practical_avg'] !== null): ?>
                                    <?= number_format($teacher['practical_avg'], 1) ?>%
                                <?php endif; ?>
                            </td>
                            <td class="py-2 px-4 border text-center font-bold">
                                <?php if ($teacher['overall_avg'] !== null): ?>
                                    <?= number_format($teacher['overall_avg'], 1) ?>%
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <!-- معدل الأداء لجميع المعلمين -->
                    <tr class="bg-green-100">
                        <td class="py-2 px-4 border text-center font-bold">معدل الأداء لجميع المعلمين</td>
                        <td class="py-2 px-4 border text-center font-bold">-</td>
                        <td class="py-2 px-4 border text-center font-bold">
                            <?= array_sum(array_column($teachers_data, 'visits_count')) ?>
                        </td>
                        <td class="py-2 px-4 border text-center font-bold">
                            <?= number_format($avg_planning, 1) ?>%
                        </td>
                        <td class="py-2 px-4 border text-center font-bold">
                            <?= number_format($avg_lesson_execution, 1) ?>%
                        </td>
                        <td class="py-2 px-4 border text-center font-bold">
                            <?= number_format($avg_classroom_management, 1) ?>%
                        </td>
                        <td class="py-2 px-4 border text-center font-bold">
                            <?= number_format($avg_evaluation, 1) ?>%
                        </td>
                        <td class="py-2 px-4 border text-center font-bold">
                            <?= number_format($avg_practical, 1) ?>%
                        </td>
                        <td class="py-2 px-4 border text-center font-bold">
                            <?= number_format($avg_overall, 1) ?>%
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
                        <td class="py-2 px-4 border text-center">التخطيط</td>
                        <td class="py-2 px-4 border text-center"><?= htmlspecialchars($max_planning_teacher) ?></td>
                        <td class="py-2 px-4 border text-center"><?= htmlspecialchars($min_planning_teacher) ?></td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 border text-center">تنفيذ الدرس</td>
                        <td class="py-2 px-4 border text-center"><?= htmlspecialchars($max_lesson_teacher) ?></td>
                        <td class="py-2 px-4 border text-center"><?= htmlspecialchars($min_lesson_teacher) ?></td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 border text-center">الإدارة الصفية</td>
                        <td class="py-2 px-4 border text-center"><?= htmlspecialchars($max_management_teacher) ?></td>
                        <td class="py-2 px-4 border text-center"><?= htmlspecialchars($min_management_teacher) ?></td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 border text-center">التقويم</td>
                        <td class="py-2 px-4 border text-center"><?= htmlspecialchars($max_evaluation_teacher) ?></td>
                        <td class="py-2 px-4 border text-center"><?= htmlspecialchars($min_evaluation_teacher) ?></td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 border text-center">النشاط العملي</td>
                        <td class="py-2 px-4 border text-center"><?= htmlspecialchars($max_practical_teacher) ?></td>
                        <td class="py-2 px-4 border text-center"><?= htmlspecialchars($min_practical_teacher) ?></td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 border text-center">المتوسط العام</td>
                        <td class="py-2 px-4 border text-center"><?= htmlspecialchars($max_overall_teacher) ?></td>
                        <td class="py-2 px-4 border text-center"><?= htmlspecialchars($min_overall_teacher) ?></td>
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