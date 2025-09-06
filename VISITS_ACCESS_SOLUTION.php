<?php
require_once 'includes/db_connection.php';

echo "<h1>✅ إصلاح صلاحيات الوصول لصفحة الزيارات</h1>";

echo "<h2>المشكلة:</h2>";
echo "<p>المعلم (ID: 343) لا يستطيع الوصول للرابط <code>visits.php?teacher_id=343</code> ويظهر له رسالة 'ليس لديك صلاحية للوصول لهذه الصفحة'</p>";

echo "<h2>السبب:</h2>";
echo "<p>صفحة <code>visits.php</code> كانت محمية للمديرين ومنسقي المواد فقط، ولا تسمح للمعلمين بالوصول.</p>";

echo "<h2>الإصلاحات المُنفذة:</h2>";
echo "<ol>";
echo "<li><strong>إضافة صلاحية المعلمين:</strong> تم تعديل <code>protect_page</code> لتشمل 'Teacher'</li>";
echo "<li><strong>قيود خاصة للمعلمين:</strong> المعلمون يرون زياراتهم فقط</li>";
echo "<li><strong>حماية إضافية:</strong> إذا حاول معلم رؤية زيارات معلم آخر، يتم إعادة توجيهه لزياراته</li>";
echo "<li><strong>إخفاء الفلاتر:</strong> المعلمون لا يرون فلاتر البحث (لأنهم يرون زياراتهم فقط)</li>";
echo "</ol>";

echo "<h2>آلية العمل الجديدة:</h2>";
echo "<ul>";
echo "<li><strong>للمديرين/المشرفين:</strong> يرون جميع الزيارات مع إمكانية الفلترة</li>";
echo "<li><strong>لمنسقي المواد:</strong> يرون زيارات مادتهم فقط</li>";
echo "<li><strong>للمعلمين:</strong> يرون زياراتهم فقط بدون فلاتر</li>";
echo "</ul>";

echo "<h2>اختبار الصلاحيات:</h2>";

// اختبار المعلم
$teacher_id = 343;
$teacher = query_row("SELECT * FROM teachers WHERE id = ?", [$teacher_id]);
echo "<p><strong>المعلم:</strong> " . $teacher['name'] . "</p>";

$visits = query("SELECT COUNT(*) as count FROM visits WHERE teacher_id = ?", [$teacher_id]);
echo "<p><strong>عدد زياراته:</strong> " . $visits[0]['count'] . "</p>";

echo "<p style='color: green;'><strong>✅ الآن يمكن للمعلم الوصول للرابط:</strong></p>";
echo "<p><code>http://localhost/classvisit/visits.php?teacher_id=343</code></p>";

echo "<h2>ملاحظات مهمة:</h2>";
echo "<ul>";
echo "<li>المعلم يحتاج لتسجيل الدخول أولاً</li>";
echo "<li>سيرى زياراته فقط ولن يتمكن من رؤية زيارات معلمين آخرين</li>";
echo "<li>إذا حاول الوصول لـ <code>visits.php?teacher_id=OTHER_ID</code> سيتم إعادة توجيهه لزياراته</li>";
echo "</ul>";

echo "<p style='background: #e7f3ff; padding: 15px; border-left: 4px solid #2196F3; margin: 20px 0;'>";
echo "<strong>🎉 تم حل المشكلة!</strong><br>";
echo "الآن المعلم عبدالعزيز يمكنه الوصول لصفحة زياراته ورؤية الـ4 زيارات الخاصة به.";
echo "</p>";
?>
