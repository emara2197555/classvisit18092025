-- إدراج بيانات تجريبية للمنسقين والموجهين

-- إدراج معلمين بوظائف منسق وموجه
INSERT INTO teachers (name, job_title, school_id, created_at) VALUES 
('أحمد محمد - منسق الرياضيات', 'منسق المادة', 1, NOW()),
('سارة أحمد - موجه الرياضيات', 'موجه المادة', 1, NOW()),
('محمد علي - منسق العلوم', 'منسق المادة', 1, NOW()),
('فاطمة سعد - موجه العلوم', 'موجه المادة', 1, NOW());

-- ربط المنسقين بالمواد في coordinator_supervisors  
INSERT INTO coordinator_supervisors (user_id, subject_id, created_at) VALUES
(1, 1, NOW()), -- منسق الرياضيات
(3, 2, NOW()); -- منسق العلوم

-- ربط المنسقين والموجهين بالمواد في teacher_subjects
INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES
((SELECT id FROM teachers WHERE name LIKE '%منسق الرياضيات%'), 1), -- الرياضيات
((SELECT id FROM teachers WHERE name LIKE '%موجه الرياضيات%'), 1), -- الرياضيات  
((SELECT id FROM teachers WHERE name LIKE '%منسق العلوم%'), 2), -- العلوم
((SELECT id FROM teachers WHERE name LIKE '%موجه العلوم%'), 2); -- العلوم
