<?php
/**
 * ملف الاتصال بقاعدة البيانات
 * 
 * يحتوي على الإعدادات والوظائف الأساسية للاتصال بقاعدة البيانات
 */

// إعدادات الاتصال بقاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'classvisit_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// تكوين DSN
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

// خيارات PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// إنشاء اتصال PDO
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    // إعداد الترميز للاتصال
    $pdo->exec("SET NAMES utf8mb4");
    $pdo->exec("SET CHARACTER SET utf8mb4");
    $pdo->exec("SET collation_connection = utf8mb4_unicode_ci");
} catch (PDOException $e) {
    // رسالة خطأ للمطورين (يمكن تعديلها في بيئة الإنتاج)
    die("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
}

/**
 * دالة تنفيذ استعلام SQL وإرجاع النتائج
 *
 * @param string $sql استعلام SQL مع علامات استفهام للمتغيرات
 * @param array $params مصفوفة المتغيرات المستخدمة في الاستعلام
 * @return array مصفوفة النتائج
 */
function query($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * دالة تنفيذ استعلام SQL وإرجاع صف واحد فقط
 *
 * @param string $sql استعلام SQL مع علامات استفهام للمتغيرات
 * @param array $params مصفوفة المتغيرات المستخدمة في الاستعلام
 * @return array|false مصفوفة تمثل الصف الأول أو false في حالة عدم وجود نتائج
 */
function query_row($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

/**
 * دالة تنفيذ استعلام SQL لإدخال أو تحديث أو حذف البيانات وإرجاع معرف السجل المُدرج أو عدد الصفوف المتأثرة
 *
 * @param string $sql استعلام SQL مع علامات استفهام للمتغيرات
 * @param array $params مصفوفة المتغيرات المستخدمة في الاستعلام
 * @return int معرف السجل المُدرج (للـ INSERT) أو عدد الصفوف المتأثرة
 */
function execute($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // إذا كان استعلام INSERT، أرجع آخر معرف مُدرج
    if (stripos(trim($sql), 'INSERT') === 0) {
        return $pdo->lastInsertId();
    }
    
    // وإلا أرجع عدد الصفوف المتأثرة
    return $stmt->rowCount();
}

/**
 * دالة للحصول على آخر معرف تم إدخاله في قاعدة البيانات
 *
 * @return string آخر معرف تم إدخاله
 */
function last_insert_id() {
    global $pdo;
    return $pdo->lastInsertId();
}

/**
 * دالة لتأمين المدخلات قبل إرسالها لقاعدة البيانات
 *
 * @param string $data البيانات المراد تأمينها
 * @return string البيانات بعد التأمين
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
} 