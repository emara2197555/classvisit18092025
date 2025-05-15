<?php
/**
 * صفحة إدارة المواد الدراسية
 * 
 * تتيح هذه الصفحة إضافة وتعديل وحذف المواد الدراسية التي تدرس في المدرسة
 */

// تضمين ملف الاتصال بقاعدة البيانات
require_once 'includes/db_connection.php';

// التحقق من وجود مدرسة مسجلة في النظام
$school = query_row("SELECT id FROM schools LIMIT 1");
if (!$school) {
    // إعادة التوجيه إلى صفحة إعدادات المدرسة إذا لم تكن هناك مدرسة مسجلة
    header("Location: school_settings.php");
    exit;
}

$school_id = $school['id'];
$success_message = '';
$error_message = '';

// معالجة طلبات الحذف
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $subject_id = (int)$_GET['delete_id'];
    
    try {
        // التحقق من عدم استخدام المادة في جداول أخرى
        $used_in_visits = query_row("SELECT id FROM visits WHERE subject_id = ? LIMIT 1", [$subject_id]);
        $used_in_teacher_subjects = query_row("SELECT id FROM teacher_subjects WHERE subject_id = ? LIMIT 1", [$subject_id]);
        
        if ($used_in_visits || $used_in_teacher_subjects) {
            $error_message = "لا يمكن حذف هذه المادة لأنها مستخدمة في سجلات أخرى (زيارات أو معلمين).";
        } else {
            // حذف المادة
            execute("DELETE FROM subjects WHERE id = ? AND (school_id = ? OR school_id IS NULL)", [$subject_id, $school_id]);
            $success_message = "تم حذف المادة الدراسية بنجاح.";
        }
    } catch (PDOException $e) {
        $error_message = "فشل حذف المادة الدراسية: " . $e->getMessage();
    }
}

// متغيرات النموذج
$subject_id = 0;
$subject_name = '';
$is_school_specific = false;

// معالجة تقديم النموذج (إضافة أو تحديث)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // استلام البيانات من النموذج وتنظيفها
    $subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
    $subject_name = sanitize($_POST['subject_name']);
    $is_school_specific = isset($_POST['is_school_specific']) ? true : false;
    
    // تحديد school_id بناءً على ما إذا كانت المادة خاصة بالمدرسة أم لا
    $subject_school_id = $is_school_specific ? $school_id : null;
    
    try {
        if ($subject_id > 0) {
            // تحديث مادة موجودة
            $sql = "UPDATE subjects SET name = ?, school_id = ? WHERE id = ?";
            execute($sql, [$subject_name, $subject_school_id, $subject_id]);
            $success_message = "تم تحديث بيانات المادة الدراسية بنجاح.";
        } else {
            // التحقق من عدم وجود مادة بنفس الاسم
            $existing_subject = query_row("SELECT id FROM subjects WHERE name = ? AND (school_id = ? OR school_id IS NULL)", 
                                        [$subject_name, $subject_school_id]);
            
            if ($existing_subject) {
                $error_message = "هذه المادة موجودة بالفعل.";
            } else {
                // إضافة مادة جديدة
                $sql = "INSERT INTO subjects (name, school_id) VALUES (?, ?)";
                execute($sql, [$subject_name, $subject_school_id]);
                $success_message = "تمت إضافة المادة الدراسية بنجاح.";
            }
        }
        
        // إعادة تعيين النموذج
        $subject_id = 0;
        $subject_name = '';
        $is_school_specific = false;
    } catch (PDOException $e) {
        $error_message = "حدث خطأ أثناء حفظ البيانات: " . $e->getMessage();
    }
}

// تحميل بيانات المادة للتعديل
if (isset($_GET['edit_id']) && !empty($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $subject = query_row("SELECT * FROM subjects WHERE id = ? AND (school_id = ? OR school_id IS NULL)", [$edit_id, $school_id]);
    
    if ($subject) {
        $subject_id = $subject['id'];
        $subject_name = $subject['name'];
        $is_school_specific = !is_null($subject['school_id']);
    }
}

// استرجاع قائمة المواد الدراسية (المواد العامة + مواد المدرسة الخاصة)
$subjects = query("SELECT * FROM subjects WHERE school_id = ? OR school_id IS NULL ORDER BY name", [$school_id]);

// تنظيم المواد إلى فئتين: عامة وخاصة بالمدرسة
$general_subjects = [];
$school_subjects = [];

foreach ($subjects as $subject) {
    if (is_null($subject['school_id'])) {
        $general_subjects[] = $subject;
    } else {
        $school_subjects[] = $subject;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المواد الدراسية</title>
    <!-- رابط لمكتبة بوتستراب للتصميم -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <!-- رابط للأيقونات -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .form-label {
            font-weight: 600;
        }
        .subject-table th {
            background-color: #f0f7ff;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="display-5 fw-bold text-primary">إدارة المواد الدراسية</h1>
            <div>
                <a href="index.php" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-house-door"></i> الرئيسية
                </a>
                <a href="school_settings.php" class="btn btn-outline-primary">
                    <i class="bi bi-gear"></i> إعدادات المدرسة
                </a>
            </div>
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
        
        <div class="row">
            <!-- نموذج إضافة/تعديل مادة دراسية -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <?php echo $subject_id > 0 ? 'تعديل مادة دراسية' : 'إضافة مادة دراسية جديدة'; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
                            
                            <div class="mb-3">
                                <label for="subject_name" class="form-label">اسم المادة الدراسية <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="subject_name" name="subject_name" value="<?php echo $subject_name; ?>" required>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_school_specific" name="is_school_specific" <?php echo $is_school_specific ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_school_specific">مادة خاصة بالمدرسة فقط</label>
                                <div class="form-text">
                                    المواد التي ليست خاصة بالمدرسة ستكون متاحة لجميع المدارس في النظام
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> 
                                    <?php echo $subject_id > 0 ? 'تحديث المادة' : 'إضافة المادة'; ?>
                                </button>
                                
                                <?php if ($subject_id > 0): ?>
                                    <a href="subjects_management.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-plus-circle"></i> إضافة مادة جديدة
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- عرض المواد الدراسية -->
            <div class="col-md-8">
                <!-- المواد الخاصة بالمدرسة -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">المواد الدراسية الخاصة بالمدرسة</h5>
                        <span class="badge bg-light text-dark rounded-pill"><?php echo count($school_subjects); ?> مادة</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($school_subjects)): ?>
                            <div class="alert alert-info">
                                لا توجد مواد دراسية خاصة بالمدرسة. يمكنك إضافة مواد خاصة بالمدرسة عن طريق تحديد خيار "مادة خاصة بالمدرسة فقط".
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover subject-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 60px;">#</th>
                                            <th>اسم المادة</th>
                                            <th style="width: 120px;">العمليات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($school_subjects as $index => $subject): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($subject['name']); ?></td>
                                                <td>
                                                    <a href="subjects_management.php?edit_id=<?php echo $subject['id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <a href="#" onclick="confirmDelete(<?php echo $subject['id']; ?>, '<?php echo htmlspecialchars($subject['name']); ?>')" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- المواد العامة -->
                <div class="card">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">المواد الدراسية العامة</h5>
                        <span class="badge bg-light text-dark rounded-pill"><?php echo count($general_subjects); ?> مادة</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($general_subjects)): ?>
                            <div class="alert alert-info">
                                لا توجد مواد دراسية عامة. يمكنك إضافة مواد عامة متاحة لجميع المدارس.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover subject-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 60px;">#</th>
                                            <th>اسم المادة</th>
                                            <th style="width: 120px;">العمليات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($general_subjects as $index => $subject): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($subject['name']); ?></td>
                                                <td>
                                                    <a href="subjects_management.php?edit_id=<?php echo $subject['id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <a href="#" onclick="confirmDelete(<?php echo $subject['id']; ?>, '<?php echo htmlspecialchars($subject['name']); ?>')" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal تأكيد الحذف -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">تأكيد الحذف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    هل أنت متأكد من رغبتك في حذف المادة الدراسية: <span id="subjectNameToDelete"></span>؟
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">نعم، حذف</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // وظيفة تأكيد الحذف
        function confirmDelete(id, name) {
            document.getElementById('subjectNameToDelete').textContent = name;
            document.getElementById('confirmDeleteBtn').href = 'subjects_management.php?delete_id=' + id;
            
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }
    </script>
</body>
</html> 