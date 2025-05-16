SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE DATABASE IF NOT EXISTS classvisit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE classvisit;

-- تعطيل التحقق من المفاتيح الأجنبية
SET FOREIGN_KEY_CHECKS=0;

-- إنشاء جدول المدارس
CREATE TABLE IF NOT EXISTS schools (
    id int NOT NULL AUTO_INCREMENT,
    name varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- حذف الجداول القديمة
DROP TABLE IF EXISTS visit_evaluations;
DROP TABLE IF EXISTS visits;
DROP TABLE IF EXISTS teacher_subjects;
DROP TABLE IF EXISTS teachers;
DROP TABLE IF EXISTS subjects;

-- إعادة إنشاء جدول المواد الدراسية
CREATE TABLE subjects (
    id int NOT NULL AUTO_INCREMENT,
    name varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    school_id int NULL,
    is_school_specific TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT NULL,
    updated_by INT NULL,
    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إعادة إنشاء جدول المعلمين
CREATE TABLE teachers (
    id int NOT NULL AUTO_INCREMENT,
    name varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    personal_id varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
    email varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    job_title enum('معلم','منسق المادة','موجه المادة','النائب الأكاديمي','المدير') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'معلم',
    phone varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    school_id int NOT NULL,
    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY school_id (school_id),
    CONSTRAINT teachers_school_id_foreign FOREIGN KEY (school_id) REFERENCES schools (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إعادة إنشاء جدول علاقات المعلمين بالمواد
CREATE TABLE teacher_subjects (
    id int NOT NULL AUTO_INCREMENT,
    teacher_id int NOT NULL,
    subject_id int NOT NULL,
    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY teacher_id (teacher_id),
    KEY subject_id (subject_id),
    CONSTRAINT teacher_subjects_teacher_id_foreign FOREIGN KEY (teacher_id) REFERENCES teachers (id) ON DELETE CASCADE,
    CONSTRAINT teacher_subjects_subject_id_foreign FOREIGN KEY (subject_id) REFERENCES subjects (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إضافة المفاتيح الأجنبية لجدول subjects
ALTER TABLE subjects
ADD CONSTRAINT subjects_created_by_foreign FOREIGN KEY (created_by) REFERENCES teachers (id) ON DELETE SET NULL,
ADD CONSTRAINT subjects_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES teachers (id) ON DELETE SET NULL,
ADD CONSTRAINT subjects_school_id_foreign FOREIGN KEY (school_id) REFERENCES schools (id) ON DELETE CASCADE;

-- إعادة تفعيل التحقق من المفاتيح الأجنبية
SET FOREIGN_KEY_CHECKS=1;

-- إضافة المواد الدراسية
INSERT INTO subjects (name, school_id, is_school_specific) VALUES
('اللغة العربية', 1, 1),
('اللغة الإنجليزية', 1, 1),
('الرياضيات', 1, 1),
('العلوم', 1, 1),
('الفيزياء', 1, 1),
('الكيمياء', 1, 1),
('الأحياء', 1, 1),
('التربية الإسلامية', 1, 1),
('الدراسات الاجتماعية', 1, 1),
('التربية الفنية', 1, 1),
('التربية البدنية', 1, 1),
('الحاسب الآلي', 1, 1),
('المهارات الحياتية', 1, 1),
('التربية الموسيقية', 1, 1),
('اللغة الفرنسية', 1, 1),
('التصميم والتكنولوجيا', 1, 1),
('المكتبة والبحث', 1, 1);

-- إضافة المعلمين مع البيانات الإضافية
INSERT INTO teachers (name, personal_id, email, job_title, phone, school_id) VALUES
-- المدير والنائب الأكاديمي
('د. محمد عبدالله الكواري', '28540000001', 'mohammed.alkuwari@edu.gov.qa', 'المدير', '97450000001', 1),
('د. خالد جاسم النعيمي', '28540000002', 'khalid.alnaimi@edu.gov.qa', 'النائب الأكاديمي', '97450000002', 1),

-- منسقي المواد (17 منسق)
('أحمد محمد السليطي', '28540000003', 'ahmed.alsulaiti@edu.gov.qa', 'منسق المادة', '97450000003', 1),
('فاطمة علي المري', '28540000004', 'fatima.almurri@edu.gov.qa', 'منسق المادة', '97450000004', 1),
('عبدالرحمن حسن العمادي', '28540000005', 'abdulrahman.alamadi@edu.gov.qa', 'منسق المادة', '97450000005', 1),
('نورة جاسم الكبيسي', '28540000006', 'noora.alkubaisi@edu.gov.qa', 'منسق المادة', '97450000006', 1),
('حمد خليفة السويدي', '28540000007', 'hamad.alsuwaidi@edu.gov.qa', 'منسق المادة', '97450000007', 1),
('مريم راشد المهندي', '28540000008', 'maryam.almuhannadi@edu.gov.qa', 'منسق المادة', '97450000008', 1),
('عبدالله محمد الهاجري', '28540000009', 'abdullah.alhajri@edu.gov.qa', 'منسق المادة', '97450000009', 1),
('عائشة سعيد العطية', '28540000010', 'aisha.alattiyah@edu.gov.qa', 'منسق المادة', '97450000010', 1),
('يوسف علي الكواري', '28540000011', 'yousef.alkuwari@edu.gov.qa', 'منسق المادة', '97450000011', 1),
('لطيفة جاسم المناعي', '28540000012', 'latifa.almanai@edu.gov.qa', 'منسق المادة', '97450000012', 1),
('خالد محمد المالكي', '28540000013', 'khalid.almalki@edu.gov.qa', 'منسق المادة', '97450000013', 1),
('شيخة حمد آل ثاني', '28540000014', 'shaikha.althani@edu.gov.qa', 'منسق المادة', '97450000014', 1),
('جاسم أحمد البوعينين', '28540000015', 'jassim.alboenain@edu.gov.qa', 'منسق المادة', '97450000015', 1),
('موزة خليفة النعيمي', '28540000016', 'moza.alnaimi@edu.gov.qa', 'منسق المادة', '97450000016', 1),
('علي حسن الدرهم', '28540000017', 'ali.aldarham@edu.gov.qa', 'منسق المادة', '97450000017', 1),
('حصة محمد المسند', '28540000018', 'hissa.almusnad@edu.gov.qa', 'منسق المادة', '97450000018', 1),
('عبدالعزيز جاسم الدرويش', '28540000019', 'abdulaziz.aldarwish@edu.gov.qa', 'منسق المادة', '97450000019', 1),

-- موجهي المواد (17 موجه)
('د. ناصر علي المري', '28540000020', 'nasser.almurri@edu.gov.qa', 'موجه المادة', '97450000020', 1),
('د. سارة محمد الكواري', '28540000021', 'sara.alkuwari@edu.gov.qa', 'موجه المادة', '97450000021', 1),
('د. جاسم خالد النعيمي', '28540000022', 'jassim.alnaimi@edu.gov.qa', 'موجه المادة', '97450000022', 1),
('د. عائشة أحمد السليطي', '28540000023', 'aisha.alsulaiti@edu.gov.qa', 'موجه المادة', '97450000023', 1),
('د. خليفة حمد المهندي', '28540000024', 'khalifa.almuhannadi@edu.gov.qa', 'موجه المادة', '97450000024', 1),
('د. نورة عبدالله العطية', '28540000025', 'noora.alattiyah@edu.gov.qa', 'موجه المادة', '97450000025', 1),
('د. محمد راشد الهاجري', '28540000026', 'mohammed.alhajri@edu.gov.qa', 'موجه المادة', '97450000026', 1),
('د. فاطمة جاسم المناعي', '28540000027', 'fatima.almanai@edu.gov.qa', 'موجه المادة', '97450000027', 1),
('د. عبدالرحمن علي البوعينين', '28540000028', 'abdulrahman.alboenain@edu.gov.qa', 'موجه المادة', '97450000028', 1),
('د. موزة سعيد آل ثاني', '28540000029', 'moza.althani@edu.gov.qa', 'موجه المادة', '97450000029', 1),
('د. حمد خالد الدرويش', '28540000030', 'hamad.aldarwish@edu.gov.qa', 'موجه المادة', '97450000030', 1),
('د. لطيفة محمد السويدي', '28540000031', 'latifa.alsuwaidi@edu.gov.qa', 'موجه المادة', '97450000031', 1),
('د. يوسف حسن العمادي', '28540000032', 'yousef.alamadi@edu.gov.qa', 'موجه المادة', '97450000032', 1),
('د. شيخة جاسم الكبيسي', '28540000033', 'shaikha.alkubaisi@edu.gov.qa', 'موجه المادة', '97450000033', 1),
('د. علي عبدالله المالكي', '28540000034', 'ali.almalki@edu.gov.qa', 'موجه المادة', '97450000034', 1),
('د. مريم خليفة الدرهم', '28540000035', 'maryam.aldarham@edu.gov.qa', 'موجه المادة', '97450000035', 1),
('د. عبدالعزيز محمد المسند', '28540000036', 'abdulaziz.almusnad@edu.gov.qa', 'موجه المادة', '97450000036', 1),

-- المعلمين (50 معلم)
('سعيد محمد الكواري', '28540000037', 'saeed.alkuwari@edu.gov.qa', 'معلم', '97450000037', 1),
('نورة علي المري', '28540000038', 'noora.almurri@edu.gov.qa', 'معلم', '97450000038', 1),
('جاسم خالد السليطي', '28540000039', 'jassim.alsulaiti@edu.gov.qa', 'معلم', '97450000039', 1),
('فاطمة أحمد النعيمي', '28540000040', 'fatima.alnaimi@edu.gov.qa', 'معلم', '97450000040', 1),
('عبدالله حمد العمادي', '28540000041', 'abdullah.alamadi@edu.gov.qa', 'معلم', '97450000041', 1),
('موزة راشد الكبيسي', '28540000042', 'moza.alkubaisi@edu.gov.qa', 'معلم', '97450000042', 1),
('خليفة محمد السويدي', '28540000043', 'khalifa.alsuwaidi@edu.gov.qa', 'معلم', '97450000043', 1),
('عائشة جاسم المهندي', '28540000044', 'aisha.almuhannadi@edu.gov.qa', 'معلم', '97450000044', 1),
('حمد علي الهاجري', '28540000045', 'hamad.alhajri@edu.gov.qa', 'معلم', '97450000045', 1),
('لطيفة سعيد العطية', '28540000046', 'latifa.alattiyah@edu.gov.qa', 'معلم', '97450000046', 1),
('يوسف عبدالله الكواري', '28540000047', 'yousef.alkuwari2@edu.gov.qa', 'معلم', '97450000047', 1),
('شيخة جاسم المناعي', '28540000048', 'shaikha.almanai@edu.gov.qa', 'معلم', '97450000048', 1),
('علي محمد المالكي', '28540000049', 'ali.almalki2@edu.gov.qa', 'معلم', '97450000049', 1),
('مريم خالد آل ثاني', '28540000050', 'maryam.althani@edu.gov.qa', 'معلم', '97450000050', 1),
('عبدالرحمن حسن البوعينين', '28540000051', 'abdulrahman.alboenain2@edu.gov.qa', 'معلم', '97450000051', 1),
('نورة خليفة النعيمي', '28540000052', 'noora.alnaimi2@edu.gov.qa', 'معلم', '97450000052', 1),
('جاسم أحمد الدرهم', '28540000053', 'jassim.aldarham@edu.gov.qa', 'معلم', '97450000053', 1),
('فاطمة محمد المسند', '28540000054', 'fatima.almusnad@edu.gov.qa', 'معلم', '97450000054', 1),
('خالد علي الدرويش', '28540000055', 'khalid.aldarwish2@edu.gov.qa', 'معلم', '97450000055', 1),
('عائشة راشد الكواري', '28540000056', 'aisha.alkuwari@edu.gov.qa', 'معلم', '97450000056', 1),
('حمد جاسم المري', '28540000057', 'hamad.almurri@edu.gov.qa', 'معلم', '97450000057', 1),
('موزة عبدالله السليطي', '28540000058', 'moza.alsulaiti@edu.gov.qa', 'معلم', '97450000058', 1),
('عبدالعزيز محمد النعيمي', '28540000059', 'abdulaziz.alnaimi@edu.gov.qa', 'معلم', '97450000059', 1),
('لطيفة خالد العمادي', '28540000060', 'latifa.alamadi@edu.gov.qa', 'معلم', '97450000060', 1),
('سعيد أحمد الكبيسي', '28540000061', 'saeed.alkubaisi@edu.gov.qa', 'معلم', '97450000061', 1),
('نورة حمد السويدي', '28540000062', 'noora.alsuwaidi@edu.gov.qa', 'معلم', '97450000062', 1),
('جاسم علي المهندي', '28540000063', 'jassim.almuhannadi@edu.gov.qa', 'معلم', '97450000063', 1),
('فاطمة راشد الهاجري', '28540000064', 'fatima.alhajri@edu.gov.qa', 'معلم', '97450000064', 1),
('عبدالله جاسم العطية', '28540000065', 'abdullah.alattiyah@edu.gov.qa', 'معلم', '97450000065', 1),
('موزة محمد الكواري', '28540000066', 'moza.alkuwari@edu.gov.qa', 'معلم', '97450000066', 1),
('خليفة عبدالله المناعي', '28540000067', 'khalifa.almanai@edu.gov.qa', 'معلم', '97450000067', 1),
('عائشة خالد المالكي', '28540000068', 'aisha.almalki@edu.gov.qa', 'معلم', '97450000068', 1),
('حمد أحمد آل ثاني', '28540000069', 'hamad.althani@edu.gov.qa', 'معلم', '97450000069', 1),
('لطيفة علي البوعينين', '28540000070', 'latifa.alboenain@edu.gov.qa', 'معلم', '97450000070', 1),
('يوسف حمد النعيمي', '28540000071', 'yousef.alnaimi@edu.gov.qa', 'معلم', '97450000071', 1),
('شيخة راشد الدرهم', '28540000072', 'shaikha.aldarham@edu.gov.qa', 'معلم', '97450000072', 1),
('علي جاسم المسند', '28540000073', 'ali.almusnad@edu.gov.qa', 'معلم', '97450000073', 1),
('مريم محمد الدرويش', '28540000074', 'maryam.aldarwish@edu.gov.qa', 'معلم', '97450000074', 1),
('عبدالرحمن عبدالله الكواري', '28540000075', 'abdulrahman.alkuwari@edu.gov.qa', 'معلم', '97450000075', 1),
('نورة خالد المري', '28540000076', 'noora.almurri2@edu.gov.qa', 'معلم', '97450000076', 1),
('جاسم أحمد السليطي', '28540000077', 'jassim.alsulaiti2@edu.gov.qa', 'معلم', '97450000077', 1),
('فاطمة حمد النعيمي', '28540000078', 'fatima.alnaimi2@edu.gov.qa', 'معلم', '97450000078', 1),
('خالد علي العمادي', '28540000079', 'khalid.alamadi@edu.gov.qa', 'معلم', '97450000079', 1),
('عائشة راشد الكبيسي', '28540000080', 'aisha.alkubaisi2@edu.gov.qa', 'معلم', '97450000080', 1),
('حمد جاسم السويدي', '28540000081', 'hamad.alsuwaidi2@edu.gov.qa', 'معلم', '97450000081', 1),
('موزة عبدالله المهندي', '28540000082', 'moza.almuhannadi2@edu.gov.qa', 'معلم', '97450000082', 1),
('عبدالعزيز محمد الهاجري', '28540000083', 'abdulaziz.alhajri@edu.gov.qa', 'معلم', '97450000083', 1),
('لطيفة خالد العطية', '28540000084', 'latifa.alattiyah2@edu.gov.qa', 'معلم', '97450000084', 1),
('سعيد أحمد الكواري', '28540000085', 'saeed.alkuwari2@edu.gov.qa', 'معلم', '97450000085', 1);

-- ربط المنسقين بالمواد (كل منسق مسؤول عن مادة واحدة)
INSERT INTO teacher_subjects (teacher_id, subject_id)
SELECT t.id, s.id
FROM teachers t
CROSS JOIN subjects s
WHERE t.job_title = 'منسق المادة'
AND (
    -- منسق اللغة العربية
    (t.id = (SELECT id FROM teachers WHERE name = 'أحمد محمد السليطي' AND job_title = 'منسق المادة') AND s.name = 'اللغة العربية')
    -- منسق اللغة الإنجليزية
    OR (t.id = (SELECT id FROM teachers WHERE name = 'فاطمة علي المري' AND job_title = 'منسق المادة') AND s.name = 'اللغة الإنجليزية')
    -- منسق الرياضيات
    OR (t.id = (SELECT id FROM teachers WHERE name = 'عبدالرحمن حسن العمادي' AND job_title = 'منسق المادة') AND s.name = 'الرياضيات')
    -- منسق العلوم
    OR (t.id = (SELECT id FROM teachers WHERE name = 'نورة جاسم الكبيسي' AND job_title = 'منسق المادة') AND s.name = 'العلوم')
    -- منسق الفيزياء
    OR (t.id = (SELECT id FROM teachers WHERE name = 'حمد خليفة السويدي' AND job_title = 'منسق المادة') AND s.name = 'الفيزياء')
    -- منسق الكيمياء
    OR (t.id = (SELECT id FROM teachers WHERE name = 'مريم راشد المهندي' AND job_title = 'منسق المادة') AND s.name = 'الكيمياء')
    -- منسق الأحياء
    OR (t.id = (SELECT id FROM teachers WHERE name = 'عبدالله محمد الهاجري' AND job_title = 'منسق المادة') AND s.name = 'الأحياء')
    -- منسق التربية الإسلامية
    OR (t.id = (SELECT id FROM teachers WHERE name = 'عائشة سعيد العطية' AND job_title = 'منسق المادة') AND s.name = 'التربية الإسلامية')
    -- منسق الدراسات الاجتماعية
    OR (t.id = (SELECT id FROM teachers WHERE name = 'يوسف علي الكواري' AND job_title = 'منسق المادة') AND s.name = 'الدراسات الاجتماعية')
    -- منسق التربية الفنية
    OR (t.id = (SELECT id FROM teachers WHERE name = 'لطيفة جاسم المناعي' AND job_title = 'منسق المادة') AND s.name = 'التربية الفنية')
    -- منسق التربية البدنية
    OR (t.id = (SELECT id FROM teachers WHERE name = 'خالد محمد المالكي' AND job_title = 'منسق المادة') AND s.name = 'التربية البدنية')
    -- منسق الحاسب الآلي
    OR (t.id = (SELECT id FROM teachers WHERE name = 'شيخة حمد آل ثاني' AND job_title = 'منسق المادة') AND s.name = 'الحاسب الآلي')
    -- منسق المهارات الحياتية
    OR (t.id = (SELECT id FROM teachers WHERE name = 'جاسم أحمد البوعينين' AND job_title = 'منسق المادة') AND s.name = 'المهارات الحياتية')
    -- منسق التربية الموسيقية
    OR (t.id = (SELECT id FROM teachers WHERE name = 'موزة خليفة النعيمي' AND job_title = 'منسق المادة') AND s.name = 'التربية الموسيقية')
    -- منسق اللغة الفرنسية
    OR (t.id = (SELECT id FROM teachers WHERE name = 'علي حسن الدرهم' AND job_title = 'منسق المادة') AND s.name = 'اللغة الفرنسية')
    -- منسق التصميم والتكنولوجيا
    OR (t.id = (SELECT id FROM teachers WHERE name = 'حصة محمد المسند' AND job_title = 'منسق المادة') AND s.name = 'التصميم والتكنولوجيا')
    -- منسق المكتبة والبحث
    OR (t.id = (SELECT id FROM teachers WHERE name = 'عبدالعزيز جاسم الدرويش' AND job_title = 'منسق المادة') AND s.name = 'المكتبة والبحث')
);

-- ربط الموجهين بالمواد (كل موجه مسؤول عن مادة واحدة)
INSERT INTO teacher_subjects (teacher_id, subject_id)
SELECT t.id, s.id
FROM teachers t
CROSS JOIN subjects s
WHERE t.job_title = 'موجه المادة'
AND (
    -- موجه اللغة العربية
    (t.id = (SELECT id FROM teachers WHERE name = 'د. ناصر علي المري' AND job_title = 'موجه المادة') AND s.name = 'اللغة العربية')
    -- موجه اللغة الإنجليزية
    OR (t.id = (SELECT id FROM teachers WHERE name = 'د. سارة محمد الكواري' AND job_title = 'موجه المادة') AND s.name = 'اللغة الإنجليزية')
    -- موجه الرياضيات
    OR (t.id = (SELECT id FROM teachers WHERE name = 'د. جاسم خالد النعيمي' AND job_title = 'موجه المادة') AND s.name = 'الرياضيات')
    -- موجه العلوم
    OR (t.id = (SELECT id FROM teachers WHERE name = 'د. عائشة أحمد السليطي' AND job_title = 'موجه المادة') AND s.name = 'العلوم')
    -- موجه الفيزياء
    OR (t.id = (SELECT id FROM teachers WHERE name = 'د. خليفة حمد المهندي' AND job_title = 'موجه المادة') AND s.name = 'الفيزياء')
    -- موجه الكيمياء
    OR (t.id = (SELECT id FROM teachers WHERE name = 'د. نورة عبدالله العطية' AND job_title = 'موجه المادة') AND s.name = 'الكيمياء')
    -- موجه الأحياء
    OR (t.id = (SELECT id FROM teachers WHERE name = 'د. محمد راشد الهاجري' AND job_title = 'موجه المادة') AND s.name = 'الأحياء')
    -- موجه التربية الإسلامية
    OR (t.id = (SELECT id FROM teachers WHERE name = 'د. فاطمة جاسم المناعي' AND job_title = 'موجه المادة') AND s.name = 'التربية الإسلامية')
    -- موجه الدراسات الاجتماعية
    OR (t.id = (SELECT id FROM teachers WHERE name = 'د. عبدالرحمن علي البوعينين' AND job_title = 'موجه المادة') AND s.name = 'الدراسات الاجتماعية')
    -- موجه التربية الفنية
    OR (t.id = (SELECT id FROM teachers WHERE name = 'د. موزة سعيد آل ثاني' AND job_title = 'موجه المادة') AND s.name = 'التربية الفنية')
    -- موجه التربية البدنية
    OR (t.id = (SELECT id FROM teachers WHERE name = 'د. حمد خالد الدرويش' AND job_title = 'موجه المادة') AND s.name = 'التربية البدنية')
    -- موجه الحاسب الآلي
    OR (t.id = (SELECT id FROM teachers WHERE name = 'د. لطيفة محمد السويدي' AND job_title = 'موجه المادة') AND s.name = 'الحاسب الآلي')
    -- موجه المهارات الحياتية
    OR (t.id = (SELECT id FROM teachers WHERE name = 'د. يوسف حسن العمادي' AND job_title = 'موجه المادة') AND s.name = 'المهارات الحياتية')
    -- موجه التربية الموسيقية
    OR (t.id = (SELECT id FROM teachers WHERE name = 'د. شيخة جاسم الكبيسي' AND job_title = 'موجه المادة') AND s.name = 'التربية الموسيقية')
    -- موجه اللغة الفرنسية
    OR (t.id = (SELECT id FROM teachers WHERE name = 'د. علي عبدالله المالكي' AND job_title = 'موجه المادة') AND s.name = 'اللغة الفرنسية')
    -- موجه التصميم والتكنولوجيا
    OR (t.id = (SELECT id FROM teachers WHERE name = 'د. مريم خليفة الدرهم' AND job_title = 'موجه المادة') AND s.name = 'التصميم والتكنولوجيا')
    -- موجه المكتبة والبحث
    OR (t.id = (SELECT id FROM teachers WHERE name = 'د. عبدالعزيز محمد المسند' AND job_title = 'موجه المادة') AND s.name = 'المكتبة والبحث')
); 