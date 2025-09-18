<?php
/**
 * نموذج تقييم زيارة صفية - نسخة جديدة مبسطة
 * تم إنشاؤها لحل مشكلة نوع الزائر
 */

require_once 'includes/db_connection.php';
require_once 'includes/auth_functions.php';

// بدء الجلسة
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error_message = '';
$success_message = '';

// معالجة إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // جلب البيانات من النموذج
        $school_id = $_POST['school_id'] ?? null;
        $teacher_id = $_POST['teacher_id'] ?? null;
        $subject_id = $_POST['subject_id'] ?? null;
        $grade_id = $_POST['grade_id'] ?? null;
        $section_id = $_POST['section_id'] ?? null;
        $level_id = $_POST['level_id'] ?? null;
        $visitor_type_id = $_POST['visitor_type_id'] ?? null;
        $visitor_person_id = $_POST['visitor_person_id'] ?? null;
        $visit_date = $_POST['visit_date'] ?? null;
        $visit_type = $_POST['visit_type'] ?? 'full';
        $attendance_type = $_POST['attendance_type'] ?? 'physical';
        $topic = $_POST['topic'] ?? '';
        
        // التحقق من البيانات الأساسية
        if (!$school_id || !$teacher_id || !$subject_id || !$visit_date || !$visitor_type_id || !$visitor_person_id) {
            throw new Exception("جميع الحقول مطلوبة.");
        }
        
        $success_message = "تم حفظ بيانات الزيارة بنجاح! (هذا اختبار - لم يتم الحفظ فعلياً)";
        
    } catch (Exception $e) {
        $error_message = "خطأ: " . $e->getMessage();
    }
}

try {
    // جلب البيانات الأساسية
    $schools = query("SELECT * FROM schools ORDER BY name");
    $subjects = query("SELECT * FROM subjects ORDER BY name");
    $grades = query("SELECT * FROM grades ORDER BY level_id, id");
    $visitor_types = query("SELECT * FROM visitor_types ORDER BY name");
    $academic_years = query("SELECT * FROM academic_years ORDER BY id DESC");
    
    // تحديد المدرسة الافتراضية (أول مدرسة)
    $default_school_id = !empty($schools) ? $schools[0]['id'] : null;
    
} catch (Exception $e) {
    $error_message = "خطأ في جلب البيانات: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نموذج تقييم زيارة صفية - نسخة جديدة</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        }
        .loading-spinner {
            border: 2px solid #f3f4f6;
            border-top: 2px solid #3b82f6;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .debug-panel {
            position: fixed;
            top: 10px;
            left: 10px;
            width: 300px;
            max-height: 400px;
            overflow-y: auto;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
            font-size: 12px;
            z-index: 1000;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .debug-message {
            padding: 2px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .debug-message:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body class="bg-gray-50">

    <!-- لوحة التشخيص -->
    <div id="debug-panel" class="debug-panel">
        <div class="flex justify-between items-center mb-2">
            <h3 class="font-bold text-sm">🔍 لوحة التشخيص</h3>
            <button onclick="clearDebugPanel()" class="text-red-500 text-xs hover:text-red-700">مسح</button>
        </div>
        <div id="debug-messages">
            <div class="debug-message text-gray-500">تم تحميل الصفحة...</div>
        </div>
    </div>

    <div class="container mx-auto p-6 max-w-6xl">
        
        <!-- العنوان -->
        <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">📋 نموذج تقييم زيارة صفية - نسخة جديدة</h1>
            <p class="text-gray-600">نسخة مبسطة لاختبار وظيفة نوع الزائر</p>
            <div class="mt-2 text-sm text-blue-600">
                <strong>ملاحظة:</strong> هذه نسخة جديدة للاختبار - الصفحة القديمة ما زالت موجودة
            </div>
        </div>

        <!-- الرسائل -->
        <?php if ($error_message): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                <strong>خطأ:</strong> <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
                <strong>نجح:</strong> <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <!-- النموذج الرئيسي -->
        <form method="POST" id="evaluation-form" class="space-y-6">
            
            <!-- البيانات الأساسية -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-lg font-semibold mb-4">📋 البيانات الأساسية</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    
                    <!-- المدرسة -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">🏫 المدرسة:</label>
                        <select id="school_id" name="school_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <?php foreach ($schools as $school): ?>
                                <option value="<?= $school['id'] ?>" <?= ($school['id'] == $default_school_id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($school['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- المادة -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">📚 المادة:</label>
                        <select id="subject_id" name="subject_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">اختر المادة...</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- المعلم -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">👨‍🏫 المعلم:</label>
                        <select id="teacher_id" name="teacher_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">اختر المعلم...</option>
                        </select>
                    </div>

                    <!-- الصف -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">📖 الصف:</label>
                        <select id="grade_id" name="grade_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">اختر الصف...</option>
                            <?php foreach ($grades as $grade): ?>
                                <option value="<?= $grade['id'] ?>"><?= htmlspecialchars($grade['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- الشعبة -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">👥 الشعبة:</label>
                        <select id="section_id" name="section_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">اختر الشعبة...</option>
                        </select>
                    </div>

                    <!-- تاريخ الزيارة -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">📅 تاريخ الزيارة:</label>
                        <input type="date" id="visit_date" name="visit_date" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>

                </div>
            </div>

            <!-- بيانات الزائر -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-lg font-semibold mb-4">👤 بيانات الزائر</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <!-- نوع الزائر -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">🧑‍💼 نوع الزائر:</label>
                        <select id="visitor_type_id" name="visitor_type_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">اختر نوع الزائر...</option>
                            <?php foreach ($visitor_types as $type): ?>
                                <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- اسم الزائر -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">👤 اسم الزائر:</label>
                        <div id="visitor-name-container" class="w-full border border-gray-300 rounded-md px-3 py-2 bg-gray-50 min-h-[42px] flex items-center">
                            <span class="text-gray-500 text-sm">اختر نوع الزائر أولاً</span>
                        </div>
                        <input type="hidden" id="visitor_person_id" name="visitor_person_id" value="">
                    </div>

                </div>
            </div>

            <!-- إعدادات الزيارة -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-lg font-semibold mb-4">⚙️ إعدادات الزيارة</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <!-- نوع الزيارة -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">📝 نوع الزيارة:</label>
                        <select id="visit_type" name="visit_type" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="full">زيارة كاملة</option>
                            <option value="partial">زيارة جزئية</option>
                        </select>
                    </div>

                    <!-- طريقة الحضور -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">🎯 طريقة الحضور:</label>
                        <select id="attendance_type" name="attendance_type" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="physical">حضوري</option>
                            <option value="virtual">افتراضي</option>
                        </select>
                    </div>

                    <!-- موضوع الدرس -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">📖 موضوع الدرس:</label>
                        <input type="text" id="topic" name="topic" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="اكتب موضوع الدرس...">
                    </div>

                </div>
            </div>

            <!-- أزرار التحكم -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex gap-4">
                    <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded-md hover:bg-blue-600 transition-colors flex items-center gap-2">
                        💾 حفظ الزيارة (اختبار)
                    </button>
                    <button type="button" onclick="resetForm()" class="bg-gray-500 text-white px-6 py-2 rounded-md hover:bg-gray-600 transition-colors">
                        🔄 إعادة تعيين
                    </button>
                    <button type="button" onclick="showFormData()" class="bg-green-500 text-white px-6 py-2 rounded-md hover:bg-green-600 transition-colors">
                        📊 عرض البيانات
                    </button>
                </div>
            </div>

        </form>

    </div>

<script>
// متغيرات عامة
let debugCounter = 0;

// دالة إضافة رسائل التشخيص
function addDebugMessage(message, type = 'info') {
    debugCounter++;
    const debugDiv = document.getElementById('debug-messages');
    const messageDiv = document.createElement('div');
    messageDiv.className = 'debug-message';
    
    let icon = '📝';
    let color = 'text-gray-700';
    
    switch(type) {
        case 'success': icon = '✅'; color = 'text-green-600'; break;
        case 'error': icon = '❌'; color = 'text-red-600'; break;
        case 'warning': icon = '⚠️'; color = 'text-yellow-600'; break;
        case 'info': icon = 'ℹ️'; color = 'text-blue-600'; break;
        case 'loading': icon = '🔄'; color = 'text-purple-600'; break;
    }
    
    const time = new Date().toLocaleTimeString();
    messageDiv.innerHTML = `
        <span class="text-xs text-gray-400">[${time}]</span> 
        <span class="${color}">${icon} ${message}</span>
    `;
    
    debugDiv.appendChild(messageDiv);
    debugDiv.scrollTop = debugDiv.scrollHeight;
    
    // الاحتفاظ بآخر 50 رسالة فقط
    if (debugDiv.children.length > 50) {
        debugDiv.removeChild(debugDiv.firstChild);
    }
    
    console.log(`[${time}] ${message}`);
}

// دالة مسح لوحة التشخيص
function clearDebugPanel() {
    document.getElementById('debug-messages').innerHTML = '';
    debugCounter = 0;
    addDebugMessage('تم مسح لوحة التشخيص', 'info');
}

// دالة تحديث اسم الزائر - نسخة مبسطة جداً
function updateVisitorName() {
    addDebugMessage('🔄 بدء تحديث اسم الزائر...', 'loading');
    
    // الحصول على العناصر
    const visitorTypeSelect = document.getElementById('visitor_type_id');
    const visitorNameContainer = document.getElementById('visitor-name-container');
    const visitorPersonIdInput = document.getElementById('visitor_person_id');
    const subjectSelect = document.getElementById('subject_id');
    const schoolSelect = document.getElementById('school_id');
    
    // التحقق من وجود العناصر
    if (!visitorTypeSelect) {
        addDebugMessage('❌ عنصر نوع الزائر غير موجود', 'error');
        return;
    }
    
    if (!visitorNameContainer) {
        addDebugMessage('❌ حاوي اسم الزائر غير موجود', 'error');
        return;
    }
    
    addDebugMessage('✅ تم العثور على جميع العناصر', 'success');
    
    // التحقق من اختيار نوع الزائر
    if (!visitorTypeSelect.value) {
        addDebugMessage('⚠️ لم يتم اختيار نوع الزائر', 'warning');
        visitorNameContainer.innerHTML = '<span class="text-gray-500 text-sm">اختر نوع الزائر أولاً</span>';
        if (visitorPersonIdInput) visitorPersonIdInput.value = '';
        return;
    }
    
    addDebugMessage(`✅ نوع الزائر المختار: ${visitorTypeSelect.value}`, 'info');
    
    // بناء رابط الـ API
    let apiUrl = `api/get_visitor_name.php?visitor_type_id=${visitorTypeSelect.value}`;
    
    if (subjectSelect && subjectSelect.value) {
        apiUrl += `&subject_id=${subjectSelect.value}`;
        addDebugMessage(`📚 تم إضافة معرف المادة: ${subjectSelect.value}`, 'info');
    }
    
    if (schoolSelect && schoolSelect.value) {
        apiUrl += `&school_id=${schoolSelect.value}`;
        addDebugMessage(`🏫 تم إضافة معرف المدرسة: ${schoolSelect.value}`, 'info');
    }
    
    addDebugMessage(`🌐 رابط API: ${apiUrl}`, 'info');
    
    // إظهار رسالة التحميل
    visitorNameContainer.innerHTML = '<div class="flex items-center gap-2 text-blue-600"><div class="loading-spinner"></div><span class="text-sm">جاري التحميل...</span></div>';
    
    // إرسال الطلب
    addDebugMessage('🔄 إرسال طلب AJAX...', 'loading');
    
    fetch(apiUrl)
        .then(response => {
            addDebugMessage(`📡 استجابة الخادم: ${response.status} ${response.statusText}`, response.ok ? 'success' : 'error');
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return response.json();
        })
        .then(data => {
            addDebugMessage(`📦 البيانات المستلمة: ${JSON.stringify(data)}`, 'info');
            
            if (data.success && data.visitors && data.visitors.length > 0) {
                addDebugMessage(`✅ تم العثور على ${data.visitors.length} زائر`, 'success');
                
                // إنشاء قائمة منسدلة
                const select = document.createElement('select');
                select.id = 'visitor_person_select';
                select.name = 'visitor_person_select';
                select.className = 'w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500';
                select.required = true;
                
                // الخيار الافتراضي
                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = 'اختر اسم الزائر...';
                select.appendChild(defaultOption);
                
                // إضافة الزوار
                data.visitors.forEach(visitor => {
                    const option = document.createElement('option');
                    option.value = visitor.id;
                    option.textContent = visitor.name;
                    select.appendChild(option);
                    addDebugMessage(`✅ تم إضافة الزائر: ${visitor.name} (ID: ${visitor.id})`, 'info');
                });
                
                // تحديث الحاوي
                visitorNameContainer.innerHTML = '';
                visitorNameContainer.appendChild(select);
                
                addDebugMessage('✅ تم إنشاء قائمة الزوار بنجاح!', 'success');
                
                // إضافة مستمع للاختيار
                select.addEventListener('change', function() {
                    addDebugMessage(`👤 تم اختيار الزائر: ${this.value}`, 'info');
                    if (visitorPersonIdInput) {
                        visitorPersonIdInput.value = this.value;
                        addDebugMessage(`💾 تم حفظ معرف الزائر: ${this.value}`, 'success');
                    }
                });
                
            } else if (data.success && (!data.visitors || data.visitors.length === 0)) {
                addDebugMessage('⚠️ لا توجد زوار متاحين لهذا النوع', 'warning');
                visitorNameContainer.innerHTML = '<span class="text-amber-600 text-sm">لا توجد زوار متاحين لهذا النوع</span>';
                if (visitorPersonIdInput) visitorPersonIdInput.value = '';
                
            } else {
                addDebugMessage(`❌ خطأ من الخادم: ${data.message || 'خطأ غير معروف'}`, 'error');
                visitorNameContainer.innerHTML = `<span class="text-red-600 text-sm">خطأ: ${data.message || 'فشل في جلب البيانات'}</span>`;
                if (visitorPersonIdInput) visitorPersonIdInput.value = '';
            }
        })
        .catch(error => {
            addDebugMessage(`❌ خطأ في الشبكة: ${error.message}`, 'error');
            visitorNameContainer.innerHTML = '<span class="text-red-600 text-sm">خطأ في الاتصال بالخادم</span>';
            if (visitorPersonIdInput) visitorPersonIdInput.value = '';
        });
}

// دالة تحميل المعلمين
function loadTeachers() {
    const schoolId = document.getElementById('school_id').value;
    const subjectId = document.getElementById('subject_id').value;
    const teacherSelect = document.getElementById('teacher_id');
    
    if (!schoolId || !subjectId) {
        teacherSelect.innerHTML = '<option value="">اختر المعلم...</option>';
        return;
    }
    
    addDebugMessage(`🔄 تحميل المعلمين للمدرسة ${schoolId} والمادة ${subjectId}`, 'loading');
    
    fetch(`api/get_teachers_by_school_subject.php?school_id=${schoolId}&subject_id=${subjectId}`)
        .then(response => response.json())
        .then(data => {
            teacherSelect.innerHTML = '<option value="">اختر المعلم...</option>';
            
            if (data.success && data.teachers) {
                data.teachers.forEach(teacher => {
                    const option = document.createElement('option');
                    option.value = teacher.id;
                    option.textContent = teacher.name;
                    teacherSelect.appendChild(option);
                });
                addDebugMessage(`✅ تم تحميل ${data.teachers.length} معلم`, 'success');
            }
        })
        .catch(error => {
            addDebugMessage(`❌ خطأ في تحميل المعلمين: ${error.message}`, 'error');
        });
}

// دالة تحميل الشعب
function loadSections() {
    const schoolId = document.getElementById('school_id').value;
    const gradeId = document.getElementById('grade_id').value;
    const sectionSelect = document.getElementById('section_id');
    
    if (!schoolId || !gradeId) {
        sectionSelect.innerHTML = '<option value="">اختر الشعبة...</option>';
        return;
    }
    
    addDebugMessage(`🔄 تحميل الشعب للمدرسة ${schoolId} والصف ${gradeId}`, 'loading');
    
    fetch(`api/get_sections_by_school_grade.php?school_id=${schoolId}&grade_id=${gradeId}`)
        .then(response => response.json())
        .then(data => {
            sectionSelect.innerHTML = '<option value="">اختر الشعبة...</option>';
            
            if (data.success && data.sections) {
                data.sections.forEach(section => {
                    const option = document.createElement('option');
                    option.value = section.id;
                    option.textContent = section.name;
                    sectionSelect.appendChild(option);
                });
                addDebugMessage(`✅ تم تحميل ${data.sections.length} شعبة`, 'success');
            }
        })
        .catch(error => {
            addDebugMessage(`❌ خطأ في تحميل الشعب: ${error.message}`, 'error');
        });
}

// دالة إعادة تعيين النموذج
function resetForm() {
    document.getElementById('evaluation-form').reset();
    document.getElementById('visitor-name-container').innerHTML = '<span class="text-gray-500 text-sm">اختر نوع الزائر أولاً</span>';
    document.getElementById('visitor_person_id').value = '';
    addDebugMessage('🔄 تم إعادة تعيين النموذج', 'info');
}

// دالة عرض بيانات النموذج
function showFormData() {
    const formData = new FormData(document.getElementById('evaluation-form'));
    const data = Object.fromEntries(formData.entries());
    
    addDebugMessage('📊 بيانات النموذج الحالية:', 'info');
    Object.entries(data).forEach(([key, value]) => {
        if (value) {
            addDebugMessage(`  ${key}: ${value}`, 'info');
        }
    });
}

// عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    addDebugMessage('🚀 تم تحميل الصفحة بنجاح', 'success');
    
    // تعيين التاريخ الحالي
    document.getElementById('visit_date').value = new Date().toISOString().split('T')[0];
    
    // تحميل المعلمين للمدرسة الافتراضية إذا تم اختيار مادة
    const defaultSchoolId = document.getElementById('school_id').value;
    if (defaultSchoolId) {
        addDebugMessage(`🏫 المدرسة الافتراضية: ${defaultSchoolId}`, 'info');
    }
    
    // ربط Event Listeners
    
    // نوع الزائر
    document.getElementById('visitor_type_id').addEventListener('change', function() {
        addDebugMessage(`🔄 تغيير نوع الزائر إلى: ${this.value}`, 'info');
        if (this.value) {
            updateVisitorName();
        } else {
            document.getElementById('visitor-name-container').innerHTML = '<span class="text-gray-500 text-sm">اختر نوع الزائر أولاً</span>';
            document.getElementById('visitor_person_id').value = '';
        }
    });
    
    // المدرسة
    document.getElementById('school_id').addEventListener('change', function() {
        addDebugMessage(`🏫 تغيير المدرسة إلى: ${this.value}`, 'info');
        loadTeachers();
        loadSections();
        
        // تحديث قائمة الزوار إذا كان نوع الزائر محدد
        const visitorType = document.getElementById('visitor_type_id').value;
        if (visitorType) {
            updateVisitorName();
        }
    });
    
    // المادة
    document.getElementById('subject_id').addEventListener('change', function() {
        addDebugMessage(`📚 تغيير المادة إلى: ${this.value}`, 'info');
        loadTeachers();
        
        // تحديث قائمة الزوار إذا كان نوع الزائر محدد
        const visitorType = document.getElementById('visitor_type_id').value;
        if (visitorType) {
            updateVisitorName();
        }
    });
    
    // الصف
    document.getElementById('grade_id').addEventListener('change', function() {
        addDebugMessage(`📖 تغيير الصف إلى: ${this.value}`, 'info');
        loadSections();
    });
    
    addDebugMessage('✅ تم ربط جميع Event Listeners', 'success');
    addDebugMessage('🎯 الصفحة جاهزة للاستخدام!', 'success');
});

// طباعة معلومات إضافية
console.log('🆕 صفحة تقييم الزيارة الجديدة - تم التحميل');
console.log('📊 معلومات:', {
    timestamp: new Date().toISOString(),
    url: window.location.href
});
</script>

</body>
</html>
