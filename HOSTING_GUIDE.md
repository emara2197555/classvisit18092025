# دليل رفع قاعدة البيانات للهوست الحقيقي
## classvisit_db للاستضافة

## المشاكل الشائعة والحلول:

### 1. مشكلة DEFINER في الإجراءات المخزنة
**المشكلة:** 
```
#1227 - Access denied; you need (at least one of) the SUPER or SET_USER_ID privilege(s) for this operation
```

**السبب:** معظم شركات الاستضافة لا تسمح بصلاحيات SUPER أو إنشاء إجراءات مخزنة

### 2. مشكلة Static Analysis 
**المشكلة:** 
```
Unrecognized statement type (DECLARE, IF, ELSEIF, etc.)
```

**السبب:** phpMyAdmin يرفض بعض أوامر الإجراءات المخزنة

## الحلول:

### الحل الأول: استخدام ملف قاعدة البيانات بدون إجراءات مخزنة

1. استخدم الملف `classvisit_db_tables_only.sql` بدلاً من `classvisit_db.sql`
2. هذا الملف يحتوي على الجداول والبيانات فقط بدون إجراءات مخزنة

### الحل الثاني: إزالة الإجراءات المخزنة يدوياً

1. افتح ملف `classvisit_db.sql` في محرر نصوص
2. احذف الأسطر من:
   ```sql
   DELIMITER $$
   --
   -- Procedures
   --
   ```
   حتى:
   ```sql
   DELIMITER ;
   ```

### الحل الثالث: رفع الجداول على مراحل

1. أنشئ قاعدة البيانات أولاً
2. ارفع جداول المستخدمين والأدوار أولاً
3. ارفع باقي الجداول تدريجياً
4. أدخل البيانات الأساسية

### الحل الرابع: استخدام phpMyAdmin بطريقة مختلفة

1. في phpMyAdmin اختر "Import"
2. اختر الملف
3. في "SQL compatibility mode" اختر "MYSQL40"
4. قم بتعطيل "Enable foreign key checks"

## ملفات قاعدة البيانات المتوفرة:

1. `classvisit_db.sql` - الملف الكامل مع الإجراءات المخزنة
2. `classvisit_db_hosting.sql` - ملف محسّن للاستضافة بدون إجراءات مخزنة
3. `classvisit_db_structure_only.sql` - هيكل الجداول فقط
4. `classvisit_db_data_only.sql` - البيانات فقط

## خطوات الرفع للهوست:

### الخطوة 1: إنشاء قاعدة البيانات
```sql
CREATE DATABASE classvisit_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### الخطوة 2: رفع الملف المحسّن
استخدم `classvisit_db_hosting.sql`

### الخطوة 3: تحديث ملف الاتصال
تأكد من تحديث بيانات الاتصال في:
- `includes/db_connection.php`

### الخطوة 4: إنشاء المستخدم الأول
```sql
INSERT INTO users (username, email, password_hash, full_name, role_id, school_id, is_active) 
VALUES ('admin', 'admin@school.edu', '$2y$10$L0Uo60GyAXjKryjF4qO9ZusWC5kqqt4.oYnW1LaBnGLfnBN7PXEsy', 'مدير النظام', 1, 1, 1);
```

## نصائح إضافية:

1. **استخدم MySQL 5.7 أو أحدث** للحصول على أفضل دعم
2. **تأكد من وجود صلاحيات إنشاء الجداول** 
3. **استخدم phpMyAdmin الحديث** (5.0+)
4. **قم بضغط الملف** إذا كان كبيراً
5. **ارفع على مراحل** إذا كان الملف كبيراً جداً

## استكشاف الأخطاء:

### إذا ظهر خطأ "Table doesn't exist"
```sql
SHOW TABLES;
```

### إذا ظهر خطأ في التشفير
```sql
SET NAMES utf8mb4;
```

### إذا ظهر خطأ في المفاتيح الخارجية
```sql
SET FOREIGN_KEY_CHECKS=0;
-- رفع البيانات
SET FOREIGN_KEY_CHECKS=1;
```
