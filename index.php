<?php
// تعيين عنوان الصفحة
$page_title = 'نظام الزيارات الصفية';

// تضمين ملف رأس الصفحة
require_once 'includes/header.php';

// عرض رسالة النجاح إذا كانت موجودة
if (isset($_SESSION['success_message'])) {
    echo '<div class="bg-green-100 border-r-4 border-green-500 text-green-700 p-4 mb-6 rounded-md">';
    echo $_SESSION['success_message'];
    echo '</div>';
    
    // حذف الرسالة من الجلسة بعد عرضها
    unset($_SESSION['success_message']);
}
?>

<!-- قسم البطاقة الترحيبية -->
<div class="bg-white rounded-lg shadow-md border-r-4 border-primary-600 p-8 mb-8">
    <h1 class="text-2xl font-bold text-primary-700 mb-6 pb-2 border-b-2 border-gray-200">نموذج الزيارة الصفية</h1>
    <p class="text-gray-600 mb-6">مرحباً بكم في نظام الزيارة الصفية. هذا النظام يساعد في تقييم وتحسين العملية التعليمية من خلال مراقبة وتقييم مختلف جوانب الدرس.</p>
    
    <!-- ميزات النظام -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div>
            <h2 class="text-lg font-semibold mb-3 text-gray-700">المميزات الرئيسية:</h2>
            <ul class="list-disc list-inside space-y-2 text-gray-600 mr-4">
                <li>تقييم شامل لجميع جوانب العملية التعليمية</li>
                <li>نظام تقييم متدرج وواضح</li>
                <li>توصيات تلقائية للتحسين</li>
                <li>إمكانية إضافة ملاحظات مخصصة</li>
                <li>حساب تلقائي للدرجات والتقديرات</li>
                <li>تقارير تحليلية متقدمة</li>
            </ul>
        </div>
        <div>
            <h2 class="text-lg font-semibold mb-3 text-gray-700">مجالات التقييم:</h2>
            <ul class="list-disc list-inside space-y-2 text-gray-600 mr-4">
                <li>التخطيط للدرس</li>
                <li>تنفيذ الدرس</li>
                <li>التقويم</li>
                <li>الإدارة الصفية</li>
                <li>النشاط العملي (لمادة العلوم)</li>
            </ul>
        </div>
    </div>

    <a href="evaluation_form.php" class="bg-primary-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-primary-700 transition transform hover:-translate-y-1 inline-block">بدء تقييم جديد</a>
</div>

<!-- قسم الإحصائيات والأدوات السريعة -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- إحصائيات الزيارات -->
    <div class="bg-white rounded-lg shadow-md p-6 border-t-4 border-secondary-500">
        <h2 class="text-lg font-semibold mb-4 text-gray-700">إحصائيات الزيارات</h2>
        <?php
        // استعلام لعرض إحصائيات الزيارات
        $total_visits = 0; // سيتم تحديثه لاحقاً عند تطوير قاعدة البيانات
        $recent_visits = 0; // زيارات الشهر الحالي
        $pending_visits = 0; // زيارات بانتظار الاعتماد

        try {
            $sql = "SELECT COUNT(*) as total FROM visits";
            $result = query_row($sql);
            if ($result) {
                $total_visits = $result['total'];
            }

            $current_month = date('Y-m-01');
            $sql = "SELECT COUNT(*) as recent FROM visits WHERE visit_date >= ?";
            $result = query_row($sql, [$current_month]);
            if ($result) {
                $recent_visits = $result['recent'];
            }
        } catch (PDOException $e) {
            // لا شيء - هذا لتجنب ظهور أخطاء في حالة عدم وجود الجداول بعد
        }
        ?>
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-gray-600">إجمالي الزيارات:</span>
                <span class="text-primary-600 font-bold"><?= $total_visits ?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">زيارات الشهر الحالي:</span>
                <span class="text-primary-600 font-bold"><?= $recent_visits ?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">بانتظار الاعتماد:</span>
                <span class="text-primary-600 font-bold"><?= $pending_visits ?></span>
            </div>
        </div>
        <a href="visits.php" class="mt-4 text-sm text-primary-600 hover:text-primary-800 block">عرض جميع الزيارات &larr;</a>
    </div>

    <!-- الزيارات الأخيرة -->
    <div class="bg-white rounded-lg shadow-md p-6 border-t-4 border-secondary-500">
        <h2 class="text-lg font-semibold mb-4 text-gray-700">الزيارات الأخيرة</h2>
        <div class="space-y-3">
            <?php
            // استعلام للحصول على أحدث 3 زيارات
            try {
                $sql = "SELECT v.id, v.visit_date, s.name as school_name, t.name as teacher_name
                        FROM visits v 
                        JOIN schools s ON v.school_id = s.id
                        JOIN teachers t ON v.teacher_id = t.id
                        ORDER BY v.visit_date DESC LIMIT 3";
                $recent_visits_list = query($sql);

                if (count($recent_visits_list) > 0) {
                    foreach ($recent_visits_list as $visit) {
                        echo '<div class="border-b pb-2 last:border-0">
                                <div class="font-medium">' . htmlspecialchars($visit['school_name']) . '</div>
                                <div class="text-sm text-gray-600">المعلم: ' . htmlspecialchars($visit['teacher_name']) . '</div>
                                <div class="text-xs text-gray-500 mt-1">' . format_date_ar($visit['visit_date']) . '</div>
                              </div>';
                    }
                } else {
                    echo '<p class="text-gray-500">لا توجد زيارات حتى الآن</p>';
                }
            } catch (PDOException $e) {
                echo '<p class="text-gray-500">لا توجد زيارات حتى الآن</p>';
            }
            ?>
        </div>
        <a href="visits.php" class="mt-4 text-sm text-primary-600 hover:text-primary-800 block">عرض المزيد &larr;</a>
    </div>

    <!-- روابط سريعة -->
    <div class="bg-white rounded-lg shadow-md p-6 border-t-4 border-secondary-500">
        <h2 class="text-lg font-semibold mb-4 text-gray-700">روابط سريعة</h2>
        <div class="space-y-2">
            <a href="evaluation_form.php" class="bg-primary-50 hover:bg-primary-100 text-primary-700 p-3 rounded-md flex items-center justify-between">
                <span>زيارة صفية جديدة</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 00-1 1v5H4a1 1 0 100 2h5v5a1 1 0 102 0v-5h5a1 1 0 100-2h-5V4a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </a>
            <a href="school_settings.php" class="bg-primary-50 hover:bg-primary-100 text-primary-700 p-3 rounded-md flex items-center justify-between">
                <span>إعدادات المدرسة</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                </svg>
            </a>
            <a href="teachers_management.php" class="bg-primary-50 hover:bg-primary-100 text-primary-700 p-3 rounded-md flex items-center justify-between">
                <span>إدارة المعلمين</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v1h8v-1zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-1a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v1h-3zM4.75 12.094A5.973 5.973 0 004 15v1H1v-1a3 3 0 013.75-2.906z" />
                </svg>
            </a>
            <a href="sections_management.php" class="bg-primary-50 hover:bg-primary-100 text-primary-700 p-3 rounded-md flex items-center justify-between">
                <span>إدارة الشعب الدراسية</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z" />
                </svg>
            </a>
            <a href="subjects_management.php" class="bg-primary-50 hover:bg-primary-100 text-primary-700 p-3 rounded-md flex items-center justify-between">
                <span>إدارة المواد الدراسية</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z" />
                </svg>
            </a>
            <a href="reports.php" class="bg-primary-50 hover:bg-primary-100 text-primary-700 p-3 rounded-md flex items-center justify-between">
                <span>التقارير والإحصائيات</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            </a>
        </div>
    </div>
</div>

<!-- إرشادات الاستخدام -->
<div class="bg-white rounded-lg shadow-md p-6 mb-8 border-r-4 border-primary-600">
    <h2 class="text-lg font-semibold mb-4 text-gray-700">إرشادات الاستخدام:</h2>
    <ul class="list-disc list-inside space-y-2 text-gray-600 mr-4">
        <li>قم باختيار المدرسة والمرحلة والصف والشعبة والمادة والمعلم قبل بدء التقييم</li>
        <li>اختر التقدير المناسب لكل معيار من معايير التقييم</li>
        <li>اختر التوصيات المناسبة أو أضف توصيات مخصصة</li>
        <li>يمكنك التنقل بين أقسام التقييم باستخدام أزرار التالي والسابق</li>
        <li>تأكد من حفظ التقييم بعد الانتهاء</li>
        <li>يمكنك استعراض التقارير المختلفة من صفحة التقارير</li>
    </ul>
</div>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?> 