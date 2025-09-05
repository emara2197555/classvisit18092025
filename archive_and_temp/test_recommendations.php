<?php
// تضمين ملف الاتصال بقاعدة البيانات
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

echo '<h1>اختبار التوصيات</h1>';

try {
    // الحصول على جميع التوصيات
    $recommendations = query("SELECT * FROM recommendations");
    
    echo '<h2>جميع التوصيات (' . count($recommendations) . ')</h2>';
    echo '<ul>';
    foreach ($recommendations as $rec) {
        echo '<li>(' . $rec['id'] . ') ' . $rec['text'] . ' - المؤشر: ' . $rec['indicator_id'] . '</li>';
    }
    echo '</ul>';
    
    // اختبار الدالة get_recommendations_by_indicator
    echo '<h2>توصيات المؤشر 1</h2>';
    
    // تعديل مؤقت للدالة (حالة إختبار)
    $indicator1_recs = query("SELECT id, text FROM recommendations WHERE indicator_id = ? ORDER BY text", [1]);
    
    echo '<ul>';
    foreach ($indicator1_recs as $rec) {
        echo '<li>(' . $rec['id'] . ') ' . $rec['text'] . '</li>';
    }
    echo '</ul>';
    
} catch (Exception $e) {
    echo '<div style="color: red; padding: 10px; background: #ffeeee; border: 1px solid red;">';
    echo 'خطأ: ' . $e->getMessage();
    echo '</div>';
}
?> 