<?php
/**
 * صفحة تسجيل الدخول
 */

// تضمين ملفات النظام
require_once 'includes/auth_functions.php';
require_once 'includes/functions.php';

// إذا كان المستخدم مسجل دخول مسبقاً، توجيهه للوحة التحكم
if (is_logged_in()) {
    $role = $_SESSION['role_name'] ?? 'admin';
    header('Location: ' . get_dashboard_url($role));
    exit;
}

$error_message = '';
$success_message = '';

// معالجة نموذج تسجيل الدخول
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error_message = 'يرجى إدخال اسم المستخدم وكلمة المرور';
    } else {
        $result = authenticate_user($username, $password);
        
        if ($result['success']) {
            header('Location: ' . $result['redirect']);
            exit;
        } else {
            $error_message = $result['message'];
        }
    }
}

// تعيين عنوان الصفحة
$page_title = 'تسجيل الدخول - نظام الزيارات الصفية';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    
    <!-- الحاوي الرئيسي -->
    <div class="w-full max-w-md">
        
        <!-- بطاقة تسجيل الدخول -->
        <div class="glass-effect rounded-2xl shadow-2xl p-8">
            
            <!-- شعار النظام -->
            <div class="text-center mb-8">
                <div class="bg-white bg-opacity-20 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-4">
                    <i class="fas fa-graduation-cap text-white text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-white mb-2">نظام الزيارات الصفية</h1>
                <p class="text-white text-opacity-80 text-sm">تسجيل الدخول للوصول للنظام</p>
            </div>

            <!-- رسائل التنبيه -->
            <?php if ($error_message): ?>
                <div class="bg-red-500 bg-opacity-20 border border-red-300 text-white p-3 rounded-lg mb-6 text-center">
                    <i class="fas fa-exclamation-circle ml-2"></i>
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="bg-green-500 bg-opacity-20 border border-green-300 text-white p-3 rounded-lg mb-6 text-center">
                    <i class="fas fa-check-circle ml-2"></i>
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <!-- نموذج تسجيل الدخول -->
            <form method="POST" action="" class="space-y-6">
                
                <!-- حقل اسم المستخدم -->
                <div>
                    <label for="username" class="block text-white text-sm font-medium mb-2">
                        <i class="fas fa-user ml-2"></i>
                        اسم المستخدم
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        class="w-full px-4 py-3 bg-white bg-opacity-20 border border-white border-opacity-30 rounded-lg text-white placeholder-white placeholder-opacity-70 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 focus:border-transparent transition"
                        placeholder="أدخل اسم المستخدم"
                        autocomplete="username"
                    >
                </div>

                <!-- حقل كلمة المرور -->
                <div>
                    <label for="password" class="block text-white text-sm font-medium mb-2">
                        <i class="fas fa-lock ml-2"></i>
                        كلمة المرور
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="w-full px-4 py-3 bg-white bg-opacity-20 border border-white border-opacity-30 rounded-lg text-white placeholder-white placeholder-opacity-70 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 focus:border-transparent transition pr-12"
                            placeholder="أدخل كلمة المرور"
                            autocomplete="current-password"
                        >
                        <button 
                            type="button" 
                            onclick="togglePassword()"
                            class="absolute left-3 top-1/2 transform -translate-y-1/2 text-white text-opacity-70 hover:text-opacity-100 transition"
                        >
                            <i class="fas fa-eye" id="passwordToggleIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- زر تسجيل الدخول -->
                <button 
                    type="submit" 
                    class="w-full bg-white bg-opacity-20 hover:bg-opacity-30 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50"
                >
                    <i class="fas fa-sign-in-alt ml-2"></i>
                    تسجيل الدخول
                </button>

            </form>

            <!-- روابط إضافية -->
            <div class="mt-6 text-center">
                <a href="#" class="text-white text-opacity-80 hover:text-opacity-100 text-sm transition">
                    <i class="fas fa-question-circle ml-1"></i>
                    نسيت كلمة المرور؟
                </a>
            </div>

        </div>

        <!-- معلومات إضافية -->
        <div class="text-center mt-6">
            <p class="text-white text-opacity-70 text-sm">
                © <?= date('Y') ?> نظام الزيارات الصفية - جميع الحقوق محفوظة
            </p>
        </div>

    </div>

    <!-- JavaScript -->
    <script>
        // تبديل إظهار/إخفاء كلمة المرور
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('passwordToggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // التركيز على حقل اسم المستخدم عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });

        // التحقق من صحة النموذج قبل الإرسال
        document.querySelector('form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                alert('يرجى إدخال اسم المستخدم وكلمة المرور');
                return false;
            }
        });
    </script>

</body>
</html>
