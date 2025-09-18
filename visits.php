<?php
// بدء التخزين المؤقت للمخرجات - سيحل مشكلة Headers already sent
ob_start();

// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';
require_once 'includes/auth_functions.php';

// حماية الصفحة - الوصول للمديرين والمشرفين ومنسقي المواد والمعلمين
protect_page(['Admin', 'Director', 'Academic Deputy', 'Supervisor', 'Subject Coordinator', 'Teacher']);

// الحصول على معلومات المستخدم
$user_id = $_SESSION['user_id'];
$user_role_name = $_SESSION['role_name'];

// تعيين عنوان الصفحة
$page_title = 'إدارة الزيارات الصفية';

// تضمين ملف رأس الصفحة
require_once 'includes/header.php';

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

// إضافة حقل الترشيح للمادة الدراسية
$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

// جلب جميع المواد الدراسية للفلترة
$subjects = query("SELECT * FROM subjects ORDER BY name");

// جلب الأعوام الأكاديمية
$academic_years_query = "SELECT * FROM academic_years ORDER BY id DESC";
$academic_years = query($academic_years_query);

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

// إضافة شرط العام الأكاديمي والفصل الدراسي
$search_condition = " WHERE v.academic_year_id = ?";
$search_params = [$selected_year_id];

// إضافة قيود منسق المادة
if ($user_role_name === 'Subject Coordinator') {
    $coordinator_data = query_row("
        SELECT subject_id 
        FROM coordinator_supervisors 
        WHERE user_id = ?
    ", [$user_id]);
    
    if ($coordinator_data) {
        $search_condition .= " AND v.subject_id = ?";
        $search_params[] = $coordinator_data['subject_id'];
    } else {
        // إذا لم يكن هناك مادة مخصصة، لا تظهر أي زيارات
        $search_condition .= " AND 1 = 0";
    }
}

// إضافة قيود المعلم - يرى زياراته فقط
if ($user_role_name === 'Teacher') {
    // الحصول على teacher_id من الجلسة أو من قاعدة البيانات
    $teacher_id = $_SESSION['teacher_id'] ?? null;
    
    // إذا لم يكن teacher_id موجود في الجلسة، ابحث عنه
    if (!$teacher_id) {
        $teacher_data = query_row("SELECT id FROM teachers WHERE user_id = ?", [$user_id]);
        if ($teacher_data) {
            $teacher_id = $teacher_data['id'];
            $_SESSION['teacher_id'] = $teacher_id; // حفظ في الجلسة للمرات القادمة
        }
    }
    
    if ($teacher_id) {
        $search_condition .= " AND v.teacher_id = ?";
        $search_params[] = $teacher_id;
    } else {
        // إذا لم يتم العثور على المعلم، لا تظهر أي زيارات
        $search_condition .= " AND 1 = 0";
    }
    
    // إذا تم تمرير teacher_id في الرابط، تأكد أنه نفس المعلم المسجل دخوله
    if (isset($_GET['teacher_id']) && $_GET['teacher_id'] != $teacher_id) {
        // إعادة توجيه المعلم لزياراته فقط
        header("Location: visits.php?teacher_id=$teacher_id");
        exit();
    }
}

// إضافة شرط الفلترة حسب الفصل الدراسي إذا كان محددًا
if ($selected_term != 'all' && !empty($date_condition)) {
    // نستخدم شرط التاريخ مباشرة (هو يتضمن القيم وليس علامات استفهام)
    $search_condition .= $date_condition;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $conditions = [];
    
    foreach ($search_filters as $key => $condition) {
        if (isset($_GET[$key]) && !empty($_GET[$key])) {
            $conditions[] = $condition;
            $search_params[] = $_GET[$key];
        }
    }
    
    if (!empty($conditions)) {
        $search_condition .= ' AND ' . implode(' AND ', $conditions);
    }
}

// تحديث فلتر العام والفصل الدراسي إذا تم تقديم النموذج
if (isset($_POST['filter_academic_year'])) {
    $_SESSION['selected_academic_year'] = $_POST['academic_year_id'];
    $_SESSION['selected_term'] = $_POST['term'];
    
    // إعادة توجيه إلى نفس الصفحة لتطبيق التغييرات
    header("Location: " . $_SERVER['PHP_SELF'] . (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
    exit;
}

// استعلام لجلب إجمالي عدد الزيارات
$count_sql = "SELECT COUNT(*) as total FROM visits v $search_condition";
$total_items = query_row($count_sql, $search_params)['total'] ?? 0;

// حساب إجمالي عدد الصفحات
$total_pages = ceil($total_items / $items_per_page);

// تحديث استعلام جلب الزيارات ليشمل جميع البيانات المطلوبة
$visits_sql = "
    SELECT 
        v.id,
        v.visit_date,
        v.visitor_person_id,
        t.id AS teacher_id,
        t.name AS teacher_name,
        s.name AS school_name,
        vt.name AS visitor_type,
        g.name AS grade_name,
        sec.name AS section_name,
        subj.id AS subject_id,
        subj.name AS subject_name
    FROM 
        visits v
    JOIN 
        teachers t ON v.teacher_id = t.id
    JOIN 
        schools s ON v.school_id = s.id
    JOIN 
        visitor_types vt ON v.visitor_type_id = vt.id
    JOIN 
        grades g ON v.grade_id = g.id
    JOIN 
        sections sec ON v.section_id = sec.id
    JOIN 
        subjects subj ON v.subject_id = subj.id
    $search_condition
    ORDER BY 
        v.visit_date DESC
    LIMIT $offset, $items_per_page
";

try {
    $visits = query($visits_sql, $search_params);
} catch (Exception $e) {
    $visits = [];
    $alert_message = show_alert('حدث خطأ أثناء استرجاع البيانات: ' . $e->getMessage(), 'error');
}

// جلب المدارس للفلتر
$schools = query("SELECT id, name FROM schools ORDER BY name");

// جلب المعلمين للفلتر مع تطبيق قيود منسق المادة
if ($user_role_name === 'Subject Coordinator') {
    $coordinator_data = query_row("
        SELECT subject_id 
        FROM coordinator_supervisors 
        WHERE user_id = ?
    ", [$user_id]);
    
    if ($coordinator_data) {
        // جلب المعلمين الذين يدرسون مادة المنسق فقط
        $teachers = query("
            SELECT DISTINCT t.id, t.name 
            FROM teachers t 
            JOIN teacher_subjects ts ON t.id = ts.teacher_id 
            WHERE ts.subject_id = ? 
            ORDER BY t.name
        ", [$coordinator_data['subject_id']]);
        
        // جلب مادة المنسق فقط
        $subjects = query("
            SELECT id, name 
            FROM subjects 
            WHERE id = ? 
            ORDER BY name
        ", [$coordinator_data['subject_id']]);
    } else {
        $teachers = [];
        $subjects = [];
    }
} else {
    // المدراء والمشرفون يرون جميع المعلمين والمواد
    $teachers = query("SELECT id, name FROM teachers ORDER BY name");
    $subjects = query("SELECT id, name FROM subjects ORDER BY name");
}

// جلب أنواع الزائرين للفلتر
$visitor_types = query("SELECT id, name FROM visitor_types ORDER BY name");
?>

<div class="container mx-auto px-4 py-8" style="margin-top: 20px;">
    <h1 class="text-2xl font-bold mb-6">إدارة الزيارات الصفية</h1>
    
    <!-- نموذج البحث والتصفية الموحد -->
    <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">البحث والتصفية</h2>
        
        <form action="" method="post" class="space-y-4">
            <!-- الصف الأول - فلترة العام الأكاديمي والفصل الدراسي -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="academic_year_id" class="block mb-1">العام الدراسي</label>
                    <select id="academic_year_id" name="academic_year_id" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200">
                        <?php foreach ($academic_years as $year): ?>
                        <option value="<?= $year['id'] ?>" <?= $year['id'] == $selected_year_id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($year['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="term" class="block mb-1">الفصل الدراسي</label>
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
                
                <div class="md:col-span-2">
                    <button type="submit" name="filter_academic_year" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-md shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <i class="fas fa-filter ml-1"></i>
                        تطبيق فلتر العام الدراسي
                    </button>
                </div>
            </div>
        </form>
        
        <hr class="my-6 border-gray-300">
        
        <?php if ($user_role_name === 'Subject Coordinator'): ?>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-center">
                <i class="fas fa-info-circle text-blue-600 ml-2"></i>
                <div>
                    <h4 class="font-semibold text-blue-800">ملاحظة للمنسق</h4>
                    <p class="text-blue-700 text-sm mt-1">
                        يمكنك حذف زيارات <strong>مادتك فقط</strong> من نوع <span class="bg-blue-100 px-2 py-1 rounded">منسق المادة</span> أو <span class="bg-blue-100 px-2 py-1 rounded">موجه المادة</span>
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($user_role_name !== 'Teacher'): ?>
        <form action="" method="get" class="space-y-4">
            <!-- الصف الثاني - فلترة المدرسة والمادة والمعلم -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="school_id" class="block mb-1">المدرسة</label>
                    <select id="school_id" name="school_id" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200">
                        <option value="0">الكل</option>
                        <?php foreach ($schools as $school): ?>
                            <option value="<?= $school['id'] ?>" <?= (isset($_GET['school_id']) && $_GET['school_id'] == $school['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($school['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="subject_id" class="block mb-1">المادة الدراسية</label>
                    <select id="subject_id" name="subject_id" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200">
                        <option value="0">الكل</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?= $subject['id'] ?>" <?= (isset($_GET['subject_id']) && $_GET['subject_id'] == $subject['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($subject['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="teacher_id" class="block mb-1">المعلم</label>
                    <select id="teacher_id" name="teacher_id" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200">
                        <option value="0">الكل</option>
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?= $teacher['id'] ?>" <?= (isset($_GET['teacher_id']) && $_GET['teacher_id'] == $teacher['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($teacher['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- الصف الثالث - فلترة نوع الزائر وتاريخ الزيارة -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="visitor_type_id" class="block mb-1">نوع الزائر</label>
                    <select id="visitor_type_id" name="visitor_type_id" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200">
                        <option value="0">الكل</option>
                        <?php foreach ($visitor_types as $type): ?>
                            <option value="<?= $type['id'] ?>" <?= (isset($_GET['visitor_type_id']) && $_GET['visitor_type_id'] == $type['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="visit_date_from" class="block mb-1">تاريخ الزيارة (من)</label>
                    <input type="date" id="visit_date_from" name="visit_date_from" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200"
                           value="<?= isset($_GET['visit_date_from']) ? htmlspecialchars($_GET['visit_date_from']) : '' ?>">
                </div>
                
                <div>
                    <label for="visit_date_to" class="block mb-1">تاريخ الزيارة (إلى)</label>
                    <input type="date" id="visit_date_to" name="visit_date_to" class="w-full border border-gray-300 shadow-sm rounded-md focus:border-primary-500 focus:ring focus:ring-primary-200"
                           value="<?= isset($_GET['visit_date_to']) ? htmlspecialchars($_GET['visit_date_to']) : '' ?>">
                </div>
            </div>
            
            <div class="flex items-end">
                <button type="submit" name="search" value="1" class="bg-primary-600 text-white px-4 py-2 rounded-md hover:bg-primary-700 transition-colors">
                    <i class="fas fa-search ml-1"></i>
                    بحث
                </button>
                
                <a href="visits.php" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors mr-2">
                    <i class="fas fa-redo ml-1"></i>
                    إعادة ضبط
                </a>
            </div>
        </form>
        <?php endif; ?>
    </div>
    
    <!-- رسالة تنبيه إذا وجدت -->
    <?php if (!empty($alert_message)): ?>
        <?= $alert_message ?>
    <?php endif; ?>
    
    <!-- جدول الزيارات -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between mb-4">
            <h2 class="text-lg font-semibold">قائمة الزيارات</h2>
            <a href="evaluation_form.php" class="bg-primary-600 text-white px-4 py-2 rounded-md hover:bg-primary-700 transition-colors">
                <i class="fas fa-plus ml-1"></i>
                إضافة زيارة جديدة
            </a>
        </div>
        
        <?php if (empty($visits)): ?>
            <p class="text-gray-500">لا توجد زيارات مسجلة.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="px-4 py-2 border text-right">#</th>
                            <th class="px-4 py-2 border text-right">التاريخ</th>
                            <th class="px-4 py-2 border text-right">المعلم</th>
                            <th class="px-4 py-2 border text-right">المادة</th>
                            <th class="px-4 py-2 border text-right">الصف</th>
                            <th class="px-4 py-2 border text-right">الشعبة</th>
                            <th class="px-4 py-2 border text-right">المدرسة</th>
                            <th class="px-4 py-2 border text-right">الزائر</th>
                            <th class="px-4 py-2 border text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($visits as $index => $visit): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 border"><?= $offset + $index + 1 ?></td>
                                <td class="px-4 py-2 border"><?= date('Y-m-d', strtotime($visit['visit_date'])) ?></td>
                                <td class="px-4 py-2 border">
                                    <a href="class_performance_report.php?teacher_id=<?= $visit['teacher_id'] ?>" class="text-blue-600 hover:text-blue-800">
                                        <?= htmlspecialchars($visit['teacher_name']) ?>
                                    </a>
                                </td>
                                <td class="px-4 py-2 border">
                                    <a href="subject_detailed_report.php?subject_id=<?= $visit['subject_id'] ?>" class="text-blue-600 hover:text-blue-800">
                                        <?= htmlspecialchars($visit['subject_name']) ?>
                                    </a>
                                </td>
                                <td class="px-4 py-2 border"><?= htmlspecialchars($visit['grade_name']) ?></td>
                                <td class="px-4 py-2 border"><?= htmlspecialchars($visit['section_name']) ?></td>
                                <td class="px-4 py-2 border"><?= htmlspecialchars($visit['school_name']) ?></td>
                                <td class="px-4 py-2 border"><?= htmlspecialchars($visit['visitor_type']) ?></td>
                                <td class="px-4 py-2 border text-center">
                                    <div class="flex space-x-2 space-x-reverse justify-center">
                                        <a href="view_visit.php?id=<?= $visit['id'] ?>" class="text-blue-600 hover:text-blue-800" title="عرض">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        
                                        <?php if ($user_role_name !== 'Teacher'): ?>
                                        <a href="edit_visit.php?id=<?= $visit['id'] ?>" class="text-green-600 hover:text-green-800" title="تعديل">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        <?php endif; ?>
                                        
                                        <a href="print_visit.php?id=<?= $visit['id'] ?>" class="text-gray-600 hover:text-gray-800" title="طباعة">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                        </a>
                                        
                                        <?php 
                                        // تحديد صلاحيات الحذف حسب نوع المستخدم
                                        $can_delete = false;
                                        
                                        if (in_array($user_role_name, ['Admin', 'Director', 'Academic Deputy'])) {
                                            // المدراء يمكنهم حذف جميع الزيارات
                                            $can_delete = true;
                                        } elseif ($user_role_name === 'Supervisor') {
                                            // المشرف يمكنه حذف زياراته فقط
                                            $can_delete = ($visit['visitor_person_id'] == $user_id);
                                        } elseif ($user_role_name === 'Subject Coordinator') {
                                            // المنسق يمكنه حذف زيارات مادته فقط (منسق وموجه)
                                            $coordinator_subject = query_row("SELECT subject_id FROM coordinator_supervisors WHERE user_id = ?", [$user_id]);
                                            $can_delete = (
                                                $coordinator_subject && 
                                                $coordinator_subject['subject_id'] == $visit['subject_id'] &&
                                                in_array($visit['visitor_type'], ['منسق المادة', 'موجه المادة'])
                                            );
                                        }
                                        
                                        if ($can_delete): 
                                        ?>
                                        <a href="delete_visit.php?id=<?= $visit['id'] ?>" class="text-red-600 hover:text-red-800" 
                                           onclick="return confirm('هل أنت متأكد من حذف هذه الزيارة؟\n\nالمعلم: <?= htmlspecialchars($visit['teacher_name']) ?>\nالمادة: <?= htmlspecialchars($visit['subject_name']) ?>\nنوع الزائر: <?= htmlspecialchars($visit['visitor_type']) ?>');" title="حذف">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </a>
                                        <?php else: ?>
                                        <span class="text-gray-400" title="لا توجد صلاحية للحذف">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636" />
                                            </svg>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
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
</div>

<!-- إضافة سكريبت للفلترة التفاعلية -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // الحصول على عناصر القوائم المنسدلة
    const schoolSelect = document.getElementById('school_id');
    const subjectSelect = document.getElementById('subject_id');
    const teacherSelect = document.getElementById('teacher_id');
    
    // تحديث قائمة المواد عند تغيير المدرسة
    schoolSelect.addEventListener('change', function() {
        const schoolId = this.value;
        
        // تحديث قائمة المواد الدراسية
        fetch(`api/get_subjects_by_school.php?school_id=${schoolId}`)
            .then(response => response.json())
            .then(data => {
                // إعادة بناء قائمة المواد
                subjectSelect.innerHTML = '<option value="0">الكل</option>';
                data.forEach(subject => {
                    subjectSelect.innerHTML += `<option value="${subject.id}">${subject.name}</option>`;
                });
                
                // إعادة تحديث قائمة المعلمين
                updateTeachersList(schoolId, 0);
            })
            .catch(error => console.error('خطأ في جلب المواد الدراسية:', error));
    });
    
    // تحديث قائمة المعلمين عند تغيير المادة
    subjectSelect.addEventListener('change', function() {
        const schoolId = schoolSelect.value;
        const subjectId = this.value;
        
        // تحديث قائمة المعلمين
        updateTeachersList(schoolId, subjectId);
    });
    
    // دالة لتحديث قائمة المعلمين
    function updateTeachersList(schoolId, subjectId) {
        fetch(`api/get_teachers_by_school_subject.php?school_id=${schoolId}&subject_id=${subjectId}`)
            .then(response => response.json())
            .then(data => {
                // إعادة بناء قائمة المعلمين
                teacherSelect.innerHTML = '<option value="0">الكل</option>';
                data.forEach(teacher => {
                    teacherSelect.innerHTML += `<option value="${teacher.id}">${teacher.name}</option>`;
                });
            })
            .catch(error => console.error('خطأ في جلب المعلمين:', error));
    }
});
</script>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?> 