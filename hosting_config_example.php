<?php
/**
 * مثال على إعدادات قاعدة البيانات للهوست الحقيقي
 * 
 * انسخ هذه الإعدادات إلى includes/db_connection.php واستبدل القيم ببيانات الهوست الخاص بك
 */

// إعدادات الاتصال بقاعدة البيانات للهوست الحقيقي
define('DB_HOST', 'your_host_server.com');     // مثل: mysql.hostgator.com أو sql.domain.com
define('DB_NAME', 'your_database_name');       // مثل: username_classvisit
define('DB_USER', 'your_database_username');   // مثل: username_dbuser
define('DB_PASS', 'your_database_password');   // كلمة مرور قاعدة البيانات
define('DB_CHARSET', 'utf8mb4');

// أمثلة شائعة للهوستات:

// Hostgator
// define('DB_HOST', 'gator4xxx.hostgator.com');
// define('DB_NAME', 'username_classvisit');
// define('DB_USER', 'username_dbuser');
// define('DB_PASS', 'strong_password_here');

// Bluehost  
// define('DB_HOST', 'box5xxx.bluehost.com');
// define('DB_NAME', 'username_classvisit');
// define('DB_USER', 'username_dbuser');
// define('DB_PASS', 'strong_password_here');

// GoDaddy
// define('DB_HOST', 'p3plmysqlxx.prod.phx3.secureserver.net');
// define('DB_NAME', 'username_classvisit');
// define('DB_USER', 'username');
// define('DB_PASS', 'strong_password_here');

// SiteGround
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'dbxxxxxx_classvisit');
// define('DB_USER', 'dbxxxxxx_user');
// define('DB_PASS', 'strong_password_here');

// NameCheap
// define('DB_HOST', 'srv-XXX.hstgr.io');
// define('DB_NAME', 'username_classvisit');
// define('DB_USER', 'username_dbuser');
// define('DB_PASS', 'strong_password_here');

/**
 * خطوات التهيئة للهوست الحقيقي:
 * 
 * 1. احصل على بيانات قاعدة البيانات من لوحة تحكم الهوست
 * 2. انسخ هذا الملف إلى includes/db_connection.php
 * 3. استبدل القيم أعلاه ببياناتك الحقيقية
 * 4. احذف هذا الملف بعد النسخ للأمان
 * 5. ارفع ملف classvisit_db_hosting.sql إلى قاعدة البيانات
 */

?>
