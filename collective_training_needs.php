<?php
// استخدام القوانين الموحدة لنظام الزيارات الصفية
require_once 'visit_rules.php';

// بدء التخزين المؤقت للمخرجات
ob_start();

// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';
require_once 'includes/auth_functions.php';

// حماية الصفحة
protect_page();

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

// المستويات المطلوبة لتحديد الاحتياج التدريبي (استخدام القوانين الموحدة)
$threshold_score = TRAINING_NEEDS_THRESHOLD; // إذا كان متوسط الدرجات أقل من هذا الرقم يكون هناك احتياج تدريبي

// الحصول على معرف العام الدراسي المحدد من جلسة المستخدم
if (!isset($_SESSION['selected_academic_year'])) {
    // ابحث عن العام الأكاديمي النشط
    $active_year = get_active_academic_year();
    $_SESSION['selected_academic_year'] = $active_year['id'] ?? null;
}

// جلب المعلومات من النموذج
$academic_year_id = isset($_GET['academic_year_id']) ? (int)$_GET['academic_year_id'] : $_SESSION['selected_academic_year'];

// تحديد البيانات بناءً على دور المستخدم
$user_role = $_SESSION['role_name'] ?? '';
$is_coordinator = ($user_role === 'Subject Coordinator');
$coordinator_subject_id = null;
$coordinator_school_id = null;

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
}

$school_id = $is_coordinator ? $coordinator_school_id : (isset($_GET['school_id']) ? (int)$_GET['school_id'] : 0);
$subject_id = $is_coordinator ? $coordinator_subject_id : (isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0);

// للمنسق: تحديد المادة تلقائياً إذا لم تكن محددة في الرابط
if ($is_coordinator && $coordinator_subject_id && !isset($_GET['subject_id'])) {
    $subject_id = $coordinator_subject_id;
    $school_id = $coordinator_school_id;
}

$semester = isset($_GET['semester']) ? $_GET['semester'] : null;
$visitor_type_id = isset($_GET['visitor_type_id']) ? (int)$_GET['visitor_type_id'] : 0;

// جلب قائمة الأعوام الأكاديمية
$academic_years = query("SELECT * FROM academic_years ORDER BY is_current DESC, name DESC");

// جلب قائمة المدارس (للمديرين فقط)
$schools = [];
if (!$is_coordinator) {
    $schools = query("SELECT * FROM schools ORDER BY name");
}

// جلب قائمة المواد حسب المدرسة
$subjects = [];
if ($is_coordinator) {
    // للمنسق: جلب مادته فقط
    $subjects = query("
        SELECT s.* 
        FROM subjects s
        WHERE s.id = ?
        ORDER BY s.name
    ", [$coordinator_subject_id]);
} elseif ($school_id > 0) {
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

// إذا تم اختيار مادة محددة أو إذا كان المستخدم منسقاً
$teachers_data = [];
$collective_needs = [];

// للمنسق: استخدام مادته مباشرة
if ($is_coordinator && $coordinator_subject_id) {
    $subject_id = $coordinator_subject_id;
}

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
    
    // بناء شرط المدرسة للمنسق
    $school_condition = '';
    if ($is_coordinator && $coordinator_school_id) {
        $school_condition = "AND t.school_id = ?";
        $params[] = $coordinator_school_id;
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
            ts.subject_id = ? {$visitor_condition} {$semester_condition} {$school_condition}
        ORDER BY 
            t.name
    ";
    
    $teachers = query($teachers_sql, $params);
    
    // إعداد استعلام لجلب متوسط درجات المؤشرات لجميع المعلمين في المادة المحددة
    // إعداد المعاملات للاستعلام الثاني
    $indicators_params = [$subject_id];
    if ($visitor_type_id) {
        $indicators_params[] = $visitor_type_id;
    }
    if ($is_coordinator && $coordinator_school_id) {
        $indicators_params[] = $coordinator_school_id;
    }
    
    $indicators_sql = "
        SELECT 
            ei.id AS indicator_id,
            ei.name AS indicator_name,
            ed.id AS domain_id,
            ed.name AS domain_name,
            AVG(ve.score) AS avg_score,
            (AVG(ve.score) / " . MAX_INDICATOR_SCORE . ") * 100 AS percentage_score
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
        JOIN 
            teachers t ON v.teacher_id = t.id
        WHERE 
            ts.subject_id = ? {$visitor_condition} {$semester_condition} {$school_condition}
            AND ve.score > 0 -- استثناء المؤشرات غير المقاسة
        GROUP BY 
            ei.id, ei.name, ed.id, ed.name
        ORDER BY 
            avg_score ASC
    ";
    
    $indicators_data = query($indicators_sql, $indicators_params);
    
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
                (AVG(ve.score) / " . MAX_INDICATOR_SCORE . ") * 100 AS percentage_score,
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
    <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
        <div class="bg-yellow-100 p-4 rounded mb-4">
            <h4>معلومات التشخيص:</h4>
            <p>المستخدم: <?= $_SESSION['username'] ?? 'غير محدد' ?></p>
            <p>الدور: <?= $_SESSION['role_name'] ?? 'غير محدد' ?></p>
            <p>هل منسق؟ <?= $is_coordinator ? 'نعم' : 'لا' ?></p>
            <p>معرف المادة: <?= $coordinator_subject_id ?? 'غير محدد' ?></p>
            <p>معرف المدرسة: <?= $coordinator_school_id ?? 'غير محدد' ?></p>
            <p>المادة المحددة حالياً: <?= $subject_id ?? 'غير محدد' ?></p>
            <p>عدد المواد المتاحة: <?= count($subjects) ?></p>
        </div>
    <?php endif; ?>
    
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">الاحتياجات التدريبية الجماعية</h1>
        <a href="expert_trainers.php" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors">
            <i class="fas fa-chalkboard-teacher mr-2"></i>
            المدربين المؤهلين
        </a>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <?php if ($is_coordinator): ?>
            <div class="mb-4 p-3 bg-blue-100 text-blue-800 rounded">
                <strong>مرحباً بك كمنسق مادة!</strong> 
                أنت تعرض البيانات الخاصة بمادتك فقط.
            </div>
        <?php endif; ?>
        
        <form action="" method="get" class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-<?= $is_coordinator ? '4' : '6' ?> gap-4 mb-4">
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

                <?php if (!$is_coordinator): ?>
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
                <?php else: ?>
                    <!-- عرض المادة للمنسق كمعلومة فقط -->
                    <div>
                        <label class="block mb-1">المادة</label>
                        <div class="w-full p-2 bg-gray-100 border border-gray-300 rounded-md">
                            <?php 
                            $coordinator_subject_name = 'غير محددة';
                            if ($coordinator_subject_id) {
                                foreach ($subjects as $subject) {
                                    if ($subject['id'] == $coordinator_subject_id) {
                                        $coordinator_subject_name = $subject['name'];
                                        break;
                                    }
                                }
                            }
                            echo htmlspecialchars($coordinator_subject_name);
                            ?>
                        </div>
                        <!-- حقول مخفية للمنسق -->
                        <input type="hidden" name="school_id" value="<?= $coordinator_school_id ?>">
                        <input type="hidden" name="subject_id" value="<?= $coordinator_subject_id ?>">
                    </div>
                <?php endif; ?>
                
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
                    <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-8">
                        <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                            <h3 class="text-xl font-bold text-white flex items-center">
                                <i class="fas fa-exclamation-triangle mr-3"></i>
                                أبرز الاحتياجات التدريبية المشتركة
                            </h3>
                            <p class="text-green-100 text-sm mt-1">المؤشرات التي تحتاج إلى تدريب مشترك لجميع المعلمين</p>
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
                                                المؤشر
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 text-right font-semibold text-gray-700 min-w-[250px]">
                                                <i class="fas fa-graduation-cap mr-2 text-green-600"></i>
                                                الورشة المقترحة
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 min-w-[120px]">
                                                <i class="fas fa-calculator mr-2 text-orange-600"></i>
                                                متوسط الدرجة
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 min-w-[120px]">
                                                <i class="fas fa-percentage mr-2 text-red-600"></i>
                                                النسبة المئوية
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php $counter = 1; ?>
                                    <?php foreach ($collective_needs as $id => $need): ?>
                                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                                            <td class="border border-gray-300 px-4 py-3 text-center font-medium">
                                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm font-bold">
                                                    <?= $counter++ ?>
                                                </span>
                                            </td>
                                            <td class="border border-gray-300 px-4 py-3 text-right">
                                                <span class="font-medium text-gray-800"><?= htmlspecialchars($need['domain_name']) ?></span>
                                            </td>
                                            <td class="border border-gray-300 px-4 py-3 text-right">
                                                <div class="font-medium text-gray-800"><?= htmlspecialchars($need['indicator_name']) ?></div>
                                            </td>
                                            <td class="border border-gray-300 px-4 py-3 text-right">
                                                <div class="text-blue-700 font-medium"><?= htmlspecialchars($need['workshop']) ?></div>
                                            </td>
                                            <td class="border border-gray-300 px-4 py-3 text-center">
                                                <span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full font-bold">
                                                    <?= number_format($need['avg_score'], 2) ?>
                                                </span>
                                            </td>
                                            <td class="border border-gray-300 px-4 py-3 text-center">
                                                <?php
                                                $percentage = $need['percentage_score'];
                                                $performance_level = getPerformanceLevel($percentage);
                                                ?>
                                                <div class="inline-block px-3 py-1 rounded-full font-bold <?= $performance_level['color_class'] ?>">
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
                                        <span class="text-gray-600">ممتاز (<?= EXCELLENT_THRESHOLD ?>% فأكثر)</span>
                                    </div>
                                    <div class="flex items-center">
                                        <div class="w-4 h-4 bg-blue-100 border border-blue-300 rounded-full mr-2"></div>
                                        <span class="text-gray-600">جيد جداً (<?= VERY_GOOD_THRESHOLD ?>% - <?= EXCELLENT_THRESHOLD - 0.1 ?>%)</span>
                                    </div>
                                    <div class="flex items-center">
                                        <div class="w-4 h-4 bg-yellow-100 border border-yellow-300 rounded-full mr-2"></div>
                                        <span class="text-gray-600">جيد (<?= GOOD_THRESHOLD ?>% - <?= VERY_GOOD_THRESHOLD - 0.1 ?>%)</span>
                                    </div>
                                    <div class="flex items-center">
                                        <div class="w-4 h-4 bg-orange-100 border border-orange-300 rounded-full mr-2"></div>
                                        <span class="text-gray-600">مقبول (<?= ACCEPTABLE_THRESHOLD ?>% - <?= GOOD_THRESHOLD - 0.1 ?>%)</span>
                                    </div>
                                    <div class="flex items-center">
                                        <div class="w-4 h-4 bg-red-100 border border-red-300 rounded-full mr-2"></div>
                                        <span class="text-gray-600">يحتاج تحسين (أقل من <?= ACCEPTABLE_THRESHOLD ?>%)</span>
                                    </div>
                                </div>
                            </div>
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
                    <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-8">
                        <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4">
                            <h3 class="text-xl font-bold text-white flex items-center">
                                <i class="fas fa-users-cog mr-3"></i>
                                مقارنة التقييمات حسب نوع الزائر
                            </h3>
                            <p class="text-purple-100 text-sm mt-1">مقارنة شاملة لتقييمات المؤشرات من قبل أنواع الزوار المختلفة</p>
                        </div>
                        
                        <div class="p-6">
                            <div class="overflow-x-auto">
                                <table class="w-full table-auto border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="border border-gray-300 px-4 py-3 text-right font-semibold text-gray-700 min-w-[300px]">
                                                <i class="fas fa-list-ul mr-2 text-blue-600"></i>
                                                المؤشر
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 min-w-[140px]">
                                                <i class="fas fa-user-graduate mr-2 text-green-600"></i>
                                                منسق المادة
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 min-w-[140px]">
                                                <i class="fas fa-chalkboard-teacher mr-2 text-blue-600"></i>
                                                موجه المادة
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 min-w-[140px]">
                                                <i class="fas fa-user-tie mr-2 text-purple-600"></i>
                                                النائب الأكاديمي
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 min-w-[140px]">
                                                <i class="fas fa-crown mr-2 text-yellow-600"></i>
                                                مدير المدرسة
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 min-w-[120px]">
                                                <i class="fas fa-calculator mr-2 text-orange-600"></i>
                                                متوسط الدرجة
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 min-w-[120px]">
                                                <i class="fas fa-percentage mr-2 text-red-600"></i>
                                                النسبة المئوية
                                            </th>
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
                        $percentage_score = ($avg_score / MAX_INDICATOR_SCORE) * 100;
                                    ?>
                                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                                            <td class="border border-gray-300 px-4 py-3 text-right">
                                                <div class="font-medium text-gray-800"><?= htmlspecialchars($indicator_name) ?></div>
                                            </td>
                                            <!-- منسق المادة -->
                                            <td class="border border-gray-300 px-4 py-3 text-center">
                                                <?php
                                                if (isset($visitor_metrics[15][$indicator_id])) {
                                                    $score = $visitor_metrics[15][$indicator_id];
                                                    $percentage = $score['percentage_score'];
                                                    $performance_level = getPerformanceLevel($percentage);
                                                    echo '<div class="inline-block px-3 py-1 rounded-full font-bold ' . $performance_level['color_class'] . '">';
                                                    echo number_format($percentage, 1) . '%';
                                                    echo '</div>';
                                                    echo '<div class="text-xs text-gray-500 mt-1">(' . $score['visit_count'] . ' زيارة)</div>';
                                                } else {
                                                    echo '<span class="text-gray-400 font-medium">-</span>';
                                                }
                                                ?>
                                            </td>
                                            <!-- موجه المادة -->
                                            <td class="border border-gray-300 px-4 py-3 text-center">
                                                <?php
                                                if (isset($visitor_metrics[16][$indicator_id])) {
                                                    $score = $visitor_metrics[16][$indicator_id];
                                                    $percentage = $score['percentage_score'];
                                                    $performance_level = getPerformanceLevel($percentage);
                                                    echo '<div class="inline-block px-3 py-1 rounded-full font-bold ' . $performance_level['color_class'] . '">';
                                                    echo number_format($percentage, 1) . '%';
                                                    echo '</div>';
                                                    echo '<div class="text-xs text-gray-500 mt-1">(' . $score['visit_count'] . ' زيارة)</div>';
                                                } else {
                                                    echo '<span class="text-gray-400 font-medium">-</span>';
                                                }
                                                ?>
                                            </td>
                                            <!-- النائب الأكاديمي -->
                                            <td class="border border-gray-300 px-4 py-3 text-center">
                                                <?php
                                                if (isset($visitor_metrics[17][$indicator_id])) {
                                                    $score = $visitor_metrics[17][$indicator_id];
                                                    $percentage = $score['percentage_score'];
                                                    $performance_level = getPerformanceLevel($percentage);
                                                    echo '<div class="inline-block px-3 py-1 rounded-full font-bold ' . $performance_level['color_class'] . '">';
                                                    echo number_format($percentage, 1) . '%';
                                                    echo '</div>';
                                                    echo '<div class="text-xs text-gray-500 mt-1">(' . $score['visit_count'] . ' زيارة)</div>';
                                                } else {
                                                    echo '<span class="text-gray-400 font-medium">-</span>';
                                                }
                                                ?>
                                            </td>
                                            <!-- مدير المدرسة -->
                                            <td class="border border-gray-300 px-4 py-3 text-center">
                                                <?php
                                                if (isset($visitor_metrics[18][$indicator_id])) {
                                                    $score = $visitor_metrics[18][$indicator_id];
                                                    $percentage = $score['percentage_score'];
                                                    $performance_level = getPerformanceLevel($percentage);
                                                    echo '<div class="inline-block px-3 py-1 rounded-full font-bold ' . $performance_level['color_class'] . '">';
                                                    echo number_format($percentage, 1) . '%';
                                                    echo '</div>';
                                                    echo '<div class="text-xs text-gray-500 mt-1">(' . $score['visit_count'] . ' زيارة)</div>';
                                                } else {
                                                    echo '<span class="text-gray-400 font-medium">-</span>';
                                                }
                                                ?>
                                            </td>
                                            <!-- متوسط الدرجة -->
                                            <td class="border border-gray-300 px-4 py-3 text-center">
                                                <?php
                                                if ($total_visits > 0) {
                                                    echo '<span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full font-bold">';
                                                    echo number_format($avg_score, 2);
                                                    echo '</span>';
                                                    echo '<div class="text-xs text-gray-500 mt-1">(' . $total_visits . ' زيارة)</div>';
                                                } else {
                                                    echo '<span class="text-gray-400 font-medium">-</span>';
                                                }
                                                ?>
                                            </td>
                                            <!-- النسبة المئوية -->
                                            <td class="border border-gray-300 px-4 py-3 text-center">
                                                <?php
                                                if ($total_visits > 0) {
                                                    $performance_level = getPerformanceLevel($percentage_score);
                                                    echo '<div class="inline-block px-3 py-1 rounded-full font-bold ' . $performance_level['color_class'] . '">';
                                                    echo number_format($percentage_score, 1) . '%';
                                                    echo '</div>';
                                                } else {
                                                    echo '<span class="text-gray-400 font-medium">-</span>';
                                                }
                                                ?>
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
                                        <span class="text-gray-600">ممتاز (<?= EXCELLENT_THRESHOLD ?>% فأكثر)</span>
                                    </div>
                                    <div class="flex items-center">
                                        <div class="w-4 h-4 bg-blue-100 border border-blue-300 rounded-full mr-2"></div>
                                        <span class="text-gray-600">جيد جداً (<?= VERY_GOOD_THRESHOLD ?>% - <?= EXCELLENT_THRESHOLD - 0.1 ?>%)</span>
                                    </div>
                                    <div class="flex items-center">
                                        <div class="w-4 h-4 bg-yellow-100 border border-yellow-300 rounded-full mr-2"></div>
                                        <span class="text-gray-600">جيد (<?= GOOD_THRESHOLD ?>% - <?= VERY_GOOD_THRESHOLD - 0.1 ?>%)</span>
                                    </div>
                                    <div class="flex items-center">
                                        <div class="w-4 h-4 bg-orange-100 border border-orange-300 rounded-full mr-2"></div>
                                        <span class="text-gray-600">مقبول (<?= ACCEPTABLE_THRESHOLD ?>% - <?= GOOD_THRESHOLD - 0.1 ?>%)</span>
                                    </div>
                                    <div class="flex items-center">
                                        <div class="w-4 h-4 bg-red-100 border border-red-300 rounded-full mr-2"></div>
                                        <span class="text-gray-600">يحتاج تحسين (أقل من <?= ACCEPTABLE_THRESHOLD ?>%)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($teachers_data)): ?>
                    <div class="bg-white shadow rounded-lg p-6 mb-8">
                        <h3 class="text-xl font-bold text-gray-800 mb-6 border-b pb-3">
                            <i class="fas fa-users text-blue-600 mr-2"></i>
                            احتياجات المعلمين الفردية
                        </h3>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                            <?php foreach ($teachers_data as $teacher): ?>
                                <div class="bg-gradient-to-br from-white to-gray-50 border border-gray-200 rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow duration-300">
                                    <div class="flex items-start justify-between mb-4">
                                        <div>
                                            <h4 class="text-lg font-semibold text-gray-800 mb-1">
                                                <i class="fas fa-user-tie text-blue-500 mr-2"></i>
                                                <?= htmlspecialchars($teacher['name']) ?>
                                            </h4>
                                            <p class="text-sm text-gray-600">
                                                <i class="fas fa-id-card text-gray-400 mr-1"></i>
                                                رقم الموظف: <span class="font-medium"><?= htmlspecialchars($teacher['personal_id']) ?></span>
                                            </p>
                                        </div>
                                        <div class="bg-orange-100 text-orange-800 px-2 py-1 rounded-full text-xs font-medium">
                                            <?= count($teacher['weakest_areas']) ?> نقطة
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h5 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                                            <i class="fas fa-exclamation-triangle text-orange-500 mr-2"></i>
                                            النقاط التي تحتاج إلى تحسين:
                                        </h5>
                                        <div class="space-y-3">
                                            <?php foreach ($teacher['weakest_areas'] as $area): ?>
                                                <div class="bg-white border border-gray-200 rounded-lg p-3">
                                                    <div class="text-sm text-gray-800 font-medium mb-1">
                                                        <?= htmlspecialchars($area['indicator_name']) ?>
                                                    </div>
                                                    <div class="text-xs text-blue-600 font-medium mb-1">
                                                        <i class="fas fa-graduation-cap mr-1"></i>
                                                        <?= htmlspecialchars($area['workshop']) ?>
                                                    </div>
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-xs text-gray-500">النسبة الحالية:</span>
                                                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-bold">
                                                            <?= number_format($area['percentage'], 1) ?>%
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="pt-3 border-t border-gray-200">
                                        <a href="training_needs.php?teacher_id=<?= $teacher['id'] ?>" 
                                           class="inline-flex items-center justify-center w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                                            <i class="fas fa-chart-line mr-2"></i>
                                            عرض التفاصيل الكاملة
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
        
        <!-- عرض جدول المقارنة -->
        <?php if (!empty($collective_needs)): ?>
        <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-8">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-chart-bar mr-3"></i>
                    مقارنة أداء المؤشرات حسب نوع الزائر
                </h3>
                <p class="text-blue-100 text-sm mt-1">تحليل شامل لأداء المؤشرات من خلال أنواع الزوار المختلفة</p>
            </div>
            
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="w-full table-auto border-collapse">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="border border-gray-300 px-4 py-3 text-right font-semibold text-gray-700 min-w-[300px]">
                                    <i class="fas fa-list-ul mr-2 text-blue-600"></i>
                                    المؤشر
                                </th>
                                <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 min-w-[140px]">
                                    <i class="fas fa-user-graduate mr-2 text-green-600"></i>
                                    منسق المادة
                                </th>
                                <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 min-w-[140px]">
                                    <i class="fas fa-chalkboard-teacher mr-2 text-blue-600"></i>
                                    موجه المادة
                                </th>
                                <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 min-w-[140px]">
                                    <i class="fas fa-user-tie mr-2 text-purple-600"></i>
                                    النائب الأكاديمي
                                </th>
                                <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 min-w-[140px]">
                                    <i class="fas fa-crown mr-2 text-yellow-600"></i>
                                    مدير المدرسة
                                </th>
                            </tr>
                        </thead>
                        <tbody>
            <?php foreach ($collective_needs as $indicator_id => $need): ?>
                <tr class="hover:bg-gray-50 transition-colors duration-200">
                    <td class="border border-gray-300 px-4 py-3 text-right">
                        <div class="font-medium text-gray-800">
                            <?php echo htmlspecialchars($need['indicator_name']); ?>
                        </div>
                    </td>
                    <!-- منسق المادة -->
                    <td class="border border-gray-300 px-4 py-3 text-center">
                        <?php
                        if (isset($visitor_metrics[15][$indicator_id])) {
                            $score = $visitor_metrics[15][$indicator_id];
                            $percentage = $score['percentage_score'];
                            $performance_level = getPerformanceLevel($percentage);
                            echo '<div class="inline-block px-3 py-1 rounded-full font-bold ' . $performance_level['color_class'] . '">';
                            echo number_format($percentage, 1) . '%';
                            echo '</div>';
                            echo '<div class="text-xs text-gray-500 mt-1">(' . $score['visit_count'] . ' زيارة)</div>';
                        } else {
                            echo '<span class="text-gray-400 font-medium">-</span>';
                        }
                        ?>
                    </td>
                    <!-- موجه المادة -->
                    <td class="border border-gray-300 px-4 py-3 text-center">
                        <?php
                        if (isset($visitor_metrics[16][$indicator_id])) {
                            $score = $visitor_metrics[16][$indicator_id];
                            $percentage = $score['percentage_score'];
                            $performance_level = getPerformanceLevel($percentage);
                            echo '<div class="inline-block px-3 py-1 rounded-full font-bold ' . $performance_level['color_class'] . '">';
                            echo number_format($percentage, 1) . '%';
                            echo '</div>';
                            echo '<div class="text-xs text-gray-500 mt-1">(' . $score['visit_count'] . ' زيارة)</div>';
                        } else {
                            echo '<span class="text-gray-400 font-medium">-</span>';
                        }
                        ?>
                    </td>
                    <!-- النائب الأكاديمي -->
                    <td class="border border-gray-300 px-4 py-3 text-center">
                        <?php
                        if (isset($visitor_metrics[17][$indicator_id])) {
                            $score = $visitor_metrics[17][$indicator_id];
                            $percentage = $score['percentage_score'];
                            $performance_level = getPerformanceLevel($percentage);
                            echo '<div class="inline-block px-3 py-1 rounded-full font-bold ' . $performance_level['color_class'] . '">';
                            echo number_format($percentage, 1) . '%';
                            echo '</div>';
                            echo '<div class="text-xs text-gray-500 mt-1">(' . $score['visit_count'] . ' زيارة)</div>';
                        } else {
                            echo '<span class="text-gray-400 font-medium">-</span>';
                        }
                        ?>
                    </td>
                    <!-- مدير المدرسة -->
                    <td class="border border-gray-300 px-4 py-3 text-center">
                        <?php
                        if (isset($visitor_metrics[18][$indicator_id])) {
                            $score = $visitor_metrics[18][$indicator_id];
                            $percentage = $score['percentage_score'];
                            $performance_level = getPerformanceLevel($percentage);
                            echo '<div class="inline-block px-3 py-1 rounded-full font-bold ' . $performance_level['color_class'] . '">';
                            echo number_format($percentage, 1) . '%';
                            echo '</div>';
                            echo '<div class="text-xs text-gray-500 mt-1">(' . $score['visit_count'] . ' زيارة)</div>';
                        } else {
                            echo '<span class="text-gray-400 font-medium">-</span>';
                        }
                        ?>
                    </td>
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
        <?php endif; ?>
    </div>
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

<style>
/* تحسينات إضافية للجدول */
.table-responsive {
    max-width: 100%;
    overflow-x: auto;
}

.table-auto {
    table-layout: auto;
    width: 100%;
}

/* تحسين مظهر النسب المئوية */
.percentage-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-weight: 700;
    font-size: 0.875rem;
    line-height: 1.25rem;
}

/* تأثيرات hover للصفوف */
.table-row:hover {
    background-color: #f9fafb;
    transition: background-color 0.2s ease-in-out;
}

/* تحسين مظهر الخلايا الفارغة */
.empty-cell {
    color: #9ca3af;
    font-weight: 500;
    font-style: italic;
}

/* تحسين responsive للشاشات الصغيرة */
@media (max-width: 768px) {
    .table-auto th,
    .table-auto td {
        min-width: 120px;
        padding: 0.5rem;
        font-size: 0.875rem;
    }
    
    .table-auto th:first-child,
    .table-auto td:first-child {
        min-width: 200px;
        position: sticky;
        right: 0;
        background-color: white;
        z-index: 10;
    }
}

/* تحسين مظهر احتياجات المعلمين */
.teacher-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.25rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.teacher-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.improvement-item {
    background-color: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    transition: border-color 0.2s ease;
}

.improvement-item:hover {
    border-color: #3b82f6;
}
</style>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?> 