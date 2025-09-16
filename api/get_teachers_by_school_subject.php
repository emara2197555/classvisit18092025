<?php
// تضمين ملفات قاعدة البيانات والوظائف
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';
require_once '../includes/auth_functions.php';

// التحقق من تسجيل الدخول
session_start();
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role_name = $_SESSION['role_name'];

// استلام معرف المدرسة والمادة
$school_id = isset($_GET['school_id']) ? (int)$_GET['school_id'] : 0;
$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

// التحقق من صلاحيات منسق المادة
if ($user_role_name === 'Subject Coordinator') {
    $coordinator_data = query_row("
        SELECT subject_id 
        FROM coordinator_supervisors 
        WHERE user_id = ?
    ", [$user_id]);
    
    if (!$coordinator_data) {
        http_response_code(403);
        echo json_encode(['error' => 'No subject assigned']);
        exit;
    }
    
    // تأكد من أن منسق المادة لا يمكنه الوصول إلا لمادته
    if ($subject_id > 0 && $subject_id != $coordinator_data['subject_id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied to this subject']);
        exit;
    }
    
    // فرض معرف المادة للمنسق
    $subject_id = $coordinator_data['subject_id'];
}

try {
    // بناء الاستعلام حسب المعلمات المتوفرة
    if ($school_id > 0 && $subject_id > 0) {
        // فلترة حسب المدرسة والمادة
        $query = "
            SELECT DISTINCT t.id, t.name
            FROM teachers t
            JOIN teacher_subjects ts ON t.id = ts.teacher_id
            WHERE t.school_id = ? AND ts.subject_id = ?
            ORDER BY t.name
        ";
        $teachers = query($query, [$school_id, $subject_id]);
    } elseif ($school_id > 0) {
        // فلترة حسب المدرسة فقط
        $query = "
            SELECT DISTINCT t.id, t.name
            FROM teachers t
            WHERE t.school_id = ?
            ORDER BY t.name
        ";
        $teachers = query($query, [$school_id]);
    } elseif ($subject_id > 0) {
        // فلترة حسب المادة فقط
        $query = "
            SELECT DISTINCT t.id, t.name
            FROM teachers t
            JOIN teacher_subjects ts ON t.id = ts.teacher_id
            WHERE ts.subject_id = ?
            ORDER BY t.name
        ";
        $teachers = query($query, [$subject_id]);
    } else {
        // إذا لم يتم تحديد أي منهما، ارجع جميع المعلمين
        $teachers = query("SELECT id, name FROM teachers ORDER BY name");
    }

    // إرجاع البيانات بتنسيق JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'teachers' => $teachers,
        'message' => count($teachers) > 0 ? 'تم جلب المعلمين بنجاح' : 'لا توجد معلمين متاحين'
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'teachers' => [],
        'message' => 'خطأ في جلب البيانات: ' . $e->getMessage()
    ]);
}
?> 