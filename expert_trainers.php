<?php
// استخدام القوانين الموحدة لنظام الزيارات الصفية
require_once 'visit_rules.php';

// بدء التخزين المؤقت للمخرجات
ob_start();

// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';
require_once 'includes/auth_functions.php';

// حماية الصفحة - إضافة المعلمين للصلاحيات
protect_page(['Admin', 'Director', 'Academic Deputy', 'Supervisor', 'Subject Coordinator', 'Teacher']);

// تحديد البيانات بناءً على دور المستخدم
$user_role = $_SESSION['role_name'] ?? '';
$is_coordinator = ($user_role === 'Subject Coordinator');
$is_teacher = ($user_role === 'Teacher');

// تعيين عنوان الصفحة
if ($is_teacher) {
    $page_title = 'تميزي في التدريب - نقاط قوتك التدريبية';
} else {
    $page_title = 'المعلمين المتميزين المؤهلين للتدريب';
}
$current_page = 'expert_trainers.php';

$coordinator_subject_id = null;
$coordinator_school_id = null;
$current_teacher_id = null;

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
} elseif ($is_teacher) {
    // جلب معلومات المعلم
    $current_teacher_id = $_SESSION['teacher_id'] ?? null;
    
    // إذا لم يكن teacher_id موجوداً في الجلسة، ابحث عنه
    if (!$current_teacher_id) {
        $teacher_data = query_row("SELECT id FROM teachers WHERE user_id = ?", [$_SESSION['user_id']]);
        if ($teacher_data) {
            $current_teacher_id = $teacher_data['id'];
            $_SESSION['teacher_id'] = $current_teacher_id;
        }
    }
    
    if (!$current_teacher_id) {
        die('خطأ: لم يتم العثور على بيانات المعلم. يرجى الاتصال بالإدارة.');
    }
}

// تضمين ملف رأس الصفحة
require_once 'includes/header.php';

// تضمين مكون فلترة العام الأكاديمي والفصل الدراسي
require_once 'includes/academic_filter.php';

// مصفوفة تربط بين المؤشرات والورش التدريبية
$workshops_mapping = [
    // مجال التخطيط
    1 => '(1.1-1.2) التخطيط الفعال للدروس اليومية.',
    2 => '(1.1 - 1.4) صياغة أهداف تعليمية تراعي الفروق الفردية.',
    3 => '(1.4- 1.5) تصميم أنشطة تعلم تراعي فئات الطلبة المختلفة',
    
    // مجال تنفيذ الدرس
    4 => '(1.1) عرض أهداف الدرس بوضوح ومناقشتها الطلبة.',
    5 => '(2.1 - 2.4) تطبيق أنشطة تمهيد تثير دافعية الطلاب.',
    6 => '(2.1 - 2.2) تنظيم المحتوى التعليمي بطريقة واضحة ومنطقية.',
    7 => '( 2.1 - 2.2 - 2.3 - 2.4 - 2.5 ) استراتيجيات التعلم النشط.',
    8 => '(2.2) استخدام مصادر تعلم تفاعلية ومتنوعة.',
    9 => '(2.2) مهارات تصميم عروض تقديمية جاذبة وتفاعلية.',
    10 => '(2.6) تنويع مستويات الأسئلة الصفية.',
    11 => '(2.7 - 5.3) دقة المادة العلمية وتحليل محتوى المقرر الدراسي.',
    12 => '(2.4 - 2.5 - 2.6) توظيف الكفايات الأساسية بكفاءة وفاعلية.',
    13 => '(3.3 - 6.3) اختيار القيم المناسبة وربطها بموضوع الدرس بشكل منهجي.',
    14 => '(2.7) الربط بين المادة ومع المواد الأخرى بشكل واضح وفعّال.',
    15 => '(1.3 - 2.1 - 2.3) توظيف أنشطة متنوعة تناسب قدرات الطلاب المختلفة.',
    16 => '(4.1 - 4.4) تنويع أساليب غلق الأهداف بكفاءة وفاعلية.',
    
    // مجال التقويم
    17 => '(4.2 - 4.3) تنويع استراتيجيات وأساليب التقويم.',
    18 => '(4.4 - 4.5) تنويع أساليب التغذية الراجعة لتعزيز تعلم الطلبة.',
    19 => '(4.2) تقديم ملاحظات وصفية واضحة على أعمال الطلبة.',
    
    // مجال الإدارة الصفية وبيئة التعلم
    20 => '(3.1 - 3.2 - 3.3) مهارات إدارة السلوك وتعزيز النظام بشكل أكثر فعالية.',
    21 => '(3.1) مهارات تنظيم المشاركات الصفية وتوزيع الأدوار بين الطلاب.',
    22 => '(3.1) استخدام أساليب فعّالة في إدارة السلوك الصفي بشكل مباشر.',
    23 => 'مهارات إدارة الوقت بشكل متوازن بين الشرح والتفاعل والتقييم.'
];

// المستوى المطلوب للاعتبار كخبير (استخدام القوانين الموحدة)
$expert_threshold = EXCELLENT_THRESHOLD;

// جلب المعلمين المتميزين في كل مؤشر
$expert_trainers_sql = "
    SELECT 
        ei.id AS indicator_id,
        ei.name AS indicator_name,
        ed.id AS domain_id,
        ed.name AS domain_name,
        t.id AS teacher_id,
        t.name AS teacher_name,
        s.name AS subject_name,
        AVG(ve.score) AS avg_score,
        (AVG(ve.score) / " . MAX_INDICATOR_SCORE . ") * 100 AS percentage_score,
        COUNT(DISTINCT v.id) AS visits_count
    FROM 
        visit_evaluations ve
    JOIN 
        visits v ON ve.visit_id = v.id
    JOIN 
        evaluation_indicators ei ON ve.indicator_id = ei.id
    JOIN 
        evaluation_domains ed ON ei.domain_id = ed.id
    JOIN 
        teachers t ON v.teacher_id = t.id
    JOIN 
        subjects s ON v.subject_id = s.id
    WHERE 
        v.academic_year_id = ?
        " . (!empty($date_condition) ? $date_condition : "") . "
        AND ve.score IS NOT NULL
        AND t.job_title = 'معلم'";

// إضافة قيود المنسق
if ($is_coordinator && $coordinator_subject_id) {
    $expert_trainers_sql .= " AND s.id = ?";
}
if ($is_coordinator && $coordinator_school_id) {
    $expert_trainers_sql .= " AND t.school_id = ?";
}

// إضافة قيود المعلم - يرى نفسه فقط
if ($is_teacher && $current_teacher_id) {
    $expert_trainers_sql .= " AND t.id = ?";
}

$expert_trainers_sql .= "
    GROUP BY 
        ei.id, ei.name, ed.id, ed.name, t.id, t.name, s.name
    HAVING 
        percentage_score >= ?
        AND visits_count >= 2
    ORDER BY 
        ed.id, ei.id, percentage_score DESC, visits_count DESC
";

$params = [$selected_year_id];
if (!empty($date_params)) {
    $params = array_merge($params, $date_params);
}
if ($is_coordinator && $coordinator_subject_id) {
    $params[] = $coordinator_subject_id;
}
if ($is_coordinator && $coordinator_school_id) {
    $params[] = $coordinator_school_id;
}
if ($is_teacher && $current_teacher_id) {
    $params[] = $current_teacher_id;
}
$params[] = $expert_threshold;

$expert_trainers = query($expert_trainers_sql, $params);

// تنظيم البيانات حسب المؤشر
$trainers_by_indicator = [];
foreach ($expert_trainers as $trainer) {
    $indicator_id = $trainer['indicator_id'];
    if (!isset($trainers_by_indicator[$indicator_id])) {
        $trainers_by_indicator[$indicator_id] = [
            'indicator_name' => $trainer['indicator_name'],
            'domain_name' => $trainer['domain_name'],
            'workshop' => $workshops_mapping[$indicator_id] ?? '',
            'trainers' => []
        ];
    }
    $trainers_by_indicator[$indicator_id]['trainers'][] = $trainer;
}

// إحصائيات عامة
$total_indicators = count($trainers_by_indicator);
$total_expert_teachers = count(array_unique(array_column($expert_trainers, 'teacher_id')));

// إحصائيات إضافية
$indicators_with_trainers = $total_indicators;
$indicators_without_trainers = 23 - $total_indicators; // إجمالي المؤشرات 23
$average_trainers_per_indicator = $total_indicators > 0 ? round(count($expert_trainers) / $total_indicators, 1) : 0;

// الحصول على المؤشرات التي لا تحتوي على مدربين مؤهلين
$all_indicators_sql = "
    SELECT 
        ei.id AS indicator_id,
        ei.name AS indicator_name,
        ed.name AS domain_name
    FROM 
        evaluation_indicators ei
    JOIN 
        evaluation_domains ed ON ei.domain_id = ed.id
    ORDER BY 
        ed.id, ei.id
";
$all_indicators = query($all_indicators_sql);

$indicators_with_trainers_ids = array_keys($trainers_by_indicator);
$indicators_without_trainers_list = [];

foreach ($all_indicators as $indicator) {
    if (!in_array($indicator['indicator_id'], $indicators_with_trainers_ids)) {
        $indicators_without_trainers_list[] = [
            'indicator_id' => $indicator['indicator_id'],
            'indicator_name' => $indicator['indicator_name'],
            'domain_name' => $indicator['domain_name'],
            'workshop' => $workshops_mapping[$indicator['indicator_id']] ?? 'غير محدد'
        ];
    }
}

// أفضل المجالات (التي لديها أكبر عدد من المدربين المؤهلين)
$domain_stats = [];
foreach ($expert_trainers as $trainer) {
    $domain_name = $trainer['domain_name'];
    if (!isset($domain_stats[$domain_name])) {
        $domain_stats[$domain_name] = 0;
    }
    $domain_stats[$domain_name]++;
}
arsort($domain_stats);
$top_domain = !empty($domain_stats) ? array_key_first($domain_stats) : '-';
$top_domain_count = !empty($domain_stats) ? reset($domain_stats) : 0;

?>

<div class="mb-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">المعلمين المتميزين المؤهلين للتدريب</h1>
        <div class="flex space-x-2 space-x-reverse">
            <a href="training_needs.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                <i class="fas fa-chart-line mr-2"></i>
                احتياجات المعلمين
            </a>
            <?php if (!$is_teacher): ?>
            <a href="collective_training_needs.php" class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 transition-colors">
                <i class="fas fa-users mr-2"></i>
                الاحتياجات الجماعية
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($is_coordinator): ?>
        <div class="mb-4 p-3 bg-blue-100 text-blue-800 rounded">
            <strong>مرحباً بك كمنسق مادة!</strong> 
            أنت تعرض المدربين المؤهلين في مادتك فقط.
        </div>
    <?php elseif ($is_teacher): ?>
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
            <strong>مرحباً بك كمعلم متميز!</strong> 
            هذه صفحتك الشخصية لعرض نقاط قوتك التدريبية والمؤشرات التي تتميز فيها.
            <?php if (empty($expert_trainers)): ?>
                <br><br><span class="text-orange-600">
                <i class="fas fa-info-circle mr-1"></i>
                لم تصل بعد للحد الأدنى للتميز في أي مؤشر (<?= EXCELLENT_THRESHOLD ?>% مع زيارتين على الأقل). 
                استمر في التطوير لتصبح مدرباً معتمداً!
                </span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <!-- إحصائيات عامة -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-green-100 p-4 rounded-lg text-center">
                <div class="text-2xl font-bold text-green-800"><?= $total_expert_teachers ?></div>
                <div class="text-green-600">معلم متميز</div>
            </div>
            <div class="bg-blue-100 p-4 rounded-lg text-center">
                <div class="text-2xl font-bold text-blue-800"><?= $indicators_with_trainers ?></div>
                <div class="text-blue-600">مؤشر متاح للتدريب</div>
            </div>
            <div class="bg-purple-100 p-4 rounded-lg text-center">
                <div class="text-2xl font-bold text-purple-800"><?= $average_trainers_per_indicator ?></div>
                <div class="text-purple-600">متوسط المدربين/مؤشر</div>
            </div>
            <div class="bg-orange-100 p-4 rounded-lg text-center">
                <div class="text-2xl font-bold text-orange-800"><?= $indicators_without_trainers ?></div>
                <div class="text-orange-600">مؤشر يحتاج مدربين</div>
            </div>
        </div>

        <!-- أفضل المجالات -->
        <?php if (!empty($domain_stats)): ?>
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white p-4 rounded-lg mb-6">
            <h3 class="text-lg font-semibold mb-2">
                <i class="fas fa-trophy mr-2"></i>
                أفضل المجالات من حيث عدد المدربين المؤهلين
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach (array_slice($domain_stats, 0, 3) as $domain => $count): ?>
                <div class="bg-white bg-opacity-20 p-3 rounded">
                    <div class="text-sm opacity-90"><?= htmlspecialchars($domain) ?></div>
                    <div class="text-xl font-bold"><?= $count ?> مدرب مؤهل</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- معلومات المرشح -->
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        <strong>معايير الترشيح:</strong> المعلمين الذين حققوا ≥ <?= $expert_threshold ?>% في المؤشر مع وجود زيارتين على الأقل خلال العام الأكاديمي <?= htmlspecialchars($current_year_data['name'] ?? '') ?>
                        <?php if ($selected_term != 'all'): ?>
                        (<?= $selected_term == 'first' ? 'الفصل الأول' : 'الفصل الثاني' ?>)
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <?php if (empty($trainers_by_indicator)): ?>
            <div class="text-center py-8">
                <div class="text-gray-500 text-lg">لا توجد بيانات متاحة للعرض</div>
                <div class="text-gray-400 text-sm mt-2">لم يتم العثور على معلمين متميزين بالمعايير المحددة</div>
            </div>
        <?php else: ?>
            
            <!-- جدول المعلمين المتميزين -->
            <div class="space-y-6">
                <?php foreach ($trainers_by_indicator as $indicator_id => $indicator_data): ?>
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <!-- رأس المؤشر -->
                        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4">
                            <h3 class="text-lg font-semibold mb-2">
                                المؤشر: <?= htmlspecialchars($indicator_data['indicator_name']) ?>
                            </h3>
                            <p class="text-blue-100 text-sm mb-2">
                                <strong>المجال:</strong> <?= htmlspecialchars($indicator_data['domain_name']) ?>
                            </p>
                            <?php if (!empty($indicator_data['workshop'])): ?>
                                <div class="bg-blue-800 bg-opacity-50 p-3 rounded">
                                    <p class="text-sm">
                                        <strong>الورشة التدريبية المقترحة:</strong> 
                                        <?= htmlspecialchars($indicator_data['workshop']) ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- جدول المدربين -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">المعلم المدرب</th>
                                        <th class="py-3 px-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">المادة</th>
                                        <th class="py-3 px-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">النسبة المئوية</th>
                                        <th class="py-3 px-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">عدد الزيارات</th>
                                        <th class="py-3 px-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">التقدير</th>
                                        <th class="py-3 px-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($indicator_data['trainers'] as $trainer): ?>
                                        <?php
                                            $percentage = round($trainer['percentage_score'], 1);
                                            $grade_class = '';
                                            $grade_text = '';
                                            
                            $performance_level = getPerformanceLevel($percentage);
                            $grade_class = $performance_level['color_class'];
                            $grade_text = $performance_level['grade_ar'];
                                        ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-4 px-4">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                                                            <span class="text-white font-medium text-sm">
                                                                <?= mb_substr($trainer['teacher_name'], 0, 2) ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            <a href="teacher_report.php?teacher_id=<?= $trainer['teacher_id'] ?>" 
                                                               class="text-blue-600 hover:text-blue-900 hover:underline">
                                                                <?= htmlspecialchars($trainer['teacher_name']) ?>
                                                            </a>
                                                        </div>
                                                        <div class="text-sm text-gray-500">مدرب مؤهل</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-4 px-4 text-center text-sm text-gray-900">
                                                <?= htmlspecialchars($trainer['subject_name']) ?>
                                            </td>
                                            <td class="py-4 px-4 text-center">
                                                <span class="text-2xl font-bold text-green-600">
                                                    <?= $percentage ?>%
                                                </span>
                                            </td>
                                            <td class="py-4 px-4 text-center text-sm text-gray-900">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    <?= $trainer['visits_count'] ?> زيارة
                                                </span>
                                            </td>
                                            <td class="py-4 px-4 text-center">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $grade_class ?>">
                                                    <?= $grade_text ?>
                                                </span>
                                            </td>
                                            <td class="py-4 px-4 text-center">
                                                <div class="flex justify-center space-x-2 space-x-reverse">
                                                    <a href="teacher_report.php?teacher_id=<?= $trainer['teacher_id'] ?>" 
                                                       class="text-blue-600 hover:text-blue-900 text-sm font-medium bg-blue-100 px-3 py-1 rounded-full hover:bg-blue-200 transition-colors">
                                                        <i class="fas fa-chart-line mr-1"></i>
                                                        التقرير
                                                    </a>
                                                    <button onclick="contactTrainer('<?= htmlspecialchars($trainer['teacher_name']) ?>', '<?= htmlspecialchars($trainer['subject_name']) ?>')"
                                                            class="text-green-600 hover:text-green-900 text-sm font-medium bg-green-100 px-3 py-1 rounded-full hover:bg-green-200 transition-colors">
                                                        <i class="fas fa-phone mr-1"></i>
                                                        تواصل
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
                            </div>
            </div>
            
            <!-- قسم المؤشرات التي تحتاج إلى مدربين -->
            <?php if (!empty($indicators_without_trainers_list)): ?>
            <div class="mt-8 border-t-4 border-red-500 bg-red-50 p-6 rounded-lg">
                <h3 class="text-xl font-semibold text-red-800 mb-4">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    المؤشرات التي تحتاج إلى مدربين مؤهلين (<?= count($indicators_without_trainers_list) ?> مؤشر)
                </h3>
                
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                    <p class="text-yellow-800 text-sm">
                        <strong>تنبيه:</strong> هذه المؤشرات لا تحتوي على معلمين حققوا ≥ <?= $expert_threshold ?>% أو لديهم أقل من زيارتين. يُنصح بالبحث عن مدربين خارجيين أو تطوير المعلمين الحاليين.
                    </p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($indicators_without_trainers_list as $indicator): ?>
                    <div class="bg-white border border-red-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user-times text-red-600 text-sm"></i>
                                </div>
                            </div>
                            <div class="ml-3 flex-1">
                                <h4 class="font-medium text-gray-900 mb-1">
                                    <?= htmlspecialchars($indicator['indicator_name']) ?>
                                </h4>
                                <p class="text-sm text-gray-600 mb-2">
                                    <strong>المجال:</strong> <?= htmlspecialchars($indicator['domain_name']) ?>
                                </p>
                                <p class="text-xs text-gray-500 bg-gray-100 p-2 rounded">
                                    <strong>الورشة المطلوبة:</strong> <?= htmlspecialchars($indicator['workshop']) ?>
                                </p>
                                <div class="mt-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        يحتاج مدرب خارجي
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-4 text-center">
                    <button onclick="generateExternalTrainingPlan()" 
                            class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors">
                        <i class="fas fa-external-link-alt mr-2"></i>
                        خطة تدريب خارجي مقترحة
                    </button>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- زر الطباعة وتصدير البيانات -->
            <div class="mt-8 text-center space-y-3">
                <div class="flex justify-center space-x-4 space-x-reverse">
                    <button onclick="window.print()" 
                            class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 transition-colors">
                        <i class="bi bi-printer mr-2"></i> طباعة قائمة المدربين
                    </button>
                    <button onclick="exportToExcel()" 
                            class="bg-green-600 text-white px-6 py-3 rounded-md hover:bg-green-700 transition-colors">
                        <i class="fas fa-file-excel mr-2"></i> تصدير إلى Excel
                    </button>
                    <button onclick="generateTrainingPlan()" 
                            class="bg-purple-600 text-white px-6 py-3 rounded-md hover:bg-purple-700 transition-colors">
                        <i class="fas fa-clipboard-list mr-2"></i> خطة تدريبية مقترحة
                    </button>
                </div>
            </div>
            
        <?php endif; ?>
    </div>
</div>

<style media="print">
    @page {
        size: A4;
        margin: 1cm;
    }
    
    header, nav, footer, button {
        display: none !important;
    }
    
    body {
        background-color: white;
        font-size: 12px;
    }
    
    .bg-gradient-to-r {
        background: #2563eb !important;
        color: white !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .bg-gray-50 {
        background-color: #f9fafb !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .bg-green-100 {
        background-color: #dcfce7 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .bg-blue-100 {
        background-color: #dbeafe !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    table {
        page-break-inside: avoid;
    }
    
    tr {
        page-break-inside: avoid;
    }
</style>

<script>
function contactTrainer(teacherName, subject) {
    alert(`التواصل مع المعلم المدرب:\n\nالاسم: ${teacherName}\nالمادة: ${subject}\n\nيرجى التواصل مع إدارة المدرسة لترتيب جلسة التدريب.`);
}

function exportToExcel() {
    // يمكن تطوير هذه الوظيفة لاحقاً لتصدير البيانات فعلياً
    alert('سيتم تطوير ميزة التصدير إلى Excel قريباً');
}

function generateTrainingPlan() {
    let plan = "خطة تدريبية مقترحة بناءً على المدربين المؤهلين:\n\n";
    
    <?php foreach ($trainers_by_indicator as $indicator_id => $indicator_data): ?>
        <?php if (!empty($indicator_data['trainers'])): ?>
            plan += "• <?= htmlspecialchars($indicator_data['indicator_name']) ?>\n";
            plan += "  المدرب المقترح: <?= htmlspecialchars($indicator_data['trainers'][0]['teacher_name']) ?>\n";
            plan += "  الورشة: <?= htmlspecialchars($indicator_data['workshop']) ?>\n\n";
        <?php endif; ?>
    <?php endforeach; ?>
    
    plan += "\nيرجى التنسيق مع المدربين المقترحين لتحديد مواعيد مناسبة للتدريب.";
    
    alert(plan);
}

function generateExternalTrainingPlan() {
    let plan = "خطة التدريب الخارجي المطلوبة:\n\n";
    plan += "المؤشرات التي تحتاج إلى مدربين خارجيين:\n\n";
    
    <?php foreach ($indicators_without_trainers_list as $indicator): ?>
        plan += "• <?= htmlspecialchars($indicator['indicator_name']) ?>\n";
        plan += "  المجال: <?= htmlspecialchars($indicator['domain_name']) ?>\n";
        plan += "  الورشة المطلوبة: <?= htmlspecialchars($indicator['workshop']) ?>\n\n";
    <?php endforeach; ?>
    
    plan += "\nالتوصيات:\n";
    plan += "1. التواصل مع مراكز التدريب المتخصصة\n";
    plan += "2. الاستعانة بخبراء تعليميين من خارج المؤسسة\n";
    plan += "3. تطوير المعلمين الحاليين في هذه المجالات\n";
    plan += "4. تبادل الخبرات مع مؤسسات تعليمية أخرى";
    
    alert(plan);
}

// تحسين طباعة الصفحة
window.addEventListener('beforeprint', function() {
    document.querySelectorAll('button').forEach(btn => {
        btn.style.display = 'none';
    });
});

window.addEventListener('afterprint', function() {
    document.querySelectorAll('button').forEach(btn => {
        btn.style.display = '';
    });
});
</script>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?>
