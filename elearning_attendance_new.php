<?php
/**
 * صفحة تسجيل حضور حصص التعليم الإلكتروني
 */

require_once 'includes/auth_functions.php';
require_once 'includes/functions.php';

// حماية الصفحة لمنسقي التعليم الإلكتروني
protect_page(['E-Learning Coordinator', 'Admin', 'Director', 'Academic Deputy']);

$page_title = 'تسجيل حضور حصة التعليم الإلكتروني';
$success_message = '';
$error_message = '';

// الحصول على السنة الدراسية الحالية
$current_year = get_active_academic_year();
$current_year_id = $current_year ? $current_year['id'] : 2;

// جلب البيانات المرجعية
$academic_years = query("SELECT * FROM academic_years ORDER BY name DESC");
$subjects = query("SELECT * FROM subjects ORDER BY name");
$schools = query("SELECT * FROM schools ORDER BY name");

// معالجة النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $academic_year_id = $_POST['academic_year_id'] ?? $current_year_id;
    $school_id = $_POST['school_id'] ?? '';
    $subject_id = $_POST['subject_id'] ?? '';
    $teacher_id = $_POST['teacher_id'] ?? '';
    $grade_id = $_POST['grade_id'] ?? '';
    $section_id = $_POST['section_id'] ?? '';
    $lesson_date = $_POST['lesson_date'] ?? '';
    $lesson_number = $_POST['lesson_number'] ?? '';
    $attendance_type = $_POST['attendance_type'] ?? 'direct'; // افتراضياً حضور مباشر
    $elearning_tools = $_POST['elearning_tools'] ?? [];
    $lesson_topic = trim($_POST['lesson_topic'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    // التحقق من صحة البيانات
    $errors = [];
    
    if (empty($school_id)) $errors[] = 'يرجى اختيار المدرسة';
    if (empty($subject_id)) $errors[] = 'يرجى اختيار المادة';
    if (empty($teacher_id)) $errors[] = 'يرجى اختيار المعلم';
    if (empty($grade_id)) $errors[] = 'يرجى اختيار الصف';
    if (empty($section_id)) $errors[] = 'يرجى اختيار الشعبة';
    if (empty($lesson_date)) $errors[] = 'يرجى تحديد تاريخ الحصة';
    if (empty($lesson_number)) $errors[] = 'يرجى تحديد رقم الحصة';
    if (empty($elearning_tools)) $errors[] = 'يرجى تحديد أدوات التعليم الإلكتروني المستخدمة';
    if (empty($lesson_topic)) $errors[] = 'يرجى إدخال موضوع الحصة';
    
    // التحقق من عدم وجود تسجيل مسبق
    if (empty($errors)) {
        $existing_record = query_row("
            SELECT id FROM elearning_attendance 
            WHERE academic_year_id = ? AND school_id = ? AND teacher_id = ? 
            AND subject_id = ? AND grade_id = ? AND section_id = ? 
            AND lesson_date = ? AND lesson_number = ?
        ", [$academic_year_id, $school_id, $teacher_id, $subject_id, $grade_id, $section_id, $lesson_date, $lesson_number]);
        
        if ($existing_record) {
            $errors[] = 'يوجد تسجيل مسبق لهذه الحصة في نفس التاريخ والوقت';
        }
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
    
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO elearning_attendance 
                    (academic_year_id, school_id, subject_id, teacher_id, grade_id, section_id,
                     lesson_date, lesson_number, attendance_type, elearning_tools, lesson_topic,
                     attendance_rating, coordinator_id, notes, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $result = query($sql, [
                $academic_year_id, $school_id, $subject_id, $teacher_id, $grade_id, $section_id,
                $lesson_date, $lesson_number, $attendance_type, json_encode($elearning_tools), $lesson_topic,
                $attendance_rating, $_SESSION['user_id'], $notes
            ]);
            
            global $pdo;
            if ($pdo->lastInsertId()) {
                $success_message = 'تم تسجيل حضور الحصة بنجاح';
                $_POST = []; // إعادة تعيين النموذج
            } else {
                $error_message = 'حدث خطأ أثناء تسجيل الحضور';
            }
        } catch (Exception $e) {
            $error_message = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
            error_log("خطأ في تسجيل الحضور: " . $e->getMessage());
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}

?>

<?php include 'includes/elearning_header.php'; ?>

<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-4xl mx-auto px-4">
        <!-- عنوان الصفحة -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">تسجيل حضور حصة التعليم الإلكتروني</h1>
            <p class="text-gray-600 mt-1">تسجيل ومتابعة حضور المعلمين لحصص التعليم الإلكتروني</p>
        </div>

        <!-- الرسائل -->
        <?php if ($success_message): ?>
            <div class="bg-green-100 border-r-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                <div class="flex items-center">
                    <i class="fas fa-check-circle ml-2"></i>
                    <?= htmlspecialchars($success_message) ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="bg-red-100 border-r-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle ml-2"></i>
                    <?= $error_message ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- النموذج -->
        <form method="POST" class="space-y-6">
            <!-- المعلومات الأساسية -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-info-circle ml-2 text-blue-600"></i>
                    المعلومات الأساسية
                </h2>
                
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">تاريخ الحصة *</label>
                        <input type="date" name="lesson_date" required 
                               value="<?= htmlspecialchars($_POST['lesson_date'] ?? date('Y-m-d')) ?>"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">رقم الحصة *</label>
                        <select name="lesson_number" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">اختر رقم الحصة</option>
                            <?php for ($i = 1; $i <= 7; $i++): ?>
                                <option value="<?= $i ?>" <?= ($_POST['lesson_number'] ?? '') == $i ? 'selected' : '' ?>>
                                    الحصة <?= $i ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">المدرسة *</label>
                        <select name="school_id" id="school_id" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">اختر المدرسة</option>
                            <?php foreach ($schools as $school): ?>
                                <option value="<?= $school['id'] ?>" <?= ($_POST['school_id'] ?? '') == $school['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($school['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">نوع الحضور</label>
                        <select name="attendance_type" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="direct" <?= ($_POST['attendance_type'] ?? 'direct') == 'direct' ? 'selected' : '' ?>>حضور مباشر</option>
                            <option value="remote" <?= ($_POST['attendance_type'] ?? '') == 'remote' ? 'selected' : '' ?>>حضور عن بُعد</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- اختيار المادة والمعلم -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-chalkboard-teacher ml-2 text-green-600"></i>
                    المادة والمعلم
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">المادة *</label>
                        <select name="subject_id" id="subject_id" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                        <select name="teacher_id" id="teacher_id" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">اختر المعلم</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- اختيار الصف والشعبة -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-users ml-2 text-purple-600"></i>
                    الصف والشعبة
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">الصف *</label>
                        <select name="grade_id" id="grade_id" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">اختر الصف</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">الشعبة *</label>
                        <select name="section_id" id="section_id" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">اختر الشعبة</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- أدوات التعليم الإلكتروني -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-laptop ml-2 text-indigo-600"></i>
                    أدوات التعليم الإلكتروني المستخدمة *
                </h2>
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
                    
                    foreach ($elearning_tools_options as $key => $label): 
                        $checked = in_array($key, $_POST['elearning_tools'] ?? []) ? 'checked' : '';
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

            <!-- تفاصيل الحصة -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-clipboard-list ml-2 text-orange-600"></i>
                    تفاصيل الحصة
                </h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">موضوع الحصة *</label>
                        <input type="text" name="lesson_topic" required 
                               value="<?= htmlspecialchars($_POST['lesson_topic'] ?? '') ?>"
                               placeholder="أدخل موضوع الحصة..."
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">ملاحظات إضافية</label>
                        <textarea name="notes" rows="3" 
                                  placeholder="أي ملاحظات أو تفاصيل إضافية..."
                                  class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- أزرار التحكم -->
            <div class="flex justify-end space-x-reverse space-x-4">
                <a href="elearning_coordinator_dashboard.php" 
                   class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-6 rounded transition duration-200">
                    إلغاء
                </a>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded transition duration-200">
                    <i class="fas fa-save ml-2"></i>
                    تسجيل الحضور
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// تحديث المعلمين عند اختيار المادة
document.getElementById('subject_id').addEventListener('change', function() {
    const subjectId = this.value;
    const schoolId = document.getElementById('school_id').value;
    const teacherSelect = document.getElementById('teacher_id');
    
    // إعادة تعيين قائمة المعلمين
    teacherSelect.innerHTML = '<option value="">اختر المعلم</option>';
    
    if (subjectId && schoolId) {
        fetch(`api/get_teachers_by_school_subject.php?school_id=${schoolId}&subject_id=${subjectId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    data.teachers.forEach(teacher => {
                        const option = document.createElement('option');
                        option.value = teacher.id;
                        option.textContent = teacher.name;
                        teacherSelect.appendChild(option);
                    });
                }
            })
            .catch(error => console.error('خطأ في جلب المعلمين:', error));
    }
});

// تحديث المعلمين عند اختيار المدرسة أيضاً
document.getElementById('school_id').addEventListener('change', function() {
    const subjectId = document.getElementById('subject_id').value;
    if (subjectId) {
        document.getElementById('subject_id').dispatchEvent(new Event('change'));
    }
    
    // تحديث الصفوف حسب المدرسة
    updateGrades();
});

// تحديث الصفوف حسب المدرسة
function updateGrades() {
    const schoolId = document.getElementById('school_id').value;
    const gradeSelect = document.getElementById('grade_id');
    const sectionSelect = document.getElementById('section_id');
    
    // إعادة تعيين الصفوف والشعب
    gradeSelect.innerHTML = '<option value="">اختر الصف</option>';
    sectionSelect.innerHTML = '<option value="">اختر الشعبة</option>';
    
    if (schoolId) {
        fetch(`api/get_grades.php?school_id=${schoolId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    data.grades.forEach(grade => {
                        const option = document.createElement('option');
                        option.value = grade.id;
                        option.textContent = grade.name;
                        gradeSelect.appendChild(option);
                    });
                }
            })
            .catch(error => console.error('خطأ في جلب الصفوف:', error));
    }
}

// تحديث الشعب عند اختيار الصف
document.getElementById('grade_id').addEventListener('change', function() {
    const gradeId = this.value;
    const schoolId = document.getElementById('school_id').value;
    const sectionSelect = document.getElementById('section_id');
    
    // إعادة تعيين الشعب
    sectionSelect.innerHTML = '<option value="">اختر الشعبة</option>';
    
    if (gradeId && schoolId) {
        fetch(`api/get_sections.php?school_id=${schoolId}&grade_id=${gradeId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    data.sections.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section.id;
                        option.textContent = section.name;
                        sectionSelect.appendChild(option);
                    });
                }
            })
            .catch(error => console.error('خطأ في جلب الشعب:', error));
    }
});

// تحديث مؤشر التقييم عند تغيير الأدوات المختارة
document.querySelectorAll('input[name="elearning_tools[]"]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const checkedTools = document.querySelectorAll('input[name="elearning_tools[]"]:checked');
        const toolsCount = checkedTools.length;
        
        // يمكن إضافة تأثير بصري هنا لإظهار التقييم المتوقع
        console.log(`عدد الأدوات المختارة: ${toolsCount}`);
    });
});
</script>

<?php include 'includes/elearning_footer.php'; ?>
