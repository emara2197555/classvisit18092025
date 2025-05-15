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

// التحقق من وجود معلم محدد
$teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;

// الفصل الدراسي (اختياري)
$semester = isset($_GET['semester']) ? $_GET['semester'] : null;

// جلب قائمة المعلمين
$teachers = query("SELECT * FROM teachers ORDER BY name");

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
    
    // استعلام لجلب متوسط التقييمات لكل مؤشر لهذا المعلم
    $sql = "
        SELECT 
            ei.id AS indicator_id,
            ei.name AS indicator_name,
            ed.id AS domain_id,
            ed.name AS domain_name,
            AVG(ve.score) AS avg_score,
            (AVG(ve.score) * 25) AS percentage_score,
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
            v.teacher_id = ? {$semester_condition}
            AND ve.score > 0 -- استثناء المؤشرات غير المقاسة
        GROUP BY 
            ei.id, ei.name, ed.id, ed.name
        ORDER BY 
            ed.id, ei.id
    ";
    
    $indicators_data = query($sql, $params);
    
    // تجهيز بيانات الاحتياجات التدريبية
    foreach ($indicators_data as $indicator) {
        $needs_training = $indicator['avg_score'] < $threshold_score;
        
        $training_needs[] = [
            'indicator_id' => $indicator['indicator_id'],
            'indicator_name' => $indicator['indicator_name'],
            'domain_id' => $indicator['domain_id'],
            'domain_name' => $indicator['domain_name'],
            'avg_score' => $indicator['avg_score'],
            'percentage_score' => $indicator['percentage_score'],
            'visitor_types' => $indicator['visitor_types'],
            'visitor_types_count' => $indicator['visitor_types_count'],
            'needs_training' => $needs_training,
            'workshop' => isset($workshops_mapping[$indicator['indicator_id']]) ? $workshops_mapping[$indicator['indicator_id']] : ''
        ];
    }
    
    // إحصائية عامة
    $overall_stats = query_row("
        SELECT 
            AVG(ve.score) AS overall_avg,
            (AVG(ve.score) * 25) AS overall_percentage
        FROM 
            visit_evaluations ve
        JOIN 
            visits v ON ve.visit_id = v.id
        WHERE 
            v.teacher_id = ? {$semester_condition}
            AND ve.score > 0 -- استثناء المؤشرات غير المقاسة
    ", $params);
    
    // جلب متوسطات الدرجات لكل نوع زائر على حدة
    $visitor_types = [1 => 'المدير', 2 => 'النائب الأكاديمي', 3 => 'منسق المادة', 4 => 'موجه المادة'];
    $visitor_scores = [];
    
    foreach ($visitor_types as $visitor_type_id => $visitor_type_name) {
        $visitor_condition = "AND v.visitor_type_id = ?";
        $visitor_params = [$teacher_id, $visitor_type_id];
        
        // إضافة شرط الفصل الدراسي إذا تم تحديده
        if (!empty($semester_condition)) {
            $visitor_params = array_merge([$teacher_id, $visitor_type_id], array_slice($params, 1));
        }
        
        $visitor_sql = "
            SELECT 
                ei.id AS indicator_id,
                AVG(ve.score) AS avg_score
            FROM 
                visit_evaluations ve
            JOIN 
                visits v ON ve.visit_id = v.id
            JOIN 
                evaluation_indicators ei ON ve.indicator_id = ei.id
            WHERE 
                v.teacher_id = ? {$visitor_condition} {$semester_condition}
                AND ve.score > 0
            GROUP BY 
                ei.id
            ORDER BY 
                ei.id
        ";
        
        $visitor_data = query($visitor_sql, $visitor_params);
        
        foreach ($visitor_data as $data) {
            if (!isset($visitor_scores[$data['indicator_id']])) {
                $visitor_scores[$data['indicator_id']] = [];
            }
            $visitor_scores[$data['indicator_id']][$visitor_type_id] = $data['avg_score'];
        }
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
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold mb-4">الاحتياجات التدريبية للمعلمين</h1>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form action="" method="get" class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="teacher_id" class="block mb-1">المعلم</label>
                    <select id="teacher_id" name="teacher_id" class="w-full rounded border-gray-300" required>
                        <option value="">اختر المعلم</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= $teacher_id == $t['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="semester" class="block mb-1">الفصل الدراسي</label>
                    <select id="semester" name="semester" class="w-full rounded border-gray-300">
                        <option value="">جميع الفصول</option>
                        <option value="first" <?= $semester == 'first' ? 'selected' : '' ?>>الفصل الأول</option>
                        <option value="second" <?= $semester == 'second' ? 'selected' : '' ?>>الفصل الثاني</option>
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
                <h2 class="text-xl font-semibold mb-3">
                    الاحتياجات التدريبية للمعلم: <?= htmlspecialchars($teacher['name']) ?>
                </h2>
                
                <?php if (!empty($overall_stats)): ?>
                    <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                        <p class="text-md">
                            متوسط الدرجات العام: 
                            <strong><?= number_format($overall_stats['overall_avg'], 2) ?></strong>
                            (<?= number_format($overall_stats['overall_percentage'], 2) ?>%)
                        </p>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($required_workshops)): ?>
                    <h3 class="text-lg font-medium mb-3">الورش التدريبية المقترحة</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="py-3 px-4 border-b text-right">م</th>
                                    <th class="py-3 px-4 border-b text-right">المجال</th>
                                    <th class="py-3 px-4 border-b text-right">مؤشر الأداء</th>
                                    <th class="py-3 px-4 border-b text-right">الورشة</th>
                                    <th class="py-3 px-4 border-b text-right">الدرجة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $counter = 1; ?>
                                <?php foreach ($required_workshops as $id => $workshop): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-2 px-4 border-b"><?= $counter++ ?></td>
                                        <td class="py-2 px-4 border-b"><?= htmlspecialchars($workshop['domain']) ?></td>
                                        <td class="py-2 px-4 border-b"><?= htmlspecialchars($workshop['indicator']) ?></td>
                                        <td class="py-2 px-4 border-b font-medium"><?= htmlspecialchars($workshop['name']) ?></td>
                                        <td class="py-2 px-4 border-b text-center">
                                            <?php
                                            $score_class = '';
                                            if ($workshop['score'] < 50) {
                                                $score_class = 'text-red-700 bg-red-100';
                                            } elseif ($workshop['score'] < 60) {
                                                $score_class = 'text-orange-700 bg-orange-100';
                                            } elseif ($workshop['score'] < 70) {
                                                $score_class = 'text-yellow-700 bg-yellow-100';
                                            }
                                            ?>
                                            <span class="px-2 py-1 rounded <?= $score_class ?>">
                                                <?= number_format($workshop['score'], 2) ?>%
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
                    <h3 class="text-lg font-medium my-6">تفاصيل تقييم جميع المؤشرات</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="py-3 px-4 border-b text-right">المجال</th>
                                    <th class="py-3 px-4 border-b text-right">المؤشر</th>
                                    <th class="py-3 px-4 border-b text-center">متوسط الدرجة</th>
                                    <th class="py-3 px-4 border-b text-right">النسبة المئوية</th>
                                    <th class="py-3 px-4 border-b text-center">مدير</th>
                                    <th class="py-3 px-4 border-b text-center">أكاديمي</th>
                                    <th class="py-3 px-4 border-b text-center">موجه</th>
                                    <th class="py-3 px-4 border-b text-center">منسق</th>
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
                                    <tr class="bg-gray-50">
                                        <td colspan="8" class="py-2 px-4 border-b font-medium">
                                            <?= htmlspecialchars($need['domain_name']) ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                    <tr class="<?= $need['needs_training'] ? 'bg-red-50' : '' ?> hover:bg-gray-100">
                                        <td class="py-2 px-4 border-b"></td>
                                        <td class="py-2 px-4 border-b"><?= htmlspecialchars($need['indicator_name']) ?></td>
                                        <td class="py-2 px-4 border-b text-center">
                                            <?= number_format($need['avg_score'], 2) ?>
                                        </td>
                                        <td class="py-2 px-4 border-b">
                                            <?php
                                            $score_class = '';
                                            if ($need['percentage_score'] < 50) {
                                                $score_class = 'text-red-700';
                                            } elseif ($need['percentage_score'] < 60) {
                                                $score_class = 'text-orange-700';
                                            } elseif ($need['percentage_score'] < 70) {
                                                $score_class = 'text-yellow-700';
                                            } elseif ($need['percentage_score'] < 80) {
                                                $score_class = 'text-blue-600';
                                            } else {
                                                $score_class = 'text-green-700';
                                            }
                                            ?>
                                            <span class="<?= $score_class ?>">
                                                <?= number_format($need['percentage_score'], 2) ?>%
                                            </span>
                                        </td>
                                        <?php
                                        // عرض درجات أنواع الزائرين الأربعة
                                        $visitor_types = [1 => 'المدير', 2 => 'النائب الأكاديمي', 3 => 'منسق المادة', 4 => 'موجه المادة'];
                                        foreach ($visitor_types as $visitor_type_id => $visitor_type_name):
                                            $score = isset($visitor_scores[$need['indicator_id']][$visitor_type_id]) 
                                                ? $visitor_scores[$need['indicator_id']][$visitor_type_id] 
                                                : null;
                                        ?>
                                        <td class="py-2 px-4 border-b text-center">
                                            <?php if ($score !== null): ?>
                                                <?php 
                                                // تحويل الدرجة إلى نسبة مئوية
                                                $score_percentage = $score * 25;
                                                
                                                // تحديد لون النسبة حسب قيمتها
                                                $score_class = '';
                                                if ($score_percentage < 50) {
                                                    $score_class = 'text-red-700';
                                                } elseif ($score_percentage < 60) {
                                                    $score_class = 'text-orange-700';
                                                } elseif ($score_percentage < 70) {
                                                    $score_class = 'text-yellow-700';
                                                } elseif ($score_percentage < 80) {
                                                    $score_class = 'text-blue-600';
                                                } else {
                                                    $score_class = 'text-green-700';
                                                }
                                                ?>
                                                <span class="<?= $score_class ?>">
                                                    <?= number_format($score_percentage, 2) ?>%
                                                </span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
    // يمكن إضافة أي تفاعلات جافاسكريبت هنا إذا لزم الأمر
</script>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?> 