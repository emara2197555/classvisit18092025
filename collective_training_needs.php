<?php
// بدء التخزين المؤقت للمخرجات
ob_start();

// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// تعيين عنوان الصفحة
$page_title = 'الاحتياجات التدريبية الجماعية';

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

// تعريف ورش تدريبية إضافية غير مرتبطة بمؤشرات محددة
$additional_workshops = [
    'اختيار المصادر الملائمة لتحقيق أهداف الدرس',
    'اختيار مصادر تعلم تشرك الطلبة بصورة تفاعلية',
    'استثمار الفرص المتاحة لتعزيز تعلم الطلبة من خلال الشراكات مع المجتمع',
    'استخدام استراتيجيات تعلم تثير التحدي وتلبي احتياجات الطلبة',
    'استخدام بيانات التقييم لبناء الخطط العلاجية و الإثرائية',
    'استخدام بيانات الطلبة للتخطيط للأنشطة',
    'استخدام مجموعة من الأنشطة تتوافق مع احتياجات الطلبة',
    'البحث العلمي لتنمية متعلم مستقل',
    'البيئة الصفية والتفكير الناقد',
    'التعرف على الاحتياجات المختلفة للطلبة حسب مراحل النمو',
    'الذكاءات المتعددة وأنماط التعلم لدى الطلاب',
    'المشاركة الفاعلة مع أولياء الأمور والزملاء لتحسين تحصيل الطلبة',
    'تأسيس قواعد صفية وإشراك الطلبة في تحقيقها',
    'تطوير القدرات الإبداعية والابتكارية لدى الطلبة',
    'تطوير فرص العمل مع الزملاء لدعم تعلم الطلبة',
    'تعزيز المواطنة',
    'تنفيذ وإثراء المنهج بمجموعة من الأنشطة الإضافية',
    'توظيف أنشطة تلبي احتياجات الطلبة بمن فيهم ذوي الاحتياجات الخاصة والموهوبين والمتفوقين',
    'توظيف مصادر تعلم تراعي الفروق الفردية لدى الطلبة',
    'توفير فرص تشجع الطلبة على الاحترام والتسامح',
    'توفير فرص للتكامل مع المواد الأخرى',
    'توفير فرص للعمل الفردي والجماعي داخل الصف',
    'قيادة الأنشطة البحثية لتحسين الممارسات المهنية على الصعيد الفردي والجماعي',
    'وضع أهداف تعلم تراعي الفروق الفردية للطلبة'
];

// المستويات المطلوبة لتحديد الاحتياج التدريبي
$threshold_score = 2.5; // إذا كان متوسط الدرجات أقل من هذا الرقم يكون هناك احتياج تدريبي

// الحصول على معرف العام الدراسي المحدد من جلسة المستخدم
if (!isset($_SESSION['selected_academic_year'])) {
    // ابحث عن العام الأكاديمي النشط
    $active_year = get_active_academic_year();
    $_SESSION['selected_academic_year'] = $active_year['id'] ?? null;
}

// جلب المعلومات من النموذج
$academic_year_id = isset($_GET['academic_year_id']) ? (int)$_GET['academic_year_id'] : $_SESSION['selected_academic_year'];
$school_id = isset($_GET['school_id']) ? (int)$_GET['school_id'] : 0;
$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$semester = isset($_GET['semester']) ? $_GET['semester'] : null;
$visitor_type_id = isset($_GET['visitor_type_id']) ? (int)$_GET['visitor_type_id'] : 0;

// جلب قائمة الأعوام الأكاديمية
$academic_years = query("SELECT * FROM academic_years ORDER BY is_current DESC, name DESC");

// جلب قائمة المدارس
$schools = query("SELECT * FROM schools ORDER BY name");

// جلب قائمة المواد حسب المدرسة
$subjects = [];
if ($school_id > 0) {
    $subjects = query("
        SELECT DISTINCT s.* 
        FROM subjects s
        JOIN teacher_subjects ts ON s.id = ts.subject_id
        JOIN teachers t ON ts.teacher_id = t.id
        WHERE t.school_id = ?
        ORDER BY s.name
    ", [$school_id]);
} else {
$subjects = query("SELECT * FROM subjects ORDER BY name");
}

// جلب قائمة أنواع الزائرين
$visitor_types = query("SELECT * FROM visitor_types ORDER BY id");

// إذا تم اختيار مادة محددة
$teachers_data = [];
$collective_needs = [];

if ($subject_id) {
    // بناء شرط الفصل الدراسي (اختياري)
    $semester_condition = '';
    $params = [$subject_id];
    
    if ($semester) {
        if ($semester == 'first') {
            $semester_condition = "AND (MONTH(v.visit_date) BETWEEN 9 AND 12 OR MONTH(v.visit_date) BETWEEN 1 AND 2)";
        } else if ($semester == 'second') {
            $semester_condition = "AND MONTH(v.visit_date) BETWEEN 3 AND 8";
        }
    }
    
    // بناء شرط نوع الزائر (اختياري)
    $visitor_condition = '';
    if ($visitor_type_id) {
        $visitor_condition = "AND v.visitor_type_id = ?";
        $params[] = $visitor_type_id;
    }
    
    // جلب قائمة المعلمين للمادة المحددة
    $teachers_sql = "
        SELECT DISTINCT 
            t.id, 
            t.name,
            t.personal_id
        FROM 
            teacher_subjects ts
        JOIN 
            teachers t ON ts.teacher_id = t.id
        JOIN 
            visits v ON v.teacher_id = t.id
        WHERE 
            ts.subject_id = ? {$visitor_condition} {$semester_condition}
        ORDER BY 
            t.name
    ";
    
    $teachers = query($teachers_sql, $params);
    
    // إعداد استعلام لجلب متوسط درجات المؤشرات لجميع المعلمين في المادة المحددة
    $indicators_sql = "
        SELECT 
            ei.id AS indicator_id,
            ei.name AS indicator_name,
            ed.id AS domain_id,
            ed.name AS domain_name,
            AVG(ve.score) AS avg_score,
            (AVG(ve.score) * 25) AS percentage_score
        FROM 
            visit_evaluations ve
        JOIN 
            visits v ON ve.visit_id = v.id
        JOIN 
            evaluation_indicators ei ON ve.indicator_id = ei.id
        JOIN 
            evaluation_domains ed ON ei.domain_id = ed.id
        JOIN 
            teacher_subjects ts ON ts.teacher_id = v.teacher_id
        WHERE 
            ts.subject_id = ? {$visitor_condition} {$semester_condition}
            AND ve.score > 0 -- استثناء المؤشرات غير المقاسة
        GROUP BY 
            ei.id, ei.name, ed.id, ed.name
        ORDER BY 
            avg_score ASC
    ";
    
    $indicators_data = query($indicators_sql, $params);
    
    // تجهيز بيانات الاحتياجات التدريبية المشتركة
    foreach ($indicators_data as $indicator) {
        $needs_training = $indicator['avg_score'] < $threshold_score;
        
        if ($needs_training && isset($workshops_mapping[$indicator['indicator_id']])) {
            $collective_needs[$indicator['indicator_id']] = [
                'workshop' => $workshops_mapping[$indicator['indicator_id']],
                'indicator_name' => $indicator['indicator_name'],
                'domain_name' => $indicator['domain_name'],
                'avg_score' => $indicator['avg_score'],
                'percentage_score' => $indicator['percentage_score']
            ];
        }
    }
    
    // جمع بيانات لكل معلم
    foreach ($teachers as $teacher) {
        // جلب بيانات المؤشرات لهذا المعلم
        $teacher_indicators = get_teacher_weakest_indicators($teacher['id'], $threshold_score);
        
        $weakest_areas = [];
        foreach ($teacher_indicators as $indicator) {
            if (isset($workshops_mapping[$indicator['indicator_id']])) {
                $weakest_areas[] = [
                    'indicator_id' => $indicator['indicator_id'],
                    'indicator_name' => $indicator['indicator_name'],
                    'workshop' => $workshops_mapping[$indicator['indicator_id']],
                    'score' => $indicator['avg_score'],
                    'percentage' => $indicator['percentage_score']
                ];
            }
        }
        
        // إذا كان لدى المعلم نقاط ضعف، قم بإضافته للقائمة
        if (!empty($weakest_areas)) {
            $teachers_data[$teacher['id']] = [
                'id' => $teacher['id'],
                'name' => $teacher['name'],
                'personal_id' => $teacher['personal_id'],
                'weakest_areas' => $weakest_areas
            ];
        }
    }
    
    // جلب البيانات لكل نوع زائر ومقارنتها
    $visitor_metrics = [];
    
    foreach ($visitor_types as $visitor_type) {
        $metrics = [];
        // جلب متوسط درجات كل مؤشر لهذا النوع من الزائرين
        $visitor_sql = "
            SELECT 
                ei.id AS indicator_id,
                ei.name AS indicator_name,
                AVG(ve.score) AS avg_score,
                (AVG(ve.score) * 25) AS percentage_score,
                COUNT(DISTINCT v.id) as visit_count
            FROM 
                visit_evaluations ve
            JOIN 
                visits v ON ve.visit_id = v.id
            JOIN 
                evaluation_indicators ei ON ve.indicator_id = ei.id
            JOIN
                teacher_subjects ts ON ts.teacher_id = v.teacher_id
            WHERE 
                ts.subject_id = ?
                AND v.visitor_type_id = ? {$semester_condition}
                AND ve.score > 0 -- استثناء المؤشرات غير المقاسة
            GROUP BY 
                ei.id, ei.name
            ORDER BY 
                ei.id
        ";
        
        $visitor_params = [$subject_id, $visitor_type['id']];
        $visitor_data = query($visitor_sql, $visitor_params);
        
        // تحويل البيانات إلى مصفوفة مفتاحها هو رقم المؤشر
        foreach ($visitor_data as $item) {
            $metrics[$item['indicator_id']] = [
                'avg_score' => $item['avg_score'],
                'percentage_score' => $item['percentage_score'],
                'visit_count' => $item['visit_count']
            ];
        }
        
        if (!empty($metrics)) {
            $visitor_metrics[$visitor_type['id']] = $metrics;
        }
    }
}
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold mb-4">الاحتياجات التدريبية الجماعية</h1>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form action="" method="get" class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-4">
                <!-- العام الأكاديمي -->
                <div>
                    <label for="academic_year_id" class="block mb-1">العام الأكاديمي</label>
                    <select id="academic_year_id" name="academic_year_id" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200" required>
                        <?php foreach ($academic_years as $year): ?>
                            <option value="<?= $year['id'] ?>" <?= $academic_year_id == $year['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($year['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- المدرسة -->
                <div>
                    <label for="school_id" class="block mb-1">المدرسة</label>
                    <select id="school_id" name="school_id" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200">
                        <option value="">اختر المدرسة...</option>
                        <?php foreach ($schools as $school): ?>
                            <option value="<?= $school['id'] ?>" <?= $school_id == $school['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($school['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- المادة -->
                <div>
                    <label for="subject_id" class="block mb-1">المادة</label>
                    <select id="subject_id" name="subject_id" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200" required>
                        <option value="">اختر المادة...</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?= $subject['id'] ?>" <?= $subject_id == $subject['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($subject['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- نوع الزائر -->
                <div>
                    <label for="visitor_type_id" class="block mb-1">نوع الزائر</label>
                    <select id="visitor_type_id" name="visitor_type_id" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200">
                        <option value="">جميع الزائرين</option>
                        <?php foreach ($visitor_types as $type): ?>
                            <option value="<?= $type['id'] ?>" <?= $visitor_type_id == $type['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- الفصل الدراسي -->
                <div>
                    <label for="semester" class="block mb-1">الفصل الدراسي</label>
                    <select id="semester" name="semester" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200">
                        <option value="">جميع الفصول</option>
                        <option value="first" <?= $semester == 'first' ? 'selected' : '' ?>>الفصل الأول</option>
                        <option value="second" <?= $semester == 'second' ? 'selected' : '' ?>>الفصل الثاني</option>
                    </select>
                </div>
                
                <!-- زر البحث -->
                <div class="flex items-end">
                    <button type="submit" class="bg-primary-600 text-white px-4 py-2 rounded-md hover:bg-primary-700 transition-colors w-full">
                        عرض الاحتياجات التدريبية
                    </button>
                </div>
            </div>
        </form>
        
        <?php if ($subject_id): ?>
            <?php 
            // الحصول على اسم المادة المختارة
            $subject_name = 'كل المواد';
            if ($subject_id > 0) {
                foreach ($subjects as $sub) {
                    if ($sub['id'] == $subject_id) {
                        $subject_name = $sub['name'];
                        break;
                    }
                }
            }
            
            // الحصول على اسم نوع الزائر
            $visitor_type_name = 'جميع الزائرين';
            if ($visitor_type_id) {
                foreach ($visitor_types as $type) {
                    if ($type['id'] == $visitor_type_id) {
                        $visitor_type_name = $type['name'];
                        break;
                    }
                }
            }
            
            // الفصل الدراسي
            $semester_name = 'جميع الفصول';
            if ($semester == 'first') {
                $semester_name = 'الفصل الأول';
            } else if ($semester == 'second') {
                $semester_name = 'الفصل الثاني';
            }
            ?>
            
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-3">
                    الاحتياجات التدريبية لمعلمي مادة: <?= htmlspecialchars($subject_name) ?>
                </h2>
                <p class="text-gray-600 mb-4">
                    <span class="font-medium">نوع الزائر:</span> <?= htmlspecialchars($visitor_type_name) ?> | 
                    <span class="font-medium">الفصل الدراسي:</span> <?= htmlspecialchars($semester_name) ?>
                </p>
                
                <?php if (!empty($collective_needs)): ?>
                    <div class="mb-8">
                        <h3 class="text-lg font-medium mb-4">أبرز الاحتياجات التدريبية المشتركة</h3>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white border border-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="py-3 px-4 border-b text-right">م</th>
                                        <th class="py-3 px-4 border-b text-right">المجال</th>
                                        <th class="py-3 px-4 border-b text-right">المؤشر</th>
                                        <th class="py-3 px-4 border-b text-right">الورشة المقترحة</th>
                                        <th class="py-3 px-4 border-b text-center">متوسط الدرجة</th>
                                        <th class="py-3 px-4 border-b text-center">النسبة المئوية</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $counter = 1; ?>
                                    <?php foreach ($collective_needs as $id => $need): ?>
                                        <tr>
                                            <td class="py-2 px-4 border-b"><?= $counter++ ?></td>
                                            <td class="py-2 px-4 border-b"><?= htmlspecialchars($need['domain_name']) ?></td>
                                            <td class="py-2 px-4 border-b"><?= htmlspecialchars($need['indicator_name']) ?></td>
                                            <td class="py-2 px-4 border-b font-medium"><?= htmlspecialchars($need['workshop']) ?></td>
                                            <td class="py-2 px-4 border-b text-center"><?= number_format($need['avg_score'], 2) ?></td>
                                            <td class="py-2 px-4 border-b text-center">
                                                <?php
                                                $score_class = '';
                                                if ($need['percentage_score'] < 50) {
                                                    $score_class = 'text-red-700 bg-red-100';
                                                } elseif ($need['percentage_score'] < 60) {
                                                    $score_class = 'text-orange-700 bg-orange-100';
                                                } elseif ($need['percentage_score'] < 70) {
                                                    $score_class = 'text-yellow-700 bg-yellow-100';
                                                }
                                                ?>
                                                <span class="px-2 py-1 rounded <?= $score_class ?>">
                                                    <?= number_format($need['percentage_score'], 2) ?>%
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php elseif (!empty($indicators_data)): ?>
                    <div class="bg-green-100 text-green-700 p-4 rounded mb-6">
                        لا توجد احتياجات تدريبية ملحة مشتركة. مستوى الأداء جيد!
                    </div>
                <?php else: ?>
                    <div class="bg-yellow-100 text-yellow-700 p-4 rounded mb-6">
                        لا توجد بيانات كافية لتحليل الاحتياجات التدريبية المشتركة. تأكد من وجود زيارات صفية مسجلة.
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($visitor_metrics) && count($visitor_metrics) > 1): ?>
                    <div class="mb-8">
                        <h3 class="text-lg font-medium mb-4">مقارنة التقييمات حسب نوع الزائر</h3>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white border border-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="py-3 px-4 border-b text-right">المؤشر</th>
                                        <th class="py-3 px-4 border-b text-center">منسق المادة</th>
                                        <th class="py-3 px-4 border-b text-center">موجه المادة</th>
                                        <th class="py-3 px-4 border-b text-center">النائب الأكاديمي</th>
                                        <th class="py-3 px-4 border-b text-center">مدير المدرسة</th>
                                        <th class="py-3 px-4 border-b text-center">متوسط الدرجة</th>
                                        <th class="py-3 px-4 border-b text-center">النسبة المئوية</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // جلب أسماء المؤشرات
                                    $indicators_names = [];
                                    $indicators_list = query("SELECT id, name FROM evaluation_indicators ORDER BY id");
                                    foreach ($indicators_list as $ind) {
                                        $indicators_names[$ind['id']] = $ind['name'];
                                    }
                                    
                                    // عرض البيانات لكل مؤشر
                                    foreach ($indicators_names as $indicator_id => $indicator_name):
                                        // حساب المتوسط الكلي للمؤشر
                                        $total_score = 0;
                                        $total_visits = 0;
                                        $visitor_count = 0;
                                        
                                        foreach ([15, 16, 17, 18] as $visitor_id) {
                                            if (isset($visitor_metrics[$visitor_id][$indicator_id])) {
                                                $score = $visitor_metrics[$visitor_id][$indicator_id];
                                                $total_score += ($score['avg_score'] * $score['visit_count']);
                                                $total_visits += $score['visit_count'];
                                                $visitor_count++;
                                            }
                                        }
                                        
                                        $avg_score = $total_visits > 0 ? $total_score / $total_visits : 0;
                                        $percentage_score = $avg_score * 25;
                                    ?>
                                        <tr>
                                            <td class="py-2 px-4 border-b"><?= htmlspecialchars($indicator_name) ?></td>
                                            <!-- منسق المادة -->
                                            <td class="py-2 px-4 border-b text-center">
                                                <?php
                                                if (isset($visitor_metrics[15][$indicator_id])) {
                                                    $score = $visitor_metrics[15][$indicator_id];
                                                    echo number_format($score['percentage_score'], 1) . '%';
                                                    echo '<br><small class="text-muted">(' . $score['visit_count'] . ' زيارة)</small>';
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <!-- موجه المادة -->
                                            <td class="py-2 px-4 border-b text-center">
                                                <?php
                                                if (isset($visitor_metrics[16][$indicator_id])) {
                                                    $score = $visitor_metrics[16][$indicator_id];
                                                    echo number_format($score['percentage_score'], 1) . '%';
                                                    echo '<br><small class="text-muted">(' . $score['visit_count'] . ' زيارة)</small>';
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <!-- النائب الأكاديمي -->
                                            <td class="py-2 px-4 border-b text-center">
                                                <?php
                                                if (isset($visitor_metrics[17][$indicator_id])) {
                                                    $score = $visitor_metrics[17][$indicator_id];
                                                    echo number_format($score['percentage_score'], 1) . '%';
                                                    echo '<br><small class="text-muted">(' . $score['visit_count'] . ' زيارة)</small>';
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <!-- مدير المدرسة -->
                                            <td class="py-2 px-4 border-b text-center">
                                                <?php
                                                if (isset($visitor_metrics[18][$indicator_id])) {
                                                    $score = $visitor_metrics[18][$indicator_id];
                                                    echo number_format($score['percentage_score'], 1) . '%';
                                                    echo '<br><small class="text-muted">(' . $score['visit_count'] . ' زيارة)</small>';
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <!-- متوسط الدرجة -->
                                            <td class="py-2 px-4 border-b text-center">
                                                <?php
                                                if ($total_visits > 0) {
                                                    echo number_format($avg_score, 2);
                                                    echo '<br><small class="text-muted">(' . $total_visits . ' زيارة)</small>';
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <!-- النسبة المئوية -->
                                                <td class="py-2 px-4 border-b text-center">
                                                <?php
                                                if ($total_visits > 0) {
                                                        $score_class = '';
                                                    if ($percentage_score < 50) {
                                                            $score_class = 'text-red-700';
                                                    } elseif ($percentage_score < 60) {
                                                            $score_class = 'text-orange-700';
                                                    } elseif ($percentage_score < 70) {
                                                            $score_class = 'text-yellow-700';
                                                    } elseif ($percentage_score < 80) {
                                                            $score_class = 'text-blue-700';
                                                        } else {
                                                            $score_class = 'text-green-700';
                                                    }
                                                    echo '<span class="' . $score_class . '">';
                                                    echo number_format($percentage_score, 1) . '%';
                                                    echo '</span>';
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                                </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($teachers_data)): ?>
                    <div class="mb-8">
                        <h3 class="text-lg font-medium mb-4">احتياجات المعلمين الفردية</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php foreach ($teachers_data as $teacher): ?>
                                <div class="border rounded-lg p-4">
                                    <h4 class="text-md font-medium mb-2"><?= htmlspecialchars($teacher['name']) ?></h4>
                                    <p class="text-sm text-gray-600 mb-3">رقم الموظف: <?= htmlspecialchars($teacher['personal_id']) ?></p>
                                    
                                    <div class="mt-2">
                                        <h5 class="text-sm font-medium mb-2">النقاط التي تحتاج إلى تحسين:</h5>
                                        <ul class="list-disc list-inside space-y-1 text-sm">
                                            <?php foreach ($teacher['weakest_areas'] as $area): ?>
                                                <li>
                                                    <span class="text-gray-700"><?= htmlspecialchars($area['indicator_name']) ?></span> - 
                                                    <span class="text-primary-600 font-medium"><?= htmlspecialchars($area['workshop']) ?></span>
                                                    <span class="text-gray-500">(<?= number_format($area['percentage'], 2) ?>%)</span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    
                                    <div class="mt-3 text-right">
                                        <a href="training_needs.php?teacher_id=<?= $teacher['id'] ?>" class="text-primary-600 hover:text-primary-700 text-sm">
                                            عرض التفاصيل الكاملة &larr;
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="bg-blue-100 text-blue-700 p-4 rounded">
                الرجاء اختيار المادة لعرض الاحتياجات التدريبية للمعلمين.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- عرض جدول المقارنة -->
<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>المؤشر</th>
                <th>منسق المادة</th>
                <th>موجه المادة</th>
                <th>النائب الأكاديمي</th>
                <th>مدير المدرسة</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($collective_needs as $indicator_id => $need): ?>
                <tr>
                    <td>
                        <?php echo htmlspecialchars($need['indicator_name']); ?>
                    </td>
                    <!-- منسق المادة -->
                    <td>
                        <?php
                        if (isset($visitor_metrics[15][$indicator_id])) {
                            $score = $visitor_metrics[15][$indicator_id];
                            echo number_format($score['percentage_score'], 1) . '%';
                            echo '<br><small class="text-muted">(' . $score['visit_count'] . ' زيارة)</small>';
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                    <!-- موجه المادة -->
                    <td>
                        <?php
                        if (isset($visitor_metrics[16][$indicator_id])) {
                            $score = $visitor_metrics[16][$indicator_id];
                            echo number_format($score['percentage_score'], 1) . '%';
                            echo '<br><small class="text-muted">(' . $score['visit_count'] . ' زيارة)</small>';
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                    <!-- النائب الأكاديمي -->
                    <td>
                        <?php
                        if (isset($visitor_metrics[17][$indicator_id])) {
                            $score = $visitor_metrics[17][$indicator_id];
                            echo number_format($score['percentage_score'], 1) . '%';
                            echo '<br><small class="text-muted">(' . $score['visit_count'] . ' زيارة)</small>';
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                    <!-- مدير المدرسة -->
                    <td>
                        <?php
                        if (isset($visitor_metrics[18][$indicator_id])) {
                            $score = $visitor_metrics[18][$indicator_id];
                            echo number_format($score['percentage_score'], 1) . '%';
                            echo '<br><small class="text-muted">(' . $score['visit_count'] . ' زيارة)</small>';
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- إضافة سكريبت JavaScript في نهاية الملف -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const schoolSelect = document.getElementById('school_id');
    const subjectSelect = document.getElementById('subject_id');
    
    // دالة لتحديث قائمة المواد
    function updateSubjects(schoolId) {
        // تفريغ قائمة المواد
        subjectSelect.innerHTML = '<option value="">اختر المادة...</option>';
        
        if (schoolId) {
            // جلب المواد من الخادم
            fetch(`api/get_subjects.php?school_id=${schoolId}`)
                .then(response => response.json())
                .then(subjects => {
                    subjects.forEach(subject => {
                        const option = document.createElement('option');
                        option.value = subject.id;
                        option.textContent = subject.name;
                        subjectSelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Error:', error));
        }
    }
    
    // تحديث المواد عند تغيير المدرسة
    schoolSelect.addEventListener('change', function() {
        updateSubjects(this.value);
    });
});
</script>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?> 