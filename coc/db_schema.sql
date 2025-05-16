-- إنشاء قاعدة البيانات
CREATE DATABASE IF NOT EXISTS classvisit_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE classvisit_db;

-- جدول المدارس
CREATE TABLE IF NOT EXISTS schools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    school_code VARCHAR(50) UNIQUE,
    email VARCHAR(255),
    phone VARCHAR(50),
    address TEXT,
    logo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول المراحل التعليمية
CREATE TABLE IF NOT EXISTS educational_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الصفوف
CREATE TABLE IF NOT EXISTS grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    level_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (level_id) REFERENCES educational_levels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الشعب
CREATE TABLE IF NOT EXISTS sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    grade_id INT NOT NULL,
    school_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (grade_id) REFERENCES grades(id) ON DELETE CASCADE,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول المواد الدراسية
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    school_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول المعلمين
CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    personal_id VARCHAR(50) UNIQUE,
    email VARCHAR(255),
    job_title VARCHAR(255),
    school_id INT,
    phone VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول ربط المعلمين بالمواد
CREATE TABLE IF NOT EXISTS teacher_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_subject (teacher_id, subject_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول أنواع الزائرين
CREATE TABLE IF NOT EXISTS visitor_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول مجالات التقييم
CREATE TABLE IF NOT EXISTS evaluation_domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول مؤشرات التقييم
CREATE TABLE IF NOT EXISTS evaluation_indicators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name TEXT NOT NULL,
    domain_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (domain_id) REFERENCES evaluation_domains(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول التوصيات
CREATE TABLE IF NOT EXISTS recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    text TEXT NOT NULL,
    indicator_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (indicator_id) REFERENCES evaluation_indicators(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الزيارات الصفية
CREATE TABLE IF NOT EXISTS visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    grade_id INT NOT NULL,
    section_id INT NOT NULL,
    level_id INT NOT NULL,
    visitor_type_id INT NOT NULL,
    visitor_person_id INT,
    visit_date DATE NOT NULL,
    visit_type ENUM('full', 'partial') NOT NULL DEFAULT 'full',
    attendance_type ENUM('physical', 'remote', 'hybrid') NOT NULL DEFAULT 'physical',
    has_lab TINYINT(1) NOT NULL DEFAULT 0,
    general_notes TEXT,
    recommendation_notes TEXT,
    appreciation_notes TEXT,
    total_score DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (visitor_type_id) REFERENCES visitor_types(id) ON DELETE CASCADE,
    FOREIGN KEY (grade_id) REFERENCES grades(id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    FOREIGN KEY (level_id) REFERENCES educational_levels(id) ON DELETE CASCADE,
    FOREIGN KEY (visitor_person_id) REFERENCES teachers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول تقييمات المؤشرات للزيارة
CTREAE TABLE IF NOT EXISTS visit_evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visit_id INT NOT NULL,
    indicator_id INT NOT NULL,
    score TINYINT NOT NULL,
    recommendation_id INT,
    custom_recommendation TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE,
    FOREIGN KEY (indicator_id) REFERENCES evaluation_indicators(id) ON DELETE CASCADE,
    FOREIGN KEY (recommendation_id) REFERENCES recommendations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدخال البيانات الأساسية: أنواع الزائرين
INSERT INTO visitor_types (name) VALUES 
('المدير'),
('النائب الأكاديمي'),
('منسق المادة'),
('موجه المادة');

-- إدخال البيانات الأساسية: مجالات التقييم
INSERT INTO evaluation_domains (name) VALUES 
('التخطيط للدرس'),
('تنفيذ الدرس'),
('التقويم'),
('الإدارة الصفية وبيئة التعلم'),
('جزء خاص بمادة العلوم (النشاط العملي)');

-- إدخال البيانات الأساسية: المراحل التعليمية
INSERT INTO educational_levels (name) VALUES 
('الابتدائية'),
('الإعدادية'),
('الثانوية');

-- الصفوف للمرحلة الابتدائية
INSERT INTO grades (name, level_id) VALUES 
('الصف الأول', 1),
('الصف الثاني', 1),
('الصف الثالث', 1),
('الصف الرابع', 1),
('الصف الخامس', 1),
('الصف السادس', 1);

-- الصفوف للمرحلة المتوسطة
INSERT INTO grades (name, level_id) VALUES 
('الصف السابع', 2),
('الصف الثامن', 2),
('الصف التاسع', 2);

-- الصفوف للمرحلة الثانوية
INSERT INTO grades (name, level_id) VALUES 
('الصف العاشر', 3),
('الصف الحادي عشر', 3),
('الصف الثاني عشر', 3);

-- إدخال البيانات الأساسية: مؤشرات التقييم
-- مؤشرات التخطيط للدرس (المجال 1)
INSERT INTO evaluation_indicators (name, domain_id) VALUES 
('خطة الدرس متوفرة وبنودها مستكملة ومناسبة.', 1),
('أهداف التعلم مناسبة ودقيقة الصياغة وقابلة للقياس.', 1),
('أنشطة الدرس الرئيسة واضحة ومتدرجة ومرتبطة بالأهداف.', 1);

-- مؤشرات تنفيذ الدرس (المجال 2)
INSERT INTO evaluation_indicators (name, domain_id) VALUES 
('أهداف التعلم معروضة ويتم مناقشتها .', 2),
('أنشطة التمهيد مفعلة بشكل مناسب.', 2),
('محتوى الدرس واضح والعرض منظّم ومترابط.', 2),
('طرائق التدريس وإستراتيجياته متنوعه وتتمحور حول الطالب.', 2),
('مصادر التعلم الرئيسة والمساندة موظّفة بصورة واضحة وسليمة.', 2),
('الوسائل التعليميّة والتكنولوجيا موظّفة بصورة مناسبة.', 2),
('الأسئلة الصفية ذات صياغة سليمة ومتدرجة ومثيرة للتفكير .', 2),
('المادة العلمية دقيقة و مناسبة.', 2),
('الكفايات الأساسية متضمنة في السياق المعرفي للدرس.', 2),
('القيم الأساسية متضمنة في السياق المعرفي للدرس.', 2),
('التكامل بين محاور المادة ومع المواد الأخرى يتم بشكل مناسب.', 2),
('الفروق الفردية بين الطلبة يتم مراعاتها.', 2),
('غلق الدرس يتم بشكل مناسب.', 2);

-- مؤشرات التقويم (المجال 3)
INSERT INTO evaluation_indicators (name, domain_id) VALUES 
('أساليب التقويم ( القبلي والبنائي والختامي ) مناسبة ومتنوعة.', 3),
('التغذية الراجعة متنوعة ومستمرة', 3),
('أعمال الطلبة متابعة ومصححة بدقة ورقيًا وإلكترونيًا .', 3);

-- مؤشرات الإدارة الصفية (المجال 4)
INSERT INTO evaluation_indicators (name, domain_id) VALUES 
('البيئة الصفية إيجابية وآمنة وداعمة للتعلّم.', 4),
('إدارة أنشطة التعلّم والمشاركات الصّفيّة تتم بصورة منظمة.', 4),
('قوانين إدارة الصف وإدارة السلوك مفعّلة.', 4),
('الاستثمار الأمثل لزمن الحصة', 4);

-- مؤشرات النشاط العملي (المجال 5)
INSERT INTO evaluation_indicators (name, domain_id) VALUES 
('مدى صلاحية وتوافر الأدوات اللازمة لتنفيذ النشاط العملي.', 5),
('شرح إجراءات الأمن والسلامة المناسبة للتجربة ومتابعة تفعيلها.', 5),
('إعطاء تعليمات واضحة وسليمة لأداء النشاط العملي قبل وأثناء التنفيذ.', 5),
('تسجيل الطلبة للملاحظات والنتائج أثناء تنفيذ النشاط العملي.', 5),
('تقويم مهارات الطلبة أثناء تنفيذ النشاط العملي.', 5),
('تنويع أساليب تقديم التغذية الراجعة للطلبة لتنمية مهاراتهم.', 5);

-- إدخال البيانات الأساسية: المواد الدراسية
INSERT INTO subjects (name, school_id) VALUES 
('العلوم', NULL),
('الرياضيات', NULL),
('اللغة العربية', NULL),
('اللغة الإنجليزية', NULL); 