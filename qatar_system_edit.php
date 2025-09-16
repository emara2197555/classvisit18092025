<?php
require_once 'includes/auth_functions.php';
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// حماية الصفحة
protect_page(['E-Learning Coordinator', 'Admin', 'Director', 'Academic Deputy']);

if (!isset($_GET['id'])) {
    header('Location: qatar_system_reports.php?error=معرف التقييم مفقود');
    exit;
}

$evaluation_id = (int)$_GET['id'];

// معالجة النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // تشخيص البيانات الواردة
        error_log("POST Data: " . json_encode($_POST));
        
        // التحقق من وجود الحقول المطلوبة
        if (empty($_POST['teacher_id']) || empty($_POST['subject_id']) || empty($_POST['academic_year_id']) || 
            empty($_POST['term']) || empty($_POST['evaluation_date'])) {
            $error = 'يرجى ملء جميع الحقول المطلوبة: teacher_id=' . ($_POST['teacher_id'] ?? 'فارغ') . 
                     ', subject_id=' . ($_POST['subject_id'] ?? 'فارغ') . 
                     ', academic_year_id=' . ($_POST['academic_year_id'] ?? 'فارغ') . 
                     ', term=' . ($_POST['term'] ?? 'فارغ') . 
                     ', evaluation_date=' . ($_POST['evaluation_date'] ?? 'فارغ');
        } else {
        $teacher_id = (int)$_POST['teacher_id'];
        $subject_id = (int)$_POST['subject_id'];
        $academic_year_id = (int)$_POST['academic_year_id'];
        $term = $_POST['term'];
        $evaluation_date = $_POST['evaluation_date'];
            $strengths = trim($_POST['strengths'] ?? '');
            $improvement_areas = trim($_POST['improvement_areas'] ?? '');
            $recommendations = trim($_POST['recommendations'] ?? '');
            $follow_up_date = $_POST['follow_up_date'] ?? null;
            $notes = trim($_POST['notes'] ?? '');
        }
        
        if (empty($error)) {
            // جلب المعايير
            $criteria = query("SELECT * FROM qatar_system_criteria WHERE is_active = 1 ORDER BY category, sort_order");
            error_log("عدد المعايير: " . count($criteria));
            
            // التحقق من درجات المعايير
            $criteria_scores = [];
            $total_score = 0;
            $criteria_count = 0;
            
            foreach ($criteria as $criterion) {
                $score_key = 'criterion_' . $criterion['id'];
                $score = $_POST[$score_key] ?? '';
                error_log("المعيار " . $criterion['id'] . " القيمة: " . $score);
                
                if (empty($score) || !is_numeric($score) || $score < 1 || $score > 5) {
                    $error = 'يرجى إدخال درجة صحيحة (1-5) لجميع المعايير - المعيار ' . $criterion['id'] . ' قيمته: ' . $score;
                    break;
                }
                
                $criteria_scores[$criterion['id']] = (int)$score;
                $total_score += (int)$score;
                $criteria_count++;
            }
            
                if (empty($error) && $criteria_count > 0) {
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

        // تحديث التقييم
                    $sql = "UPDATE qatar_system_performance 
            SET teacher_id = ?, subject_id = ?, academic_year_id = ?, term = ?, 
                                evaluation_date = ?, criteria_scores = ?, total_score = ?, 
                                performance_level = ?, strengths = ?, improvement_areas = ?, 
                                recommendations = ?, follow_up_date = ?, notes = ?
                            WHERE id = ?";
                    
                    $params = [
            $teacher_id, $subject_id, $academic_year_id, $term,
                        $evaluation_date, json_encode($criteria_scores), $average_score,
                        $performance_level, $strengths, $improvement_areas,
                        $recommendations, $follow_up_date, $notes, $evaluation_id
                    ];
                    
                    // إضافة تشخيص
                    error_log("SQL: " . $sql);
                    error_log("Params: " . json_encode($params));
                    error_log("معرف التقييم: " . $evaluation_id);
                    
                    // التحقق من وجود التقييم قبل التحديث
                    $check_eval = query("SELECT id, teacher_id, subject_id FROM qatar_system_performance WHERE id = ?", [$evaluation_id]);
                    error_log("التحقق من وجود التقييم: " . json_encode($check_eval));
                    
                    if (empty($check_eval)) {
                        $error = "التقييم غير موجود في قاعدة البيانات - معرف التقييم: " . $evaluation_id;
                    } else {
                        $updated = execute($sql, $params);
                        error_log("عدد الصفوف المتأثرة: " . $updated);
                    }

        if ($updated) {
            header('Location: qatar_system_view.php?id=' . $evaluation_id . '&success=تم تحديث التقييم بنجاح');
            exit;
        } else {
                        $error = "فشل في تحديث التقييم - لم يتم تحديث أي سجل. تحقق من وجود التقييم في قاعدة البيانات.";
                        error_log("فشل في تحديث التقييم - لم يتم تحديث أي سجل");
                        error_log("معرف التقييم: " . $evaluation_id);
                    }
                }
        }
    } catch (Exception $e) {
        $error = "خطأ في تحديث التقييم: " . $e->getMessage();
        // إضافة تفاصيل أكثر للتشخيص
        error_log("خطأ في تحديث التقييم: " . $e->getMessage());
        error_log("البيانات المرسلة: " . json_encode($_POST));
    }
}

try {
    // جلب بيانات التقييم الحالي
    $evaluation = query("
        SELECT qsp.*, t.name as teacher_name, s.name as subject_name
        FROM qatar_system_performance qsp
        JOIN teachers t ON qsp.teacher_id = t.id
        JOIN subjects s ON qsp.subject_id = s.id
        WHERE qsp.id = ?
    ", [$evaluation_id]);
    
    if (empty($evaluation)) {
        header('Location: qatar_system_reports.php?error=التقييم غير موجود');
        exit;
    }
    
    $eval = $evaluation[0];
    
    // جلب البيانات للنماذج
    $academic_years = query("SELECT * FROM academic_years ORDER BY name DESC");
    $subjects = query("SELECT * FROM subjects ORDER BY name");
    $teachers = query("SELECT * FROM teachers ORDER BY name");
    
    // جلب المعايير من قاعدة البيانات
    $criteria = query("SELECT * FROM qatar_system_criteria WHERE is_active = 1 ORDER BY category, sort_order");
    $criteria_by_category = [];
    foreach ($criteria as $criterion) {
        $criteria_by_category[$criterion['category']][] = $criterion;
    }
    
    // تحليل درجات المعايير المحفوظة
    $criteria_scores = json_decode($eval['criteria_scores'], true) ?? [];
    
    $page_title = 'تعديل تقييم نظام قطر - ' . $eval['teacher_name'];
    
} catch (Exception $e) {
    header('Location: qatar_system_reports.php?error=خطأ في جلب بيانات التقييم');
    exit;
}
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
    <div class="max-w-4xl mx-auto px-4">
        <!-- العنوان والتنقل -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-edit ml-2"></i>
                تعديل تقييم نظام قطر
            </h1>
            <nav class="text-sm text-gray-600">
                <a href="elearning_coordinator_dashboard.php" class="hover:text-blue-600">لوحة التحكم</a>
                <span class="mx-2">/</span>
                <a href="qatar_system_reports.php" class="hover:text-blue-600">تقارير نظام قطر</a>
                <span class="mx-2">/</span>
                <a href="qatar_system_view.php?id=<?= $eval['id'] ?>" class="hover:text-blue-600">عرض التقييم</a>
                <span class="mx-2">/</span>
                <span>تعديل</span>
            </nav>
        </div>

        <!-- رسائل الأخطاء -->
        <?php if (isset($error)): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-600 ml-2"></i>
                    <span class="text-red-800"><?= htmlspecialchars($error) ?></span>
                </div>
            </div>
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

        <!-- نموذج التعديل -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-800">تعديل بيانات التقييم</h2>
            </div>
            
            <form method="POST" class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- السنة الدراسية -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            السنة الدراسية <span class="text-red-500">*</span>
                        </label>
                        <select name="academic_year_id" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <?php foreach ($academic_years as $year): ?>
                                <option value="<?= $year['id'] ?>" 
                                        <?= $year['id'] == $eval['academic_year_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($year['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- الفصل الدراسي -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            الفصل الدراسي <span class="text-red-500">*</span>
                        </label>
                        <select name="term" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">اختر الفصل</option>
                            <option value="first" <?= $eval['term'] === 'first' ? 'selected' : '' ?>>الفصل الأول</option>
                            <option value="second" <?= $eval['term'] === 'second' ? 'selected' : '' ?>>الفصل الثاني</option>
                        </select>
                    </div>

                    <!-- تاريخ التقييم -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            تاريخ التقييم <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="evaluation_date" required
                               value="<?= date('Y-m-d', strtotime($eval['evaluation_date'])) ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- المادة -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            المادة <span class="text-red-500">*</span>
                        </label>
                        <select name="subject_id" id="subject_id" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">اختر المادة</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= $subject['id'] ?>" 
                                        <?= $subject['id'] == $eval['subject_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($subject['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- المعلم -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            المعلم <span class="text-red-500">*</span>
                        </label>
                        <select name="teacher_id" id="teacher_id" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
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
            <div class="bg-white rounded-lg shadow p-6 mt-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-book-open ml-2 text-blue-600"></i>
                    معايير بناء الدروس على نظام قطر للتعليم
                </h2>
                
                <div class="space-y-4">
                    <?php foreach ($criteria_by_category['lesson_building'] as $criterion): ?>
                        <?php $current_score = $criteria_scores[$criterion['id']] ?? ''; ?>
                        <div class="criterion-card border border-gray-200 rounded-lg p-4 <?= $current_score ? 'filled' : '' ?>">
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
                                $current_score = $criteria_scores[$criterion['id']] ?? '';
                                ?>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <div class="score-radio-option">
                                        <input type="radio" 
                                               name="criterion_<?= $criterion['id'] ?>" 
                                               value="<?= $i ?>" 
                                               id="criterion_<?= $criterion['id'] ?>_<?= $i ?>"
                                               class="score-radio" 
                                               required
                                               <?= $current_score == $i ? 'checked' : '' ?>>
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
            <div class="bg-white rounded-lg shadow p-6 mt-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-tasks ml-2 text-green-600"></i>
                    معايير إسناد التقييمات على نظام قطر للتعليم
                </h2>
                
                <div class="space-y-4">
                    <?php foreach ($criteria_by_category['assessment_management'] as $criterion): ?>
                        <?php $current_score = $criteria_scores[$criterion['id']] ?? ''; ?>
                        <div class="criterion-card border border-gray-200 rounded-lg p-4 <?= $current_score ? 'filled' : '' ?>">
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
                                $current_score = $criteria_scores[$criterion['id']] ?? '';
                                ?>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <div class="score-radio-option">
                                        <input type="radio" 
                                               name="criterion_<?= $criterion['id'] ?>" 
                                               value="<?= $i ?>" 
                                               id="criterion_<?= $criterion['id'] ?>_<?= $i ?>"
                                               class="score-radio" 
                                               required
                                               <?= $current_score == $i ? 'checked' : '' ?>>
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

            <!-- النتائج المحسوبة -->
            <div class="bg-white rounded-lg shadow p-6 mt-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-calculator ml-2 text-purple-600"></i>
                    النتائج المحسوبة
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">الدرجة الإجمالية الحالية</label>
                        <div class="text-2xl font-bold text-blue-600"><?= number_format($eval['total_score'], 2) ?></div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">مستوى الأداء الحالي</label>
                        <div class="text-lg font-bold">
                            <?php
                            $level_labels = [
                                'excellent' => 'ممتاز',
                                'very_good' => 'جيد جداً',
                                'good' => 'جيد',
                                'needs_improvement' => 'يحتاج تحسين',
                                'poor' => 'ضعيف'
                            ];
                            echo $level_labels[$eval['performance_level']] ?? 'غير محدد';
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle ml-1"></i>
                        سيتم إعادة حساب الدرجة الإجمالية ومستوى الأداء تلقائياً عند حفظ التعديلات
                    </p>
                    </div>
                </div>

            <!-- التقييم النوعي -->
            <div class="bg-white rounded-lg shadow p-6 mt-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">التقييم النوعي</h2>
                
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">نقاط القوة</label>
                        <textarea name="strengths" rows="4" 
                                  placeholder="اذكر نقاط القوة في أداء المعلم على نظام قطر للتعليم..."
                                  class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($eval['strengths']) ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">جوانب تحتاج للتحسين</label>
                        <textarea name="improvement_areas" rows="4" 
                                  placeholder="اذكر الجوانب التي تحتاج للتحسين في استخدام نظام قطر للتعليم..."
                                  class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($eval['improvement_areas']) ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">التوصيات</label>
                        <textarea name="recommendations" rows="4" 
                                  placeholder="اذكر التوصيات لتطوير أداء المعلم على نظام قطر للتعليم..."
                                  class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($eval['recommendations']) ?></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">تاريخ المتابعة المقترح</label>
                            <input type="date" name="follow_up_date" 
                                   value="<?= $eval['follow_up_date'] ? date('Y-m-d', strtotime($eval['follow_up_date'])) : '' ?>"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ملاحظات إضافية</label>
                            <textarea name="notes" rows="3" 
                                      placeholder="أي ملاحظات أخرى..."
                                      class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($eval['notes']) ?></textarea>
                        </div>
                    </div>
                </div>
                </div>

                <!-- أزرار الإجراءات -->
                <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                    <div class="flex gap-3">
                        <button type="submit" 
                                class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 transition-colors">
                            <i class="fas fa-save ml-2"></i>
                            حفظ التغييرات
                        </button>
                        
                        <a href="qatar_system_view.php?id=<?= $eval['id'] ?>" 
                           class="bg-gray-600 text-white px-6 py-2 rounded-md hover:bg-gray-700 transition-colors">
                            <i class="fas fa-times ml-2"></i>
                            إلغاء
                        </a>
                    </div>
                    
                    <a href="qatar_system_reports.php" 
                       class="text-blue-600 hover:text-blue-800 transition-colors">
                        <i class="fas fa-arrow-right ml-2"></i>
                        العودة للتقارير
                    </a>
                </div>
            </form>
        </div>
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
                    if (teacherLoading) {
                        teacherLoading.classList.remove('hidden');
                    }
                    teacherSelect.disabled = true;
                    teacherSelect.innerHTML = '<option value="">جاري التحميل...</option>';
                    
                    // طلب AJAX لجلب المعلمين
                    fetch('api/get_teachers_by_subject.php?subject_id=' + subjectId)
                        .then(response => response.json())
                        .then(data => {
                            if (teacherLoading) {
                                teacherLoading.classList.add('hidden');
                            }
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
                            if (teacherLoading) {
                                teacherLoading.classList.add('hidden');
                            }
                            teacherSelect.disabled = false;
                            teacherSelect.innerHTML = '<option value="">خطأ في تحميل المعلمين</option>';
                            console.error('Error:', error);
                        });
                });
                
                // تحميل المعلمين إذا كانت المادة محددة مسبقاً
                if (subjectSelect.value) {
                    // تحميل المعلمين للمادة المحددة
                    const subjectId = subjectSelect.value;
                    const currentTeacherId = '<?= $eval['teacher_id'] ?>';
                    
                    if (teacherLoading) {
                        teacherLoading.classList.remove('hidden');
                    }
                    teacherSelect.disabled = true;
                    teacherSelect.innerHTML = '<option value="">جاري التحميل...</option>';
                    
                    // طلب AJAX لجلب المعلمين
                    fetch('api/get_teachers_by_subject.php?subject_id=' + subjectId)
                        .then(response => response.json())
                        .then(data => {
                            if (teacherLoading) {
                                teacherLoading.classList.add('hidden');
                            }
                            teacherSelect.disabled = false;
                            
                            if (data.success) {
                                teacherSelect.innerHTML = '<option value="">اختر المعلم</option>';
                                data.teachers.forEach(teacher => {
                                    const option = document.createElement('option');
                                    option.value = teacher.id;
                                    option.textContent = teacher.name;
                                    if (teacher.id == currentTeacherId) {
                                        option.selected = true;
                                    }
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
                            if (teacherLoading) {
                                teacherLoading.classList.add('hidden');
                            }
                            teacherSelect.disabled = false;
                            teacherSelect.innerHTML = '<option value="">خطأ في تحميل المعلمين</option>';
                            console.error('Error:', error);
                        });
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
