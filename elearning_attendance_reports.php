<?php
/**
 * صفحة تقارير الزيارات الصفية
 */

require_once 'includes/auth_functions.php';
require_once 'includes/functions.php';

// حماية الصفحة لمنسقي التعليم الإلكتروني
protect_page(['E-Learning Coordinator', 'Admin', 'Director', 'Academic Deputy']);

$page_title = 'تقارير الزيارات الصفية';

// الحصول على السنة الدراسية الحالية
$current_year = get_active_academic_year();
$current_year_id = $current_year ? $current_year['id'] : 2;

// المعاملات
$academic_year_id = $_GET['academic_year_id'] ?? $current_year_id;
$term = $_GET['term'] ?? '';
$subject_id = $_GET['subject_id'] ?? '';
$teacher_id = $_GET['teacher_id'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// جلب البيانات للفلاتر
$academic_years = query("SELECT * FROM academic_years ORDER BY name DESC");
$subjects = query("SELECT * FROM subjects ORDER BY name");
$teachers = query("SELECT * FROM teachers ORDER BY name");

// بناء استعلام التقرير
$where_conditions = ["ea.academic_year_id = ?"];
$params = [$academic_year_id];

if ($term) {
    $where_conditions[] = "ea.term = ?";
    $params[] = $term;
}

if ($subject_id) {
    $where_conditions[] = "ea.subject_id = ?";
    $params[] = $subject_id;
}

if ($teacher_id) {
    $where_conditions[] = "ea.teacher_id = ?";
    $params[] = $teacher_id;
}

if ($date_from) {
    $where_conditions[] = "ea.lesson_date >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "ea.lesson_date <= ?";
    $params[] = $date_to;
}

$where_clause = implode(' AND ', $where_conditions);

// جلب بيانات التقرير
$attendance_records = query("
    SELECT 
        ea.*,
        t.name as teacher_name,
        s.name as subject_name,
        ay.name as academic_year_name,
        g.name as grade_name,
        sec.name as section_name,
        sch.name as school_name,
        (ea.attendance_students * 100.0 / ea.num_students) as attendance_percentage
    FROM elearning_attendance ea
    JOIN teachers t ON ea.teacher_id = t.id
    JOIN subjects s ON ea.subject_id = s.id
    JOIN academic_years ay ON ea.academic_year_id = ay.id
    LEFT JOIN grades g ON ea.grade_id = g.id
    LEFT JOIN sections sec ON ea.section_id = sec.id
    LEFT JOIN schools sch ON ea.school_id = sch.id
    WHERE {$where_clause}
    ORDER BY ea.lesson_date DESC
", $params);

// إحصائيات التقرير
$summary_stats = [];
if (!empty($attendance_records)) {
    $total_sessions = count($attendance_records);
    $total_students = array_sum(array_column($attendance_records, 'num_students'));
    $total_present = array_sum(array_column($attendance_records, 'attendance_students'));
    $avg_attendance = $total_students > 0 ? ($total_present / $total_students) * 100 : 0;
    
    $summary_stats = [
        'total_sessions' => $total_sessions,
        'total_students' => $total_students,
        'total_present' => $total_present,
        'avg_attendance' => $avg_attendance,
        'unique_teachers' => count(array_unique(array_column($attendance_records, 'teacher_id'))),
        'unique_subjects' => count(array_unique(array_column($attendance_records, 'subject_id')))
    ];
}

// إحصائيات حسب المادة
$subject_stats = [];
if (!empty($attendance_records)) {
    foreach ($attendance_records as $record) {
        $subject = $record['subject_name'];
        if (!isset($subject_stats[$subject])) {
            $subject_stats[$subject] = [
                'sessions' => 0,
                'total_students' => 0,
                'attendance_students' => 0,
                'attendance_percentage' => 0
            ];
        }
        $subject_stats[$subject]['sessions']++;
        $subject_stats[$subject]['total_students'] += $record['num_students'];
        $subject_stats[$subject]['attendance_students'] += $record['attendance_students'];
    }
    
    foreach ($subject_stats as $subject => &$stats) {
        $stats['attendance_percentage'] = $stats['total_students'] > 0 
            ? ($stats['attendance_students'] / $stats['total_students']) * 100 
            : 0;
    }
}
?>

<?php include 'includes/elearning_header.php'; ?>

<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4">
        <!-- العنوان -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-chart-bar ml-2"></i>
                تقارير الزيارات الصفية
            </h1>
            <nav class="text-sm text-gray-600">
                <a href="elearning_coordinator_dashboard.php" class="hover:text-blue-600">لوحة التحكم</a>
                <span class="mx-2">/</span>
                <span>تقارير الزيارات</span>
            </nav>
        </div>

        <!-- رسائل النجاح والأخطاء -->
        <?php if (isset($_GET['success'])): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 ml-2"></i>
                    <span class="text-green-800"><?= htmlspecialchars($_GET['success']) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-600 ml-2"></i>
                    <span class="text-red-800"><?= htmlspecialchars($_GET['error']) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- فلاتر التقرير -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4">فلاتر التقرير</h2>
            
            <form method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">السنة الدراسية</label>
                        <select name="academic_year_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <?php foreach ($academic_years as $year): ?>
                                <option value="<?= $year['id'] ?>" <?= ($year['id'] == $academic_year_id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($year['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">الفصل الدراسي</label>
                        <select name="term" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">كل الفصول</option>
                            <option value="first" <?= $term == 'first' ? 'selected' : '' ?>>الفصل الأول</option>
                            <option value="second" <?= $term == 'second' ? 'selected' : '' ?>>الفصل الثاني</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">المادة</label>
                        <select name="subject_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">كل المواد</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= $subject['id'] ?>" <?= ($subject['id'] == $subject_id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($subject['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">المعلم</label>
                        <select name="teacher_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">كل المعلمين</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= $teacher['id'] ?>" <?= ($teacher['id'] == $teacher_id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($teacher['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">من تاريخ</label>
                        <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">إلى تاريخ</label>
                        <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="flex justify-end space-x-reverse space-x-4">
                    <a href="?" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded transition duration-200">
                        إعادة تعيين
                    </a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded transition duration-200">
                        <i class="fas fa-search ml-2"></i>
                        بحث
                    </button>
                </div>
            </form>
        </div>

        <!-- عدد الجلسات حسب المادة -->
        <?php if (!empty($subject_stats)): ?>
        <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-chart-bar ml-2"></i>
                إجمالي الجلسات حسب المادة
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <?php foreach ($subject_stats as $subject => $stats): ?>
                    <div class="bg-white rounded-lg shadow p-6 border-r-4 border-blue-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 mb-1">
                                    <?= htmlspecialchars($subject) ?>
                                </h3>
                                <p class="text-sm text-gray-600">زيارة صفية</p>
                            </div>
                            <div class="text-left">
                                <div class="text-3xl font-bold text-blue-600">
                                    <?= number_format($stats['sessions']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- جدول التفاصيل -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-800">تفاصيل الزيارات الصفية</h2>
            </div>
            
            <?php if (empty($attendance_records)): ?>
                <div class="p-6 text-center text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-4"></i>
                    <p>لا توجد سجلات حضور تطابق المعايير المحددة</p>
                </div>
            <?php else: ?>
                <div class="overflow-hidden">
                    <table class="w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">التاريخ</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المعلم</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المادة</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الصف</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نوع الحضور</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحضور</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">النسبة</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">التقييم</th>
                                <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($attendance_records as $record): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= date('Y/m/d', strtotime($record['lesson_date'])) ?>
                                    </td>
                                    <td class="px-3 py-4 text-sm text-gray-900">
                                        <div class="max-w-xs truncate" title="<?= htmlspecialchars($record['teacher_name']) ?>">
                                            <?= htmlspecialchars($record['teacher_name']) ?>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 text-sm text-gray-900">
                                        <div class="max-w-xs truncate" title="<?= htmlspecialchars($record['subject_name']) ?>">
                                            <?= htmlspecialchars($record['subject_name']) ?>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php
                                        // عرض معلومات الصف والشعبة
                                        $grade_name = $record['grade_name'] ?? 'غير محدد';
                                        $section_name = $record['section_name'] ?? 'غير محدد';
                                        echo htmlspecialchars($grade_name . ' - شعبة ' . $section_name);
                                        ?>
                                    </td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm">
                                        <?php
                                        $type_colors = [
                                            'direct' => 'bg-green-100 text-green-800',
                                            'remote' => 'bg-blue-100 text-blue-800'
                                        ];
                                        $type_labels = [
                                            'direct' => 'مباشر',
                                            'remote' => 'عن بُعد'
                                        ];
                                        ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $type_colors[$record['attendance_type']] ?? 'bg-gray-100 text-gray-800' ?>">
                                            <?= $type_labels[$record['attendance_type']] ?? $record['attendance_type'] ?>
                                        </span>
                                    </td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= $record['attendance_students'] ?>/<?= $record['num_students'] ?>
                                    </td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm">
                                        <?php
                                        $percentage = $record['attendance_percentage'];
                                        $color = 'text-red-600';
                                        if ($percentage >= 80) $color = 'text-green-600';
                                        elseif ($percentage >= 60) $color = 'text-yellow-600';
                                        elseif ($percentage >= 40) $color = 'text-orange-600';
                                        ?>
                                        <span class="font-medium <?= $color ?>"><?= number_format($percentage, 1) ?>%</span>
                                    </td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm">
                                        <?php
                                        $rating_colors = [
                                            'excellent' => 'bg-green-100 text-green-800',
                                            'very_good' => 'bg-blue-100 text-blue-800',
                                            'good' => 'bg-yellow-100 text-yellow-800',
                                            'acceptable' => 'bg-orange-100 text-orange-800',
                                            'poor' => 'bg-red-100 text-red-800'
                                        ];
                                        $rating_labels = [
                                            'excellent' => 'ممتاز',
                                            'very_good' => 'جيد جداً',
                                            'good' => 'جيد',
                                            'acceptable' => 'مقبول',
                                            'poor' => 'ضعيف'
                                        ];
                                        ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $rating_colors[$record['attendance_rating']] ?? 'bg-gray-100 text-gray-800' ?>">
                                            <?= $rating_labels[$record['attendance_rating']] ?? $record['attendance_rating'] ?>
                                        </span>
                                    </td>
                                    <td class="px-2 py-4 text-center text-sm text-gray-500">
                                        <div class="flex flex-col space-y-1">
                                            <!-- الصف الأول: طباعة + عرض -->
                                            <div class="flex space-x-1 rtl:space-x-reverse justify-center">
                                                <!-- طباعة -->
                                                <a href="elearning_print_attendance.php?id=<?= $record['id'] ?>" 
                                                   target="_blank"
                                                   class="inline-flex items-center px-1.5 py-1 bg-purple-100 text-purple-700 text-xs font-medium rounded hover:bg-purple-200 transition-colors"
                                                   title="طباعة الزيارة">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                                
                                                <!-- عرض -->
                                                <a href="elearning_view_attendance.php?id=<?= $record['id'] ?>" 
                                                   class="inline-flex items-center px-1.5 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded hover:bg-blue-200 transition-colors"
                                                   title="عرض التفاصيل">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                            
                                            <!-- الصف الثاني: تعديل + حذف -->
                                            <div class="flex space-x-1 rtl:space-x-reverse justify-center">
                                                <!-- تعديل -->
                                                <a href="elearning_edit_attendance.php?id=<?= $record['id'] ?>" 
                                                   class="inline-flex items-center px-1.5 py-1 bg-green-100 text-green-700 text-xs font-medium rounded hover:bg-green-200 transition-colors"
                                                   title="تعديل">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <!-- حذف -->
                                                <button onclick="confirmDelete(<?= $record['id'] ?>, '<?= htmlspecialchars($record['teacher_name']) ?>', '<?= htmlspecialchars($record['subject_name']) ?>', '<?= date('Y/m/d', strtotime($record['lesson_date'])) ?>')" 
                                                        class="inline-flex items-center px-1.5 py-1 bg-red-100 text-red-700 text-xs font-medium rounded hover:bg-red-200 transition-colors"
                                                        title="حذف">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript للرسوم البيانية -->
    <script>
        // وظيفة تأكيد الحذف
        function confirmDelete(id, teacherName, subjectName, lessonDate) {
            if (confirm(`هل أنت متأكد من حذف سجل الحضور؟\n\nالمعلم: ${teacherName}\nالمادة: ${subjectName}\nالتاريخ: ${lessonDate}`)) {
                // إرسال طلب الحذف
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'elearning_delete_attendance.php';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = id;
                
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
    
    </div>
</div>

<?php include 'includes/elearning_footer.php'; ?>
