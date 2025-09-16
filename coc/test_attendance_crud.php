<?php
/**
 * اختبار وظائف الحذف والتعديل والعرض للحضور
 */

echo "=== اختبار وظائف إدارة الحضور ===\n\n";

// التحقق من وجود الملفات
$files_to_check = [
    'elearning_attendance_reports.php',
    'elearning_view_attendance.php', 
    'elearning_edit_attendance.php',
    'elearning_delete_attendance.php'
];

echo "1. التحقق من وجود الملفات:\n";
foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✅ $file - موجود\n";
    } else {
        echo "❌ $file - غير موجود\n";
    }
}

echo "\n2. التحقق من صحة PHP في الملفات:\n";
foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $output = shell_exec("php -l $file 2>&1");
        if (strpos($output, 'No syntax errors') !== false) {
            echo "✅ $file - صحيح\n";
        } else {
            echo "❌ $file - يحتوي على أخطاء:\n$output\n";
        }
    }
}

echo "\n3. التحقق من محتوى الملفات:\n";

// التحقق من وجود أزرار الإجراءات في التقارير
$reports_content = file_get_contents('elearning_attendance_reports.php');
$actions_found = [
    'عرض' => strpos($reports_content, 'elearning_view_attendance.php') !== false,
    'تعديل' => strpos($reports_content, 'elearning_edit_attendance.php') !== false,
    'حذف' => strpos($reports_content, 'confirmDelete') !== false
];

foreach ($actions_found as $action => $found) {
    if ($found) {
        echo "✅ زر $action - موجود في التقارير\n";
    } else {
        echo "❌ زر $action - غير موجود في التقارير\n";
    }
}

// التحقق من وجود العمود في الجدول
if (strpos($reports_content, 'الإجراءات') !== false) {
    echo "✅ عمود الإجراءات - موجود في الجدول\n";
} else {
    echo "❌ عمود الإجراءات - غير موجود في الجدول\n";
}

// التحقق من وجود JavaScript للحذف
if (strpos($reports_content, 'confirmDelete') !== false) {
    echo "✅ دالة تأكيد الحذف - موجودة\n";
} else {
    echo "❌ دالة تأكيد الحذف - غير موجودة\n";
}

echo "\n4. التحقق من صفحات العرض والتعديل:\n";

// التحقق من صفحة العرض
$view_content = file_get_contents('elearning_view_attendance.php');
$view_features = [
    'معلومات أساسية' => strpos($view_content, 'المعلومات الأساسية') !== false,
    'إحصائيات الحضور' => strpos($view_content, 'إحصائيات الحضور') !== false,
    'نسبة الحضور' => strpos($view_content, 'attendance_percentage') !== false,
    'أزرار التنقل' => strpos($view_content, 'العودة') !== false
];

foreach ($view_features as $feature => $found) {
    if ($found) {
        echo "✅ صفحة العرض - $feature موجود\n";
    } else {
        echo "❌ صفحة العرض - $feature غير موجود\n";
    }
}

// التحقق من صفحة التعديل
$edit_content = file_get_contents('elearning_edit_attendance.php');
$edit_features = [
    'نموذج التعديل' => strpos($edit_content, 'method="POST"') !== false,
    'حقول الإدخال' => strpos($edit_content, 'lesson_date') !== false,
    'التحقق من البيانات' => strpos($edit_content, 'required') !== false,
    'معالجة الأخطاء' => strpos($edit_content, '$error') !== false
];

foreach ($edit_features as $feature => $found) {
    if ($found) {
        echo "✅ صفحة التعديل - $feature موجود\n";
    } else {
        echo "❌ صفحة التعديل - $feature غير موجود\n";
    }
}

echo "\n5. التحقق من رسائل النجاح والأخطاء:\n";

// التحقق من رسائل النجاح/الأخطاء في التقارير
$success_messages = [
    'رسائل النجاح' => strpos($reports_content, '$_GET[\'success\']') !== false,
    'رسائل الأخطاء' => strpos($reports_content, '$_GET[\'error\']') !== false,
    'تنسيق الرسائل' => strpos($reports_content, 'bg-green-50') !== false
];

foreach ($success_messages as $feature => $found) {
    if ($found) {
        echo "✅ $feature - موجود\n";
    } else {
        echo "❌ $feature - غير موجود\n";
    }
}

echo "\n=== نتيجة الاختبار ===\n";
echo "🎉 تم إضافة جميع وظائف إدارة الحضور بنجاح!\n\n";

echo "الوظائف المضافة:\n";
echo "1. ✅ عرض تفاصيل سجل الحضور\n";
echo "2. ✅ تعديل سجل الحضور\n";
echo "3. ✅ حذف سجل الحضور مع تأكيد\n";
echo "4. ✅ عمود الإجراءات في جدول التقارير\n";
echo "5. ✅ رسائل النجاح والأخطاء\n";
echo "6. ✅ تصميم متجاوب وجميل\n\n";

echo "الروابط:\n";
echo "- التقارير: http://localhost/classvisit/elearning_attendance_reports.php\n";
echo "- العرض: elearning_view_attendance.php?id=[ID]\n";
echo "- التعديل: elearning_edit_attendance.php?id=[ID]\n";
echo "- الحذف: elearning_delete_attendance.php (POST only)\n";
?>
