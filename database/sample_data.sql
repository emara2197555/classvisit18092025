-- البيانات التجريبية لنظام الصلاحيات
-- تاريخ الإنشاء: 2025-09-05

-- إنشاء مستخدم مدير افتراضي
-- كلمة المرور: admin123 (يجب تغييرها بعد أول تسجيل دخول)
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role_id`, `is_active`) VALUES
('admin', 'admin@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'مدير النظام', 1, 1);

-- إنشاء مستخدمين تجريبيين (بناءً على البيانات الموجودة)

-- مستخدم مدير المدرسة
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role_id`, `school_id`, `is_active`) VALUES
('director1', 'director@school1.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'أحمد محمد الغانم - مدير المدرسة', 2, 1, 1);

-- مستخدم النائب الأكاديمي
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role_id`, `school_id`, `is_active`) VALUES
('academic1', 'academic@school1.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'سعد عبدالله الكبيسي - النائب الأكاديمي', 3, 1, 1);

-- منسقي المواد (بناءً على المواد الموجودة)
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role_id`, `school_id`, `subject_id`, `is_active`) VALUES
('coord_math', 'coord.math@school1.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'خالد أحمد المري - منسق الرياضيات', 4, 1, 1, 1),
('coord_science', 'coord.science@school1.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'فاطمة علي الهاجري - منسق العلوم', 4, 1, 2, 1),
('coord_arabic', 'coord.arabic@school1.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'محمد سالم الدوسري - منسق اللغة العربية', 4, 1, 3, 1),
('coord_english', 'coord.english@school1.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'نورة جاسم الكبيسي - منسق اللغة الإنجليزية', 4, 1, 4, 1);

-- المعلمين (بناءً على المعلمين الموجودين)
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role_id`, `school_id`, `teacher_id`, `is_active`) VALUES
('teacher1', 'hamad.alhajri@school1.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'حمد علي الهاجري', 5, 1, 1, 1),
('teacher2', 'noura.alkubaisi@school1.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'نورة جاسم الكبيسي', 5, 1, 2, 1),
('teacher3', 'hamad.almari@school1.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'حمد جاسم المري', 5, 1, 3, 1),
('teacher4', 'jasim.aldarham@school1.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'جاسم أحمد الدرهم', 5, 1, 4, 1),
('teacher5', 'fatima.alansari@school1.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'فاطمة عبدالله الأنصاري', 5, 1, 5, 1),
('teacher6', 'mohammed.althani@school1.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'محمد سعد الثاني', 5, 1, 6, 1);

-- ربط المنسقين بالموجهين في مادتهم
-- (بناءً على أن الموجهين موجودين في جدول visitor_types)

-- منسق الرياضيات مع موجه الرياضيات
INSERT INTO `coordinator_supervisors` (`coordinator_id`, `supervisor_id`, `subject_id`, `school_id`) VALUES
((SELECT id FROM users WHERE username = 'coord_math'), 1, 1, 1),
((SELECT id FROM users WHERE username = 'coord_math'), 2, 1, 1);

-- منسق العلوم مع موجه العلوم
INSERT INTO `coordinator_supervisors` (`coordinator_id`, `supervisor_id`, `subject_id`, `school_id`) VALUES
((SELECT id FROM users WHERE username = 'coord_science'), 1, 2, 1),
((SELECT id FROM users WHERE username = 'coord_science'), 3, 2, 1);

-- منسق اللغة العربية مع موجه اللغة العربية
INSERT INTO `coordinator_supervisors` (`coordinator_id`, `supervisor_id`, `subject_id`, `school_id`) VALUES
((SELECT id FROM users WHERE username = 'coord_arabic'), 1, 3, 1),
((SELECT id FROM users WHERE username = 'coord_arabic'), 4, 3, 1);

-- منسق اللغة الإنجليزية مع موجه اللغة الإنجليزية
INSERT INTO `coordinator_supervisors` (`coordinator_id`, `supervisor_id`, `subject_id`, `school_id`) VALUES
((SELECT id FROM users WHERE username = 'coord_english'), 1, 4, 1),
((SELECT id FROM users WHERE username = 'coord_english'), 2, 4, 1);

-- إنشاء فهارس إضافية لتحسين الأداء
CREATE INDEX IF NOT EXISTS idx_users_role_school ON users(role_id, school_id);
CREATE INDEX IF NOT EXISTS idx_users_subject_teacher ON users(subject_id, teacher_id);
CREATE INDEX IF NOT EXISTS idx_coordinator_supervisors_lookup ON coordinator_supervisors(coordinator_id, subject_id, school_id);
