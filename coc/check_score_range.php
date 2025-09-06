<?php
require_once 'includes/functions.php';
require_once 'includes/db_connection.php';

echo "القيم الموجودة في visit_evaluations:\n";
$sample = $pdo->query('SELECT DISTINCT score FROM visit_evaluations WHERE score IS NOT NULL ORDER BY score');
foreach ($sample as $row) {
    echo 'النتيجة: ' . $row['score'] . "\n";
}

echo "\nفحص النطاق:\n";
$range = $pdo->query('SELECT MIN(score) as min_score, MAX(score) as max_score FROM visit_evaluations WHERE score IS NOT NULL')->fetch();
echo "أقل نتيجة: " . $range['min_score'] . "\n";
echo "أعلى نتيجة: " . $range['max_score'] . "\n";
?>
