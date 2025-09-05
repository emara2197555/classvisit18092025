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
$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['full_name'];
$school_id = $_SESSION['school_id'];

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

// متوسط الأداء العام
$avg_performance = query_row("
    SELECT AVG((ve.lesson_execution + ve.classroom_management) / 2) as avg_score
    FROM visits v
    INNER JOIN visit_evaluations ve ON v.id = ve.visit_id
    WHERE v.teacher_id = ?
", [$teacher_id]);
$stats['avg_performance'] = $avg_performance['avg_score'] ? round($avg_performance['avg_score'], 1) : 0;

// متوسط أداء تنفيذ الدرس
$avg_lesson = query_row("
    SELECT AVG(ve.lesson_execution) as avg_score
    FROM visits v
    INNER JOIN visit_evaluations ve ON v.id = ve.visit_id
    WHERE v.teacher_id = ?
", [$teacher_id]);
$stats['avg_lesson'] = $avg_lesson['avg_score'] ? round($avg_lesson['avg_score'], 1) : 0;

// متوسط أداء الإدارة الصفية
$avg_management = query_row("
    SELECT AVG(ve.classroom_management) as avg_score
    FROM visits v
    INNER JOIN visit_evaluations ve ON v.id = ve.visit_id
    WHERE v.teacher_id = ?
", [$teacher_id]);
$stats['avg_management'] = $avg_management['avg_score'] ? round($avg_management['avg_score'], 1) : 0;

// آخر الزيارات
$recent_visits = query("
    SELECT v.*, s.name as subject_name, vt.name as visitor_name,
           sec.name as section_name, g.name as grade_name,
           ve.lesson_execution, ve.classroom_management
    FROM visits v
    LEFT JOIN subjects s ON v.subject_id = s.id
    LEFT JOIN visitor_types vt ON v.visitor_id = vt.id
    LEFT JOIN sections sec ON v.section_id = sec.id
    LEFT JOIN grades g ON sec.grade_id = g.id
    LEFT JOIN visit_evaluations ve ON v.id = ve.visit_id
    WHERE v.teacher_id = ?
    ORDER BY v.visit_date DESC, v.visit_time DESC
    LIMIT 10
", [$teacher_id]);

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

    <!-- متوسط تنفيذ الدرس -->
    <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-xl p-6 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold mb-2">تنفيذ الدرس</h3>
                <p class="text-3xl font-bold"><?= $stats['avg_lesson'] ?>%</p>
                <p class="text-green-200 text-sm mt-1">متوسط الأداء</p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-3">
                <i class="fas fa-tasks text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- متوسط الإدارة الصفية -->
    <div class="bg-gradient-to-br from-orange-500 to-red-500 text-white rounded-xl p-6 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold mb-2">الإدارة الصفية</h3>
                <p class="text-3xl font-bold"><?= $stats['avg_management'] ?>%</p>
                <p class="text-orange-200 text-sm mt-1">متوسط الأداء</p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-3">
                <i class="fas fa-users-cog text-2xl"></i>
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
                    <span class="text-gray-600">تنفيذ الدرس</span>
                    <span class="font-semibold"><?= $stats['avg_lesson'] ?>%</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">الإدارة الصفية</span>
                    <span class="font-semibold"><?= $stats['avg_management'] ?>%</span>
                </div>
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
                                        <p><i class="fas fa-calendar ml-1"></i> <?= format_date_ar($visit['visit_date']) ?> - <?= $visit['visit_time'] ?></p>
                                    </div>
                                </div>
                                
                                <?php if ($visit['lesson_execution'] && $visit['classroom_management']): ?>
                                    <div class="text-left">
                                        <div class="mb-2">
                                            <?php 
                                            $total_score = ($visit['lesson_execution'] + $visit['classroom_management']) / 2;
                                            $color = $total_score >= 80 ? 'green' : ($total_score >= 60 ? 'yellow' : 'red');
                                            ?>
                                            <span class="bg-<?= $color ?>-100 text-<?= $color ?>-800 px-3 py-1 rounded-full font-bold">
                                                <?= round($total_score, 1) ?>%
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-500 space-y-1">
                                            <div>تنفيذ الدرس: <?= $visit['lesson_execution'] ?>%</div>
                                            <div>الإدارة الصفية: <?= $visit['classroom_management'] ?>%</div>
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
