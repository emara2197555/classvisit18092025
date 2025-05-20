<?php
// بدء التخزين المؤقت للمخرجات لمنع مشكلة "headers already sent"
ob_start();

// تضمين ملفات الاتصال بقاعدة البيانات والوظائف المشتركة
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// التحقق من وجود معرف الزيارة
$visit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($visit_id <= 0) {
    // إذا لم يتم تحديد معرف صحيح، توجيه المستخدم إلى صفحة الزيارات
    $_SESSION['alert_message'] = "يجب تحديد زيارة لتعديلها";
    $_SESSION['alert_type'] = "error";
    header('Location: visits.php');
    exit;
}

// استرجاع بيانات الزيارة
$visit_query = "
    SELECT 
        v.*,
        t.name AS teacher_name,
        s.name AS school_name,
        g.name AS grade_name,
        sec.name AS section_name,
        subj.name AS subject_name,
        vt.name AS visitor_type_name
    FROM 
        visits v
    JOIN 
        teachers t ON v.teacher_id = t.id
    JOIN 
        schools s ON v.school_id = s.id
    JOIN 
        grades g ON v.grade_id = g.id
    JOIN 
        sections sec ON v.section_id = sec.id
    JOIN 
        subjects subj ON v.subject_id = subj.id
    JOIN 
        visitor_types vt ON v.visitor_type_id = vt.id
    WHERE 
        v.id = ?
";

$visit = query_row($visit_query, [$visit_id]);

if (!$visit) {
    // إذا لم يتم العثور على الزيارة، توجيه المستخدم إلى صفحة الزيارات
    $_SESSION['alert_message'] = "لم يتم العثور على الزيارة المطلوبة";
    $_SESSION['alert_type'] = "error";
    header('Location: visits.php');
    exit;
}

// استرجاع تقييمات الزيارة
$evaluations_query = "
    SELECT 
        ve.*,
        ei.name AS indicator_name,
        ed.id AS domain_id,
        ed.name AS domain_name
    FROM 
        visit_evaluations ve
    JOIN 
        evaluation_indicators ei ON ve.indicator_id = ei.id
    JOIN 
        evaluation_domains ed ON ei.domain_id = ed.id
    WHERE 
        ve.visit_id = ?
    ORDER BY 
        ed.id, ei.id
";

$evaluations = query($evaluations_query, [$visit_id]);

// معالجة النموذج عند إرساله
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // بدء المعاملة
        global $pdo;
        $pdo->beginTransaction();

        // تحديث بيانات الزيارة
        $update_visit_query = "
            UPDATE visits 
            SET 
                teacher_id = ?,
                school_id = ?,
                grade_id = ?,
                section_id = ?,
                subject_id = ?,
                visitor_type_id = ?,
                visitor_person_id = ?,
                visit_date = ?,
                recommendation_notes = ?,
                appreciation_notes = ?,
                updated_at = NOW(),
                academic_year_id = ?
            WHERE 
                id = ?
        ";

        $visit_params = [
            $_POST['teacher_id'],
            $_POST['school_id'],
            $_POST['grade_id'],
            $_POST['section_id'],
            $_POST['subject_id'],
            $_POST['visitor_type_id'],
            $_POST['visitor_person_id'],
            $_POST['visit_date'],
            $_POST['recommendation_notes'],
            $_POST['appreciation_notes'],
            $_POST['academic_year_id'],
            $visit_id
        ];

        execute($update_visit_query, $visit_params);

        // تحديث تقييمات الزيارة
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'score_') === 0) {
                $evaluation_id = (int)substr($key, strlen('score_'));
                $score = (int)$value;
                $notes = $_POST['notes_' . $evaluation_id] ?? '';
                $recommendation_id = $_POST['recommendation_' . $evaluation_id] ?? null;
                
                // تحديث التقييم
                $update_evaluation_query = "
                    UPDATE visit_evaluations 
                    SET 
                        score = ?,
                        custom_recommendation = ?,
                        recommendation_id = ?,
                        updated_at = NOW()
                    WHERE 
                        id = ?
                ";
                
                execute($update_evaluation_query, [$score, $notes, $recommendation_id ?: null, $evaluation_id]);
            }
        }

        // تأكيد المعاملة
        $pdo->commit();

        // تعيين رسالة نجاح وتوجيه المستخدم
        $_SESSION['alert_message'] = "تم تحديث الزيارة بنجاح";
        $_SESSION['alert_type'] = "success";
        header('Location: visits.php');
        exit;
    } catch (Exception $e) {
        // التراجع عن المعاملة في حالة حدوث خطأ
        $pdo->rollBack();
        $error_message = "حدث خطأ أثناء تحديث الزيارة: " . $e->getMessage();
    }
}

// جلب بيانات القوائم المنسدلة
$schools = query("SELECT id, name FROM schools ORDER BY name");
$visitor_types = query("SELECT id, name FROM visitor_types ORDER BY name");
$grades = query("SELECT id, name FROM grades ORDER BY name");
$sections = query("SELECT id, name FROM sections ORDER BY name");
$subjects = query("SELECT id, name FROM subjects ORDER BY name");
$teachers = query("SELECT id, name FROM teachers ORDER BY name");
$visitors = query("SELECT id, name FROM teachers ORDER BY name");
$academic_years = query("SELECT id, name FROM academic_years ORDER BY id DESC");

// جلب مجالات ومؤشرات التقييم
$domains_query = "SELECT * FROM evaluation_domains ORDER BY id";
$domains = query($domains_query);

$indicators_by_domain = [];
foreach ($domains as $domain) {
    $indicators_query = "SELECT * FROM evaluation_indicators WHERE domain_id = ? ORDER BY id";
    $indicators_by_domain[$domain['id']] = query($indicators_query, [$domain['id']]);
}

// لم نعد بحاجة لجلب التوصيات هنا، سنقوم بجلبها مباشرة في كل مؤشر أداء

// تنظيم التقييمات حسب المؤشر
$evaluations_by_indicator = [];
foreach ($evaluations as $evaluation) {
    $evaluations_by_indicator[$evaluation['indicator_id']] = $evaluation;
}

// تعيين عنوان الصفحة
$page_title = 'تعديل زيارة صفية';

// تضمين ملف رأس الصفحة
require_once 'includes/header.php';
?>

<div class="container mx-auto px-4 mt-6">
    <h1 class="text-2xl font-bold mb-6">تعديل زيارة صفية</h1>
    
    <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border-r-4 border-red-500 text-red-700 p-4 mb-6 rounded-md">
            <?= $error_message ?>
        </div>
    <?php endif; ?>
    
    <form method="post" class="bg-white rounded-lg shadow-md p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- القسم الأول: معلومات الزيارة -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h2 class="text-lg font-semibold mb-4">معلومات الزيارة</h2>
                
                <!-- العام الدراسي -->
                <div class="mb-4">
                    <label for="academic_year_id" class="block mb-1">العام الدراسي</label>
                    <select id="academic_year_id" name="academic_year_id" class="w-full border border-gray-300 rounded-md">
                        <?php foreach ($academic_years as $year): ?>
                            <option value="<?= $year['id'] ?>" <?= $year['id'] == $visit['academic_year_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($year['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- المدرسة -->
                <div class="mb-4">
                    <label for="school_id" class="block mb-1">المدرسة</label>
                    <select id="school_id" name="school_id" class="w-full border border-gray-300 rounded-md">
                        <?php foreach ($schools as $school): ?>
                            <option value="<?= $school['id'] ?>" <?= $school['id'] == $visit['school_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($school['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- الصف -->
                <div class="mb-4">
                    <label for="grade_id" class="block mb-1">الصف</label>
                    <select id="grade_id" name="grade_id" class="w-full border border-gray-300 rounded-md">
                        <?php foreach ($grades as $grade): ?>
                            <option value="<?= $grade['id'] ?>" <?= $grade['id'] == $visit['grade_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($grade['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- الشعبة -->
                <div class="mb-4">
                    <label for="section_id" class="block mb-1">الشعبة</label>
                    <select id="section_id" name="section_id" class="w-full border border-gray-300 rounded-md">
                        <?php foreach ($sections as $section): ?>
                            <option value="<?= $section['id'] ?>" <?= $section['id'] == $visit['section_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($section['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- المادة -->
                <div class="mb-4">
                    <label for="subject_id" class="block mb-1">المادة</label>
                    <select id="subject_id" name="subject_id" class="w-full border border-gray-300 rounded-md">
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?= $subject['id'] ?>" <?= $subject['id'] == $visit['subject_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($subject['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- المعلم -->
                <div class="mb-4">
                    <label for="teacher_id" class="block mb-1">المعلم</label>
                    <select id="teacher_id" name="teacher_id" class="w-full border border-gray-300 rounded-md">
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?= $teacher['id'] ?>" <?= $teacher['id'] == $visit['teacher_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($teacher['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- القسم الثاني: معلومات الزائر وتفاصيل الزيارة -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h2 class="text-lg font-semibold mb-4">معلومات الزائر وتفاصيل الزيارة</h2>
                
                <!-- نوع الزائر -->
                <div class="mb-4">
                    <label for="visitor_type_id" class="block mb-1">نوع الزائر</label>
                    <select id="visitor_type_id" name="visitor_type_id" class="w-full border border-gray-300 rounded-md">
                        <?php foreach ($visitor_types as $type): ?>
                            <option value="<?= $type['id'] ?>" <?= $type['id'] == $visit['visitor_type_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- الزائر -->
                <div class="mb-4">
                    <label for="visitor_person_id" class="block mb-1">الزائر</label>
                    <select id="visitor_person_id" name="visitor_person_id" class="w-full border border-gray-300 rounded-md">
                        <?php foreach ($visitors as $visitor): ?>
                            <option value="<?= $visitor['id'] ?>" <?= $visitor['id'] == $visit['visitor_person_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($visitor['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- تاريخ الزيارة -->
                <div class="mb-4">
                    <label for="visit_date" class="block mb-1">تاريخ الزيارة</label>
                    <input type="date" id="visit_date" name="visit_date" class="w-full border border-gray-300 rounded-md" value="<?= $visit['visit_date'] ?>">
                </div>
                
                <!-- انصح المعلم -->
                <div class="mb-4">
                    <label for="recommendation_notes" class="block mb-1">انصح المعلم</label>
                    <textarea id="recommendation_notes" name="recommendation_notes" rows="3" class="w-full border border-gray-300 rounded-md"><?= htmlspecialchars($visit['recommendation_notes'] ?? '') ?></textarea>
                </div>
                
                <!-- اشكر المعلم -->
                <div class="mb-4">
                    <label for="appreciation_notes" class="block mb-1">اشكر المعلم</label>
                    <textarea id="appreciation_notes" name="appreciation_notes" rows="3" class="w-full border border-gray-300 rounded-md"><?= htmlspecialchars($visit['appreciation_notes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- قسم التقييم -->
        <div class="mb-6">
            <h2 class="text-xl font-semibold mb-4">نموذج التقييم</h2>
            
            <?php foreach ($domains as $domain): ?>
                <div class="mb-6">
                    <h3 class="text-lg font-medium mb-3 bg-primary-100 p-2 rounded-md"><?= $domain['name'] ?></h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-200">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="px-4 py-2 border text-right" width="50%">المؤشر</th>
                                    <th class="px-4 py-2 border text-center" width="15%">التقييم</th>
                                    <th class="px-4 py-2 border text-right" width="15%">التوصية</th>
                                    <th class="px-4 py-2 border text-right" width="20%">ملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($indicators_by_domain[$domain['id']])): ?>
                                    <?php foreach ($indicators_by_domain[$domain['id']] as $indicator): ?>
                                        <?php 
                                        // الحصول على بيانات التقييم بشكل آمن
                                        $evaluation = $evaluations_by_indicator[$indicator['id']] ?? null;
                                        $score = $evaluation ? $evaluation['score'] : 0;
                                        $notes = $evaluation && isset($evaluation['notes']) ? $evaluation['notes'] : '';
                                        $recommendation_id = $evaluation && isset($evaluation['recommendation_id']) ? $evaluation['recommendation_id'] : null;
                                        $evaluation_id = $evaluation ? $evaluation['id'] : 0;
                                        ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2 border"><?= htmlspecialchars($indicator['name']) ?></td>
                                            <td class="px-4 py-2 border text-center">
                                                <select name="score_<?= $evaluation_id ?>" class="border border-gray-300 rounded-md score-<?= $score ?>">
                                                    <option value="0" <?= $score == 0 ? 'selected' : '' ?>>لم يتم قياسه</option>
                                                    <option value="1" <?= $score == 1 ? 'selected' : '' ?>>الأدلة غير متوفرة أو محدودة</option>
                                                    <option value="2" <?= $score == 2 ? 'selected' : '' ?>>تتوفر بعض الأدلة</option>
                                                    <option value="3" <?= $score == 3 ? 'selected' : '' ?>>تتوفر معظم الأدلة</option>
                                                    <option value="4" <?= $score == 4 ? 'selected' : '' ?>>الأدلة مستكملة وفاعلة</option>
                                                </select>
                                            </td>
                                            <td class="px-4 py-2 border">
                                                <select name="recommendation_<?= $evaluation_id ?>" class="border border-gray-300 rounded-md w-full">
                                                    <option value="">بدون توصية</option>
                                                    <?php 
                                                    // جلب التوصيات المرتبطة بمؤشر الأداء الحالي
                                                    $indicator_recommendations = query("SELECT * FROM recommendations WHERE indicator_id = ? ORDER BY id", [$indicator['id']]);
                                                    foreach ($indicator_recommendations as $recommendation): 
                                                    ?>
                                                        <option value="<?= $recommendation['id'] ?>" <?= $recommendation_id == $recommendation['id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars(mb_substr($recommendation['text'], 0, 40) . (mb_strlen($recommendation['text']) > 40 ? '...' : '')) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td class="px-4 py-2 border">
                                                <input type="text" name="notes_<?= $evaluation_id ?>" class="border border-gray-300 rounded-md w-full" value="<?= htmlspecialchars($notes ?? '') ?>">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="px-4 py-2 border text-center">لا توجد مؤشرات لهذا المجال</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="flex justify-between">
            <button type="submit" class="bg-primary-600 text-white px-6 py-2 rounded-md hover:bg-primary-700 transition-colors">حفظ التعديلات</button>
            <a href="visits.php" class="bg-gray-300 text-gray-800 px-6 py-2 rounded-md hover:bg-gray-400 transition-colors">إلغاء</a>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // تحديث ألوان حقول التقييم
        updateSelectColor();
        
        // تحديث ألوان حقول التقييم عند التغيير
        function updateSelectColor() {
            const selects = document.querySelectorAll('select[name^="score_"]');
            selects.forEach(select => {
                select.addEventListener('change', function() {
                    // إزالة جميع الكلاسات السابقة
                    this.classList.remove('score-0', 'score-1', 'score-2', 'score-3', 'score-4');
                    // إضافة الكلاس الجديد
                    this.classList.add(`score-${this.value}`);
                });
                
                // تطبيق الكلاس الحالي
                select.classList.add(`score-${select.value}`);
            });
        }
        
        // تحديث المواد عند تغيير المدرسة
        document.getElementById('school_id').addEventListener('change', function() {
            const schoolId = this.value;
            
            // تحديث قائمة المواد الدراسية
            fetch(`api/get_subjects_by_school.php?school_id=${schoolId}`)
                .then(response => response.json())
                .then(data => {
                    const subjectSelect = document.getElementById('subject_id');
                    const currentSubject = subjectSelect.value;
                    
                    // إعادة بناء قائمة المواد
                    subjectSelect.innerHTML = '';
                    data.forEach(subject => {
                        const option = document.createElement('option');
                        option.value = subject.id;
                        option.textContent = subject.name;
                        if (subject.id == currentSubject) {
                            option.selected = true;
                        }
                        subjectSelect.appendChild(option);
                    });
                    
                    // تحديث قائمة المعلمين
                    updateTeachersList(schoolId, subjectSelect.value);
                })
                .catch(error => console.error('خطأ في جلب المواد الدراسية:', error));
        });
        
        // تحديث المعلمين عند تغيير المادة
        document.getElementById('subject_id').addEventListener('change', function() {
            const schoolId = document.getElementById('school_id').value;
            const subjectId = this.value;
            
            // تحديث قائمة المعلمين
            updateTeachersList(schoolId, subjectId);
        });
        
        // دالة لتحديث قائمة المعلمين
        function updateTeachersList(schoolId, subjectId) {
            fetch(`api/get_teachers_by_school_subject.php?school_id=${schoolId}&subject_id=${subjectId}`)
                .then(response => response.json())
                .then(data => {
                    const teacherSelect = document.getElementById('teacher_id');
                    const currentTeacher = teacherSelect.value;
                    
                    // إعادة بناء قائمة المعلمين
                    teacherSelect.innerHTML = '';
                    data.forEach(teacher => {
                        const option = document.createElement('option');
                        option.value = teacher.id;
                        option.textContent = teacher.name;
                        if (teacher.id == currentTeacher) {
                            option.selected = true;
                        }
                        teacherSelect.appendChild(option);
                    });
                })
                .catch(error => console.error('خطأ في جلب المعلمين:', error));
        }
    });
</script>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?> 