<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'نظام منسق التعليم الإلكتروني' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .nav-link:hover { background-color: rgba(255, 255, 255, 0.1); }
        .nav-link.active { background-color: rgba(255, 255, 255, 0.2); }
    </style>
</head>
<body class="bg-gray-50">
    <!-- شريط التنقل العلوي الخاص بمنسق التعليم الإلكتروني -->
    <nav class="bg-gradient-to-r from-blue-600 to-purple-700 shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <!-- اللوجو والعنوان -->
                <div class="flex items-center space-x-reverse space-x-4">
                    <div class="flex-shrink-0">
                        <i class="fas fa-laptop-code text-white text-2xl"></i>
                    </div>
                </div>

                <!-- القائمة الرئيسية -->
                <div class="hidden md:block">
                    <div class="flex items-center space-x-reverse space-x-1">
                        <a href="elearning_coordinator_dashboard.php" 
                           class="nav-link px-4 py-2 rounded-md text-white text-sm font-medium transition duration-300 <?= basename($_SERVER['PHP_SELF']) == 'elearning_coordinator_dashboard.php' ? 'active' : '' ?>">
                            <i class="fas fa-home ml-2"></i>لوحة التحكم
                        </a>
                        
                        <a href="elearning_attendance.php" 
                           class="nav-link px-4 py-2 rounded-md text-white text-sm font-medium transition duration-300 <?= basename($_SERVER['PHP_SELF']) == 'elearning_attendance.php' ? 'active' : '' ?>">
                            <i class="fas fa-chalkboard-teacher ml-2"></i>تسجيل زيارة 
                        </a>
                        
                        <a href="qatar_system_evaluation.php" 
                           class="nav-link px-4 py-2 rounded-md text-white text-sm font-medium transition duration-300 <?= basename($_SERVER['PHP_SELF']) == 'qatar_system_evaluation.php' ? 'active' : '' ?>">
                            <i class="fas fa-star ml-2"></i>تقييم نظام قطر
                        </a>
                        
                        <a href="elearning_reports.php" 
                           class="nav-link px-4 py-2 rounded-md text-white text-sm font-medium transition duration-300 <?= basename($_SERVER['PHP_SELF']) == 'elearning_reports.php' ? 'active' : '' ?>">
                            <i class="fas fa-chart-bar ml-2"></i>التقارير الشاملة
                        </a>
                        
                        <a href="elearning_attendance_reports.php" 
                           class="nav-link px-4 py-2 rounded-md text-white text-sm font-medium transition duration-300 <?= basename($_SERVER['PHP_SELF']) == 'elearning_attendance_reports.php' ? 'active' : '' ?>">
                            <i class="fas fa-file-alt ml-2"></i>تقارير الزيارات
                        </a>
                        
                        <a href="qatar_system_reports.php" 
                           class="nav-link px-4 py-2 rounded-md text-white text-sm font-medium transition duration-300 <?= basename($_SERVER['PHP_SELF']) == 'qatar_system_reports.php' ? 'active' : '' ?>">
                            <i class="fas fa-chart-line ml-2"></i>تقارير نظام قطر
                        </a>
                    </div>
                </div>

                <!-- قائمة المستخدم المنسدلة -->
                <div class="relative">
                    <button type="button" class="user-menu-button flex items-center space-x-reverse space-x-2 text-white hover:text-blue-200 transition duration-300 focus:outline-none">
                        <i class="fas fa-user-circle text-xl"></i>
                        <div class="text-sm text-right">
                            <div class="font-medium"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></div>
                            <div class="text-xs text-blue-100"><?= htmlspecialchars($_SESSION['role_name'] ?? '') ?></div>
                        </div>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    
                    <!-- القائمة المنسدلة -->
                    <div class="user-menu hidden absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                        <a href="profile.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition duration-200">
                            <i class="fas fa-user ml-3 text-blue-600"></i>
                            الملف الشخصي
                        </a>
                        <div class="border-t border-gray-100"></div>
                        <a href="logout.php" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition duration-200" 
                           onclick="return confirm('هل أنت متأكد من تسجيل الخروج؟')">
                            <i class="fas fa-sign-out-alt ml-3"></i>
                            تسجيل الخروج
                        </a>
                    </div>
                </div>

                <!-- قائمة الجوال -->
                <div class="md:hidden">
                    <button type="button" class="mobile-menu-button text-white hover:text-blue-200 focus:outline-none">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- القائمة المنسدلة للجوال -->
        <div class="mobile-menu hidden md:hidden bg-blue-700">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="elearning_coordinator_dashboard.php" class="block px-3 py-2 text-white hover:bg-blue-600 rounded-md">
                    <i class="fas fa-home ml-2"></i>لوحة التحكم
                </a>
                <a href="elearning_attendance.php" class="block px-3 py-2 text-white hover:bg-blue-600 rounded-md">
                    <i class="fas fa-chalkboard-teacher ml-2"></i>تسجيل زيارة 
                </a>
                <a href="qatar_system_evaluation.php" class="block px-3 py-2 text-white hover:bg-blue-600 rounded-md">
                    <i class="fas fa-star ml-2"></i>تقييم نظام قطر
                </a>
                <a href="elearning_reports.php" class="block px-3 py-2 text-white hover:bg-blue-600 rounded-md">
                    <i class="fas fa-chart-bar ml-2"></i>التقارير الشاملة
                </a>
                <a href="elearning_attendance_reports.php" class="block px-3 py-2 text-white hover:bg-blue-600 rounded-md">
                    <i class="fas fa-file-alt ml-2"></i>تقارير الزيارات
                </a>
                <a href="qatar_system_reports.php" class="block px-3 py-2 text-white hover:bg-blue-600 rounded-md">
                    <i class="fas fa-chart-line ml-2"></i>تقارير نظام قطر
                </a>
            </div>
        </div>
    </nav>

    <script>
        // تفعيل قائمة الجوال
        document.querySelector('.mobile-menu-button')?.addEventListener('click', function() {
            document.querySelector('.mobile-menu')?.classList.toggle('hidden');
        });

        // تفعيل قائمة المستخدم المنسدلة
        document.querySelector('.user-menu-button')?.addEventListener('click', function(e) {
            e.stopPropagation();
            document.querySelector('.user-menu')?.classList.toggle('hidden');
        });

        // إخفاء القائمة عند النقر في أي مكان آخر
        document.addEventListener('click', function() {
            const userMenu = document.querySelector('.user-menu');
            if (userMenu && !userMenu.classList.contains('hidden')) {
                userMenu.classList.add('hidden');
            }
        });

        // منع إخفاء القائمة عند النقر عليها
        document.querySelector('.user-menu')?.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    </script>
