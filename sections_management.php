<?php
/**
 * صفحة إدارة الشعب الدراسية
 * 
 * تتيح هذه الصفحة إضافة وتعديل وحذف الشعب الدراسية للمدرسة
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
    $section_id = (int)$_GET['delete_id'];
    
    try {
        // حذف الشعبة
        execute("DELETE FROM sections WHERE id = ? AND school_id = ?", [$section_id, $school_id]);
        $success_message = "تم حذف الشعبة بنجاح.";
    } catch (PDOException $e) {
        $error_message = "فشل حذف الشعبة: " . $e->getMessage();
    }
}

// متغيرات النموذج
$section_id = 0;
$section_name = '';
$grade_id = '';
$grade_name = '';

// معالجة تقديم النموذج (إضافة أو تحديث)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // استلام البيانات من النموذج وتنظيفها
    $section_id = isset($_POST['section_id']) ? (int)$_POST['section_id'] : 0;
    $section_name = sanitize($_POST['section_name']);
    $grade_id = (int)$_POST['grade_id'];
    
    try {
        if ($section_id > 0) {
            // تحديث شعبة موجودة
            $sql = "UPDATE sections SET name = ?, grade_id = ? WHERE id = ? AND school_id = ?";
            execute($sql, [$section_name, $grade_id, $section_id, $school_id]);
            $success_message = "تم تحديث بيانات الشعبة بنجاح.";
        } else {
            // إضافة شعبة جديدة
            $sql = "INSERT INTO sections (name, grade_id, school_id) VALUES (?, ?, ?)";
            execute($sql, [$section_name, $grade_id, $school_id]);
            $success_message = "تمت إضافة الشعبة بنجاح.";
        }
        
        // إعادة تعيين النموذج
        $section_id = 0;
        $section_name = '';
        $grade_id = '';
    } catch (PDOException $e) {
        $error_message = "حدث خطأ أثناء حفظ البيانات: " . $e->getMessage();
    }
}

// تحميل بيانات الشعبة للتعديل
if (isset($_GET['edit_id']) && !empty($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $section = query_row("SELECT s.*, g.name as grade_name FROM sections s
                         JOIN grades g ON s.grade_id = g.id
                         WHERE s.id = ? AND s.school_id = ?", [$edit_id, $school_id]);
    
    if ($section) {
        $section_id = $section['id'];
        $section_name = $section['name'];
        $grade_id = $section['grade_id'];
        $grade_name = $section['grade_name'];
    }
}

// استرجاع قائمة المراحل التعليمية
$educational_levels = query("SELECT * FROM educational_levels ORDER BY id");

// استرجاع قائمة الشعب مع معلومات الصفوف والمراحل
$sections = query("SELECT s.id, s.name, g.name AS grade_name, e.name AS level_name
                  FROM sections s
                  JOIN grades g ON s.grade_id = g.id
                  JOIN educational_levels e ON g.level_id = e.id
                  WHERE s.school_id = ?
                  ORDER BY e.id, g.id, s.name", [$school_id]);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الشعب الدراسية</title>
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
        .level-header {
            background-color: #e7f5ff;
            padding: 10px 15px;
            border-radius: 5px;
            margin-top: 20px;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .section-group {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="display-5 fw-bold text-primary">إدارة الشعب الدراسية</h1>
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
            <!-- نموذج إضافة/تعديل شعبة -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <?php echo $section_id > 0 ? 'تعديل الشعبة' : 'إضافة شعبة جديدة'; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="section_id" value="<?php echo $section_id; ?>">
                            
                            <?php if ($section_id > 0): ?>
                                <div class="mb-3">
                                    <label class="form-label">الصف الدراسي الحالي</label>
                                    <input type="text" class="form-control" value="<?php echo $grade_name; ?>" disabled>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label for="grade_id" class="form-label">الصف الدراسي <span class="text-danger">*</span></label>
                                <select class="form-select" id="grade_id" name="grade_id" required>
                                    <option value="">-- اختر الصف الدراسي --</option>
                                    <?php foreach ($educational_levels as $level): ?>
                                        <optgroup label="<?php echo htmlspecialchars($level['name']); ?>">
                                            <?php 
                                            $grades = query("SELECT * FROM grades WHERE level_id = ? ORDER BY id", [$level['id']]);
                                            foreach ($grades as $grade):
                                            ?>
                                                <option value="<?php echo $grade['id']; ?>" <?php echo ($grade_id == $grade['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($grade['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="section_name" class="form-label">اسم الشعبة <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="section_name" name="section_name" value="<?php echo $section_name; ?>" required>
                                <small class="text-muted">مثال: أ, ب, ج, د أو 1, 2, 3, 4</small>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> 
                                    <?php echo $section_id > 0 ? 'تحديث الشعبة' : 'إضافة الشعبة'; ?>
                                </button>
                                
                                <?php if ($section_id > 0): ?>
                                    <a href="sections_management.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-plus-circle"></i> إضافة شعبة جديدة
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- عرض الشعب الدراسية -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">قائمة الشعب الدراسية</h5>
                        <span class="badge bg-light text-dark rounded-pill"><?php echo count($sections); ?> شعبة</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($sections)): ?>
                            <div class="alert alert-info">
                                لا توجد شعب دراسية مسجلة. يرجى إضافة شعبة جديدة.
                            </div>
                        <?php else: ?>
                            <?php
                            // تنظيم الشعب حسب المرحلة والصف
                            $organized_sections = [];
                            $current_level = '';
                            $current_grade = '';
                            
                            foreach ($sections as $section) {
                                if ($section['level_name'] != $current_level) {
                                    $current_level = $section['level_name'];
                                    $organized_sections[$current_level] = [];
                                }
                                
                                if ($section['grade_name'] != $current_grade) {
                                    $current_grade = $section['grade_name'];
                                    $organized_sections[$current_level][$current_grade] = [];
                                }
                                
                                $organized_sections[$current_level][$current_grade][] = $section;
                            }
                            
                            // عرض الشعب بطريقة منظمة
                            foreach ($organized_sections as $level_name => $grades):
                            ?>
                                <div class="level-header">
                                    <i class="bi bi-diagram-3"></i> <?php echo $level_name; ?>
                                </div>
                                
                                <?php foreach ($grades as $grade_name => $grade_sections): ?>
                                    <div class="section-group">
                                        <h6 class="text-primary mb-2"><?php echo $grade_name; ?></h6>
                                        <div class="row">
                                            <?php foreach ($grade_sections as $section): ?>
                                                <div class="col-md-4 mb-2">
                                                    <div class="card h-100">
                                                        <div class="card-body p-3 d-flex justify-content-between align-items-center">
                                                            <span><i class="bi bi-people"></i> <?php echo htmlspecialchars($section['name']); ?></span>
                                                            <div>
                                                                <a href="sections_management.php?edit_id=<?php echo $section['id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                                                    <i class="bi bi-pencil-square"></i>
                                                                </a>
                                                                <a href="#" onclick="confirmDelete(<?php echo $section['id']; ?>, '<?php echo htmlspecialchars($section['name']); ?>')" class="btn btn-sm btn-outline-danger">
                                                                    <i class="bi bi-trash"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
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
                    هل أنت متأكد من رغبتك في حذف الشعبة: <span id="sectionNameToDelete"></span>؟
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
            document.getElementById('sectionNameToDelete').textContent = name;
            document.getElementById('confirmDeleteBtn').href = 'sections_management.php?delete_id=' + id;
            
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }
    </script>
</body>
</html> 