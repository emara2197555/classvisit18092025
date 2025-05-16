-- إضافة عمود school_id إلى جدول sections
ALTER TABLE sections ADD COLUMN IF NOT EXISTS school_id INT NOT NULL DEFAULT 1 AFTER grade_id;

-- إضافة مفتاح أجنبي لربط الجدول بجدول schools
ALTER TABLE sections ADD CONSTRAINT sections_school_id_foreign 
FOREIGN KEY (school_id) REFERENCES schools(id);

-- تحديث الشعب الحالية لربطها بالمدرسة
UPDATE sections SET school_id = (SELECT id FROM schools LIMIT 1); 