<?php
require_once 'includes/db_connection.php';

echo "التحقق من أعداد الوظائف:\n";
echo "=========================\n\n";

// التحقق من جميع الوظائف في النظام
$job_titles = query("SELECT job_title, COUNT(*) as count FROM teachers GROUP BY job_title ORDER BY count DESC");

echo "توزيع الوظائف في النظام:\n";
echo "-----------------------\n";
$total_all = 0;
foreach ($job_titles as $job) {
    echo $job['job_title'] . ": " . $job['count'] . "\n";
    $total_all += $job['count'];
}
echo "الإجمالي: " . $total_all . "\n\n";

// التحقق من الأعداد المحدثة
$teachers_count = query_row("SELECT COUNT(*) as count FROM teachers WHERE job_title = 'معلم'")['count'];
$coordinators_count = query_row("SELECT COUNT(*) as count FROM teachers WHERE job_title = 'منسق المادة'")['count'];
$supervisors_count = query_row("SELECT COUNT(*) as count FROM teachers WHERE job_title = 'موجه المادة'")['count'];

echo "الأعداد المحددة:\n";
echo "----------------\n";
echo "المعلمين: " . $teachers_count . "\n";
echo "المنسقين: " . $coordinators_count . "\n";
echo "الموجهين: " . $supervisors_count . "\n";

// التحقق من الزيارات
$teachers_evaluated = query_row("
    SELECT COUNT(DISTINCT v.teacher_id) as count
    FROM visits v
    JOIN teachers t ON v.teacher_id = t.id
    WHERE t.job_title = 'معلم'
")['count'];

$coordinators_evaluated = query_row("
    SELECT COUNT(DISTINCT v.teacher_id) as count
    FROM visits v
    JOIN teachers t ON v.teacher_id = t.id
    WHERE t.job_title = 'منسق المادة'
")['count'];

echo "\nالذين تم تقييمهم:\n";
echo "-----------------\n";
echo "معلمين مقيمين: " . $teachers_evaluated . " من أصل " . $teachers_count . "\n";
echo "منسقين مقيمين: " . $coordinators_evaluated . " من أصل " . $coordinators_count . "\n";

echo "\nنسب التغطية:\n";
echo "-------------\n";
echo "نسبة تغطية المعلمين: " . round(($teachers_evaluated / $teachers_count) * 100, 1) . "%\n";
echo "نسبة تغطية المنسقين: " . round(($coordinators_evaluated / $coordinators_count) * 100, 1) . "%\n";

?>
