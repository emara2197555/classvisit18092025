-- التحقق من وجود المفاتيح الأجنبية وحذفها
SET FOREIGN_KEY_CHECKS=0;

-- إعادة إنشاء الجدول بالشكل الصحيح
DROP TABLE IF EXISTS sections;
CREATE TABLE sections (
    id int NOT NULL AUTO_INCREMENT,
    name varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    grade_id int NOT NULL,
    school_id int NOT NULL,
    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY grade_id (grade_id),
    KEY school_id (school_id),
    CONSTRAINT sections_grade_id_foreign FOREIGN KEY (grade_id) REFERENCES grades (id),
    CONSTRAINT sections_school_id_foreign FOREIGN KEY (school_id) REFERENCES schools (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إضافة بيانات المدرسة إذا لم تكن موجودة
INSERT IGNORE INTO schools (id, name) VALUES 
(1, 'مدرسة عبد الله بن علي المسند الثانوية للبنين');

-- إعادة إدخال البيانات في جدول sections مع school_id
INSERT INTO sections (name, grade_id, school_id)
SELECT 'أ', id, 1 FROM grades;

SET FOREIGN_KEY_CHECKS=1; 