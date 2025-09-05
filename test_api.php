<?php
// اختبار مباشر لـ API
echo "=== اختبار API get_previous_visits.php ===\n";

// المعاملات من آخر زيارة في قاعدة البيانات
$teacher_id = 45; // من البيانات المعروضة أعلاه
$visitor_person_id = 1; // من البيانات المعروضة أعلاه

echo "اختبار مع teacher_id=$teacher_id و visitor_person_id=$visitor_person_id\n\n";

// محاكاة استدعاء API
$_GET['teacher_id'] = $teacher_id;
$_GET['visitor_person_id'] = $visitor_person_id;

// تضمين ملف API
ob_start();
include 'api/get_previous_visits.php';
$output = ob_get_clean();

echo "النتيجة من API:\n";
echo $output;
?>
