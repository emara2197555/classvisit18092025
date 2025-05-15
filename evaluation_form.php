<?php
// بدء التخزين المؤقت للمخرجات - سيحل مشكلة Headers already sent
ob_start();

// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// تعيين عنوان الصفحة
$page_title = 'نموذج تقييم زيارة صفية';

// معالجة النموذج إذا تم تقديمه
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_visit'])) {
    try {
        // استخراج البيانات من النموذج
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
        $has_lab = isset($_POST['has_lab']) && $_POST['has_lab'] == '1' ? 1 : 0;
        $general_notes = $_POST['general_notes'] ?? '';
        $recommendation_notes = $_POST['recommendation_notes'] ?? '';
        $appreciation_notes = $_POST['appreciation_notes'] ?? '';
        $total_score = $_POST['total_score'] ?? 0;
        
        // التحقق من وجود البيانات الأساسية
        if (!$school_id || !$teacher_id || !$subject_id || !$visit_date || !$visitor_type_id || !$visitor_person_id) {
            throw new Exception("البيانات الأساسية غير مكتملة.");
        }
        
        // إضافة الزيارة الصفية إلى جدول الزيارات
        $sql = "
            INSERT INTO visits (
                school_id, teacher_id, subject_id, grade_id, section_id, level_id, 
                visitor_type_id, visitor_person_id, visit_date, visit_type, attendance_type, has_lab, 
                general_notes, recommendation_notes, appreciation_notes, total_score, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
            )
        ";
        
        execute($sql, [
            $school_id, $teacher_id, $subject_id, $grade_id, $section_id, $level_id,
            $visitor_type_id, $visitor_person_id, $visit_date, $visit_type, $attendance_type, $has_lab,
            $general_notes, $recommendation_notes, $appreciation_notes, $total_score
        ]);
        
        // الحصول على معرف الزيارة المضافة
        $visit_id = last_insert_id();
        
        // حفظ تفاصيل التقييم لكل مؤشر
        $sql = "
            INSERT INTO visit_evaluations (
                visit_id, indicator_id, score, recommendation_id, custom_recommendation, 
                created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, NOW(), NOW()
            )
        ";
        
        // البحث عن مؤشرات التقييم في النموذج المرسل
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'score_') === 0) {
                $indicator_id = substr($key, 6);
                $score = intval($value);
                
                // حصول على التوصيات المختارة (إن وجدت)
                $recommendations = isset($_POST['recommend_' . $indicator_id]) ? $_POST['recommend_' . $indicator_id] : [];
                
                // التوصية المخصصة (إن وجدت)
                $custom_recommendation = $_POST['custom_recommend_' . $indicator_id] ?? '';
                
                // في حالة عدم وجود توصيات مختارة، نضيف سجل واحد مع التوصية المخصصة فقط
                if (empty($recommendations)) {
                    execute($sql, [
                        $visit_id, $indicator_id, $score, null, $custom_recommendation
                    ]);
                } else {
                    // إضافة كل توصية مختارة كسجل منفصل
                    foreach ($recommendations as $recommendation_id) {
                        execute($sql, [
                            $visit_id, $indicator_id, $score, $recommendation_id, $custom_recommendation
                        ]);
                    }
                }
            }
        }
        
        // توجيه المستخدم إلى الصفحة الرئيسية مع رسالة نجاح
        $_SESSION['success_message'] = "تم حفظ تقييم الزيارة الصفية بنجاح!";
        header('Location: index.php');
        exit;
        
    } catch (Exception $e) {
        // إلغاء المعاملة في حالة حدوث خطأ
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // تخزين رسالة الخطأ لعرضها للمستخدم
        $error_message = "حدث خطأ أثناء حفظ التقييم: " . $e->getMessage();
    }
}

// تضمين ملف رأس الصفحة
require_once 'includes/header.php';

// جلب البيانات الأساسية من قاعدة البيانات
try {
    // الحصول على معلومات المدرسة (نستخدم أول مدرسة)
    $school = query_row("SELECT * FROM schools LIMIT 1");
    if (!$school) {
        throw new Exception("لم يتم العثور على بيانات المدرسة. يرجى إعداد المدرسة أولاً.");
    }
    $school_id = $school['id'];
    
    // جلب الصفوف مباشرة بدون الاعتماد على المرحلة
    $grades = query("SELECT g.*, e.id as level_id FROM grades g JOIN educational_levels e ON g.level_id = e.id ORDER BY e.id, g.id");
    
    $domains = query("SELECT * FROM evaluation_domains ORDER BY id");
    $visitor_types = query("SELECT * FROM visitor_types ORDER BY name");
    
    // جلب المواد الدراسية للمدرسة
    $subjects = query("SELECT * FROM subjects WHERE school_id = ? OR school_id IS NULL ORDER BY name", [$school_id]);
} catch (PDOException $e) {
    // تعامل مع أي أخطاء في قاعدة البيانات
    $error_message = "حدث خطأ أثناء جلب البيانات. يرجى المحاولة مرة أخرى لاحقاً.";
}
?>

<!-- نموذج اختيار المدرسة والمادة والمعلم -->
<div id="selection-form" class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">نموذج تقييم زيارة صفية</h1>
    
    <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border-r-4 border-red-500 text-red-700 p-4 mb-6 rounded-md">
            <?= $error_message ?>
        </div>
    <?php endif; ?>
    
    <form action="evaluation_form.php" method="post" id="visit-form">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <!-- سطر نوع الزائر وتقييم المعمل -->
            <div class="flex items-center space-x-4 space-x-reverse col-span-2">
                <div class="flex-1">
                    <label class="block mb-2 font-semibold">نوع الزائر:</label>
                    <select id="visitor-type" name="visitor_type_id" class="w-full border p-2 rounded" required onchange="updateVisitorName()">
                        <option value="">اختر نوع الزائر...</option>
                        <?php foreach ($visitor_types as $type): ?>
                            <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div id="visitor-name" class="mt-2 text-sm text-gray-600"></div>
                    <input type="hidden" id="visitor-person-id" name="visitor_person_id" value="">
                </div>
                <div class="flex items-center mr-4 mt-8">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="has-lab" name="has_lab" class="form-checkbox h-5 w-5 text-blue-600">
                        <span class="mr-2 font-semibold">إضافة تقييم المعمل</span>
                    </label>
                </div>
            </div>

            <!-- نوع الزيارة وطريقة الحضور -->
            <div>
                <label class="block mb-2 font-semibold">نوع الزيارة:</label>
                <select id="visit-type" name="visit_type" class="w-full border p-2 rounded" required>
                    <option value="full">تقييم كلي</option>
                    <option value="partial">تقييم جزئي</option>
                </select>
            </div>

            <div>
                <label class="block mb-2 font-semibold">طريقة الحضور:</label>
                <select id="attendance-type" name="attendance_type" class="w-full border p-2 rounded" required>
                    <option value="physical">حضور</option>
                    <option value="remote">عن بعد</option>
                    <option value="hybrid">مدمج</option>
                </select>
            </div>

            <!-- حذف حقل المدرسة - سنستخدم المدرسة الافتراضية -->
            <input type="hidden" id="school" name="school_id" value="<?= $school_id ?>">

            <!-- حذف حقل المرحلة التعليمية -->

            <!-- الصف (تحميل جميع الصفوف مباشرة) -->
            <div>
                <label class="block mb-2 font-semibold">الصف:</label>
                <select id="grade" name="grade_id" class="w-full border p-2 rounded" required onchange="loadSections(this.value)">
                    <option value="">اختر الصف...</option>
                    <?php foreach ($grades as $grade): ?>
                        <option value="<?= $grade['id'] ?>" data-level-id="<?= $grade['level_id'] ?>"><?= htmlspecialchars($grade['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- الشعبة (الجديدة) -->
            <div>
                <label class="block mb-2 font-semibold">الشعبة:</label>
                <select id="section" name="section_id" class="w-full border p-2 rounded" required>
                    <option value="">اختر الشعبة...</option>
                    <!-- سيتم تحديث هذه القائمة ديناميكياً بناءً على الصف المختار -->
                </select>
            </div>

            <!-- المادة -->
            <div>
                <label class="block mb-2 font-semibold">المادة:</label>
                <select id="subject" name="subject_id" class="w-full border p-2 rounded" required>
                    <option value="">اختر المادة...</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- المعلم -->
            <div>
                <label class="block mb-2 font-semibold">المعلم:</label>
                <select id="teacher" name="teacher_id" class="w-full border p-2 rounded" required>
                    <option value="">اختر المعلم...</option>
                    <!-- سيتم تحديث هذه القائمة ديناميكياً بناءً على المدرسة والمادة المختارة -->
                </select>
            </div>

            <!-- تاريخ الزيارة -->
            <div>
                <label class="block mb-2 font-semibold">تاريخ الزيارة:</label>
                <input type="date" id="visit-date" name="visit_date" class="w-full border p-2 rounded" required>
            </div>
        </div>

        <button type="button" id="start-evaluation-btn" class="bg-primary-600 text-white px-6 py-2 rounded hover:bg-primary-700 transition">بدء التقييم</button>
    </form>
</div>

<!-- نموذج التقييم (يظهر بعد اختيار بيانات المعلم والمدرسة) -->
<div id="evaluation-form" class="bg-white rounded-lg shadow-md p-6" style="display: none;">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">تقييم الزيارة الصفية</h1>
        <button id="back-to-selection" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">تغيير المعلم/المدرسة</button>
    </div>

    <div id="evaluation-header" class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6"></div>
    
    <!-- معلومات الزيارات السابقة -->
    <div id="previous-visits-info" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6" style="display: none;">
        <h2 class="text-lg font-bold text-blue-700 mb-3">معلومات الزيارات السابقة</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="font-semibold">عدد الزيارات السابقة: <span id="visits-count" class="font-normal">-</span></p>
                <p class="font-semibold">تاريخ آخر زيارة: <span id="last-visit-date" class="font-normal">-</span></p>
                <p class="font-semibold">نسبة آخر زيارة: <span id="last-visit-percentage" class="font-normal">-</span></p>
            </div>
            <div>
                <p class="font-semibold">الصف والشعبة: <span id="last-visit-class" class="font-normal">-</span></p>
            </div>
        </div>
        <div class="mt-3">
            <p class="font-semibold">ملاحظات الزيارة السابقة:</p>
            <div id="last-visit-notes" class="mt-2 p-3 bg-white rounded border border-blue-200 text-sm max-h-32 overflow-y-auto">-</div>
        </div>
    </div>

    <form id="evaluation-save-form" action="evaluation_form.php" method="post">
    <!-- أقسام التقييم -->
    <?php $step = 1; ?>
    <?php foreach ($domains as $domain): ?>
        <div id="step-<?= $step ?>" class="evaluation-section bg-white border border-gray-200 border-r-4 border-r-primary-600 rounded-lg p-6 mb-4 max-h-[70vh] overflow-y-auto scrollbar-thin" style="display: <?= $step === 1 ? 'block' : 'none' ?>;">
            <h2 class="text-xl font-bold text-primary-700 mb-6 pb-2 border-b border-gray-200"><?= htmlspecialchars($domain['name']) ?></h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <?php 
                try {
                    $indicators = get_indicators_by_domain($domain['id']);
                    foreach ($indicators as $indicator): 
                        // تحديد ما إذا كان المؤشر ينتمي إلى مجموعة مؤشرات المعمل (يبدأ من المؤشر 24 إلى 29)
                        $is_lab_indicator = ($indicator['id'] >= 24 && $indicator['id'] <= 29);
                        
                        // إضافة class جديد للتحكم بظهور مؤشرات المعمل
                        $lab_class = $is_lab_indicator ? 'lab-indicator' : '';
                ?>
                    <div class="indicator-block <?= $lab_class ?>">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500"><?= htmlspecialchars($indicator['name']) ?></label>
                        <select name="score_<?= $indicator['id'] ?>" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                        <?php 
                        try {
                            $recommendations = get_recommendations_by_indicator($indicator['id']);
                            if (count($recommendations) > 0): 
                        ?>
                            <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                <?php foreach ($recommendations as $rec): ?>
                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_<?= $indicator['id'] ?>[]" value="<?= $rec['id'] ?>" id="rec_<?= $rec['id'] ?>_<?= $indicator['id'] ?>" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_<?= $rec['id'] ?>_<?= $indicator['id'] ?>" class="mr-2 text-sm text-gray-700"><?= htmlspecialchars($rec['text']) ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php 
                            endif;
                        } catch (PDOException $e) {
                            // تعديل لعرض الخطأ ومعرفة المشكلة
                            echo '<p class="text-red-500 text-sm">خطأ في جلب التوصيات: ' . $e->getMessage() . '</p>';
                        }
                        ?>
                        
                        <input type="text" name="custom_recommend_<?= $indicator['id'] ?>" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                <?php 
                    endforeach;
                } catch (PDOException $e) {
                    echo '<p class="text-red-500">حدث خطأ أثناء جلب مؤشرات التقييم</p>';
                }
                ?>
            </div>
            
            <div class="flex justify-between mt-6">
                <?php if ($step > 1): ?>
                    <button type="button" class="prev-step bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600" data-step="<?= $step ?>">السابق</button>
                <?php else: ?>
                    <div></div>
                <?php endif; ?>
                
                <?php if ($step < count($domains)): ?>
                    <button type="button" class="next-step bg-primary-600 text-white px-4 py-2 rounded hover:bg-primary-700" data-step="<?= $step ?>">التالي</button>
                <?php else: ?>
                    <button type="button" class="show-final-result bg-primary-600 text-white px-4 py-2 rounded hover:bg-primary-700">عرض النتيجة النهائية</button>
                <?php endif; ?>
            </div>
        </div>
        <?php $step++; ?>
    <?php endforeach; ?>

    <!-- قسم الملاحظات والتوصيات -->
    <div id="step-<?= $step ?>" class="evaluation-section bg-white border border-gray-200 border-r-4 border-r-primary-600 rounded-lg p-6 mb-4" style="display: none;">
        <h2 class="text-xl font-bold text-primary-700 mb-6 pb-2 border-b border-gray-200">ملاحظات وتوصيات عامة</h2>
        
        <div class="space-y-6">
            <div>
                <label class="block mb-3 font-semibold text-gray-700">أوصي بـ:</label>
                <textarea id="recommendation-notes" name="recommendation_notes" class="w-full border-2 border-gray-300 p-4 rounded-lg h-32 resize-none" placeholder="أدخل التوصيات هنا..."></textarea>
            </div>
            
            <div>
                <label class="block mb-3 font-semibold text-gray-700">أشكر المعلم على:</label>
                <textarea id="appreciation-notes" name="appreciation_notes" class="w-full border-2 border-gray-300 p-4 rounded-lg h-32 resize-none" placeholder="أدخل نقاط الشكر والتقدير هنا..."></textarea>
            </div>
        </div>
        
        <div class="flex justify-between mt-6">
            <button type="button" class="prev-step bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600" data-step="<?= $step ?>">السابق</button>
            <button type="button" class="show-final-result bg-primary-600 text-white px-4 py-2 rounded hover:bg-primary-700">عرض النتيجة النهائية</button>
        </div>
    </div>
    <?php $step++; ?>

    <!-- قسم النتيجة النهائية -->
    <div id="step-<?= $step ?>" class="evaluation-section bg-white border border-gray-200 border-r-4 border-r-primary-600 rounded-lg p-6 mb-4" style="display: none;">
        <h2 class="text-xl font-bold text-primary-700 mb-6 pb-2 border-b border-gray-200">نتيجة التقييم النهائية</h2>
        
        <div class="grid grid-cols-1 gap-6">
            <div id="total-score" class="text-xl font-bold p-4 bg-gray-50 rounded-lg border border-gray-200"></div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-3 text-gray-700">نقاط القوة:</h3>
                    <ul id="strengths" class="list-disc list-inside space-y-2 text-gray-600"></ul>
                </div>
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-3 text-gray-700">نقاط تحتاج إلى تحسين:</h3>
                    <ul id="improvements" class="list-disc list-inside space-y-2 text-gray-600"></ul>
                </div>
            </div>

            <div id="recommendation-notes-display" class="bg-white p-4 rounded-lg border border-gray-200" style="display: none;">
                <h3 class="text-lg font-semibold mb-3 text-gray-700">أوصي بـ:</h3>
                <p class="text-gray-600 whitespace-pre-line"></p>
            </div>
            
            <div id="appreciation-notes-display" class="bg-white p-4 rounded-lg border border-gray-200" style="display: none;">
                <h3 class="text-lg font-semibold mb-3 text-gray-700">أشكر المعلم على:</h3>
                <p class="text-gray-600 whitespace-pre-line"></p>
            </div>
        </div>

        <div class="flex justify-between mt-6">
            <button type="button" class="prev-step bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600" data-step="<?= $step ?>">العودة</button>
            <button type="submit" name="save_visit" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">حفظ التقييم</button>
        </div>
    </div>
    
    <!-- حقول مخفية لتخزين البيانات الضرورية -->
    <input type="hidden" name="school_id" id="hidden-school-id">
    <input type="hidden" name="level_id" id="hidden-level-id">
    <input type="hidden" name="grade_id" id="hidden-grade-id">
    <input type="hidden" name="section_id" id="hidden-section-id">
    <input type="hidden" name="subject_id" id="hidden-subject-id">
    <input type="hidden" name="teacher_id" id="hidden-teacher-id">
    <input type="hidden" name="visitor_type_id" id="hidden-visitor-type-id">
    <input type="hidden" name="visitor_person_id" id="hidden-visitor-person-id">
    <input type="hidden" name="visit_date" id="hidden-visit-date">
    <input type="hidden" name="visit_type" id="hidden-visit-type">
    <input type="hidden" name="attendance_type" id="hidden-attendance-type">
    <input type="hidden" name="has_lab" id="hidden-has-lab" value="0">
    <input type="hidden" name="total_score" id="hidden-total-score">
    <input type="hidden" name="average_score" id="hidden-average-score">
    <input type="hidden" name="grade" id="hidden-grade">
    </form>
</div>

<script>
// متغيرات عامة للتقييم
let indicators = <?= json_encode($indicators ?? []) ?>;
let currentStep = 1;
let isPartialEvaluation = false;

// إضافة متغير للتحكم بظهور مؤشرات المعمل
let hasLab = false;

// قائمة المؤشرات التفصيلية (لاستخدامها عند عرض النتائج)
const indicatorsDetails = [
  "خطة الدرس متوفرة وبنودها مستكملة ومناسبة.",
  "أهداف التعلم مناسبة ودقيقة الصياغة وقابلة للقياس.",
  "أنشطة الدرس الرئيسة واضحة ومتدرجة ومرتبطة بالأهداف.",
  "أهداف التعلم معروضة ويتم مناقشتها .",
  "أنشطة التمهيد مفعلة بشكل مناسب.",
  "محتوى الدرس واضح والعرض منظّم ومترابط.",
  "طرائق التدريس وإستراتيجياته متنوعه وتتمحور حول الطالب.",
  "مصادر التعلم الرئيسة والمساندة موظّفة بصورة واضحة وسليمة.",
  "الوسائل التعليميّة والتكنولوجيا موظّفة بصورة مناسبة.",
  "الأسئلة الصفية ذات صياغة سليمة ومتدرجة ومثيرة للتفكير .",
  "المادة العلمية دقيقة و مناسبة.",
  "الكفايات الأساسية متضمنة في السياق المعرفي للدرس.",
  "القيم الأساسية متضمنة في السياق المعرفي للدرس.",
  "التكامل بين محاور المادة ومع المواد الأخرى يتم بشكل مناسب.",
  "الفروق الفردية بين الطلبة يتم مراعاتها.",
  "غلق الدرس يتم بشكل مناسب.",
  "أساليب التقويم ( القبلي والبنائي والختامي ) مناسبة ومتنوعة.",
  "التغذية الراجعة متنوعة ومستمرة",
  "أعمال الطلبة متابعة ومصححة بدقة ورقيًا وإلكترونيًا .",
  "البيئة الصفية إيجابية وآمنة وداعمة للتعلّم.",
  "إدارة أنشطة التعلّم والمشاركات الصّفيّة تتم بصورة منظمة.",
  "قوانين إدارة الصف وإدارة السلوك مفعّلة.",
  "الاستثمار الأمثل لزمن الحصة",
  "مدى صلاحية وتوافر الأدوات اللازمة لتنفيذ النشاط العملي.",
  "شرح إجراءات الأمن والسلامة المناسبة للتجربة ومتابعة تفعيلها.",
  "إعطاء تعليمات واضحة وسليمة لأداء النشاط العملي قبل وأثناء التنفيذ.",
  "تسجيل الطلبة للملاحظات والنتائج أثناء تنفيذ النشاط العملي.",
  "تقويم مهارات الطلبة أثناء تنفيذ النشاط العملي.",
  "تنويع أساليب تقديم التغذية الراجعة للطلبة لتنمية مهاراتهم."
];

// دالة التحقق من صحة النموذج
function validateForm() {
    const requiredFields = ['visitor-type', 'grade', 'section', 'subject', 'teacher', 'visit-date'];
    let isValid = true;

    requiredFields.forEach(field => {
        const element = document.getElementById(field);
        if (!element.value) {
            element.classList.add('border-red-500');
            isValid = false;
        } else {
            element.classList.remove('border-red-500');
        }
    });
    
    // التحقق من اختيار الزائر الشخصي
    const visitorPersonId = document.getElementById('visitor-person-id').value;
    const visitorPersonElement = document.getElementById('visitor-person');
    
    if (visitorPersonElement && !visitorPersonId) {
        visitorPersonElement.classList.add('border-red-500');
        isValid = false;
    } else if (visitorPersonElement) {
        visitorPersonElement.classList.remove('border-red-500');
    }

    if (!isValid) {
        alert('يرجى ملء جميع الحقول المطلوبة');
        return false;
    }

    return isValid;
}

// عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    // وضع التاريخ الحالي كقيمة افتراضية
    document.getElementById('visit-date').value = new Date().toISOString().split('T')[0];
    
    // أزرار التنقل بين خطوات التقييم
    document.querySelectorAll('.next-step').forEach(button => {
        button.addEventListener('click', function() {
            const step = parseInt(this.getAttribute('data-step'));
            showStep(step + 1);
        });
    });
    
    document.querySelectorAll('.prev-step').forEach(button => {
        button.addEventListener('click', function() {
            const step = parseInt(this.getAttribute('data-step'));
            showStep(step - 1);
        });
    });
    
    // زر العودة إلى اختيار المعلم والمدرسة
    document.getElementById('back-to-selection').addEventListener('click', function() {
        document.getElementById('selection-form').style.display = 'block';
        document.getElementById('evaluation-form').style.display = 'none';
    });
    
    // عند ضغط زر بدء التقييم
    document.getElementById('start-evaluation-btn').addEventListener('click', function(e) {
        e.preventDefault();
        if (validateForm()) {
            // نقل المعلومات من نموذج الاختيار إلى نموذج التقييم
            const schoolId = document.getElementById('school').value;
            const gradeId = document.getElementById('grade').value;
            const sectionId = document.getElementById('section').value;
            const levelId = document.querySelector(`#grade option[value="${gradeId}"]`).getAttribute('data-level-id');
            const subjectId = document.getElementById('subject').value;
            const teacherId = document.getElementById('teacher').value;
            const visitorTypeId = document.getElementById('visitor-type').value;
            const visitorPersonId = document.getElementById('visitor-person-id').value;
            const visitDate = document.getElementById('visit-date').value;
            const visitType = document.getElementById('visit-type').value;
            const attendanceType = document.getElementById('attendance-type').value;
            
            // نقل القيم إلى الحقول المخفية
            document.getElementById('hidden-school-id').value = schoolId;
            document.getElementById('hidden-level-id').value = levelId;
            document.getElementById('hidden-grade-id').value = gradeId;
            document.getElementById('hidden-section-id').value = sectionId;
            document.getElementById('hidden-subject-id').value = subjectId;
            document.getElementById('hidden-teacher-id').value = teacherId;
            document.getElementById('hidden-visitor-type-id').value = visitorTypeId;
            document.getElementById('hidden-visitor-person-id').value = visitorPersonId;
            document.getElementById('hidden-visit-date').value = visitDate;
            document.getElementById('hidden-visit-type').value = visitType;
            document.getElementById('hidden-attendance-type').value = attendanceType;
            
            // تحديث معلومات نوع التقييم
            isPartialEvaluation = (visitType === 'partial');

            // تحديث قيمة خيار المعمل
            hasLab = document.getElementById('has-lab').checked;
            document.getElementById('hidden-has-lab').value = hasLab ? '1' : '0';

            // التحكم بظهور مؤشرات المعمل
            toggleLabIndicators();
            
            // تحديث عنوان التقييم
            const schoolName = document.querySelector(`#school option[value="${schoolId}"]`)?.textContent || '';
            const gradeName = document.querySelector(`#grade option[value="${gradeId}"]`)?.textContent || '';
            const sectionName = document.querySelector(`#section option[value="${sectionId}"]`)?.textContent || '';
            const subjectName = document.querySelector(`#subject option[value="${subjectId}"]`)?.textContent || '';
            const teacherName = document.querySelector(`#teacher option[value="${teacherId}"]`)?.textContent || '';
            const visitTypeName = document.querySelector(`#visit-type option[value="${visitType}"]`)?.textContent || '';
            
            const headerHtml = `
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="font-semibold">المعلم: <span class="font-normal">${teacherName}</span></p>
                        <p class="font-semibold">المادة: <span class="font-normal">${subjectName}</span></p>
                    </div>
                    <div>
                        <p class="font-semibold">الصف: <span class="font-normal">${gradeName}</span></p>
                        <p class="font-semibold">الشعبة: <span class="font-normal">${sectionName}</span></p>
                    </div>
                    <div>
                        <p class="font-semibold">نوع التقييم: <span class="font-normal">${visitTypeName}</span></p>
                        <p class="font-semibold">تاريخ الزيارة: <span class="font-normal">${formatDate(visitDate)}</span></p>
                    </div>
                </div>
            `;
            document.getElementById('evaluation-header').innerHTML = headerHtml;
            
            // إخفاء نموذج الاختيار وإظهار نموذج التقييم
            document.getElementById('selection-form').style.display = 'none';
            document.getElementById('evaluation-form').style.display = 'block';
            
            // تحميل معلومات الزيارات السابقة
            loadPreviousVisitsInfo(teacherId, visitorPersonId);
            
            // إظهار الخطوة الأولى
            showStep(1);
        }
    });
    
    // زر عرض النتيجة النهائية
    document.querySelectorAll('.show-final-result').forEach(button => {
        button.addEventListener('click', function() {
            calculateAndShowFinalResult();
            showStep(document.querySelectorAll('.evaluation-section').length);
        });
    });
    
    // تعيين قيمة نوع التقييم
    document.getElementById('visit-type').addEventListener('change', function() {
        isPartialEvaluation = this.value === 'partial';
    });
    
    // استدعاء وظيفة تحديث اسم الزائر عند تغيير نوع الزائر
    document.getElementById('visitor-type').addEventListener('change', function() {
        updateVisitorName();
        
        // إذا تم اختيار مادة، قم بتحديث قائمة المعلمين
        const subjectId = document.getElementById('subject').value;
        const schoolId = document.getElementById('school').value;
        if (subjectId && schoolId) {
            loadTeachers(schoolId, subjectId);
        }
    });

    // عند تغيير حالة اختيار المعمل
    document.getElementById('has-lab').addEventListener('change', function() {
        hasLab = this.checked;
        document.getElementById('hidden-has-lab').value = hasLab ? '1' : '0';
        
        // التحكم بظهور مؤشرات المعمل
        toggleLabIndicators();
    });

    // مستمع لتغيير المادة لتحديث قائمة الزائرين والمعلمين
    document.getElementById('subject').addEventListener('change', function() {
        const schoolId = document.getElementById('school').value;
        const subjectId = this.value;
        
        // تحديث قائمة المعلمين
        if (schoolId && subjectId) {
            loadTeachers(schoolId, subjectId);
        }
        
        // إذا كان نوع الزائر محدد، نقوم بتحديث قائمة الزائرين
        if (document.getElementById('visitor-type').value) {
            updateVisitorName();
        }
    });
});

// دالة جلب الشعب حسب الصف
function loadSections(gradeId) {
    const sectionSelect = document.getElementById('section');
    sectionSelect.innerHTML = '<option value="">اختر الشعبة...</option>';
    
    if (!gradeId) return;
    
    // نحدد المرحلة التعليمية من الصف المحدد
    const gradeOption = document.querySelector(`#grade option[value="${gradeId}"]`);
    const levelId = gradeOption ? gradeOption.getAttribute('data-level-id') : null;
    
    // استعلام AJAX لجلب الشعب
    fetch(`api/get_sections.php?grade_id=${gradeId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.sections.length > 0) {
                data.sections.forEach(section => {
                    sectionSelect.add(new Option(section.name, section.id));
                });
            }
        })
        .catch(error => {
            console.error('Error loading sections:', error);
        });
}

// دالة تحديث اسم الزائر
function updateVisitorName() {
    const visitorTypeSelect = document.getElementById('visitor-type');
    const visitorNameDiv = document.getElementById('visitor-name');
    const visitorPersonIdInput = document.getElementById('visitor-person-id');
    const schoolId = document.getElementById('school').value;
    const subjectSelect = document.getElementById('subject');
    const subjectId = subjectSelect ? subjectSelect.value : '';
    const visitorPersonId = visitorPersonIdInput.value; // حفظ قيمة الزائر الحالي
    
    // تفريغ القيم الحالية
    visitorNameDiv.innerHTML = '';
    // لا نفرغ معرف الزائر لكي نحتفظ بالقيمة الحالية
    
    // لا نفعل شيئاً إذا كان نوع الزائر غير محدد
    if (!visitorTypeSelect.value) return;
    
    // إظهار مؤشر التحميل
    visitorNameDiv.innerHTML = '<span class="text-gray-500">جاري تحميل الأسماء...</span>';
    
    // طلب الحصول على أسماء الزائرين بناءً على النوع
    fetch(`api/get_visitor_name.php?visitor_type_id=${visitorTypeSelect.value}&school_id=${schoolId}&subject_id=${subjectId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.visitors.length > 0) {
                // إنشاء قائمة منسدلة للزائرين
                const select = document.createElement('select');
                select.id = 'visitor-person';
                select.className = 'w-full border p-2 rounded mt-2';
                select.required = true;
                
                // إضافة خيار فارغ
                const emptyOption = document.createElement('option');
                emptyOption.value = '';
                emptyOption.textContent = 'اختر اسم الزائر...';
                select.appendChild(emptyOption);
                
                // إضافة الزائرين إلى القائمة
                data.visitors.forEach(visitor => {
                    const option = document.createElement('option');
                    option.value = visitor.id;
                    option.textContent = visitor.name;
                    select.appendChild(option);
                });
                
                // إضافة القائمة إلى الصفحة
                visitorNameDiv.innerHTML = '';
                visitorNameDiv.appendChild(select);
                
                // استعادة الاختيار السابق إذا كان موجوداً
                if (visitorPersonId) {
                    select.value = visitorPersonId;
                }
                
                // إضافة مستمع لحدث تغيير الزائر
                select.addEventListener('change', function() {
                    visitorPersonIdInput.value = this.value;
                });
            } else {
                // إظهار رسالة في حالة عدم وجود زائرين
                visitorNameDiv.innerHTML = '<span class="text-red-500">لم يتم العثور على زائرين من هذا النوع</span>';
                
                if (data.message) {
                    // عرض رسالة الخطأ إن وجدت
                    console.error(data.message);
                }
            }
        })
        .catch(error => {
            console.error('Error loading visitor names:', error);
            visitorNameDiv.innerHTML = '<span class="text-red-500">حدث خطأ أثناء تحميل البيانات</span>';
        });
}

// دالة تحميل المعلمين المتوفرين
function loadTeachers(schoolId, subjectId) {
    const teacherSelect = document.getElementById('teacher');
    
    // إذا لم يتم اختيار مدرسة أو مادة، إفراغ القائمة
    if (!schoolId || !subjectId) {
        teacherSelect.innerHTML = '<option value="">اختر المعلم...</option>';
        return;
    }

    // الحصول على نوع الزائر ومعرف الشخص الزائر (إذا تم تحديدهما)
    const visitorTypeId = document.getElementById('visitor-type').value || '';
    const visitorPersonId = document.getElementById('visitor-person-id').value || '';
    
    // استرجاع المعلمين من API
    fetch(`api/get_teachers.php?subject_id=${subjectId}&school_id=${schoolId}&visitor_type_id=${visitorTypeId}&visitor_person_id=${visitorPersonId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.teachers && data.teachers.length > 0) {
                // إنشاء خيارات القائمة المنسدلة
                let options = '<option value="">اختر المعلم...</option>';
                data.teachers.forEach(teacher => {
                    options += `<option value="${teacher.id}">${teacher.name}</option>`;
                });
                
                teacherSelect.innerHTML = options;
            } else {
                teacherSelect.innerHTML = '<option value="">غير متاح</option>';
                
                // عرض رسالة الخطأ من API
                if (data.message) {
                    alert(data.message);
                }
            }
        })
        .catch(error => {
            console.error('Error loading teachers:', error);
            teacherSelect.innerHTML = '<option value="">حدث خطأ</option>';
        });
        

}

// دالة عرض خطوة محددة من التقييم
function showStep(step) {
    const totalSteps = document.querySelectorAll('.evaluation-section').length;
    
    for (let i = 1; i <= totalSteps; i++) {
        const el = document.getElementById(`step-${i}`);
        if (el) {
            el.style.display = (i === step) ? 'block' : 'none';
        }
    }
    
    currentStep = step;
    window.scrollTo(0, 0);
}

// دالة حساب وعرض النتيجة النهائية
function calculateAndShowFinalResult() {
    // تحضير مصفوفات لتخزين النتائج
    const strengths = [];
    const improvements = [];
    let totalScore = 0;
    let totalItems = 0;
    
    // جمع نتائج التقييم
    document.querySelectorAll('.indicator-block').forEach((block, index) => {
        // لا نحسب المؤشرات المخفية (المعمل عندما يكون غير مُفعّل)
        if (block.style.display === 'none') {
            return;
        }
        
        const scoreSelect = block.querySelector('select[name^="score_"]');
        const score = parseInt(scoreSelect.value);
        const indicatorLabel = block.querySelector('label').textContent;
        
        // إذا كان التقييم جزئياً، نحسب فقط العناصر التي تم تقييمها
        // ونستثني مؤشرات "لم يتم قياسه" (score = 0) من الحساب
        if (score > 0) {
            // تصنيف نقاط القوة والتحسين
            if (score >= 3) {
                strengths.push(indicatorLabel);
            } else {
                improvements.push(indicatorLabel);
            }
            
            // إضافة النقاط للمجموع
            totalScore += score;
            totalItems++;
        }
    });
    
    // حساب المتوسط
    const average = totalItems > 0 ? (totalScore / totalItems).toFixed(2) : 0;
    
    // تحديد التقدير
    const grade = getGrade(average);
    
    // تحديث الحقول المخفية
    document.getElementById('hidden-total-score').value = totalScore;
    document.getElementById('hidden-average-score').value = average;
    document.getElementById('hidden-grade').value = grade;
    
    // حساب النسبة المئوية
    const percentage = (average * 25).toFixed(2);
    
    // عرض النتيجة الإجمالية
    const evaluationType = isPartialEvaluation ? 'تقييم جزئي' : 'تقييم كلي';
    document.getElementById('total-score').textContent = 
        `${evaluationType}: النتيجة ${totalScore} من ${totalItems * 4} (المتوسط: ${average} - النسبة: ${percentage}%)`;
    
    // عرض نقاط القوة
    const strengthsList = document.getElementById('strengths');
    strengthsList.innerHTML = '';
    if (strengths.length > 0) {
        strengths.forEach(strength => {
            strengthsList.innerHTML += `<li>${strength}</li>`;
        });
    } else {
        strengthsList.innerHTML = '<li class="text-gray-500">لم يتم تحديد نقاط قوة</li>';
    }
    
    // عرض نقاط التحسين
    const improvementsList = document.getElementById('improvements');
    improvementsList.innerHTML = '';
    if (improvements.length > 0) {
        improvements.forEach(improvement => {
            improvementsList.innerHTML += `<li>${improvement}</li>`;
        });
    } else {
        improvementsList.innerHTML = '<li class="text-gray-500">لم يتم تحديد نقاط تحتاج إلى تحسين</li>';
    }
    
    // جمع التوصيات المختارة
    const recommendationBoxes = document.querySelectorAll('input[type="checkbox"][name^="recommend_"]:checked');
    let selectedRecommendations = [];
    recommendationBoxes.forEach(box => {
        const label = document.querySelector(`label[for="${box.id}"]`);
        if (label) {
            selectedRecommendations.push(label.textContent.trim());
        }
    });
    
    // إضافة التوصيات المخصصة
    const customRecommendInputs = document.querySelectorAll('input[name^="custom_recommend_"]');
    customRecommendInputs.forEach(input => {
        if (input.value.trim()) {
            selectedRecommendations.push(input.value.trim());
        }
    });
    
    // تحديث حقل التوصيات إذا كان فارغاً
    const recommendationNotes = document.getElementById('recommendation-notes');
    if (!recommendationNotes.value.trim() && selectedRecommendations.length > 0) {
        recommendationNotes.value = selectedRecommendations.join('\n\n');
    }
    
    // عرض التوصيات
    const recommendationNotesValue = recommendationNotes.value;
    const recommendationNotesDisplay = document.getElementById('recommendation-notes-display');
    
    if (recommendationNotesValue.trim()) {
        recommendationNotesDisplay.querySelector('p').textContent = recommendationNotesValue;
        recommendationNotesDisplay.style.display = 'block';
    } else {
        recommendationNotesDisplay.style.display = 'none';
    }
    
    // عرض نقاط الشكر
    const appreciationNotes = document.getElementById('appreciation-notes').value;
    const appreciationNotesDisplay = document.getElementById('appreciation-notes-display');
    
    if (appreciationNotes.trim()) {
        appreciationNotesDisplay.querySelector('p').textContent = appreciationNotes;
        appreciationNotesDisplay.style.display = 'block';
    } else {
        appreciationNotesDisplay.style.display = 'none';
    }
    
    // عرض قسم النتيجة النهائية
    showStep(document.querySelectorAll('.evaluation-section').length);
}

// دالة الحصول على التقدير بناءً على المتوسط
function getGrade(average) {
    if (average >= 3.6) return 'ممتاز';
    if (average >= 3.2) return 'جيد جدًا';
    if (average >= 2.6) return 'جيد';
    if (average >= 2.0) return 'مقبول';
    return 'يحتاج إلى تحسين';
}

// دالة تحميل معلومات الزيارات السابقة
function loadPreviousVisitsInfo(teacherId, visitorPersonId) {
    if (!teacherId || !visitorPersonId) return;
    
    // جلب معلومات الزيارات السابقة من خلال API
    fetch(`api/get_previous_visits.php?teacher_id=${teacherId}&visitor_person_id=${visitorPersonId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const visitsInfo = data.data;
                const previousVisitsDiv = document.getElementById('previous-visits-info');
                
                // تحديث عدد الزيارات
                document.getElementById('visits-count').textContent = visitsInfo.visits_count;
                
                // إذا كان هناك زيارة سابقة، نعرض تفاصيلها
                if (visitsInfo.last_visit) {
                    const lastVisit = visitsInfo.last_visit;
                    const visitDate = new Date(lastVisit.date).toLocaleDateString('ar-EG');
                    document.getElementById('last-visit-date').textContent = visitDate;
                    document.getElementById('last-visit-class').textContent = 
                        `${lastVisit.grade || '-'} / ${lastVisit.section || '-'}`;
                    
                    // إضافة نسبة الزيارة السابقة
                    if (lastVisit.average_score !== undefined) {
                        const percentage = lastVisit.average_score * 25; // تحويل المتوسط إلى نسبة مئوية (4=100%)
                        document.getElementById('last-visit-percentage').textContent = `${percentage.toFixed(2)}%`;
                    } else {
                        document.getElementById('last-visit-percentage').textContent = 'غير متوفر';
                    }
                    
                    // عرض الملاحظات العامة من الزيارة السابقة
                    const notesElement = document.getElementById('last-visit-notes');
                    if (lastVisit.notes) {
                        notesElement.textContent = lastVisit.notes;
                    } else {
                        notesElement.textContent = 'لا توجد ملاحظات مسجلة';
                    }
                    
                    // إظهار قسم معلومات الزيارات السابقة
                    previousVisitsDiv.style.display = 'block';
                } else if (visitsInfo.visits_count > 0) {
                    // إذا كان هناك زيارات سابقة لكن بدون تفاصيل
                    document.getElementById('last-visit-date').textContent = 'غير متوفر';
                    document.getElementById('last-visit-class').textContent = 'غير متوفر';
                    document.getElementById('last-visit-percentage').textContent = 'غير متوفر';
                    document.getElementById('last-visit-notes').textContent = 'غير متوفر';
                    previousVisitsDiv.style.display = 'block';
                } else {
                    // لا توجد زيارات سابقة
                    previousVisitsDiv.style.display = 'none';
                }
            } else {
                console.error('Error loading previous visits:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading previous visits:', error);
        });
}

// وظيفة للتحكم بظهور/إخفاء مؤشرات المعمل
function toggleLabIndicators() {
    const labIndicators = document.querySelectorAll('.lab-indicator');
    labIndicators.forEach(indicator => {
        indicator.style.display = hasLab ? 'block' : 'none';
    });
}

// دالة تنسيق التاريخ بشكل صحيح
function formatDate(dateStr) {
    if (!dateStr) return '';
    
    const date = new Date(dateStr);
    // تنسيق التاريخ بالعربية (يوم/شهر/سنة)
    return date.toLocaleDateString('ar-EG');
}
</script>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?> 