# تهيئة نظام الزيارات الصفية للهوست الحقيقي

## الملفات المطلوبة:

### 1. ملف إعدادات قاعدة البيانات
**المسار:** `includes/db_connection.php`

**المحتوى الحالي (للتطوير المحلي):**
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'classvisit_db');
define('DB_USER', 'root');
define('DB_PASS', '');
```

**يجب تغييره إلى (للهوست الحقيقي):**
```php
define('DB_HOST', 'your_server_host');     // مثل: mysql.yourhost.com
define('DB_NAME', 'your_database_name');   // مثل: username_classvisit
define('DB_USER', 'your_db_username');     // مثل: username_dbuser
define('DB_PASS', 'your_db_password');     // كلمة مرور قوية
```

### 2. ملف قاعدة البيانات للرفع
**استخدم:** `classvisit_db_hosting.sql` بدلاً من `classvisit_db.sql`

هذا الملف تم تنظيفه من الإجراءات المخزنة التي تسبب مشاكل في الاستضافة.

## خطوات الرفع:

### الخطوة 1: إنشاء قاعدة البيانات
1. ادخل إلى لوحة تحكم الهوست (cPanel أو مشابه)
2. اذهب إلى MySQL Databases
3. أنشئ قاعدة بيانات جديدة (مثل: `username_classvisit`)
4. أنشئ مستخدم جديد وأعطه صلاحيات كاملة

### الخطوة 2: رفع الملفات
1. ارفع جميع ملفات المشروع عبر FTP أو File Manager
2. تأكد من رفع المجلد `includes/` و `assets/` و `api/`

### الخطوة 3: تحديث إعدادات قاعدة البيانات
1. عدّل الملف `includes/db_connection.php`
2. ضع بيانات قاعدة البيانات الصحيحة من لوحة التحكم

### الخطوة 4: استيراد قاعدة البيانات
1. في phpMyAdmin أو أداة إدارة قاعدة البيانات
2. اختر قاعدة البيانات التي أنشأتها
3. اختر "Import"
4. ارفع ملف `classvisit_db_hosting.sql`
5. اختر "Execute"

### الخطوة 5: إنشاء المستخدم الأول
بعد استيراد قاعدة البيانات، سجل دخول بـ:
- **اسم المستخدم:** admin
- **كلمة المرور:** admin123

**مهم:** غيّر كلمة المرور فوراً بعد أول تسجيل دخول!

## استكشاف الأخطاء:

### خطأ الاتصال بقاعدة البيانات
```
فشل الاتصال بقاعدة البيانات
```
**الحل:** تحقق من بيانات الاتصال في `includes/db_connection.php`

### خطأ العثور على الجداول
```
Table 'xxx' doesn't exist
```
**الحل:** تأكد من استيراد `classvisit_db_hosting.sql` بنجاح

### خطأ الترميز
```
Incorrect string value
```
**الحل:** تأكد من ضبط قاعدة البيانات على `utf8mb4_unicode_ci`

### خطأ الصلاحيات
```
Access denied for user
```
**الحل:** تحقق من صلاحيات المستخدم في لوحة التحكم

## ملفات مساعدة:

- `hosting_config_example.php` - مثال على الإعدادات
- `HOSTING_GUIDE.md` - دليل مفصل للرفع
- `classvisit_db_hosting.sql` - قاعدة البيانات المحسّنة للاستضافة

## الدعم:

إذا واجهت مشاكل، تحقق من:
1. صحة بيانات قاعدة البيانات
2. صلاحيات الملفات (755 للمجلدات، 644 للملفات)
3. تفعيل PHP version 7.4+ 
4. تفعيل إضافات PHP: PDO, PDO_MySQL, mbstring
