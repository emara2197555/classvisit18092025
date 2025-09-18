<?php
/**
 * لوحة تحكم المعلم
 */

// تضمين ملفات النظام
require_once 'includes/auth_functions.php';
require_once 'includes/functions.php';

// حماية الصفحة للمعلمين فقط
protect_page(['Teacher']);

// تعيين عنوان الصفحة
$page_title = 'لوحة تحكم المعلم - نظام الزيارات الصفية';

// الحصول على بيانات المعلم
$teacher_id = $_SESSION['teacher_id'] ?? null;
$teacher_name = $_SESSION['full_name'];
$school_id = $_SESSION['school_id'] ?? null;

// إذا لم يكن teacher_id موجوداً في الجلسة، ابحث عنه في قاعدة البيانات
if (!$teacher_id && isset($_SESSION['user_id'])) {
    $teacher_data = query_row("SELECT * FROM teachers WHERE user_id = ?", [$_SESSION['user_id']]);
    if ($teacher_data) {
        $teacher_id = $teacher_data['id'];
        $school_id = $teacher_data['school_id'] ?? $school_id;
        $_SESSION['teacher_id'] = $teacher_id; // تحديث الجلسة
        $_SESSION['school_id'] = $school_id; // تحديث الجلسة
    }
}

// التحقق من وجود teacher_id
if (!$teacher_id) {
    die('خطأ: لم يتم العثور على بيانات المعلم. يرجى تسجيل الدخول مرة أخرى.');
}

// الحصول على اسم المدرسة
$school = query_row("SELECT name FROM schools WHERE id = ?", [$school_id]);
$school_name = $school['name'] ?? 'غير محدد';

// الحصول على بيانات المعلم
$teacher_data = query_row("SELECT * FROM teachers WHERE id = ?", [$teacher_id]);

// إحصائيات سريعة للمعلم
$stats = [];

// عدد الزيارات الكلي
$total_visits = query_row("
    SELECT COUNT(*) as count 
    FROM visits 
    WHERE teacher_id = ?
", [$teacher_id]);
$stats['total_visits'] = $total_visits['count'];

// عدد الزيارات هذا الشهر
$visits_this_month = query_row("
    SELECT COUNT(*) as count 
    FROM visits 
    WHERE teacher_id = ? 
    AND MONTH(visit_date) = MONTH(CURRENT_DATE()) 
    AND YEAR(visit_date) = YEAR(CURRENT_DATE())
", [$teacher_id]);
$stats['visits_this_month'] = $visits_this_month['count'];

// الحصول على العام الدراسي الحالي
$current_academic_year = query_row("SELECT id FROM academic_years WHERE is_active = 1 LIMIT 1");
$academic_year_id = $current_academic_year['id'] ?? 2; // افتراضي العام 2

// حساب متوسطات المجالات أولاً
$domains_averages = query("
    SELECT 
        d.id,
        d.name,
        (AVG(ve.score) / 3) * 100 AS avg_percentage
    FROM 
        evaluation_domains d
    JOIN 
        evaluation_indicators i ON i.domain_id = d.id
    JOIN 
        visit_evaluations ve ON ve.indicator_id = i.id
    JOIN 
        visits v ON ve.visit_id = v.id
    WHERE 
        v.teacher_id = ?
        AND v.academic_year_id = ?
        AND ve.score IS NOT NULL
        AND (
            d.id != 5 OR 
            (d.id = 5 AND v.has_lab = 1)
        )
    GROUP BY 
        d.id, d.name
    ORDER BY 
        d.id
", [$teacher_id, $academic_year_id]);

// حساب المتوسط العام من متوسطات المجالات (نفس طريقة التقرير)
$total_percentage = 0;
$domains_count = 0;
foreach ($domains_averages as $domain) {
    $total_percentage += $domain['avg_percentage'];
    $domains_count++;
}
$stats['avg_performance'] = $domains_count > 0 ? round($total_percentage / $domains_count, 1) : 0;

// استخراج متوسطات المجالات من البيانات المحسوبة مسبقاً
$stats['avg_planning'] = 0;
$stats['avg_lesson'] = 0;
$stats['avg_assessment'] = 0;
$stats['avg_management'] = 0;

foreach ($domains_averages as $domain) {
    switch ($domain['id']) {
        case 1: // التخطيط للدرس
            $stats['avg_planning'] = round($domain['avg_percentage'], 1);
            break;
        case 2: // تنفيذ الدرس
            $stats['avg_lesson'] = round($domain['avg_percentage'], 1);
            break;
        case 3: // التقويم
            $stats['avg_assessment'] = round($domain['avg_percentage'], 1);
            break;
        case 4: // الإدارة الصفية
            $stats['avg_management'] = round($domain['avg_percentage'], 1);
            break;
    }
}

// آخر زيارة ومتوسطها (العام الدراسي الحالي)
$last_visit_data = query_row("
    SELECT 
        v.visit_date,
        AVG(
            CASE 
                WHEN i.domain_id != 5 THEN ve.score 
                WHEN i.domain_id = 5 AND v.has_lab = 1 THEN ve.score
                ELSE NULL 
            END
        ) as avg_score
    FROM visits v
    INNER JOIN visit_evaluations ve ON v.id = ve.visit_id
    INNER JOIN evaluation_indicators i ON ve.indicator_id = i.id
    WHERE v.teacher_id = ?
    AND v.academic_year_id = ?
    AND ve.score IS NOT NULL
    GROUP BY v.id, v.visit_date
    ORDER BY v.visit_date DESC
    LIMIT 1
", [$teacher_id, $academic_year_id]);

$stats['last_visit_score'] = $last_visit_data && $last_visit_data['avg_score'] ? 
    round(($last_visit_data['avg_score'] / 3) * 100, 1) : 0;
$stats['last_visit_date'] = $last_visit_data['visit_date'] ?? null;

// عدد التوصيات المتلقاة (العام الدراسي الحالي)
$recommendations_count = query_row("
    SELECT COUNT(*) as count
    FROM visits v
    INNER JOIN visit_evaluations ve ON v.id = ve.visit_id
    WHERE v.teacher_id = ?
    AND v.academic_year_id = ?
    AND (ve.recommendation_id IS NOT NULL OR ve.custom_recommendation IS NOT NULL)
", [$teacher_id, $academic_year_id]);
$stats['recommendations_count'] = $recommendations_count['count'];

// آخر الزيارات (مع حساب صحيح للمتوسطات)
$recent_visits = query("
    SELECT 
        v.*,
        s.name as subject_name, 
        vt.name as visitor_name,
        sec.name as section_name, 
        g.name as grade_name,
        AVG(
            CASE 
                WHEN i.domain_id != 5 THEN ve.score 
                WHEN i.domain_id = 5 AND v.has_lab = 1 THEN ve.score
                ELSE NULL 
            END
        ) as avg_score
    FROM visits v
    LEFT JOIN subjects s ON v.subject_id = s.id
    LEFT JOIN visitor_types vt ON v.visitor_type_id = vt.id
    LEFT JOIN sections sec ON v.section_id = sec.id
    LEFT JOIN grades g ON sec.grade_id = g.id
    LEFT JOIN visit_evaluations ve ON v.id = ve.visit_id
    LEFT JOIN evaluation_indicators i ON ve.indicator_id = i.id
    WHERE v.teacher_id = ?
    AND v.academic_year_id = ?
    AND ve.score IS NOT NULL
    GROUP BY v.id
    ORDER BY v.visit_date DESC, v.created_at DESC
    LIMIT 10
", [$teacher_id, $academic_year_id]);

// المواد التي يدرسها المعلم
$teacher_subjects = query("
    SELECT s.name, s.id
    FROM teacher_subjects ts
    INNER JOIN subjects s ON ts.subject_id = s.id
    WHERE ts.teacher_id = ?
    ORDER BY s.name
", [$teacher_id]);

// تضمين ملف رأس الصفحة
require_once 'includes/header.php';
?>

<!-- قسم الترويسة والملخص -->
<div class="bg-gradient-to-r from-green-600 to-teal-600 text-white rounded-xl shadow-lg p-6 mb-6">
    <div class="flex flex-col md:flex-row justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold mb-2">
                <i class="fas fa-chalkboard-teacher ml-3"></i>
                مرحباً، <?= htmlspecialchars($teacher_name) ?>
            </h1>
            <p class="text-green-100"><?= htmlspecialchars($school_name) ?></p>
            <?php 
            $current_year_name = query_row("SELECT name FROM academic_years WHERE id = ?", [$academic_year_id]);
            if ($current_year_name): 
            ?>
                <p class="text-green-200 text-sm mt-1">
                    <i class="fas fa-calendar-alt ml-1"></i>
                    العام الدراسي: <?= htmlspecialchars($current_year_name['name']) ?>
                </p>
            <?php endif; ?>
            <?php if (!empty($teacher_subjects)): ?>
                <div class="mt-2 flex flex-wrap gap-2">
                    <?php foreach ($teacher_subjects as $subject): ?>
                        <span class="bg-green-500 bg-opacity-30 px-3 py-1 rounded-full text-sm">
                            <?= htmlspecialchars($subject['name']) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="mt-4 md:mt-0">
            <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-lg font-semibold transition duration-200">
                <i class="fas fa-sign-out-alt ml-2"></i>
                تسجيل الخروج
            </a>
        </div>
    </div>
</div>

<!-- الإحصائيات السريعة -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- إجمالي الزيارات -->
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl p-6 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold mb-2">إجمالي الزيارات</h3>
                <p class="text-3xl font-bold"><?= $stats['total_visits'] ?></p>
                <p class="text-blue-200 text-sm mt-1">زيارة كلياً</p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-3">
                <i class="fas fa-eye text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- زيارات الشهر -->
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-xl p-6 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold mb-2">زيارات الشهر</h3>
                <p class="text-3xl font-bold"><?= $stats['visits_this_month'] ?></p>
                <p class="text-purple-200 text-sm mt-1">زيارة هذا الشهر</p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-3">
                <i class="fas fa-calendar-alt text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- الأداء العام -->
    <div class="bg-gradient-to-br from-slate-500 to-slate-600 text-white rounded-xl p-6 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold mb-2">الأداء العام</h3>
                <p class="text-3xl font-bold"><?= $stats['avg_performance'] ?>%</p>
                <p class="text-slate-200 text-sm mt-1">متوسط شامل</p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-3">
                <i class="fas fa-chart-line text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- آخر زيارة -->
    <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 text-white rounded-xl p-6 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold mb-2">آخر زيارة</h3>
                <p class="text-3xl font-bold"><?= $stats['last_visit_score'] ?>%</p>
                <p class="text-indigo-200 text-sm mt-1">
                    <?= $stats['last_visit_date'] ? date('d/m/Y', strtotime($stats['last_visit_date'])) : 'لا توجد زيارات' ?>
                </p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-3">
                <i class="fas fa-clock text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- تفاصيل الأداء حسب المجالات -->
<div class="mb-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
        <i class="fas fa-layer-group text-gray-600 ml-3"></i>
        تفاصيل الأداء حسب المجالات
    </h2>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

    <!-- التخطيط للدرس -->
    <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 text-white rounded-xl p-6 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold mb-2">التخطيط</h3>
                <p class="text-3xl font-bold"><?= $stats['avg_planning'] ?>%</p>
                <p class="text-cyan-200 text-sm mt-1">تخطيط الدرس</p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-3">
                <i class="fas fa-clipboard-list text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- تنفيذ الدرس -->
    <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-xl p-6 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold mb-2">التنفيذ</h3>
                <p class="text-3xl font-bold"><?= $stats['avg_lesson'] ?>%</p>
                <p class="text-green-200 text-sm mt-1">تنفيذ الدرس</p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-3">
                <i class="fas fa-tasks text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- التقويم -->
    <div class="bg-gradient-to-br from-amber-500 to-amber-600 text-white rounded-xl p-6 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold mb-2">التقويم</h3>
                <p class="text-3xl font-bold"><?= $stats['avg_assessment'] ?>%</p>
                <p class="text-amber-200 text-sm mt-1">تقويم الطلاب</p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-3">
                <i class="fas fa-clipboard-check text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- الإدارة الصفية -->
    <div class="bg-gradient-to-br from-orange-500 to-red-500 text-white rounded-xl p-6 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold mb-2">الإدارة الصفية</h3>
                <p class="text-3xl font-bold"><?= $stats['avg_management'] ?>%</p>
                <p class="text-orange-200 text-sm mt-1">إدارة الصف</p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-3">
                <i class="fas fa-users-cog text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- التوصيات المتلقاة -->
    <div class="bg-gradient-to-br from-rose-500 to-rose-600 text-white rounded-xl p-6 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold mb-2">التوصيات</h3>
                <p class="text-3xl font-bold"><?= $stats['recommendations_count'] ?></p>
                <p class="text-rose-200 text-sm mt-1">توصية متلقاة</p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-3">
                <i class="fas fa-lightbulb text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- المحتوى الرئيسي -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- الأداء العام -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-chart-pie text-green-600 ml-3"></i>
                الأداء العام
            </h2>
            
            <div class="text-center mb-6">
                <div class="relative w-32 h-32 mx-auto">
                    <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 36 36">
                        <path class="text-gray-300" stroke="currentColor" stroke-width="3" fill="none"
                              d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                        <path class="text-green-600" stroke="currentColor" stroke-width="3" fill="none"
                              stroke-dasharray="<?= $stats['avg_performance'] ?>, 100"
                              d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-2xl font-bold text-gray-800"><?= $stats['avg_performance'] ?>%</span>
                    </div>
                </div>
                <p class="text-gray-600 mt-2">متوسط الأداء الإجمالي</p>
            </div>
            
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">
                        <i class="fas fa-clipboard-list text-cyan-500 ml-1"></i>
                        التخطيط
                    </span>
                    <span class="font-semibold"><?= $stats['avg_planning'] ?>%</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">
                        <i class="fas fa-tasks text-green-500 ml-1"></i>
                        التنفيذ
                    </span>
                    <span class="font-semibold"><?= $stats['avg_lesson'] ?>%</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">
                        <i class="fas fa-clipboard-check text-amber-500 ml-1"></i>
                        التقويم
                    </span>
                    <span class="font-semibold"><?= $stats['avg_assessment'] ?>%</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">
                        <i class="fas fa-users-cog text-orange-500 ml-1"></i>
                        الإدارة الصفية
                    </span>
                    <span class="font-semibold"><?= $stats['avg_management'] ?>%</span>
                </div>
            </div>
            
            <!-- رابط التقرير المفصل -->
            <div class="mt-6 text-center">
                <a href="teacher_report.php?teacher_id=<?= $teacher_id ?>" 
                   class="inline-flex items-center bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition duration-200 shadow-lg">
                    <i class="fas fa-chart-bar ml-2"></i>
                    عرض التقرير المفصل
                    <i class="fas fa-external-link-alt mr-2 text-sm"></i>
                </a>
            </div>
        </div>
        
        <!-- المواد الدراسية -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-book text-blue-600 ml-3"></i>
                المواد الدراسية
            </h3>
            
            <?php if (empty($teacher_subjects)): ?>
                <p class="text-gray-500 text-center py-4">لا توجد مواد مسجلة</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($teacher_subjects as $subject): ?>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <span class="font-medium text-blue-800"><?= htmlspecialchars($subject['name']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- الزيارات الأخيرة -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-history text-purple-600 ml-3"></i>
                آخر الزيارات
            </h2>
            
            <?php if (empty($recent_visits)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-calendar-times text-gray-400 text-4xl mb-4"></i>
                    <p class="text-gray-500 text-lg">لا توجد زيارات مسجلة</p>
                    <p class="text-gray-400 text-sm">ستظهر الزيارات هنا بعد تسجيلها</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($recent_visits as $visit): ?>
                        <div class="border-r-4 border-purple-500 bg-purple-50 p-4 rounded-lg">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center mb-2">
                                        <h4 class="font-semibold text-gray-800 ml-3"><?= htmlspecialchars($visit['subject_name']) ?></h4>
                                        <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs">
                                            <?= htmlspecialchars($visit['grade_name']) ?> - <?= htmlspecialchars($visit['section_name']) ?>
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        <p><i class="fas fa-user ml-1"></i> الزائر: <?= htmlspecialchars($visit['visitor_name']) ?></p>
                                        <p><i class="fas fa-calendar ml-1"></i> <?= format_date_ar($visit['visit_date']) ?></p>
                                    </div>
                                </div>
                                
                                <?php if ($visit['avg_score']): ?>
                                    <div class="text-left">
                                        <div class="mb-2">
                                            <?php 
                                            $total_score = ($visit['avg_score'] / 3) * 100; // Convert from 3-point scale to percentage
                                            $color = $total_score >= 80 ? 'green' : ($total_score >= 60 ? 'yellow' : 'red');
                                            ?>
                                            <span class="bg-<?= $color ?>-100 text-<?= $color ?>-800 px-3 py-1 rounded-full font-bold">
                                                <?= round($total_score, 1) ?>%
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <div>متوسط الأداء: <?= round($visit['avg_score'], 2) ?>/3</div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mt-3 flex justify-end">
                                <a href="view_visit.php?id=<?= $visit['id'] ?>" class="text-purple-600 hover:text-purple-800 text-sm font-medium">
                                    عرض التفاصيل
                                    <i class="fas fa-arrow-left mr-1"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-6 text-center">
                    <a href="visits.php?teacher_id=<?= $teacher_id ?>" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg font-semibold transition duration-200">
                        عرض جميع الزيارات
                        <i class="fas fa-arrow-left mr-2"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- قسم الروابط السريعة -->
<div class="mt-8 bg-gradient-to-r from-gray-100 to-gray-200 rounded-xl p-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
        <i class="fas fa-link text-gray-600 ml-3"></i>
        الروابط السريعة
    </h2>
    
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <a href="visits.php?teacher_id=<?= $teacher_id ?>" class="bg-white hover:bg-gray-50 p-4 rounded-lg text-center transition shadow">
            <i class="fas fa-list text-blue-600 text-2xl mb-2"></i>
            <p class="font-semibold text-gray-800">جميع الزيارات</p>
        </a>
        
        <a href="teacher_report.php?teacher_id=<?= $teacher_id ?>" class="bg-white hover:bg-gray-50 p-4 rounded-lg text-center transition shadow">
            <i class="fas fa-chart-line text-green-600 text-2xl mb-2"></i>
            <p class="font-semibold text-gray-800">تقرير الأداء</p>
        </a>
        
        <a href="training_needs.php?teacher_id=<?= $teacher_id ?>" class="bg-white hover:bg-gray-50 p-4 rounded-lg text-center transition shadow">
            <i class="fas fa-graduation-cap text-purple-600 text-2xl mb-2"></i>
            <p class="font-semibold text-gray-800">الاحتياجات التدريبية</p>
        </a>
    </div>
</div>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?>
