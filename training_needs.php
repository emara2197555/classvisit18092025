<?php
// بدء التخزين المؤقت للمخرجات
ob_start();

// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// تعيين عنوان الصفحة
$page_title = 'الاحتياجات التدريبية للمعلمين';

// تضمين ملف رأس الصفحة
require_once 'includes/header.php';

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

// المستويات المطلوبة لتحديد الاحتياج التدريبي
$threshold_score = 2.5; // إذا كان متوسط الدرجات أقل من هذا الرقم يكون هناك احتياج تدريبي

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

// التحقق من وجود معلم محدد
$teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;

// اختيار المادة
$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

// اختيار المدرسة
$school_id = isset($_GET['school_id']) ? (int)$_GET['school_id'] : 0;

// الفصل الدراسي (اختياري)
$semester = isset($_GET['semester']) ? $_GET['semester'] : null;

// جلب قائمة المدارس
$schools = query("SELECT * FROM schools ORDER BY name");

// جلب جميع المواد للاختيار الأولي (سيتم تحديثها بالجافاسكريبت)
$subjects = [];
if ($school_id > 0) {
    // جلب المواد حسب المدرسة المختارة
    $subjects = query("
        SELECT DISTINCT s.* 
        FROM subjects s
        JOIN teacher_subjects ts ON s.id = ts.subject_id
        JOIN teachers t ON ts.teacher_id = t.id
        WHERE t.school_id = ?
        ORDER BY s.name
    ", [$school_id]);
} else {
    // جلب كل المواد إذا لم تختر مدرسة
    $subjects = query("SELECT * FROM subjects ORDER BY name");
}

// جلب المعلمين حسب المدرسة والمادة المختارة
$teachers = [];
if ($school_id > 0) {
    if ($subject_id > 0) {
        // جلب المعلمين حسب المدرسة والمادة
        $teachers = query("
            SELECT t.* 
            FROM teachers t
            JOIN teacher_subjects ts ON t.id = ts.teacher_id
            WHERE t.job_title = 'معلم' 
            AND t.school_id = ? 
            AND ts.subject_id = ?
            ORDER BY t.name
        ", [$school_id, $subject_id]);
    } else {
        // جلب كل المعلمين في المدرسة
        $teachers = query("
            SELECT * FROM teachers 
            WHERE job_title = 'معلم' 
            AND school_id = ? 
            ORDER BY name
        ", [$school_id]);
    }
} else {
    // جلب كل المعلمين إذا لم تختر مدرسة
    $teachers = query("SELECT * FROM teachers WHERE job_title = 'معلم' ORDER BY name");
}

// جلب بيانات المعلم المحدد إن وجد
$teacher = null;
if ($teacher_id) {
    $teacher = query_row("SELECT * FROM teachers WHERE id = ?", [$teacher_id]);
}

// إذا كان هناك معلم محدد، نقوم بجلب بيانات احتياجاته التدريبية
$training_needs = [];
if ($teacher) {
    // بناء شرط الفصل الدراسي اختيارياً
    $semester_condition = '';
    $subject_condition = '';
    $params = [$teacher_id];
    
    if ($semester) {
        // مثال: إذا كنا نريد تحديد الفصل الدراسي بناء على التاريخ
        // هذا مجرد مثال ويمكن تعديله حسب هيكل البيانات الخاص بك
        if ($semester == 'first') {
            $semester_condition = "AND (MONTH(v.visit_date) BETWEEN 9 AND 12 OR MONTH(v.visit_date) BETWEEN 1 AND 2)";
        } else if ($semester == 'second') {
            $semester_condition = "AND MONTH(v.visit_date) BETWEEN 3 AND 8";
        }
    }
    
    // شرط المادة
    if ($subject_id > 0) {
        $subject_condition = "AND v.subject_id = ?";
        $params[] = $subject_id;
    }
    
    // استعلام لجلب متوسط التقييمات لكل مؤشر لهذا المعلم
    $sql = "
        SELECT 
            ei.id AS indicator_id,
            ei.name AS indicator_name,
            ed.id AS domain_id,
            ed.name AS domain_name,
            (SUM(ve.score) / COUNT(ve.score)) AS avg_score,
            (SUM(ve.score) / (COUNT(ve.score) * 3)) * 100 AS percentage_score,
            COUNT(DISTINCT v.visitor_type_id) AS visitor_types_count,
            GROUP_CONCAT(DISTINCT vt.name ORDER BY vt.id) AS visitor_types
        FROM 
            visit_evaluations ve
        JOIN 
            visits v ON ve.visit_id = v.id
        JOIN 
            evaluation_indicators ei ON ve.indicator_id = ei.id
        JOIN 
            evaluation_domains ed ON ei.domain_id = ed.id
        LEFT JOIN
            visitor_types vt ON v.visitor_type_id = vt.id
        WHERE 
            v.teacher_id = ? 
            {$semester_condition}
            {$subject_condition}
            AND v.academic_year_id = ?
            AND ve.score IS NOT NULL -- استثناء المؤشرات غير المقاسة
        GROUP BY 
            ei.id, ei.name, ed.id, ed.name
        ORDER BY 
            ed.id, ei.id
    ";
    
    // إضافة العام الأكاديمي للمعلمات
    $params[] = $academic_year_id;
    
    $indicators_data = query($sql, $params);
    
    // جلب متوسطات الدرجات لكل نوع زائر على حدة
    $visitor_types = [
        18 => 'مدير', 
        17 => 'النائب الأكاديمي', 
        15 => 'منسق المادة',
        16 => 'موجه المادة'
    ];
    $visitor_scores = [];
    
    // جلب بيانات تقييمات كل نوع زائر بشكل منفصل
    $indicator_visitor_sql = "
        WITH visit_scores AS (
            SELECT 
                ve.indicator_id,
                v.visitor_type_id,
                ve.score,
                v.id as visit_id
            FROM 
                visits v
                JOIN visit_evaluations ve ON v.id = ve.visit_id
            WHERE 
                v.teacher_id = ?
                AND v.academic_year_id = ?
                " . ($semester_condition ? $semester_condition : "") . "
                " . ($subject_condition ? $subject_condition : "") . "
                AND ve.score IS NOT NULL
        )
        SELECT 
            ei.id AS indicator_id,
            ei.name AS indicator_name,
            ed.id AS domain_id,
            ed.name AS domain_name,
            vs.visitor_type_id,
            ROUND(AVG(vs.score), 2) AS avg_score,
            COUNT(DISTINCT vs.visit_id) AS visit_count
        FROM 
            evaluation_indicators ei
            JOIN evaluation_domains ed ON ei.domain_id = ed.id
            LEFT JOIN visit_scores vs ON ei.id = vs.indicator_id
        WHERE 
            ei.id IN (
                SELECT DISTINCT indicator_id 
                FROM visit_scores
            )
        GROUP BY 
            ei.id, ei.name, ed.id, ed.name, vs.visitor_type_id
        ORDER BY 
            ed.id, ei.id, vs.visitor_type_id
    ";
    
    // إعداد المعلمات للاستعلام
    $visitor_params = [
        $teacher_id,
        $academic_year_id
    ];
    
    // إضافة معلمة المادة الدراسية إذا كانت محددة
    if ($subject_id > 0) {
        $visitor_params[] = $subject_id;
    }
    
    $visitor_overall_data = query($indicator_visitor_sql, $visitor_params);
    
    // تنظيم البيانات في مصفوفة
    foreach ($visitor_overall_data as $data) {
        if ($data['visitor_type_id'] === null) continue;
        
        $indicator_id = $data['indicator_id'];
        $visitor_type_id = $data['visitor_type_id'];
        
        if (!isset($visitor_scores[$indicator_id])) {
            $visitor_scores[$indicator_id] = [];
        }
        
        $visitor_scores[$indicator_id][$visitor_type_id] = [
            'avg_score' => $data['avg_score'],
            'visit_count' => $data['visit_count']
        ];
    }

    // تجهيز بيانات الاحتياجات التدريبية
    foreach ($indicators_data as $indicator) {
        // حساب متوسط الدرجات من تقييمات الزائرين
        $total_score = 0;
        $total_visits = 0;
        
        foreach ($visitor_types as $visitor_type_id => $visitor_type_name) {
            if (isset($visitor_scores[$indicator['indicator_id']][$visitor_type_id])) {
                $score_data = $visitor_scores[$indicator['indicator_id']][$visitor_type_id];
                $total_score += ($score_data['avg_score'] * $score_data['visit_count']);
                $total_visits += $score_data['visit_count'];
            }
        }
        
        $avg_score = $total_visits > 0 ? round($total_score / $total_visits, 2) : 0;
        $needs_training = $avg_score < $threshold_score;
        
        $training_needs[] = [
            'indicator_id' => $indicator['indicator_id'],
            'indicator_name' => $indicator['indicator_name'],
            'domain_id' => $indicator['domain_id'],
            'domain_name' => $indicator['domain_name'],
            'avg_score' => $avg_score,
            'percentage_score' => round($avg_score * (100/3), 2),
            'visitor_types' => $indicator['visitor_types'],
            'visitor_types_count' => $indicator['visitor_types_count'],
            'needs_training' => $needs_training,
            'workshop' => isset($workshops_mapping[$indicator['indicator_id']]) ? $workshops_mapping[$indicator['indicator_id']] : ''
        ];
    }
    
    // إحصائية عامة - مع التحقق من أن النتيجة ليست خالية
    $overall_stats = query_row("
        SELECT 
            (SUM(ve.score) / COUNT(ve.score)) AS overall_avg,
            (SUM(ve.score) / (COUNT(ve.score) * 3)) * 100 AS overall_percentage
        FROM 
            visit_evaluations ve
        JOIN 
            visits v ON ve.visit_id = v.id
        WHERE 
            v.teacher_id = ? 
            {$semester_condition}
            {$subject_condition}
            AND v.academic_year_id = ?
            AND ve.score IS NOT NULL -- استثناء المؤشرات غير المقاسة
    ", $params);
    
    // التحقق من وجود نتائج وتعيين قيم افتراضية إذا لم تكن هناك نتائج
    if (!$overall_stats || $overall_stats['overall_avg'] === null) {
        $overall_stats = [
            'overall_avg' => 0,
            'overall_percentage' => 0
        ];
    }
}

// فلترة الاحتياجات للحصول على الورش المطلوبة فقط
$required_workshops = [];
foreach ($training_needs as $need) {
    if ($need['needs_training'] && !empty($need['workshop'])) {
        $required_workshops[$need['indicator_id']] = [
            'name' => $need['workshop'],
            'score' => $need['percentage_score'],
            'indicator' => $need['indicator_name'],
            'domain' => $need['domain_name']
        ];
    }
}

// ترتيب الورش حسب الدرجة (تصاعدي)
usort($required_workshops, function($a, $b) {
    return $a['score'] <=> $b['score'];
});

// جلب قائمة الأعوام الأكاديمية
$academic_years_query = "SELECT * FROM academic_years ORDER BY id DESC";
$academic_years = query($academic_years_query);
?>

<div class="mb-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">الاحتياجات التدريبية للمعلمين</h1>
        <a href="expert_trainers.php" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors">
            <i class="fas fa-chalkboard-teacher mr-2"></i>
            المدربين المؤهلين
        </a>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form action="" method="get" class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <div>
                    <label for="school_id" class="block mb-1">المدرسة</label>
                    <select id="school_id" name="school_id" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200">
                        <option value="">اختر المدرسة</option>
                        <?php foreach ($schools as $school): ?>
                        <option value="<?= $school['id'] ?>" <?= $school_id == $school['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($school['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="academic_year_id" class="block mb-1">العام الدراسي</label>
                    <select id="academic_year_id" name="academic_year_id" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200">
                        <?php foreach ($academic_years as $year): ?>
                        <option value="<?= $year['id'] ?>" <?= $year['id'] == $academic_year_id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($year['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="semester" class="block mb-1">الفصل الدراسي</label>
                    <select id="semester" name="semester" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200">
                        <option value="">جميع الفصول</option>
                        <option value="first" <?= $semester == 'first' ? 'selected' : '' ?>>الفصل الأول</option>
                        <option value="second" <?= $semester == 'second' ? 'selected' : '' ?>>الفصل الثاني</option>
                    </select>
                </div>
                
                <div>
                    <label for="subject_id" class="block mb-1">المادة الدراسية</label>
                    <select id="subject_id" name="subject_id" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200">
                        <option value="0">جميع المواد</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?= $subject['id'] ?>" <?= $subject_id == $subject['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($subject['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="teacher_id" class="block mb-1">المعلم</label>
                    <select id="teacher_id" name="teacher_id" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200" required>
                        <option value="">اختر المعلم</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= $teacher_id == $t['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="bg-primary-600 text-white px-4 py-2 rounded-md hover:bg-primary-700 transition-colors">
                        عرض الاحتياجات التدريبية
                    </button>
                </div>
            </div>
        </form>
        
        <?php if ($teacher): ?>
            <div class="mb-6">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg p-6 text-white mb-6 shadow-lg">
                    <h2 class="text-xl font-semibold mb-3">
                        الاحتياجات التدريبية للمعلم: <?= htmlspecialchars($teacher['name']) ?>
                    </h2>
                    
                    <?php if (isset($overall_stats)): ?>
                        <div class="flex flex-col md:flex-row items-center justify-between">
                            <div class="text-lg">متوسط الدرجات العام:</div>
                            <div class="flex items-center">
                                <div class="text-4xl font-bold ml-3">
                                    <?= number_format((float)$overall_stats['overall_avg'], 2) ?>
                                </div>
                                <div class="text-xl opacity-80">
                                    (<?= number_format((float)$overall_stats['overall_percentage'], 2) ?>%)
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($required_workshops)): ?>
                    <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-8">
                        <div class="bg-gradient-to-r from-orange-600 to-orange-700 px-6 py-4">
                            <h3 class="text-xl font-bold text-white flex items-center">
                                <i class="fas fa-graduation-cap mr-3"></i>
                                الورش التدريبية المقترحة
                            </h3>
                            <p class="text-orange-100 text-sm mt-1">برامج التدريب المطلوبة لتحسين الأداء في المؤشرات الضعيفة</p>
                        </div>
                        
                        <div class="p-6">
                            <div class="overflow-x-auto">
                                <table class="w-full table-auto border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 min-w-[50px]">
                                                <i class="fas fa-list-ol mr-2 text-blue-600"></i>
                                                م
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 text-right font-semibold text-gray-700 min-w-[120px]">
                                                <i class="fas fa-layer-group mr-2 text-purple-600"></i>
                                                المجال
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 text-right font-semibold text-gray-700 min-w-[300px]">
                                                <i class="fas fa-list-ul mr-2 text-blue-600"></i>
                                                مؤشر الأداء
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 text-right font-semibold text-gray-700 min-w-[250px]">
                                                <i class="fas fa-chalkboard-teacher mr-2 text-green-600"></i>
                                                الورشة
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 min-w-[100px]">
                                                <i class="fas fa-star mr-2 text-yellow-600"></i>
                                                الدرجة
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                <?php $counter = 1; ?>
                                <?php foreach ($required_workshops as $id => $workshop): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="border border-gray-300 px-4 py-3 text-center">
                                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm font-bold">
                                                <?= $counter++ ?>
                                            </span>
                                        </td>
                                        <td class="border border-gray-300 px-4 py-3 text-right">
                                            <span class="font-medium text-gray-800"><?= htmlspecialchars($workshop['domain']) ?></span>
                                        </td>
                                        <td class="border border-gray-300 px-4 py-3 text-right">
                                            <div class="font-medium text-gray-800"><?= htmlspecialchars($workshop['indicator']) ?></div>
                                        </td>
                                        <td class="border border-gray-300 px-4 py-3 text-right">
                                            <div class="text-green-700 font-medium"><?= htmlspecialchars($workshop['name']) ?></div>
                                        </td>
                                        <td class="border border-gray-300 px-4 py-3 text-center">
                                            <?php
                                            $percentage = $workshop['score'];
                                            $color_class = $percentage >= 80 ? 'text-green-700 bg-green-100' : 
                                                          ($percentage >= 60 ? 'text-yellow-700 bg-yellow-100' : 'text-red-700 bg-red-100');
                                            ?>
                                            <div class="inline-block px-3 py-1 rounded-full font-bold <?= $color_class ?>">
                                                <?= number_format($percentage, 1) ?>%
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                        
                        <!-- إضافة مفتاح الألوان -->
                        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">مفتاح الألوان:</h4>
                            <div class="flex flex-wrap gap-4 text-sm">
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
                    </div>
                </div>
                <?php else: ?>
                    <?php if (!empty($training_needs)): ?>
                        <div class="bg-green-100 text-green-700 p-4 rounded">
                            لا توجد احتياجات تدريبية ملحة بناء على نتائج الزيارات الصفية.
                        </div>
                    <?php else: ?>
                        <div class="bg-yellow-100 text-yellow-700 p-4 rounded">
                            لا توجد زيارات صفية مسجلة لهذا المعلم بعد.
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if (!empty($training_needs)): ?>
                    <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-8">
                        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                            <h3 class="text-xl font-bold text-white flex items-center">
                                <i class="fas fa-chart-line mr-3"></i>
                                تفاصيل تقييم جميع المؤشرات
                            </h3>
                            <p class="text-blue-100 text-sm mt-1">تحليل شامل لأداء المعلم في جميع مؤشرات التقييم من قبل أنواع الزوار المختلفة</p>
                        </div>
                        
                        <div class="p-6">
                            <div class="overflow-x-auto">
                                <table class="w-full table-auto border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="border border-gray-300 px-4 py-3 text-right font-semibold text-gray-700 min-w-[150px]">
                                                <i class="fas fa-layer-group mr-2 text-purple-600"></i>
                                                المجال
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 text-right font-semibold text-gray-700 min-w-[300px]">
                                                <i class="fas fa-list-ul mr-2 text-blue-600"></i>
                                                المؤشر
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 min-w-[120px]">
                                                <i class="fas fa-calculator mr-2 text-orange-600"></i>
                                                متوسط الدرجة
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 min-w-[120px]">
                                                <i class="fas fa-percentage mr-2 text-red-600"></i>
                                                النسبة المئوية
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 min-w-[100px]">
                                                <i class="fas fa-crown mr-2 text-yellow-600"></i>
                                                مدير
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 min-w-[100px]">
                                                <i class="fas fa-user-tie mr-2 text-purple-600"></i>
                                                أكاديمي
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 min-w-[100px]">
                                                <i class="fas fa-user-graduate mr-2 text-green-600"></i>
                                                منسق
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 min-w-[100px]">
                                                <i class="fas fa-chalkboard-teacher mr-2 text-blue-600"></i>
                                                موجه
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                <?php 
                                $current_domain = null;
                                foreach ($training_needs as $need): 
                                    // إضافة صف للمجال عند التغيير
                                    if ($current_domain !== $need['domain_id']):
                                        $current_domain = $need['domain_id'];
                                ?>
                                    <tr class="bg-gray-100">
                                        <td colspan="8" class="border border-gray-300 px-4 py-3 font-bold text-gray-800 text-lg">
                                            <i class="fas fa-folder-open mr-2 text-blue-600"></i>
                                            <?= htmlspecialchars($need['domain_name']) ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                    <tr class="<?= $need['needs_training'] ? 'bg-red-50' : 'bg-white' ?> hover:bg-gray-50 transition-colors duration-200">
                                        <td class="border border-gray-300 px-4 py-3"></td>
                                        <td class="border border-gray-300 px-4 py-3 text-right">
                                            <div class="font-medium text-gray-800"><?= htmlspecialchars($need['indicator_name']) ?></div>
                                        </td>
                                        <td class="border border-gray-300 px-4 py-3 text-center">
                                            <span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full font-bold">
                                                <?= number_format((float)$need['avg_score'], 2) ?>
                                            </span>
                                        </td>
                                        <td class="border border-gray-300 px-4 py-3 text-center">
                                            <?php
                                            $percentage = $need['percentage_score'];
                                            $color_class = $percentage >= 80 ? 'text-green-700 bg-green-100' : 
                                                          ($percentage >= 60 ? 'text-yellow-700 bg-yellow-100' : 'text-red-700 bg-red-100');
                                            ?>
                                            <div class="inline-block px-3 py-1 rounded-full font-bold <?= $color_class ?>">
                                                <?= number_format($percentage, 1) ?>%
                                        </td>
                                        <?php
                                        // عرض درجات أنواع الزائرين الأربعة
                                        $visitor_types = [18 => 'مدير', 17 => 'النائب الأكاديمي', 15 => 'منسق المادة', 16 => 'موجه المادة'];
                                        foreach ($visitor_types as $visitor_type_id => $visitor_type_name):
                                            $visitor_data = isset($visitor_scores[$need['indicator_id']][$visitor_type_id]) 
                                                ? $visitor_scores[$need['indicator_id']][$visitor_type_id]
                                                : null;
                                            $score = $visitor_data ? $visitor_data['avg_score'] : null;
                                            $count = $visitor_data ? $visitor_data['visit_count'] : 0;
                                        ?>
                                        <td class="border border-gray-300 px-4 py-3 text-center">
                                            <?php if ($score !== null): ?>
                                                <?php 
                                                // تحويل الدرجة إلى نسبة مئوية
                                                $score_percentage = $score * (100/3);
                                                $color_class = $score_percentage >= 80 ? 'text-green-700 bg-green-100' : 
                                                              ($score_percentage >= 60 ? 'text-yellow-700 bg-yellow-100' : 'text-red-700 bg-red-100');
                                                ?>
                                                <div class="inline-block px-2 py-1 rounded-full font-bold text-xs <?= $color_class ?>">
                                                    <?= number_format($score_percentage, 1) ?>%
                                                </div>
                                                <div class="text-xs text-gray-500 mt-1">(<?= $count ?> زيارة)</div>
                                            <?php else: ?>
                                                <span class="text-gray-400 font-medium">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                        
                        <!-- إضافة مفتاح الألوان -->
                        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">مفتاح الألوان:</h4>
                            <div class="flex flex-wrap gap-4 text-sm">
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
                                <div class="flex items-center">
                                    <div class="w-4 h-4 bg-red-50 border border-red-200 rounded-full mr-2"></div>
                                    <span class="text-gray-600">خلفية حمراء: يحتاج تدريب فوري</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="bg-blue-100 text-blue-700 p-4 rounded">
                الرجاء اختيار معلم لعرض احتياجاته التدريبية.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // حفظ قيم الفلتر للاستخدام اللاحق
    document.getElementById('academic_year_id').addEventListener('change', function() {
        localStorage.setItem('training_academic_year_id', this.value);
    });
    
    // تحديث المواد عند اختيار مدرسة
    document.getElementById('school_id').addEventListener('change', function() {
        const schoolId = this.value;
        if (schoolId) {
            fetch(`api/get_school_subjects.php?school_id=${schoolId}`)
                .then(response => response.json())
                .then(data => {
                    updateSubjects(data);
                    // تفريغ قائمة المعلمين عند تغيير المدرسة
                    updateTeachers([]);
                })
                .catch(error => console.error('خطأ في جلب المواد:', error));
        } else {
            // إذا لم يتم اختيار مدرسة، جلب كل المواد
            fetch('api/get_all_subjects.php')
                .then(response => response.json())
                .then(data => {
                    updateSubjects(data);
                    // تفريغ قائمة المعلمين
                    updateTeachers([]);
                })
                .catch(error => console.error('خطأ في جلب المواد:', error));
        }
    });
    
    // تحديث المعلمين عند اختيار مادة
    document.getElementById('subject_id').addEventListener('change', function() {
        const subjectId = this.value;
        const schoolId = document.getElementById('school_id').value;
        
        if (schoolId && subjectId) {
            fetch(`api/get_teachers.php?school_id=${schoolId}&subject_id=${subjectId}`)
                .then(response => response.json())
                .then(data => {
                    updateTeachers(data);
                })
                .catch(error => console.error('خطأ في جلب المعلمين:', error));
        } else if (schoolId) {
            // إذا تم اختيار مدرسة فقط
            fetch(`api/get_teachers.php?school_id=${schoolId}`)
                .then(response => response.json())
                .then(data => {
                    updateTeachers(data);
                })
                .catch(error => console.error('خطأ في جلب المعلمين:', error));
        } else {
            // جلب كل المعلمين إذا لم يتم اختيار مدرسة أو مادة
            fetch('api/get_all_teachers.php')
                .then(response => response.json())
                .then(data => {
                    updateTeachers(data);
                })
                .catch(error => console.error('خطأ في جلب المعلمين:', error));
        }
    });
    
    // دالة لتحديث قائمة المواد
    function updateSubjects(subjects) {
        const subjectSelect = document.getElementById('subject_id');
        subjectSelect.innerHTML = '<option value="0">جميع المواد</option>';
        
        subjects.forEach(subject => {
            const option = document.createElement('option');
            option.value = subject.id;
            option.textContent = subject.name;
            subjectSelect.appendChild(option);
        });
    }
    
    // دالة لتحديث قائمة المعلمين
    function updateTeachers(teachers) {
        const teacherSelect = document.getElementById('teacher_id');
        teacherSelect.innerHTML = '<option value="">اختر المعلم</option>';
        
        teachers.forEach(teacher => {
            const option = document.createElement('option');
            option.value = teacher.id;
            option.textContent = teacher.name;
            teacherSelect.appendChild(option);
        });
    }
</script>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?> 
