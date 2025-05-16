<?php
// بدء التخزين المؤقت للمخرجات
ob_start();

// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// تعيين عنوان الصفحة
$page_title = 'إدارة الأعوام الدراسية';
$current_page = 'academic_years_management.php';

// معالجة النموذج
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_year'])) {
            // إضافة عام دراسي جديد
            $name = $_POST['name'] ?? '';
            $first_term_start = $_POST['first_term_start'] ?? '';
            $first_term_end = $_POST['first_term_end'] ?? '';
            $second_term_start = $_POST['second_term_start'] ?? '';
            $second_term_end = $_POST['second_term_end'] ?? '';
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if (empty($name)) {
                throw new Exception("الرجاء إدخال اسم العام الدراسي");
            }
            
            // تنسيق التواريخ
            $first_term_start = !empty($first_term_start) ? date('Y-m-d', strtotime($first_term_start)) : null;
            $first_term_end = !empty($first_term_end) ? date('Y-m-d', strtotime($first_term_end)) : null;
            $second_term_start = !empty($second_term_start) ? date('Y-m-d', strtotime($second_term_start)) : null;
            $second_term_end = !empty($second_term_end) ? date('Y-m-d', strtotime($second_term_end)) : null;
            
            // إذا تم تحديد العام كنشط، نجعل جميع الأعوام الأخرى غير نشطة
            if ($is_active) {
                execute("UPDATE academic_years SET is_active = 0");
            }
            
            // إضافة العام الدراسي الجديد
            execute("
                INSERT INTO academic_years (name, first_term_start, first_term_end, second_term_start, second_term_end, is_active, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ", [$name, $first_term_start, $first_term_end, $second_term_start, $second_term_end, $is_active]);
            
            $message = "تم إضافة العام الدراسي بنجاح";
            $message_type = "success";
        }
        elseif (isset($_POST['update_year'])) {
            // تحديث عام دراسي موجود
            $id = $_POST['id'] ?? 0;
            $name = $_POST['name'] ?? '';
            $first_term_start = $_POST['first_term_start'] ?? '';
            $first_term_end = $_POST['first_term_end'] ?? '';
            $second_term_start = $_POST['second_term_start'] ?? '';
            $second_term_end = $_POST['second_term_end'] ?? '';
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if (empty($name)) {
                throw new Exception("الرجاء إدخال اسم العام الدراسي");
            }
            
            // تنسيق التواريخ
            $first_term_start = !empty($first_term_start) ? date('Y-m-d', strtotime($first_term_start)) : null;
            $first_term_end = !empty($first_term_end) ? date('Y-m-d', strtotime($first_term_end)) : null;
            $second_term_start = !empty($second_term_start) ? date('Y-m-d', strtotime($second_term_start)) : null;
            $second_term_end = !empty($second_term_end) ? date('Y-m-d', strtotime($second_term_end)) : null;
            
            // إذا تم تحديد العام كنشط، نجعل جميع الأعوام الأخرى غير نشطة
            if ($is_active) {
                execute("UPDATE academic_years SET is_active = 0");
            }
            
            // تحديث العام الدراسي
            execute("
                UPDATE academic_years
                SET name = ?, first_term_start = ?, first_term_end = ?, 
                    second_term_start = ?, second_term_end = ?, is_active = ?, updated_at = NOW()
                WHERE id = ?
            ", [$name, $first_term_start, $first_term_end, $second_term_start, $second_term_end, $is_active, $id]);
            
            $message = "تم تحديث العام الدراسي بنجاح";
            $message_type = "success";
        }
        elseif (isset($_POST['delete_year'])) {
            // حذف عام دراسي
            $id = $_POST['id'] ?? 0;
            
            // التحقق من عدم وجود زيارات مرتبطة بهذا العام الدراسي
            $visits_count = query_row("SELECT COUNT(*) as count FROM visits WHERE academic_year_id = ?", [$id]);
            
            if ($visits_count && $visits_count['count'] > 0) {
                throw new Exception("لا يمكن حذف العام الدراسي لأنه مرتبط بزيارات صفية");
            }
            
            execute("DELETE FROM academic_years WHERE id = ?", [$id]);
            
            $message = "تم حذف العام الدراسي بنجاح";
            $message_type = "success";
        }
        elseif (isset($_POST['set_active'])) {
            // تعيين العام الدراسي النشط
            $id = $_POST['id'] ?? 0;
            
            execute("UPDATE academic_years SET is_active = 0");
            execute("UPDATE academic_years SET is_active = 1 WHERE id = ?", [$id]);
            
            $message = "تم تعيين العام الدراسي النشط بنجاح";
            $message_type = "success";
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = "error";
    }
}

// جلب جميع الأعوام الدراسية
$academic_years = query("SELECT * FROM academic_years ORDER BY is_active DESC, name DESC");

// تضمين ملف رأس الصفحة
require_once 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">إدارة الأعوام الدراسية</h1>
    
    <?php if ($message): ?>
        <div class="mb-6 <?= $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?> p-4 rounded-md">
            <?= $message ?>
        </div>
    <?php endif; ?>
    
    <!-- نموذج إضافة عام دراسي جديد -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-bold mb-4">إضافة عام دراسي جديد</h2>
        
        <form action="" method="post" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block mb-1 font-medium">اسم العام الدراسي</label>
                    <input type="text" id="name" name="name" class="w-full border rounded-md px-3 py-2" placeholder="مثال: 2024/2025" required>
                </div>
                
                <div>
                    <label class="block mb-3 font-medium">الحالة</label>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" class="text-primary-600">
                        <span class="mr-2">عام دراسي نشط</span>
                    </label>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="first_term_start" class="block mb-1 font-medium">بداية الفصل الدراسي الأول</label>
                    <input type="date" id="first_term_start" name="first_term_start" class="w-full border rounded-md px-3 py-2">
                </div>
                
                <div>
                    <label for="first_term_end" class="block mb-1 font-medium">نهاية الفصل الدراسي الأول</label>
                    <input type="date" id="first_term_end" name="first_term_end" class="w-full border rounded-md px-3 py-2">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="second_term_start" class="block mb-1 font-medium">بداية الفصل الدراسي الثاني</label>
                    <input type="date" id="second_term_start" name="second_term_start" class="w-full border rounded-md px-3 py-2">
                </div>
                
                <div>
                    <label for="second_term_end" class="block mb-1 font-medium">نهاية الفصل الدراسي الثاني</label>
                    <input type="date" id="second_term_end" name="second_term_end" class="w-full border rounded-md px-3 py-2">
                </div>
            </div>
            
            <div>
                <button type="submit" name="add_year" class="bg-primary-600 text-white px-4 py-2 rounded-md hover:bg-primary-700">
                    إضافة العام الدراسي
                </button>
            </div>
        </form>
    </div>
    
    <!-- جدول الأعوام الدراسية -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold mb-4">الأعوام الدراسية</h2>
        
        <?php if (empty($academic_years)): ?>
            <p class="text-gray-500">لا توجد أعوام دراسية مضافة بعد.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border p-2 text-right">العام الدراسي</th>
                            <th class="border p-2 text-center">الفصل الأول</th>
                            <th class="border p-2 text-center">الفصل الثاني</th>
                            <th class="border p-2 text-center">الحالة</th>
                            <th class="border p-2 text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($academic_years as $year): ?>
                            <tr>
                                <td class="border p-2"><?= htmlspecialchars($year['name']) ?></td>
                                <td class="border p-2 text-center">
                                    <?php if ($year['first_term_start'] && $year['first_term_end']): ?>
                                        <?= date('d/m/Y', strtotime($year['first_term_start'])) ?> - <?= date('d/m/Y', strtotime($year['first_term_end'])) ?>
                                    <?php else: ?>
                                        <span class="text-gray-400">غير محدد</span>
                                    <?php endif; ?>
                                </td>
                                <td class="border p-2 text-center">
                                    <?php if ($year['second_term_start'] && $year['second_term_end']): ?>
                                        <?= date('d/m/Y', strtotime($year['second_term_start'])) ?> - <?= date('d/m/Y', strtotime($year['second_term_end'])) ?>
                                    <?php else: ?>
                                        <span class="text-gray-400">غير محدد</span>
                                    <?php endif; ?>
                                </td>
                                <td class="border p-2 text-center">
                                    <?php if ($year['is_active']): ?>
                                        <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-sm">نشط</span>
                                    <?php else: ?>
                                        <form method="post" class="inline">
                                            <input type="hidden" name="id" value="<?= $year['id'] ?>">
                                            <button type="submit" name="set_active" class="text-blue-600 hover:text-blue-800">
                                                تعيين كنشط
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td class="border p-2 text-center">
                                    <button type="button" onclick="openEditModal(<?= htmlspecialchars(json_encode($year)) ?>)" class="text-blue-600 hover:text-blue-800 ml-2">
                                        تعديل
                                    </button>
                                    
                                    <form method="post" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا العام الدراسي؟');">
                                        <input type="hidden" name="id" value="<?= $year['id'] ?>">
                                        <button type="submit" name="delete_year" class="text-red-600 hover:text-red-800 mr-2">
                                            حذف
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- نموذج تعديل العام الدراسي -->
<div id="editModal" class="fixed inset-0 flex items-center justify-center z-50 hidden bg-black bg-opacity-50">
    <div class="bg-white w-full max-w-2xl p-6 rounded-lg shadow-xl">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">تعديل العام الدراسي</h2>
            <button type="button" onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
                &times;
            </button>
        </div>
        
        <form id="editForm" method="post" class="space-y-4">
            <input type="hidden" id="edit_id" name="id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="edit_name" class="block mb-1 font-medium">اسم العام الدراسي</label>
                    <input type="text" id="edit_name" name="name" class="w-full border rounded-md px-3 py-2" required>
                </div>
                
                <div>
                    <label class="block mb-3 font-medium">الحالة</label>
                    <label class="flex items-center">
                        <input type="checkbox" id="edit_is_active" name="is_active" value="1" class="text-primary-600">
                        <span class="mr-2">عام دراسي نشط</span>
                    </label>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="edit_first_term_start" class="block mb-1 font-medium">بداية الفصل الدراسي الأول</label>
                    <input type="date" id="edit_first_term_start" name="first_term_start" class="w-full border rounded-md px-3 py-2">
                </div>
                
                <div>
                    <label for="edit_first_term_end" class="block mb-1 font-medium">نهاية الفصل الدراسي الأول</label>
                    <input type="date" id="edit_first_term_end" name="first_term_end" class="w-full border rounded-md px-3 py-2">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="edit_second_term_start" class="block mb-1 font-medium">بداية الفصل الدراسي الثاني</label>
                    <input type="date" id="edit_second_term_start" name="second_term_start" class="w-full border rounded-md px-3 py-2">
                </div>
                
                <div>
                    <label for="edit_second_term_end" class="block mb-1 font-medium">نهاية الفصل الدراسي الثاني</label>
                    <input type="date" id="edit_second_term_end" name="second_term_end" class="w-full border rounded-md px-3 py-2">
                </div>
            </div>
            
            <div>
                <button type="submit" name="update_year" class="bg-primary-600 text-white px-4 py-2 rounded-md hover:bg-primary-700">
                    حفظ التغييرات
                </button>
                <button type="button" onclick="closeEditModal()" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-400 mr-2">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toISOString().split('T')[0]; // Convert to YYYY-MM-DD format for input
    }

    function openEditModal(year) {
        document.getElementById('edit_id').value = year.id;
        document.getElementById('edit_name').value = year.name;
        document.getElementById('edit_is_active').checked = year.is_active == 1;
        document.getElementById('edit_first_term_start').value = formatDate(year.first_term_start);
        document.getElementById('edit_first_term_end').value = formatDate(year.first_term_end);
        document.getElementById('edit_second_term_start').value = formatDate(year.second_term_start);
        document.getElementById('edit_second_term_end').value = formatDate(year.second_term_end);
        
        document.getElementById('editModal').classList.remove('hidden');
    }
    
    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }
</script>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?> 