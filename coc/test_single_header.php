<?php
/**
 * اختبار صفحة لوحة التحكم بدون header مكرر
 */

require_once 'includes/auth_functions.php';
require_once 'includes/functions.php';

// حماية الصفحة لمنسقي التعليم الإلكتروني
protect_page(['E-Learning Coordinator', 'Admin', 'Director', 'Academic Deputy']);

$page_title = 'اختبار لوحة التحكم - بدون header مكرر';
?>

<?php include 'includes/elearning_header.php'; ?>

<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4">
        <!-- عنوان الصفحة -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">اختبار لوحة التحكم</h1>
            <p class="text-gray-600 mt-1">يجب أن تظهر قائمة واحدة فقط في الأعلى</p>
        </div>

        <!-- بطاقة تأكيد -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center space-x-reverse space-x-3">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-gray-900">تم إصلاح مشكلة القائمة المكررة</h3>
                    <p class="text-gray-600">الآن يظهر فقط قائمة منسق التعليم الإلكتروني</p>
                </div>
            </div>
            
            <div class="mt-6">
                <h4 class="font-medium text-gray-900 mb-3">روابط النظام:</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="elearning_coordinator_dashboard.php" 
                       class="flex items-center p-3 bg-blue-50 rounded-md hover:bg-blue-100 transition duration-200">
                        <i class="fas fa-home ml-2 text-blue-600"></i>
                        <span class="text-blue-800">لوحة التحكم</span>
                    </a>
                    
                    <a href="elearning_attendance.php" 
                       class="flex items-center p-3 bg-green-50 rounded-md hover:bg-green-100 transition duration-200">
                        <i class="fas fa-clipboard-check ml-2 text-green-600"></i>
                        <span class="text-green-800">تسجيل الحضور</span>
                    </a>
                    
                    <a href="elearning_attendance_reports.php" 
                       class="flex items-center p-3 bg-purple-50 rounded-md hover:bg-purple-100 transition duration-200">
                        <i class="fas fa-chart-bar ml-2 text-purple-600"></i>
                        <span class="text-purple-800">التقارير</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/elearning_footer.php'; ?>
