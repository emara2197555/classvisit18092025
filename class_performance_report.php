<?php
// بدء التخزين المؤقت للمخرجات
ob_start();

// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// تعيين عنوان الصفحة
$page_title = 'تقرير مقارنة أداء المعلمين';

// تضمين ملف رأس الصفحة
require_once 'includes/header.php';

// تحديد العام الدراسي (يمكن جعلها متغيرة وأخذها من النموذج)
$academic_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : '2024/2025';

// تحديد نوع الزائر (النائب الأكاديمي)
$visitor_type_id = isset($_GET['visitor_type_id']) ? (int)$_GET['visitor_type_id'] : 2; // النائب الأكاديمي هو 2

// استعلام لجلب المعلمين فقط (استبعاد المدير والنائب والموجه والمنسق) مع عدد الزيارات ومتوسط درجات تنفيذ الدرس والإدارة الصفية
$sql = "
    SELECT 
        t.id AS teacher_id,
        t.name AS teacher_name,
        COUNT(DISTINCT v.id) AS visits_count,
        
        -- متوسط تنفيذ الدرس (مجال رقم 2)
        (SELECT AVG(ve.score) * 25
         FROM visit_evaluations ve 
         JOIN visits vs ON ve.visit_id = vs.id
         JOIN evaluation_indicators ei ON ve.indicator_id = ei.id
         WHERE vs.teacher_id = t.id 
           " . ($visitor_type_id > 0 ? "AND vs.visitor_type_id = ?" : "") . "
           AND ei.domain_id = 2
           AND ve.score > 0) AS lesson_execution_avg,
           
        -- متوسط الإدارة الصفية (مجال رقم 3)
        (SELECT AVG(ve.score) * 25
         FROM visit_evaluations ve 
         JOIN visits vs ON ve.visit_id = vs.id
         JOIN evaluation_indicators ei ON ve.indicator_id = ei.id
         WHERE vs.teacher_id = t.id 
           " . ($visitor_type_id > 0 ? "AND vs.visitor_type_id = ?" : "") . "
           AND ei.domain_id = 3
           AND ve.score > 0) AS classroom_management_avg
    FROM 
        teachers t
    LEFT JOIN 
        visits v ON t.id = v.teacher_id " . ($visitor_type_id > 0 ? "AND v.visitor_type_id = ?" : "") . "
    WHERE
        t.job_title = 'معلم'
    GROUP BY 
        t.id, t.name
    ORDER BY 
        t.name
";

// تحضير المعلمات للاستعلام
$query_params = [];
if ($visitor_type_id > 0) {
    $query_params = [$visitor_type_id, $visitor_type_id, $visitor_type_id];
}

$teachers_data = query($sql, $query_params);

// حساب معدلات الأداء العامة
$total_lesson_execution = 0;
$total_classroom_management = 0;
$total_valid_teachers_lesson = 0;
$total_valid_teachers_management = 0;

// تحديد أفضل وأقل أداء
$max_lesson_execution = 0;
$min_lesson_execution = 100;
$max_classroom_management = 0;
$min_classroom_management = 100;
$max_lesson_teacher = '';
$min_lesson_teacher = '';
$max_management_teacher = '';
$min_management_teacher = '';

// حساب المتوسطات العامة وتحديد أفضل وأقل أداء
foreach ($teachers_data as $teacher) {
    // حساب متوسط تنفيذ الدرس
    if ($teacher['lesson_execution_avg'] !== null) {
        $total_lesson_execution += $teacher['lesson_execution_avg'];
        $total_valid_teachers_lesson++;
        
        // تحديد الأفضل والأقل
        if ($teacher['lesson_execution_avg'] > $max_lesson_execution) {
            $max_lesson_execution = $teacher['lesson_execution_avg'];
            $max_lesson_teacher = $teacher['teacher_name'];
        }
        if ($teacher['lesson_execution_avg'] < $min_lesson_execution && $teacher['lesson_execution_avg'] > 0) {
            $min_lesson_execution = $teacher['lesson_execution_avg'];
            $min_lesson_teacher = $teacher['teacher_name'];
        }
    }
    
    // حساب متوسط الإدارة الصفية
    if ($teacher['classroom_management_avg'] !== null) {
        $total_classroom_management += $teacher['classroom_management_avg'];
        $total_valid_teachers_management++;
        
        // تحديد الأفضل والأقل
        if ($teacher['classroom_management_avg'] > $max_classroom_management) {
            $max_classroom_management = $teacher['classroom_management_avg'];
            $max_management_teacher = $teacher['teacher_name'];
        }
        if ($teacher['classroom_management_avg'] < $min_classroom_management && $teacher['classroom_management_avg'] > 0) {
            $min_classroom_management = $teacher['classroom_management_avg'];
            $min_management_teacher = $teacher['teacher_name'];
        }
    }
}

// حساب المتوسط العام
$avg_lesson_execution = $total_valid_teachers_lesson > 0 ? $total_lesson_execution / $total_valid_teachers_lesson : 0;
$avg_classroom_management = $total_valid_teachers_management > 0 ? $total_classroom_management / $total_valid_teachers_management : 0;

// في حالة عدم وجود بيانات كافية
if ($min_lesson_execution === 100) $min_lesson_execution = 0;
if ($min_classroom_management === 100) $min_classroom_management = 0;
if ($min_lesson_teacher === '') $min_lesson_teacher = '-';
if ($max_lesson_teacher === '') $max_lesson_teacher = '-';
if ($min_management_teacher === '') $min_management_teacher = '-';
if ($max_management_teacher === '') $max_management_teacher = '-';

// جلب معلومات نوع الزائر
$visitor_type_name = "جميع الزائرين";
if ($visitor_type_id > 0) {
    $visitor_type = query_row("SELECT name FROM visitor_types WHERE id = ?", [$visitor_type_id]);
    $visitor_type_name = $visitor_type ? $visitor_type['name'] : 'النائب الأكاديمي';
}

// جلب جميع أنواع الزائرين للاختيار
$visitor_types = query("SELECT id, name FROM visitor_types ORDER BY id");
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold mb-4">تقرير مقارنة أداء المعلمين</h1>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <!-- نموذج تحديد العام الدراسي ونوع الزائر -->
        <form action="" method="get" class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="academic_year" class="block mb-1">العام الدراسي</label>
                    <input type="text" id="academic_year" name="academic_year" class="w-full rounded border-gray-300" value="<?= htmlspecialchars($academic_year) ?>">
                </div>
                
                <div>
                    <label for="visitor_type_id" class="block mb-1">نوع الزائر</label>
                    <select id="visitor_type_id" name="visitor_type_id" class="w-full rounded border-gray-300">
                        <option value="0" <?= $visitor_type_id == 0 ? 'selected' : '' ?>>الكل</option>
                        <?php foreach ($visitor_types as $type): ?>
                            <option value="<?= $type['id'] ?>" <?= $visitor_type_id == $type['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="bg-primary-600 text-white px-4 py-2 rounded-md hover:bg-primary-700 transition-colors">
                        عرض التقرير
                    </button>
                </div>
            </div>
        </form>
        
        <h2 class="text-xl font-semibold mb-4 text-center">
            تقرير مقارنة أداء المعلمين بناءً على المشاهدات الصفّية ل<?= htmlspecialchars($visitor_type_name) ?> للعام الأكاديمي <?= htmlspecialchars($academic_year) ?>
        </h2>
        
        <!-- جدول التقرير -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-3 px-4 border text-center font-semibold">المعلم</th>
                        <th class="py-3 px-4 border text-center font-semibold">عدد الزيارات</th>
                        <th class="py-3 px-4 border text-center font-semibold">تنفيذ الدرس</th>
                        <th class="py-3 px-4 border text-center font-semibold">الإدارة الصفية</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teachers_data as $teacher): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-4 border text-center"><?= htmlspecialchars($teacher['teacher_name']) ?></td>
                            <td class="py-2 px-4 border text-center"><?= $teacher['visits_count'] ?></td>
                            <td class="py-2 px-4 border text-center">
                                <?php if ($teacher['lesson_execution_avg'] !== null): ?>
                                    <?= number_format($teacher['lesson_execution_avg'], 1) ?>%
                                <?php endif; ?>
                            </td>
                            <td class="py-2 px-4 border text-center">
                                <?php if ($teacher['classroom_management_avg'] !== null): ?>
                                    <?= number_format($teacher['classroom_management_avg'], 1) ?>%
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <!-- معدل الأداء لجميع المعلمين -->
                    <tr class="bg-green-100">
                        <td class="py-2 px-4 border text-center font-bold">معدل الأداء لجميع المعلمين</td>
                        <td class="py-2 px-4 border text-center">
                            <?= array_sum(array_column($teachers_data, 'visits_count')) ?>
                        </td>
                        <td class="py-2 px-4 border text-center font-bold">
                            <?= number_format($avg_lesson_execution, 1) ?>%
                        </td>
                        <td class="py-2 px-4 border text-center font-bold">
                            <?= number_format($avg_classroom_management, 1) ?>%
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- جدول المقارنة -->
        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-3 px-4 border text-center font-semibold">جوانب المقارنة</th>
                        <th class="py-3 px-4 border text-center font-semibold">أفضل أداء</th>
                        <th class="py-3 px-4 border text-center font-semibold">أقل أداء</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 border text-center">تنفيذ الدرس</td>
                        <td class="py-2 px-4 border text-center"><?= htmlspecialchars($max_lesson_teacher) ?></td>
                        <td class="py-2 px-4 border text-center"><?= htmlspecialchars($min_lesson_teacher) ?></td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 border text-center">الإدارة الصفية</td>
                        <td class="py-2 px-4 border text-center"><?= htmlspecialchars($max_management_teacher) ?></td>
                        <td class="py-2 px-4 border text-center"><?= htmlspecialchars($min_management_teacher) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- زر الطباعة -->
        <div class="mt-6 text-center">
            <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                <i class="bi bi-printer ml-2"></i> طباعة التقرير
            </button>
        </div>
    </div>
</div>

<style media="print">
    @page {
        size: landscape;
    }
    
    header, nav, footer, form, button {
        display: none !important;
    }
    
    body {
        background-color: white;
    }
    
    h2 {
        margin-top: 0;
        margin-bottom: 20px;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    th, td {
        border: 1px solid #000;
        padding: 8px;
        text-align: center;
    }
    
    tr.bg-green-100 {
        background-color: #d1fae5 !important;
        -webkit-print-color-adjust: exact;
    }
    
    thead.bg-gray-100 {
        background-color: #f3f4f6 !important;
        -webkit-print-color-adjust: exact;
    }
</style>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?> 