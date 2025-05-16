<?php
// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

try {
    // تحديث المسمى الوظيفي للنائب الأكاديمي
    execute("UPDATE teachers SET job_title = 'النائب الأكاديمي' WHERE id = ?", [2]);
    echo "تم تحديث المسمى الوظيفي للنائب الأكاديمي بنجاح<br>";

    // تحديث المسمى الوظيفي لمنسقي المواد
    execute("UPDATE teachers SET job_title = 'منسق المادة' WHERE id IN (3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19)");
    echo "تم تحديث المسمى الوظيفي لمنسقي المواد بنجاح<br>";

    // تحديث المسمى الوظيفي لموجهي المواد
    execute("UPDATE teachers SET job_title = 'موجه المادة' WHERE id BETWEEN 20 AND 36");
    echo "تم تحديث المسمى الوظيفي لموجهي المواد بنجاح<br>";

    // تحديث المسمى الوظيفي للمدير
    execute("UPDATE teachers SET job_title = 'مدير' WHERE id = ?", [1]);
    echo "تم تحديث المسمى الوظيفي للمدير بنجاح<br>";

    // تأكد من أن باقي المعلمين لديهم المسمى الوظيفي "معلم"
    execute("UPDATE teachers SET job_title = 'معلم' WHERE job_title = '????????' OR job_title IS NULL OR job_title = ''");
    echo "تم تحديث المسمى الوظيفي للمعلمين بنجاح<br>";

    echo "<hr>";
    echo "تم تحديث جميع المسميات الوظيفية بنجاح!";

} catch (Exception $e) {
    echo "حدث خطأ: " . $e->getMessage();
} 