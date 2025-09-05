<?php
$page_title = 'إدارة المستخدمين';
$app_name = 'نظام الزيارات الصفية';

// تضمين ملفات النظام
require_once 'includes/header.php';

// حماية الصفحة - للمديرين فقط
protect_page(['Admin', 'Director', 'Academic Deputy']);

// الحصول على بيانات المستخدم الحالي
$current_user_role = $_SESSION['role_name'] ?? 'admin';
$current_user_school_id = $_SESSION['school_id'] ?? null;

// معالجة النماذج
$message = '';
$error = '';

// إنشاء مستخدم جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    try {
        $user_data = [
            'username' => $_POST['username'] ?? '',
            'password' => $_POST['password'] ?? '',
            'full_name' => $_POST['full_name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'role_id' => (int)($_POST['role_id'] ?? 0),
            'school_id' => (int)($_POST['school_id'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        $result = create_user($user_data);
        if ($result['success']) {
            $message = 'تم إنشاء المستخدم بنجاح!';
        } else {
            $error = $result['message'];
        }
    } catch (Exception $e) {
        $error = 'خطأ في إنشاء المستخدم: ' . $e->getMessage();
    }
}

// تحديث مستخدم
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    try {
        $user_id = (int)$_POST['user_id'];
        $user_data = [
            'full_name' => $_POST['full_name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'role_id' => (int)($_POST['role_id'] ?? 0),
            'school_id' => (int)($_POST['school_id'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        // تحديث كلمة المرور إذا تم إدخالها
        if (!empty($_POST['password'])) {
            $user_data['password'] = $_POST['password'];
        }
        
        $result = update_user($user_id, $user_data);
        if ($result['success']) {
            $message = 'تم تحديث المستخدم بنجاح!';
        } else {
            $error = $result['message'];
        }
    } catch (Exception $e) {
        $error = 'خطأ في تحديث المستخدم: ' . $e->getMessage();
    }
}

// حذف مستخدم
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    try {
        $user_id = (int)$_POST['user_id'];
        $result = delete_user($user_id);
        if ($result['success']) {
            $message = 'تم حذف المستخدم بنجاح!';
        } else {
            $error = $result['message'];
        }
    } catch (Exception $e) {
        $error = 'خطأ في حذف المستخدم: ' . $e->getMessage();
    }
}

// جلب بيانات المستخدمين
$users_query = "
    SELECT u.*, r.name as role_name, s.name as school_name 
    FROM users u 
    LEFT JOIN user_roles r ON u.role_id = r.id 
    LEFT JOIN schools s ON u.school_id = s.id 
    ORDER BY u.created_at DESC
";

$users = query($users_query);

// جلب الأدوار والمدارس للقوائم المنسدلة
$roles = query("SELECT * FROM user_roles ORDER BY name");
$schools = query("SELECT * FROM schools ORDER BY name");
?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">إدارة المستخدمين</h1>
            <button onclick="openCreateModal()" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors">
                <i class="fas fa-plus ml-2"></i>إضافة مستخدم جديد
            </button>
        </div>

        <!-- رسائل النجاح والخطأ -->
        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- جدول المستخدمين -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المعرف</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">اسم المستخدم</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الاسم الكامل</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">البريد الإلكتروني</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الدور</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المدرسة</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاريخ الإنشاء</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $user['id'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($user['username']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($user['full_name']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($user['email'] ?? '') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($user['role_name']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($user['school_name'] ?? 'غير محدد') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($user['is_active']): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">نشط</span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">غير نشط</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= format_date_ar($user['created_at']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="openEditModal(<?= htmlspecialchars(json_encode($user)) ?>)" 
                                        class="text-blue-600 hover:text-blue-900 ml-2">
                                    <i class="fas fa-edit"></i> تعديل
                                </button>
                                <button onclick="confirmDelete(<?= $user['id'] ?>, '<?= htmlspecialchars($user['full_name']) ?>')" 
                                        class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i> حذف
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- نموذج إنشاء مستخدم جديد -->
<div id="createModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">إضافة مستخدم جديد</h3>
                <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">اسم المستخدم</label>
                    <input type="text" name="username" required 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">كلمة المرور</label>
                    <input type="password" name="password" required 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الاسم الكامل</label>
                    <input type="text" name="full_name" required 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">البريد الإلكتروني</label>
                    <input type="email" name="email" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الدور</label>
                    <select name="role_id" required 
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">اختر الدور...</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">المدرسة</label>
                    <select name="school_id" 
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">اختر المدرسة...</option>
                        <?php foreach ($schools as $school): ?>
                            <option value="<?= $school['id'] ?>"><?= htmlspecialchars($school['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active_create" checked 
                           class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="is_active_create" class="mr-2 block text-sm text-gray-900">مستخدم نشط</label>
                </div>
                
                <div class="flex justify-end space-x-2 space-x-reverse pt-4">
                    <button type="button" onclick="closeCreateModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        إلغاء
                    </button>
                    <button type="submit" name="create_user" 
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                        إنشاء المستخدم
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- نموذج تحديث المستخدم -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">تعديل المستخدم</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">اسم المستخدم</label>
                    <input type="text" id="edit_username" readonly 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">كلمة المرور الجديدة (اتركها فارغة إذا لم تريد تغييرها)</label>
                    <input type="password" name="password" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الاسم الكامل</label>
                    <input type="text" name="full_name" id="edit_full_name" required 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">البريد الإلكتروني</label>
                    <input type="email" name="email" id="edit_email" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الدور</label>
                    <select name="role_id" id="edit_role_id" required 
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">اختر الدور...</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">المدرسة</label>
                    <select name="school_id" id="edit_school_id" 
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">اختر المدرسة...</option>
                        <?php foreach ($schools as $school): ?>
                            <option value="<?= $school['id'] ?>"><?= htmlspecialchars($school['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="edit_is_active" 
                           class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="edit_is_active" class="mr-2 block text-sm text-gray-900">مستخدم نشط</label>
                </div>
                
                <div class="flex justify-end space-x-2 space-x-reverse pt-4">
                    <button type="button" onclick="closeEditModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        إلغاء
                    </button>
                    <button type="submit" name="update_user" 
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                        تحديث المستخدم
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- نموذج حذف المستخدم -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">تأكيد الحذف</h3>
                <button onclick="closeDeleteModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <p class="text-gray-700 mb-6">هل أنت متأكد من حذف المستخدم "<span id="delete_user_name"></span>"؟ هذا الإجراء لا يمكن التراجع عنه.</p>
            
            <form method="POST" class="flex justify-end space-x-2 space-x-reverse">
                <input type="hidden" name="user_id" id="delete_user_id">
                <button type="button" onclick="closeDeleteModal()" 
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    إلغاء
                </button>
                <button type="submit" name="delete_user" 
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    حذف
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('createModal').classList.remove('hidden');
}

function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
}

function openEditModal(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_full_name').value = user.full_name;
    document.getElementById('edit_email').value = user.email || '';
    document.getElementById('edit_role_id').value = user.role_id;
    document.getElementById('edit_school_id').value = user.school_id || '';
    document.getElementById('edit_is_active').checked = user.is_active == 1;
    
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function confirmDelete(userId, userName) {
    document.getElementById('delete_user_id').value = userId;
    document.getElementById('delete_user_name').textContent = userName;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}
</script>

<?php require_once 'includes/footer.php'; ?>
