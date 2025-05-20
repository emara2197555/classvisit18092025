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
                visitor_type_id, visitor_person_id, visit_date, academic_year_id, visit_type, attendance_type, has_lab, 
                general_notes, recommendation_notes, appreciation_notes, total_score, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
            )
        ";
        
        // جلب العام الدراسي
        $academic_year_id = $_POST['academic_year_id'] ?? null;
        if (!$academic_year_id) {
            // إذا لم يتم تحديد العام، نستخدم العام النشط
            $active_year = get_active_academic_year();
            $academic_year_id = $active_year ? $active_year['id'] : null;
        }
        
        execute($sql, [
            $school_id, $teacher_id, $subject_id, $grade_id, $section_id, $level_id,
            $visitor_type_id, $visitor_person_id, $visit_date, $academic_year_id, $visit_type, $attendance_type, $has_lab,
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
            <!-- نوع الزائر -->
            <div>
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

            <!-- العام الدراسي -->
            <div>
                <label class="block mb-2 font-semibold">العام الدراسي:</label>
                <select id="academic-year" name="academic_year_id" class="w-full border p-2 rounded" required>
                    <option value="">اختر العام الدراسي...</option>
                    <?php 
                    // جلب العام الدراسي النشط
                    $academic_years = query("SELECT id, name, is_active FROM academic_years ORDER BY is_active DESC, name DESC");
                    foreach ($academic_years as $year): 
                        $is_selected = $year['is_active'] ? 'selected' : '';
                    ?>
                        <option value="<?= $year['id'] ?>" <?= $is_selected ?>><?= htmlspecialchars($year['name']) ?> <?= $year['is_active'] ? '(نشط)' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- نوع الزيارة -->
            <div>
                <label class="block mb-2 font-semibold">نوع الزيارة:</label>
                <select id="visit-type" name="visit_type" class="w-full border p-2 rounded" required>
                    <option value="full">تقييم كلي</option>
                    <option value="partial">تقييم جزئي</option>
                </select>
            </div>

            <!-- طريقة الحضور -->
            <div>
                <label class="block mb-2 font-semibold">طريقة الحضور:</label>
                <select id="attendance-type" name="attendance_type" class="w-full border p-2 rounded" required>
                    <option value="physical">حضور</option>
                    <option value="remote">عن بعد</option>
                    <option value="hybrid">مدمج</option>
                </select>
            </div>

            <!-- المادة -->
            <div>
                <label class="block mb-2 font-semibold">المادة:</label>
                <select id="subject" name="subject_id" class="w-full border p-2 rounded" required onchange="loadTeachers()">
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

            <!-- الصف -->
            <div>
                <label class="block mb-2 font-semibold">الصف:</label>
                <select id="grade" name="grade_id" class="w-full border p-2 rounded" required onchange="loadSections(this.value)">
                    <option value="">اختر الصف...</option>
                    <?php foreach ($grades as $grade): ?>
                        <option value="<?= $grade['id'] ?>" data-level-id="<?= $grade['level_id'] ?>"><?= htmlspecialchars($grade['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- الشعبة -->
            <div>
                <label class="block mb-2 font-semibold">الشعبة:</label>
                <select id="section" name="section_id" class="w-full border p-2 rounded" required>
                    <option value="">اختر الشعبة...</option>
                    <!-- سيتم تحديث هذه القائمة ديناميكياً بناءً على الصف المختار -->
                </select>
            </div>

            <!-- تقييم المعمل -->
            <div class="flex items-center">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="has-lab" name="has_lab" class="form-checkbox h-5 w-5 text-blue-600">
                    <span class="mr-2 font-semibold">إضافة تقييم المعمل</span>
                </label>
            </div>

            <!-- تاريخ الزيارة -->
            <div>
                <label class="block mb-2 font-semibold">تاريخ الزيارة:</label>
                <input type="date" id="visit-date" name="visit_date" class="w-full border p-2 rounded" required>
            </div>

            <!-- حذف حقل المدرسة - سنستخدم المدرسة الافتراضية -->
            <input type="hidden" id="school" name="school_id" value="<?= $school_id ?>">
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
    <div id="previous-visits-info" class="mb-6 border border-gray-200 rounded-lg p-4">
        <h2 class="text-xl font-bold text-primary-700 mb-2 pb-2 border-b border-gray-200">معلومات الزيارات السابقة</h2>
        
        <div class="mb-4 text-sm bg-blue-50 p-3 rounded-lg text-blue-800 border border-blue-100">
            <p>هذه المعلومات تساعدك على متابعة تقدم المعلم ومعرفة التوصيات السابقة قبل إجراء التقييم الجديد</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <!-- عدد الزيارات ومتوسط الأداء -->
            <div class="bg-white border border-blue-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
                <h3 class="text-lg font-bold text-blue-800 mb-2">الزيارات السابقة</h3>
                <div class="mt-2">
                    <p class="flex justify-between items-center">
                        <span class="font-semibold text-gray-700">عدد الزيارات:</span>
                        <span id="visits-count" class="text-lg font-bold text-primary-700">-</span>
                    </p>
                    <p class="flex justify-between items-center mt-2">
                        <span class="font-semibold text-gray-700">متوسط الأداء (لكل الزائرين):</span>
                        <span id="average-performance-all" class="text-lg font-bold text-primary-700">-</span>
                    </p>
                    <p class="flex justify-between items-center mt-2">
                        <span class="font-semibold text-gray-700">متوسط الأداء (الزائر الحالي):</span>
                        <span id="average-performance-current" class="text-lg font-bold text-primary-700">-</span>
                    </p>
                </div>
            </div>
            
            <!-- تفاصيل آخر زيارة -->
            <div class="bg-white border border-green-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
                <h3 class="text-lg font-bold text-green-800 mb-2">آخر زيارة</h3>
                <div class="mt-2">
                    <p class="flex justify-between items-center">
                        <span class="font-semibold text-gray-700">التاريخ:</span>
                        <span id="last-visit-date" class="text-primary-700 font-bold">-</span>
                    </p>
                    <p class="flex justify-between items-center mt-2">
                        <span class="font-semibold text-gray-700">نسبة التقييم (الزائر الحالي):</span>
                        <span id="last-visit-current-percentage" class="text-lg font-bold text-primary-700">-</span>
                    </p>
                    <p class="flex justify-between items-center mt-2">
                        <span class="font-semibold text-gray-700">نسبة التقييم (آخر زائر):</span>
                        <span id="last-visit-any-percentage" class="text-lg font-bold text-primary-700">-</span>
                    </p>
                    <p class="flex justify-between items-center mt-2">
                        <span class="font-semibold text-gray-700">الصف/الشعبة:</span>
                        <span id="last-visit-class" class="text-primary-700">-</span>
                    </p>
                </div>
            </div>
            
            <!-- توصيات آخر زيارة -->
            <div class="bg-white border border-yellow-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
                <h3 class="text-lg font-bold text-yellow-800 mb-2">توصيات آخر زيارة</h3>
                <div id="last-visit-recommendations" class="mt-2 max-h-32 overflow-y-auto">
                    <div id="last-recommendation-notes" class="p-2 bg-gray-50 rounded-lg text-sm mb-2">
                        <span class="font-semibold text-gray-700 block mb-1">أنصح المعلم:</span>
                        <p class="text-gray-800">-</p>
                    </div>
                    <div id="last-appreciation-notes" class="p-2 bg-gray-50 rounded-lg text-sm">
                        <span class="font-semibold text-gray-700 block mb-1">أشكر المعلم على:</span>
                        <p class="text-gray-800">-</p>
                    </div>
                </div>
            </div>
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
                
                <button type="button" class="go-to-notes bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600" data-notes-step="<?= count($domains) + 1 ?>">ملاحظات وتوصيات</button>
                
                <?php if ($step < count($domains)): ?>
                    <button type="button" class="next-step bg-primary-600 text-white px-4 py-2 rounded hover:bg-primary-700" data-step="<?= $step ?>">التالي</button>
                <?php else: ?>
                    <button type="button" class="notes-to-final-result bg-primary-600 text-white px-4 py-2 rounded hover:bg-primary-700">عرض النتيجة النهائية</button>
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
            <button type="button" class="notes-to-final-result bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">عرض النتيجة النهائية وحفظ التقييم</button>
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
    <input type="hidden" name="academic_year_id" id="hidden-academic-year-id">
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
    
    // عرض إشعار تلميحي للمستخدم
    const infoElement = document.createElement('div');
    infoElement.className = 'bg-blue-50 text-blue-800 p-4 rounded-lg border border-blue-200 mb-4';
    infoElement.innerHTML = `
        <p class="mb-2 font-semibold">ملاحظة هامة:</p>
        <ul class="list-disc list-inside text-sm">
            <li>ستظهر معلومات الزيارات السابقة للمعلم بعد بدء التقييم.</li>
            <li>يمكنك الاطلاع على توصيات الزيارات السابقة ونقاط التقدير قبل إجراء التقييم الجديد.</li>
        </ul>
    `;
    
    const selectionForm = document.getElementById('selection-form');
    selectionForm.insertBefore(infoElement, selectionForm.firstChild.nextSibling);
    
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
        // عند العودة إلى نموذج الاختيار، نخفي نموذج التقييم ونظهر نموذج الاختيار
        document.getElementById('selection-form').style.display = 'block';
        document.getElementById('evaluation-form').style.display = 'none';
        
        // تفريغ معلومات الزيارات السابقة
        document.getElementById('visits-count').textContent = '-';
        document.getElementById('average-performance-all').textContent = '-';
        document.getElementById('average-performance-current').textContent = '-';
        document.getElementById('last-visit-date').textContent = '-';
        document.getElementById('last-visit-class').textContent = '-';
        document.getElementById('last-visit-current-percentage').textContent = '-';
        document.getElementById('last-visit-any-percentage').textContent = '-';
        
        const recommendationElement = document.querySelector('#last-recommendation-notes p');
        if (recommendationElement) {
            recommendationElement.textContent = '-';
        }
        
        const appreciationElement = document.querySelector('#last-appreciation-notes p');
        if (appreciationElement) {
            appreciationElement.textContent = '-';
        }
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
            const academicYearId = document.getElementById('academic-year').value;
            
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
            document.getElementById('hidden-academic-year-id').value = academicYearId;
            
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
            
            // تأكد من أن قسم معلومات الزيارات السابقة ظاهر
            const previousVisitsDiv = document.getElementById('previous-visits-info');
            if (previousVisitsDiv) {
                previousVisitsDiv.style.display = 'block';
            }
        }
    });
    
    // زر عرض النتيجة النهائية - تعديل ليعرض حقلي التوصيات والشكر قبل النتيجة النهائية
    document.querySelectorAll('.notes-to-final-result').forEach(button => {
        button.addEventListener('click', function() {
            // هنا نعرض صفحة الملاحظات والتوصيات أولاً (قبل الأخيرة)
            const totalSteps = document.querySelectorAll('.evaluation-section').length;
            const notesStep = totalSteps - 1; // الخطوة قبل الأخيرة (ملاحظات وتوصيات)
            showStep(notesStep);
        });
    });
    
    // إضافة زر لعرض النتيجة النهائية من صفحة الملاحظات والتوصيات
    const finalResultButtons = document.querySelectorAll('.notes-to-final-result');
    finalResultButtons.forEach(button => {
        button.addEventListener('click', function() {
            calculateAndShowFinalResult();
            const totalSteps = document.querySelectorAll('.evaluation-section').length;
            showStep(totalSteps); // الخطوة الأخيرة (النتيجة النهائية)
        });
    });
    
    // تعيين قيمة نوع التقييم
    document.getElementById('visit-type').addEventListener('change', function() {
        isPartialEvaluation = this.value === 'partial';
    });
    
    // تحديث اسم الزائر عند اختيار نوع الزائر
    document.getElementById('visitor-type').addEventListener('change', updateVisitorName);

    // عند تغيير حالة اختيار المعمل
    document.getElementById('has-lab').addEventListener('change', function() {
        hasLab = this.checked;
        document.getElementById('hidden-has-lab').value = hasLab ? '1' : '0';
        
        // التحكم بظهور مؤشرات المعمل
        toggleLabIndicators();
    });

    // تحميل المعلمين عند اختيار المادة
    document.getElementById('subject').addEventListener('change', loadTeachers);

    // تحميل الشعب عند اختيار الصف
    document.getElementById('grade').addEventListener('change', function() {
        loadSections(this.value);
    });

    // إضافة مستمع أحداث للزر الملاحظات والتوصيات
    document.querySelectorAll('.go-to-notes').forEach(button => {
        button.addEventListener('click', function() {
            const notesStep = parseInt(this.getAttribute('data-notes-step'));
            showStep(notesStep);
        });
    });
});

// تحديث اسم الزائر عند اختيار نوع الزائر
function updateVisitorName() {
    const visitorTypeSelect = document.getElementById('visitor-type');
    const visitorNameDiv = document.getElementById('visitor-name');
    const visitorPersonIdInput = document.getElementById('visitor-person-id');
    const subjectSelect = document.getElementById('subject');
    
    if (visitorTypeSelect.value) {
        // إرسال طلب AJAX للحصول على قائمة الزوار حسب النوع المختار
        fetch(`includes/get_visitors.php?type_id=${visitorTypeSelect.value}`)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    // إنشاء قائمة منسدلة للزوار
                    let select = document.createElement('select');
                    select.id = 'visitor-person';
                    select.className = 'w-full border p-2 rounded mt-2';
                    select.required = true;
                    
                    let defaultOption = document.createElement('option');
                    defaultOption.value = '';
                    defaultOption.textContent = 'اختر الزائر...';
                    select.appendChild(defaultOption);
                    
                    // تحديد ما إذا كان نوع الزائر منسق أو موجه
                    const visitorTypeName = visitorTypeSelect.options[visitorTypeSelect.selectedIndex].text;
                    const isCoordinatorOrSupervisor = visitorTypeName === 'منسق المادة' || visitorTypeName === 'موجه المادة';
                    
                    data.forEach(visitor => {
                        let option = document.createElement('option');
                        option.value = visitor.id;
                        option.textContent = visitor.name;
                        
                        // إضافة معلومات المواد كخاصية للعنصر
                        if (isCoordinatorOrSupervisor && visitor.subjects) {
                            option.dataset.subjects = JSON.stringify(visitor.subjects);
                        }
                        
                        select.appendChild(option);
                    });
                    
                    // تحديث عنصر اسم الزائر
                    visitorNameDiv.innerHTML = '';
                    visitorNameDiv.appendChild(select);
                    
                    // تحديث معرف الزائر وإظهار المادة المناسبة عند الاختيار
                    select.addEventListener('change', function() {
                        visitorPersonIdInput.value = this.value;
                        
                        // إذا كان منسق أو موجه مادة، نقوم بتحديث قائمة المواد
                        if (isCoordinatorOrSupervisor && this.value) {
                            const selectedOption = this.options[this.selectedIndex];
                            if (selectedOption.dataset.subjects) {
                                const subjects = JSON.parse(selectedOption.dataset.subjects);
                                
                                if (subjects.length > 0) {
                                    // تعديل قائمة المواد لإظهار فقط المواد التي يشرف عليها المنسق/الموجه
                                    subjectSelect.innerHTML = '<option value="">اختر المادة...</option>';
                                    
                                    subjects.forEach(subject => {
                                        let option = document.createElement('option');
                                        option.value = subject.id;
                                        option.textContent = subject.name;
                                        subjectSelect.appendChild(option);
                                    });
                                    
                                    // إذا كان هناك مادة واحدة فقط، نختارها تلقائياً
                                    if (subjects.length === 1) {
                                        subjectSelect.value = subjects[0].id;
                                        // تحميل المعلمين المتعلقين بهذه المادة
                                        loadTeachers();
                                    }
                                }
                            }
                        }
                    });
                } else {
                    visitorNameDiv.innerHTML = '<p class="text-red-500">لا يوجد زوار من هذا النوع</p>';
                    visitorPersonIdInput.value = '';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                visitorNameDiv.innerHTML = '<p class="text-red-500">حدث خطأ في تحميل بيانات الزوار</p>';
                visitorPersonIdInput.value = '';
            });
    } else {
        visitorNameDiv.innerHTML = '';
        visitorPersonIdInput.value = '';
    }
}

// تحميل المعلمين عند اختيار المادة
function loadTeachers() {
    const subjectSelect = document.getElementById('subject');
    const teacherSelect = document.getElementById('teacher');
    const schoolId = document.getElementById('school').value;
    const visitorTypeSelect = document.getElementById('visitor-type');
    const visitorPersonSelect = document.getElementById('visitor-person');
    
    if (!subjectSelect.value || !schoolId) {
        teacherSelect.innerHTML = '<option value="">اختر المعلم...</option>';
        return;
    }
    
    // تحديد نوع الزائر
    const visitorTypeName = visitorTypeSelect.options[visitorTypeSelect.selectedIndex]?.text || '';
    const isCoordinator = visitorTypeName === 'منسق المادة';
    const isSupervisor = visitorTypeName === 'موجه المادة';
    
    // إرسال طلب AJAX للحصول على قائمة المعلمين حسب المادة والمدرسة
    let url = `includes/get_teachers.php?subject_id=${subjectSelect.value}&school_id=${schoolId}`;
    
    // إذا كان الزائر منسق المادة أو موجه المادة، نضيف معلومات إضافية للتصفية
    if (isCoordinator || isSupervisor) {
        url += `&visitor_type=${encodeURIComponent(visitorTypeName)}`;
        if (visitorPersonSelect && visitorPersonSelect.value) {
            url += `&exclude_visitor=${visitorPersonSelect.value}`;
        }
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            // إعادة تعيين قائمة المعلمين
            teacherSelect.innerHTML = '<option value="">اختر المعلم...</option>';
            
            // إذا كان الزائر موجه، نضيف منسق المادة في بداية القائمة
            if (isSupervisor && subjectSelect.value) {
                // جلب منسق المادة
                fetch(`includes/get_subject_coordinator.php?subject_id=${subjectSelect.value}&school_id=${schoolId}`)
                    .then(response => response.json())
                    .then(coordinators => {
                        if (coordinators.length > 0) {
                            // إضافة منسقي المادة إلى القائمة
                            coordinators.forEach(coord => {
                                let option = document.createElement('option');
                                option.value = coord.id;
                                option.textContent = coord.name + ' (منسق المادة)';
                                teacherSelect.appendChild(option);
                            });
                            
                            // إضافة فاصل بين منسقي المادة والمعلمين العاديين
                            if (data.length > 0) {
                                let separator = document.createElement('option');
                                separator.disabled = true;
                                separator.textContent = '---------------------';
                                teacherSelect.appendChild(separator);
                            }
                        }
                        
                        // إضافة المعلمين إلى القائمة
                        data.forEach(teacher => {
                            let option = document.createElement('option');
                            option.value = teacher.id;
                            option.textContent = teacher.name;
                            teacherSelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error loading coordinators:', error);
                        // إضافة المعلمين فقط في حالة فشل جلب المنسقين
                        data.forEach(teacher => {
                            let option = document.createElement('option');
                            option.value = teacher.id;
                            option.textContent = teacher.name;
                            teacherSelect.appendChild(option);
                        });
                    });
            } else {
                // إضافة المعلمين إلى القائمة
                data.forEach(teacher => {
                    let option = document.createElement('option');
                    option.value = teacher.id;
                    option.textContent = teacher.name;
                    teacherSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            teacherSelect.innerHTML = '<option value="">حدث خطأ في تحميل المعلمين</option>';
        });
}

// تحميل الشعب عند اختيار الصف
function loadSections(gradeId) {
    const sectionSelect = document.getElementById('section');
    const schoolId = document.getElementById('school').value;
    
    if (gradeId && schoolId) {
        // إرسال طلب AJAX للحصول على قائمة الشعب حسب الصف والمدرسة
        fetch(`includes/get_sections.php?grade_id=${gradeId}&school_id=${schoolId}`)
            .then(response => response.json())
            .then(data => {
                // إعادة تعيين قائمة الشعب
                sectionSelect.innerHTML = '<option value="">اختر الشعبة...</option>';
                
                // إضافة الشعب إلى القائمة
                data.forEach(section => {
                    let option = document.createElement('option');
                    option.value = section.id;
                    option.textContent = section.name;
                    sectionSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                sectionSelect.innerHTML = '<option value="">حدث خطأ في تحميل الشعب</option>';
            });
    } else {
        sectionSelect.innerHTML = '<option value="">اختر الشعبة...</option>';
    }
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
    
    // تأكد من أن قسم معلومات الزيارات السابقة ظاهر دائمًا
    const previousVisitsDiv = document.getElementById('previous-visits-info');
    if (previousVisitsDiv) {
        previousVisitsDiv.style.display = 'block';
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
    
    try {
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
        
        // العثور على عناصر عرض التوصيات والشكر
        const recommendationNotesDisplay = document.getElementById('recommendation-notes-display');
        const appreciationNotesDisplay = document.getElementById('appreciation-notes-display');
        
        if (!recommendationNotesDisplay || !appreciationNotesDisplay) {
            console.error('لم يتم العثور على عناصر عرض التوصيات أو الشكر');
            return;
        }
        
        // عرض التوصيات - دائماً نظهرها حتى لو كانت فارغة
        const recommendationParagraph = recommendationNotesDisplay.querySelector('p');
        if (recommendationParagraph) {
            recommendationParagraph.textContent = recommendationNotes.value || 'لم يتم إضافة توصيات';
            recommendationNotesDisplay.style.display = 'block';
        } else {
            console.error('لم يتم العثور على عنصر الفقرة لعرض التوصيات');
        }
        
        // عرض نقاط الشكر - دائماً نظهرها حتى لو كانت فارغة
        const appreciationNotes = document.getElementById('appreciation-notes');
        const appreciationParagraph = appreciationNotesDisplay.querySelector('p');
        if (appreciationParagraph && appreciationNotes) {
            appreciationParagraph.textContent = appreciationNotes.value || 'لم يتم إضافة نقاط شكر';
            appreciationNotesDisplay.style.display = 'block';
        } else {
            console.error('لم يتم العثور على عنصر الفقرة لعرض نقاط الشكر أو عنصر الإدخال');
        }
    } catch (error) {
        console.error('حدث خطأ أثناء حساب وعرض النتيجة النهائية:', error);
    }
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
    
    console.log('جاري تحميل معلومات الزيارات السابقة للمعلم ' + teacherId + ' والزائر ' + visitorPersonId);
    
    // إظهار قسم معلومات الزيارات السابقة بشكل افتراضي
    const previousVisitsDiv = document.getElementById('previous-visits-info');
    if (previousVisitsDiv) {
        previousVisitsDiv.style.display = 'block';
    }
    
    // جلب معلومات الزيارات السابقة من خلال API
    fetch(`api/get_previous_visits.php?teacher_id=${teacherId}&visitor_person_id=${visitorPersonId}`)
        .then(response => {
            console.log('تم استلام الرد من API');
            return response.json();
        })
        .then(data => {
            console.log('البيانات المستلمة من API:', data);
            
            if (data.success) {
                const visitsInfo = data.data;
                
                // تحديث عدد الزيارات
                const visitsCountElement = document.getElementById('visits-count');
                if (visitsCountElement) {
                    visitsCountElement.textContent = visitsInfo.visits_count || '0';
                }
                
                // تحديث متوسط الأداء العام لكل الزائرين
                const averagePerformanceAllElement = document.getElementById('average-performance-all');
                if (averagePerformanceAllElement) {
                    if (visitsInfo.average_performance_all !== undefined && visitsInfo.average_performance_all !== null) {
                        // تحويل المتوسط إلى نسبة مئوية - المتوسط هو بالفعل نسبة (0-1)
                        const avgPercentage = parseFloat((visitsInfo.average_performance_all * 100).toFixed(2));
                        console.log("متوسط الأداء لكل الزائرين (قيمة):", visitsInfo.average_performance_all);
                        console.log("متوسط الأداء لكل الزائرين (نسبة):", avgPercentage);
                        
                        averagePerformanceAllElement.textContent = `${avgPercentage}%`;
                    } else {
                        averagePerformanceAllElement.textContent = 'غير متوفر';
                    }
                }
                
                // تحديث متوسط الأداء للزائر الحالي
                const averagePerformanceCurrentElement = document.getElementById('average-performance-current');
                if (averagePerformanceCurrentElement) {
                    if (visitsInfo.average_performance_current_visitor !== undefined && visitsInfo.average_performance_current_visitor !== null) {
                        // تحويل المتوسط إلى نسبة مئوية - المتوسط هو بالفعل نسبة (0-1)
                        const avgPercentage = parseFloat((visitsInfo.average_performance_current_visitor * 100).toFixed(2));
                        console.log("متوسط الأداء للزائر الحالي (قيمة):", visitsInfo.average_performance_current_visitor);
                        console.log("متوسط الأداء للزائر الحالي (نسبة):", avgPercentage);
                        
                        averagePerformanceCurrentElement.textContent = `${avgPercentage}%`;
                    } else {
                        averagePerformanceCurrentElement.textContent = 'غير متوفر';
                    }
                }
                
                // إذا كان هناك زيارة سابقة للزائر الحالي، نعرض تفاصيلها
                const lastVisitCurrentVisitor = visitsInfo.last_visit_current_visitor;
                
                // تحديث تاريخ آخر زيارة
                const lastVisitDateElement = document.getElementById('last-visit-date');
                if (lastVisitDateElement) {
                    if (lastVisitCurrentVisitor && lastVisitCurrentVisitor.date) {
                        const visitDate = new Date(lastVisitCurrentVisitor.date).toLocaleDateString('ar-EG');
                        lastVisitDateElement.textContent = visitDate;
                    } else {
                        lastVisitDateElement.textContent = 'غير متوفر';
                    }
                }
                
                // تحديث الصف والشعبة
                const lastVisitClassElement = document.getElementById('last-visit-class');
                if (lastVisitClassElement) {
                    if (lastVisitCurrentVisitor) {
                        lastVisitClassElement.textContent = 
                            `${lastVisitCurrentVisitor.grade || '-'} / ${lastVisitCurrentVisitor.section || '-'}`;
                    } else {
                        lastVisitClassElement.textContent = 'غير متوفر';
                    }
                }
                
                // تحديث نسبة تقييم آخر زيارة للزائر الحالي
                const lastVisitCurrentPercentageElement = document.getElementById('last-visit-current-percentage');
                if (lastVisitCurrentPercentageElement) {
                    if (lastVisitCurrentVisitor && lastVisitCurrentVisitor.average_score !== undefined && lastVisitCurrentVisitor.average_score !== null) {
                        // تحويل المتوسط إلى نسبة مئوية
                        const percentage = parseFloat((lastVisitCurrentVisitor.average_score * 25).toFixed(2));
                        console.log("نسبة آخر زيارة للزائر الحالي (قيمة):", lastVisitCurrentVisitor.average_score);
                        console.log("نسبة آخر زيارة للزائر الحالي (نسبة):", percentage);
                        
                        lastVisitCurrentPercentageElement.textContent = `${percentage}%`;
                    } else {
                        lastVisitCurrentPercentageElement.textContent = 'غير متوفر';
                    }
                }
                
                // تحديث نسبة تقييم آخر زيارة لأي زائر
                const lastVisitAnyPercentageElement = document.getElementById('last-visit-any-percentage');
                if (lastVisitAnyPercentageElement) {
                    const lastVisitAnyVisitor = visitsInfo.last_visit_any_visitor;
                    if (lastVisitAnyVisitor && lastVisitAnyVisitor.average_score !== undefined && lastVisitAnyVisitor.average_score !== null) {
                        // تحويل المتوسط إلى نسبة مئوية
                        const percentage = parseFloat((lastVisitAnyVisitor.average_score * 25).toFixed(2));
                        console.log("نسبة آخر زيارة لأي زائر (قيمة):", lastVisitAnyVisitor.average_score);
                        console.log("نسبة آخر زيارة لأي زائر (نسبة):", percentage);
                        
                        lastVisitAnyPercentageElement.textContent = `${percentage}%`;
                        
                        // إضافة معلومات الزائر في tooltip
                        lastVisitAnyPercentageElement.title = `بواسطة: الزائر رقم ${lastVisitAnyVisitor.visitor_person_id || '-'} (${lastVisitAnyVisitor.visitor_type || '-'})`;
                    } else {
                        lastVisitAnyPercentageElement.textContent = 'غير متوفر';
                    }
                }
                
                // عرض توصيات المعلم من الزيارة السابقة
                const recommendationElement = document.querySelector('#last-recommendation-notes p');
                if (recommendationElement) {
                    if (lastVisitCurrentVisitor && lastVisitCurrentVisitor.recommendation_notes) {
                        recommendationElement.textContent = lastVisitCurrentVisitor.recommendation_notes;
                    } else {
                        recommendationElement.textContent = 'لا توجد توصيات مسجلة';
                    }
                }
                
                // عرض نقاط الشكر من الزيارة السابقة
                const appreciationElement = document.querySelector('#last-appreciation-notes p');
                if (appreciationElement) {
                    if (lastVisitCurrentVisitor && lastVisitCurrentVisitor.appreciation_notes) {
                        appreciationElement.textContent = lastVisitCurrentVisitor.appreciation_notes;
                    } else {
                        appreciationElement.textContent = 'لا توجد نقاط شكر مسجلة';
                    }
                }
                
            } else {
                console.error('خطأ في تحميل معلومات الزيارات السابقة:', data.message);
                
                // تعيين رسائل افتراضية في حالة حدوث خطأ
                document.getElementById('visits-count').textContent = '0';
                document.getElementById('average-performance-all').textContent = 'غير متوفر';
                document.getElementById('average-performance-current').textContent = 'غير متوفر';
                document.getElementById('last-visit-date').textContent = 'غير متوفر';
                document.getElementById('last-visit-class').textContent = 'غير متوفر';
                document.getElementById('last-visit-current-percentage').textContent = 'غير متوفر';
                document.getElementById('last-visit-any-percentage').textContent = 'غير متوفر';
                
                const recommendationElement = document.querySelector('#last-recommendation-notes p');
                if (recommendationElement) {
                    recommendationElement.textContent = 'لا توجد توصيات مسجلة';
                }
                
                const appreciationElement = document.querySelector('#last-appreciation-notes p');
                if (appreciationElement) {
                    appreciationElement.textContent = 'لا توجد نقاط شكر مسجلة';
                }
            }
        })
        .catch(error => {
            console.error('خطأ في تحميل معلومات الزيارات السابقة:', error);
            
            // تعيين رسائل افتراضية في حالة حدوث خطأ
            document.getElementById('visits-count').textContent = '0';
            document.getElementById('average-performance-all').textContent = 'غير متوفر';
            document.getElementById('average-performance-current').textContent = 'غير متوفر';
            document.getElementById('last-visit-date').textContent = 'غير متوفر';
            document.getElementById('last-visit-class').textContent = 'غير متوفر';
            document.getElementById('last-visit-current-percentage').textContent = 'غير متوفر';
            document.getElementById('last-visit-any-percentage').textContent = 'غير متوفر';
            
            const recommendationElement = document.querySelector('#last-recommendation-notes p');
            if (recommendationElement) {
                recommendationElement.textContent = 'لا توجد توصيات مسجلة';
            }
            
            const appreciationElement = document.querySelector('#last-appreciation-notes p');
            if (appreciationElement) {
                appreciationElement.textContent = 'لا توجد نقاط شكر مسجلة';
            }
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