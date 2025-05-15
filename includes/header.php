<?php
// بدء التخزين المؤقت للمخرجات لمنع مشكلة "headers already sent"
ob_start();

// تضمين ملفات الاتصال بقاعدة البيانات والوظائف المشتركة
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// بدء أو استئناف الجلسة
session_start();

// عنوان الصفحة الافتراضي
$page_title = $page_title ?? 'نظام الزيارات الصفية';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
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
            font-family: 'Cairo', sans-serif;
        }

        /* تصنيفات خاصة بالنموذج */
        .score-4 {
            background-color: rgba(5, 150, 105, 0.1);
            border-color: #059669;
        }

        .score-3 {
            background-color: rgba(2, 132, 199, 0.1);
            border-color: #0284c7;
        }

        .score-2 {
            background-color: rgba(245, 158, 11, 0.1);
            border-color: #f59e0b;
        }

        .score-1 {
            background-color: rgba(220, 38, 38, 0.1);
            border-color: #dc2626;
        }

        .score-0 {
            background-color: rgba(107, 114, 128, 0.1);
            border-color: #6b7280;
        }

        /* تنسيق شريط التمرير */
        .scrollbar-thin::-webkit-scrollbar {
            width: 6px;
        }

        .scrollbar-thin::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: #0284c7;
            border-radius: 3px;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background: #075985;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <!-- شريط التنقل العلوي -->
    <nav class="bg-primary-700">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <a href="index.php" class="text-white font-bold text-xl">نظام الزيارات الصفية</a>
                    </div>
                </div>
                <div class="hidden md:block">
                    <div class="flex items-center space-x-4 space-x-reverse">
                        <a href="index.php" class="<?= $current_page === 'index.php' ? 'bg-primary-800' : 'hover:bg-primary-600' ?> text-white px-3 py-2 rounded-md text-sm font-medium">الرئيسية</a>
                        <a href="visits.php" class="<?= $current_page === 'visits.php' ? 'bg-primary-800' : 'hover:bg-primary-600' ?> text-white px-3 py-2 rounded-md text-sm font-medium">الزيارات</a>
                        <a href="evaluation_form.php" class="<?= $current_page === 'evaluation_form.php' ? 'bg-primary-800' : 'hover:bg-primary-600' ?> text-white px-3 py-2 rounded-md text-sm font-medium">نموذج تقييم</a>
                        
                        <div class="relative group">
                            <button class="hover:bg-primary-600 text-white px-3 py-2 rounded-md text-sm font-medium flex items-center">
                                الإدارة
                                <svg class="mr-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition duration-150 ease-in-out z-10">
                                <a href="teachers_management.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">المعلمين</a>
                                <a href="subjects_management.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">المواد</a>
                                <a href="sections_management.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">الشعب</a>
                                <a href="school_settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">إعدادات المدرسة</a>
                            </div>
                        </div>
                        
                        <div class="relative group">
                            <button class="hover:bg-primary-600 <?= in_array($current_page, ['training_needs.php', 'collective_training_needs.php']) ? 'bg-primary-800' : '' ?> text-white px-3 py-2 rounded-md text-sm font-medium flex items-center">
                                الاحتياجات التدريبية
                                <svg class="mr-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition duration-150 ease-in-out z-10">
                                <a href="training_needs.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">الاحتياجات الفردية</a>
                                <a href="collective_training_needs.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">الاحتياجات الجماعية</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="-mr-2 flex md:hidden">
                    <button id="mobile-menu-button" class="inline-flex items-center justify-center p-2 rounded-md text-white hover:bg-primary-600 focus:outline-none">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <div id="mobile-menu" class="hidden md:hidden">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="index.php" class="<?= $current_page === 'index.php' ? 'bg-primary-800' : 'hover:bg-primary-600' ?> text-white block px-3 py-2 rounded-md text-base font-medium">الرئيسية</a>
                <a href="visits.php" class="<?= $current_page === 'visits.php' ? 'bg-primary-800' : 'hover:bg-primary-600' ?> text-white block px-3 py-2 rounded-md text-base font-medium">الزيارات</a>
                <a href="evaluation_form.php" class="<?= $current_page === 'evaluation_form.php' ? 'bg-primary-800' : 'hover:bg-primary-600' ?> text-white block px-3 py-2 rounded-md text-base font-medium">نموذج تقييم</a>
                <div id="admin-dropdown" class="relative">
                    <button id="admin-dropdown-btn" class="w-full text-right hover:bg-primary-600 text-white px-3 py-2 rounded-md text-base font-medium flex items-center justify-between">
                        الإدارة
                        <svg class="ml-1 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path id="admin-arrow" fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                    <div id="admin-dropdown-items" class="hidden mt-1 px-4 py-2 bg-primary-800">
                        <a href="teachers_management.php" class="block py-1 text-sm text-gray-100 hover:text-white">المعلمين</a>
                        <a href="subjects_management.php" class="block py-1 text-sm text-gray-100 hover:text-white">المواد</a>
                        <a href="sections_management.php" class="block py-1 text-sm text-gray-100 hover:text-white">الشعب</a>
                        <a href="school_settings.php" class="block py-1 text-sm text-gray-100 hover:text-white">إعدادات المدرسة</a>
                    </div>
                </div>
                
                <div id="training-dropdown" class="relative">
                    <button id="training-dropdown-btn" class="w-full text-right hover:bg-primary-600 <?= in_array($current_page, ['training_needs.php', 'collective_training_needs.php']) ? 'bg-primary-800' : '' ?> text-white px-3 py-2 rounded-md text-base font-medium flex items-center justify-between">
                        الاحتياجات التدريبية
                        <svg class="ml-1 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path id="training-arrow" fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                    <div id="training-dropdown-items" class="hidden mt-1 px-4 py-2 bg-primary-800">
                        <a href="training_needs.php" class="block py-1 text-sm text-gray-100 hover:text-white">الاحتياجات الفردية</a>
                        <a href="collective_training_needs.php" class="block py-1 text-sm text-gray-100 hover:text-white">الاحتياجات الجماعية</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- محتوى الصفحة الرئيسي -->
    <main class="container mx-auto py-6 px-4"><?php
// نهاية رأس الصفحة
?> 

<script>
    // القائمة المتحركة للموبايل
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    }
    
    // القائمة المنسدلة للإدارة في وضع الموبايل
    const adminDropdownBtn = document.getElementById('admin-dropdown-btn');
    const adminDropdownItems = document.getElementById('admin-dropdown-items');
    const adminArrow = document.getElementById('admin-arrow');
    
    if (adminDropdownBtn && adminDropdownItems) {
        adminDropdownBtn.addEventListener('click', () => {
            adminDropdownItems.classList.toggle('hidden');
            adminArrow.classList.toggle('transform');
            adminArrow.classList.toggle('rotate-180');
        });
    }
    
    // القائمة المنسدلة للتدريبات في وضع الموبايل
    const trainingDropdownBtn = document.getElementById('training-dropdown-btn');
    const trainingDropdownItems = document.getElementById('training-dropdown-items');
    const trainingArrow = document.getElementById('training-arrow');
    
    if (trainingDropdownBtn && trainingDropdownItems) {
        trainingDropdownBtn.addEventListener('click', () => {
            trainingDropdownItems.classList.toggle('hidden');
            trainingArrow.classList.toggle('transform');
            trainingArrow.classList.toggle('rotate-180');
        });
    }
</script>
</body>
</html> 