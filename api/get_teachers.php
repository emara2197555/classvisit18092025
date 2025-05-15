<?php
/**
 * API لجلب المعلمين حسب المدرسة والمادة
 */

// تضمين ملف الاتصال بقاعدة البيانات
require_once '../includes/db_connection.php';

// التحقق من وجود المعرفات المطلوبة
if (!isset($_GET['school_id']) || empty($_GET['school_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'معرف المدرسة مطلوب',
        'teachers' => []
    ]);
    exit;
}

$school_id = (int)$_GET['school_id'];
$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : null;
$exclude_roles = isset($_GET['exclude_roles']) ? $_GET['exclude_roles'] : '';

// تحويل القائمة المفصولة بفواصل إلى مصفوفة
$excluded_roles = [];
if (!empty($exclude_roles)) {
    $excluded_roles = explode(',', $exclude_roles);
    // تنظيف البيانات
    foreach ($excluded_roles as &$role) {
        $role = sanitize(trim($role));
    }
}

try {
    if ($subject_id) {
        // بناء جملة الاستعلام مع الاستبعاد إذا كان مطلوباً
        $sql = "SELECT t.id, t.name 
                FROM teachers t
                JOIN teacher_subjects ts ON t.id = ts.teacher_id
                WHERE t.school_id = ? AND ts.subject_id = ?";
                
        $params = [$school_id, $subject_id];
        
        // إضافة شرط لاستبعاد المعلمين حسب الوظيفة
        if (!empty($excluded_roles)) {
            $placeholders = implode(',', array_fill(0, count($excluded_roles), '?'));
            $sql .= " AND t.job_title NOT IN ($placeholders)";
            $params = array_merge($params, $excluded_roles);
        }
        
        $sql .= " ORDER BY t.name";
        $teachers = query($sql, $params);
    } else {
        // بناء جملة الاستعلام للمعلمين بدون تحديد المادة
        $sql = "SELECT id, name FROM teachers WHERE school_id = ?";
        
        $params = [$school_id];
        
        // إضافة شرط لاستبعاد المعلمين حسب الوظيفة
        if (!empty($excluded_roles)) {
            $placeholders = implode(',', array_fill(0, count($excluded_roles), '?'));
            $sql .= " AND job_title NOT IN ($placeholders)";
            $params = array_merge($params, $excluded_roles);
        }
        
        $sql .= " ORDER BY name";
        $teachers = query($sql, $params);
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => '',
        'teachers' => $teachers
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ أثناء جلب البيانات',
        'teachers' => []
    ]);
} 