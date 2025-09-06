<?php
require_once 'includes/db_connection.php';

echo "فحص بنية جدول الزيارات:\n";
echo "========================\n";

// فحص أعمدة جدول visits
$columns = query("DESCRIBE visits");

echo "أعمدة جدول visits:\n";
foreach ($columns as $column) {
    echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
}

echo "\n";

// فحص عينة من البيانات
echo "عينة من بيانات الزيارات:\n";
echo "------------------------\n";
$sample = query("SELECT * FROM visits LIMIT 3");

if (count($sample) > 0) {
    foreach ($sample as $row) {
        print_r($row);
        echo "\n";
    }
} else {
    echo "لا توجد بيانات في جدول الزيارات\n";
}

?>
