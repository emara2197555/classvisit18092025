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

try {
    // جلب بيانات التقييم
    $evaluation = query("
        SELECT 
            qsp.*,
            t.name as teacher_name,
            t.email as teacher_email,
            s.name as subject_name,
            ay.name as academic_year_name
        FROM qatar_system_performance qsp
        JOIN teachers t ON qsp.teacher_id = t.id
        JOIN subjects s ON qsp.subject_id = s.id
        JOIN academic_years ay ON qsp.academic_year_id = ay.id
        WHERE qsp.id = ?
    ", [$evaluation_id]);
    
    if (empty($evaluation)) {
        header('Location: qatar_system_reports.php?error=التقييم غير موجود');
        exit;
    }
    
    $eval = $evaluation[0];
    
    // جلب المعايير وتحليل درجاتها
    $criteria = query("SELECT * FROM qatar_system_criteria WHERE is_active = 1 ORDER BY category, sort_order");
    $criteria_by_category = [];
    foreach ($criteria as $criterion) {
        $criteria_by_category[$criterion['category']][] = $criterion;
    }
    
    // تحليل درجات المعايير المحفوظة
    $criteria_scores = json_decode($eval['criteria_scores'], true) ?? [];
    
    $page_title = 'عرض تقييم نظام قطر - ' . $eval['teacher_name'];
    
} catch (Exception $e) {
    header('Location: qatar_system_reports.php?error=خطأ في جلب بيانات التقييم');
    exit;
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
                        <i class="fas fa-eye ml-2"></i>
                        عرض تقييم نظام قطر
                    </h1>
                    <nav class="text-sm text-gray-600">
                        <a href="elearning_coordinator_dashboard.php" class="hover:text-blue-600">لوحة التحكم</a>
                        <span class="mx-2">/</span>
                        <a href="qatar_system_reports.php" class="hover:text-blue-600">تقارير نظام قطر</a>
                        <span class="mx-2">/</span>
                        <span>عرض التقييم</span>
                    </nav>
                </div>
                <div class="flex gap-3">
                    <a href="qatar_system_reports.php" 
                       class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">
                        <i class="fas fa-arrow-right ml-2"></i>
                        العودة للتقارير
                    </a>
                </div>
            </div>
        </div>

        <!-- بطاقة معلومات التقييم -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
            <!-- رأس البطاقة -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-700 px-6 py-4">
                <div class="flex items-center justify-between text-white">
                    <div>
                        <h2 class="text-xl font-bold"><?= htmlspecialchars($eval['teacher_name']) ?></h2>
                        <p class="text-blue-100"><?= htmlspecialchars($eval['subject_name']) ?></p>
                    </div>
                    <div class="text-left">
                        <div class="text-2xl font-bold"><?= number_format($eval['total_score'], 1) ?></div>
                        <div class="text-blue-100 text-sm">الدرجة الإجمالية</div>
                    </div>
                </div>
            </div>

            <!-- محتوى البطاقة -->
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- المعلومات الأساسية -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-800 border-b border-gray-200 pb-2">
                            <i class="fas fa-info-circle ml-2"></i>
                            المعلومات الأساسية
                        </h3>
                        
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <span class="text-gray-600 font-medium w-32">المعلم:</span>
                                <span class="text-gray-900"><?= htmlspecialchars($eval['teacher_name']) ?></span>
                            </div>
                            
                            <div class="flex items-center">
                                <span class="text-gray-600 font-medium w-32">البريد الإلكتروني:</span>
                                <span class="text-gray-900"><?= htmlspecialchars($eval['teacher_email']) ?></span>
                            </div>
                            
                            <div class="flex items-center">
                                <span class="text-gray-600 font-medium w-32">المادة:</span>
                                <span class="text-gray-900"><?= htmlspecialchars($eval['subject_name']) ?></span>
                            </div>
                            
                            <div class="flex items-center">
                                <span class="text-gray-600 font-medium w-32">السنة الدراسية:</span>
                                <span class="text-gray-900"><?= htmlspecialchars($eval['academic_year_name']) ?></span>
                            </div>
                            
                            <div class="flex items-center">
                                <span class="text-gray-600 font-medium w-32">الفصل الدراسي:</span>
                                <span class="text-gray-900"><?= htmlspecialchars($eval['term']) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- معلومات التقييم -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-800 border-b border-gray-200 pb-2">
                            <i class="fas fa-star ml-2"></i>
                            تفاصيل التقييم
                        </h3>
                        
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <span class="text-gray-600 font-medium w-32">تاريخ التقييم:</span>
                                <span class="text-gray-900"><?= date('Y/m/d - h:i A', strtotime($eval['evaluation_date'])) ?></span>
                            </div>
                            
                            <div class="flex items-center">
                                <span class="text-gray-600 font-medium w-32">الدرجة الإجمالية:</span>
                                <span class="text-blue-600 font-bold text-lg"><?= number_format($eval['total_score'], 1) ?></span>
                                <?php if (isset($eval['criteria_count']) && $eval['criteria_count'] > 0): ?>
                                    <span class="text-gray-500 mr-2">من <?= $eval['criteria_count'] ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex items-center">
                                <span class="text-gray-600 font-medium w-32">مستوى الأداء:</span>
                                <?php
                                $level_colors = [
                                    'excellent' => 'bg-green-100 text-green-800',
                                    'very_good' => 'bg-blue-100 text-blue-800',
                                    'good' => 'bg-yellow-100 text-yellow-800',
                                    'needs_improvement' => 'bg-orange-100 text-orange-800',
                                    'poor' => 'bg-red-100 text-red-800'
                                ];
                                $level_labels = [
                                    'excellent' => 'ممتاز',
                                    'very_good' => 'جيد جداً',
                                    'good' => 'جيد',
                                    'needs_improvement' => 'يحتاج تحسين',
                                    'poor' => 'ضعيف'
                                ];
                                ?>
                                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full <?= $level_colors[$eval['performance_level']] ?? 'bg-gray-100 text-gray-800' ?>">
                                    <?= $level_labels[$eval['performance_level']] ?? $eval['performance_level'] ?>
                                </span>
                            </div>
                            
                            <div class="flex items-center">
                                <span class="text-gray-600 font-medium w-32">تاريخ الإنشاء:</span>
                                <span class="text-gray-900"><?= date('Y/m/d - h:i A', strtotime($eval['created_at'])) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- معايير التقييم التفصيلية -->
                <?php if (!empty($criteria_scores)): ?>
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-list-check ml-2"></i>
                        تفاصيل معايير التقييم
                    </h3>
                    
                    <!-- معايير بناء الدروس -->
                    <?php if (isset($criteria_by_category['lesson_building'])): ?>
                    <div class="mb-6">
                        <h4 class="text-md font-semibold text-gray-700 mb-3">
                            <i class="fas fa-book-open ml-2 text-blue-600"></i>
                            معايير بناء الدروس على نظام قطر للتعليم
                        </h4>
                        <div class="space-y-3">
                            <?php foreach ($criteria_by_category['lesson_building'] as $criterion): ?>
                                <?php $score = $criteria_scores[$criterion['id']] ?? 0; ?>
                                <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3">
                                    <div class="flex-1">
                                        <h5 class="font-medium text-gray-800"><?= htmlspecialchars($criterion['criterion_name']) ?></h5>
                                        <p class="text-sm text-gray-600"><?= htmlspecialchars($criterion['description']) ?></p>
                                    </div>
                                    <div class="ml-4">
                                        <?php
                                        $score_colors = [
                                            1 => 'bg-red-100 text-red-800',
                                            2 => 'bg-orange-100 text-orange-800',
                                            3 => 'bg-yellow-100 text-yellow-800',
                                            4 => 'bg-green-100 text-green-800',
                                            5 => 'bg-blue-100 text-blue-800'
                                        ];
                                        $score_labels = [
                                            1 => 'غير متوفرة',
                                            2 => 'بعض الأدلة',
                                            3 => 'معظم الأدلة',
                                            4 => 'مستكملة وفاعلة',
                                            5 => 'ممتاز ومتقن'
                                        ];
                                        ?>
                                        <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full <?= $score_colors[$score] ?? 'bg-gray-100 text-gray-800' ?>">
                                            <?= $score ?> - <?= $score_labels[$score] ?? 'غير محدد' ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- معايير إسناد التقييمات -->
                    <?php if (isset($criteria_by_category['assessment_management'])): ?>
                    <div class="mb-6">
                        <h4 class="text-md font-semibold text-gray-700 mb-3">
                            <i class="fas fa-tasks ml-2 text-green-600"></i>
                            معايير إسناد التقييمات على نظام قطر للتعليم
                        </h4>
                        <div class="space-y-3">
                            <?php foreach ($criteria_by_category['assessment_management'] as $criterion): ?>
                                <?php $score = $criteria_scores[$criterion['id']] ?? 0; ?>
                                <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3">
                                    <div class="flex-1">
                                        <h5 class="font-medium text-gray-800"><?= htmlspecialchars($criterion['criterion_name']) ?></h5>
                                        <p class="text-sm text-gray-600"><?= htmlspecialchars($criterion['description']) ?></p>
                                    </div>
                                    <div class="ml-4">
                                        <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full <?= $score_colors[$score] ?? 'bg-gray-100 text-gray-800' ?>">
                                            <?= $score ?> - <?= $score_labels[$score] ?? 'غير محدد' ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- التقييم النوعي -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-comments ml-2"></i>
                        التقييم النوعي
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- نقاط القوة -->
                        <?php if (!empty($eval['strengths'])): ?>
                        <div class="bg-green-50 rounded-lg p-4">
                            <h4 class="font-semibold text-green-800 mb-2">
                                <i class="fas fa-thumbs-up ml-2"></i>
                                نقاط القوة
                            </h4>
                            <p class="text-green-700 leading-relaxed"><?= nl2br(htmlspecialchars($eval['strengths'])) ?></p>
                        </div>
                        <?php endif; ?>

                        <!-- جوانب التحسين -->
                        <?php if (!empty($eval['improvement_areas'])): ?>
                        <div class="bg-orange-50 rounded-lg p-4">
                            <h4 class="font-semibold text-orange-800 mb-2">
                                <i class="fas fa-tools ml-2"></i>
                                جوانب تحتاج للتحسين
                            </h4>
                            <p class="text-orange-700 leading-relaxed"><?= nl2br(htmlspecialchars($eval['improvement_areas'])) ?></p>
                        </div>
                        <?php endif; ?>

                        <!-- التوصيات -->
                        <?php if (!empty($eval['recommendations'])): ?>
                        <div class="bg-blue-50 rounded-lg p-4">
                            <h4 class="font-semibold text-blue-800 mb-2">
                                <i class="fas fa-lightbulb ml-2"></i>
                                التوصيات
                            </h4>
                            <p class="text-blue-700 leading-relaxed"><?= nl2br(htmlspecialchars($eval['recommendations'])) ?></p>
                        </div>
                        <?php endif; ?>

                        <!-- تاريخ المتابعة -->
                        <?php if (!empty($eval['follow_up_date'])): ?>
                        <div class="bg-purple-50 rounded-lg p-4">
                            <h4 class="font-semibold text-purple-800 mb-2">
                                <i class="fas fa-calendar-check ml-2"></i>
                                تاريخ المتابعة المقترح
                            </h4>
                            <p class="text-purple-700 font-medium"><?= date('Y/m/d', strtotime($eval['follow_up_date'])) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- الملاحظات -->
                <?php if (!empty($eval['notes'])): ?>
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-sticky-note ml-2"></i>
                        الملاحظات الإضافية
                    </h3>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-gray-700 leading-relaxed"><?= nl2br(htmlspecialchars($eval['notes'])) ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- أزرار الإجراءات -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex justify-center">
                        <a href="qatar_system_edit.php?id=<?= $eval['id'] ?>" 
                           class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors">
                            <i class="fas fa-edit ml-2"></i>
                            تعديل التقييم
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<?php include 'includes/elearning_footer.php'; ?>
