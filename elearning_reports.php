<?php
/**
 * صفحة التقارير العامة لمنسق التعليم الإلكتروني
 */

require_once 'includes/auth_functions.php';
require_once 'includes/functions.php';

// حماية الصفحة لمنسقي التعليم الإلكتروني
protect_page(['E-Learning Coordinator', 'Admin', 'Director', 'Academic Deputy']);

$page_title = 'التقارير الشاملة للزيارات الصفية';

// الحصول على السنة الدراسية الحالية
$current_year = get_active_academic_year();
$current_year_id = $current_year ? $current_year['id'] : 2;

// إحصائيات الزيارات الصفية
$attendance_stats = query_row("
    SELECT 
        COUNT(*) as total_sessions,
        COUNT(CASE WHEN attendance_type = 'direct' THEN 1 END) as direct_sessions,
        COUNT(CASE WHEN attendance_type = 'remote' THEN 1 END) as remote_sessions,
        COUNT(CASE WHEN attendance_rating = 'excellent' THEN 1 END) as excellent_sessions,
        COUNT(CASE WHEN attendance_rating = 'very_good' THEN 1 END) as very_good_sessions,
        COUNT(CASE WHEN attendance_rating = 'good' THEN 1 END) as good_sessions,
        COUNT(CASE WHEN attendance_rating = 'acceptable' THEN 1 END) as acceptable_sessions,
        COUNT(CASE WHEN attendance_rating = 'poor' THEN 1 END) as poor_sessions
    FROM elearning_attendance 
    WHERE academic_year_id = ?
", [$current_year_id]);

// إحصائيات تقييمات نظام قطر
$qatar_stats = query_row("
    SELECT 
        COUNT(*) as total_evaluations,
        COUNT(CASE WHEN performance_level = 'excellent' THEN 1 END) as excellent_count,
        COUNT(CASE WHEN performance_level = 'very_good' THEN 1 END) as very_good_count,
        COUNT(CASE WHEN performance_level = 'good' THEN 1 END) as good_count,
        COUNT(CASE WHEN performance_level = 'needs_improvement' THEN 1 END) as needs_improvement_count,
        COUNT(CASE WHEN performance_level = 'poor' THEN 1 END) as poor_count,
        AVG(total_score) as avg_score
    FROM qatar_system_performance 
    WHERE academic_year_id = ?
", [$current_year_id]);

// أحدث الحضور
$recent_attendance = query("
    SELECT ea.*, t.name as teacher_name, s.name as subject_name, sch.name as school_name
    FROM elearning_attendance ea
    JOIN teachers t ON ea.teacher_id = t.id
    JOIN subjects s ON ea.subject_id = s.id
    JOIN schools sch ON ea.school_id = sch.id
    WHERE ea.academic_year_id = ?
    ORDER BY ea.lesson_date DESC, ea.created_at DESC
    LIMIT 10
", [$current_year_id]);

// أحدث التقييمات
$recent_evaluations = query("
    SELECT qsp.*, t.name as teacher_name, s.name as subject_name
    FROM qatar_system_performance qsp
    JOIN teachers t ON qsp.teacher_id = t.id
    JOIN subjects s ON qsp.subject_id = s.id
    WHERE qsp.academic_year_id = ?
    ORDER BY qsp.evaluation_date DESC
    LIMIT 10
", [$current_year_id]);

// إحصائيات الأدوات المستخدمة
$tools_usage = query("
    SELECT 
        elearning_tools,
        COUNT(*) as usage_count
    FROM elearning_attendance 
    WHERE academic_year_id = ? AND elearning_tools IS NOT NULL
    GROUP BY elearning_tools
    ORDER BY usage_count DESC
    LIMIT 10
", [$current_year_id]);

// تحليل الأدوات المستخدمة
$tools_analysis = [];
$tool_names = [
    'qatar_system' => 'نظام قطر للتعليم',
    'tablets' => 'الأجهزة اللوحية',
    'interactive_display' => 'أجهزة العرض التفاعلي',
    'ai_applications' => 'تطبيقات الذكاء الاصطناعي',
    'interactive_websites' => 'المواقع التفاعلية'
];

foreach ($tools_usage as $usage) {
    $tools = json_decode($usage['elearning_tools'], true);
    if ($tools) {
        foreach ($tools as $tool) {
            if (!isset($tools_analysis[$tool])) {
                $tools_analysis[$tool] = 0;
            }
            $tools_analysis[$tool] += $usage['usage_count'];
        }
    }
}

// ترتيب الأدوات حسب الاستخدام
arsort($tools_analysis);

?>

<?php include 'includes/elearning_header.php'; ?>

<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4">
        <!-- عنوان الصفحة -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">التقارير الشاملة للزيارات الصفية</h1>
            <p class="text-gray-600 mt-1">نظرة عامة على الأداء والإحصائيات الشاملة للزيارات الصفية</p>
        </div>

        <!-- إحصائيات الزيارات الصفية -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-clipboard-check ml-2 text-blue-600"></i>
                إحصائيات الزيارات الصفية
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <div class="text-3xl font-bold text-blue-600"><?= number_format($attendance_stats['total_sessions'] ?? 0) ?></div>
                    <div class="text-sm text-gray-600 mt-1">إجمالي الزيارات</div>
                </div>
                
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <div class="text-3xl font-bold text-green-600"><?= number_format($attendance_stats['direct_sessions'] ?? 0) ?></div>
                    <div class="text-sm text-gray-600 mt-1">حضور مباشر</div>
                </div>
                
                <div class="text-center p-4 bg-purple-50 rounded-lg">
                    <div class="text-3xl font-bold text-purple-600"><?= number_format($attendance_stats['remote_sessions'] ?? 0) ?></div>
                    <div class="text-sm text-gray-600 mt-1">حضور عن بُعد</div>
                </div>
                
                <div class="text-center p-4 bg-indigo-50 rounded-lg">
                    <div class="text-3xl font-bold text-indigo-600"><?= number_format($attendance_stats['excellent_sessions'] ?? 0) ?></div>
                    <div class="text-sm text-gray-600 mt-1">حصص ممتازة</div>
                </div>
            </div>

            <!-- رسم بياني للحضور -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-medium text-gray-800 mb-3">توزيع تقييمات الحضور</h3>
                <div class="h-64">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>
        </div>

        <!-- إحصائيات تقييمات نظام قطر -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-star ml-2 text-yellow-600"></i>
                إحصائيات تقييمات نظام قطر للتعليم
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <div class="text-3xl font-bold text-blue-600"><?= number_format($qatar_stats['total_evaluations'] ?? 0) ?></div>
                    <div class="text-sm text-gray-600 mt-1">إجمالي التقييمات</div>
                </div>
                
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <div class="text-3xl font-bold text-green-600"><?= number_format($qatar_stats['excellent_count'] ?? 0) ?></div>
                    <div class="text-sm text-gray-600 mt-1">تقييمات ممتازة</div>
                </div>
                
                <div class="text-center p-4 bg-red-50 rounded-lg">
                    <div class="text-3xl font-bold text-red-600"><?= number_format($qatar_stats['poor_count'] ?? 0) ?></div>
                    <div class="text-sm text-gray-600 mt-1">تقييمات ضعيفة</div>
                </div>
                
                <div class="text-center p-4 bg-indigo-50 rounded-lg">
                    <div class="text-3xl font-bold text-indigo-600"><?= number_format($qatar_stats['avg_score'] ?? 0, 1) ?></div>
                    <div class="text-sm text-gray-600 mt-1">متوسط الدرجات</div>
                </div>
            </div>

            <!-- رسم بياني للتقييمات -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-medium text-gray-800 mb-3">توزيع مستويات الأداء</h3>
                <div class="h-64">
                    <canvas id="qatarChart"></canvas>
                </div>
            </div>
        </div>

        <!-- تحليل استخدام أدوات التعليم الإلكتروني -->
        <?php if (!empty($tools_analysis)): ?>
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-laptop ml-2 text-green-600"></i>
                تحليل استخدام أدوات التعليم الإلكتروني
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- قائمة الأدوات -->
                <div>
                    <h3 class="font-medium text-gray-800 mb-3">الأدوات الأكثر استخداماً</h3>
                    <div class="space-y-3">
                        <?php foreach ($tools_analysis as $tool => $count): ?>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <span class="font-medium"><?= $tool_names[$tool] ?? $tool ?></span>
                            <div class="flex items-center">
                                <span class="text-sm text-gray-600 ml-2"><?= number_format($count) ?> مرة</span>
                                <div class="w-20 bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: <?= min(100, ($count / max($tools_analysis)) * 100) ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- رسم بياني للأدوات -->
                <div>
                    <h3 class="font-medium text-gray-800 mb-3">التوزيع البصري</h3>
                    <div class="h-64">
                        <canvas id="toolsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- أحدث الأنشطة -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- أحدث سجلات الحضور -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-clock ml-2 text-blue-600"></i>
                    أحدث سجلات الحضور
                </h2>
                
                <?php if (empty($recent_attendance)): ?>
                <div class="text-center text-gray-500 py-8">
                    <i class="fas fa-inbox text-3xl mb-2"></i>
                    <p>لا توجد سجلات حضور حديثة</p>
                </div>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($recent_attendance as $attendance): ?>
                    <div class="border border-gray-200 rounded-lg p-3 hover:bg-gray-50">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-medium text-gray-800"><?= htmlspecialchars($attendance['teacher_name']) ?></h4>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($attendance['subject_name']) ?> - <?= htmlspecialchars($attendance['school_name']) ?></p>
                                <p class="text-xs text-gray-500"><?= date('Y-m-d', strtotime($attendance['lesson_date'])) ?> - الحصة <?= $attendance['lesson_number'] ?></p>
                            </div>
                            <div class="text-left">
                                <span class="px-2 py-1 text-xs rounded-full <?= 
                                    $attendance['attendance_rating'] == 'excellent' ? 'bg-blue-100 text-blue-800' :
                                    ($attendance['attendance_rating'] == 'very_good' ? 'bg-green-100 text-green-800' :
                                    ($attendance['attendance_rating'] == 'good' ? 'bg-yellow-100 text-yellow-800' :
                                    ($attendance['attendance_rating'] == 'acceptable' ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800'))) 
                                ?>">
                                    <?php
                                    $rating_text = [
                                        'excellent' => 'ممتاز',
                                        'very_good' => 'جيد جداً',
                                        'good' => 'جيد',
                                        'acceptable' => 'مقبول',
                                        'poor' => 'ضعيف'
                                    ];
                                    echo $rating_text[$attendance['attendance_rating']] ?? 'غير محدد';
                                    ?>
                                </span>
                                <div class="text-xs text-gray-500 mt-1">
                                    <?= $attendance['attendance_type'] == 'direct' ? 'مباشر' : 'عن بُعد' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4 text-center">
                    <a href="elearning_attendance_reports.php" class="text-blue-600 hover:text-blue-800 text-sm">
                        عرض جميع التقارير <i class="fas fa-arrow-left mr-1"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- أحدث التقييمات -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-star ml-2 text-yellow-600"></i>
                    أحدث تقييمات نظام قطر
                </h2>
                
                <?php if (empty($recent_evaluations)): ?>
                <div class="text-center text-gray-500 py-8">
                    <i class="fas fa-inbox text-3xl mb-2"></i>
                    <p>لا توجد تقييمات حديثة</p>
                </div>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($recent_evaluations as $evaluation): ?>
                    <div class="border border-gray-200 rounded-lg p-3 hover:bg-gray-50">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-medium text-gray-800"><?= htmlspecialchars($evaluation['teacher_name']) ?></h4>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($evaluation['subject_name']) ?></p>
                                <p class="text-xs text-gray-500"><?= date('Y-m-d', strtotime($evaluation['evaluation_date'])) ?></p>
                            </div>
                            <div class="text-left">
                                <div class="text-lg font-bold text-gray-800"><?= number_format($evaluation['total_score'], 1) ?></div>
                                <span class="px-2 py-1 text-xs rounded-full <?= 
                                    $evaluation['performance_level'] == 'excellent' ? 'bg-blue-100 text-blue-800' :
                                    ($evaluation['performance_level'] == 'very_good' ? 'bg-green-100 text-green-800' :
                                    ($evaluation['performance_level'] == 'good' ? 'bg-yellow-100 text-yellow-800' :
                                    ($evaluation['performance_level'] == 'needs_improvement' ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800'))) 
                                ?>">
                                    <?php
                                    $performance_text = [
                                        'excellent' => 'ممتاز',
                                        'very_good' => 'جيد جداً',
                                        'good' => 'جيد',
                                        'needs_improvement' => 'يحتاج تحسين',
                                        'poor' => 'ضعيف'
                                    ];
                                    echo $performance_text[$evaluation['performance_level']] ?? 'غير محدد';
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4 text-center">
                    <a href="qatar_system_evaluation.php" class="text-blue-600 hover:text-blue-800 text-sm">
                        إجراء تقييم جديد <i class="fas fa-plus mr-1"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- روابط سريعة -->
        <div class="bg-white rounded-lg shadow p-6 mt-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-link ml-2 text-purple-600"></i>
                روابط سريعة
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <a href="elearning_attendance.php" class="block p-4 border-2 border-blue-200 rounded-lg hover:border-blue-400 hover:bg-blue-50 transition duration-200">
                    <div class="text-center">
                        <i class="fas fa-clipboard-check text-2xl text-blue-600 mb-2"></i>
                        <h3 class="font-medium text-gray-800">تسجيل حضور جديد</h3>
                        <p class="text-xs text-gray-600 mt-1">تسجيل حضور حصة تعليم إلكتروني</p>
                    </div>
                </a>
                
                <a href="qatar_system_evaluation.php" class="block p-4 border-2 border-yellow-200 rounded-lg hover:border-yellow-400 hover:bg-yellow-50 transition duration-200">
                    <div class="text-center">
                        <i class="fas fa-star text-2xl text-yellow-600 mb-2"></i>
                        <h3 class="font-medium text-gray-800">تقييم نظام قطر</h3>
                        <p class="text-xs text-gray-600 mt-1">تقييم أداء معلم على نظام قطر</p>
                    </div>
                </a>
                
                <a href="elearning_attendance_reports.php" class="block p-4 border-2 border-green-200 rounded-lg hover:border-green-400 hover:bg-green-50 transition duration-200">
                    <div class="text-center">
                        <i class="fas fa-chart-bar text-2xl text-green-600 mb-2"></i>
                        <h3 class="font-medium text-gray-800">تقارير الحضور</h3>
                        <p class="text-xs text-gray-600 mt-1">تقارير مفصلة للحضور</p>
                    </div>
                </a>
                
                <a href="elearning_coordinator_dashboard.php" class="block p-4 border-2 border-purple-200 rounded-lg hover:border-purple-400 hover:bg-purple-50 transition duration-200">
                    <div class="text-center">
                        <i class="fas fa-home text-2xl text-purple-600 mb-2"></i>
                        <h3 class="font-medium text-gray-800">لوحة التحكم</h3>
                        <p class="text-xs text-gray-600 mt-1">العودة للصفحة الرئيسية</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// رسم بياني لتقييمات الحضور
const attendanceCtx = document.getElementById('attendanceChart')?.getContext('2d');
if (attendanceCtx) {
    new Chart(attendanceCtx, {
        type: 'doughnut',
        data: {
            labels: ['ممتاز', 'جيد جداً', 'جيد', 'مقبول', 'ضعيف'],
            datasets: [{
                data: [
                    <?= $attendance_stats['excellent_sessions'] ?? 0 ?>,
                    <?= $attendance_stats['very_good_sessions'] ?? 0 ?>,
                    <?= $attendance_stats['good_sessions'] ?? 0 ?>,
                    <?= $attendance_stats['acceptable_sessions'] ?? 0 ?>,
                    <?= $attendance_stats['poor_sessions'] ?? 0 ?>
                ],
                backgroundColor: ['#06b6d4', '#22c55e', '#eab308', '#f97316', '#ef4444'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// رسم بياني لتقييمات نظام قطر
const qatarCtx = document.getElementById('qatarChart')?.getContext('2d');
if (qatarCtx) {
    new Chart(qatarCtx, {
        type: 'bar',
        data: {
            labels: ['ممتاز', 'جيد جداً', 'جيد', 'يحتاج تحسين', 'ضعيف'],
            datasets: [{
                label: 'عدد التقييمات',
                data: [
                    <?= $qatar_stats['excellent_count'] ?? 0 ?>,
                    <?= $qatar_stats['very_good_count'] ?? 0 ?>,
                    <?= $qatar_stats['good_count'] ?? 0 ?>,
                    <?= $qatar_stats['needs_improvement_count'] ?? 0 ?>,
                    <?= $qatar_stats['poor_count'] ?? 0 ?>
                ],
                backgroundColor: ['#06b6d4', '#22c55e', '#eab308', '#f97316', '#ef4444'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// رسم بياني لاستخدام الأدوات
const toolsCtx = document.getElementById('toolsChart')?.getContext('2d');
if (toolsCtx) {
    new Chart(toolsCtx, {
        type: 'pie',
        data: {
            labels: [<?php foreach ($tools_analysis as $tool => $count): ?>'<?= $tool_names[$tool] ?? $tool ?>',<?php endforeach; ?>],
            datasets: [{
                data: [<?php foreach ($tools_analysis as $count): ?><?= $count ?>,<?php endforeach; ?>],
                backgroundColor: [
                    '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
                    '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#6366f1'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}
</script>

<?php include 'includes/elearning_footer.php'; ?>
