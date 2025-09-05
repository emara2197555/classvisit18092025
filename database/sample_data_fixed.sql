-- البيانات التجريبية لنظام الصلاحيات (محدث)
-- تاريخ الإنشاء: 2025-09-05

-- إنشاء مستخدم مدير افتراضي
-- كلمة المرور: admin123
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role_id`, `is_active`) VALUES
('admin_user', 'admin@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'مدير النظام', 1, 1);

-- إنشاء مستخدمين تجريبيين

-- مستخدم مدير المدرسة
-- كلمة المرور: director123
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role_id`, `school_id`, `is_active`) VALUES
('director_user', 'director@school1.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'أحمد محمد الغانم - مدير المدرسة', 2, 1, 1);

-- مستخدم النائب الأكاديمي
-- كلمة المرور: academic123
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role_id`, `school_id`, `is_active`) VALUES
('academic_user', 'academic@school1.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'سعد عبدالله الكبيسي - النائب الأكاديمي', 3, 1, 1);

-- مشرف تربوي
-- كلمة المرور: supervisor123
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role_id`, `school_id`, `is_active`) VALUES
('supervisor_user', 'supervisor@school1.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'محمد علي الكواري - مشرف تربوي', 4, 1, 1);

-- منسق مادة الرياضيات
-- كلمة المرور: coordinator123
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role_id`, `school_id`, `is_active`) VALUES
('coordinator_user', 'coordinator.math@school1.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'خالد أحمد المري - منسق الرياضيات', 5, 1, 1);

-- معلم
-- كلمة المرور: teacher123
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role_id`, `school_id`, `is_active`) VALUES
('teacher_user', 'teacher@school1.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'حمد علي الهاجري - معلم رياضيات', 6, 1, 1);

-- ربط منسق الرياضيات بمادة الرياضيات
-- (بفرض أن معرف مادة الرياضيات هو 1)
INSERT INTO `coordinator_supervisors` (`user_id`, `subject_id`, `school_id`) VALUES
((SELECT id FROM users WHERE username = 'coordinator_user'), 1, 1);

-- إضافة علاقة المعلم بالمعلم الموجود في النظام
-- تحديث user_id في جدول teachers للمعلم الأول
UPDATE `teachers` SET `user_id` = (SELECT id FROM users WHERE username = 'teacher_user') WHERE id = 1 LIMIT 1;

-- إنشاء فهارس لتحسين الأداء (بدون IF NOT EXISTS)
-- CREATE INDEX idx_users_role_school ON users(role_id, school_id);
-- CREATE INDEX idx_coordinator_supervisors_lookup ON coordinator_supervisors(user_id, subject_id, school_id);
