<?php
require_once 'includes/db_connection.php';

echo "بنية جدول elearning_attendance:\n";
$result = query('DESCRIBE elearning_attendance');
foreach($result as $row) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\nعينة من البيانات:\n";
$sample = query('SELECT * FROM elearning_attendance LIMIT 1');
if (!empty($sample)) {
    print_r($sample[0]);
} else {
    echo "لا توجد بيانات في الجدول\n";
}
?>
