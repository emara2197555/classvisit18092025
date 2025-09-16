<?php
/**
 * نظام إدارة المستخدمين والصلاحيات
 * 
 * يحتوي على جميع الوظائف المطلوبة لإدارة المستخدمين والأدوار والصلاحيات
 */

// تضمين ملف الاتصال بقاعدة البيانات
require_once 'db_connection.php';

/**
 * بدء جلسة آمنة للمستخدم
 */
function start_secure_session() {
    // التحقق من حالة الجلسة قبل تعديل الإعدادات
    if (session_status() === PHP_SESSION_NONE) {
        // إعدادات الجلسة الآمنة (فقط إذا لم تبدأ الجلسة بعد)
        @ini_set('session.cookie_httponly', 1);
        @ini_set('session.cookie_secure', 0); // 0 للـ localhost
        @ini_set('session.use_strict_mode', 1);
        session_start();
    }
}

/**
 * تسجيل دخول المستخدم
 *
 * @param string $username اسم المستخدم
 * @param string $password كلمة المرور
 * @return array نتيجة تسجيل الدخول
 */
function authenticate_user($username, $password) {
    $sql = "SELECT u.*, r.name as role_name, r.display_name as role_display_name, 
                   r.permissions, s.name as school_name,
                   t.name as teacher_name
            FROM users u
            LEFT JOIN user_roles r ON u.role_id = r.id
            LEFT JOIN schools s ON u.school_id = s.id
            LEFT JOIN teachers t ON t.user_id = u.id
            WHERE u.username = ? AND u.is_active = 1";
    
    $user = query_row($sql, [$username]);
    
    if (!$user) {
        return ['success' => false, 'message' => 'اسم المستخدم غير صحيح'];
    }
    
    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'كلمة المرور غير صحيحة'];
    }
    
    // إنشاء جلسة المستخدم
    start_secure_session();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role_name'] = $user['role_name'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['school_id'] = $user['school_id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['permissions'] = json_decode($user['permissions'], true);
    
    // للمعلمين: الحصول على معرف المعلم من جدول المعلمين
    if ($user['role_name'] === 'Teacher') {
        $teacher_data = query_row("SELECT id FROM teachers WHERE user_id = ?", [$user['id']]);
        $_SESSION['teacher_id'] = $teacher_data ? $teacher_data['id'] : null;
    } else {
        $_SESSION['teacher_id'] = null;
    }
    
    // لمنسقي المواد: الحصول على معرف المادة والتأكد من معرف المدرسة
    if ($user['role_name'] === 'Subject Coordinator') {
        $coordinator_data = query_row("SELECT subject_id FROM coordinator_supervisors WHERE user_id = ?", [$user['id']]);
        $_SESSION['subject_id'] = $coordinator_data ? $coordinator_data['subject_id'] : null;
        
        // التأكد من وجود معرف المدرسة للمنسق
        if (!$user['school_id']) {
            // محاولة الحصول على معرف المدرسة من بيانات المنسق أو تعيين قيمة افتراضية
            $school_data = query_row("
                SELECT DISTINCT t.school_id 
                FROM coordinator_supervisors cs 
                INNER JOIN teacher_subjects ts ON cs.subject_id = ts.subject_id 
                INNER JOIN teachers t ON ts.teacher_id = t.id 
                WHERE cs.user_id = ? 
                LIMIT 1
            ", [$user['id']]);
            
            if ($school_data && $school_data['school_id']) {
                $_SESSION['school_id'] = $school_data['school_id'];
            }
        }
    } else {
        $_SESSION['subject_id'] = null;
    }
    
    // تحديث آخر تسجيل دخول
    $update_sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
    query($update_sql, [$user['id']]);
    
    // تسجيل العملية في سجل النشاط
    log_user_activity($user['id'], 'login', 'users', $user['id']);
    
    return [
        'success' => true, 
        'message' => 'تم تسجيل الدخول بنجاح',
        'user' => $user,
        'redirect' => get_dashboard_url($user['role_name'])
    ];
}

/**
 * تحديد رابط لوحة التحكم حسب الدور
 *
 * @param string $role_name اسم الدور
 * @return string رابط لوحة التحكم
 */
function get_dashboard_url($role_name) {
    switch ($role_name) {
        case 'admin':
        case 'director':
        case 'academic_deputy':
            return 'index.php';
        case 'coordinator':
            return 'coordinator_dashboard.php';
        case 'teacher':
            return 'teacher_dashboard.php';
        case 'E-Learning Coordinator':
            return 'elearning_coordinator_dashboard.php';
        default:
            return 'index.php';
    }
}

/**
 * التحقق من تسجيل الدخول
 *
 * @return bool true إذا كان المستخدم مسجل الدخول
 */
function is_logged_in() {
    start_secure_session();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * التحقق من صلاحية المستخدم
 *
 * @param string $permission الصلاحية المطلوبة
 * @return bool true إذا كان المستخدم لديه الصلاحية
 */
function has_permission($permission) {
    start_secure_session();
    
    if (!is_logged_in()) {
        return false;
    }
    
    $permissions = $_SESSION['permissions'] ?? [];
    
    // المديرين لديهم صلاحية كاملة
    if (isset($permissions['all']) && $permissions['all'] === true) {
        return true;
    }
    
    return isset($permissions[$permission]) && $permissions[$permission] === true;
}

/**
 * التحقق من الدور
 *
 * @param array $allowed_roles الأدوار المسموحة
 * @return bool true إذا كان دور المستخدم ضمن المسموح
 */
function has_role($allowed_roles) {
    start_secure_session();
    
    if (!is_logged_in()) {
        return false;
    }
    
    $user_role = $_SESSION['role_name'] ?? '';
    return in_array($user_role, (array)$allowed_roles);
}

/**
 * التحقق من صلاحية الوصول للمعلم
 *
 * @param int $teacher_id معرف المعلم
 * @return bool true إذا كان المستخدم يستطيع الوصول لبيانات هذا المعلم
 */
function can_access_teacher($teacher_id) {
    start_secure_session();
    
    if (!is_logged_in()) {
        return false;
    }
    
    // المديرين يستطيعون الوصول لكل شيء
    if (has_permission('all')) {
        return true;
    }
    
    $role = $_SESSION['role_name'];
    $user_subject_id = $_SESSION['subject_id'];
    $user_teacher_id = $_SESSION['teacher_id'];
    
    // المعلم يستطيع الوصول لبياناته فقط
    if ($role === 'teacher') {
        return $teacher_id == $user_teacher_id;
    }
    
    // المنسق يستطيع الوصول لمعلمي مادته
    if ($role === 'coordinator' && $user_subject_id) {
        $sql = "SELECT COUNT(*) as count FROM teacher_subjects 
                WHERE teacher_id = ? AND subject_id = ?";
        $result = query_row($sql, [$teacher_id, $user_subject_id]);
        return $result['count'] > 0;
    }
    
    return false;
}

/**
 * التحقق من صلاحية الوصول للمادة
 *
 * @param int $subject_id معرف المادة
 * @return bool true إذا كان المستخدم يستطيع الوصول لبيانات هذه المادة
 */
function can_access_subject($subject_id) {
    start_secure_session();
    
    if (!is_logged_in()) {
        return false;
    }
    
    // المديرين يستطيعون الوصول لكل شيء
    if (has_permission('all')) {
        return true;
    }
    
    $role = $_SESSION['role_name'];
    $user_subject_id = $_SESSION['subject_id'];
    $user_teacher_id = $_SESSION['teacher_id'];
    
    // المنسق يستطيع الوصول لمادته فقط
    if ($role === 'coordinator') {
        return $subject_id == $user_subject_id;
    }
    
    // المعلم يستطيع الوصول للمواد التي يدرسها
    if ($role === 'teacher' && $user_teacher_id) {
        $sql = "SELECT COUNT(*) as count FROM teacher_subjects 
                WHERE teacher_id = ? AND subject_id = ?";
        $result = query_row($sql, [$user_teacher_id, $subject_id]);
        return $result['count'] > 0;
    }
    
    return false;
}

/**
 * الحصول على المعلمين المسموح للمستخدم الوصول إليهم
 *
 * @return array قائمة المعلمين
 */
function get_accessible_teachers() {
    start_secure_session();
    
    if (!is_logged_in()) {
        return [];
    }
    
    // المديرين يحصلون على جميع المعلمين
    if (has_permission('all')) {
        return query("SELECT * FROM teachers ORDER BY name");
    }
    
    $role = $_SESSION['role_name'];
    $user_subject_id = $_SESSION['subject_id'];
    $user_teacher_id = $_SESSION['teacher_id'];
    
    // المنسق يحصل على معلمي مادته
    if ($role === 'coordinator' && $user_subject_id) {
        $sql = "SELECT t.* FROM teachers t
                INNER JOIN teacher_subjects ts ON t.id = ts.teacher_id
                WHERE ts.subject_id = ?
                ORDER BY t.name";
        return query($sql, [$user_subject_id]);
    }
    
    // المعلم يحصل على بياناته فقط
    if ($role === 'teacher' && $user_teacher_id) {
        $sql = "SELECT * FROM teachers WHERE id = ?";
        return query($sql, [$user_teacher_id]);
    }
    
    return [];
}

/**
 * الحصول على الموجهين المسموح للمنسق تسجيل زيارات باسمهم
 *
 * @param int $coordinator_id معرف المنسق (اختياري، افتراضي المستخدم الحالي)
 * @return array قائمة الموجهين
 */
function get_coordinator_supervisors($coordinator_id = null) {
    start_secure_session();
    
    if (!$coordinator_id) {
        $coordinator_id = $_SESSION['user_id'] ?? 0;
    }
    
    if (!is_logged_in() || $_SESSION['role_name'] !== 'Subject Coordinator') {
        return [];
    }
    
    // الحصول على معرف المادة للمنسق إما من الجلسة أو من جدول coordinator_supervisors
    $subject_id = $_SESSION['subject_id'] ?? null;
    
    if (!$subject_id) {
        $coordinator_data = query_row("SELECT subject_id FROM coordinator_supervisors WHERE user_id = ?", [$coordinator_id]);
        
        if (!$coordinator_data) {
            return [];
        }
        
        $subject_id = $coordinator_data['subject_id'];
    }
    
    // الحصول على الموجهين المتاحين لهذه المادة
    $sql = "SELECT vt.* 
            FROM visitor_types vt 
            ORDER BY vt.name";
    
    return query($sql);
}

/**
 * تسجيل عملية في سجل النشاط
 *
 * @param int $user_id معرف المستخدم
 * @param string $action العملية
 * @param string $table_name اسم الجدول
 * @param int $record_id معرف السجل
 * @param array $old_values القيم القديمة
 * @param array $new_values القيم الجديدة
 */
function log_user_activity($user_id, $action, $table_name = null, $record_id = null, $old_values = null, $new_values = null) {
    $sql = "INSERT INTO user_activity_log (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = [
        $user_id,
        $action,
        $table_name,
        $record_id,
        $old_values ? json_encode($old_values) : null,
        $new_values ? json_encode($new_values) : null,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];
    
    try {
        query($sql, $params);
    } catch (Exception $e) {
        // تسجيل الخطأ ولكن عدم إيقاف التطبيق
        error_log("خطأ في تسجيل النشاط: " . $e->getMessage());
    }
}

/**
 * تسجيل خروج المستخدم
 */
function logout_user() {
    start_secure_session();
    
    if (is_logged_in()) {
        // تسجيل عملية تسجيل الخروج
        log_user_activity($_SESSION['user_id'], 'logout');
        
        // حذف جميع بيانات الجلسة
        session_unset();
        session_destroy();
        
        // حذف كوكيز الجلسة
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
    }
}

/**
 * إنشاء مستخدم جديد
 *
 * @param array $user_data بيانات المستخدم
 * @return array نتيجة العملية
 */
function create_user($user_data) {
    // التحقق من الصلاحيات
    if (!has_permission('all')) {
        return ['success' => false, 'message' => 'ليس لديك صلاحية لإنشاء مستخدمين'];
    }
    
    // التحقق من البيانات المطلوبة
    $required_fields = ['username', 'password', 'full_name', 'role_id'];
    foreach ($required_fields as $field) {
        if (empty($user_data[$field])) {
            return ['success' => false, 'message' => "الحقل {$field} مطلوب"];
        }
    }
    
    // التحقق من عدم تكرار اسم المستخدم
    $check_sql = "SELECT COUNT(*) as count FROM users WHERE username = ?";
    $exists = query_row($check_sql, [$user_data['username']]);
    if ($exists['count'] > 0) {
        return ['success' => false, 'message' => 'اسم المستخدم موجود مسبقاً'];
    }
    
    // تشفير كلمة المرور
    $password_hash = password_hash($user_data['password'], PASSWORD_DEFAULT);
    
    // إدراج المستخدم الجديد
    $sql = "INSERT INTO users (username, email, password_hash, full_name, role_id, school_id, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $params = [
        $user_data['username'],
        $user_data['email'] ?? null,
        $password_hash,
        $user_data['full_name'],
        $user_data['role_id'],
        $user_data['school_id'] ?? null,
        $user_data['is_active'] ?? 1
    ];
    
    try {
        $result = query($sql, $params);
        $user_id = get_last_insert_id();
        
        // تسجيل العملية
        log_user_activity($_SESSION['user_id'], 'create_user', 'users', $user_id, null, $user_data);
        
        return ['success' => true, 'message' => 'تم إنشاء المستخدم بنجاح', 'user_id' => $user_id];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'حدث خطأ أثناء إنشاء المستخدم: ' . $e->getMessage()];
    }
}

/**
 * تحديث بيانات مستخدم موجود
 *
 * @param int $user_id معرف المستخدم
 * @param array $user_data بيانات المستخدم الجديدة
 * @return array نتيجة العملية
 */
function update_user($user_id, $user_data) {
    try {
        // جلب البيانات القديمة للمقارنة
        $old_data = query_row("SELECT * FROM users WHERE id = ?", [$user_id]);
        
        if (!$old_data) {
            return ['success' => false, 'message' => 'المستخدم غير موجود'];
        }
        
        // إعداد البيانات للتحديث
        $fields = [];
        $params = [];
        
        if (isset($user_data['full_name'])) {
            $fields[] = "full_name = ?";
            $params[] = $user_data['full_name'];
        }
        
        if (isset($user_data['email'])) {
            $fields[] = "email = ?";
            $params[] = $user_data['email'];
        }
        
        if (isset($user_data['role_id'])) {
            $fields[] = "role_id = ?";
            $params[] = $user_data['role_id'];
        }
        
        if (isset($user_data['school_id'])) {
            $fields[] = "school_id = ?";
            $params[] = $user_data['school_id'];
        }
        
        if (isset($user_data['is_active'])) {
            $fields[] = "is_active = ?";
            $params[] = $user_data['is_active'];
        }
        
        // تحديث كلمة المرور إذا تم توفيرها
        if (isset($user_data['password']) && !empty($user_data['password'])) {
            $fields[] = "password_hash = ?";
            $params[] = password_hash($user_data['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($fields)) {
            return ['success' => false, 'message' => 'لا توجد بيانات للتحديث'];
        }
        
        // إضافة تاريخ التحديث
        $fields[] = "updated_at = NOW()";
        $params[] = $user_id;
        
        // تنفيذ الاستعلام
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        execute($sql, $params);
        
        // تسجيل النشاط
        log_user_activity($_SESSION['user_id'], 'update_user', 'users', $user_id, $old_data, $user_data);
        
        return ['success' => true, 'message' => 'تم تحديث المستخدم بنجاح'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'حدث خطأ أثناء تحديث المستخدم: ' . $e->getMessage()];
    }
}

/**
 * حذف مستخدم
 *
 * @param int $user_id معرف المستخدم
 * @return array نتيجة العملية
 */
function delete_user($user_id) {
    try {
        // التحقق من وجود المستخدم
        $user = query_row("SELECT * FROM users WHERE id = ?", [$user_id]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'المستخدم غير موجود'];
        }
        
        // منع حذف المستخدم الحالي
        if ($user_id == $_SESSION['user_id']) {
            return ['success' => false, 'message' => 'لا يمكن حذف المستخدم الحالي'];
        }
        
        // حذف المستخدم
        execute("DELETE FROM users WHERE id = ?", [$user_id]);
        
        // تسجيل النشاط
        log_user_activity($_SESSION['user_id'], 'delete_user', 'users', $user_id, $user, null);
        
        return ['success' => true, 'message' => 'تم حذف المستخدم بنجاح'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'حدث خطأ أثناء حذف المستخدم: ' . $e->getMessage()];
    }
}

/**
 * حماية الصفحة - التحقق من تسجيل الدخول والصلاحيات
 *
 * @param array $required_roles الأدوار المطلوبة
 * @param array $required_permissions الصلاحيات المطلوبة
 */
function protect_page($required_roles = [], $required_permissions = []) {
    start_secure_session();
    
    // التحقق من تسجيل الدخول
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
    
    // التحقق من الأدوار المطلوبة
    if (!empty($required_roles) && !has_role($required_roles)) {
        header('HTTP/1.0 403 Forbidden');
        die('ليس لديك صلاحية للوصول لهذه الصفحة');
    }
    
    // التحقق من الصلاحيات المطلوبة
    if (!empty($required_permissions)) {
        $has_any_permission = false;
        foreach ($required_permissions as $permission) {
            if (has_permission($permission)) {
                $has_any_permission = true;
                break;
            }
        }
        
        if (!$has_any_permission) {
            header('HTTP/1.0 403 Forbidden');
            die('ليس لديك صلاحية للوصول لهذه الصفحة');
        }
    }
}

/**
 * الحصول على آخر معرف مدرج
 */
function get_last_insert_id() {
    global $pdo;
    return $pdo->lastInsertId();
}
