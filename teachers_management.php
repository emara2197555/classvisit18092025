<?php
/**
 * صفحة إدارة المعلمين
 * 
 * تتيح هذه الصفحة إضافة وتعديل وحذف بيانات المعلمين في المدرسة
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

// معالجة طلبات الحذف
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $teacher_id = (int)$_GET['delete_id'];
    
    try {
        // حذف العلاقات بين المعلم والمواد أولاً
        execute("DELETE FROM teacher_subjects WHERE teacher_id = ?", [$teacher_id]);
        
        // ثم حذف المعلم
        execute("DELETE FROM teachers WHERE id = ? AND school_id = ?", [$teacher_id, $school_id]);
        
        $success_message = "تم حذف المعلم بنجاح.";
    } catch (PDOException $e) {
        $error_message = "فشل حذف المعلم: " . $e->getMessage();
    }
}

// متغيرات النموذج
$teacher_id = 0;
$name = '';
$personal_id = '';
$email = '';
$job_title = '';
$phone = '';
$success_message = '';
$error_message = '';
$selected_subjects = []; // إضافة مصفوفة للمواد المختارة

// الوظائف المتاحة
$job_titles = [
    'معلم',
    'منسق المادة',
    'موجه المادة',
    'النائب الأكاديمي',
    'مدير'
];

// الوظائف التي لا تتطلب اختيار مواد
$roles_without_subjects = ['النائب الأكاديمي', 'مدير'];

// معالجة تقديم النموذج (إضافة أو تحديث)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // استلام البيانات من النموذج وتنظيفها
    $teacher_id = isset($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : 0;
    $name = sanitize($_POST['name']);
    $personal_id = sanitize($_POST['personal_id']);
    $email = sanitize($_POST['email']);
    $job_title = sanitize($_POST['job_title']);
    $phone = sanitize($_POST['phone']);
    $subjects = isset($_POST['subjects']) ? $_POST['subjects'] : [];
    
    // التحقق من مواد المعلم حسب دوره
    $error_in_subjects = false;
    if (!in_array($job_title, $roles_without_subjects)) {
        // الموجه والمنسق والمعلم يجب أن يكون لديهم مادة واحدة فقط
        if (empty($subjects)) {
            $error_message = "يجب اختيار مادة دراسية واحدة على الأقل للوظيفة {$job_title}.";
            $error_in_subjects = true;
        } elseif (count($subjects) > 1) {
            $error_message = "وظيفة {$job_title} تسمح باختيار مادة واحدة فقط.";
            $error_in_subjects = true;
        }
    }
    
    // إذا لم يكن هناك أخطاء نقوم بالإضافة أو التحديث
    if (!$error_in_subjects) {
        try {
            if ($teacher_id > 0) {
                // تحديث معلم موجود
                $sql = "UPDATE teachers SET name = ?, personal_id = ?, email = ?, job_title = ?, phone = ? WHERE id = ? AND school_id = ?";
                execute($sql, [$name, $personal_id, $email, $job_title, $phone, $teacher_id, $school_id]);
                
                // حذف العلاقات القديمة بين المعلم والمواد
                execute("DELETE FROM teacher_subjects WHERE teacher_id = ?", [$teacher_id]);
                
                // إضافة العلاقات الجديدة إذا كانت الوظيفة تتطلب مواد
                if (!in_array($job_title, $roles_without_subjects) && !empty($subjects)) {
                    $sql = "INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)";
                    foreach ($subjects as $subject_id) {
                        execute($sql, [$teacher_id, $subject_id]);
                    }
                }
                
                $success_message = "تم تحديث بيانات المعلم بنجاح.";
            } else {
                // إضافة معلم جديد
                $sql = "INSERT INTO teachers (name, personal_id, email, job_title, phone, school_id) VALUES (?, ?, ?, ?, ?, ?)";
                execute($sql, [$name, $personal_id, $email, $job_title, $phone, $school_id]);
                
                // الحصول على معرف المعلم الجديد
                $new_teacher_id = last_insert_id();
                
                // إضافة العلاقات مع المواد إذا كانت الوظيفة تتطلب مواد
                if (!in_array($job_title, $roles_without_subjects) && !empty($subjects)) {
                    $sql = "INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)";
                    foreach ($subjects as $subject_id) {
                        execute($sql, [$new_teacher_id, $subject_id]);
                    }
                }
                
                $success_message = "تمت إضافة المعلم بنجاح.";
            }
            
            // إعادة تعيين النموذج
            $teacher_id = 0;
            $name = '';
            $personal_id = '';
            $email = '';
            $job_title = '';
            $phone = '';
            $selected_subjects = [];
        } catch (PDOException $e) {
            $error_message = "حدث خطأ أثناء حفظ البيانات: " . $e->getMessage();
        }
    }
}

// تحميل بيانات المعلم للتعديل
if (isset($_GET['edit_id']) && !empty($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $teacher = query_row("SELECT * FROM teachers WHERE id = ? AND school_id = ?", [$edit_id, $school_id]);
    
    if ($teacher) {
        $teacher_id = $teacher['id'];
        $name = $teacher['name'];
        $personal_id = $teacher['personal_id'];
        $email = $teacher['email'];
        $job_title = $teacher['job_title'];
        $phone = $teacher['phone'];
        
        // استرجاع المواد المرتبطة بالمعلم
        $subjects_rows = query("SELECT subject_id FROM teacher_subjects WHERE teacher_id = ?", [$teacher_id]);
        $selected_subjects = array_column($subjects_rows, 'subject_id');
    }
}

// استرجاع قائمة المعلمين
$teachers = query("SELECT * FROM teachers WHERE school_id = ? ORDER BY name", [$school_id]);

// استرجاع قائمة المواد الدراسية المتاحة
$subjects = query("SELECT * FROM subjects WHERE school_id = ? OR school_id IS NULL ORDER BY name", [$school_id]);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المعلمين</title>
    <!-- رابط لمكتبة بوتستراب للتصميم -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <!-- رابط للأيقونات -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- رابط لمكتبة اختيار متعدد -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
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
        .teacher-table th {
            background-color: #f0f7ff;
        }
        .subject-badge {
            display: inline-block;
            background-color: #e7f4ff;
            color: #0a58ca;
            padding: 3px 8px;
            border-radius: 20px;
            margin: 1px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="display-5 fw-bold text-primary">إدارة المعلمين</h1>
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
            <!-- نموذج إضافة/تعديل معلم -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <?php echo $teacher_id > 0 ? 'تعديل بيانات معلم' : 'إضافة معلم جديد'; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="teacher_id" value="<?php echo $teacher_id; ?>">
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">اسم المعلم <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo $name; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="personal_id" class="form-label">الرقم الشخصي <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="personal_id" name="personal_id" value="<?php echo $personal_id; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">البريد الإلكتروني</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="job_title" class="form-label">المسمى الوظيفي <span class="text-danger">*</span></label>
                                <select class="form-select" id="job_title" name="job_title" required onchange="handleJobTitleChange()">
                                    <option value="">اختر المسمى الوظيفي...</option>
                                    <?php foreach ($job_titles as $title): ?>
                                        <option value="<?php echo $title; ?>" <?php echo ($title == $job_title) ? 'selected' : ''; ?>>
                                            <?php echo $title; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">رقم الهاتف</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $phone; ?>">
                            </div>
                            
                            <div class="mb-3" id="subjects-container">
                                <label for="subjects" class="form-label">المواد الدراسية <span class="text-danger">*</span></label>
                                <select class="form-select select2" id="subjects" name="subjects[]">
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo $subject['id']; ?>" <?php echo in_array($subject['id'], $selected_subjects) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($subject['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text" id="subject-help-text">اختر المادة الدراسية</div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> <?php echo $teacher_id > 0 ? 'تحديث البيانات' : 'إضافة معلم'; ?>
                                </button>
                                
                                <?php if ($teacher_id > 0): ?>
                                    <a href="teachers_management.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-plus-circle"></i> إضافة معلم جديد
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- جدول المعلمين -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">قائمة المعلمين</h5>
                        <span class="badge bg-light text-dark rounded-pill"><?php echo count($teachers); ?> معلم</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($teachers)): ?>
                            <div class="alert alert-info">
                                لا يوجد معلمين مسجلين. يرجى إضافة معلم جديد.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover teacher-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>الاسم</th>
                                            <th>الرقم الشخصي</th>
                                            <th>المسمى الوظيفي</th>
                                            <th>المواد</th>
                                            <th>العمليات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($teachers as $index => $teacher): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($teacher['name']); ?>
                                                    <?php if ($teacher['email']): ?>
                                                        <div class="small text-muted"><?php echo htmlspecialchars($teacher['email']); ?></div>
                                                    <?php endif; ?>
                                                    <?php if ($teacher['phone']): ?>
                                                        <div class="small text-muted"><?php echo htmlspecialchars($teacher['phone']); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($teacher['personal_id']); ?></td>
                                                <td><?php echo htmlspecialchars($teacher['job_title']); ?></td>
                                                <td>
                                                    <?php
                                                    // استرجاع المواد الخاصة بالمعلم
                                                    $teacher_subjects = query("
                                                        SELECT s.name 
                                                        FROM subjects s 
                                                        JOIN teacher_subjects ts ON s.id = ts.subject_id 
                                                        WHERE ts.teacher_id = ?
                                                        ORDER BY s.name
                                                    ", [$teacher['id']]);
                                                    
                                                    foreach ($teacher_subjects as $subject) {
                                                        echo '<span class="subject-badge">' . htmlspecialchars($subject['name']) . '</span> ';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <a href="teachers_management.php?edit_id=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <a href="#" onclick="confirmDelete(<?php echo $teacher['id']; ?>, '<?php echo htmlspecialchars($teacher['name']); ?>')" class="btn btn-sm btn-outline-danger">
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
                    هل أنت متأكد من رغبتك في حذف المعلم: <span id="teacherNameToDelete"></span>؟
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">نعم، حذف</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // وظيفة تأكيد الحذف
        function confirmDelete(id, name) {
            document.getElementById('teacherNameToDelete').textContent = name;
            document.getElementById('confirmDeleteBtn').href = 'teachers_management.php?delete_id=' + id;
            
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }
        
        // تهيئة مكتبة select2 للاختيار المتعدد
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                dir: 'rtl',
                placeholder: 'اختر المادة...',
                allowClear: true
            });
            
            // تطبيق التغييرات حسب الوظيفة المحددة عند تحميل الصفحة
            handleJobTitleChange();
        });
        
        // التحكم في عرض وسلوك حقل اختيار المواد بناءً على المسمى الوظيفي
        function handleJobTitleChange() {
            const jobTitle = document.getElementById('job_title').value;
            const subjectsContainer = document.getElementById('subjects-container');
            const subjectsSelect = document.getElementById('subjects');
            const helpText = document.getElementById('subject-help-text');
            
            // إعادة تعيين خصائص الاختيار
            $(subjectsSelect).select2('destroy');
            
            // تحديد ما إذا كانت الوظيفة تتطلب مواد
            if (jobTitle === 'مدير' || jobTitle === 'النائب الأكاديمي') {
                // إخفاء حقل المواد للمدير والنائب الأكاديمي
                subjectsContainer.style.display = 'none';
                subjectsSelect.removeAttribute('required');
                
                // إفراغ الاختيار
                subjectsSelect.selectedIndex = -1;
            } else {
                // إظهار حقل المواد للمعلم والموجه والمنسق
                subjectsContainer.style.display = 'block';
                subjectsSelect.setAttribute('required', 'required');
                
                // تعديل النص المساعد
                if (jobTitle === 'معلم') {
                    helpText.textContent = 'اختر المادة التي يدرسها المعلم';
                } else if (jobTitle === 'موجه المادة') {
                    helpText.textContent = 'اختر المادة التي يوجهها';
                } else if (jobTitle === 'منسق المادة') {
                    helpText.textContent = 'اختر المادة التي ينسقها';
                } else {
                    helpText.textContent = 'اختر المادة الدراسية';
                }
                
                // تعيين خاصية الاختيار المفرد للمعلم والموجه والمنسق
                $(subjectsSelect).select2({
                    theme: 'bootstrap-5',
                    dir: 'rtl',
                    placeholder: 'اختر المادة...',
                    allowClear: true,
                    maximumSelectionLength: 1
                });
            }
        }
    </script>
</body>
</html> 