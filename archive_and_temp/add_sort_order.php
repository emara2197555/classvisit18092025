<?php
// تضمين ملف الاتصال بقاعدة البيانات
require_once 'includes/db_connection.php';

try {
    // إضافة عمود sort_order إلى جدول التوصيات إذا لم يكن موجوداً
    $checkColumn = query("SHOW COLUMNS FROM recommendations LIKE 'sort_order'");
    
    if (empty($checkColumn)) {
        execute("ALTER TABLE recommendations ADD COLUMN sort_order INT DEFAULT 0 AFTER text");
        echo "تم إضافة عمود sort_order بنجاح";
    } else {
        echo "العمود sort_order موجود بالفعل";
    }
    
    // إضافة بعض التوصيات للاختبار
    $recommendationsCount = query_row("SELECT COUNT(*) as count FROM recommendations")['count'];
    
    if ($recommendationsCount == 0) {
        execute("
            INSERT INTO recommendations (indicator_id, text, sort_order, created_at, updated_at)
            VALUES 
            (1, 'التخطيط اليومي للدروس وتحديد الأهداف بدقة', 1, NOW(), NOW()),
            (1, 'مراجعة خطة الدرس بانتظام والتأكد من اكتمالها', 2, NOW(), NOW()),
            (2, 'صياغة أهداف تعلم ذكية قابلة للقياس', 1, NOW(), NOW()),
            (2, 'ربط أهداف التعلم بأهداف المنهج والمعايير', 2, NOW(), NOW()),
            (3, 'استخدام أنشطة تمهيدية متنوعة وجاذبة للانتباه', 1, NOW(), NOW()),
            (4, 'عرض أهداف التعلم بشكل واضح وبلغة مناسبة للطلاب', 1, NOW(), NOW())
        ");
        echo "<br>تم إضافة 6 توصيات للاختبار";
    } else {
        echo "<br>يوجد بالفعل $recommendationsCount توصيات في النظام";
    }
    
} catch (Exception $e) {
    echo "حدث خطأ: " . $e->getMessage();
}
?> 