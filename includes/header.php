<?php
// بدء التخزين المؤقت للمخرجات لمنع مشكلة "headers already sent"
ob_start();

// تضمين ملفات الاتصال بقاعدة البيانات والوظائف المشتركة
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';
require_once 'includes/auth_functions.php';

// بدء أو استئناف الجلسة
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// التحقق من تسجيل الدخول (ما عدا صفحة تسجيل الدخول نفسها)
$current_script = basename($_SERVER['SCRIPT_NAME']);
if ($current_script !== 'login.php' && !is_logged_in()) {
    header('Location: login.php?message=' . urlencode('يجب تسجيل الدخول أولاً'));
    exit;
}

// عنوان الصفحة الافتراضي
$page_title = $page_title ?? 'نظام الزيارات الصفية';

// اسم التطبيق الافتراضي
$app_name = $app_name ?? 'نظام الزيارات الصفية';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- خط Cairo من Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- تخصيص Tailwind -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                            950: '#082f49',
                        },
                        secondary: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            200: '#99f6e4',
                            300: '#5eead4',
                            400: '#2dd4bf',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                            950: '#042f2e',
                        }
                    },
                    fontFamily: {
                        'cairo': ['Cairo', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f9fafb;
            margin: 0;
            padding: 0;
            margin-top: 100px; /* تعديل الهامش العلوي ليكون أكبر لتجنب اختفاء المحتوى */
        }
        
        /* قواعد نمط إضافية للقوائم المنسدلة - تعديل لمشكلة الاختفاء السريع */
        .dropdown-menu {
            display: none;
            transition: visibility 0.3s, opacity 0.3s;
            visibility: hidden;
            opacity: 0;
        }
        
        .dropdown:hover .dropdown-menu {
            display: block;
            visibility: visible;
            opacity: 1;
            transition-delay: 0s;
        }
        
        /* إضافة تأخير للاختفاء */
        .dropdown-menu:hover {
            visibility: visible;
            opacity: 1;
        }
        
        .dropdown {
            position: relative;
        }
        
        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            z-index: 1000;
            min-width: 10rem;
            padding: 0.5rem 0;
            background-color: #fff;
            border-radius: 0.25rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        /* تعديل لمشكلة القائمة المنسدلة للتقارير */
        .reports-dropdown .dropdown-menu {
            right: auto;
            left: 0;
        }
        
        .score-4 { background-color: rgba(5, 150, 105, 0.1); border-color: #059669; }
        .score-3 { background-color: rgba(2, 132, 199, 0.1); border-color: #0284c7; }
        .score-2 { background-color: rgba(245, 158, 11, 0.1); border-color: #f59e0b; }
        .score-1 { background-color: rgba(220, 38, 38, 0.1); border-color: #dc2626; }
        .score-0 { background-color: rgba(107, 114, 128, 0.1); border-color: #6b7280; }
        .scrollbar-thin::-webkit-scrollbar { width: 6px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 3px; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #0284c7; border-radius: 3px; }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover { background: #075985; }
    </style>
</head>
<body dir="rtl" class="bg-gray-100 font-sans">
    <header class="bg-primary-600 text-white shadow-md fixed w-full top-0 z-50">
        <div class="container mx-auto px-4 py-4 flex flex-wrap justify-between items-center">
            <h1 class="text-xl font-bold"><?= $app_name ?></h1>
            <nav class="hidden md:flex space-x-6 space-x-reverse">
                <a href="index.php" class="hover:text-primary-200 <?= $current_page == 'index.php' ? 'border-b-2 border-white' : '' ?>">الرئيسية</a>
                <a href="visits.php" class="hover:text-primary-200 <?= $current_page == 'visits.php' ? 'border-b-2 border-white' : '' ?>">الزيارات الصفية</a>
                
                <?php 
                $user_role = $_SESSION['role_name'] ?? '';
                $is_coordinator = ($user_role === 'Subject Coordinator');
                $is_teacher = ($user_role === 'Teacher');
                
                // إخفاء "زيارة جديدة" عن المعلمين
                if (!$is_teacher): 
                ?>
                <a href="evaluation_form.php" class="hover:text-primary-200 <?= $current_page == 'evaluation_form.php' ? 'border-b-2 border-white' : '' ?>">زيارة جديدة</a>
                <?php endif; ?>
                
                <!-- قائمة الإدارة - مخفية عن المنسقين والمعلمين -->
                <?php if (!$is_coordinator && !$is_teacher): ?>
                <div class="relative group">
                    <button class="hover:text-primary-200 <?= in_array($current_page, ['users_management.php', 'teachers_management.php', 'subjects_management.php', 'sections_management.php', 'school_settings.php']) ? 'border-b-2 border-white' : '' ?>">
                        الإدارة
                        <i class="fas fa-caret-down mr-1"></i>
                    </button>
                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-10">
                        <a href="users_management.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i class="fas fa-users mr-2"></i>إدارة المستخدمين</a>
                        <a href="teachers_management.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">إدارة المعلمين</a>
                        <a href="subjects_management.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">إدارة المواد الدراسية</a>
                        <a href="sections_management.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">إدارة الشعب</a>
                        <a href="school_settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">إعدادات المدرسة</a>
                        <a href="academic_years_management.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">إدارة الأعوام الدراسية</a>
                        <a href="recommendations_management.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i class="fas fa-lightbulb mr-2"></i>إدارة التوصيات</a>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- قائمة الاحتياجات التدريبية -->
                <div class="relative group">
                    <button class="hover:text-primary-200 <?= in_array($current_page, ['training_needs.php', 'collective_training_needs.php', 'expert_trainers.php']) ? 'border-b-2 border-white' : '' ?>">
                        الاحتياجات التدريبية
                        <i class="fas fa-caret-down mr-1"></i>
                    </button>
                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-10">
                        <a href="training_needs.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">احتياجات المعلمين</a>
                        <?php if (!$is_teacher): ?>
                        <a href="collective_training_needs.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">الاحتياجات الجماعية</a>
                        <?php endif; ?>
                        <a href="expert_trainers.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">المدربين المؤهلين</a>
                    </div>
                </div>
                
                <!-- قائمة التقارير - مخفية عن المعلمين -->
                <?php if (!$is_teacher): ?>
                <div class="relative group reports-dropdown">
                    <button class="hover:text-primary-200 <?= in_array($current_page, ['class_performance_report.php', 'grades_performance_report.php', 'teacher_report.php', 'subject_performance_report.php', 'subject_detailed_report.php']) ? 'border-b-2 border-white' : '' ?>">
                        التقارير
                        <i class="fas fa-caret-down mr-1"></i>
                    </button>
                    <div class="absolute mt-2 w-48 bg-white rounded-md shadow-lg py-1 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-10">
                        <a href="class_performance_report.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">تقرير أداء المعلمين</a>
                        <a href="grades_performance_report.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">تقرير أداء الصفوف</a>
                        <a href="subject_performance_report.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">تقرير أداء المواد</a>
                        <a href="sections_reports.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">تقرير أداء الشعب</a>
                    </div>
                </div>
                <?php endif; ?>
            </nav>
            
            <!-- قسم المستخدم -->
            <div class="hidden md:flex items-center space-x-4 space-x-reverse">
                <div class="relative group">
                    <button class="flex items-center space-x-2 space-x-reverse hover:text-primary-200 transition-colors duration-200">
                        <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center text-primary-600">
                            <i class="fas fa-user text-sm"></i>
                        </div>
                        <span class="text-sm font-medium"><?= $_SESSION['full_name'] ?? 'مستخدم' ?></span>
                        <i class="fas fa-caret-down text-xs"></i>
                    </button>
                    <div class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-20">
                        <div class="px-4 py-2 border-b border-gray-200">
                            <p class="text-sm font-medium text-gray-900"><?= $_SESSION['full_name'] ?? 'مستخدم' ?></p>
                            <p class="text-xs text-gray-500"><?= $_SESSION['role_name'] ?? 'غير محدد' ?></p>
                        </div>
                        <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-user-edit ml-2"></i>الملف الشخصي
                        </a>
                        <a href="site_settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-cog ml-2"></i>إعدادات الموقع
                        </a>
                        <div class="border-t border-gray-200"></div>
                        <a href="logout.php" class="block px-4 py-2 text-sm text-red-700 hover:bg-red-50">
                            <i class="fas fa-sign-out-alt ml-2"></i>تسجيل الخروج
                        </a>
                    </div>
                </div>
            </div>
            
            <button id="mobile-menu-button" class="md:hidden">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                </svg>
            </button>
        </div>
        <div id="mobile-menu" class="hidden md:hidden bg-primary-700 pb-4 px-4">
            <!-- قسم المستخدم في القائمة المحمولة -->
            <div class="border-b border-primary-600 pb-3 mb-3">
                <div class="flex items-center space-x-3 space-x-reverse py-2">
                    <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-primary-600">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <p class="text-white font-medium text-sm"><?= $_SESSION['full_name'] ?? 'مستخدم' ?></p>
                        <p class="text-primary-200 text-xs"><?= $_SESSION['role_name'] ?? 'غير محدد' ?></p>
                    </div>
                </div>
                <div class="mt-2 space-y-1">
                    <a href="profile.php" class="block py-2 text-sm text-primary-200 hover:text-white">
                        <i class="fas fa-user-edit ml-2"></i>الملف الشخصي
                    </a>
                    <a href="site_settings.php" class="block py-2 text-sm text-primary-200 hover:text-white">
                        <i class="fas fa-cog ml-2"></i>إعدادات الموقع
                    </a>
                    <a href="logout.php" class="block py-2 text-sm text-red-300 hover:text-red-100">
                        <i class="fas fa-sign-out-alt ml-2"></i>تسجيل الخروج
                    </a>
                </div>
            </div>
            
            <a href="index.php" class="block py-2 hover:text-primary-200 <?= $current_page == 'index.php' ? 'font-bold' : '' ?>">الرئيسية</a>
            <a href="visits.php" class="block py-2 hover:text-primary-200 <?= $current_page == 'visits.php' ? 'font-bold' : '' ?>">الزيارات الصفية</a>
            <a href="evaluation_form.php" class="block py-2 hover:text-primary-200 <?= $current_page == 'evaluation_form.php' ? 'font-bold' : '' ?>">زيارة جديدة</a>
            
            <?php if (!$is_coordinator): ?>
            <a href="#" class="block py-2 hover:text-primary-200 mobile-submenu-toggle">الإدارة <i class="fas fa-caret-down mr-1"></i></a>
            <div class="hidden mobile-submenu bg-primary-800 p-2 rounded mt-1 mb-2">
                <a href="users_management.php" class="block py-1 hover:text-primary-200">إدارة المستخدمين</a>
                <a href="teachers_management.php" class="block py-1 hover:text-primary-200">إدارة المعلمين</a>
                <a href="subjects_management.php" class="block py-1 hover:text-primary-200">إدارة المواد الدراسية</a>
                <a href="sections_management.php" class="block py-1 hover:text-primary-200">إدارة الشعب</a>
                <a href="school_settings.php" class="block py-1 hover:text-primary-200">إعدادات المدرسة</a>
                <a href="academic_years_management.php" class="block py-1 hover:text-primary-200">إدارة الأعوام الدراسية</a>
                <a href="add_recommendations.php" class="block py-1 hover:text-primary-200">إضافة توصيات</a>
            </div>
            <?php endif; ?>
            
            <a href="#" class="block py-2 hover:text-primary-200 mobile-submenu-toggle">الاحتياجات التدريبية <i class="fas fa-caret-down mr-1"></i></a>
            <div class="hidden mobile-submenu bg-primary-800 p-2 rounded mt-1 mb-2">
                <a href="training_needs.php" class="block py-1 hover:text-primary-200">احتياجات المعلمين</a>
                <a href="collective_training_needs.php" class="block py-1 hover:text-primary-200">الاحتياجات الجماعية</a>
                <a href="expert_trainers.php" class="block py-1 hover:text-primary-200">المدربين المؤهلين</a>
            </div>
            <a href="#" class="block py-2 hover:text-primary-200 mobile-submenu-toggle">التقارير <i class="fas fa-caret-down mr-1"></i></a>
            <div class="hidden mobile-submenu bg-primary-800 p-2 rounded mt-1 mb-2">
                <a href="class_performance_report.php" class="block py-1 hover:text-primary-200">تقرير أداء المعلمين</a>
                <a href="grades_performance_report.php" class="block py-1 hover:text-primary-200">تقرير أداء الصفوف</a>
                <a href="subject_performance_report.php" class="block py-1 hover:text-primary-200">تقرير أداء المواد</a>
                <a href="sections_reports.php" class="block py-1 hover:text-primary-200">تقرير أداء الشعب</a>
            </div>
        </div>
    </header>

    <!-- إضافة هامش علوي لحل مشكلة اختفاء المحتوى تحت القائمة -->
    <div class="pt-28"></div> <!-- هامش علوي للمحتوى الرئيسي -->
    
    <main class="container mx-auto px-4 pb-8">
<?php
// نهاية رأس الصفحة
?>