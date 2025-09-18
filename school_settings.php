<?php
$page_title = 'إعدادات المدرسة';
$app_name = 'نظام الزيارات الصفية';

// تضمين ملفات النظام
require_once 'includes/header.php';

// حماية الصفحة - للمديرين فقط
protect_page(['Admin', 'Director', 'Academic Deputy']);

// معالجة النماذج
$message = '';
$error = '';

// استرجاع بيانات المدرسة الحالية
$school = query_row("SELECT * FROM schools WHERE id = 1");
if (!$school) {
    // إنشاء سجل افتراضي إذا لم يكن موجوداً
    execute("INSERT INTO schools (id, name, school_code) VALUES (1, 'اسم المدرسة', '00000')");
    $school = query_row("SELECT * FROM schools WHERE id = 1");
}

// تحديث بيانات المدرسة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_school'])) {
    try {
        $name = trim($_POST['name'] ?? '');
        $school_code = trim($_POST['school_code'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        
        // التحقق من البيانات المطلوبة
        if (empty($name) || empty($school_code)) {
            throw new Exception('اسم المدرسة وكود المدرسة مطلوبان');
        }
        
        // معالجة رفع الشعار
        $logo_path = $school['logo'] ?? '';
    
    if (isset($_FILES['logo']) && $_FILES['logo']['size'] > 0) {
        $target_dir = "uploads/logos/";
        
        // إنشاء المجلد إذا لم يكن موجوداً
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["logo"]["name"], PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($file_extension, $allowed_types)) {
                throw new Exception('نوع الملف غير مسموح. يُسمح فقط بملفات: JPG, PNG, GIF, WEBP');
            }
            
            // التحقق من حجم الملف (5MB كحد أقصى)
            if ($_FILES['logo']['size'] > 5 * 1024 * 1024) {
                throw new Exception('حجم الملف كبير جداً. الحد الأقصى 5 ميجابايت');
            }
            
        $new_file_name = "school_logo_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_file_name;
        
            if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
                // حذف الشعار القديم إذا كان موجوداً
                if (!empty($school['logo']) && file_exists($school['logo'])) {
                    unlink($school['logo']);
                }
                $logo_path = $target_file;
            } else {
                throw new Exception('فشل في رفع الشعار');
            }
        }
        
        // تحديث البيانات
        execute("
            UPDATE schools 
            SET name = ?, school_code = ?, email = ?, phone = ?, address = ?, logo = ?, updated_at = NOW()
            WHERE id = 1
        ", [$name, $school_code, $email, $phone, $address, $logo_path]);
        
        $message = 'تم حفظ بيانات المدرسة بنجاح!';
        
        // إعادة تحميل البيانات
        $school = query_row("SELECT * FROM schools WHERE id = 1");
        
    } catch (Exception $e) {
        $error = 'خطأ في حفظ البيانات: ' . $e->getMessage();
    }
}

// جلب إحصائيات المدرسة مفصلة حسب المهن
$stats = [
    // إحصائيات الموظفين من جدول المستخدمين (الأدوار الفعلية)
    'directors' => query_row("SELECT COUNT(*) as count FROM users u JOIN user_roles r ON u.role_id = r.id WHERE u.school_id = 1 AND u.is_active = 1 AND r.name = 'Director'")['count'] ?? 0,
    'academic_deputy' => query_row("SELECT COUNT(*) as count FROM users u JOIN user_roles r ON u.role_id = r.id WHERE u.school_id = 1 AND u.is_active = 1 AND r.name = 'Academic Deputy'")['count'] ?? 0,
    'coordinators' => query_row("SELECT COUNT(*) as count FROM users u JOIN user_roles r ON u.role_id = r.id WHERE u.school_id = 1 AND u.is_active = 1 AND r.name = 'Subject Coordinator'")['count'] ?? 0,
    'teachers' => query_row("SELECT COUNT(*) as count FROM users u JOIN user_roles r ON u.role_id = r.id WHERE u.school_id = 1 AND u.is_active = 1 AND r.name = 'Teacher'")['count'] ?? 0,
    'elearning_coordinators' => query_row("SELECT COUNT(*) as count FROM users u JOIN user_roles r ON u.role_id = r.id WHERE u.school_id = 1 AND u.is_active = 1 AND r.name = 'E-Learning Coordinator'")['count'] ?? 0,
    'supervisors' => query_row("SELECT COUNT(*) as count FROM users u JOIN user_roles r ON u.role_id = r.id WHERE u.school_id = 1 AND u.is_active = 1 AND r.name = 'Supervisor'")['count'] ?? 0,
    
    // إحصائيات أخرى
    'total_subjects' => query_row("SELECT COUNT(*) as count FROM subjects")['count'] ?? 0,
    'total_visits' => query_row("SELECT COUNT(*) as count FROM visits WHERE school_id = 1")['count'] ?? 0,
    'total_sections' => query_row("SELECT COUNT(*) as count FROM sections WHERE school_id = 1")['count'] ?? 0
];
?>

<div class="container mx-auto px-4 py-8">
    <!-- العنوان الرئيسي -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">إعدادات المدرسة</h1>
            <p class="text-gray-600">إدارة البيانات الأساسية للمدرسة والإعدادات العامة</p>
        </div>
        <a href="index.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
            <i class="fas fa-home ml-2"></i>الرئيسية
        </a>
        </div>
        
    <!-- رسائل النجاح والخطأ -->
    <?php if ($message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 ml-2"></i>
                <?= htmlspecialchars($message) ?>
            </div>
            </div>
        <?php endif; ?>
        
    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 ml-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            </div>
        <?php endif; ?>
        
    <!-- ملخص إجمالي الموظفين -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg shadow-lg p-6 mb-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">إجمالي الموظفين</h2>
                <p class="text-blue-100">توزيع الموظفين حسب الوظائف</p>
            </div>
            <div class="text-center">
                <div class="text-4xl font-bold">
                    <?= $stats['directors'] + $stats['academic_deputy'] + $stats['coordinators'] + $stats['teachers'] + $stats['elearning_coordinators'] + $stats['supervisors'] ?>
                </div>
                <div class="text-sm text-blue-100">موظف</div>
            </div>
        </div>
    </div>

    <!-- إحصائيات سريعة مفصلة -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
        <!-- المديرين -->
        <div class="bg-white p-6 rounded-lg shadow-md border-r-4 border-red-500">
            <div class="flex items-center">
                <div class="p-3 bg-red-100 rounded-full">
                    <i class="fas fa-user-tie text-red-600 text-xl"></i>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">المديرين</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['directors'] ?></p>
                </div>
            </div>
        </div>

        <!-- النائب الأكاديمي -->
        <div class="bg-white p-6 rounded-lg shadow-md border-r-4 border-orange-500">
            <div class="flex items-center">
                <div class="p-3 bg-orange-100 rounded-full">
                    <i class="fas fa-user-cog text-orange-600 text-xl"></i>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">النائب الأكاديمي</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['academic_deputy'] ?></p>
                </div>
            </div>
        </div>

        <!-- منسقو المواد -->
        <div class="bg-white p-6 rounded-lg shadow-md border-r-4 border-green-500">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-full">
                    <i class="fas fa-user-graduate text-green-600 text-xl"></i>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">منسقو المواد</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['coordinators'] ?></p>
                </div>
            </div>
        </div>

        <!-- المعلمين -->
        <div class="bg-white p-6 rounded-lg shadow-md border-r-4 border-blue-500">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-full">
                    <i class="fas fa-chalkboard-teacher text-blue-600 text-xl"></i>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">المعلمين</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['teachers'] ?></p>
                </div>
            </div>
        </div>

        <!-- منسق التعليم الإلكتروني -->
        <div class="bg-white p-6 rounded-lg shadow-md border-r-4 border-indigo-500">
            <div class="flex items-center">
                <div class="p-3 bg-indigo-100 rounded-full">
                    <i class="fas fa-laptop-code text-indigo-600 text-xl"></i>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">منسق التعليم الإلكتروني</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['elearning_coordinators'] ?></p>
                </div>
            </div>
        </div>

        <!-- المشرفين التربويين (الموجهين) -->
        <div class="bg-white p-6 rounded-lg shadow-md border-r-4 border-purple-500">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-full">
                    <i class="fas fa-user-check text-purple-600 text-xl"></i>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">المشرفين التربويين</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['supervisors'] ?></p>
                </div>
            </div>
                        </div>
                        
        <!-- المواد الدراسية -->
        <div class="bg-white p-6 rounded-lg shadow-md border-r-4 border-teal-500">
            <div class="flex items-center">
                <div class="p-3 bg-teal-100 rounded-full">
                    <i class="fas fa-book text-teal-600 text-xl"></i>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">المواد الدراسية</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['total_subjects'] ?></p>
                </div>
            </div>
                        </div>
                        
        <!-- الزيارات -->
        <div class="bg-white p-6 rounded-lg shadow-md border-r-4 border-amber-500">
            <div class="flex items-center">
                <div class="p-3 bg-amber-100 rounded-full">
                    <i class="fas fa-clipboard-check text-amber-600 text-xl"></i>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">الزيارات</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['total_visits'] ?></p>
                </div>
                                </div>
                            </div>
                            
        <!-- الشعب -->
        <div class="bg-white p-6 rounded-lg shadow-md border-r-4 border-pink-500">
            <div class="flex items-center">
                <div class="p-3 bg-pink-100 rounded-full">
                    <i class="fas fa-users text-pink-600 text-xl"></i>
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-600">الشعب</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['total_sections'] ?></p>
                </div>
                                </div>
                            </div>
                        </div>
                        
    <!-- بطاقة إعدادات المدرسة -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
            <h2 class="text-xl font-bold text-white flex items-center">
                <i class="fas fa-school text-white ml-3"></i>
                بيانات المدرسة الأساسية
            </h2>
        </div>

        <form method="POST" enctype="multipart/form-data" class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- البيانات الأساسية -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-school text-blue-600 ml-2"></i>
                                اسم المدرسة <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="name" name="name" required
                                   value="<?= htmlspecialchars($school['name'] ?? '') ?>"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        </div>

                        <div>
                            <label for="school_code" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-hashtag text-blue-600 ml-2"></i>
                                كود المدرسة <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="school_code" name="school_code" required
                                   value="<?= htmlspecialchars($school['school_code'] ?? '') ?>"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-envelope text-green-600 ml-2"></i>
                                البريد الإلكتروني
                            </label>
                            <input type="email" id="email" name="email"
                                   value="<?= htmlspecialchars($school['email'] ?? '') ?>"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-phone text-green-600 ml-2"></i>
                                رقم الهاتف
                            </label>
                            <input type="text" id="phone" name="phone"
                                   value="<?= htmlspecialchars($school['phone'] ?? '') ?>"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                        </div>
                    </div>
                    
                    <div>
                        <label for="address" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-map-marker-alt text-purple-600 ml-2"></i>
                            العنوان
                        </label>
                        <textarea id="address" name="address" rows="4"
                                  class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all resize-none"
                                  placeholder="أدخل عنوان المدرسة التفصيلي..."><?= htmlspecialchars($school['address'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- شعار المدرسة -->
                <div class="lg:col-span-1">
                    <div class="bg-gray-50 rounded-lg p-6 text-center h-full flex flex-col justify-center">
                        <label class="block text-sm font-semibold text-gray-700 mb-4">
                            <i class="fas fa-image text-orange-600 ml-2"></i>
                            شعار المدرسة
                        </label>
                        
                        <div class="mb-4">
                            <?php if (!empty($school['logo']) && file_exists($school['logo'])): ?>
                                <img id="logo-preview" src="<?= $school['logo'] ?>" alt="شعار المدرسة" 
                                     class="w-32 h-32 object-contain mx-auto border-2 border-gray-300 rounded-lg bg-white p-2">
                            <?php else: ?>
                                <div id="logo-preview" class="w-32 h-32 mx-auto border-2 border-dashed border-gray-300 rounded-lg bg-white flex items-center justify-center">
                                    <i class="fas fa-school text-4xl text-gray-400"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="space-y-2">
                            <input type="file" id="logo" name="logo" accept="image/*" 
                                   class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-all">
                            <p class="text-xs text-gray-500">
                                <i class="fas fa-info-circle ml-1"></i>
                                الحد الأقصى: 5 ميجابايت<br>
                                الأنواع المدعومة: JPG, PNG, GIF, WEBP
                            </p>
                        </div>
                        </div>
                    </div>
                </div>
                
            <!-- أزرار الحفظ -->
            <div class="flex justify-end mt-8 pt-6 border-t border-gray-200">
                <div class="flex space-x-3 space-x-reverse">
                    <button type="button" onclick="resetForm()" 
                            class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        <i class="fas fa-undo ml-2"></i>إعادة تعيين
                    </button>
                    <button type="submit" name="update_school" 
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold">
                        <i class="fas fa-save ml-2"></i>حفظ التغييرات
                    </button>
                </div>
                </div>
            </form>
        </div>

    <!-- معلومات إضافية -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
        <!-- معلومات النظام -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-info-circle text-blue-600 ml-2"></i>
                معلومات النظام
            </h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-gray-600">آخر تحديث:</span>
                    <span class="font-medium text-gray-800">
                        <?= $school['updated_at'] ? format_date_ar($school['updated_at']) : 'غير محدد' ?>
                    </span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-gray-600">تاريخ الإنشاء:</span>
                    <span class="font-medium text-gray-800">
                        <?= $school['created_at'] ? format_date_ar($school['created_at']) : 'غير محدد' ?>
                    </span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-gray-600">معرف المدرسة:</span>
                    <span class="font-medium text-gray-800"><?= $school['id'] ?></span>
                </div>
            </div>
        </div>

        <!-- روابط سريعة -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-link text-purple-600 ml-2"></i>
                روابط سريعة
            </h3>
            <div class="space-y-3">
                <a href="users_management.php" class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                    <i class="fas fa-users text-blue-600 ml-3"></i>
                    <span class="font-medium text-blue-800">إدارة المستخدمين</span>
                </a>
                <a href="subjects_management.php" class="flex items-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                    <i class="fas fa-book text-green-600 ml-3"></i>
                    <span class="font-medium text-green-800">إدارة المواد الدراسية</span>
                </a>
                <a href="teachers_management.php" class="flex items-center p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                    <i class="fas fa-chalkboard-teacher text-purple-600 ml-3"></i>
                    <span class="font-medium text-purple-800">إدارة المعلمين</span>
                </a>
                <a href="system_settings.php" class="flex items-center p-3 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors">
                    <i class="fas fa-cog text-orange-600 ml-3"></i>
                    <span class="font-medium text-orange-800">إعدادات النظام</span>
                </a>
            </div>
        </div>
    </div>
    </div>
    
    <script>
// معاينة الشعار قبل الرفع
        document.getElementById('logo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
        // التحقق من نوع الملف
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('نوع الملف غير مدعوم. يُسمح فقط بملفات الصور (JPG, PNG, GIF, WEBP)');
            this.value = '';
            return;
        }
        
        // التحقق من حجم الملف (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('حجم الملف كبير جداً. الحد الأقصى 5 ميجابايت');
            this.value = '';
            return;
        }
        
                const reader = new FileReader();
                reader.onload = function(event) {
            const preview = document.getElementById('logo-preview');
            preview.innerHTML = `<img src="${event.target.result}" alt="معاينة الشعار" class="w-32 h-32 object-contain mx-auto border-2 border-gray-300 rounded-lg bg-white p-2">`;
                };
                reader.readAsDataURL(file);
            }
        });

// إعادة تعيين النموذج
function resetForm() {
    if (confirm('هل أنت متأكد من إعادة تعيين جميع البيانات؟')) {
        document.querySelector('form').reset();
        location.reload();
    }
}

// تحسين تجربة المستخدم
document.addEventListener('DOMContentLoaded', function() {
    // إضافة تأثيرات للحقول
    const inputs = document.querySelectorAll('input, textarea');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('scale-105');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('scale-105');
        });
    });
    
    // إخفاء الرسائل تلقائياً بعد 5 ثوانٍ
    const alerts = document.querySelectorAll('.bg-green-100, .bg-red-100');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease-out';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});
    </script>

<?php require_once 'includes/footer.php'; ?>