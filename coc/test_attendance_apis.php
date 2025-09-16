<?php
require_once 'includes/auth_functions.php';
require_once 'includes/functions.php';

// تسجيل دخول تجريبي
start_secure_session();
$_SESSION['user_id'] = 1;
$_SESSION['role_name'] = 'E-Learning Coordinator';
$_SESSION['full_name'] = 'مختبر النظام';

$page_title = 'اختبار تسجيل الحضور - API المحدث';
?>

<?php include 'includes/elearning_header.php'; ?>

<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-4xl mx-auto px-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">اختبار تسجيل الحضور - API المحدث</h1>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">نموذج الاختبار</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">المدرسة *</label>
                    <select id="school_id" required class="w-full border border-gray-300 rounded-md px-3 py-2">
                        <option value="">اختر المدرسة</option>
                        <option value="1">مدرسة عبد الله بن على المسند الثانوية للبنين</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">المادة *</label>
                    <select id="subject_id" required class="w-full border border-gray-300 rounded-md px-3 py-2">
                        <option value="">اختر المادة</option>
                        <option value="1">اللغة العربية</option>
                        <option value="2">الرياضيات</option>
                        <option value="3">الفيزياء</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">المعلم *</label>
                    <select id="teacher_id" required class="w-full border border-gray-300 rounded-md px-3 py-2">
                        <option value="">اختر المعلم</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">الصف *</label>
                    <select id="grade_id" required class="w-full border border-gray-300 rounded-md px-3 py-2">
                        <option value="">اختر الصف</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">الشعبة *</label>
                    <select id="section_id" required class="w-full border border-gray-300 rounded-md px-3 py-2">
                        <option value="">اختر الشعبة</option>
                    </select>
                </div>
            </div>
            
            <div class="mt-6">
                <button onclick="testAllAPIs()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    اختبار جميع APIs
                </button>
                <button onclick="clearLog()" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 ml-2">
                    مسح السجل
                </button>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">سجل الاختبار</h2>
            <div id="log" class="bg-gray-100 rounded p-4 h-96 overflow-y-auto font-mono text-sm"></div>
        </div>
    </div>
</div>

<script>
function log(message, type = 'info') {
    const logDiv = document.getElementById('log');
    const timestamp = new Date().toLocaleTimeString('ar-SA');
    const colors = {
        info: 'text-blue-600',
        success: 'text-green-600',
        error: 'text-red-600',
        warning: 'text-yellow-600'
    };
    
    logDiv.innerHTML += `<div class="${colors[type] || colors.info}">[${timestamp}] ${message}</div>`;
    logDiv.scrollTop = logDiv.scrollHeight;
}

function clearLog() {
    document.getElementById('log').innerHTML = '';
}

// تحديث المعلمين
function updateTeachers() {
    const schoolId = document.getElementById('school_id').value;
    const subjectId = document.getElementById('subject_id').value;
    const teacherSelect = document.getElementById('teacher_id');
    
    teacherSelect.innerHTML = '<option value="">اختر المعلم</option>';
    
    if (schoolId && subjectId) {
        log(`🔄 جلب المعلمين للمدرسة ${schoolId} والمادة ${subjectId}`);
        
        fetch(`api/get_teachers_by_school_subject.php?school_id=${schoolId}&subject_id=${subjectId}`)
            .then(response => {
                log(`📡 استجابة HTTP: ${response.status}`);
                return response.json();
            })
            .then(data => {
                log(`📦 البيانات المستلمة: ${JSON.stringify(data)}`, 'info');
                
                if (data.success && data.teachers) {
                    data.teachers.forEach(teacher => {
                        const option = document.createElement('option');
                        option.value = teacher.id;
                        option.textContent = teacher.name;
                        teacherSelect.appendChild(option);
                    });
                    log(`✅ تم تحميل ${data.teachers.length} معلم بنجاح`, 'success');
                } else {
                    log(`❌ فشل في جلب المعلمين: ${data.message || data.error || 'سبب غير معروف'}`, 'error');
                }
            })
            .catch(error => {
                log(`💥 خطأ في الشبكة: ${error.message}`, 'error');
            });
    } else {
        if (!schoolId) log('⚠️ المدرسة غير محددة', 'warning');
        if (!subjectId) log('⚠️ المادة غير محددة', 'warning');
    }
}

// تحديث الصفوف
function updateGrades() {
    const schoolId = document.getElementById('school_id').value;
    const gradeSelect = document.getElementById('grade_id');
    const sectionSelect = document.getElementById('section_id');
    
    gradeSelect.innerHTML = '<option value="">اختر الصف</option>';
    sectionSelect.innerHTML = '<option value="">اختر الشعبة</option>';
    
    if (schoolId) {
        log(`🔄 جلب الصفوف للمدرسة ${schoolId}`);
        
        fetch(`api/get_grades_by_school.php?school_id=${schoolId}`)
            .then(response => response.json())
            .then(data => {
                log(`📦 صفوف: ${JSON.stringify(data)}`, 'info');
                
                if (data.success && data.grades) {
                    data.grades.forEach(grade => {
                        const option = document.createElement('option');
                        option.value = grade.id;
                        option.textContent = grade.name;
                        gradeSelect.appendChild(option);
                    });
                    log(`✅ تم تحميل ${data.grades.length} صف`, 'success');
                } else {
                    log(`❌ فشل في جلب الصفوف: ${data.message || 'غير معروف'}`, 'error');
                }
            })
            .catch(error => {
                log(`💥 خطأ في جلب الصفوف: ${error.message}`, 'error');
            });
    }
}

// تحديث الشعب
function updateSections() {
    const schoolId = document.getElementById('school_id').value;
    const gradeId = document.getElementById('grade_id').value;
    const sectionSelect = document.getElementById('section_id');
    
    sectionSelect.innerHTML = '<option value="">اختر الشعبة</option>';
    
    if (schoolId && gradeId) {
        log(`🔄 جلب الشعب للمدرسة ${schoolId} والصف ${gradeId}`);
        
        fetch(`api/get_sections_by_school_grade.php?school_id=${schoolId}&grade_id=${gradeId}`)
            .then(response => response.json())
            .then(data => {
                log(`📦 شعب: ${JSON.stringify(data)}`, 'info');
                
                if (data.success && data.sections) {
                    data.sections.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section.id;
                        option.textContent = section.name;
                        sectionSelect.appendChild(option);
                    });
                    log(`✅ تم تحميل ${data.sections.length} شعبة`, 'success');
                } else {
                    log(`❌ فشل في جلب الشعب: ${data.message || 'غير معروف'}`, 'error');
                }
            })
            .catch(error => {
                log(`💥 خطأ في جلب الشعب: ${error.message}`, 'error');
            });
    }
}

// ربط الأحداث
document.getElementById('school_id').addEventListener('change', function() {
    log(`🏫 تم اختيار المدرسة: ${this.value}`, 'info');
    updateGrades();
    updateTeachers();
});

document.getElementById('subject_id').addEventListener('change', function() {
    log(`📚 تم اختيار المادة: ${this.value}`, 'info');
    updateTeachers();
});

document.getElementById('grade_id').addEventListener('change', function() {
    log(`🎓 تم اختيار الصف: ${this.value}`, 'info');
    updateSections();
});

function testAllAPIs() {
    log('🚀 بدء اختبار شامل لجميع APIs', 'info');
    
    // تعيين قيم للاختبار
    document.getElementById('school_id').value = '1';
    document.getElementById('subject_id').value = '1';
    
    // تشغيل الاختبارات
    updateGrades();
    updateTeachers();
    
    setTimeout(() => {
        document.getElementById('grade_id').value = '10';
        updateSections();
    }, 2000);
}

log('✅ تم تحميل صفحة الاختبار', 'success');
</script>

<?php include 'includes/elearning_footer.php'; ?>
