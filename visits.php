<?php
// بدء التخزين المؤقت للمخرجات - سيحل مشكلة Headers already sent
ob_start();

// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// تعيين عنوان الصفحة
$page_title = 'إدارة الزيارات الصفية';

// تضمين ملف رأس الصفحة
require_once 'includes/header.php';

// التحقق من وجود رسالة تنبيه
$alert_message = '';
if (isset($_SESSION['alert_message']) && isset($_SESSION['alert_type'])) {
    $alert_message = show_alert($_SESSION['alert_message'], $_SESSION['alert_type']);
    // حذف الرسالة بعد عرضها
    unset($_SESSION['alert_message']);
    unset($_SESSION['alert_type']);
}

// تحديد عدد العناصر في الصفحة
$items_per_page = 10;

// الحصول على رقم الصفحة الحالية
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// حساب الإزاحة للاستعلام
$offset = ($current_page - 1) * $items_per_page;

// بناء شرط البحث إذا تم تقديم نموذج البحث
$search_condition = '';
$search_params = [];
$search_filters = [
    'school_id' => 'v.school_id = ?',
    'teacher_id' => 'v.teacher_id = ?',
    'subject_id' => 'v.subject_id = ?',
    'visitor_type_id' => 'v.visitor_type_id = ?',
    'visit_date_from' => 'v.visit_date >= ?',
    'visit_date_to' => 'v.visit_date <= ?'
];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $conditions = [];
    
    foreach ($search_filters as $key => $condition) {
        if (isset($_GET[$key]) && !empty($_GET[$key])) {
            $conditions[] = $condition;
            $search_params[] = $_GET[$key];
        }
    }
    
    if (!empty($conditions)) {
        $search_condition = ' WHERE ' . implode(' AND ', $conditions);
    }
}

// استعلام لجلب إجمالي عدد الزيارات
$count_sql = "SELECT COUNT(*) as total FROM visits v $search_condition";
$total_items = query_row($count_sql, $search_params)['total'] ?? 0;

// حساب إجمالي عدد الصفحات
$total_pages = ceil($total_items / $items_per_page);

// استعلام جلب الزيارات مع البيانات ذات الصلة
$sql = "
    SELECT 
        v.id, 
        v.visit_date,
        v.visit_type,
        v.attendance_type,
        v.total_score,
        s.name as school_name,
        t.name as teacher_name,
        sub.name as subject_name,
        vt.name as visitor_type,
        g.name as grade_name,
        sec.name as section_name
    FROM 
        visits v
    JOIN 
        schools s ON v.school_id = s.id
    JOIN 
        teachers t ON v.teacher_id = t.id
    JOIN 
        subjects sub ON v.subject_id = sub.id
    JOIN 
        visitor_types vt ON v.visitor_type_id = vt.id
    JOIN 
        grades g ON v.grade_id = g.id
    JOIN 
        sections sec ON v.section_id = sec.id
    $search_condition
    ORDER BY 
        v.visit_date DESC
    LIMIT 
        $items_per_page OFFSET $offset
";

try {
    $visits = query($sql, $search_params);
} catch (Exception $e) {
    $visits = [];
    $alert_message = show_alert('حدث خطأ أثناء استرجاع البيانات: ' . $e->getMessage(), 'error');
}

// جلب المدارس للفلتر
$schools = query("SELECT id, name FROM schools ORDER BY name");

// جلب المعلمين للفلتر
$teachers = query("SELECT id, name FROM teachers ORDER BY name");

// جلب المواد للفلتر
$subjects = query("SELECT id, name FROM subjects ORDER BY name");

// جلب أنواع الزائرين للفلتر
$visitor_types = query("SELECT id, name FROM visitor_types ORDER BY name");
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold mb-2">إدارة الزيارات الصفية</h1>
    <p class="text-gray-600">عرض وإدارة جميع الزيارات الصفية في النظام</p>
</div>

<?= $alert_message ?>

<!-- نموذج البحث والفلترة -->
<div class="bg-white p-4 rounded-lg shadow-md mb-6">
    <h2 class="text-lg font-semibold mb-4">بحث وتصفية</h2>
    <form method="GET" action="" class="space-y-4">
        <input type="hidden" name="search" value="1">
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- المدرسة -->
            <div>
                <label for="school_id" class="block mb-1 text-sm">المدرسة</label>
                <select id="school_id" name="school_id" class="w-full rounded border-gray-300">
                    <option value="">جميع المدارس</option>
                    <?php foreach ($schools as $school): ?>
                    <option value="<?= $school['id'] ?>" <?= (isset($_GET['school_id']) && $_GET['school_id'] == $school['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($school['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- المعلم -->
            <div>
                <label for="teacher_id" class="block mb-1 text-sm">المعلم</label>
                <select id="teacher_id" name="teacher_id" class="w-full rounded border-gray-300">
                    <option value="">جميع المعلمين</option>
                    <?php foreach ($teachers as $teacher): ?>
                    <option value="<?= $teacher['id'] ?>" <?= (isset($_GET['teacher_id']) && $_GET['teacher_id'] == $teacher['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($teacher['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- المادة -->
            <div>
                <label for="subject_id" class="block mb-1 text-sm">المادة</label>
                <select id="subject_id" name="subject_id" class="w-full rounded border-gray-300">
                    <option value="">جميع المواد</option>
                    <?php foreach ($subjects as $subject): ?>
                    <option value="<?= $subject['id'] ?>" <?= (isset($_GET['subject_id']) && $_GET['subject_id'] == $subject['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($subject['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- نوع الزائر -->
            <div>
                <label for="visitor_type_id" class="block mb-1 text-sm">نوع الزائر</label>
                <select id="visitor_type_id" name="visitor_type_id" class="w-full rounded border-gray-300">
                    <option value="">جميع أنواع الزائرين</option>
                    <?php foreach ($visitor_types as $type): ?>
                    <option value="<?= $type['id'] ?>" <?= (isset($_GET['visitor_type_id']) && $_GET['visitor_type_id'] == $type['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($type['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- تاريخ الزيارة (من) -->
            <div>
                <label for="visit_date_from" class="block mb-1 text-sm">تاريخ الزيارة (من)</label>
                <input type="date" id="visit_date_from" name="visit_date_from" class="w-full rounded border-gray-300"
                       value="<?= isset($_GET['visit_date_from']) ? htmlspecialchars($_GET['visit_date_from']) : '' ?>">
            </div>
            
            <!-- تاريخ الزيارة (إلى) -->
            <div>
                <label for="visit_date_to" class="block mb-1 text-sm">تاريخ الزيارة (إلى)</label>
                <input type="date" id="visit_date_to" name="visit_date_to" class="w-full rounded border-gray-300"
                       value="<?= isset($_GET['visit_date_to']) ? htmlspecialchars($_GET['visit_date_to']) : '' ?>">
            </div>
        </div>
        
        <div class="flex justify-between">
            <button type="submit" class="bg-primary-600 text-white px-4 py-2 rounded hover:bg-primary-700 transition">
                بحث
            </button>
            <a href="visits.php" class="bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300 transition">
                إعادة تعيين
            </a>
        </div>
    </form>
</div>

<!-- زر إضافة زيارة جديدة -->
<div class="flex justify-end mb-4">
    <a href="evaluation_form.php" class="bg-secondary-600 text-white px-4 py-2 rounded hover:bg-secondary-700 transition">
        إضافة زيارة جديدة
    </a>
</div>

<!-- جدول الزيارات -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">#</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">المدرسة</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">المعلم</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">المادة</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">الصف/الشعبة</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">تاريخ الزيارة</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">نوع الزائر</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">النتيجة</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($visits)): ?>
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-gray-500">لا توجد زيارات صفية مسجلة</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($visits as $index => $visit): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-700"><?= $offset + $index + 1 ?></td>
                        <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($visit['school_name']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($visit['teacher_name']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($visit['subject_name']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            <?= htmlspecialchars($visit['grade_name']) ?> / <?= htmlspecialchars($visit['section_name']) ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700"><?= format_date_ar($visit['visit_date']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($visit['visitor_type']) ?></td>
                        <td class="px-4 py-3 text-sm">
                            <?php 
                            $score = floatval($visit['total_score']);
                            $grade = get_grade($score);
                            $bg_color = 'bg-gray-100';
                            
                            if ($score >= 3.6) $bg_color = 'bg-green-100 text-green-800';
                            else if ($score >= 3.2) $bg_color = 'bg-blue-100 text-blue-800';
                            else if ($score >= 2.6) $bg_color = 'bg-yellow-100 text-yellow-800';
                            else if ($score >= 2.0) $bg_color = 'bg-orange-100 text-orange-800';
                            else $bg_color = 'bg-red-100 text-red-800';
                            ?>
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?= $bg_color ?>">
                                <?= number_format($score, 2) ?> - <?= $grade ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex space-x-2 space-x-reverse">
                                <a href="view_visit.php?id=<?= $visit['id'] ?>" class="text-blue-600 hover:text-blue-800" title="عرض">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                <a href="print_visit.php?id=<?= $visit['id'] ?>" class="text-gray-600 hover:text-gray-800" title="طباعة">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                </a>
                                <a href="delete_visit.php?id=<?= $visit['id'] ?>" class="text-red-600 hover:text-red-800" 
                                   onclick="return confirm('هل أنت متأكد من حذف هذه الزيارة؟');" title="حذف">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ترقيم الصفحات -->
<?php if ($total_pages > 1): ?>
<div class="mt-6 flex justify-center">
    <div class="flex space-x-2 space-x-reverse">
        <?php if ($current_page > 1): ?>
        <a href="?page=<?= $current_page - 1 ?><?= isset($_GET['search']) ? '&' . http_build_query(array_filter($_GET, function($key) { return $key != 'page'; }, ARRAY_FILTER_USE_KEY)) : '' ?>" 
           class="px-3 py-1 border rounded bg-white hover:bg-gray-100">
            السابق
        </a>
        <?php endif; ?>
        
        <?php
        $start_page = max(1, $current_page - 2);
        $end_page = min($start_page + 4, $total_pages);
        
        if ($end_page - $start_page < 4 && $start_page > 1) {
            $start_page = max(1, $end_page - 4);
        }
        
        for ($i = $start_page; $i <= $end_page; $i++):
        ?>
        <a href="?page=<?= $i ?><?= isset($_GET['search']) ? '&' . http_build_query(array_filter($_GET, function($key) { return $key != 'page'; }, ARRAY_FILTER_USE_KEY)) : '' ?>" 
           class="px-3 py-1 border rounded <?= $i == $current_page ? 'bg-primary-600 text-white' : 'bg-white hover:bg-gray-100' ?>">
            <?= $i ?>
        </a>
        <?php endfor; ?>
        
        <?php if ($current_page < $total_pages): ?>
        <a href="?page=<?= $current_page + 1 ?><?= isset($_GET['search']) ? '&' . http_build_query(array_filter($_GET, function($key) { return $key != 'page'; }, ARRAY_FILTER_USE_KEY)) : '' ?>" 
           class="px-3 py-1 border rounded bg-white hover:bg-gray-100">
            التالي
        </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?> 