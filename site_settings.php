<?php
require_once 'includes/db_connection.php';
require_once 'includes/auth_functions.php';

// التحقق من تسجيل الدخول والصلاحيات
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

if (!has_permission('all')) {
    die('غير مصرح لك بالوصول لهذه الصفحة');
}

$success_message = '';
$error_message = '';

// معالجة حفظ الإعدادات
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $settings = $_POST['settings'] ?? [];
    
    try {
        // حفظ كل إعداد
        foreach ($settings as $key => $value) {
            // التحقق من وجود الإعداد
            $existing = query_row("SELECT id FROM system_settings WHERE setting_key = ?", [$key]);
            
            if ($existing) {
                // تحديث الإعداد الموجود
                execute("UPDATE system_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?", [$value, $key]);
            } else {
                // إنشاء إعداد جديد
                execute("INSERT INTO system_settings (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())", [$key, $value]);
            }
        }
        
        $success_message = 'تم حفظ الإعدادات بنجاح';
        
        // تسجيل العملية
        log_user_activity($_SESSION['user_id'], 'update_settings', 'system_settings', null, null, $settings);
        
    } catch (Exception $e) {
        $error_message = 'حدث خطأ أثناء حفظ الإعدادات: ' . $e->getMessage();
    }
}

// التأكد من وجود جدول الإعدادات
try {
    query("SELECT 1 FROM system_settings LIMIT 1", []);
} catch (Exception $e) {
    // إنشاء الجدول إذا لم يكن موجوداً
    execute("
        CREATE TABLE IF NOT EXISTS system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            description VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ", []);
    
    // إضافة الإعدادات الافتراضية
    $default_settings = [
        ['site_name', 'نظام زيارة الصفوف', 'اسم الموقع'],
        ['site_description', 'نظام إداري لمتابعة وتقييم الزيارات الصفية', 'وصف الموقع'],
        ['academic_year', date('Y') . '-' . (date('Y') + 1), 'العام الأكاديمي الحالي'],
        ['max_file_size', '5', 'الحد الأقصى لحجم الملفات (بالميجابايت)'],
        ['allowed_file_types', 'pdf,doc,docx,jpg,jpeg,png', 'أنواع الملفات المسموحة'],
        ['session_timeout', '120', 'مهلة انتهاء الجلسة (بالدقائق)'],
        ['backup_frequency', 'daily', 'تكرار النسخ الاحتياطي'],
        ['email_notifications', '1', 'تفعيل الإشعارات بالبريد الإلكتروني'],
        ['maintenance_mode', '0', 'وضع الصيانة'],
        ['default_language', 'ar', 'اللغة الافتراضية'],
        ['timezone', 'Asia/Qatar', 'المنطقة الزمنية'],
        ['max_login_attempts', '5', 'عدد محاولات تسجيل الدخول القصوى']
    ];
    
    foreach ($default_settings as $setting) {
        execute("INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES (?, ?, ?)", $setting);
    }
}

// جلب جميع الإعدادات
$settings = query("SELECT * FROM system_settings ORDER BY setting_key", []);
$settings_array = [];
foreach ($settings as $setting) {
    $settings_array[$setting['setting_key']] = $setting['setting_value'];
}

// إحصائيات النظام
$stats = [
    'total_users' => query_row("SELECT COUNT(*) as count FROM users")['count'],
    'total_schools' => query_row("SELECT COUNT(*) as count FROM schools")['count'],
    'total_teachers' => query_row("SELECT COUNT(*) as count FROM teachers")['count'],
    'total_visits' => query_row("SELECT COUNT(*) as count FROM visits")['count'],
    'database_size' => get_database_size(),
    'php_version' => phpversion(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'غير معروف'
];

function get_database_size() {
    try {
        $result = query_row("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'size_mb'
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
        ");
        return $result['size_mb'] ?? 0;
    } catch (Exception $e) {
        return 0;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعدادات الموقع</title>
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
        .setting-group {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: #fff;
        }
        .stat-card {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            text-align: center;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
        }
        .warning-card {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #000;
        }
        .success-card {
            background: linear-gradient(135deg, #28a745, #1e7e34);
        }
        .danger-card {
            background: linear-gradient(135deg, #dc3545, #bd2130);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-gear"></i> إعدادات الموقع
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
            <!-- إحصائيات النظام -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-graph-up"></i> إحصائيات النظام</h5>
                    </div>
                    <div class="card-body p-2">
                        <div class="stat-card">
                            <h3><?php echo $stats['total_users']; ?></h3>
                            <small>المستخدمين</small>
                        </div>
                        <div class="stat-card success-card">
                            <h3><?php echo $stats['total_schools']; ?></h3>
                            <small>المدارس</small>
                        </div>
                        <div class="stat-card warning-card">
                            <h3><?php echo $stats['total_teachers']; ?></h3>
                            <small>المعلمين</small>
                        </div>
                        <div class="stat-card danger-card">
                            <h3><?php echo $stats['total_visits']; ?></h3>
                            <small>الزيارات</small>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="bi bi-server"></i> معلومات الخادم</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>إصدار PHP:</strong> <?php echo $stats['php_version']; ?></p>
                        <p><strong>خادم الويب:</strong> <?php echo $stats['server_software']; ?></p>
                        <p><strong>حجم قاعدة البيانات:</strong> <?php echo $stats['database_size']; ?> ميجابايت</p>
                        <p><strong>الوقت الحالي:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                    </div>
                </div>
            </div>

            <!-- نموذج الإعدادات -->
            <div class="col-md-8">
                <form method="post">
                    <!-- إعدادات عامة -->
                    <div class="setting-group">
                        <h5 class="text-primary mb-3"><i class="bi bi-globe"></i> الإعدادات العامة</h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="site_name" class="form-label">اسم الموقع</label>
                                <input type="text" class="form-control" id="site_name" name="settings[site_name]" 
                                       value="<?php echo htmlspecialchars($settings_array['site_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="academic_year" class="form-label">العام الأكاديمي</label>
                                <input type="text" class="form-control" id="academic_year" name="settings[academic_year]" 
                                       value="<?php echo htmlspecialchars($settings_array['academic_year'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="site_description" class="form-label">وصف الموقع</label>
                            <textarea class="form-control" id="site_description" name="settings[site_description]" rows="2"><?php echo htmlspecialchars($settings_array['site_description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="default_language" class="form-label">اللغة الافتراضية</label>
                                <select class="form-select" id="default_language" name="settings[default_language]">
                                    <option value="ar" <?php echo ($settings_array['default_language'] ?? '') == 'ar' ? 'selected' : ''; ?>>العربية</option>
                                    <option value="en" <?php echo ($settings_array['default_language'] ?? '') == 'en' ? 'selected' : ''; ?>>English</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="timezone" class="form-label">المنطقة الزمنية</label>
                                <select class="form-select" id="timezone" name="settings[timezone]">
                                    <option value="Asia/Qatar" <?php echo ($settings_array['timezone'] ?? '') == 'Asia/Qatar' ? 'selected' : ''; ?>>آسيا/قطر</option>
                                    <option value="Asia/Riyadh" <?php echo ($settings_array['timezone'] ?? '') == 'Asia/Riyadh' ? 'selected' : ''; ?>>آسيا/الرياض</option>
                                    <option value="Asia/Dubai" <?php echo ($settings_array['timezone'] ?? '') == 'Asia/Dubai' ? 'selected' : ''; ?>>آسيا/دبي</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- إعدادات الملفات -->
                    <div class="setting-group">
                        <h5 class="text-success mb-3"><i class="bi bi-files"></i> إعدادات الملفات</h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="max_file_size" class="form-label">الحد الأقصى لحجم الملف (ميجابايت)</label>
                                <input type="number" class="form-control" id="max_file_size" name="settings[max_file_size]" 
                                       value="<?php echo htmlspecialchars($settings_array['max_file_size'] ?? '5'); ?>" min="1" max="100">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="allowed_file_types" class="form-label">أنواع الملفات المسموحة</label>
                                <input type="text" class="form-control" id="allowed_file_types" name="settings[allowed_file_types]" 
                                       value="<?php echo htmlspecialchars($settings_array['allowed_file_types'] ?? ''); ?>"
                                       placeholder="pdf,doc,docx,jpg,png">
                            </div>
                        </div>
                    </div>

                    <!-- إعدادات الأمان -->
                    <div class="setting-group">
                        <h5 class="text-warning mb-3"><i class="bi bi-shield-check"></i> إعدادات الأمان</h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="session_timeout" class="form-label">مهلة انتهاء الجلسة (دقيقة)</label>
                                <input type="number" class="form-control" id="session_timeout" name="settings[session_timeout]" 
                                       value="<?php echo htmlspecialchars($settings_array['session_timeout'] ?? '120'); ?>" min="30" max="480">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="max_login_attempts" class="form-label">عدد محاولات تسجيل الدخول القصوى</label>
                                <input type="number" class="form-control" id="max_login_attempts" name="settings[max_login_attempts]" 
                                       value="<?php echo htmlspecialchars($settings_array['max_login_attempts'] ?? '5'); ?>" min="3" max="10">
                            </div>
                        </div>
                    </div>

                    <!-- إعدادات النسخ الاحتياطي والإشعارات -->
                    <div class="setting-group">
                        <h5 class="text-info mb-3"><i class="bi bi-bell"></i> النسخ الاحتياطي والإشعارات</h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="backup_frequency" class="form-label">تكرار النسخ الاحتياطي</label>
                                <select class="form-select" id="backup_frequency" name="settings[backup_frequency]">
                                    <option value="daily" <?php echo ($settings_array['backup_frequency'] ?? '') == 'daily' ? 'selected' : ''; ?>>يومي</option>
                                    <option value="weekly" <?php echo ($settings_array['backup_frequency'] ?? '') == 'weekly' ? 'selected' : ''; ?>>أسبوعي</option>
                                    <option value="monthly" <?php echo ($settings_array['backup_frequency'] ?? '') == 'monthly' ? 'selected' : ''; ?>>شهري</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" id="email_notifications" name="settings[email_notifications]" value="1"
                                           <?php echo ($settings_array['email_notifications'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="email_notifications">
                                        تفعيل الإشعارات بالبريد الإلكتروني
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- إعدادات الصيانة -->
                    <div class="setting-group">
                        <h5 class="text-danger mb-3"><i class="bi bi-tools"></i> إعدادات الصيانة</h5>
                        
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="maintenance_mode" name="settings[maintenance_mode]" value="1"
                                   <?php echo ($settings_array['maintenance_mode'] ?? '0') == '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="maintenance_mode">
                                تفعيل وضع الصيانة
                            </label>
                            <div class="form-text">عند التفعيل، لن يتمكن المستخدمون من الوصول للموقع إلا المديرين</div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save"></i> حفظ جميع الإعدادات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // تحذير عند تفعيل وضع الصيانة
        document.getElementById('maintenance_mode').addEventListener('change', function() {
            if (this.checked) {
                if (!confirm('هل أنت متأكد من تفعيل وضع الصيانة؟ سيتم منع جميع المستخدمين من الوصول للموقع عدا المديرين.')) {
                    this.checked = false;
                }
            }
        });
    </script>
</body>
</html>
