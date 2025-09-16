<?php
require_once 'includes/auth_functions.php';
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// حماية الصفحة
protect_page(['E-Learning Coordinator', 'Admin', 'Director', 'Academic Deputy']);

$page_title = 'تقارير نظام قطر';

// الحصول على السنة الدراسية الحالية
$current_year = get_active_academic_year();
$current_year_id = $current_year ? $current_year['id'] : 2;

// معاملات الفلترة
$academic_year_id = $_GET['academic_year_id'] ?? $current_year_id;
$term = $_GET['term'] ?? '';
$subject_id = $_GET['subject_id'] ?? '';
$teacher_id = $_GET['teacher_id'] ?? '';
$performance_level = $_GET['performance_level'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// جلب البيانات للفلاتر
$academic_years = query("SELECT * FROM academic_years ORDER BY name DESC");
$subjects = query("SELECT * FROM subjects ORDER BY name");
$teachers = query("SELECT * FROM teachers ORDER BY name");

// بناء استعلام الفلترة
$where_conditions = ["qsp.academic_year_id = ?"];
$params = [$academic_year_id];

if ($term) {
    $where_conditions[] = "qsp.term = ?";
    $params[] = $term;
}

if ($subject_id) {
    $where_conditions[] = "qsp.subject_id = ?";
    $params[] = $subject_id;
}

if ($teacher_id) {
    $where_conditions[] = "qsp.teacher_id = ?";
    $params[] = $teacher_id;
}

if ($performance_level) {
    $where_conditions[] = "qsp.performance_level = ?";
    $params[] = $performance_level;
}

if ($date_from) {
    $where_conditions[] = "qsp.evaluation_date >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "qsp.evaluation_date <= ?";
    $params[] = $date_to;
}

$where_clause = implode(' AND ', $where_conditions);

// جلب البيانات مع الفلاتر
try {
    $evaluation_records = query("
        SELECT 
            qsp.*,
            t.name as teacher_name,
            s.name as subject_name,
            ay.name as academic_year_name
        FROM qatar_system_performance qsp
        JOIN teachers t ON qsp.teacher_id = t.id
        JOIN subjects s ON qsp.subject_id = s.id
        JOIN academic_years ay ON qsp.academic_year_id = ay.id
        WHERE {$where_clause}
        ORDER BY qsp.evaluation_date DESC
    ", $params);
} catch (Exception $e) {
    $evaluation_records = [];
    $error_message = "خطأ في جلب البيانات: " . $e->getMessage();
}
?>

<?php include 'includes/elearning_header.php'; ?>

<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4">
        <!-- العنوان -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-chart-line ml-2"></i>
                تقارير نظام قطر للتعليم
            </h1>
            <nav class="text-sm text-gray-600">
                <a href="elearning_coordinator_dashboard.php" class="hover:text-blue-600">لوحة التحكم</a>
                <span class="mx-2">/</span>
                <span>تقارير نظام قطر</span>
            </nav>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-600 ml-2"></i>
                    <span class="text-red-800"><?= htmlspecialchars($error_message) ?></span>
                </div>
            </div>
        <?php endif; ?>

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
            <h2 class="text-lg font-bold text-gray-800 mb-4">
                <i class="fas fa-filter ml-2"></i>
                فلاتر التقرير
            </h2>
            
            <form method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- السنة الدراسية -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">السنة الدراسية</label>
                        <select name="academic_year_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <?php foreach ($academic_years as $year): ?>
                                <option value="<?= $year['id'] ?>" <?= $year['id'] == $academic_year_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($year['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- الفصل الدراسي -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">الفصل الدراسي</label>
                        <select name="term" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">جميع الفصول</option>
                            <option value="الأول" <?= $term === 'الأول' ? 'selected' : '' ?>>الأول</option>
                            <option value="الثاني" <?= $term === 'الثاني' ? 'selected' : '' ?>>الثاني</option>
                            <option value="الثالث" <?= $term === 'الثالث' ? 'selected' : '' ?>>الثالث</option>
                        </select>
                    </div>

                    <!-- المادة -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">المادة</label>
                        <select name="subject_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">جميع المواد</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= $subject['id'] ?>" <?= $subject['id'] == $subject_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($subject['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- المعلم -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">المعلم</label>
                        <select name="teacher_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">جميع المعلمين</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= $teacher['id'] ?>" <?= $teacher['id'] == $teacher_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($teacher['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- مستوى الأداء -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">مستوى الأداء</label>
                        <select name="performance_level" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">جميع المستويات</option>
                            <option value="excellent" <?= $performance_level === 'excellent' ? 'selected' : '' ?>>ممتاز</option>
                            <option value="very_good" <?= $performance_level === 'very_good' ? 'selected' : '' ?>>جيد جداً</option>
                            <option value="good" <?= $performance_level === 'good' ? 'selected' : '' ?>>جيد</option>
                            <option value="needs_improvement" <?= $performance_level === 'needs_improvement' ? 'selected' : '' ?>>يحتاج تحسين</option>
                            <option value="poor" <?= $performance_level === 'poor' ? 'selected' : '' ?>>ضعيف</option>
                        </select>
                    </div>

                    <!-- التاريخ من -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">من تاريخ</label>
                        <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- التاريخ إلى -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">إلى تاريخ</label>
                        <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <!-- أزرار البحث -->
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                            <i class="fas fa-search ml-2"></i>
                            تصفية النتائج
                        </button>
                    </div>
                    
                    <div class="flex items-end">
                        <a href="?" class="w-full bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 text-center transition-colors">
                            <i class="fas fa-times ml-2"></i>
                            إعادة تعيين
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- الإحصائيات السريعة -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-clipboard-check text-xl"></i>
                    </div>
                    <div class="mr-4">
                        <h3 class="text-lg font-semibold text-gray-900"><?= count($evaluation_records) ?></h3>
                        <p class="text-sm text-gray-600">إجمالي التقييمات</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <div class="mr-4">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <?= count(array_unique(array_column($evaluation_records, 'teacher_id'))) ?>
                        </h3>
                        <p class="text-sm text-gray-600">معلم مقيّم</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-book text-xl"></i>
                    </div>
                    <div class="mr-4">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <?= count(array_unique(array_column($evaluation_records, 'subject_id'))) ?>
                        </h3>
                        <p class="text-sm text-gray-600">مادة دراسية</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                        <i class="fas fa-star text-xl"></i>
                    </div>
                    <div class="mr-4">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <?= !empty($evaluation_records) ? number_format(array_sum(array_column($evaluation_records, 'total_score')) / count($evaluation_records), 1) : '0.0' ?>
                        </h3>
                        <p class="text-sm text-gray-600">متوسط الدرجات</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- جدول التقييمات -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-bold text-gray-800">تقييمات نظام قطر</h2>
                <div class="flex gap-2">
                    <a href="qatar_system_evaluation.php" 
                       class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm transition-colors">
                        <i class="fas fa-plus ml-2"></i>
                        إضافة تقييم جديد
                    </a>
                </div>
            </div>
            
            <?php if (empty($evaluation_records)): ?>
                <div class="p-6 text-center text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-4"></i>
                    <p>لا توجد تقييمات تطابق المعايير المحددة</p>
                    <p class="text-sm mt-2">جرب تغيير الفلاتر أو إضافة تقييم جديد</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">التاريخ</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المعلم</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المادة</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الفصل</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الدرجة</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">مستوى الأداء</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php 
                            $counter = 1;
                            foreach ($evaluation_records as $record): 
                            ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                        <?= $counter++ ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= date('Y/m/d', strtotime($record['evaluation_date'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars($record['teacher_name']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars($record['subject_name']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars($record['term']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="font-medium text-blue-600"><?= number_format($record['total_score'], 1) ?></span>
                                        <?php if (isset($record['criteria_count']) && $record['criteria_count'] > 0): ?>
                                            <span class="text-gray-500">/ <?= $record['criteria_count'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php
                                        $level_colors = [
                                            'excellent' => 'bg-green-100 text-green-800',
                                            'very_good' => 'bg-blue-100 text-blue-800',
                                            'good' => 'bg-yellow-100 text-yellow-800',
                                            'needs_improvement' => 'bg-orange-100 text-orange-800',
                                            'poor' => 'bg-red-100 text-red-800'
                                        ];
                                        $level_labels = [
                                            'excellent' => 'ممتاز',
                                            'very_good' => 'جيد جداً',
                                            'good' => 'جيد',
                                            'needs_improvement' => 'يحتاج تحسين',
                                            'poor' => 'ضعيف'
                                        ];
                                        ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $level_colors[$record['performance_level']] ?? 'bg-gray-100 text-gray-800' ?>">
                                            <?= $level_labels[$record['performance_level']] ?? $record['performance_level'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex space-x-2 rtl:space-x-reverse">
                                            <!-- عرض -->
                                            <a href="qatar_system_view.php?id=<?= $record['id'] ?>" 
                                               class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded hover:bg-blue-200 transition-colors"
                                               title="عرض التفاصيل">
                                                <i class="fas fa-eye ml-1"></i>
                                                عرض
                                            </a>
                                            
                                            <!-- طباعة -->
                                            <a href="qatar_system_print.php?id=<?= $record['id'] ?>" 
                                               class="inline-flex items-center px-2 py-1 bg-purple-100 text-purple-700 text-xs font-medium rounded hover:bg-purple-200 transition-colors"
                                               title="طباعة التقييم"
                                               target="_blank">
                                                <i class="fas fa-print ml-1"></i>
                                                طباعة
                                            </a>
                                            
                                            <!-- تعديل -->
                                            <a href="qatar_system_edit.php?id=<?= $record['id'] ?>" 
                                               class="inline-flex items-center px-2 py-1 bg-green-100 text-green-700 text-xs font-medium rounded hover:bg-green-200 transition-colors"
                                               title="تعديل التقييم">
                                                <i class="fas fa-edit ml-1"></i>
                                                تعديل
                                            </a>
                                            
                                            <!-- حذف -->
                                            <button onclick="confirmDelete(<?= $record['id'] ?>, '<?= htmlspecialchars($record['teacher_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($record['subject_name'], ENT_QUOTES) ?>', '<?= date('Y/m/d', strtotime($record['evaluation_date'])) ?>')" 
                                                    class="inline-flex items-center px-2 py-1 bg-red-100 text-red-700 text-xs font-medium rounded hover:bg-red-200 transition-colors"
                                                    title="حذف التقييم">
                                                <i class="fas fa-trash ml-1"></i>
                                                حذف
                                            </button>
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
</div>

<script>
    // وظيفة تأكيد الحذف
    function confirmDelete(id, teacherName, subjectName, evaluationDate) {
        const message = `هل أنت متأكد من حذف هذا التقييم؟\n\nالمعلم: ${teacherName}\nالمادة: ${subjectName}\nالتاريخ: ${evaluationDate}\n\nتحذير: هذا الإجراء لا يمكن التراجع عنه!`;
        
        if (confirm(message)) {
            // إرسال طلب الحذف
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'qatar_system_delete.php';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = id;
            
            const confirmInput = document.createElement('input');
            confirmInput.type = 'hidden';
            confirmInput.name = 'confirm';
            confirmInput.value = '1';
            
            form.appendChild(idInput);
            form.appendChild(confirmInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    // تحسين تجربة المستخدم - إظهار loading عند الإرسال
    document.querySelector('form').addEventListener('submit', function() {
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i>جاري التحديث...';
        submitBtn.disabled = true;
        
        // إعادة تفعيل الزر في حالة فشل الإرسال
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 5000);
    });
</script>

<?php include 'includes/elearning_footer.php'; ?>
