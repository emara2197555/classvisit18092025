<?php
require_once 'includes/db_connection.php';

echo "فحص نظام الزيارات حسب الأدوار:\n";
echo "================================\n\n";

// فحص من يقوم بالزيارة (الزائر) حسب النوع
echo "من يقوم بالزيارة (الزائر) حسب النوع:\n";
echo "-----------------------------------\n";
$visitors_by_type = query("SELECT vt.name as visitor_type, COUNT(*) as visit_count 
                           FROM visits v
                           JOIN visitor_types vt ON v.visitor_type_id = vt.id
                           GROUP BY vt.name 
                           ORDER BY visit_count DESC");

foreach ($visitors_by_type as $row) {
    echo "نوع الزائر: " . $row['visitor_type'] . " - عدد الزيارات: " . $row['visit_count'] . "\n";
}

echo "\n";

// فحص الأشخاص الذين يقومون بالزيارة
echo "الأشخاص الذين يقومون بالزيارة:\n";
echo "------------------------------\n";
$individual_visitors = query("SELECT t.name as visitor_name, t.job_title, vt.name as visitor_type, COUNT(*) as visit_count 
                              FROM visits v
                              JOIN teachers t ON v.visitor_person_id = t.id
                              JOIN visitor_types vt ON v.visitor_type_id = vt.id
                              GROUP BY t.name, t.job_title, vt.name 
                              ORDER BY visit_count DESC");

foreach ($individual_visitors as $row) {
    echo "الزائر: " . $row['visitor_name'] . " (وظيفته: " . $row['job_title'] . ", نوع الزيارة: " . $row['visitor_type'] . ") - عدد الزيارات: " . $row['visit_count'] . "\n";
}

echo "\n";

// فحص من يتم زيارته (المُزار) حسب الوظيفة
echo "من يتم زيارته (المُزار) حسب الوظيفة:\n";
echo "-------------------------------\n";
$visited_by_job = query("SELECT t.job_title, COUNT(v.id) as visit_count
                         FROM teachers t
                         JOIN visits v ON t.id = v.teacher_id
                         GROUP BY t.job_title
                         ORDER BY visit_count DESC");

foreach ($visited_by_job as $row) {
    echo "وظيفة: " . $row['job_title'] . " - عدد الزيارات المستقبلة: " . $row['visit_count'] . "\n";
}

echo "\n";

// تحليل أدوار الزيارة حسب القواعد المطلوبة
echo "تحليل أدوار الزيارة:\n";
echo "-------------------\n";

// الموجهين - يجب أن يقوموا بالزيارة ولا يُزاروا (حسب القاعدة المطلوبة)
echo "الموجهين:\n";
echo "----------\n";

$supervisors_as_visitors = query("SELECT t.name, COUNT(*) as visits_done
                                  FROM visits v
                                  JOIN teachers t ON v.visitor_person_id = t.id
                                  WHERE t.job_title = 'موجه المادة'
                                  GROUP BY t.name
                                  ORDER BY visits_done DESC");

$supervisors_visited = query("SELECT t.name, COUNT(v.id) as visits_received
                              FROM teachers t
                              JOIN visits v ON t.id = v.teacher_id
                              WHERE t.job_title = 'موجه المادة'
                              GROUP BY t.name
                              ORDER BY visits_received DESC");

echo "الموجهين الذين يقومون بالزيارة: " . count($supervisors_as_visitors) . "\n";
foreach ($supervisors_as_visitors as $row) {
    echo "  - " . $row['name'] . ": " . $row['visits_done'] . " زيارة قام بها\n";
}

echo "الموجهين الذين يتم زيارتهم: " . count($supervisors_visited) . "\n";
foreach ($supervisors_visited as $row) {
    echo "  - " . $row['name'] . ": " . $row['visits_received'] . " زيارة استقبلها\n";
}

echo "\n";

// المنسقين - يجب أن يقوموا بالزيارة ويُزاروا (حسب القاعدة المطلوبة)
echo "المنسقين:\n";
echo "----------\n";

$coordinators_as_visitors = query("SELECT t.name, COUNT(*) as visits_done
                                   FROM visits v
                                   JOIN teachers t ON v.visitor_person_id = t.id
                                   WHERE t.job_title = 'منسق المادة'
                                   GROUP BY t.name
                                   ORDER BY visits_done DESC");

$coordinators_visited = query("SELECT t.name, COUNT(v.id) as visits_received
                               FROM teachers t
                               JOIN visits v ON t.id = v.teacher_id
                               WHERE t.job_title = 'منسق المادة'
                               GROUP BY t.name
                               ORDER BY visits_received DESC");

echo "المنسقين الذين يقومون بالزيارة: " . count($coordinators_as_visitors) . "\n";
foreach ($coordinators_as_visitors as $row) {
    echo "  - " . $row['name'] . ": " . $row['visits_done'] . " زيارة قام بها\n";
}

echo "المنسقين الذين يتم زيارتهم: " . count($coordinators_visited) . "\n";
foreach ($coordinators_visited as $row) {
    echo "  - " . $row['name'] . ": " . $row['visits_received'] . " زيارة استقبلها\n";
}

echo "\n";

// المعلمين
echo "المعلمين:\n";
echo "---------\n";

$teachers_as_visitors = query("SELECT t.name, COUNT(*) as visits_done
                               FROM visits v
                               JOIN teachers t ON v.visitor_person_id = t.id
                               WHERE t.job_title = 'معلم'
                               GROUP BY t.name
                               ORDER BY visits_done DESC");

$teachers_visited = query("SELECT t.name, COUNT(v.id) as visits_received
                           FROM teachers t
                           JOIN visits v ON t.id = v.teacher_id
                           WHERE t.job_title = 'معلم'
                           GROUP BY t.name
                           ORDER BY visits_received DESC");

echo "المعلمين الذين يقومون بالزيارة: " . count($teachers_as_visitors) . "\n";
foreach ($teachers_as_visitors as $row) {
    echo "  - " . $row['name'] . ": " . $row['visits_done'] . " زيارة قام بها\n";
}

echo "المعلمين الذين يتم زيارتهم: " . count($teachers_visited) . "\n";
foreach ($teachers_visited as $row) {
    echo "  - " . $row['name'] . ": " . $row['visits_received'] . " زيارة استقبلها\n";
}

echo "\n";

// خلاصة التحليل
echo "خلاصة التحليل:\n";
echo "==============\n";
echo "حسب القواعد المطلوبة:\n";
echo "- الموجه: يقوم بالزيارة ولا يُزار\n";
echo "- المنسق: يقوم بالزيارة ويُزار\n";
echo "- المعلم: يُزار\n\n";

echo "الوضع الحالي في النظام:\n";
echo "- موجهين يقومون بالزيارة: " . count($supervisors_as_visitors) . "\n";
echo "- موجهين يتم زيارتهم: " . count($supervisors_visited) . " (يجب أن يكون 0 حسب القاعدة)\n";
echo "- منسقين يقومون بالزيارة: " . count($coordinators_as_visitors) . "\n";
echo "- منسقين يتم زيارتهم: " . count($coordinators_visited) . "\n";
echo "- معلمين يقومون بالزيارة: " . count($teachers_as_visitors) . " (غير متوقع حسب القاعدة)\n";
echo "- معلمين يتم زيارتهم: " . count($teachers_visited) . "\n";

?>
?>
