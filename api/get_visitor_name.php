<?php
/**
 * API لجلب أسماء الزائرين حسب نوع الزائر
 */

// تضمين ملف الاتصال بقاعدة البيانات
require_once '../includes/db_connection.php';
require_once '../includes/auth_functions.php';

// بدء الجلسة للتحقق من صلاحيات المستخدم
session_start();

// التحقق من وجود المعرف المطلوب
if (!isset($_GET['visitor_type_id']) || empty($_GET['visitor_type_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'معرف نوع الزائر مطلوب',
        'visitors' => []
    ]);
    exit;
}

$visitor_type_id = (int)$_GET['visitor_type_id'];
$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : null;
$school_id = isset($_GET['school_id']) ? (int)$_GET['school_id'] : null;

// الحصول على بيانات المستخدم الحالي
$current_user_role = $_SESSION['role_name'] ?? 'admin';
$current_user_id = $_SESSION['user_id'] ?? null;

try {
    // جلب نوع الزائر أولاً للتحقق من الوظيفة
    $visitor_type = query_row("SELECT name FROM visitor_types WHERE id = ?", [$visitor_type_id]);
    
    if (!$visitor_type) {
        throw new Exception("نوع الزائر غير موجود");
    }
    
    // تحديد الوظيفة المطلوبة بناءً على نوع الزائر
    $job_title = '';
    $additional_condition = '';
    $params = [];
    
    switch ($visitor_type['name']) {
        case 'مدير المدرسة':
            $job_title = 'مدير';
            if ($school_id) {
                $additional_condition = "AND school_id = ?";
                $params[] = $school_id;
            }
            break;
        case 'المدير':
            $job_title = 'مدير';
            if ($school_id) {
                $additional_condition = "AND school_id = ?";
                $params[] = $school_id;
            }
            break;
        case 'نائب المدير للشؤون الأكاديمية':
            $job_title = 'النائب الأكاديمي';
            if ($school_id) {
                $additional_condition = "AND school_id = ?";
                $params[] = $school_id;
            }
            break;
        case 'منسق المادة':
            $job_title = 'منسق المادة';
            
            // إذا كان المستخدم الحالي منسق مادة، فقط يستطيع رؤية نفسه
            if ($current_user_role === 'Subject Coordinator') {
                $visitors = query("
                    SELECT id, name 
                    FROM teachers 
                    WHERE id = ? AND job_title = ?
                ", [$current_user_id, $job_title]);
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => '',
                    'visitors' => $visitors
                ]);
                exit;
            }
            
            if ($subject_id) {
                // للمنسق نتحقق من وجود المادة المحددة
                $sql = "
                    SELECT t.id, t.name 
                    FROM teachers t
                    JOIN teacher_subjects ts ON t.id = ts.teacher_id
                    WHERE t.job_title = ? AND ts.subject_id = ?";
                
                if ($school_id) {
                    $sql .= " AND t.school_id = ?";
                    $visitors = query($sql, [$job_title, $subject_id, $school_id]);
                } else {
                    $visitors = query($sql, [$job_title, $subject_id]);
                }
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => '',
                    'visitors' => $visitors
                ]);
                exit;
            }
            break;
        case 'موجه المادة':
            $job_title = 'موجه المادة';
            
            // إذا كان المستخدم الحالي منسق مادة، فقط يستطيع رؤية موجهي مادته
            if ($current_user_role === 'Subject Coordinator') {
                // جلب مادة منسق المادة
                $coordinator_data = query_row("
                    SELECT subject_id 
                    FROM coordinator_supervisors 
                    WHERE user_id = ?
                ", [$current_user_id]);
                
                if ($coordinator_data) {
                    $sql = "
                        SELECT t.id, t.name 
                        FROM teachers t
                        JOIN teacher_subjects ts ON t.id = ts.teacher_id
                        WHERE t.job_title = ? AND ts.subject_id = ?";
                    
                    $visitors = query($sql, [$job_title, $coordinator_data['subject_id']]);
                } else {
                    $visitors = [];
                }
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => '',
                    'visitors' => $visitors
                ]);
                exit;
            }
            
            if ($subject_id) {
                // للموجه نتحقق من وجود المادة المحددة بغض النظر عن المدرسة
                $sql = "
                    SELECT t.id, t.name 
                    FROM teachers t
                    JOIN teacher_subjects ts ON t.id = ts.teacher_id
                    WHERE t.job_title = ? AND ts.subject_id = ?";
                
                $visitors = query($sql, [$job_title, $subject_id]);
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => '',
                    'visitors' => $visitors
                ]);
                exit;
            }
            break;
        default:
            $job_title = $visitor_type['name'];
            break;
    }
    
    // جلب المعلمين بالوظيفة المحددة
    $sql = "SELECT id, name FROM teachers WHERE job_title = ? $additional_condition ORDER BY name";
    array_unshift($params, $job_title);
    $visitors = query($sql, $params);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => '',
        'visitors' => $visitors
    ]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'visitors' => []
    ]);
} 