<?php
require_once 'includes/db_connection.php';

echo "إنشاء جدول elearning_attendance الجديد...\n";

// حذف الجدول القديم إذا كان موجوداً
query("DROP TABLE IF EXISTS elearning_attendance_old");
query("RENAME TABLE elearning_attendance TO elearning_attendance_old");

// إنشاء الجدول الجديد
$sql = "
CREATE TABLE elearning_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    academic_year_id INT NOT NULL,
    school_id INT NOT NULL,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    grade_id INT NOT NULL,
    section_id INT NOT NULL,
    lesson_date DATE NOT NULL,
    lesson_number INT NOT NULL,
    attendance_type ENUM('direct', 'remote') DEFAULT 'direct',
    elearning_tools JSON,
    lesson_topic VARCHAR(255) NOT NULL,
    attendance_rating ENUM('excellent', 'very_good', 'good', 'acceptable', 'poor') DEFAULT 'poor',
    coordinator_id INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id),
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (teacher_id) REFERENCES teachers(id),
    FOREIGN KEY (grade_id) REFERENCES grades(id),
    FOREIGN KEY (section_id) REFERENCES sections(id),
    FOREIGN KEY (coordinator_id) REFERENCES users(id),
    
    INDEX idx_date (lesson_date),
    INDEX idx_teacher (teacher_id),
    INDEX idx_subject (subject_id),
    INDEX idx_school (school_id)
)";

try {
    query($sql);
    echo "✅ تم إنشاء جدول elearning_attendance الجديد بنجاح\n";
    
    echo "بنية الجدول الجديد:\n";
    $result = query('DESCRIBE elearning_attendance');
    foreach($result as $row) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
} catch (Exception $e) {
    echo "❌ خطأ في إنشاء الجدول: " . $e->getMessage() . "\n";
}
?>
