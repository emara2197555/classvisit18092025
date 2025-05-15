<?php
/**
 * صفحة إدارة بيانات المدرسة
 * 
 * تتيح هذه الصفحة إضافة وتعديل بيانات المدرسة الأساسية
 */

// تضمين ملف الاتصال بقاعدة البيانات
require_once 'includes/db_connection.php';

// التحقق مما إذا كان النموذج قد تم إرساله
$success_message = '';
$error_message = '';

// استرجاع بيانات المدرسة الحالية إن وجدت
$school = [];
$sql = "SELECT * FROM schools LIMIT 1";
$result = query_row($sql);

if ($result) {
    $school = $result;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // استلام البيانات من النموذج وتنظيفها
    $name = sanitize($_POST['name']);
    $school_code = sanitize($_POST['school_code']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    
    // التحقق من تحميل الشعار
    $logo_path = '';
    if (isset($school['logo']) && !empty($school['logo'])) {
        $logo_path = $school['logo'];
    }
    
    if (isset($_FILES['logo']) && $_FILES['logo']['size'] > 0) {
        $target_dir = "uploads/logos/";
        
        // إنشاء المجلد إذا لم يكن موجوداً
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["logo"]["name"], PATHINFO_EXTENSION));
        $new_file_name = "school_logo_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_file_name;
        
        // التحقق من نوع الملف (يجب أن يكون صورة)
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_types)) {
            if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
                $logo_path = $target_file;
            } else {
                $error_message = "حدث خطأ أثناء رفع الشعار. يرجى المحاولة مرة أخرى.";
            }
        } else {
            $error_message = "نوع الملف غير مسموح به. يرجى اختيار صورة من نوع JPG أو PNG أو GIF.";
        }
    }
    
    try {
        // إذا كانت هناك بيانات مدرسة موجودة، نقوم بتحديثها، وإلا نقوم بإنشاء سجل جديد
        if (!empty($school)) {
            $sql = "UPDATE schools SET name = ?, school_code = ?, email = ?, phone = ?, address = ?, logo = ? WHERE id = ?";
            execute($sql, [$name, $school_code, $email, $phone, $address, $logo_path, $school['id']]);
        } else {
            $sql = "INSERT INTO schools (name, school_code, email, phone, address, logo) VALUES (?, ?, ?, ?, ?, ?)";
            execute($sql, [$name, $school_code, $email, $phone, $address, $logo_path]);
        }
        
        $success_message = "تم حفظ بيانات المدرسة بنجاح.";
        
        // إعادة تحميل بيانات المدرسة
        $sql = "SELECT * FROM schools LIMIT 1";
        $result = query_row($sql);
        if ($result) {
            $school = $result;
        }
    } catch (PDOException $e) {
        $error_message = "حدث خطأ أثناء حفظ البيانات: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة بيانات المدرسة</title>
    <!-- رابط لمكتبة بوتستراب للتصميم -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <!-- رابط للأيقونات -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .form-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-top: 20px;
        }
        .school-logo-preview {
            max-width: 150px;
            max-height: 150px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 5px;
        }
        .form-label {
            font-weight: 600;
        }
        .form-control {
            border-radius: 5px;
            padding: 10px 15px;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="display-5 fw-bold text-primary">إدارة بيانات المدرسة</h1>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-house-door"></i> الرئيسية
            </a>
        </div>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="post" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="name" class="form-label">اسم المدرسة <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($school['name']) ? $school['name'] : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="school_code" class="form-label">كود المدرسة <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="school_code" name="school_code" value="<?php echo isset($school['school_code']) ? $school['school_code'] : ''; ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">البريد الإلكتروني</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($school['email']) ? $school['email'] : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">رقم الهاتف</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo isset($school['phone']) ? $school['phone'] : ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">العنوان</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?php echo isset($school['address']) ? $school['address'] : ''; ?></textarea>
                        </div>
                    </div>
                    
                    <div class="col-md-4 text-center">
                        <div class="mb-3">
                            <label for="logo" class="form-label d-block">شعار المدرسة</label>
                            <?php if (isset($school['logo']) && !empty($school['logo'])): ?>
                                <img src="<?php echo $school['logo']; ?>" alt="شعار المدرسة" class="school-logo-preview">
                            <?php else: ?>
                                <img src="assets/img/school-placeholder.png" alt="صورة افتراضية" class="school-logo-preview">
                            <?php endif; ?>
                            <input type="file" class="form-control mt-2" id="logo" name="logo" accept="image/*">
                            <small class="text-muted">اختر صورة بحجم مناسب (الحد الأقصى 2 ميجابايت)</small>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-save"></i> حفظ البيانات
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // عرض معاينة للصورة المختارة قبل الرفع
        document.getElementById('logo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const img = document.querySelector('.school-logo-preview');
                    img.src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html> 