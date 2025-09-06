<?php
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

$job_titles = query('SELECT DISTINCT job_title FROM teachers ORDER BY job_title');
echo "الوظائف الموجودة في النظام:\n";
foreach ($job_titles as $job) {
    echo "- " . $job['job_title'] . "\n";
}

$teacher_counts = query('SELECT job_title, COUNT(*) as count FROM teachers GROUP BY job_title ORDER BY job_title');
echo "\nعدد الموظفين لكل وظيفة:\n";
foreach ($teacher_counts as $count) {
    echo "- " . $count['job_title'] . ": " . $count['count'] . " شخص\n";
}
?>
