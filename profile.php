<?php
require_once 'includes/db_connection.php';
require_once 'includes/auth_functions.php';

// التحقق من تسجيل الدخول
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// معالجة تحديث البيانات
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        
        if (empty($full_name)) {
            $error_message = 'الاسم الكامل مطلوب';
        } else {
            try {
                // تحديث بيانات المستخدم
                $sql = "UPDATE users SET full_name = ?, email = ? WHERE id = ?";
                execute($sql, [$full_name, $email, $user_id]);
                
                // تحديث البيانات في الجلسة
                $_SESSION['full_name'] = $full_name;
                
                $success_message = 'تم تحديث البيانات بنجاح';
                
                // تسجيل العملية
                log_user_activity($user_id, 'update_profile', 'users', $user_id, null, [
                    'full_name' => $full_name,
                    'email' => $email
                ]);
                
            } catch (Exception $e) {
                $error_message = 'حدث خطأ أثناء تحديث البيانات: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = 'جميع حقول كلمة المرور مطلوبة';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'كلمة المرور الجديدة وتأكيدها غير متطابقتين';
        } elseif (strlen($new_password) < 6) {
            $error_message = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
        } else {
            // التحقق من كلمة المرور الحالية
            $user = query_row("SELECT password_hash FROM users WHERE id = ?", [$user_id]);
            
            if (!password_verify($current_password, $user['password_hash'])) {
                $error_message = 'كلمة المرور الحالية غير صحيحة';
            } else {
                try {
                    // تحديث كلمة المرور
                    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql = "UPDATE users SET password_hash = ? WHERE id = ?";
                    execute($sql, [$password_hash, $user_id]);
                    
                    $success_message = 'تم تغيير كلمة المرور بنجاح';
                    
                    // تسجيل العملية
                    log_user_activity($user_id, 'change_password', 'users', $user_id, null, []);
                    
                } catch (Exception $e) {
                    $error_message = 'حدث خطأ أثناء تغيير كلمة المرور: ' . $e->getMessage();
                }
            }
        }
    }
}

// جلب بيانات المستخدم
$user = query_row("
    SELECT u.*, r.name as role_name, s.name as school_name 
    FROM users u 
    LEFT JOIN user_roles r ON u.role_id = r.id 
    LEFT JOIN schools s ON u.school_id = s.id 
    WHERE u.id = ?
", [$user_id]);

// جلب إحصائيات المستخدم
$stats = [];
if (has_permission('create_visit')) {
    $stats['total_visits'] = query_row("SELECT COUNT(*) as count FROM visits WHERE visitor_person_id = ?", [$user_id])['count'];
    $stats['visits_this_month'] = query_row("
        SELECT COUNT(*) as count FROM visits 
        WHERE visitor_person_id = ? AND MONTH(visit_date) = MONTH(CURRENT_DATE()) AND YEAR(visit_date) = YEAR(CURRENT_DATE())
    ", [$user_id])['count'];
}

// جلب آخر نشاطات المستخدم
$activities = query("
    SELECT action, description, created_at 
    FROM user_activity_log 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
", [$user_id]);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #007bff, #0056b3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            margin: 0 auto 20px;
        }
        .stat-card {
            border: none;
            border-radius: 15px;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .activity-item {
            border-left: 3px solid #007bff;
            padding-left: 15px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-house-door"></i> نظام زيارة الصفوف
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="bi bi-arrow-right"></i> العودة للرئيسية
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- معلومات المستخدم -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="profile-avatar">
                            <?php echo mb_substr($user['full_name'], 0, 1); ?>
                        </div>
                        <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                        <p class="text-muted mb-2">
                            <i class="bi bi-person-badge"></i> <?php echo htmlspecialchars($user['role_name']); ?>
                        </p>
                        <p class="text-muted mb-2">
                            <i class="bi bi-at"></i> <?php echo htmlspecialchars($user['username']); ?>
                        </p>
                        <?php if ($user['school_name']): ?>
                        <p class="text-muted mb-2">
                            <i class="bi bi-building"></i> <?php echo htmlspecialchars($user['school_name']); ?>
                        </p>
                        <?php endif; ?>
                        <?php if ($user['email']): ?>
                        <p class="text-muted mb-2">
                            <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
                        </p>
                        <?php endif; ?>
                        <p class="text-muted">
                            <i class="bi bi-calendar"></i> عضو منذ <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                        </p>
                        <div class="mt-3">
                            <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'danger'; ?>">
                                <?php echo $user['is_active'] ? 'نشط' : 'غير نشط'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- إحصائيات المستخدم -->
                <?php if (!empty($stats)): ?>
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bi bi-graph-up"></i> الإحصائيات</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="stat-card card bg-primary text-white p-3">
                                    <h3><?php echo $stats['total_visits']; ?></h3>
                                    <small>إجمالي الزيارات</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-card card bg-success text-white p-3">
                                    <h3><?php echo $stats['visits_this_month']; ?></h3>
                                    <small>زيارات هذا الشهر</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- نماذج التحديث -->
            <div class="col-md-8">
                <!-- تحديث البيانات الشخصية -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-person-gear"></i> تحديث البيانات الشخصية</h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="full_name" class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">البريد الإلكتروني</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>">
                                </div>
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="bi bi-save"></i> حفظ التغييرات
                            </button>
                        </form>
                    </div>
                </div>

                <!-- تغيير كلمة المرور -->
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bi bi-shield-lock"></i> تغيير كلمة المرور</h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">كلمة المرور الحالية <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="new_password" class="form-label">كلمة المرور الجديدة <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <div class="form-text">يجب أن تكون 6 أحرف على الأقل</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">تأكيد كلمة المرور <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-warning">
                                <i class="bi bi-key"></i> تغيير كلمة المرور
                            </button>
                        </form>
                    </div>
                </div>

                <!-- سجل النشاطات -->
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> آخر النشاطات</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($activities)): ?>
                            <p class="text-muted">لا توجد نشاطات مسجلة</p>
                        <?php else: ?>
                            <div class="activity-timeline">
                                <?php foreach ($activities as $activity): ?>
                                    <div class="activity-item">
                                        <div class="d-flex justify-content-between">
                                            <strong>
                                                <?php
                                                $action_names = [
                                                    'login' => 'تسجيل دخول',
                                                    'logout' => 'تسجيل خروج',
                                                    'create_visit' => 'إنشاء زيارة',
                                                    'update_visit' => 'تحديث زيارة',
                                                    'delete_visit' => 'حذف زيارة',
                                                    'update_profile' => 'تحديث الملف الشخصي',
                                                    'change_password' => 'تغيير كلمة المرور'
                                                ];
                                                echo $action_names[$activity['action']] ?? $activity['action'];
                                                ?>
                                            </strong>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?>
                                            </small>
                                        </div>
                                        <?php if ($activity['description']): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($activity['description']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // التحقق من تطابق كلمات المرور
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('كلمات المرور غير متطابقة');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
