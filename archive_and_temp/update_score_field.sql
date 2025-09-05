-- تعديل جدول visit_evaluations لدعم NULL في حقل score
-- وتحديث النظام الجديد للدرجات

-- تعديل حقل score ليسمح بـ NULL
ALTER TABLE `visit_evaluations` 
MODIFY COLUMN `score` DECIMAL(5,2) NULL DEFAULT NULL;

-- تحديث البيانات الموجودة بناءً على النظام الجديد
-- تحويل الدرجات القديمة إلى النظام الجديد
-- 4 -> 3 (الأدلة مستكملة وفاعلة)
-- 3 -> 2 (تتوفر معظم الأدلة)
-- 2 -> 1 (تتوفر بعض الأدلة)
-- 1 -> 0 (الأدلة غير متوفرة أو محدودة)
-- 0 -> NULL (لم يتم قياسه)

UPDATE `visit_evaluations` 
SET `score` = CASE 
    WHEN `score` = 4 THEN 3
    WHEN `score` = 3 THEN 2
    WHEN `score` = 2 THEN 1
    WHEN `score` = 1 THEN 0
    WHEN `score` = 0 THEN NULL
    ELSE `score`
END;

-- إضافة تعليق للحقل للتوضيح
ALTER TABLE `visit_evaluations` 
MODIFY COLUMN `score` DECIMAL(5,2) NULL DEFAULT NULL 
COMMENT 'درجة التقييم: NULL=لم يتم قياسه, 0=الأدلة غير متوفرة, 1=بعض الأدلة, 2=معظم الأدلة, 3=الأدلة مستكملة';
