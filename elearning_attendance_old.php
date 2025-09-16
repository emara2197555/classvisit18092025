<?php
/**
 * صفحة تسجيل حضور التعليم الإلكتروني
 */

require_once 'includes/auth_functions.php';
require_once 'includes/functions.php';

// حماية الصفحة لمنسقي التعليم الإلكتروني
protect_page(['E-Learning Coordinator', 'Admin', 'Director', 'Academic Deputy']);

$page_title = 'تسجيل حضور التعليم الإلكتروني';
$success_message = '';
$error_message = '';

// الحصول على السنة الدراسية الحالية
$current_year = get_active_academic_year();
$current_year_id = $current_year ? $current_year['id'] : 0;

// معالجة النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $academic_year_id = $_POST['academic_year_id'] ?? $current_year_id;
    $term = $_POST['term'] ?? '';
    $date = $_POST['date'] ?? '';
    $subject_id = $_POST['subject_id'] ?? '';
    $teacher_id = $_POST['teacher_id'] ?? '';
    $class_name = trim($_POST['class_name'] ?? '');
    $lesson_topic = trim($_POST['lesson_topic'] ?? '');
    $lesson_time = $_POST['lesson_time'] ?? '';
    $lesson_duration = (int)($_POST['lesson_duration'] ?? 45);
    $attendance_type = $_POST['attendance_type'] ?? '';
    $platform_used = trim($_POST['platform_used'] ?? '');
    $total_students = (int)($_POST['total_students'] ?? 0);
    $present_students = (int)($_POST['present_students'] ?? 0);
    $interaction_level = $_POST['interaction_level'] ?? '';
    $technical_issues = trim($_POST['technical_issues'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    // التحقق من صحة البيانات
    $errors = [];
    
    if (empty($academic_year_id)) $errors[] = 'يرجى اختيار السنة الدراسية';
    if (empty($term)) $errors[] = 'يرجى اختيار الفصل الدراسي';
    if (empty($date)) $errors[] = 'يرجى تحديد التاريخ';
    if (empty($subject_id)) $errors[] = 'يرجى اختيار المادة';
    if (empty($teacher_id)) $errors[] = 'يرجى اختيار المعلم';
    if (empty($class_name)) $errors[] = 'يرجى إدخال اسم الصف';
    if (empty($lesson_topic)) $errors[] = 'يرجى إدخال موضوع الدرس';
    if (empty($lesson_time)) $errors[] = 'يرجى تحديد وقت الحصة';
    if (empty($attendance_type)) $errors[] = 'يرجى اختيار نوع الحضور';
    if ($total_students <= 0) $errors[] = 'يرجى إدخال عدد صحيح للطلاب الإجمالي';
    if ($present_students < 0) $errors[] = 'عدد الطلاب الحاضرين لا يمكن أن يكون سالباً';
    if ($present_students > $total_students) $errors[] = 'عدد الطلاب الحاضرين لا يمكن أن يزيد عن العدد الإجمالي';
    if (empty($interaction_level)) $errors[] = 'يرجى تحديد مستوى التفاعل';
    
    if (empty($errors)) {
        try {
            $absent_students = $total_students - $present_students;
            
            $sql = "INSERT INTO elearning_attendance 
                    (academic_year_id, term, date, subject_id, teacher_id, class_name, 
                     lesson_topic, lesson_time, lesson_duration, attendance_type, 
                     platform_used, total_students, present_students, absent_students, 
                     interaction_level, technical_issues, notes, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $result = query($sql, [
                $academic_year_id, $term, $date, $subject_id, $teacher_id, $class_name,
                $lesson_topic, $lesson_time, $lesson_duration, $attendance_type,
                $platform_used, $total_students, $present_students, $absent_students,
                $interaction_level, $technical_issues, $notes, $_SESSION['user_id']
            ]);
            
            if ($result) {
                $success_message = 'تم تسجيل الحضور بنجاح';
                // إعادة تعيين المتغيرات
                $_POST = [];
            } else {
                $error_message = 'حدث خطأ أثناء تسجيل الحضور';
            }
        } catch (Exception $e) {
            $error_message = 'حدث خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}

// جلب البيانات المطلوبة للنموذج
$academic_years = query("SELECT * FROM academic_years ORDER BY name DESC");
$subjects = query("SELECT * FROM subjects ORDER BY name");
$teachers = query("SELECT * FROM teachers ORDER BY name");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- العنوان -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-clipboard-check ml-2"></i>
                تسجيل حضور التعليم الإلكتروني
            </h1>
            <nav class="text-sm text-gray-600">
                <a href="elearning_coordinator_dashboard.php" class="hover:text-blue-600">لوحة التحكم</a>
                <span class="mx-2">/</span>
                <span>تسجيل حضور التعليم الإلكتروني</span>
            </nav>
        </div>

        <!-- الرسائل -->
        <?php if ($success_message): ?>
            <?= show_alert($success_message, 'success') ?>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <?= show_alert($error_message, 'error') ?>
        <?php endif; ?>

        <!-- النموذج -->
        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" class="space-y-6">
                <!-- معلومات أساسية -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">السنة الدراسية *</label>
                        <select name="academic_year_id" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <?php foreach ($academic_years as $year): ?>
                                <option value="<?= $year['id'] ?>" <?= ($year['id'] == $current_year_id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($year['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">الفصل الدراسي *</label>
                        <select name="term" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">اختر الفصل</option>
                            <option value="first" <?= ($_POST['term'] ?? '') == 'first' ? 'selected' : '' ?>>الفصل الأول</option>
                            <option value="second" <?= ($_POST['term'] ?? '') == 'second' ? 'selected' : '' ?>>الفصل الثاني</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">التاريخ *</label>
                        <input type="date" name="date" required 
                               value="<?= htmlspecialchars($_POST['date'] ?? date('Y-m-d')) ?>"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- المادة والمعلم -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">المادة *</label>
                        <select name="subject_id" required onchange="loadTeachers()" 
                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">اختر المادة</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= $subject['id'] ?>" <?= ($_POST['subject_id'] ?? '') == $subject['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($subject['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">المعلم *</label>
                        <select name="teacher_id" required 
                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">اختر المعلم</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= $teacher['id'] ?>" <?= ($_POST['teacher_id'] ?? '') == $teacher['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($teacher['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- تفاصيل الحصة -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">اسم الصف *</label>
                        <input type="text" name="class_name" required 
                               value="<?= htmlspecialchars($_POST['class_name'] ?? '') ?>"
                               placeholder="مثال: الصف السابع أ"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">موضوع الدرس *</label>
                        <input type="text" name="lesson_topic" required 
                               value="<?= htmlspecialchars($_POST['lesson_topic'] ?? '') ?>"
                               placeholder="مثال: الجبر - المعادلات الخطية"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- وقت ومدة الحصة -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">وقت الحصة *</label>
                        <input type="time" name="lesson_time" required 
                               value="<?= htmlspecialchars($_POST['lesson_time'] ?? '') ?>"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">مدة الحصة (دقيقة)</label>
                        <input type="number" name="lesson_duration" min="15" max="120" 
                               value="<?= htmlspecialchars($_POST['lesson_duration'] ?? '45') ?>"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">نوع الحضور *</label>
                        <select name="attendance_type" required 
                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">اختر النوع</option>
                            <option value="live" <?= ($_POST['attendance_type'] ?? '') == 'live' ? 'selected' : '' ?>>مباشر</option>
                            <option value="recorded" <?= ($_POST['attendance_type'] ?? '') == 'recorded' ? 'selected' : '' ?>>مسجل</option>
                            <option value="interactive" <?= ($_POST['attendance_type'] ?? '') == 'interactive' ? 'selected' : '' ?>>تفاعلي</option>
                        </select>
                    </div>
                </div>

                <!-- منصة التعليم -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">المنصة المستخدمة</label>
                    <input type="text" name="platform_used" 
                           value="<?= htmlspecialchars($_POST['platform_used'] ?? '') ?>"
                           placeholder="مثال: Microsoft Teams, Zoom, نظام قطر للتعليم"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- إحصائيات الحضور -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">العدد الإجمالي للطلاب *</label>
                        <input type="number" name="total_students" required min="1" 
                               value="<?= htmlspecialchars($_POST['total_students'] ?? '') ?>"
                               onchange="calculateAbsent()"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">عدد الطلاب الحاضرين *</label>
                        <input type="number" name="present_students" required min="0" 
                               value="<?= htmlspecialchars($_POST['present_students'] ?? '') ?>"
                               onchange="calculateAbsent()"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">مستوى التفاعل *</label>
                        <select name="interaction_level" required 
                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">اختر المستوى</option>
                            <option value="high" <?= ($_POST['interaction_level'] ?? '') == 'high' ? 'selected' : '' ?>>عالي</option>
                            <option value="medium" <?= ($_POST['interaction_level'] ?? '') == 'medium' ? 'selected' : '' ?>>متوسط</option>
                            <option value="low" <?= ($_POST['interaction_level'] ?? '') == 'low' ? 'selected' : '' ?>>منخفض</option>
                        </select>
                    </div>
                </div>

                <!-- المشاكل التقنية -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">المشاكل التقنية (إن وجدت)</label>
                    <textarea name="technical_issues" rows="3" 
                              placeholder="اذكر أي مشاكل تقنية واجهتها أثناء الحصة..."
                              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($_POST['technical_issues'] ?? '') ?></textarea>
                </div>

                <!-- ملاحظات -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ملاحظات إضافية</label>
                    <textarea name="notes" rows="3" 
                              placeholder="أي ملاحظات أخرى حول الحصة..."
                              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                </div>

                <!-- أزرار التحكم -->
                <div class="flex justify-end space-x-reverse space-x-4">
                    <a href="elearning_coordinator_dashboard.php" 
                       class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-6 rounded transition duration-200">
                        إلغاء
                    </a>
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded transition duration-200">
                        <i class="fas fa-save ml-2"></i>
                        حفظ الحضور
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function calculateAbsent() {
            const total = parseInt(document.querySelector('[name="total_students"]').value) || 0;
            const present = parseInt(document.querySelector('[name="present_students"]').value) || 0;
            
            if (present > total) {
                alert('عدد الطلاب الحاضرين لا يمكن أن يزيد عن العدد الإجمالي');
                document.querySelector('[name="present_students"]').value = total;
            }
        }
    </script>
</body>
</html>
