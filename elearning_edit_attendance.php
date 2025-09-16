<?php
/**
 * صفحة تعديل سجل حضور التعليم الإلكتروني
 */

require_once 'includes/auth_functions.php';
require_once 'includes/functions.php';

// حماية الصفحة لمنسقي التعليم الإلكتروني
protect_page(['E-Learning Coordinator', 'Admin', 'Director', 'Academic Deputy']);

$page_title = 'تعديل سجل الحضور';

// التحقق من وجود معرف السجل
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: elearning_attendance_reports.php");
    exit;
}

$attendance_id = (int)$_GET['id'];

// جلب تفاصيل السجل
$attendance_result = query("
    SELECT 
        ea.*,
        t.name as teacher_name,
        s.name as subject_name,
        g.name as grade_name,
        sec.name as section_name,
        sch.name as school_name
    FROM elearning_attendance ea
    JOIN teachers t ON ea.teacher_id = t.id
    JOIN subjects s ON ea.subject_id = s.id
    LEFT JOIN grades g ON ea.grade_id = g.id
    LEFT JOIN sections sec ON ea.section_id = sec.id
    LEFT JOIN schools sch ON ea.school_id = sch.id
    WHERE ea.id = ?
", [$attendance_id]);

$attendance = !empty($attendance_result) ? $attendance_result[0] : null;

if (!$attendance) {
    header("Location: elearning_attendance_reports.php");
    exit;
}

// جلب البيانات للقوائم المنسدلة
$academic_years = query("SELECT * FROM academic_years ORDER BY name DESC");
$subjects = query("SELECT * FROM subjects ORDER BY name");
$teachers = query("SELECT * FROM teachers ORDER BY name");
$grades = query("SELECT * FROM grades ORDER BY name");
$sections = query("SELECT * FROM sections ORDER BY name");

$message = '';
$error = '';

// معالجة النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $lesson_date = $_POST['lesson_date'] ?? '';
        $teacher_id = (int)($_POST['teacher_id'] ?? 0);
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        $grade_id = (int)($_POST['grade_id'] ?? 0);
        $section_id = (int)($_POST['section_id'] ?? 0);
        $academic_year_id = (int)($_POST['academic_year_id'] ?? 0);
        $lesson_number = $_POST['lesson_number'] ?? '';
        $attendance_type = $_POST['attendance_type'] ?? '';
        $elearning_tools = $_POST['elearning_tools'] ?? [];
        $lesson_topic = $_POST['lesson_topic'] ?? '';
        $num_students = (int)($_POST['num_students'] ?? 0);
        $attendance_students = (int)($_POST['attendance_students'] ?? 0);
        $notes = $_POST['notes'] ?? '';

        // التحقق من البيانات
        if (empty($lesson_date) || $teacher_id <= 0 || $subject_id <= 0 || $academic_year_id <= 0 || empty($lesson_number)) {
            throw new Exception('يرجى ملء جميع الحقول المطلوبة');
        }

        if ($num_students <= 0) {
            throw new Exception('عدد الطلاب يجب أن يكون أكبر من صفر');
        }

        if ($attendance_students < 0 || $attendance_students > $num_students) {
            throw new Exception('عدد الطلاب الحاضرين غير صحيح');
        }

        if (empty($elearning_tools)) {
            throw new Exception('يرجى تحديد أدوات التعليم الإلكتروني المستخدمة');
        }

        if (empty($lesson_topic)) {
            throw new Exception('يرجى إدخال موضوع الحصة');
        }

        // حساب تقييم الحضور بناءً على عدد الأدوات المستخدمة
        $attendance_rating = 'poor';
        $tools_count = count($elearning_tools);
        if ($tools_count >= 4) {
            $attendance_rating = 'excellent';
        } elseif ($tools_count >= 3) {
            $attendance_rating = 'very_good';
        } elseif ($tools_count >= 2) {
            $attendance_rating = 'good';
        } elseif ($tools_count >= 1) {
            $attendance_rating = 'acceptable';
        }

        // تحديث السجل
        $update_result = query("
            UPDATE elearning_attendance 
            SET lesson_date = ?, 
                teacher_id = ?, 
                subject_id = ?, 
                grade_id = ?, 
                section_id = ?, 
                academic_year_id = ?, 
                lesson_number = ?, 
                attendance_type = ?, 
                elearning_tools = ?, 
                lesson_topic = ?, 
                num_students = ?, 
                attendance_students = ?, 
                attendance_rating = ?, 
                notes = ?,
                updated_at = NOW()
            WHERE id = ?
        ", [
            $lesson_date, $teacher_id, $subject_id, $grade_id, $section_id,
            $academic_year_id, $lesson_number, $attendance_type, json_encode($elearning_tools), $lesson_topic,
            $num_students, $attendance_students, $attendance_rating, $notes, $attendance_id
        ]);

        if ($update_result !== false) {
            $message = 'تم تحديث سجل الحضور بنجاح';
            
            // إعادة جلب البيانات المحدثة
            $attendance_result = query("
                SELECT 
                    ea.*,
                    t.name as teacher_name,
                    s.name as subject_name,
                    g.name as grade_name,
                    sec.name as section_name,
                    sch.name as school_name
                FROM elearning_attendance ea
                JOIN teachers t ON ea.teacher_id = t.id
                JOIN subjects s ON ea.subject_id = s.id
                LEFT JOIN grades g ON ea.grade_id = g.id
                LEFT JOIN sections sec ON ea.section_id = sec.id
                LEFT JOIN schools sch ON ea.school_id = sch.id
                WHERE ea.id = ?
            ", [$attendance_id]);
            $attendance = !empty($attendance_result) ? $attendance_result[0] : null;
        } else {
            throw new Exception('حدث خطأ أثناء تحديث السجل');
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<?php include 'includes/elearning_header.php'; ?>

<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-4xl mx-auto px-4">
        <!-- العنوان والتنقل -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">
                        <i class="fas fa-edit ml-2"></i>
                        تعديل سجل الحضور
                    </h1>
                    <nav class="text-sm text-gray-600">
                        <a href="elearning_coordinator_dashboard.php" class="hover:text-blue-600">لوحة التحكم</a>
                        <span class="mx-2">/</span>
                        <a href="elearning_attendance_reports.php" class="hover:text-blue-600">تقارير الحضور</a>
                        <span class="mx-2">/</span>
                        <span>تعديل السجل</span>
                    </nav>
                </div>
                <div class="flex space-x-2 rtl:space-x-reverse">
                    <a href="elearning_view_attendance.php?id=<?= $attendance['id'] ?>" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                        <i class="fas fa-eye ml-2"></i>
                        عرض
                    </a>
                    <a href="elearning_attendance_reports.php" 
                       class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                        <i class="fas fa-arrow-right ml-2"></i>
                        العودة
                    </a>
                </div>
            </div>
        </div>

        <!-- رسائل النجاح والأخطاء -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 ml-2"></i>
                    <span class="text-green-800"><?= htmlspecialchars($message) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-600 ml-2"></i>
                    <span class="text-red-800"><?= htmlspecialchars($error) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- نموذج التعديل -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 bg-blue-50 border-b border-blue-200">
                <h2 class="text-lg font-bold text-blue-800">تعديل بيانات الحضور</h2>
            </div>

            <form method="POST" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- العمود الأول -->
                    <div class="space-y-4">
                        <!-- تاريخ الدرس -->
                        <div>
                            <label for="lesson_date" class="block text-sm font-medium text-gray-700 mb-2">
                                تاريخ الدرس <span class="text-red-500">*</span>
                            </label>
                            <input type="date" 
                                   id="lesson_date" 
                                   name="lesson_date" 
                                   value="<?= htmlspecialchars($attendance['lesson_date']) ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   required>
                        </div>

                        <!-- رقم الحصة -->
                        <div>
                            <label for="lesson_number" class="block text-sm font-medium text-gray-700 mb-2">
                                رقم الحصة <span class="text-red-500">*</span>
                            </label>
                            <input type="number" 
                                   id="lesson_number" 
                                   name="lesson_number" 
                                   value="<?= htmlspecialchars($attendance['lesson_number']) ?>"
                                   min="1" max="8"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   required>
                        </div>

                        <!-- المعلم -->
                        <div>
                            <label for="teacher_id" class="block text-sm font-medium text-gray-700 mb-2">
                                المعلم <span class="text-red-500">*</span>
                            </label>
                            <select id="teacher_id" 
                                    name="teacher_id" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required>
                                <option value="">اختر المعلم</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?= $teacher['id'] ?>" 
                                            <?= $teacher['id'] == $attendance['teacher_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($teacher['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- المادة -->
                        <div>
                            <label for="subject_id" class="block text-sm font-medium text-gray-700 mb-2">
                                المادة <span class="text-red-500">*</span>
                            </label>
                            <select id="subject_id" 
                                    name="subject_id" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required>
                                <option value="">اختر المادة</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= $subject['id'] ?>" 
                                            <?= $subject['id'] == $attendance['subject_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($subject['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- الصف -->
                        <div>
                            <label for="grade_id" class="block text-sm font-medium text-gray-700 mb-2">
                                الصف
                            </label>
                            <select id="grade_id" 
                                    name="grade_id" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">اختر الصف</option>
                                <?php foreach ($grades as $grade): ?>
                                    <option value="<?= $grade['id'] ?>" 
                                            <?= $grade['id'] == $attendance['grade_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($grade['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- الشعبة -->
                        <div>
                            <label for="section_id" class="block text-sm font-medium text-gray-700 mb-2">
                                الشعبة
                            </label>
                            <select id="section_id" 
                                    name="section_id" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">اختر الشعبة</option>
                                <?php foreach ($sections as $section): ?>
                                    <option value="<?= $section['id'] ?>" 
                                            <?= $section['id'] == $attendance['section_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($section['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- السنة الدراسية -->
                        <div>
                            <label for="academic_year_id" class="block text-sm font-medium text-gray-700 mb-2">
                                السنة الدراسية <span class="text-red-500">*</span>
                            </label>
                            <select id="academic_year_id" 
                                    name="academic_year_id" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required>
                                <option value="">اختر السنة الدراسية</option>
                                <?php foreach ($academic_years as $year): ?>
                                    <option value="<?= $year['id'] ?>" 
                                            <?= $year['id'] == $attendance['academic_year_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($year['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- العمود الثاني -->
                    <div class="space-y-4">

                        <!-- نوع الحضور -->
                        <div>
                            <label for="attendance_type" class="block text-sm font-medium text-gray-700 mb-2">
                                نوع الحضور
                            </label>
                            <select id="attendance_type" 
                                    name="attendance_type" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">اختر النوع</option>
                                <option value="direct" <?= $attendance['attendance_type'] == 'direct' ? 'selected' : '' ?>>حضور مباشر</option>
                                <option value="remote" <?= $attendance['attendance_type'] == 'remote' ? 'selected' : '' ?>>حضور عن بُعد</option>
                            </select>
                        </div>

                        <!-- عدد الطلاب -->
                        <div>
                            <label for="num_students" class="block text-sm font-medium text-gray-700 mb-2">
                                إجمالي عدد الطلاب <span class="text-red-500">*</span>
                            </label>
                            <input type="number" 
                                   id="num_students" 
                                   name="num_students" 
                                   value="<?= htmlspecialchars($attendance['num_students']) ?>"
                                   min="1"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   required>
                        </div>

                        <!-- عدد الحاضرين -->
                        <div>
                            <label for="attendance_students" class="block text-sm font-medium text-gray-700 mb-2">
                                عدد الطلاب الحاضرين <span class="text-red-500">*</span>
                            </label>
                            <input type="number" 
                                   id="attendance_students" 
                                   name="attendance_students" 
                                   value="<?= htmlspecialchars($attendance['attendance_students']) ?>"
                                   min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   required>
                        </div>

                    </div>
                </div>

                <!-- أدوات التعليم الإلكتروني -->
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        أدوات التعليم الإلكتروني المستخدمة <span class="text-red-500">*</span>
                    </label>
                    <p class="text-sm text-gray-600 mb-4">حدد الأدوات والحلول الرقمية التي تم تفعيلها في الحصة (يمكن اختيار أكثر من أداة)</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php 
                        $elearning_tools_options = [
                            'qatar_system' => 'نظام قطر للتعليم',
                            'tablets' => 'الأجهزة اللوحية',
                            'interactive_display' => 'برامج أجهزة العرض التفاعلي',
                            'ai_applications' => 'تطبيقات الذكاء الاصطناعي',
                            'interactive_websites' => 'المواقع التفاعلية'
                        ];
                        
                        $selected_tools = json_decode($attendance['elearning_tools'] ?? '[]', true) ?: [];
                        
                        foreach ($elearning_tools_options as $key => $label): 
                            $checked = in_array($key, $selected_tools) ? 'checked' : '';
                        ?>
                            <div class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                                <input type="checkbox" name="elearning_tools[]" value="<?= $key ?>" 
                                       id="tool_<?= $key ?>" <?= $checked ?>
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="tool_<?= $key ?>" class="mr-3 text-sm font-medium text-gray-700 cursor-pointer">
                                    <?= htmlspecialchars($label) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- مؤشر تقييم الحضور -->
                    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-medium text-gray-800 mb-2">تقييم الحضور بناءً على الأدوات المستخدمة:</h4>
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-2 text-xs">
                            <div class="text-center p-2 bg-red-100 text-red-800 rounded">لا توجد أدوات - ضعيف</div>
                            <div class="text-center p-2 bg-orange-100 text-orange-800 rounded">أداة واحدة - مقبول</div>
                            <div class="text-center p-2 bg-yellow-100 text-yellow-800 rounded">أداتان - جيد</div>
                            <div class="text-center p-2 bg-green-100 text-green-800 rounded">3 أدوات - جيد جداً</div>
                            <div class="text-center p-2 bg-blue-100 text-blue-800 rounded">4+ أدوات - ممتاز</div>
                        </div>
                    </div>
                </div>

                <!-- موضوع الدرس -->
                <div class="mt-6">
                    <label for="lesson_topic" class="block text-sm font-medium text-gray-700 mb-2">
                        موضوع الدرس
                    </label>
                    <input type="text" 
                           id="lesson_topic" 
                           name="lesson_topic" 
                           value="<?= htmlspecialchars($attendance['lesson_topic']) ?>"
                           placeholder="أدخل موضوع الدرس"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- الملاحظات -->
                <div class="mt-6">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                        ملاحظات
                    </label>
                    <textarea id="notes" 
                              name="notes" 
                              rows="4"
                              placeholder="أدخل أي ملاحظات إضافية"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($attendance['notes'] ?? '') ?></textarea>
                </div>

                <!-- أزرار الحفظ -->
                <div class="mt-8 flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        <span class="text-red-500">*</span> الحقول المطلوبة
                    </div>
                    <div class="flex space-x-3 rtl:space-x-reverse">
                        <a href="elearning_attendance_reports.php" 
                           class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            إلغاء
                        </a>
                        <button type="submit" 
                                class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            <i class="fas fa-save ml-2"></i>
                            حفظ التغييرات
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// التحقق من صحة البيانات عند الإرسال
document.querySelector('form').addEventListener('submit', function(e) {
    const numStudents = parseInt(document.getElementById('num_students').value);
    const attendanceStudents = parseInt(document.getElementById('attendance_students').value);
    const elearningTools = document.querySelectorAll('input[name="elearning_tools[]"]:checked');
    const lessonTopic = document.getElementById('lesson_topic').value.trim();
    
    if (attendanceStudents > numStudents) {
        e.preventDefault();
        alert('عدد الطلاب الحاضرين لا يمكن أن يكون أكبر من إجمالي عدد الطلاب');
        return false;
    }
    
    if (elearningTools.length === 0) {
        e.preventDefault();
        alert('يرجى تحديد أدوات التعليم الإلكتروني المستخدمة');
        return false;
    }
    
    if (lessonTopic === '') {
        e.preventDefault();
        alert('يرجى إدخال موضوع الحصة');
        return false;
    }
});

// تحديث تقييم الحضور بناءً على الأدوات المختارة
function updateAttendanceRating() {
    const elearningTools = document.querySelectorAll('input[name="elearning_tools[]"]:checked');
    const toolsCount = elearningTools.length;
    
    // إزالة التمييز من جميع المؤشرات
    const indicators = document.querySelectorAll('.text-center.p-2');
    indicators.forEach(indicator => {
        indicator.classList.remove('ring-2', 'ring-blue-500');
    });
    
    // تمييز المؤشر المناسب
    if (toolsCount >= 4) {
        indicators[4].classList.add('ring-2', 'ring-blue-500');
    } else if (toolsCount >= 3) {
        indicators[3].classList.add('ring-2', 'ring-blue-500');
    } else if (toolsCount >= 2) {
        indicators[2].classList.add('ring-2', 'ring-blue-500');
    } else if (toolsCount >= 1) {
        indicators[1].classList.add('ring-2', 'ring-blue-500');
    } else {
        indicators[0].classList.add('ring-2', 'ring-blue-500');
    }
}

// إضافة مستمعي الأحداث لأدوات التعليم الإلكتروني
document.querySelectorAll('input[name="elearning_tools[]"]').forEach(checkbox => {
    checkbox.addEventListener('change', updateAttendanceRating);
});

// تحديث نسبة الحضور تلقائياً
function updateAttendancePercentage() {
    const numStudents = parseInt(document.getElementById('num_students').value) || 0;
    const attendanceStudents = parseInt(document.getElementById('attendance_students').value) || 0;
    
    if (numStudents > 0) {
        const percentage = (attendanceStudents / numStudents * 100).toFixed(1);
        // يمكن إضافة عرض النسبة هنا إذا أردت
    }
}

document.getElementById('num_students').addEventListener('input', updateAttendancePercentage);
document.getElementById('attendance_students').addEventListener('input', updateAttendancePercentage);

// تحديث التقييم عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    updateAttendanceRating();
});
</script>

<?php include 'includes/elearning_footer.php'; ?>
