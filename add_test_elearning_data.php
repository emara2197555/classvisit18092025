<?php
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

echo "<h2>إضافة بيانات اختبار للتعليم الإلكتروني</h2>";

try {
    // التحقق من وجود البيانات المرجعية المطلوبة
    $academic_year = query("SELECT id FROM academic_years LIMIT 1");
    $school = query("SELECT id FROM schools LIMIT 1");
    $subject = query("SELECT id FROM subjects LIMIT 1");
    $teacher = query("SELECT id FROM teachers LIMIT 1");
    $grade = query("SELECT id FROM grades LIMIT 1");
    $section = query("SELECT id FROM sections LIMIT 1");
    
    if (empty($academic_year) || empty($school) || empty($subject) || empty($teacher) || empty($grade) || empty($section)) {
        echo "❌ خطأ: البيانات المرجعية غير مكتملة. يرجى التأكد من وجود:<br>";
        echo "- سنوات دراسية<br>";
        echo "- مدارس<br>";
        echo "- مواد<br>";
        echo "- معلمين<br>";
        echo "- صفوف<br>";
        echo "- شعب<br>";
        exit;
    }
    
    // إضافة سجلات اختبار
    $test_data = [
        [
            'academic_year_id' => $academic_year[0]['id'],
            'school_id' => $school[0]['id'],
            'subject_id' => $subject[0]['id'],
            'teacher_id' => $teacher[0]['id'],
            'grade_id' => $grade[0]['id'],
            'section_id' => $section[0]['id'],
            'lesson_date' => '2024-12-03',
            'lesson_number' => 1,
            'num_students' => 25,
            'attendance_students' => 23,
            'attendance_type' => 'direct',
            'elearning_tools' => '["qatar_system", "tablets", "interactive_display"]',
            'lesson_topic' => 'الدرس الأول: مقدمة في التعليم الإلكتروني',
            'attendance_rating' => 'excellent',
            'coordinator_id' => 1,
            'notes' => 'حصة ممتازة واستخدام فعال للأدوات'
        ],
        [
            'academic_year_id' => $academic_year[0]['id'],
            'school_id' => $school[0]['id'],
            'subject_id' => $subject[0]['id'],
            'teacher_id' => $teacher[0]['id'],
            'grade_id' => $grade[0]['id'],
            'section_id' => $section[0]['id'],
            'lesson_date' => '2024-12-02',
            'lesson_number' => 2,
            'num_students' => 25,
            'attendance_students' => 20,
            'attendance_type' => 'remote',
            'elearning_tools' => '["qatar_system", "ai_applications"]',
            'lesson_topic' => 'الدرس الثاني: التطبيقات التفاعلية',
            'attendance_rating' => 'very_good',
            'coordinator_id' => 1,
            'notes' => 'حصة جيدة مع تحسن في المشاركة'
        ],
        [
            'academic_year_id' => $academic_year[0]['id'],
            'school_id' => $school[0]['id'],
            'subject_id' => $subject[0]['id'],
            'teacher_id' => $teacher[0]['id'],
            'grade_id' => $grade[0]['id'],
            'section_id' => $section[0]['id'],
            'lesson_date' => '2024-12-01',
            'lesson_number' => 3,
            'num_students' => 25,
            'attendance_students' => 18,
            'attendance_type' => 'direct',
            'elearning_tools' => '["interactive_websites", "tablets"]',
            'lesson_topic' => 'الدرس الثالث: المواقع التفاعلية',
            'attendance_rating' => 'good',
            'coordinator_id' => 1,
            'notes' => 'حضور جيد ولكن يحتاج لمزيد من التفاعل'
        ]
    ];
    
    foreach ($test_data as $data) {
        $sql = "INSERT INTO elearning_attendance 
                (academic_year_id, school_id, subject_id, teacher_id, grade_id, section_id,
                 lesson_date, lesson_number, num_students, attendance_students, attendance_type, 
                 elearning_tools, lesson_topic, attendance_rating, coordinator_id, notes, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
        execute($sql, [
            $data['academic_year_id'], $data['school_id'], $data['subject_id'], $data['teacher_id'], 
            $data['grade_id'], $data['section_id'], $data['lesson_date'], $data['lesson_number'], 
            $data['num_students'], $data['attendance_students'], $data['attendance_type'], 
            $data['elearning_tools'], $data['lesson_topic'], $data['attendance_rating'], 
            $data['coordinator_id'], $data['notes']
        ]);
        
        echo "✅ تم إضافة سجل: " . $data['lesson_topic'] . "<br>";
    }
    
    echo "<h3>✅ تم إضافة جميع البيانات الاختبارية بنجاح!</h3>";
    echo "<p><a href='elearning_attendance_reports.php'>عرض التقارير</a></p>";
    echo "<p><a href='elearning_coordinator_dashboard.php'>لوحة التحكم</a></p>";
    
} catch (Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "<br>";
    echo "تفاصيل الخطأ: " . $e->getTraceAsString();
}
?>
