-- تعديل جدول المواد الدراسية
ALTER TABLE subjects
ADD COLUMN is_school_specific TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'هل المادة خاصة بالمدرسة فقط',
ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'هل المادة نشطة',
ADD COLUMN created_by INT NULL COMMENT 'من أنشأ المادة',
ADD COLUMN updated_by INT NULL COMMENT 'من عدل المادة',
MODIFY COLUMN school_id INT NULL COMMENT 'رقم المدرسة (NULL للمواد العامة)';

-- إضافة مفتاح أجنبي للمستخدم الذي أنشأ/عدل المادة
ALTER TABLE subjects
ADD CONSTRAINT subjects_created_by_foreign FOREIGN KEY (created_by) REFERENCES teachers (id) ON DELETE SET NULL,
ADD CONSTRAINT subjects_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES teachers (id) ON DELETE SET NULL;

-- تحديث المواد الحالية لتكون عامة
UPDATE subjects SET school_id = NULL WHERE is_school_specific = 0;

-- إنشاء إجراء محفوظ لحذف المادة بأمان
DELIMITER //

CREATE PROCEDURE safe_delete_subject(IN subject_id INT, OUT can_delete BOOLEAN, OUT message VARCHAR(255))
BEGIN
    DECLARE visit_count INT;
    DECLARE teacher_count INT;
    
    -- التحقق من وجود زيارات مرتبطة بالمادة
    SELECT COUNT(*) INTO visit_count FROM visits WHERE subject_id = subject_id;
    
    -- التحقق من وجود معلمين مرتبطين بالمادة
    SELECT COUNT(*) INTO teacher_count FROM teacher_subjects WHERE subject_id = subject_id;
    
    IF visit_count > 0 THEN
        SET can_delete = FALSE;
        SET message = 'لا يمكن حذف هذه المادة لأنها مستخدمة في سجلات الزيارات';
    ELSEIF teacher_count > 0 THEN
        SET can_delete = FALSE;
        SET message = 'لا يمكن حذف هذه المادة لأنها مرتبطة بمعلمين';
    ELSE
        -- يمكن حذف المادة بأمان
        DELETE FROM subjects WHERE id = subject_id;
        SET can_delete = TRUE;
        SET message = 'تم حذف المادة بنجاح';
    END IF;
END //

DELIMITER ;

-- إنشاء إجراء محفوظ لتعديل المادة
DELIMITER //

CREATE PROCEDURE update_subject(
    IN p_subject_id INT,
    IN p_name VARCHAR(255),
    IN p_school_id INT,
    IN p_is_school_specific TINYINT(1),
    IN p_updated_by INT,
    OUT success BOOLEAN,
    OUT message VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET success = FALSE;
        SET message = 'حدث خطأ أثناء تحديث المادة';
        ROLLBACK;
    END;

    START TRANSACTION;
    
    IF p_is_school_specific = 0 THEN
        SET p_school_id = NULL;
    END IF;

    UPDATE subjects 
    SET 
        name = p_name,
        school_id = p_school_id,
        is_school_specific = p_is_school_specific,
        updated_by = p_updated_by,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_subject_id;

    SET success = TRUE;
    SET message = 'تم تحديث المادة بنجاح';
    
    COMMIT;
END //

DELIMITER ;

-- إضافة مؤشر للبحث السريع
CREATE INDEX idx_subjects_school ON subjects(school_id, is_school_specific);
CREATE INDEX idx_subjects_active ON subjects(is_active);

-- تحديث البيانات الحالية
UPDATE subjects SET is_school_specific = 1 WHERE school_id IS NOT NULL;
UPDATE subjects SET is_active = 1; 