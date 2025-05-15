<?php
/**
 * API لجلب المعلمين حسب المدرسة والمادة ونوع الزائر
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
$visitor_type_id = isset($_GET['visitor_type_id']) ? (int)$_GET['visitor_type_id'] : null;
$visitor_person_id = isset($_GET['visitor_person_id']) ? (int)$_GET['visitor_person_id'] : null;

// تحويل القائمة المفصولة بفواصل إلى مصفوفة
$excluded_roles = [];
if (!empty($exclude_roles)) {
    $excluded_roles = explode(',', $exclude_roles);
    // تنظيف البيانات
    foreach ($excluded_roles as &$role) {
        $role = sanitize(trim($role));
    }
}

// التحقق من نوع الزائر لتطبيق فلتر الصلاحيات
if (!$visitor_type_id) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'يجب تحديد نوع الزائر',
        'teachers' => []
    ]);
    exit;
}

// جلب معلومات نوع الزائر
$visitor_type = query_row("SELECT name FROM visitor_types WHERE id = ?", [$visitor_type_id]);
if (!$visitor_type) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'نوع الزائر غير موجود',
        'teachers' => []
    ]);
    exit;
}

// تحديد نوع الزائر وصلاحياته
$visitor_role = '';
switch ($visitor_type['name']) {
    case 'مدير المدرسة':
        $visitor_role = 'مدير';
        break;
    case 'نائب المدير للشؤون الأكاديمية':
        $visitor_role = 'النائب الأكاديمي';
        break;
    case 'منسق المادة':
        $visitor_role = 'منسق المادة';
        break;
    case 'موجه المادة':
        $visitor_role = 'موجه المادة';
        break;
    default:
        $visitor_role = $visitor_type['name'];
}

try {
    // إذا كان المستخدم موجهاً للمادة
    if ($visitor_role === 'موجه المادة') {
        // الموجه يستطيع زيارة كل المعلمين والمنسقين في نفس المادة بجميع المدارس
        if (!$subject_id) {
            // يجب تحديد المادة لموجه المادة
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'يجب تحديد المادة لموجه المادة',
                'teachers' => []
            ]);
            exit;
        }
        
        // جلب مادة الموجه لمقارنتها
        if ($visitor_person_id) {
            $visitor_subject = query_row("
                SELECT subject_id 
                FROM teacher_subjects 
                WHERE teacher_id = ?
            ", [$visitor_person_id]);
            
            if (!$visitor_subject || $visitor_subject['subject_id'] != $subject_id) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'لا يمكن للموجه زيارة معلمين في مادة مختلفة عن مادته',
                    'teachers' => []
                ]);
                exit;
            }
        }
        
        $sql = "
            SELECT DISTINCT t.id, t.name 
            FROM teachers t
            JOIN teacher_subjects ts ON t.id = ts.teacher_id
            WHERE ts.subject_id = ?
            AND (t.job_title = 'معلم' OR t.job_title = 'منسق المادة')
            ORDER BY t.name
        ";
        $params = [$subject_id];
    }
    // إذا كان المستخدم منسقاً للمادة
    else if ($visitor_role === 'منسق المادة') {
        // المنسق يستطيع زيارة معلمي المادة في مدرسته فقط
        if (!$subject_id) {
            // يجب تحديد المادة لمنسق المادة
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'يجب تحديد المادة لمنسق المادة',
                'teachers' => []
            ]);
            exit;
        }
        
        // جلب مادة المنسق ومدرسته لمقارنتها
        if ($visitor_person_id) {
            $visitor_info = query_row("
                SELECT ts.subject_id, t.school_id 
                FROM teachers t
                JOIN teacher_subjects ts ON t.id = ts.teacher_id
                WHERE t.id = ?
            ", [$visitor_person_id]);
            
            if (!$visitor_info || $visitor_info['subject_id'] != $subject_id || $visitor_info['school_id'] != $school_id) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'لا يمكن للمنسق زيارة معلمين في مادة مختلفة أو مدرسة مختلفة',
                    'teachers' => []
                ]);
                exit;
            }
        }
        
        $sql = "
            SELECT DISTINCT t.id, t.name 
            FROM teachers t
            JOIN teacher_subjects ts ON t.id = ts.teacher_id
            WHERE t.school_id = ? 
            AND ts.subject_id = ?
            AND t.job_title = 'معلم'
            ORDER BY t.name
        ";
        $params = [$school_id, $subject_id];
    }
    // إذا كان المستخدم أكاديمياً
    else if ($visitor_role === 'النائب الأكاديمي') {
        // الأكاديمي يستطيع زيارة جميع المعلمين والمنسقين في مدرسته فقط
        $sql = "
            SELECT DISTINCT t.id, t.name 
            FROM teachers t
            WHERE t.school_id = ? 
            AND (t.job_title = 'معلم' OR t.job_title = 'منسق المادة')
            ORDER BY t.name
        ";
        $params = [$school_id];
        
        // إذا تم تحديد مادة، نضيف التصفية بالمادة
        if ($subject_id) {
            $sql = "
                SELECT DISTINCT t.id, t.name 
                FROM teachers t
                JOIN teacher_subjects ts ON t.id = ts.teacher_id
                WHERE t.school_id = ? 
                AND ts.subject_id = ?
                AND (t.job_title = 'معلم' OR t.job_title = 'منسق المادة')
                ORDER BY t.name
            ";
            $params = [$school_id, $subject_id];
        }
    }
    // إذا كان المستخدم مديراً
    else if ($visitor_role === 'مدير') {
        // المدير يستطيع زيارة كل شخص في مدرسته
        $sql = "
            SELECT id, name 
            FROM teachers 
            WHERE school_id = ?
            ORDER BY name
        ";
        $params = [$school_id];
        
        // إذا تم تحديد مادة، نضيف التصفية بالمادة
        if ($subject_id) {
            $sql = "
                SELECT DISTINCT t.id, t.name 
                FROM teachers t
                JOIN teacher_subjects ts ON t.id = ts.teacher_id
                WHERE t.school_id = ?
                AND ts.subject_id = ?
                ORDER BY t.name
            ";
            $params = [$school_id, $subject_id];
        }
    }
    // لأنواع الزائرين الأخرى أو في حالة عدم وجود نوع زائر محدد
    else {
        // استخدام الاستعلام الافتراضي
        if ($subject_id) {
            $sql = "
                SELECT t.id, t.name 
                FROM teachers t
                JOIN teacher_subjects ts ON t.id = ts.teacher_id
                WHERE t.school_id = ? 
                AND ts.subject_id = ?
            ";
            $params = [$school_id, $subject_id];
        } else {
            $sql = "
                SELECT id, name 
                FROM teachers 
                WHERE school_id = ?
            ";
            $params = [$school_id];
        }
        
        // إضافة شرط لاستبعاد المعلمين حسب الوظيفة
        if (!empty($excluded_roles)) {
            $placeholders = implode(',', array_fill(0, count($excluded_roles), '?'));
            $sql .= " AND t.job_title NOT IN ($placeholders)";
            $params = array_merge($params, $excluded_roles);
        }
        
        $sql .= " ORDER BY name";
    }

    // تنفيذ الاستعلام
    $teachers = query($sql, $params);

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
        'message' => 'حدث خطأ أثناء جلب البيانات: ' . $e->getMessage(),
        'teachers' => []
    ]);
} 