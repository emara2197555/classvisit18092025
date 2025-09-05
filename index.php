<?php
// تعيين عنوان الصفحة
$page_title = 'لوحة التحكم - نظام الزيارات الصفية';

// تضمين ملف رأس الصفحة
require_once 'includes/header.php';

// تضمين ملفات البيانات الإحصائية والرسوم البيانية والتنبيهات
require_once 'includes/dashboard_stats.php';
require_once 'includes/dashboard_charts.php';
require_once 'includes/dashboard_alerts.php';

// عرض رسالة النجاح إذا كانت موجودة
if (isset($_SESSION['success_message'])) {
    echo '<div class="bg-green-100 border-r-4 border-green-500 text-green-700 p-4 mb-6 rounded-md">';
    echo $_SESSION['success_message'];
    echo '</div>';
    
    // حذف الرسالة من الجلسة بعد عرضها
    unset($_SESSION['success_message']);
}
?>







<!-- قسم الترويسة والملخص -->
<div class="bg-white rounded-lg shadow-md border-r-4 border-primary-600 p-6 mb-6">
    <div class="flex flex-col md:flex-row justify-between items-center mb-4">
        <h1 class="text-2xl font-bold text-primary-700 mb-2 md:mb-0">لوحة التحكم – نظام الزيارات الصفية</h1>
        <div class="flex items-center space-x-3 space-x-reverse">
            <a href="evaluation_form.php" class="bg-primary-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-primary-700 transition">
                <i class="fas fa-plus ml-1"></i> زيارة جديدة
            </a>
            <button id="refreshDashboard" class="bg-gray-200 text-gray-700 px-3 py-2 rounded-lg font-medium hover:bg-gray-300 transition">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>

    <!-- رسالة الترحيب -->

    <p class="text-gray-600 mb-2"><?php date_default_timezone_set('Asia/Riyadh'); ?><?= format_date_ar(date('Y-m-d')) ?> | <?= date('h:i A') ?></p>
    </div>

<?php 
// تضمين عنصر الفلترة حسب العام الأكاديمي والفصل الدراسي
if (!isset($academic_year_id) || !isset($date_condition)) {
    require_once 'includes/academic_filter.php';
}
?>

<!-- قسم الإحصائيات الرئيسية -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- عدد الزيارات المسجلة -->
    <div class="dashboard-card bg-white rounded-lg shadow-md p-5 border-r-4 border-primary-500 transition-all duration-300">
        <div class="flex justify-between">
            <div>
                <h3 class="text-gray-500 text-sm font-medium mb-2">عدد الزيارات المسجلة هذا الفصل</h3>
                <div class="text-3xl font-bold text-gray-800" id="visitsCount"><?= $visits_count ?></div>
                <div class="text-xs text-gray-500 mt-1">زيارة</div>
            </div>
            <div class="h-12 w-12 bg-primary-100 rounded-full flex items-center justify-center text-primary-500">
                <i class="fas fa-clipboard-list fa-lg"></i>
            </div>
        </div>
    </div>

    <!-- عدد المعلمين -->
    <div class="dashboard-card bg-white rounded-lg shadow-md p-5 border-r-4 border-blue-500 transition-all duration-300">
        <div class="flex justify-between">
            <div>
                <h3 class="text-gray-500 text-sm font-medium mb-2">عدد المعلمين الذين تم تقييمهم</h3>
                <div class="text-3xl font-bold text-gray-800" id="teachersCount"><?= $evaluated_teachers_count ?></div>
                <div class="text-xs text-gray-500 mt-1">معلمًا</div>
            </div>
            <div class="h-12 w-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-500">
                <i class="fas fa-chalkboard-teacher fa-lg"></i>
            </div>
        </div>
    </div>

    <!-- متوسط الأداء -->
    <div class="dashboard-card bg-white rounded-lg shadow-md p-5 border-r-4 border-green-500 transition-all duration-300">
        <div class="flex justify-between">
            <div>
                <h3 class="text-gray-500 text-sm font-medium mb-2">متوسط الأداء العام للمعلمين</h3>
                <div class="text-3xl font-bold text-gray-800" id="avgPerformance"><?= $avg_performance ?>%</div>
                <div class="text-xs text-gray-500 mt-1">
                    <?php if ($avg_performance >= 90): ?>
                    <span class="text-green-500">ممتاز</span>
                    <?php elseif ($avg_performance >= 80): ?>
                    <span class="text-blue-500">جيد جداً</span>
                    <?php elseif ($avg_performance >= 70): ?>
                    <span class="text-yellow-500">جيد</span>
                    <?php else: ?>
                    <span class="text-red-500">يحتاج إلى تحسين</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="h-12 w-12 bg-green-100 rounded-full flex items-center justify-center text-green-500">
                <i class="fas fa-chart-line fa-lg"></i>
            </div>
        </div>
    </div>

    <!-- عدد المعلمين المسجلين -->
    <div class="dashboard-card bg-white rounded-lg shadow-md p-5 border-r-4 border-yellow-500 transition-all duration-300">
        <div class="flex justify-between">
            <div>
                <h3 class="text-gray-500 text-sm font-medium mb-2">إجمالي عدد المعلمين المسجلين</h3>
                <div class="text-3xl font-bold text-gray-800" id="totalTeachers"><?= $total_teachers_count ?></div>
                <div class="text-xs text-gray-500 mt-1">معلمًا</div>
            </div>
            <div class="h-12 w-12 bg-yellow-100 rounded-full flex items-center justify-center text-yellow-500">
                <i class="fas fa-users fa-lg"></i>
            </div>
        </div>
    </div>
</div>

<!-- إحصائيات تفصيلية على مستوى الوظائف -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h2 class="text-lg font-semibold mb-4 text-gray-700 flex items-center">
        <i class="fas fa-users-cog text-primary-500 ml-2"></i>
        إحصائيات على مستوى الوظائف
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- المعلمين المقيمين -->
        <div class="dashboard-card bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-blue-600 text-sm font-medium mb-1">معلمين تم تقييمهم</h3>
                    <div class="text-2xl font-bold text-blue-800"><?= $teachers_evaluated_count ?></div>
                    <div class="text-xs text-blue-600">من أصل <?= $total_teachers_count ?> معلم</div>
                    <div class="text-xs text-blue-500 mt-1">نسبة التغطية: <?= $teachers_coverage_percentage ?>%</div>
                </div>
                <div class="h-10 w-10 bg-blue-200 rounded-full flex items-center justify-center text-blue-600">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
            </div>
        </div>

        <!-- المنسقين المقيمين -->
        <div class="dashboard-card bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-200">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-green-600 text-sm font-medium mb-1">منسقين تم تقييمهم</h3>
                    <div class="text-2xl font-bold text-green-800"><?= $coordinators_evaluated_count ?></div>
                    <div class="text-xs text-green-600">من أصل <?= $total_coordinators_count ?> منسق</div>
                    <div class="text-xs text-green-500 mt-1">نسبة التغطية: <?= $coordinators_coverage_percentage ?>%</div>
                </div>
                <div class="h-10 w-10 bg-green-200 rounded-full flex items-center justify-center text-green-600">
                    <i class="fas fa-user-tie"></i>
                </div>
            </div>
        </div>

        <!-- الموجهين النشطين -->
        <div class="dashboard-card bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 border border-purple-200">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-purple-600 text-sm font-medium mb-1">موجهين نشطين</h3>
                    <div class="text-2xl font-bold text-purple-800"><?= $supervisors_visiting_count ?></div>
                    <div class="text-xs text-purple-600">من أصل <?= $total_supervisors_count ?> موجه</div>
                    <div class="text-xs text-purple-500 mt-1">يقومون بالزيارة</div>
                </div>
                <div class="h-10 w-10 bg-purple-200 rounded-full flex items-center justify-center text-purple-600">
                    <i class="fas fa-user-graduate"></i>
                </div>
            </div>
        </div>

        <!-- مقارنة الأداء -->
        <div class="dashboard-card bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-4 border border-orange-200">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-orange-600 text-sm font-medium mb-1">مقارنة الأداء</h3>
                    <div class="text-sm text-orange-700">
                        <div>معلمين: <?= $teachers_avg_performance ?>%</div>
                        <div>منسقين: <?= $coordinators_avg_performance ?>%</div>
                    </div>
                    <div class="text-xs text-orange-500 mt-1">
                        <?php if ($coordinators_avg_performance > $teachers_avg_performance): ?>
                            المنسقين متقدمين
                        <?php elseif ($teachers_avg_performance > $coordinators_avg_performance): ?>
                            المعلمين متقدمين
                        <?php else: ?>
                            أداء متقارب
                        <?php endif; ?>
                    </div>
                </div>
                <div class="h-10 w-10 bg-orange-200 rounded-full flex items-center justify-center text-orange-600">
                    <i class="fas fa-balance-scale"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- إحصائيات على مستوى المواد والجودة -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- إحصائيات المواد -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold mb-4 text-gray-700 flex items-center">
            <i class="fas fa-book text-primary-500 ml-2"></i>
            إحصائيات المواد الدراسية
        </h2>
        
        <!-- المواد الأكثر زيارة -->
        <div class="mb-4">
            <h3 class="text-md font-medium text-gray-700 mb-2">🔸 المواد الأكثر زيارة:</h3>
            <div class="space-y-2">
                <?php foreach ($most_visited_subjects as $index => $subject): ?>
                <div class="flex justify-between items-center p-2 bg-green-50 rounded">
                    <span class="text-sm font-medium"><?= htmlspecialchars($subject['subject_name']) ?></span>
                    <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">
                        <?= $subject['visits_count'] ?> زيارة
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- المواد التي تحتاج اهتمام -->
        <?php if (!empty($least_visited_subjects)): ?>
        <div class="mb-4">
            <h3 class="text-md font-medium text-gray-700 mb-2">⚠️ مواد تحتاج اهتمام:</h3>
            <div class="space-y-2">
                <?php foreach ($least_visited_subjects as $subject): ?>
                <div class="flex justify-between items-center p-2 bg-yellow-50 rounded">
                    <span class="text-sm font-medium"><?= htmlspecialchars($subject['subject_name']) ?></span>
                    <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full">
                        <?= $subject['visits_count'] ?> زيارة
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- أفضل المواد أداءً -->
        <?php if (!empty($best_subjects_performance)): ?>
        <div>
            <h3 class="text-md font-medium text-gray-700 mb-2">🏆 أفضل المواد أداءً:</h3>
            <div class="space-y-2">
                <?php foreach ($best_subjects_performance as $subject): ?>
                <div class="flex justify-between items-center p-2 bg-blue-50 rounded">
                    <span class="text-sm font-medium"><?= htmlspecialchars($subject['subject_name']) ?></span>
                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">
                        <?= number_format($subject['avg_score'], 1) ?>%
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- إحصائيات الجودة والتميز -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold mb-4 text-gray-700 flex items-center">
            <i class="fas fa-medal text-primary-500 ml-2"></i>
            إحصائيات الجودة والتميز
        </h2>
        
        <!-- مؤشرات الجودة -->
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="p-3 bg-green-50 rounded-lg text-center">
                <div class="text-green-600 text-sm mb-1">متميزين (90%+)</div>
                <div class="text-2xl font-bold text-green-800"><?= $excellent_teachers_count ?></div>
                <div class="text-xs text-green-600">
                    <?= $evaluated_teachers_count > 0 ? round(($excellent_teachers_count / $evaluated_teachers_count) * 100, 1) : 0 ?>%
                </div>
            </div>
            <div class="p-3 bg-red-50 rounded-lg text-center">
                <div class="text-red-600 text-sm mb-1">يحتاج تطوير (<70%)</div>
                <div class="text-2xl font-bold text-red-800"><?= $needs_improvement_count ?></div>
                <div class="text-xs text-red-600">
                    <?= $evaluated_teachers_count > 0 ? round(($needs_improvement_count / $evaluated_teachers_count) * 100, 1) : 0 ?>%
                </div>
            </div>
        </div>

        <!-- أكثر الزوار نشاطاً -->
        <div>
            <h3 class="text-md font-medium text-gray-700 mb-2">⭐ أكثر الزوار نشاطاً:</h3>
            <div class="space-y-2">
                <?php foreach ($most_active_visitors as $visitor): ?>
                <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                    <div>
                        <div class="text-sm font-medium"><?= htmlspecialchars($visitor['visitor_name']) ?></div>
                        <div class="text-xs text-gray-500"><?= htmlspecialchars($visitor['visitor_type']) ?></div>
                    </div>
                    <span class="bg-primary-100 text-primary-800 text-xs px-2 py-1 rounded-full">
                        <?= $visitor['visits_count'] ?> زيارة
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- إحصائيات المدير والنائب الأكاديمي -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h2 class="text-lg font-semibold mb-4 text-gray-700 flex items-center">
        <i class="fas fa-user-crown text-primary-500 ml-2"></i>
        إحصائيات القيادة المدرسية
    </h2>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- إحصائيات المدير -->
        <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-lg p-5 border border-indigo-200">
            <h3 class="text-lg font-semibold text-indigo-800 mb-3 flex items-center">
                <i class="fas fa-user-tie text-indigo-600 ml-2"></i>
                مدير المدرسة
            </h3>
            
            <!-- إجمالي الزيارات -->
            <div class="mb-4">
                <div class="flex justify-between items-center p-3 bg-white rounded-lg shadow-sm">
                    <span class="text-gray-700 font-medium">إجمالي الزيارات</span>
                    <span class="bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full font-bold">
                        <?= $principal_visits_count ?> زيارة
                    </span>
                </div>
            </div>

            <!-- متوسط أداء المعلمين الذين زارهم -->
            <?php if ($principal_visits_count > 0): ?>
            <div class="mb-4">
                <div class="flex justify-between items-center p-3 bg-white rounded-lg shadow-sm">
                    <span class="text-gray-700 font-medium">متوسط أداء من زارهم</span>
                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full font-bold">
                        <?= $principal_visited_teachers_avg ?>%
                    </span>
                </div>
            </div>
            <?php endif; ?>

            <!-- الزيارات حسب المادة -->
            <?php if (!empty($principal_visits_by_subject)): ?>
            <div class="mb-4">
                <h4 class="text-sm font-semibold text-indigo-700 mb-2">الزيارات حسب المادة:</h4>
                <div class="space-y-1">
                    <?php foreach ($principal_visits_by_subject as $subject): ?>
                    <div class="flex justify-between items-center text-sm p-2 bg-indigo-50 rounded">
                        <span><?= htmlspecialchars($subject['subject_name']) ?></span>
                        <span class="font-medium"><?= $subject['visits_count'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- المعلمين الذين زارهم -->
            <?php if (!empty($teachers_visited_by_principal)): ?>
            <div>
                <h4 class="text-sm font-semibold text-indigo-700 mb-2">المعلمين الذين تمت زيارتهم:</h4>
                <div class="space-y-1 max-h-32 overflow-y-auto">
                    <?php foreach ($teachers_visited_by_principal as $teacher): ?>
                    <div class="flex justify-between items-center text-sm p-2 bg-white rounded shadow-sm">
                        <div>
                            <div class="font-medium"><?= htmlspecialchars($teacher['teacher_name']) ?></div>
                            <div class="text-xs text-gray-500">
                                <?= htmlspecialchars($teacher['job_title']) ?> - <?= htmlspecialchars($teacher['subject_name']) ?>
                            </div>
                        </div>
                        <span class="bg-indigo-100 text-indigo-800 text-xs px-2 py-1 rounded-full">
                            <?= $teacher['visits_count'] ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($principal_visits_count == 0): ?>
            <div class="text-center text-gray-500 py-4">
                <i class="fas fa-info-circle mb-2"></i>
                <div>لم يقم المدير بأي زيارات هذا الفصل</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- إحصائيات النائب الأكاديمي -->
        <div class="bg-gradient-to-br from-teal-50 to-teal-100 rounded-lg p-5 border border-teal-200">
            <h3 class="text-lg font-semibold text-teal-800 mb-3 flex items-center">
                <i class="fas fa-user-graduate text-teal-600 ml-2"></i>
                النائب الأكاديمي
            </h3>
            
            <!-- إجمالي الزيارات -->
            <div class="mb-4">
                <div class="flex justify-between items-center p-3 bg-white rounded-lg shadow-sm">
                    <span class="text-gray-700 font-medium">إجمالي الزيارات</span>
                    <span class="bg-teal-100 text-teal-800 px-3 py-1 rounded-full font-bold">
                        <?= $academic_deputy_visits_count ?> زيارة
                    </span>
                </div>
            </div>

            <!-- متوسط أداء المعلمين الذين زارهم -->
            <?php if ($academic_deputy_visits_count > 0): ?>
            <div class="mb-4">
                <div class="flex justify-between items-center p-3 bg-white rounded-lg shadow-sm">
                    <span class="text-gray-700 font-medium">متوسط أداء من زارهم</span>
                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full font-bold">
                        <?= $deputy_visited_teachers_avg ?>%
                    </span>
                </div>
            </div>
            <?php endif; ?>

            <!-- الزيارات حسب المادة -->
            <?php if (!empty($academic_deputy_visits_by_subject)): ?>
            <div class="mb-4">
                <h4 class="text-sm font-semibold text-teal-700 mb-2">الزيارات حسب المادة:</h4>
                <div class="space-y-1">
                    <?php foreach ($academic_deputy_visits_by_subject as $subject): ?>
                    <div class="flex justify-between items-center text-sm p-2 bg-teal-50 rounded">
                        <span><?= htmlspecialchars($subject['subject_name']) ?></span>
                        <span class="font-medium"><?= $subject['visits_count'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- المعلمين الذين زارهم -->
            <?php if (!empty($teachers_visited_by_deputy)): ?>
            <div>
                <h4 class="text-sm font-semibold text-teal-700 mb-2">المعلمين الذين تمت زيارتهم:</h4>
                <div class="space-y-1 max-h-32 overflow-y-auto">
                    <?php foreach ($teachers_visited_by_deputy as $teacher): ?>
                    <div class="flex justify-between items-center text-sm p-2 bg-white rounded shadow-sm">
                        <div>
                            <div class="font-medium"><?= htmlspecialchars($teacher['teacher_name']) ?></div>
                            <div class="text-xs text-gray-500">
                                <?= htmlspecialchars($teacher['job_title']) ?> - <?= htmlspecialchars($teacher['subject_name']) ?>
                            </div>
                        </div>
                        <span class="bg-teal-100 text-teal-800 text-xs px-2 py-1 rounded-full">
                            <?= $teacher['visits_count'] ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($academic_deputy_visits_count == 0): ?>
            <div class="text-center text-gray-500 py-4">
                <i class="fas fa-info-circle mb-2"></i>
                <div>لم يقم النائب الأكاديمي بأي زيارات هذا الفصل</div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- صف المحتوى الرئيسي -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- العمود الأول: الرسوم البيانية والإحصائيات -->
    <div class="lg:col-span-2 space-y-6">
        <!-- إحصائيات سريعة -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-700 flex items-center">
                <i class="fas fa-chart-pie text-primary-500 ml-2"></i>
                إحصائيات سريعة
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="p-3 bg-gray-50 rounded-lg text-center">
                    <div class="text-gray-500 text-sm mb-1">عدد المدارس</div>
                    <div class="text-xl font-bold text-gray-800"><?= $schools_count ?></div>
                </div>
                <div class="p-3 bg-gray-50 rounded-lg text-center">
                    <div class="text-gray-500 text-sm mb-1">عدد الزائرين</div>
                    <div class="text-xl font-bold text-gray-800"><?= $visitors_count ?></div>
                </div>
                <div class="p-3 bg-gray-50 rounded-lg text-center">
                    <div class="text-gray-500 text-sm mb-1">عدد المواد</div>
                    <div class="text-xl font-bold text-gray-800"><?= $subjects_count ?></div>
                </div>
                <div class="p-3 bg-gray-50 rounded-lg text-center">
                    <div class="text-gray-500 text-sm mb-1">عدد الشعب</div>
                    <div class="text-xl font-bold text-gray-800"><?= $sections_count ?></div>
                </div>
            </div>
        </div>

        <!-- رسم بياني: متوسط الأداء حسب المجالات -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-700 flex items-center">
                <i class="fas fa-chart-bar text-primary-500 ml-2"></i>
                متوسط الأداء حسب المجالات
            </h2>
            <div class="h-64">
                <canvas id="domainsChart"></canvas>
            </div>
        </div>
        
        <!-- رسم بياني: تطور متوسط الأداء -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-700 flex items-center">
                <i class="fas fa-chart-line text-primary-500 ml-2"></i>
                تطور متوسط الأداء عبر الوقت
            </h2>
            <div class="h-64">
                <canvas id="performanceOverTimeChart"></canvas>
            </div>
        </div>
        
        <!-- أداء المعلمين المحسن -->
        <div class="bg-gradient-to-br from-white to-blue-50 rounded-xl shadow-lg border border-blue-100 p-6">
            <!-- العنوان الرئيسي -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg p-4 mb-6">
                <h2 class="text-xl font-bold flex items-center">
                    <i class="fas fa-user-chart text-blue-200 ml-3"></i>
                    تقييم أداء المعلمين
                </h2>
                <p class="text-blue-100 text-sm mt-1">نظرة شاملة على مستويات الأداء في النظام التعليمي</p>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <!-- أفضل المعلمين أداءً -->
                <div class="bg-white rounded-lg border-r-4 border-green-500 p-5 shadow-sm">
                    <div class="flex items-center mb-4">
                        <div class="bg-green-100 p-2 rounded-lg ml-3">
                            <i class="fas fa-trophy text-green-600 text-lg"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-green-700">أفضل المعلمين أداءً</h3>
                    </div>
                    
                    <div class="space-y-4">
                        <?php foreach ($best_teachers as $index => $teacher): ?>
                        <?php if ($index >= 2) continue; ?>
                        <div class="bg-green-50 rounded-lg p-4 border border-green-200 hover:shadow-md transition-all duration-200">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold ml-3">
                                        <?= $index + 1 ?>
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-800"><?= htmlspecialchars($teacher['teacher_name']) ?></div>
                                        <div class="text-sm text-gray-600 flex items-center mt-1">
                                            <i class="fas fa-book text-green-500 ml-1"></i>
                                            <?= htmlspecialchars($teacher['subject_name']) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gradient-to-r from-green-500 to-emerald-500 text-white px-3 py-2 rounded-full text-sm font-bold">
                                    <?= number_format($teacher['avg_score'], 0) ?>%
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- المعلمين الذين يحتاجون تطوير -->
                <div class="bg-white rounded-lg border-r-4 border-orange-500 p-5 shadow-sm">
                    <div class="flex items-center mb-4">
                        <div class="bg-orange-100 p-2 rounded-lg ml-3">
                            <i class="fas fa-chart-line text-orange-600 text-lg"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-orange-700">يحتاجون تطوير</h3>
                    </div>
                    
                    <div class="space-y-4">
                        <?php foreach ($worst_teachers as $index => $teacher): ?>
                        <?php if ($index >= 2) continue; ?>
                        <div class="bg-orange-50 rounded-lg p-4 border border-orange-200 hover:shadow-md transition-all duration-200">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold ml-3">
                                        <i class="fas fa-exclamation text-xs"></i>
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-800"><?= htmlspecialchars($teacher['teacher_name']) ?></div>
                                        <div class="text-sm text-gray-600 flex items-center mt-1">
                                            <i class="fas fa-book text-orange-500 ml-1"></i>
                                            <?= htmlspecialchars($teacher['subject_name']) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gradient-to-r from-orange-500 to-red-500 text-white px-3 py-2 rounded-full text-sm font-bold">
                                    <?= number_format($teacher['avg_score'], 0) ?>%
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ملخص سريع -->
            <div class="mt-6 bg-gradient-to-r from-blue-100 to-indigo-100 rounded-lg p-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                    <div>
                        <div class="text-2xl font-bold text-blue-600"><?= count($best_teachers) ?></div>
                        <div class="text-sm text-blue-700">معلم متميز</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-green-600">
                            <?= isset($best_teachers[0]) ? number_format($best_teachers[0]['avg_score'], 0) : '0' ?>%
                        </div>
                        <div class="text-sm text-green-700">أعلى نسبة</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-orange-600"><?= count($worst_teachers) ?></div>
                        <div class="text-sm text-orange-700">يحتاج تطوير</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-red-600">
                            <?= isset($worst_teachers[0]) ? number_format($worst_teachers[0]['avg_score'], 0) : '0' ?>%
                        </div>
                        <div class="text-sm text-red-700">أقل نسبة</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- أداء المدارس / الصفوف المحسن -->
        <div class="bg-gradient-to-br from-white to-purple-50 rounded-xl shadow-lg border border-purple-100 p-6">
            <!-- العنوان الرئيسي -->
            <div class="bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg p-4 mb-6">
                <h2 class="text-xl font-bold flex items-center">
                    <i class="fas fa-school text-purple-200 ml-3"></i>
                    تقييم أداء المدارس والصفوف
                </h2>
                <p class="text-purple-100 text-sm mt-1">مؤشرات الأداء على مستوى المؤسسات التعليمية</p>
            </div>

            <div class="grid gap-6">
                <!-- أفضل مدرسة -->
                <div class="bg-white rounded-lg shadow-sm border-2 border-blue-200 p-5 hover:shadow-md transition-all duration-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-3 rounded-lg ml-4">
                                <i class="fas fa-crown text-white text-xl"></i>
                            </div>
                            <div>
                                <div class="text-sm text-blue-600 font-medium mb-1">🏆 أفضل مدرسة من حيث نتائج التقييم</div>
                                <div class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($best_school) ?></div>
                            </div>
                        </div>
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-4 py-2 rounded-full font-bold text-lg">
                            <?= $best_school_score ?>%
                        </div>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <!-- الصف الأعلى أداءً -->
                    <div class="bg-white rounded-lg shadow-sm border-2 border-green-200 p-5 hover:shadow-md transition-all duration-200">
                        <div class="flex items-center mb-3">
                            <div class="bg-gradient-to-r from-green-500 to-emerald-500 p-2 rounded-lg ml-3">
                                <i class="fas fa-arrow-up text-white"></i>
                            </div>
                            <div class="text-green-700 font-semibold">الصف الأعلى أداءً</div>
                        </div>
                        <div class="bg-green-50 rounded-lg p-3 border border-green-200">
                            <div class="font-bold text-gray-800"><?= htmlspecialchars($best_grade) ?></div>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-sm text-green-600">نسبة الأداء</span>
                                <span class="bg-gradient-to-r from-green-500 to-emerald-500 text-white px-3 py-1 rounded-full text-sm font-bold">
                                    <?= $best_grade_score ?>%
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- الصف الأقل أداءً -->
                    <div class="bg-white rounded-lg shadow-sm border-2 border-red-200 p-5 hover:shadow-md transition-all duration-200">
                        <div class="flex items-center mb-3">
                            <div class="bg-gradient-to-r from-red-500 to-orange-500 p-2 rounded-lg ml-3">
                                <i class="fas fa-arrow-down text-white"></i>
                            </div>
                            <div class="text-red-700 font-semibold">يحتاج تطوير</div>
                        </div>
                        <div class="bg-red-50 rounded-lg p-3 border border-red-200">
                            <div class="font-bold text-gray-800"><?= htmlspecialchars($worst_grade) ?></div>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-sm text-red-600">نسبة الأداء</span>
                                <span class="bg-gradient-to-r from-red-500 to-orange-500 text-white px-3 py-1 rounded-full text-sm font-bold">
                                    <?= $worst_grade_score ?>%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- الإحصائيات السريعة -->
                <div class="bg-gradient-to-r from-purple-100 to-pink-100 rounded-lg p-4">
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <div class="text-2xl font-bold text-purple-600"><?= $best_school_score ?>%</div>
                            <div class="text-sm text-purple-700">أفضل مدرسة</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-green-600"><?= $best_grade_score ?>%</div>
                            <div class="text-sm text-green-700">أعلى صف</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-red-600"><?= $worst_grade_score ?>%</div>
                            <div class="text-sm text-red-700">أقل صف</div>
                        </div>
                    </div>
                </div>

                <!-- رابط التقرير المفصل -->
                <div class="bg-gradient-to-r from-gray-100 to-gray-200 rounded-lg p-4 text-center">
                    <a href="grades_performance_report.php" class="inline-flex items-center bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-3 rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 transition-all duration-200 shadow-md hover:shadow-lg">
                        <i class="fas fa-chart-bar ml-2"></i>
                        عرض تقرير الأداء المفصل
                        <i class="fas fa-arrow-left mr-2"></i>
                    </a>
                    <div class="text-xs text-gray-600 mt-2">تحليل شامل لجميع المؤشرات والإحصائيات</div>
                </div>
            </div>
        </div>

        <!-- إحصائيات المواد الدراسية -->
        <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-8">
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                <h2 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-book mr-3"></i>
                    إحصائيات المواد الدراسية
                </h2>
                <p class="text-indigo-100 text-sm mt-1">تحليل شامل لأداء المواد الدراسية من حيث المعلمين والزيارات والأداء</p>
            </div>
            
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="w-full table-auto border-collapse">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="border border-gray-300 px-4 py-3 text-right font-semibold text-gray-700 min-w-[150px]">
                                    <i class="fas fa-graduation-cap mr-2 text-blue-600"></i>
                                    المادة
                                </th>
                                <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 min-w-[120px]">
                                    <i class="fas fa-users mr-2 text-green-600"></i>
                                    عدد المعلمين
                                </th>
                                <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 min-w-[120px]">
                                    <i class="fas fa-clipboard-list mr-2 text-purple-600"></i>
                                    عدد الزيارات
                                </th>
                                <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 min-w-[140px]">
                                    <i class="fas fa-user-check mr-2 text-orange-600"></i>
                                    المعلمين المُزارين
                                </th>
                                <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 min-w-[120px]">
                                    <i class="fas fa-chart-line mr-2 text-red-600"></i>
                                    متوسط الأداء
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($subjects_stats as $subject): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <td class="border border-gray-300 px-4 py-3 text-right">
                                <div class="font-medium text-gray-800"><?= htmlspecialchars($subject['subject_name']) ?></div>
                            </td>
                            <td class="border border-gray-300 px-4 py-3 text-center">
                                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-bold">
                                    <?= $subject['teachers_count'] ?>
                                </span>
                            </td>
                            <td class="border border-gray-300 px-4 py-3 text-center">
                                <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full font-bold">
                                    <?= $subject['visits_count'] ?>
                                </span>
                            </td>
                            <td class="border border-gray-300 px-4 py-3 text-center">
                                <div class="flex items-center justify-center">
                                    <span class="bg-orange-100 text-orange-800 px-3 py-1 rounded-full font-bold">
                                        <?= $subject['visited_teachers_count'] ?>
                                    </span>
                                    <?php if ($subject['teachers_count'] > 0): ?>
                                        <span class="text-xs text-gray-500 mr-2">
                                            (<?= number_format(($subject['visited_teachers_count'] / $subject['teachers_count']) * 100, 1) ?>%)
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="border border-gray-300 px-4 py-3 text-center">
                                <?php if ($subject['avg_performance'] > 0): ?>
                                    <?php
                                    $performance = $subject['avg_performance'];
                                    $color_class = $performance >= 80 ? 'text-green-700 bg-green-100' : 
                                                  ($performance >= 60 ? 'text-yellow-700 bg-yellow-100' : 'text-red-700 bg-red-100');
                                    ?>
                                    <div class="inline-block px-3 py-1 rounded-full font-bold <?= $color_class ?>">
                                        <?= number_format($performance, 1) ?>%
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-400 font-medium">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <!-- الإجماليات -->
                        <tr class="bg-gradient-to-r from-gray-100 to-gray-200">
                            <td class="border border-gray-300 px-4 py-4 text-right">
                                <div class="font-bold text-gray-800 flex items-center">
                                    <i class="fas fa-calculator mr-2 text-blue-600"></i>
                                    الإجمالي
                                </div>
                            </td>
                            <td class="border border-gray-300 px-4 py-4 text-center">
                                <span class="bg-blue-200 text-blue-900 px-4 py-2 rounded-full font-bold text-lg">
                                    <?= $total_subject_teachers ?>
                                </span>
                            </td>
                            <td class="border border-gray-300 px-4 py-4 text-center">
                                <span class="bg-purple-200 text-purple-900 px-4 py-2 rounded-full font-bold text-lg">
                                    <?= $total_subject_visits ?>
                                </span>
                            </td>
                            <td class="border border-gray-300 px-4 py-4 text-center">
                                <div class="flex items-center justify-center">
                                    <span class="bg-orange-200 text-orange-900 px-4 py-2 rounded-full font-bold text-lg">
                                        <?= $total_visited_teachers ?>
                                    </span>
                                    <?php if ($total_subject_teachers > 0): ?>
                                        <span class="text-sm text-gray-600 mr-2 font-medium">
                                            (<?= number_format(($total_visited_teachers / $total_subject_teachers) * 100, 1) ?>%)
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="border border-gray-300 px-4 py-4 text-center">
                                <?php
                                $overall_color_class = $overall_avg_performance >= 80 ? 'text-green-700 bg-green-200' : 
                                                      ($overall_avg_performance >= 60 ? 'text-yellow-700 bg-yellow-200' : 'text-red-700 bg-red-200');
                                ?>
                                <div class="inline-block px-4 py-2 rounded-full font-bold text-lg <?= $overall_color_class ?>">
                                    <?= $overall_avg_performance ?>%
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>
                
                <!-- إضافة مفتاح الألوان والإحصائيات -->
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- مفتاح الألوان -->
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">مفتاح الألوان:</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center">
                                <div class="w-4 h-4 bg-green-100 border border-green-300 rounded-full mr-2"></div>
                                <span class="text-gray-600">ممتاز (80% فأكثر)</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-4 h-4 bg-yellow-100 border border-yellow-300 rounded-full mr-2"></div>
                                <span class="text-gray-600">جيد (60% - 79%)</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-4 h-4 bg-red-100 border border-red-300 rounded-full mr-2"></div>
                                <span class="text-gray-600">يحتاج تحسين (أقل من 60%)</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- إحصائيات سريعة -->
                    <div class="p-4 bg-indigo-50 rounded-lg">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">إحصائيات سريعة:</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">معدل التغطية:</span>
                                <span class="font-bold text-indigo-700">
                                    <?= $total_subject_teachers > 0 ? number_format(($total_visited_teachers / $total_subject_teachers) * 100, 1) : 0 ?>%
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">متوسط الزيارات لكل معلم:</span>
                                <span class="font-bold text-indigo-700">
                                    <?= $total_visited_teachers > 0 ? number_format($total_subject_visits / $total_visited_teachers, 1) : 0 ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">عدد المواد الفعالة:</span>
                                <span class="font-bold text-indigo-700"><?= count($subjects_stats) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- العمود الثاني: التنبيهات والروابط السريعة -->
 
        
        <!-- الزيارات المجدولة القادمة -->
 
        
        <!-- الوصول السريع -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-700 flex items-center">
                <i class="fas fa-link text-primary-500 ml-2"></i>
                الوصول السريع
            </h2>
            
            <div class="grid grid-cols-2 gap-3">
                <a href="teachers_management.php" class="bg-primary-50 hover:bg-primary-100 text-primary-700 p-3 rounded-md flex flex-col items-center justify-center text-center">
                    <i class="fas fa-chalkboard-teacher mb-2 text-xl"></i>
                    <span>إدارة المعلمين</span>
                </a>
                
                <a href="class_performance_report.php" class="bg-primary-50 hover:bg-primary-100 text-primary-700 p-3 rounded-md flex flex-col items-center justify-center text-center">
                    <i class="fas fa-chart-pie mb-2 text-xl"></i>
                    <span>تقارير الأداء</span>
                </a>
                
                <a href="add_recommendations.php" class="bg-primary-50 hover:bg-primary-100 text-primary-700 p-3 rounded-md flex flex-col items-center justify-center text-center">
                    <i class="fas fa-tasks mb-2 text-xl"></i>
                    <span>التوصيات</span>
                </a>
                
                <a href="school_settings.php" class="bg-primary-50 hover:bg-primary-100 text-primary-700 p-3 rounded-md flex flex-col items-center justify-center text-center">
                    <i class="fas fa-cogs mb-2 text-xl"></i>
                    <span>إعدادات المدرسة</span>
                </a>
                
                <a href="evaluation_form.php" class="bg-primary-50 hover:bg-primary-100 text-primary-700 p-3 rounded-md flex flex-col items-center justify-center text-center">
                    <i class="fas fa-plus-circle mb-2 text-xl"></i>
                    <span>إنشاء زيارة جديدة</span>
                </a>
                
                <a href="training_needs.php" class="bg-primary-50 hover:bg-primary-100 text-primary-700 p-3 rounded-md flex flex-col items-center justify-center text-center">
                    <i class="fas fa-clipboard-list mb-2 text-xl"></i>
                    <span>الاحتياجات التدريبية</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- بيانات الرسوم البيانية -->
<script>
    // تحميل بيانات الرسوم البيانية من PHP
    const domainsChartData = <?= $domains_chart_json ?>;
    const performanceOverTimeChartData = <?= $performance_over_time_chart_json ?>;
    const levelChartData = <?= $level_chart_json ?>;
    const weakIndicatorsChartData = <?= $weak_indicators_chart_json ?>;
</script>

<!-- تحميل Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- تحميل ملف جافاسكريبت لوحة التحكم -->
<script src="assets/js/dashboard.js"></script>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?> 