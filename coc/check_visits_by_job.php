<?php
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

echo "فحص زيارات المنسقين والموجهين:\n";
$visits = query("SELECT t.name, t.job_title, COUNT(v.id) as visits_count 
                 FROM teachers t 
                 LEFT JOIN visits v ON t.id = v.teacher_id 
                 WHERE t.job_title IN ('منسق المادة', 'موجه المادة') 
                 GROUP BY t.id 
                 ORDER BY visits_count DESC");

foreach ($visits as $visit) {
    echo $visit['name'] . ' (' . $visit['job_title'] . '): ' . $visit['visits_count'] . " زيارة\n";
}

echo "\n" . str_repeat("-", 50) . "\n";
echo "مقارنة مع المعلمين:\n";

$teacher_visits = query("SELECT t.name, t.job_title, COUNT(v.id) as visits_count 
                        FROM teachers t 
                        LEFT JOIN visits v ON t.id = v.teacher_id 
                        WHERE t.job_title = 'معلم'
                        GROUP BY t.id 
                        HAVING visits_count > 0
                        ORDER BY visits_count DESC 
                        LIMIT 5");

foreach ($teacher_visits as $visit) {
    echo $visit['name'] . ' (' . $visit['job_title'] . '): ' . $visit['visits_count'] . " زيارة\n";
}
?>
