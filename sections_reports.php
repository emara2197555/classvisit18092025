<?php
// بدء التخزين المؤقت للمخرجات
ob_start();

// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// تعيين عنوان الصفحة
$page_title = 'تقارير الشعب الدراسية';
$current_page = 'sections_reports.php';

// تضمين ملف رأس الصفحة
require_once 'includes/header.php';

// تحديد المرحلة والصف للتصفية (اختياري)
$level_id = isset($_GET['level_id']) ? (int)$_GET['level_id'] : 0;
$grade_id = isset($_GET['grade_id']) ? (int)$_GET['grade_id'] : 0;
$academic_year_id = isset($_GET['academic_year_id']) ? (int)$_GET['academic_year_id'] : 0;
$school_id = isset($_GET['school_id']) ? (int)$_GET['school_id'] : 0;
$term = isset($_GET['term']) ? $_GET['term'] : 'all';

// تحديد العام الدراسي النشط إذا لم يتم تحديد عام
$year = null;
$academic_year_name = '';

if (!$academic_year_id) {
    $active_year = get_active_academic_year();
    $academic_year_id = $active_year ? $active_year['id'] : 0;
    $academic_year_name = $active_year ? $active_year['name'] : '';
} else {
    $year = query_row("SELECT name, first_term_start, first_term_end, second_term_start, second_term_end FROM academic_years WHERE id = ?", [$academic_year_id]);
    $academic_year_name = $year ? $year['name'] : '';
}

// تحديد معلومات الفلتر للفصل الدراسي
$date_filter = "";
$date_params = [];

if ($academic_year_id > 0 && $term != 'all' && $year) {
    if ($term == 'first' && !empty($year['first_term_start']) && !empty($year['first_term_end'])) {
        $date_filter = " AND v.visit_date BETWEEN ? AND ?";
        $date_params[] = $year['first_term_start'];
        $date_params[] = $year['first_term_end'];
    } elseif ($term == 'second' && !empty($year['second_term_start']) && !empty($year['second_term_end'])) {
        $date_filter = " AND v.visit_date BETWEEN ? AND ?";
        $date_params[] = $year['second_term_start'];
        $date_params[] = $year['second_term_end'];
    }
}

// جلب جميع المراحل التعليمية
$educational_levels = query("SELECT * FROM educational_levels ORDER BY id");

// جلب الأعوام الدراسية للاختيار
$academic_years = query("SELECT id, name, is_active FROM academic_years ORDER BY is_active DESC, name DESC");

// جلب قائمة المدارس للاختيار
$schools = query("SELECT id, name FROM schools ORDER BY name");

// إذا تم تحديد مرحلة، جلب الصفوف الخاصة بها
$grades = [];
if ($level_id > 0) {
    $grades = query("SELECT id, name FROM grades WHERE level_id = ? ORDER BY name", [$level_id]);
}

// استرجاع قائمة الشعب مع معلومات الصفوف والمراحل
$sql = "
    SELECT 
        s.id AS section_id, 
        s.name AS section_name, 
        g.id AS grade_id,
        g.name AS grade_name, 
        e.id AS level_id,
        e.name AS level_name,
        sch.id AS school_id,
        sch.name AS school_name,
        (SELECT COUNT(DISTINCT v.id) 
         FROM visits v 
         WHERE v.section_id = s.id 
         AND v.grade_id = g.id
         " . ($academic_year_id > 0 ? " AND v.academic_year_id = ?" : "") . "
         " . ($school_id > 0 ? " AND v.school_id = ?" : "") . "
         " . $date_filter . "
        ) AS visits_count
    FROM 
        sections s
    JOIN 
        grades g ON s.grade_id = g.id
    JOIN 
        educational_levels e ON g.level_id = e.id
    JOIN
        schools sch ON s.school_id = sch.id
    WHERE 
        1=1
        " . ($level_id > 0 ? " AND e.id = ?" : "") . "
        " . ($grade_id > 0 ? " AND g.id = ?" : "") . "
        " . ($school_id > 0 ? " AND sch.id = ?" : "") . "
    ORDER BY 
        e.id, g.id, s.name
";

// تحضير المعلمات للاستعلام
$query_params = [];
if ($academic_year_id > 0) {
    $query_params[] = $academic_year_id;
}
if ($school_id > 0) {
    $query_params[] = $school_id;
}
if (!empty($date_filter)) {
    $query_params = array_merge($query_params, $date_params);
}
if ($level_id > 0) {
    $query_params[] = $level_id;
}
if ($grade_id > 0) {
    $query_params[] = $grade_id;
}
if ($school_id > 0) {
    $query_params[] = $school_id;
}

$sections = query($sql, $query_params);

// تنظيم الشعب حسب المرحلة والصف
$organized_sections = [];
$current_level = '';
$current_grade = '';

foreach ($sections as $section) {
    if ($section['level_name'] != $current_level) {
        $current_level = $section['level_name'];
        $organized_sections[$current_level] = [];
    }
    
    if ($section['grade_name'] != $current_grade || $current_grade != $section['grade_name']) {
        $current_grade = $section['grade_name'];
        $organized_sections[$current_level][$current_grade] = [];
    }
    
    $organized_sections[$current_level][$current_grade][] = $section;
}

// تحديد معلومات المدرسة المحددة
$school_name = '';
if ($school_id > 0) {
    foreach ($schools as $school) {
        if ($school['id'] == $school_id) {
            $school_name = $school['name'];
            break;
        }
    }
}

?>

<div class="container mx-auto py-6 px-4">
    <h1 class="text-2xl font-bold mb-4">تقارير الشعب الدراسية</h1>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <!-- نموذج التصفية -->
        <form action="" method="get" class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label for="level_id" class="block mb-1">المرحلة التعليمية</label>
                    <select id="level_id" name="level_id" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200" onchange="this.form.submit()">
                        <option value="0">جميع المراحل</option>
                        <?php foreach ($educational_levels as $level): ?>
                            <option value="<?= $level['id'] ?>" <?= $level_id == $level['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($level['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="grade_id" class="block mb-1">الصف الدراسي</label>
                    <select id="grade_id" name="grade_id" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200" onchange="this.form.submit()" <?= $level_id == 0 ? 'disabled' : '' ?>>
                        <option value="0">جميع الصفوف</option>
                        <?php foreach ($grades as $grade): ?>
                            <option value="<?= $grade['id'] ?>" <?= $grade_id == $grade['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($grade['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="academic_year_id" class="block mb-1">العام الدراسي</label>
                    <select id="academic_year_id" name="academic_year_id" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200" onchange="this.form.submit()">
                        <option value="0">جميع الأعوام الدراسية</option>
                        <?php foreach ($academic_years as $year): ?>
                            <option value="<?= $year['id'] ?>" <?= $academic_year_id == $year['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($year['name']) ?> <?= $year['is_active'] ? '(نشط)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="term" class="block mb-1">الفصل الدراسي</label>
                    <select id="term" name="term" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200" onchange="this.form.submit()">
                        <option value="all" <?= $term == 'all' ? 'selected' : '' ?>>الكل</option>
                        <option value="first" <?= $term == 'first' ? 'selected' : '' ?>>الفصل الأول</option>
                        <option value="second" <?= $term == 'second' ? 'selected' : '' ?>>الفصل الثاني</option>
                    </select>
                </div>
                
                <div>
                    <label for="school_id" class="block mb-1">المدرسة</label>
                    <select id="school_id" name="school_id" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200" onchange="this.form.submit()">
                        <option value="0">جميع المدارس</option>
                        <?php foreach ($schools as $school): ?>
                            <option value="<?= $school['id'] ?>" <?= $school_id == $school['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($school['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </form>
        
        <?php if (empty($sections)): ?>
            <div class="bg-yellow-100 border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4 border-r-4">
                لا توجد شعب دراسية متاحة ضمن المعايير المحددة. يرجى تعديل معايير التصفية.
            </div>
        <?php else: ?>
            <div class="mb-4 text-center text-lg font-semibold">
                <span>
                    تقارير الشعب
                    <?php if ($academic_year_id > 0): ?>
                        للعام الدراسي: <?= htmlspecialchars($academic_year_name) ?>
                    <?php endif; ?>
                    <?php if ($term != 'all'): ?>
                        (<?= $term == 'first' ? 'الفصل الأول' : 'الفصل الثاني' ?>)
                    <?php endif; ?>
                    <?php if ($school_id > 0): ?>
                        - مدرسة: <?= htmlspecialchars($school_name) ?>
                    <?php endif; ?>
                </span>
            </div>
            
            <!-- عرض الشعب حسب المرحلة والصف -->
            <?php foreach ($organized_sections as $level_name => $level_grades): ?>
                <div class="mb-6">
                    <h2 class="text-xl font-bold mb-3 bg-blue-100 p-2 rounded-t-lg">
                        <?= htmlspecialchars($level_name) ?>
                    </h2>
                    
                    <?php foreach ($level_grades as $grade_name => $grade_sections): ?>
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold mb-2 bg-gray-100 p-2">
                                <?= htmlspecialchars($grade_name) ?>
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <?php foreach ($grade_sections as $section): ?>
                                    <div class="border rounded-lg overflow-hidden hover:shadow-md bg-white">
                                        <div class="p-4">
                                            <div class="font-bold text-lg mb-1">
                                                الشعبة <?= htmlspecialchars($section['section_name']) ?>
                                            </div>
                                            <div class="text-sm text-gray-600 mb-2">
                                                <?= htmlspecialchars($section['school_name']) ?>
                                            </div>
                                            <div class="text-sm mb-3">
                                                <span class="inline-block bg-blue-100 text-blue-800 rounded-full px-2 py-1 text-xs font-semibold ml-2">
                                                    عدد الزيارات: <?= $section['visits_count'] ?>
                                                </span>
                                                <span class="inline-block bg-green-100 text-green-800 rounded-full px-2 py-1 text-xs font-semibold">
                                                    <?= htmlspecialchars($grade_name) ?>
                                                </span>
                                            </div>
                                            <?php if ($section['visits_count'] > 0): ?>
                                                <a href="section_report.php?grade_id=<?= $section['grade_id'] ?>&section_id=<?= $section['section_id'] ?>&academic_year_id=<?= $academic_year_id ?>" class="block w-full bg-primary-600 text-white text-center py-2 rounded-md hover:bg-primary-700 transition-colors">
                                                    عرض التقرير
                                                </a>
                                            <?php else: ?>
                                                <button disabled class="block w-full bg-gray-300 text-gray-500 cursor-not-allowed text-center py-2 rounded-md">
                                                    لا توجد زيارات
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            
            <!-- عرض إحصائيات الشعب -->
            <div class="bg-gray-50 p-4 rounded-lg mt-6">
                <h3 class="text-lg font-semibold mb-3">إحصائيات عامة</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                    <div class="bg-white p-3 rounded-lg shadow-sm">
                        <div class="text-3xl font-bold text-blue-600"><?= count($sections) ?></div>
                        <div class="text-gray-600">عدد الشعب</div>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow-sm">
                        <div class="text-3xl font-bold text-green-600">
                            <?= array_sum(array_column($sections, 'visits_count')) ?>
                        </div>
                        <div class="text-gray-600">إجمالي عدد الزيارات</div>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow-sm">
                        <div class="text-3xl font-bold text-purple-600">
                            <?= count(array_filter($sections, function($s) { return $s['visits_count'] > 0; })) ?>
                        </div>
                        <div class="text-gray-600">الشعب التي تمت زيارتها</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // JavaScript للتعامل مع اختيار المرحلة والصف
    document.addEventListener('DOMContentLoaded', function() {
        const levelSelect = document.getElementById('level_id');
        const gradeSelect = document.getElementById('grade_id');
        
        // عند تغيير المرحلة، إرسال النموذج لتحديث قائمة الصفوف
        levelSelect.addEventListener('change', function() {
            gradeSelect.disabled = levelSelect.value == 0;
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?> 