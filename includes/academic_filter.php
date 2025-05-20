<?php
/**
 * مكون فلترة العام الأكاديمي والفصل الدراسي
 * يستخدم في جميع الصفحات التي تحتاج إلى فلترة البيانات حسب العام والفصل
 */

// تحقق مما إذا كان هذا الملف قد تم تضمينه بالفعل في visits.php
$is_visits_page = basename($_SERVER['PHP_SELF']) === 'visits.php';

// لا تعرض الفلتر إذا كنا في صفحة visits.php لأنها تتضمن فلتر مخصص
if ($is_visits_page) {
    return;
}

// الحصول على قائمة الأعوام الأكاديمية
$academic_years_query = "SELECT * FROM academic_years ORDER BY id DESC";
$academic_years = query($academic_years_query);

// تحقق من وجود الجلسة وقيم الفلترة
if (!isset($_SESSION['selected_academic_year'])) {
    // ابحث عن العام الأكاديمي النشط
    $active_year = get_active_academic_year();
    $_SESSION['selected_academic_year'] = $active_year['id'] ?? null;
    $_SESSION['selected_term'] = 'all';
}

// الحصول على معرف العام الدراسي المحدد من جلسة المستخدم
$selected_year_id = $_SESSION['selected_academic_year'];
$selected_term = $_SESSION['selected_term'] ?? 'all';

// الحصول على تفاصيل العام الأكاديمي المحدد
$current_year_query = "SELECT * FROM academic_years WHERE id = ?";
$current_year_data = query_row($current_year_query, [$selected_year_id]);

// تحديد تواريخ الفصول الدراسية
$first_term_start = $current_year_data['first_term_start'] ?? null;
$first_term_end = $current_year_data['first_term_end'] ?? null;
$second_term_start = $current_year_data['second_term_start'] ?? null;
$second_term_end = $current_year_data['second_term_end'] ?? null;

// تحديد شرط تاريخ SQL للفلترة
$date_condition = "";
if ($selected_term == 'first' && $first_term_start && $first_term_end) {
    $date_condition = " AND visit_date BETWEEN '$first_term_start' AND '$first_term_end'";
} elseif ($selected_term == 'second' && $second_term_start && $second_term_end) {
    $date_condition = " AND visit_date BETWEEN '$second_term_start' AND '$second_term_end'";
}

// معالجة الطلب عند تغيير الفلاتر
if (isset($_POST['filter_academic_year'])) {
    $_SESSION['selected_academic_year'] = $_POST['academic_year_id'];
    $_SESSION['selected_term'] = $_POST['term'];
    
    // إعادة توجيه إلى نفس الصفحة لتطبيق التغييرات
    header("Location: " . $_SERVER['PHP_SELF'] . (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
    exit;
}
?>

<!-- مكون فلترة العام الأكاديمي والفصل الدراسي -->
<div class="bg-white rounded-lg shadow-sm border-r-4 border-primary-500 p-4 mb-6">
    <form method="post" class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
            <label for="academic_year_id" class="block text-sm font-medium text-gray-700 mb-1">العام الدراسي</label>
            <select id="academic_year_id" name="academic_year_id" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200">
                <?php foreach ($academic_years as $year): ?>
                <option value="<?= $year['id'] ?>" <?= $year['id'] == $selected_year_id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($year['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="flex-1 min-w-[200px]">
            <label for="term" class="block text-sm font-medium text-gray-700 mb-1">الفصل الدراسي</label>
            <select id="term" name="term" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200">
                <option value="all" <?= $selected_term == 'all' ? 'selected' : '' ?>>كل الفصول</option>
                <option value="first" <?= $selected_term == 'first' ? 'selected' : '' ?>>
                    الفصل الأول 
                    <?php if ($first_term_start && $first_term_end): ?>
                    (<?= format_date_ar($first_term_start) ?> - <?= format_date_ar($first_term_end) ?>)
                    <?php endif; ?>
                </option>
                <option value="second" <?= $selected_term == 'second' ? 'selected' : '' ?>>
                    الفصل الثاني
                    <?php if ($second_term_start && $second_term_end): ?>
                    (<?= format_date_ar($second_term_start) ?> - <?= format_date_ar($second_term_end) ?>)
                    <?php endif; ?>
                </option>
            </select>
        </div>
        
        <div>
            <button type="submit" name="filter_academic_year" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-md shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500">
                <i class="fas fa-filter ml-1"></i>
                تطبيق الفلتر
            </button>
        </div>
    </form>
</div> 