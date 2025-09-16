<?php
/**
 * تشغيل سكريپت إعداد منسق التعليم الإلكتروني
 */

require_once 'includes/db_connection.php';

$sql_file = 'database/elearning_coordinator_setup.sql';

if (!file_exists($sql_file)) {
    die("ملف SQL غير موجود: $sql_file");
}

$sql_content = file_get_contents($sql_file);

if ($sql_content === false) {
    die("فشل في قراءة ملف SQL");
}

// تقسيم الاستعلامات
$queries = array_filter(array_map('trim', explode(';', $sql_content)));

$success_count = 0;
$error_count = 0;
$errors = [];

foreach ($queries as $query) {
    if (empty($query)) continue;
    
    try {
        $result = query($query);
        if ($result !== false) {
            $success_count++;
            echo "✓ تم تنفيذ الاستعلام بنجاح\n";
        } else {
            $error_count++;
            $errors[] = "خطأ في الاستعلام: " . $query;
            echo "✗ فشل في تنفيذ الاستعلام\n";
        }
    } catch (Exception $e) {
        $error_count++;
        $errors[] = "خطأ: " . $e->getMessage() . " في الاستعلام: " . substr($query, 0, 100) . "...";
        echo "✗ خطأ: " . $e->getMessage() . "\n";
    }
}

echo "\n=== ملخص التنفيذ ===\n";
echo "عدد الاستعلامات الناجحة: $success_count\n";
echo "عدد الاستعلامات الفاشلة: $error_count\n";

if (!empty($errors)) {
    echo "\nالأخطاء:\n";
    foreach ($errors as $error) {
        echo "- $error\n";
    }
}

if ($error_count === 0) {
    echo "\n🎉 تم إعداد منسق التعليم الإلكتروني بنجاح!\n";
} else {
    echo "\n⚠️ تم الإعداد مع بعض الأخطاء. يرجى مراجعة الأخطاء أعلاه.\n";
}
?>
