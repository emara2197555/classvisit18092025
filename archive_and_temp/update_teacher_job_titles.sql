SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- تحديث المسميات الوظيفية للمعلمين
ALTER TABLE teachers MODIFY COLUMN job_title VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'معلم';

-- تحديث المسميات الوظيفية للمعلمين لتتوافق مع أنواع الزوار
-- هذا الملف يحل مشكلة عدم ظهور الزوار من نوع معين رغم وجودهم في قاعدة البيانات

-- تحديث المسمى الوظيفي للنائب الأكاديمي
UPDATE teachers 
SET job_title = 'النائب الأكاديمي'
WHERE id = 2;

-- تحديث المسمى الوظيفي لمنسقي المواد
UPDATE teachers 
SET job_title = 'منسق المادة'
WHERE id IN (3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19);

-- تحديث المسمى الوظيفي لموجهي المواد
UPDATE teachers 
SET job_title = 'موجه المادة'
WHERE id BETWEEN 20 AND 36;

-- تحديث المسمى الوظيفي للمدير
UPDATE teachers 
SET job_title = 'مدير'
WHERE id = 1;

-- تأكد من أن باقي المعلمين لديهم المسمى الوظيفي "معلم"
UPDATE teachers 
SET job_title = 'معلم'
WHERE job_title = '????????' OR job_title IS NULL OR job_title = ''; 