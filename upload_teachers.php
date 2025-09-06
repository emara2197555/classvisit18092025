<?php
require_once 'includes/db_connection.php';
require_once 'includes/auth_functions.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// التحقق من تسجيل الدخول
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// التحقق من الصلاحيات
if (!has_permission('all')) {
    die('غير مصرح لك بالوصول لهذه الصفحة');
}

$success_message = '';
$error_message = '';
$results = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    $upload_dir = 'uploads/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_tmp = $_FILES['excel_file']['tmp_name'];
    $file_name = $_FILES['excel_file']['name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // التحقق من نوع الملف
    if (!in_array($file_ext, ['xlsx', 'xls', 'csv'])) {
        $error_message = 'نوع الملف غير مدعوم. يرجى رفع ملف Excel (.xlsx, .xls) أو CSV';
    } else {
        try {
            // قراءة الملف
            $spreadsheet = IOFactory::load($file_tmp);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // إزالة العنوان (الصف الأول)
            array_shift($rows);
            
            $success_count = 0;
            $error_count = 0;
            $results = [];
            
            foreach ($rows as $index => $row) {
                $row_num = $index + 2; // +2 لأن الصف الأول هو العنوان والمصفوفة تبدأ من 0
                
                // تجاهل الصفوف الفارغة
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // استخراج البيانات
                $name = trim($row[0] ?? '');
                $personal_id = trim($row[1] ?? '');
                $email = trim($row[2] ?? '');
                $job_title = trim($row[3] ?? '');
                $phone = trim($row[4] ?? '');
                $school_name = trim($row[5] ?? '');
                $subject_names = trim($row[6] ?? '');
                $create_account = strtolower(trim($row[7] ?? '')) === 'نعم';
                
                // التحقق من البيانات الأساسية
                if (empty($name) || empty($personal_id) || empty($job_title) || empty($school_name)) {
                    $results[] = [
                        'row' => $row_num,
                        'name' => $name,
                        'status' => 'error',
                        'message' => 'بيانات مفقودة (الاسم، الرقم الشخصي، المسمى الوظيفي، المدرسة مطلوبة)'
                    ];
                    $error_count++;
                    continue;
                }
                
                try {
                    // البحث عن المدرسة - البحث الذكي
                    $school = query_row("SELECT id FROM schools WHERE name = ?", [$school_name]);
                    
                    // إذا لم تُوجد، ابحث بطريقة أكثر مرونة
                    if (!$school) {
                        // إزالة كلمة "مدرسة" من البداية إذا كانت موجودة
                        $school_name_clean = preg_replace('/^مدرسة\s+/', '', $school_name);
                        $school = query_row("SELECT id FROM schools WHERE name = ? OR name LIKE ?", 
                                          [$school_name_clean, "%{$school_name_clean}%"]);
                    }
                    
                    // إذا لم تُوجد، ابحث بكلمات مفتاحية
                    if (!$school) {
                        $keywords = explode(' ', $school_name_clean);
                        $main_keyword = '';
                        foreach ($keywords as $keyword) {
                            if (strlen($keyword) > 4) { // كلمات أطول من 4 أحرف
                                $main_keyword = $keyword;
                                break;
                            }
                        }
                        if ($main_keyword) {
                            $school = query_row("SELECT id FROM schools WHERE name LIKE ?", ["%{$main_keyword}%"]);
                        }
                    }
                    
                    if (!$school) {
                        $results[] = [
                            'row' => $row_num,
                            'name' => $name,
                            'status' => 'error',
                            'message' => "المدرسة '$school_name' غير موجودة. المدارس المتاحة: " . implode(', ', array_column(query("SELECT name FROM schools", []), 'name'))
                        ];
                        $error_count++;
                        continue;
                    }
                    $school_id = $school['id'];
                    
                    // التحقق من عدم تكرار الرقم الشخصي
                    $existing = query_row("SELECT id FROM teachers WHERE personal_id = ?", [$personal_id]);
                    if ($existing) {
                        $results[] = [
                            'row' => $row_num,
                            'name' => $name,
                            'status' => 'error',
                            'message' => "الرقم الشخصي '$personal_id' موجود مسبقاً"
                        ];
                        $error_count++;
                        continue;
                    }
                    
                    // إضافة المعلم
                    $sql = "INSERT INTO teachers (name, personal_id, email, job_title, phone, school_id) VALUES (?, ?, ?, ?, ?, ?)";
                    execute($sql, [$name, $personal_id, $email, $job_title, $phone, $school_id]);
                    $teacher_id = last_insert_id();
                    
                    // معالجة المواد إذا كانت موجودة
                    if (!empty($subject_names)) {
                        $subjects_array = array_map('trim', explode(',', $subject_names));
                        foreach ($subjects_array as $subject_name) {
                            if (!empty($subject_name)) {
                                $subject = query_row("SELECT id FROM subjects WHERE name = ? AND (school_id = ? OR school_id IS NULL)", [$subject_name, $school_id]);
                                if ($subject) {
                                    execute("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)", [$teacher_id, $subject['id']]);
                                }
                            }
                        }
                    }
                    
                    $message = "تمت إضافة المعلم بنجاح";
                    
                    // إنشاء حساب مستخدم إذا طُلب ذلك
                    if ($create_account) {
                        $username = !empty($email) ? $email : $personal_id;
                        $password = '123456';
                        
                        // تحديد الدور حسب الوظيفة
                        $role_mapping = [
                            'مدير' => 2,
                            'النائب الأكاديمي' => 3,
                            'منسق المادة' => 5,
                            'موجه المادة' => 4,
                            'معلم' => 6
                        ];
                        $role_id = $role_mapping[$job_title] ?? 6;
                        
                        // إنشاء الحساب
                        $user_data = [
                            'username' => $username,
                            'password' => $password,
                            'full_name' => $name,
                            'email' => $email,
                            'role_id' => $role_id,
                            'school_id' => $school_id,
                            'is_active' => 1
                        ];
                        
                        $result = create_user($user_data);
                        
                        if ($result['success']) {
                            // إضافة إلى coordinator_supervisors إذا كان منسق مادة
                            if ($job_title == 'منسق المادة' && !empty($subject_names)) {
                                $subjects_array = array_map('trim', explode(',', $subject_names));
                                foreach ($subjects_array as $subject_name) {
                                    if (!empty($subject_name)) {
                                        $subject = query_row("SELECT id FROM subjects WHERE name = ? AND (school_id = ? OR school_id IS NULL)", [$subject_name, $school_id]);
                                        if ($subject) {
                                            execute("INSERT INTO coordinator_supervisors (user_id, subject_id, created_at) VALUES (?, ?, NOW())", [$result['user_id'], $subject['id']]);
                                        }
                                    }
                                }
                            }
                            
                            $message .= " + تم إنشاء حساب المستخدم (اسم المستخدم: $username)";
                        } else {
                            $message .= " + فشل إنشاء الحساب: " . $result['message'];
                        }
                    }
                    
                    $results[] = [
                        'row' => $row_num,
                        'name' => $name,
                        'status' => 'success',
                        'message' => $message
                    ];
                    $success_count++;
                    
                } catch (Exception $e) {
                    $results[] = [
                        'row' => $row_num,
                        'name' => $name,
                        'status' => 'error',
                        'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
                    ];
                    $error_count++;
                }
            }
            
            $success_message = "تم معالجة الملف بنجاح. تمت إضافة $success_count معلم، فشل في $error_count صف.";
            
        } catch (Exception $e) {
            $error_message = 'خطأ في قراءة الملف: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رفع بيانات المعلمين</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .card { border-radius: 10px; box-shadow: 0 0 20px rgba(0, 0, 0, 0.1); }
        .result-success { background-color: #d1edff; border-left: 4px solid #0d6efd; }
        .result-error { background-color: #f8d7da; border-left: 4px solid #dc3545; }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="display-5 fw-bold text-primary">رفع بيانات المعلمين</h1>
            <div>
                <a href="teachers_management.php" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-arrow-right"></i> العودة لإدارة المعلمين
                </a>
            </div>
        </div>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-upload"></i> رفع ملف Excel</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="excel_file" class="form-label">اختر ملف Excel</label>
                                <input type="file" class="form-control" id="excel_file" name="excel_file" 
                                       accept=".xlsx,.xls,.csv" required>
                                <div class="form-text">الملفات المدعومة: .xlsx, .xls, .csv</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload"></i> رفع ومعالجة الملف
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> تنسيق الملف المطلوب</h5>
                    </div>
                    <div class="card-body">
                        <h6>ترتيب الأعمدة:</h6>
                        <ol class="mb-3">
                            <li><strong>الاسم</strong> (مطلوب)</li>
                            <li><strong>الرقم الشخصي</strong> (مطلوب)</li>
                            <li><strong>البريد الإلكتروني</strong> (اختياري)</li>
                            <li><strong>المسمى الوظيفي</strong> (مطلوب)</li>
                            <li><strong>رقم الهاتف</strong> (اختياري)</li>
                            <li><strong>اسم المدرسة</strong> (مطلوب)</li>
                            <li><strong>المواد الدراسية</strong> (مفصولة بفاصلة)</li>
                            <li><strong>إنشاء حساب</strong> (نعم/لا)</li>
                        </ol>
                        
                        <h6>المسميات الوظيفية المسموحة:</h6>
                        <ul class="mb-3">
                            <li>مدير</li>
                            <li>النائب الأكاديمي</li>
                            <li>منسق المادة</li>
                            <li>موجه المادة</li>
                            <li>معلم</li>
                        </ul>
                        
                        <div class="alert alert-warning">
                            <small>
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>ملاحظات مهمة:</strong><br>
                                • اسم المستخدم سيكون البريد الإلكتروني أو الرقم الشخصي<br>
                                • كلمة المرور الافتراضية: 123456<br>
                                • أسماء المدارس والمواد يجب أن تكون موجودة مسبقاً في النظام
                            </small>
                        </div>
                        
                        <a href="sample_teachers.xlsx" class="btn btn-outline-success" download>
                            <i class="bi bi-download"></i> تحميل ملف نموذجي
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($results)): ?>
        <div class="card mt-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="bi bi-list-check"></i> نتائج المعالجة</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>الصف</th>
                                <th>الاسم</th>
                                <th>الحالة</th>
                                <th>الرسالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $result): ?>
                            <tr class="<?php echo $result['status'] == 'success' ? 'result-success' : 'result-error'; ?>">
                                <td><?php echo $result['row']; ?></td>
                                <td><?php echo htmlspecialchars($result['name']); ?></td>
                                <td>
                                    <?php if ($result['status'] == 'success'): ?>
                                        <i class="bi bi-check-circle text-success"></i> نجح
                                    <?php else: ?>
                                        <i class="bi bi-x-circle text-danger"></i> فشل
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($result['message']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
