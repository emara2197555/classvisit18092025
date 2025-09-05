# دليل حل خطأ "Column 'score' cannot be null"

## المشكلة:
عند محاولة حفظ تقييم جديد، يظهر الخطأ:
```
SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'score' cannot be null
```

## السبب:
جدول `visit_evaluations` في قاعدة البيانات لا يسمح بقيم NULL في حقل `score`، لكن النظام الجديد يرسل NULL للمؤشرات غير المقاسة.

## الحلول المتاحة:

### 1. الحل الفوري (مُطبق بالفعل):
- تم تعديل منطق النموذج ليرسل 0 بدلاً من NULL مؤقتاً
- الملف: `evaluation_form.php` (السطر 82)

### 2. الحل النهائي (يحتاج تطبيق):

#### أ) تشغيل ملف الإصلاح العاجل:
```
http://localhost/classvisit/fix_score_null_error.php
```

#### ب) أو تشغيل ملف التحديثات الشامل:
```
http://localhost/classvisit/apply_updates.php
```

#### ج) أو تنفيذ SQL يدوياً:
```sql
-- السماح بـ NULL في حقل score
ALTER TABLE `visit_evaluations` 
MODIFY COLUMN `score` DECIMAL(5,2) NULL DEFAULT NULL;

-- تحديث البيانات الموجودة (اختياري)
UPDATE `visit_evaluations` 
SET `score` = CASE 
    WHEN `score` = 4 THEN 3
    WHEN `score` = 3 THEN 2
    WHEN `score` = 2 THEN 1
    WHEN `score` = 1 THEN 0
    WHEN `score` = 0 THEN NULL
    ELSE `score`
END;
```

## التحقق من نجاح الحل:

### 1. فحص هيكل الجدول:
```sql
DESCRIBE visit_evaluations;
```
يجب أن يظهر حقل `score` كـ `decimal(5,2) | YES | NULL`

### 2. اختبار النموذج:
- الدخول إلى: `http://localhost/classvisit/evaluation_form.php`
- ترك بعض المؤشرات بدون تقييم (قيمة فارغة)
- حفظ النموذج
- يجب أن يتم الحفظ بنجاح

## الحالة الحالية:
✅ تم تطبيق حل مؤقت - النموذج يعمل الآن
⏳ يحتاج تطبيق الحل النهائي لدعم NULL الكامل

## اختبار النظام:
بعد تطبيق الحل النهائي، تأكد من:
- [ ] حفظ التقييمات بنجاح
- [ ] عرض الدرجات NULL كـ "لم يتم قياسه"
- [ ] حساب المتوسطات بطريقة صحيحة (استثناء NULL)
- [ ] عمل التقارير بدون أخطاء
