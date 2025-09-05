-- إضافة عمود العام الدراسي إلى جدول الزيارات
ALTER TABLE `visits` 
ADD COLUMN `academic_year_id` int DEFAULT NULL AFTER `visit_date`;

-- إنشاء مفتاح أجنبي للربط مع جدول الأعوام الدراسية
ALTER TABLE `visits`
ADD CONSTRAINT `visits_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) 
REFERENCES `academic_years` (`id`) ON DELETE RESTRICT;

-- تحديث البيانات الموجودة لتعيين العام الدراسي النشط للزيارات الحالية
UPDATE `visits` v
SET v.`academic_year_id` = (SELECT id FROM academic_years WHERE is_active = 1 LIMIT 1)
WHERE v.`academic_year_id` IS NULL; 