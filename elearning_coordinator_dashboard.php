<?php
/**
 * لوحة تحكم منسق التعليم الإلكتروني
 */

require_once 'includes/auth_functions.php';
require_once 'includes/functions.php';

// حماية الصفحة لمنسقي التعليم الإلكتروني
protect_page(['E-Learning Coordinator', 'Admin', 'Director', 'Academic Deputy']);

$page_title = 'لوحة تحكم منسق التعليم الإلكتروني';

// الحصول على السنة الدراسية الحالية
$current_year = get_active_academic_year();
$current_year_id = $current_year ? $current_year['id'] : 2;

// إحصائيات حضور التعليم الإلكتروني
$attendance_stats = [];
if ($current_year_id) {
    $attendance_stats = query_row("
        SELECT 
            COUNT(*) as total_sessions,
            COUNT(DISTINCT teacher_id) as active_teachers,
            COUNT(DISTINCT subject_id) as unique_subjects,
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
}

// إحصائيات تقييمات نظام قطر
$qatar_stats = [];
if ($current_year_id) {
    $qatar_stats = query_row("
        SELECT 
            COUNT(*) as total_evaluations,
            COUNT(DISTINCT teacher_id) as evaluated_teachers,
            AVG(total_score) as avg_score,
            COUNT(CASE WHEN performance_level = 'excellent' THEN 1 END) as excellent_count,
            COUNT(CASE WHEN performance_level = 'very_good' THEN 1 END) as very_good_count,
            COUNT(CASE WHEN performance_level = 'good' THEN 1 END) as good_count,
            COUNT(CASE WHEN performance_level = 'needs_improvement' THEN 1 END) as needs_improvement_count,
            COUNT(CASE WHEN performance_level = 'poor' THEN 1 END) as poor_count
        FROM qatar_system_performance 
        WHERE academic_year_id = ?
    ", [$current_year_id]);
}

// احصائيات الحضور الحديث (آخر 7 أيام)
$recent_attendance = [];
if ($current_year_id) {
    $recent_attendance = query("
        SELECT 
            DATE(lesson_date) as attendance_date,
            COUNT(*) as sessions_count,
            COUNT(CASE WHEN attendance_rating = 'excellent' THEN 1 END) as excellent_sessions
        FROM elearning_attendance 
        WHERE academic_year_id = ? 
            AND lesson_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(lesson_date)
        ORDER BY attendance_date DESC
        LIMIT 7
    ", [$current_year_id]);
}

// أحدث التقييمات
$recent_evaluations = [];
if ($current_year_id) {
    $recent_evaluations = query("
        SELECT 
            qsp.*,
            t.name as teacher_name,
            s.name as subject_name,
            DATE(qsp.evaluation_date) as eval_date
        FROM qatar_system_performance qsp
        JOIN teachers t ON qsp.teacher_id = t.id
        JOIN subjects s ON qsp.subject_id = s.id
        WHERE qsp.academic_year_id = ?
        ORDER BY qsp.evaluation_date DESC
        LIMIT 5
    ", [$current_year_id]);
}

// أحدث الزيارات الصفية  
$recent_visits = [];
if ($current_year_id) {
    $recent_visits = query("
        SELECT 
            ea.*,
            t.name as teacher_name,
            s.name as subject_name,
            DATE(ea.lesson_date) as visit_date
        FROM elearning_attendance ea
        JOIN teachers t ON ea.teacher_id = t.id
        JOIN subjects s ON ea.subject_id = s.id
        WHERE ea.academic_year_id = ?
        ORDER BY ea.lesson_date DESC, ea.id DESC
        LIMIT 5
    ", [$current_year_id]);
}

// استخراج البيانات للرسوم البيانية
$excellent_count = $qatar_stats['excellent_count'] ?? 0;
$very_good_count = $qatar_stats['very_good_count'] ?? 0;
$good_count = $qatar_stats['good_count'] ?? 0;
$needs_improvement_count = $qatar_stats['needs_improvement_count'] ?? 0;
$poor_count = $qatar_stats['poor_count'] ?? 0;

?>

<?php include 'includes/elearning_header.php'; ?>

<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4">
        <!-- عنوان الصفحة -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">مرحباً، منسق التعليم الإلكتروني</h1>
            <p class="text-gray-600 mt-1">متابعة وإدارة أنشطة التعليم الإلكتروني ونظام قطر للتعليم</p>
            <div id="current-datetime" class="text-sm text-gray-500 mt-2"></div>
        </div>

        <!-- بطاقات الإحصائيات الرئيسية -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- إجمالي الزيارات الصفية -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-chalkboard-teacher text-xl"></i>
                    </div>
                    <div class="mr-4">
                        <p class="text-sm font-medium text-gray-600">إجمالي الزيارات الصفية</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?= number_format($attendance_stats['total_sessions'] ?? 0) ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- الزيارات الممتازة -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-trophy text-xl"></i>
                    </div>
                    <div class="mr-4">
                        <p class="text-sm font-medium text-gray-600">الزيارات الممتازة</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?= number_format($attendance_stats['excellent_sessions'] ?? 0) ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- متوسط تقييمات نظام قطر -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-chart-line text-xl"></i>
                    </div>
                    <div class="mr-4">
                        <p class="text-sm font-medium text-gray-600">متوسط تقييمات قطر</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?= number_format($qatar_stats['avg_score'] ?? 0, 1) ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- التقييمات المكتملة -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-star text-xl"></i>
                    </div>
                    <div class="mr-4">
                        <p class="text-sm font-medium text-gray-600">التقييمات المكتملة</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?= number_format($qatar_stats['total_evaluations'] ?? 0) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- الإجراءات السريعة -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-chalkboard-teacher ml-2"></i>
                    الزيارات الصفية
                </h2>
                <p class="text-gray-600 mb-4">تسجيل ومتابعة الزيارات الصفية للمعلمين</p>
                <div class="space-y-2">
                    <a href="elearning_attendance.php" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded transition duration-200">
                        <i class="fas fa-plus ml-2"></i>
                        تسجيل زيارة جديدة
                    </a>
                    <a href="elearning_attendance_reports.php" class="block w-full bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded transition duration-200">
                        <i class="fas fa-chart-bar ml-2"></i>
                        تقارير الزيارات
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-award ml-2"></i>
                    نظام قطر للتعليم
                </h2>
                <p class="text-gray-600 mb-4">متابعة وتقييم أداء المعلمين على نظام قطر للتعليم</p>
                <div class="space-y-2">
                    <a href="qatar_system_evaluation.php" class="block w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded transition duration-200">
                        <i class="fas fa-star ml-2"></i>
                        تقييم معلم جديد
                    </a>
                    <a href="qatar_system_reports.php" class="block w-full bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded transition duration-200">
                        <i class="fas fa-chart-line ml-2"></i>
                        تقارير الأداء
                    </a>
                </div>
            </div>
        </div>

        <!-- الرسوم البيانية والتقارير -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- أحدث الزيارات الصفية -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">أحدث الزيارات الصفية</h3>
                <?php if (empty($recent_visits)): ?>
                    <p class="text-gray-500 text-center py-4">لا توجد زيارات حديثة</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($recent_visits as $visit): ?>
                            <div class="border-r-4 <?= get_attendance_color($visit['attendance_rating']) ?> pr-4 py-2">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-medium"><?= htmlspecialchars($visit['teacher_name']) ?></p>
                                        <p class="text-sm text-gray-600"><?= htmlspecialchars($visit['subject_name']) ?></p>
                                        <p class="text-xs text-gray-500"><?= date('Y/m/d', strtotime($visit['visit_date'])) ?></p>
                                    </div>
                                    <div class="text-left">
                                        <span class="text-sm font-medium <?= get_attendance_text_color($visit['attendance_rating']) ?>">
                                            <?= get_attendance_rating_text($visit['attendance_rating']) ?>
                                        </span>
                                        <p class="text-xs text-gray-500">
                                            <?= $visit['attendance_type'] == 'direct' ? 'مباشر' : 'عن بُعد' ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- أحدث التقييمات -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">أحدث التقييمات</h3>
                <?php if (empty($recent_evaluations)): ?>
                    <p class="text-gray-500 text-center py-4">لا توجد تقييمات حديثة</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($recent_evaluations as $eval): ?>
                            <div class="border-r-4 <?= get_performance_color($eval['performance_level']) ?> pr-4 py-2">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-medium"><?= htmlspecialchars($eval['teacher_name']) ?></p>
                                        <p class="text-sm text-gray-600"><?= htmlspecialchars($eval['subject_name']) ?></p>
                                        <p class="text-xs text-gray-500"><?= date('Y/m/d', strtotime($eval['eval_date'])) ?></p>
                                    </div>
                                    <div class="text-left">
                                        <span class="text-lg font-bold"><?= number_format($eval['total_score'], 1) ?></span>
                                        <p class="text-xs <?= get_performance_text_color($eval['performance_level']) ?>">
                                            <?= get_performance_text($eval['performance_level']) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- JavaScript للرسوم البيانية -->
    <script>
        // رسم مخطط الحضور
        const attendanceData = <?= json_encode(array_reverse($recent_attendance)) ?>;
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: attendanceData.map(item => {
                    const date = new Date(item.attendance_date);
                    return date.toLocaleDateString('ar');
                }),
                datasets: [{
                    label: 'نسبة الحضور %',
                    data: attendanceData.map(item => parseFloat(item.attendance_percentage || 0).toFixed(1)),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>

<?php
function get_performance_color($level) {
    switch ($level) {
        case 'excellent': return 'border-green-500';
        case 'very_good': return 'border-blue-500';
        case 'good': return 'border-yellow-500';
        case 'needs_improvement': return 'border-orange-500';
        case 'poor': return 'border-red-500';
        default: return 'border-gray-500';
    }
}

function get_performance_text_color($level) {
    switch ($level) {
        case 'excellent': return 'text-green-600';
        case 'very_good': return 'text-blue-600';
        case 'good': return 'text-yellow-600';
        case 'needs_improvement': return 'text-orange-600';
        case 'poor': return 'text-red-600';
        default: return 'text-gray-600';
    }
}

function get_performance_text($level) {
    switch ($level) {
        case 'excellent': return 'ممتاز';
        case 'very_good': return 'جيد جداً';
        case 'good': return 'جيد';
        case 'needs_improvement': return 'يحتاج تحسين';
        case 'poor': return 'ضعيف';
        default: return 'غير محدد';
    }
}

function get_attendance_color($rating) {
    switch ($rating) {
        case 'excellent': return 'border-green-500';
        case 'very_good': return 'border-blue-500';
        case 'good': return 'border-yellow-500';
        case 'acceptable': return 'border-orange-500';
        case 'poor': return 'border-red-500';
        default: return 'border-gray-500';
    }
}

function get_attendance_text_color($rating) {
    switch ($rating) {
        case 'excellent': return 'text-green-600';
        case 'very_good': return 'text-blue-600';
        case 'good': return 'text-yellow-600';
        case 'acceptable': return 'text-orange-600';
        case 'poor': return 'text-red-600';
        default: return 'text-gray-600';
    }
}

function get_attendance_rating_text($rating) {
    switch ($rating) {
        case 'excellent': return 'ممتاز';
        case 'very_good': return 'جيد جداً';
        case 'good': return 'جيد';
        case 'acceptable': return 'مقبول';
        case 'poor': return 'ضعيف';
        default: return 'غير محدد';
    }
}
?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // رسم بياني لتقييمات نظام قطر
    const evaluationCtx = document.getElementById('evaluationChart')?.getContext('2d');
    if (evaluationCtx) {
        new Chart(evaluationCtx, {
            type: 'bar',
            data: {
                labels: ['ممتاز', 'جيد جداً', 'جيد', 'يحتاج تحسين', 'ضعيف'],
                datasets: [{
                    label: 'عدد المعلمين',
                    data: [<?= $excellent_count ?>, <?= $very_good_count ?>, <?= $good_count ?>, <?= $needs_improvement_count ?>, <?= $poor_count ?>],
                    backgroundColor: ['#06b6d4', '#22c55e', '#eab308', '#f97316', '#ef4444'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // تحديث التاريخ والوقت
    function updateDateTime() {
        const now = new Date();
        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        };
        
        const dateTimeString = now.toLocaleDateString('ar-SA', options);
        const datetimeElement = document.getElementById('current-datetime');
        if (datetimeElement) {
            datetimeElement.textContent = dateTimeString;
        }
    }

    // تحديث التاريخ والوقت فور تحميل الصفحة
    updateDateTime();
    
    // تحديث التاريخ والوقت كل ثانية
    setInterval(updateDateTime, 1000);
</script>

    </div>
</div>

<?php include 'includes/elearning_footer.php'; ?>
