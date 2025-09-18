<?php
/**
 * لوحة تحكم المنسق
 * 
 * تستخدم هذه الصفحة ملف visit_rules.php للقوانين الموحدة:
 * - حساب النسب المئوية للأداء
 * - تحديد مستويات الأداء (ممتاز، جيد جداً، إلخ)
 * - ثوابت التقييم الموحدة
 * - دالة get_grade() في functions.php متوافقة مع نفس العتبات
 * 
 * @version 2.0 - محدثة لاستخدام القوانين الموحدة
 */

// تضمين ملفات النظام
require_once 'includes/auth_functions.php';
require_once 'includes/functions.php';
require_once 'visit_rules.php';

// حماية الصفحة للمنسقين فقط
protect_page(['Subject Coordinator']);

// تعيين عنوان الصفحة
$page_title = 'لوحة تحكم المنسق - نظام الزيارات الصفية';

// الحصول على بيانات المنسق
$coordinator_id = $_SESSION['user_id'];
$coordinator_name = $_SESSION['full_name'];
$subject_id = $_SESSION['subject_id'];
$school_id = $_SESSION['school_id'];

// التحقق من وجود بيانات المنسق المطلوبة
if (!$subject_id || !$school_id) {
    // محاولة الحصول على بيانات المنسق من قاعدة البيانات
    $coordinator_data = query_row("
        SELECT cs.subject_id, u.school_id 
        FROM coordinator_supervisors cs 
        INNER JOIN users u ON cs.user_id = u.id 
        WHERE cs.user_id = ?
    ", [$coordinator_id]);
    
    if ($coordinator_data) {
        $subject_id = $coordinator_data['subject_id'];
        $school_id = $coordinator_data['school_id'];
        
        // تحديث بيانات الجلسة
        $_SESSION['subject_id'] = $subject_id;
        $_SESSION['school_id'] = $school_id;
    } else {
        die("خطأ: لا يمكن العثور على بيانات المنسق. يرجى التواصل مع الإدارة.");
    }
}

// الحصول على اسم المادة
$subject = query_row("SELECT name FROM subjects WHERE id = ?", [$subject_id]);
$subject_name = $subject['name'] ?? 'غير محدد';

// الحصول على اسم المدرسة
$school = query_row("SELECT name FROM schools WHERE id = ?", [$school_id]);
$school_name = $school['name'] ?? 'غير محدد';

// إحصائيات سريعة للمنسق
$stats = [];

// عدد المعلمين في المادة (المعلمين فقط - بدون منسقين أو موجهين)
if ($subject_id && $school_id) {
    $teachers_count = query_row("
        SELECT COUNT(DISTINCT t.id) as count 
        FROM teacher_subjects ts 
        INNER JOIN teachers t ON ts.teacher_id = t.id 
        WHERE ts.subject_id = ? AND t.school_id = ? AND t.job_title = 'معلم'
    ", [$subject_id, $school_id]);
    $stats['teachers_count'] = $teachers_count['count'] ?? 0;
} else {
    $stats['teachers_count'] = 0;
}

// عدد الزيارات هذا الشهر للمادة
if ($subject_id && $school_id) {
    $visits_this_month = query_row("
        SELECT COUNT(*) as count 
        FROM visits v 
        WHERE v.subject_id = ? 
        AND v.school_id = ? 
        AND MONTH(v.visit_date) = MONTH(CURRENT_DATE()) 
        AND YEAR(v.visit_date) = YEAR(CURRENT_DATE())
    ", [$subject_id, $school_id]);
    $stats['visits_this_month'] = $visits_this_month['count'] ?? 0;
} else {
    $stats['visits_this_month'] = 0;
}

// متوسط الأداء للمادة (باستخدام الدوال الموحدة)
if ($subject_id && $school_id) {
    $avg_performance = query_row("
        SELECT AVG(visit_scores.avg_score) as avg_score
        FROM visits v
        INNER JOIN (
            SELECT ve.visit_id, AVG(ve.score) as avg_score
            FROM visit_evaluations ve
            GROUP BY ve.visit_id
        ) visit_scores ON v.id = visit_scores.visit_id
        INNER JOIN teachers t ON v.teacher_id = t.id
        WHERE v.subject_id = ? AND v.school_id = ? AND t.job_title = 'معلم'
    ", [$subject_id, $school_id]);
    
    // استخدام الدالة الموحدة لحساب النسبة المئوية
    $avg_score = $avg_performance['avg_score'] ?? 0;
    $stats['avg_performance'] = $avg_score ? round(($avg_score / MAX_INDICATOR_SCORE) * 100, 1) : 0;
} else {
    $stats['avg_performance'] = 0;
}

// الزيارات الأخيرة للمادة
if ($subject_id && $school_id) {
    $recent_visits = query("
        SELECT v.*, t.name as teacher_name, vt.name as visitor_type_name,
               AVG(ve.score) as avg_score
        FROM visits v
        LEFT JOIN teachers t ON v.teacher_id = t.id
        LEFT JOIN visitor_types vt ON v.visitor_type_id = vt.id
        LEFT JOIN visit_evaluations ve ON v.id = ve.visit_id
        WHERE v.subject_id = ? AND v.school_id = ? AND t.job_title = 'معلم'
        GROUP BY v.id
        ORDER BY v.visit_date DESC, v.created_at DESC
        LIMIT 10
    ", [$subject_id, $school_id]);
} else {
    $recent_visits = [];
}

// معلمي المادة وأدائهم
if ($subject_id && $school_id) {
    $teachers_performance = query("
        SELECT 
            t.id, 
            t.name, 
            IFNULL(COUNT(DISTINCT v.id), 0) as visits_count,
            IFNULL(AVG(visit_scores.avg_score), 0) as avg_score
        FROM 
            teachers t
        INNER JOIN 
            teacher_subjects ts ON t.id = ts.teacher_id
        LEFT JOIN 
            visits v ON t.id = v.teacher_id AND v.subject_id = ?
        LEFT JOIN (
            SELECT 
                ve.visit_id, 
                AVG(ve.score) as avg_score
            FROM 
                visit_evaluations ve
            GROUP BY 
                ve.visit_id
        ) visit_scores ON v.id = visit_scores.visit_id
        WHERE 
            ts.subject_id = ? AND t.school_id = ? AND t.job_title = 'معلم'
        GROUP BY 
            t.id, t.name
        ORDER BY 
            visits_count DESC, t.name
    ", [$subject_id, $subject_id, $school_id]);
    
    // حساب توزيع مستويات الأداء
    $performance_distribution = [
        'excellent' => 0,
        'very_good' => 0, 
        'good' => 0,
        'acceptable' => 0,
        'needs_improvement' => 0,
        'no_visits' => 0
    ];
    
    foreach ($teachers_performance as $teacher) {
        if ($teacher['visits_count'] > 0) {
            $percentage = ($teacher['avg_score'] / MAX_INDICATOR_SCORE) * 100;
            $level = getPerformanceLevel($percentage);
            
            switch ($level['grade_ar']) {
                case 'ممتاز':
                    $performance_distribution['excellent']++;
                    break;
                case 'جيد جداً':
                    $performance_distribution['very_good']++;
                    break;
                case 'جيد':
                    $performance_distribution['good']++;
                    break;
                case 'مقبول':
                    $performance_distribution['acceptable']++;
                    break;
                default:
                    $performance_distribution['needs_improvement']++;
            }
        } else {
            $performance_distribution['no_visits']++;
        }
    }
} else {
    $teachers_performance = [];
    $performance_distribution = [];
}

// الموجهين المخصصين للمنسق
$supervisors = get_coordinator_supervisors($coordinator_id);

// تضمين ملف رأس الصفحة
require_once 'includes/header.php';


?>

<!-- قسم الترويسة والملخص -->
<div class="bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl shadow-lg p-6 mb-6">
    <div class="flex flex-col md:flex-row justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold mb-2">
                <i class="fas fa-user-tie ml-3"></i>
                مرحباً، <?= htmlspecialchars($coordinator_name) ?>
            </h1>
            <p class="text-purple-100">منسق مادة <?= htmlspecialchars($subject_name) ?> - <?= htmlspecialchars($school_name) ?></p>
        </div>
        <div class="mt-4 md:mt-0 flex space-x-4 space-x-reverse">
            <a href="evaluation_form.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-6 py-3 rounded-lg font-semibold transition duration-200">
                <i class="fas fa-plus ml-2"></i>
                زيارة جديدة
            </a>
            <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-lg font-semibold transition duration-200">
                <i class="fas fa-sign-out-alt ml-2"></i>
                تسجيل الخروج
            </a>
        </div>
    </div>
</div>

<!-- الإحصائيات السريعة -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- عدد المعلمين -->
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl p-6 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold mb-2">معلمي المادة</h3>
                <p class="text-3xl font-bold"><?= $stats['teachers_count'] ?></p>
                <p class="text-blue-200 text-sm mt-1">معلم في مادتك</p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-3">
                <i class="fas fa-users text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- الزيارات هذا الشهر -->
    <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-xl p-6 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold mb-2">زيارات الشهر</h3>
                <p class="text-3xl font-bold"><?= $stats['visits_this_month'] ?></p>
                <p class="text-green-200 text-sm mt-1">زيارة هذا الشهر</p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-3">
                <i class="fas fa-calendar-check text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- متوسط الأداء -->
    <?php 
    $avg_percentage = $stats['avg_performance'];
    $avg_performance_level = getPerformanceLevel($avg_percentage);
    
    // تحديد لون الخلفية حسب مستوى الأداء
    $gradient_colors = [
        'ممتاز' => 'from-green-500 to-green-600',
        'جيد جداً' => 'from-blue-500 to-blue-600', 
        'جيد' => 'from-yellow-500 to-yellow-600',
        'مقبول' => 'from-orange-500 to-orange-600',
        'يحتاج تحسين' => 'from-red-500 to-red-600'
    ];
    $bg_gradient = $gradient_colors[$avg_performance_level['grade_ar']] ?? 'from-gray-500 to-gray-600';
    ?>
    <div class="bg-gradient-to-br <?= $bg_gradient ?> text-white rounded-xl p-6 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold mb-2">متوسط الأداء</h3>
                <p class="text-3xl font-bold"><?= $avg_percentage ?>%</p>
                <p class="text-white opacity-80 text-sm mt-1">
                    <?= $avg_performance_level['grade_ar'] ?> - للمادة بشكل عام
                </p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-3">
                <i class="fas fa-chart-line text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- المحتوى الرئيسي -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    
    <!-- أداء المعلمين -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-chart-bar text-blue-600 ml-3"></i>
            أداء معلمي المادة
        </h2>
        
        <div class="space-y-4">
            <?php if (empty($teachers_performance)): ?>
                <p class="text-gray-500 text-center py-8">لا توجد بيانات متاحة</p>
            <?php else: ?>
                <?php foreach ($teachers_performance as $teacher): ?>
                    <div class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-semibold text-gray-800"><?= htmlspecialchars($teacher['name']) ?></h4>
                                <p class="text-sm text-gray-600"><?= $teacher['visits_count'] ?> زيارة</p>
                            </div>
                            <div class="text-left">
                                <?php 
                                if ($teacher['visits_count'] > 0) {
                                    $avg_score = $teacher['avg_score'] ?? 0;
                                    $percentage = $avg_score ? round(($avg_score / MAX_INDICATOR_SCORE) * 100, 1) : 0;
                                    
                                    // استخدام الدالة الموحدة لتحديد مستوى الأداء
                                    $performance_level = getPerformanceLevel($percentage);
                                    $color_class = str_replace('text-', '', $performance_level['color_class']);
                                    $color = explode('-', $color_class)[0]; // استخراج اللون الأساسي
                                    ?>
                                    <div class="text-center">
                                        <span class="bg-<?= $color ?>-100 text-<?= $color ?>-800 px-3 py-1 rounded-full font-bold">
                                            <?= $percentage ?>%
                                        </span>
                                        <div class="text-xs <?= $performance_level['color_class'] ?> mt-1">
                                            <?= $performance_level['grade_ar'] ?>
                                        </div>
                                    </div>
                                <?php } else { ?>
                                    <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-sm">
                                        لا توجد زيارات
                                    </span>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="mt-4 text-left">
            <a href="teacher_report.php?subject_id=<?= $subject_id ?>" class="text-blue-600 hover:text-blue-800 font-medium">
                عرض التقرير المفصل
                <i class="fas fa-arrow-left mr-2"></i>
            </a>
        </div>
    </div>

    <!-- الزيارات الأخيرة -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-history text-purple-600 ml-3"></i>
            الزيارات الأخيرة
        </h2>
        
        <div class="space-y-4">
            <?php if (empty($recent_visits)): ?>
                <p class="text-gray-500 text-center py-8">لا توجد زيارات حديثة</p>
            <?php else: ?>
                <?php foreach (array_slice($recent_visits, 0, 5) as $visit): ?>
                    <div class="border-r-4 border-purple-500 bg-purple-50 p-4 rounded-lg">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-semibold text-gray-800"><?= htmlspecialchars($visit['teacher_name']) ?></h4>
                                <p class="text-sm text-gray-600">الزائر: <?= htmlspecialchars($visit['visitor_type_name']) ?></p>
                                <p class="text-xs text-gray-500"><?= format_date_ar($visit['visit_date']) ?></p>
                            </div>
                            <?php if ($visit['avg_score']): ?>
                                <div class="text-left">
                                    <?php 
                                    $avg_score = $visit['avg_score'];
                                    $percentage = ($avg_score / MAX_INDICATOR_SCORE) * 100;
                                    
                                    // استخدام الدالة الموحدة لتحديد مستوى الأداء
                                    $performance_level = getPerformanceLevel($percentage);
                                    $color = explode('-', str_replace('text-', '', $performance_level['color_class']))[0];
                                    ?>
                                    <div class="text-center">
                                        <span class="bg-<?= $color ?>-100 text-<?= $color ?>-800 px-2 py-1 rounded text-xs font-semibold">
                                            <?= round($percentage, 1) ?>%
                                        </span>
                                        <div class="text-xs <?= $performance_level['color_class'] ?> mt-1">
                                            <?= $performance_level['grade_ar'] ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="mt-4 text-left">
            <a href="visits.php?subject_id=<?= $subject_id ?>" class="text-purple-600 hover:text-purple-800 font-medium">
                عرض جميع الزيارات
                <i class="fas fa-arrow-left mr-2"></i>
            </a>
        </div>
    </div>
</div>

<!-- توزيع مستويات الأداء -->
<?php if (!empty($performance_distribution) && array_sum($performance_distribution) > 0): ?>
<div class="mt-8 bg-white rounded-xl shadow-lg p-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
        <i class="fas fa-chart-pie text-indigo-600 ml-3"></i>
        توزيع مستويات الأداء للمعلمين
    </h2>
    
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <?php if ($performance_distribution['excellent'] > 0): ?>
        <div class="text-center p-4 bg-green-50 rounded-lg border border-green-200">
            <div class="text-2xl font-bold text-green-600"><?= $performance_distribution['excellent'] ?></div>
            <div class="text-sm text-green-800">ممتاز</div>
            <div class="text-xs text-green-600"><?= EXCELLENT_THRESHOLD ?>%+</div>
        </div>
        <?php endif; ?>
        
        <?php if ($performance_distribution['very_good'] > 0): ?>
        <div class="text-center p-4 bg-blue-50 rounded-lg border border-blue-200">
            <div class="text-2xl font-bold text-blue-600"><?= $performance_distribution['very_good'] ?></div>
            <div class="text-sm text-blue-800">جيد جداً</div>
            <div class="text-xs text-blue-600"><?= VERY_GOOD_THRESHOLD ?>%+</div>
        </div>
        <?php endif; ?>
        
        <?php if ($performance_distribution['good'] > 0): ?>
        <div class="text-center p-4 bg-yellow-50 rounded-lg border border-yellow-200">
            <div class="text-2xl font-bold text-yellow-600"><?= $performance_distribution['good'] ?></div>
            <div class="text-sm text-yellow-800">جيد</div>
            <div class="text-xs text-yellow-600"><?= GOOD_THRESHOLD ?>%+</div>
        </div>
        <?php endif; ?>
        
        <?php if ($performance_distribution['acceptable'] > 0): ?>
        <div class="text-center p-4 bg-orange-50 rounded-lg border border-orange-200">
            <div class="text-2xl font-bold text-orange-600"><?= $performance_distribution['acceptable'] ?></div>
            <div class="text-sm text-orange-800">مقبول</div>
            <div class="text-xs text-orange-600"><?= ACCEPTABLE_THRESHOLD ?>%+</div>
        </div>
        <?php endif; ?>
        
        <?php if ($performance_distribution['needs_improvement'] > 0): ?>
        <div class="text-center p-4 bg-red-50 rounded-lg border border-red-200">
            <div class="text-2xl font-bold text-red-600"><?= $performance_distribution['needs_improvement'] ?></div>
            <div class="text-sm text-red-800">يحتاج تحسين</div>
            <div class="text-xs text-red-600">&lt;<?= ACCEPTABLE_THRESHOLD ?>%</div>
        </div>
        <?php endif; ?>
        
        <?php if ($performance_distribution['no_visits'] > 0): ?>
        <div class="text-center p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="text-2xl font-bold text-gray-600"><?= $performance_distribution['no_visits'] ?></div>
            <div class="text-sm text-gray-800">بدون زيارات</div>
            <div class="text-xs text-gray-600">لم يتم تقييمهم</div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="mt-4 text-sm text-gray-600 text-center">
        <i class="fas fa-info-circle ml-1"></i>
        المستويات محسوبة باستخدام القوانين الموحدة من visit_rules.php
    </div>
</div>
<?php endif; ?>

<!-- قسم الروابط السريعة -->
<div class="mt-8 bg-gradient-to-r from-gray-100 to-gray-200 rounded-xl p-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
        <i class="fas fa-link text-gray-600 ml-3"></i>
        الروابط السريعة
    </h2>
    
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="evaluation_form.php" class="bg-white hover:bg-gray-50 p-4 rounded-lg text-center transition shadow">
            <i class="fas fa-plus text-green-600 text-2xl mb-2"></i>
            <p class="font-semibold text-gray-800">زيارة جديدة</p>
        </a>
        
        <a href="visits.php?subject_id=<?= $subject_id ?>" class="bg-white hover:bg-gray-50 p-4 rounded-lg text-center transition shadow">
            <i class="fas fa-list text-blue-600 text-2xl mb-2"></i>
            <p class="font-semibold text-gray-800">جميع الزيارات</p>
        </a>
        
        <a href="training_needs.php?subject_id=<?= $subject_id ?>" class="bg-white hover:bg-gray-50 p-4 rounded-lg text-center transition shadow">
            <i class="fas fa-chart-line text-purple-600 text-2xl mb-2"></i>
            <p class="font-semibold text-gray-800">الاحتياجات التدريبية</p>
        </a>
        
        <a href="teachers_management.php?subject_id=<?= $subject_id ?>" class="bg-white hover:bg-gray-50 p-4 rounded-lg text-center transition shadow">
            <i class="fas fa-users text-orange-600 text-2xl mb-2"></i>
            <p class="font-semibold text-gray-800">إدارة المعلمين</p>
        </a>
    </div>
</div>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?>
