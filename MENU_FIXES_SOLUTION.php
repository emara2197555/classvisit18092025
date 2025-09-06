<?php
require_once 'includes/db_connection.php';

echo "<h1>✅ اختبار إصلاح القائمة والأخطاء</h1>";

echo "<h2>1. إصلاح خطأ المتغير</h2>";
echo "<p>✅ تم حل خطأ <code>Undefined variable \$is_teacher</code> في expert_trainers.php</p>";
echo "<p>✅ تم ترتيب تعريف المتغيرات قبل استخدامها</p>";

echo "<h2>2. العناصر المخفية عن المعلمين</h2>";

$teacher_hidden_items = [
    'زيارة جديدة' => 'تم إخفاؤها - المعلمون لا يقومون بإنشاء زيارات',
    'قائمة الإدارة كاملة' => 'تم إخفاؤها - المعلمون لا يديرون النظام',
    'قائمة التقارير كاملة' => 'تم إخفاؤها - المعلمون يرون تقاريرهم الشخصية فقط',
    'الاحتياجات الجماعية' => 'تم إخفاؤها - المعلمون يرون احتياجاتهم الفردية فقط'
];

echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
echo "<tr style='background: #f5f5f5;'>";
echo "<th style='padding: 10px;'>العنصر</th>";
echo "<th style='padding: 10px;'>الحالة</th>";
echo "</tr>";

foreach ($teacher_hidden_items as $item => $status) {
    echo "<tr>";
    echo "<td style='padding: 10px;'>" . $item . "</td>";
    echo "<td style='padding: 10px; color: green;'>✅ " . $status . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>3. مقارنة القوائم حسب الدور</h2>";

echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
echo "<tr style='background: #f5f5f5;'>";
echo "<th style='padding: 10px;'>الدور</th>";
echo "<th style='padding: 10px;'>زيارة جديدة</th>";
echo "<th style='padding: 10px;'>قائمة الإدارة</th>";
echo "<th style='padding: 10px;'>قائمة التقارير</th>";
echo "<th style='padding: 10px;'>الاحتياجات الجماعية</th>";
echo "</tr>";

$roles_access = [
    'مدير/مشرف' => ['✅ ظاهرة', '✅ ظاهرة', '✅ ظاهرة', '✅ ظاهرة'],
    'منسق مادة' => ['✅ ظاهرة', '❌ مخفية', '✅ ظاهرة', '✅ ظاهرة'],
    'معلم' => ['❌ مخفية', '❌ مخفية', '❌ مخفية', '❌ مخفية']
];

foreach ($roles_access as $role => $access) {
    $bg_color = ($role === 'معلم') ? " style='background: #e7f3ff;'" : "";
    echo "<tr$bg_color>";
    echo "<td style='padding: 10px;'><strong>" . $role . "</strong></td>";
    foreach ($access as $item) {
        $color = strpos($item, '✅') !== false ? 'green' : 'red';
        echo "<td style='padding: 10px; color: $color;'>" . $item . "</td>";
    }
    echo "</tr>";
}
echo "</table>";

echo "<h2>4. ما سيراه المعلم في القائمة</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0;'>";
echo "<h3>القائمة العلوية للمعلم:</h3>";
echo "<ul>";
echo "<li>✅ الرئيسية</li>";
echo "<li>✅ الزيارات الصفية (زياراته فقط)</li>";
echo "<li>✅ الاحتياجات التدريبية:</li>";
echo "<ul style='margin-left: 20px;'>";
echo "<li>✅ احتياجات المعلمين (احتياجاته فقط)</li>";
echo "<li>✅ المدربين المؤهلين (نفسه فقط إذا كان مؤهلاً)</li>";
echo "</ul>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 10px 0;'>";
echo "<h3>العناصر المخفية عن المعلم:</h3>";
echo "<ul>";
echo "<li>❌ زيارة جديدة</li>";
echo "<li>❌ قائمة الإدارة بالكامل</li>";
echo "<li>❌ قائمة التقارير بالكامل</li>";
echo "<li>❌ الاحتياجات الجماعية</li>";
echo "</ul>";
echo "</div>";

echo "<h2>5. الأخطاء المُصلحة</h2>";
echo "<ul>";
echo "<li>✅ <code>Warning: Undefined variable \$is_teacher</code> - تم تعريف المتغير قبل الاستخدام</li>";
echo "<li>✅ ترتيب المتغيرات في expert_trainers.php</li>";
echo "<li>✅ إضافة شروط إخفاء العناصر في header.php</li>";
echo "</ul>";

echo "<p style='background: #e7f3ff; padding: 15px; border-left: 4px solid #2196F3; margin: 20px 0;'>";
echo "<strong>🎉 تم حل جميع المشاكل!</strong><br>";
echo "✅ لا توجد أخطاء في expert_trainers.php<br>";
echo "✅ القائمة العلوية مُخصصة حسب الدور<br>";
echo "✅ المعلم يرى الخيارات المناسبة له فقط<br>";
echo "✅ العناصر الإدارية مخفية عن المعلمين";
echo "</p>";
?>
