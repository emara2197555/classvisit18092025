<?php
/**
 * صفحة تقييم أداء المعلمين على نظام قطر للتعليم
 */

require_once 'includes/auth_functions.php';
require_once 'includes/functions.php';

// حماية الصفحة لمنسقي التعليم الإلكتروني
protect_page(['E-Learning Coordinator', 'Admin', 'Director', 'Academic Deputy']);

$page_title = 'تقييم أداء المعلمين على نظام قطر للتعليم';
$success_message = '';
$error_message = '';

// الحصول على السنة الدراسية الحالية
$current_year = get_active_academic_year();
$current_year_id = $current_year ? $current_year['id'] : 2; // استخدام السنة 2 كافتراضي

// جلب المعايير من قاعدة البيانات
$criteria = query("SELECT * FROM qatar_system_criteria WHERE is_active = 1 ORDER BY category, sort_order");
$criteria_by_category = [];
foreach ($criteria as $criterion) {
    $criteria_by_category[$criterion['category']][] = $criterion;
}

// معالجة النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $academic_year_id = $_POST['academic_year_id'] ?? $current_year_id;
    $term = $_POST['term'] ?? '';
    $teacher_id = $_POST['teacher_id'] ?? '';
    $subject_id = $_POST['subject_id'] ?? '';
    $evaluation_date = $_POST['evaluation_date'] ?? '';
    $strengths = trim($_POST['strengths'] ?? '');
    $improvement_areas = trim($_POST['improvement_areas'] ?? '');
    $recommendations = trim($_POST['recommendations'] ?? '');
    $follow_up_date = $_POST['follow_up_date'] ?? null;
    $notes = trim($_POST['notes'] ?? '');
    
    // التحقق من صحة البيانات
    $errors = [];
    
    if (empty($academic_year_id)) $errors[] = 'يرجى اختيار السنة الدراسية';
    if (empty($term)) $errors[] = 'يرجى اختيار الفصل الدراسي';
    if (empty($teacher_id)) $errors[] = 'يرجى اختيار المعلم';
    if (empty($subject_id)) $errors[] = 'يرجى اختيار المادة';
    if (empty($evaluation_date)) $errors[] = 'يرجى تحديد تاريخ التقييم';
    
    // التحقق من درجات المعايير
    $criteria_scores = [];
    $total_score = 0;
    $criteria_count = 0;
    
    foreach ($criteria as $criterion) {
        $score_key = 'criterion_' . $criterion['id'];
        $score = $_POST[$score_key] ?? '';
        
        if (empty($score) || !is_numeric($score) || $score < 1 || $score > 5) {
            $errors[] = 'يرجى إدخال درجة صحيحة (1-5) لجميع المعايير';
            break;
        }
        
        $criteria_scores[$criterion['id']] = (int)$score;
        $total_score += (int)$score;
        $criteria_count++;
    }
    
    if (empty($errors) && $criteria_count > 0) {
        // حساب المتوسط
        $average_score = $total_score / $criteria_count;
        
        // تحديد مستوى الأداء
        $performance_level = 'poor';
        if ($average_score >= 4.5) {
            $performance_level = 'excellent';
        } elseif ($average_score >= 4.0) {
            $performance_level = 'very_good';
        } elseif ($average_score >= 3.0) {
            $performance_level = 'good';
        } elseif ($average_score >= 2.0) {
            $performance_level = 'needs_improvement';
        }
        
        // التحقق من وجود تقييم مسبق
        $existing_evaluation = query_row("
            SELECT id FROM qatar_system_performance 
            WHERE teacher_id = ? AND subject_id = ? AND academic_year_id = ? AND term = ? AND evaluation_date = ?
        ", [$teacher_id, $subject_id, $academic_year_id, $term, $evaluation_date]);
        
        if ($existing_evaluation) {
            $errors[] = 'يوجد تقييم مسبق لهذا المعلم في نفس المادة والتاريخ';
        }
    }
    
    if (empty($errors)) {
        try {
            if (empty($follow_up_date)) $follow_up_date = null;
            
            $sql = "INSERT INTO qatar_system_performance 
                    (academic_year_id, term, teacher_id, subject_id, evaluation_date, 
                     evaluator_id, criteria_scores, total_score, performance_level,
                     strengths, improvement_areas, recommendations, follow_up_date, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $result = query($sql, [
                $academic_year_id, $term, $teacher_id, $subject_id, $evaluation_date,
                $_SESSION['user_id'], json_encode($criteria_scores), $average_score, $performance_level,
                $strengths, $improvement_areas, $recommendations, $follow_up_date, $notes
            ]);
            
            // التحقق من نجاح العملية
            global $pdo;
            if ($pdo->lastInsertId()) {
                $success_message = 'تم حفظ التقييم بنجاح';
                // إعادة تعيين المتغيرات
                $_POST = [];
            } else {
                $error_message = 'حدث خطأ أثناء حفظ التقييم';
            }
        } catch (Exception $e) {
            $error_message = 'حدث خطأ في قاعدة البيانات: ' . $e->getMessage();
            // إضافة تفاصيل أكثر للتشخيص
            error_log("خطأ في حفظ التقييم: " . $e->getMessage());
            error_log("البيانات المرسلة: " . json_encode([
                $academic_year_id, $term, $teacher_id, $subject_id, $evaluation_date,
                $_SESSION['user_id'], json_encode($criteria_scores), $average_score, $performance_level,
                $strengths, $improvement_areas, $recommendations, $follow_up_date, $notes
            ]));
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

<?php include 'includes/elearning_header.php'; ?>

<style>
        .score-radio-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        
        .score-radio-option {
            position: relative;
            cursor: pointer;
        }
        
        .score-radio-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }
        
        .score-radio-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            background-color: white;
            transition: all 0.3s ease;
            min-width: 80px;
            position: relative;
        }
        
        .score-radio-label:hover {
            border-color: #3b82f6;
            background-color: #f8fafc;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .score-radio-option input[type="radio"]:checked + .score-radio-label {
            border-color: #3b82f6;
            background-color: #eff6ff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .score-radio-option input[type="radio"]:checked + .score-radio-label.score-1 {
            border-color: #ef4444;
            background-color: #fef2f2;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }
        
        .score-radio-option input[type="radio"]:checked + .score-radio-label.score-2 {
            border-color: #f97316;
            background-color: #fff7ed;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }
        
        .score-radio-option input[type="radio"]:checked + .score-radio-label.score-3 {
            border-color: #eab308;
            background-color: #fefce8;
            box-shadow: 0 0 0 3px rgba(234, 179, 8, 0.1);
        }
        
        .score-radio-option input[type="radio"]:checked + .score-radio-label.score-4 {
            border-color: #22c55e;
            background-color: #f0fdf4;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
        }
        
        .score-radio-option input[type="radio"]:checked + .score-radio-label.score-5 {
            border-color: #06b6d4;
            background-color: #ecfeff;
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
        }
        
        .score-number {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }
        
        .score-text {
            font-size: 0.75rem;
            text-align: center;
            font-weight: 500;
        }
        
        .score-1 .score-number { color: #ef4444; }
        .score-2 .score-number { color: #f97316; }
        .score-3 .score-number { color: #eab308; }
        .score-4 .score-number { color: #22c55e; }
        .score-5 .score-number { color: #06b6d4; }
        
        .criterion-card {
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        .criterion-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border-color: #d1d5db;
        }
        
        .criterion-card.filled {
            border-color: #3b82f6;
            background-color: #f8fafc;
        }
        
        .score-radio-label.selected {
            transform: scale(1.05);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        #progressContainer {
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.95);
        }
        
        .criterion-card.highlighted {
            animation: highlight 0.5s ease-in-out;
        }
        
        @keyframes highlight {
            0% { background-color: #fef2f2; border-color: #f87171; }
            50% { background-color: #fef2f2; border-color: #ef4444; }
            100% { background-color: white; border-color: #e5e7eb; }
        }
        
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 640px) {
            .score-radio-group {
                gap: 0.5rem;
            }
            
            .score-radio-label {
                min-width: 60px;
                padding: 0.75rem;
            }
            
            .score-number {
                font-size: 1.25rem;
            }
            
            .score-text {
                font-size: 0.625rem;
            }
        }
    </style>

<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-6xl mx-auto px-4">
        <!-- العنوان -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-star ml-2"></i>
                تقييم أداء المعلمين على نظام قطر للتعليم
            </h1>
            <nav class="text-sm text-gray-600">
                <a href="elearning_coordinator_dashboard.php" class="hover:text-blue-600">لوحة التحكم</a>
                <span class="mx-2">/</span>
                <span>تقييم أداء المعلمين</span>
            </nav>
        </div>

        <!-- الرسائل -->
        <?php if ($success_message): ?>
            <?= show_alert($success_message, 'success') ?>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <?= show_alert($error_message, 'error') ?>
        <?php endif; ?>

        <!-- مقياس التقييم -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <h3 class="text-lg font-bold text-blue-800 mb-3">مقياس التقييم:</h3>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 text-sm">
                <div class="text-center">
                    <div class="bg-red-100 text-red-800 font-bold py-2 rounded">1</div>
                    <p class="mt-1">الأدلة غير متوفرة أو محدودة</p>
                </div>
                <div class="text-center">
                    <div class="bg-orange-100 text-orange-800 font-bold py-2 rounded">2</div>
                    <p class="mt-1">تتوفر بعض الأدلة</p>
                </div>
                <div class="text-center">
                    <div class="bg-yellow-100 text-yellow-800 font-bold py-2 rounded">3</div>
                    <p class="mt-1">تتوفر معظم الأدلة</p>
                </div>
                <div class="text-center">
                    <div class="bg-green-100 text-green-800 font-bold py-2 rounded">4</div>
                    <p class="mt-1">الأدلة مستكملة وفاعلة</p>
                </div>
                <div class="text-center">
                    <div class="bg-blue-100 text-blue-800 font-bold py-2 rounded">5</div>
                    <p class="mt-1">ممتاز ومتقن</p>
                </div>
            </div>
        </div>

        <!-- النموذج -->
        <form method="POST" class="space-y-6">
            <!-- المعلومات الأساسية -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">المعلومات الأساسية</h2>
                
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">تاريخ التقييم *</label>
                        <input type="date" name="evaluation_date" required 
                               value="<?= htmlspecialchars($_POST['evaluation_date'] ?? date('Y-m-d')) ?>"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
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
                            <option value="">اختر المادة أولاً</option>
                        </select>
                        <div id="teacher_loading" class="hidden mt-2 text-sm text-gray-600">
                            <i class="fas fa-spinner fa-spin ml-1"></i>
                            جاري تحميل المعلمين...
                        </div>
                    </div>
                </div>
            </div>

            <!-- معايير بناء الدروس -->
            <?php if (isset($criteria_by_category['lesson_building'])): ?>
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-book-open ml-2 text-blue-600"></i>
                    معايير بناء الدروس على نظام قطر للتعليم
                </h2>
                
                <div class="space-y-4">
                    <?php foreach ($criteria_by_category['lesson_building'] as $criterion): ?>
                        <div class="criterion-card border border-gray-200 rounded-lg p-4">
                            <h4 class="font-medium text-gray-800 mb-2"><?= htmlspecialchars($criterion['criterion_name']) ?></h4>
                            <p class="text-sm text-gray-600 mb-3"><?= htmlspecialchars($criterion['description']) ?></p>
                            
                            <div class="score-radio-group">
                                <?php 
                                $score_labels = [
                                    1 => 'غير متوفرة',
                                    2 => 'بعض الأدلة', 
                                    3 => 'معظم الأدلة',
                                    4 => 'مستكملة وفاعلة',
                                    5 => 'ممتاز ومتقن'
                                ];
                                ?>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <div class="score-radio-option">
                                        <input type="radio" 
                                               name="criterion_<?= $criterion['id'] ?>" 
                                               value="<?= $i ?>" 
                                               id="criterion_<?= $criterion['id'] ?>_<?= $i ?>"
                                               class="score-radio" 
                                               required>
                                        <label for="criterion_<?= $criterion['id'] ?>_<?= $i ?>" 
                                               class="score-radio-label score-<?= $i ?>">
                                            <div class="score-number"><?= $i ?></div>
                                            <div class="score-text"><?= $score_labels[$i] ?></div>
                                        </label>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- معايير إسناد التقييمات -->
            <?php if (isset($criteria_by_category['assessment_management'])): ?>
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-tasks ml-2 text-green-600"></i>
                    معايير إسناد التقييمات على نظام قطر للتعليم
                </h2>
                
                <div class="space-y-4">
                    <?php foreach ($criteria_by_category['assessment_management'] as $criterion): ?>
                        <div class="criterion-card border border-gray-200 rounded-lg p-4">
                            <h4 class="font-medium text-gray-800 mb-2"><?= htmlspecialchars($criterion['criterion_name']) ?></h4>
                            <p class="text-sm text-gray-600 mb-3"><?= htmlspecialchars($criterion['description']) ?></p>
                            
                            <div class="score-radio-group">
                                <?php 
                                $score_labels = [
                                    1 => 'غير متوفرة',
                                    2 => 'بعض الأدلة', 
                                    3 => 'معظم الأدلة',
                                    4 => 'مستكملة وفاعلة',
                                    5 => 'ممتاز ومتقن'
                                ];
                                ?>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <div class="score-radio-option">
                                        <input type="radio" 
                                               name="criterion_<?= $criterion['id'] ?>" 
                                               value="<?= $i ?>" 
                                               id="criterion_<?= $criterion['id'] ?>_<?= $i ?>"
                                               class="score-radio" 
                                               required>
                                        <label for="criterion_<?= $criterion['id'] ?>_<?= $i ?>" 
                                               class="score-radio-label score-<?= $i ?>">
                                            <div class="score-number"><?= $i ?></div>
                                            <div class="score-text"><?= $score_labels[$i] ?></div>
                                        </label>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- التقييم النوعي -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">التقييم النوعي</h2>
                
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">نقاط القوة</label>
                        <textarea name="strengths" rows="4" 
                                  placeholder="اذكر نقاط القوة في أداء المعلم على نظام قطر للتعليم..."
                                  class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($_POST['strengths'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">جوانب تحتاج للتحسين</label>
                        <textarea name="improvement_areas" rows="4" 
                                  placeholder="اذكر الجوانب التي تحتاج للتحسين في استخدام نظام قطر للتعليم..."
                                  class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($_POST['improvement_areas'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">التوصيات</label>
                        <textarea name="recommendations" rows="4" 
                                  placeholder="اذكر التوصيات لتطوير أداء المعلم على نظام قطر للتعليم..."
                                  class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($_POST['recommendations'] ?? '') ?></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">تاريخ المتابعة المقترح</label>
                            <input type="date" name="follow_up_date" 
                                   value="<?= htmlspecialchars($_POST['follow_up_date'] ?? '') ?>"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ملاحظات إضافية</label>
                            <textarea name="notes" rows="3" 
                                      placeholder="أي ملاحظات أخرى..."
                                      class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                        </div>
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
                    حفظ التقييم
                </button>
            </div>
        </form>
    </div>
</div>

    <script>
        // تحديث المظهر البصري عند اختيار الدرجة
        document.addEventListener('DOMContentLoaded', function() {
            const radioButtons = document.querySelectorAll('input[type="radio"]');
            
            radioButtons.forEach(radio => {
                radio.addEventListener('change', function() {
                    const criterionCard = this.closest('.criterion-card');
                    const allRadios = criterionCard.querySelectorAll('input[type="radio"]');
                    
                    // إعادة تعيين حالة جميع التسميات
                    allRadios.forEach(r => {
                        const label = r.nextElementSibling;
                        label.classList.remove('selected');
                    });
                    
                    // تمييز التسمية المختارة
                    if (this.checked) {
                        this.nextElementSibling.classList.add('selected');
                        criterionCard.classList.add('filled');
                        
                        // إضافة تأثير بصري ناعم
                        criterionCard.style.transform = 'scale(1.02)';
                        setTimeout(() => {
                            criterionCard.style.transform = 'scale(1)';
                        }, 200);
                    }
                });
            });
            
            // إضافة تأثيرات التمرير
            const criterionCards = document.querySelectorAll('.criterion-card');
            criterionCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transition = 'all 0.3s ease';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transition = 'all 0.3s ease';
                });
            });
            
            // تحقق من إكمال النموذج
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const totalCriteria = document.querySelectorAll('.criterion-card').length;
                    const completedCriteria = document.querySelectorAll('.criterion-card.filled').length;
                    
                    if (completedCriteria < totalCriteria) {
                        e.preventDefault();
                        alert('يرجى إكمال تقييم جميع المعايير قبل الحفظ');
                        
                        // التمرير إلى أول معيار غير مكتمل
                        const firstIncomplete = document.querySelector('.criterion-card:not(.filled)');
                        if (firstIncomplete) {
                            firstIncomplete.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            firstIncomplete.style.border = '2px solid #ef4444';
                            setTimeout(() => {
                                firstIncomplete.style.border = '';
                            }, 2000);
                        }
                    }
                });
            }
            
            // إضافة شريط التقدم
            updateProgressBar();
            
            // تحديث شريط التقدم عند تغيير أي اختيار
            radioButtons.forEach(radio => {
                radio.addEventListener('change', updateProgressBar);
            });
            
            // إضافة مستمع لتحميل المعلمين عند تغيير المادة
            const subjectSelect = document.getElementById('subject_id');
            const teacherSelect = document.getElementById('teacher_id');
            const teacherLoading = document.getElementById('teacher_loading');
            
            if (subjectSelect && teacherSelect) {
                subjectSelect.addEventListener('change', function() {
                    const subjectId = this.value;
                    
                    if (!subjectId) {
                        teacherSelect.innerHTML = '<option value="">اختر المادة أولاً</option>';
                        teacherSelect.disabled = true;
                        return;
                    }
                    
                    // إظهار مؤشر التحميل
                    teacherLoading.classList.remove('hidden');
                    teacherSelect.disabled = true;
                    teacherSelect.innerHTML = '<option value="">جاري التحميل...</option>';
                    
                    // طلب AJAX لجلب المعلمين
                    fetch('api/get_teachers_by_subject.php?subject_id=' + subjectId)
                        .then(response => response.json())
                        .then(data => {
                            teacherLoading.classList.add('hidden');
                            teacherSelect.disabled = false;
                            
                            if (data.success) {
                                teacherSelect.innerHTML = '<option value="">اختر المعلم</option>';
                                data.teachers.forEach(teacher => {
                                    const option = document.createElement('option');
                                    option.value = teacher.id;
                                    option.textContent = teacher.name;
                                    teacherSelect.appendChild(option);
                                });
                                
                                if (data.teachers.length === 0) {
                                    teacherSelect.innerHTML = '<option value="">لا يوجد معلمون لهذه المادة</option>';
                                }
                            } else {
                                teacherSelect.innerHTML = '<option value="">خطأ في تحميل المعلمين</option>';
                                console.error('Error loading teachers:', data.message);
                            }
                        })
                        .catch(error => {
                            teacherLoading.classList.add('hidden');
                            teacherSelect.disabled = false;
                            teacherSelect.innerHTML = '<option value="">خطأ في تحميل المعلمين</option>';
                            console.error('Error:', error);
                        });
                });
                
                // تحميل المعلمين إذا كانت المادة محددة مسبقاً
                if (subjectSelect.value) {
                    subjectSelect.dispatchEvent(new Event('change'));
                }
            }
        });
        
        function updateProgressBar() {
            const totalCriteria = document.querySelectorAll('.criterion-card').length;
            const completedCriteria = document.querySelectorAll('.criterion-card.filled').length;
            const percentage = totalCriteria > 0 ? (completedCriteria / totalCriteria) * 100 : 0;
            
            // إنشاء شريط التقدم إذا لم يكن موجوداً
            let progressContainer = document.getElementById('progressContainer');
            if (!progressContainer) {
                progressContainer = document.createElement('div');
                progressContainer.id = 'progressContainer';
                progressContainer.className = 'fixed top-16 left-4 right-4 z-50 bg-white shadow-lg rounded-lg p-4';
                progressContainer.innerHTML = `
                    <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                        <span>تقدم التقييم</span>
                        <span id="progressText">${completedCriteria}/${totalCriteria}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div id="progressBar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                `;
                document.body.appendChild(progressContainer);
            }
            
            // تحديث شريط التقدم
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');
            
            if (progressBar && progressText) {
                progressBar.style.width = percentage + '%';
                progressText.textContent = `${completedCriteria}/${totalCriteria}`;
                
                // تغيير لون الشريط حسب التقدم
                if (percentage === 100) {
                    progressBar.className = 'bg-green-600 h-2 rounded-full transition-all duration-300';
                } else if (percentage >= 50) {
                    progressBar.className = 'bg-blue-600 h-2 rounded-full transition-all duration-300';
                } else {
                    progressBar.className = 'bg-yellow-600 h-2 rounded-full transition-all duration-300';
                }
                
                // إخفاء شريط التقدم عند الانتهاء بعد فترة
                if (percentage === 100) {
                    setTimeout(() => {
                        progressContainer.style.transform = 'translateY(-100%)';
                        setTimeout(() => {
                            progressContainer.style.display = 'none';
                        }, 300);
                    }, 2000);
                } else {
                    progressContainer.style.display = 'block';
                    progressContainer.style.transform = 'translateY(0)';
                }
            }
        }
    </script>

<?php include 'includes/elearning_footer.php'; ?>
