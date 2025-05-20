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
        
        <!-- رسم بياني: أداء المعلمين -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-700 flex items-center">
                <i class="fas fa-user-chart text-primary-500 ml-2"></i>
                أداء المعلمين
            </h2>
            
            <h3 class="text-md font-medium text-gray-700 mb-2">🔸 أفضل المعلمين أداءً:</h3>
            <div class="space-y-3 mb-6">
                <?php foreach ($best_teachers as $index => $teacher): ?>
                <?php if ($index >= 2) continue; // إظهار أفضل معلمين فقط ?>
                <div class="flex items-center">
                    <div class="h-10 w-10 bg-green-100 rounded-full flex items-center justify-center text-green-500 ml-3">
                        <i class="fas fa-medal"></i>
                    </div>
                    <div>
                        <div class="font-medium"><?= htmlspecialchars($teacher['teacher_name']) ?></div>
                        <div class="text-sm text-gray-500">
                            <span class="ml-2">مادة <?= htmlspecialchars($teacher['subject_name']) ?></span>
                            <span class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">
                                نسبة الأداء: <?= number_format($teacher['avg_score'], 0) ?>%
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <h3 class="text-md font-medium text-gray-700 mb-2">🔸 أقل المعلمين أداءً:</h3>
            <div class="space-y-3">
                <?php foreach ($worst_teachers as $index => $teacher): ?>
                <?php if ($index >= 2) continue; // إظهار أقل معلمين فقط ?>
                <div class="flex items-center">
                    <div class="h-10 w-10 bg-red-100 rounded-full flex items-center justify-center text-red-500 ml-3">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div>
                        <div class="font-medium"><?= htmlspecialchars($teacher['teacher_name']) ?></div>
                        <div class="text-sm text-gray-500">
                            <span class="ml-2">مادة <?= htmlspecialchars($teacher['subject_name']) ?></span>
                            <span class="inline-block bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">
                                نسبة الأداء: <?= number_format($teacher['avg_score'], 0) ?>%
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- أداء المدارس / الصفوف -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-700 flex items-center">
                <i class="fas fa-school text-primary-500 ml-2"></i>
                أداء المدارس / الصفوف
            </h2>
            
            <div class="space-y-4">
                <div class="border-r-4 border-blue-500 pr-3 py-1">
                    <div class="text-sm text-gray-500">أفضل مدرسة من حيث نتائج التقييم:</div>
                    <div class="font-medium"><?= htmlspecialchars($best_school) ?> (متوسط <?= $best_school_score ?>%)</div>
                </div>
                
                <div class="border-r-4 border-green-500 pr-3 py-1">
                    <div class="text-sm text-gray-500">الصف الأعلى أداءً:</div>
                    <div class="font-medium"><?= htmlspecialchars($best_grade) ?> (<?= $best_grade_score ?>%)</div>
                </div>
                
                <div class="border-r-4 border-red-500 pr-3 py-1">
                    <div class="text-sm text-gray-500">الصف الأقل أداءً:</div>
                    <div class="font-medium"><?= htmlspecialchars($worst_grade) ?> (<?= $worst_grade_score ?>%)</div>
                </div>
            </div>
            
            <div class="mt-4 text-left">
                <a href="grades_performance_report.php" class="inline-block text-primary-600 hover:text-primary-800 hover:underline text-sm">
                    <i class="fas fa-arrow-left ml-1"></i>
                    عرض تقرير الأداء المفصل
                </a>
            </div>
        </div>

        <!-- إحصائيات المواد الدراسية -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-700 flex items-center">
                <i class="fas fa-book text-primary-500 ml-2"></i>
                إحصائيات المواد الدراسية
            </h2>
            
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-2 px-4 border-b text-right">المادة</th>
                            <th class="py-2 px-4 border-b text-center">عدد المعلمين</th>
                            <th class="py-2 px-4 border-b text-center">عدد الزيارات</th>
                            <th class="py-2 px-4 border-b text-center">المعلمين المُزارين</th>
                            <th class="py-2 px-4 border-b text-center">متوسط الأداء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects_stats as $subject): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-4 border-b"><?= htmlspecialchars($subject['subject_name']) ?></td>
                            <td class="py-2 px-4 border-b text-center"><?= $subject['teachers_count'] ?></td>
                            <td class="py-2 px-4 border-b text-center"><?= $subject['visits_count'] ?></td>
                            <td class="py-2 px-4 border-b text-center"><?= $subject['visited_teachers_count'] ?></td>
                            <td class="py-2 px-4 border-b text-center">
                                <?php if ($subject['avg_performance'] > 0): ?>
                                    <span class="inline-block <?= $subject['avg_performance'] >= 80 ? 'bg-green-100 text-green-800' : ($subject['avg_performance'] >= 70 ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800') ?> text-xs px-2 py-1 rounded-full">
                                        <?= number_format($subject['avg_performance'], 1) ?>%
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <!-- الإجماليات -->
                        <tr class="bg-gray-100 font-bold">
                            <td class="py-2 px-4 border-b">الإجمالي</td>
                            <td class="py-2 px-4 border-b text-center"><?= $total_subject_teachers ?></td>
                            <td class="py-2 px-4 border-b text-center"><?= $total_subject_visits ?></td>
                            <td class="py-2 px-4 border-b text-center"><?= $total_visited_teachers ?></td>
                            <td class="py-2 px-4 border-b text-center">
                                <span class="inline-block <?= $overall_avg_performance >= 80 ? 'bg-green-100 text-green-800' : ($overall_avg_performance >= 70 ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800') ?> text-xs px-2 py-1 rounded-full">
                                    <?= $overall_avg_performance ?>%
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
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