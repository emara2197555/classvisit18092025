<?php
require_once 'includes/db_connection.php';

try {
    echo "تم الاتصال بقاعدة البيانات بنجاح\n";
    
    // فحص جدول qatar_system_performance
    $result = query('SHOW TABLES LIKE "qatar_system_performance"');
    if (count($result) > 0) {
        echo "جدول qatar_system_performance موجود\n";
        
        // فحص عدد السجلات
        $count = query('SELECT COUNT(*) as count FROM qatar_system_performance');
        echo "عدد السجلات: " . $count[0]['count'] . "\n";
        
    } else {
        echo "جدول qatar_system_performance غير موجود!\n";
        echo "إنشاء الجدول...\n";
        
        $create_table = "
        CREATE TABLE qatar_system_performance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            teacher_id INT NOT NULL,
            subject_id INT NOT NULL,
            academic_year_id INT NOT NULL,
            term VARCHAR(50) NOT NULL,
            evaluation_date DATETIME NOT NULL,
            total_score DECIMAL(5,2) NOT NULL,
            criteria_count INT DEFAULT 0,
            performance_level VARCHAR(50) NOT NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (teacher_id) REFERENCES teachers(id),
            FOREIGN KEY (subject_id) REFERENCES subjects(id),
            FOREIGN KEY (academic_year_id) REFERENCES academic_years(id)
        )";
        
        query($create_table);
        echo "تم إنشاء الجدول بنجاح\n";
    }
    
} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage() . "\n";
}
?>
