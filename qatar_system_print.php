<?php
// بدء التخزين المؤقت للمخرجات
ob_start();

// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// التحقق من وجود معرف التقييم
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // إعادة التوجيه إلى صفحة التقارير
    header('Location: qatar_system_reports.php');
    exit;
}

$evaluation_id = (int)$_GET['id'];

try {
    // جلب بيانات التقييم
    $evaluation_sql = "
        SELECT 
            qsp.*,
            t.name as teacher_name,
            s.name as subject_name,
            ay.name as academic_year_name
        FROM 
            qatar_system_performance qsp
        LEFT JOIN 
            teachers t ON qsp.teacher_id = t.id
        LEFT JOIN 
            subjects s ON qsp.subject_id = s.id
        LEFT JOIN
            academic_years ay ON qsp.academic_year_id = ay.id
        WHERE 
            qsp.id = ?
    ";
    
    $evaluation = query_row($evaluation_sql, [$evaluation_id]);

    if (!$evaluation) {
        throw new Exception('التقييم غير موجود');
    }

    // جلب معايير التقييم مع الدرجات
    $criteria = query("SELECT * FROM qatar_system_criteria WHERE is_active = 1 ORDER BY category, sort_order");
    $criteria_scores = json_decode($evaluation['criteria_scores'], true) ?: [];

} catch (Exception $e) {
    // في حالة وجود خطأ، إعادة التوجيه إلى صفحة التقارير
    header('Location: qatar_system_reports.php');
    exit;
}

// تحويل مستوى الأداء إلى نص مفهوم
function get_performance_level_text($level) {
    switch ($level) {
        case 'excellent': return 'ممتاز';
        case 'very_good': return 'جيد جداً';
        case 'good': return 'جيد';
        case 'needs_improvement': return 'يحتاج تحسين';
        case 'poor': return 'ضعيف';
        default: return 'غير محدد';
    }
}

// تحويل درجة المعيار إلى نص
function get_score_text($score) {
    switch ($score) {
        case 1: return 'غير متوفرة';
        case 2: return 'بعض الأدلة';
        case 3: return 'معظم الأدلة';
        case 4: return 'مستكملة وفاعلة';
        case 5: return 'ممتاز ومتقن';
        default: return 'غير محدد';
    }
}

// تحويل الفصل الدراسي إلى نص عربي
function get_term_text($term) {
    switch ($term) {
        case 'first': return 'الأول';
        case 'second': return 'الثاني';
        case 'third': return 'الثالث';
        default: return $term;
    }
}

// استخراج التاريخ واليوم
$date_obj = new DateTime($evaluation['evaluation_date']);
$day_names = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
$day_name = $day_names[$date_obj->format('w')];
$date_formatted = $date_obj->format('Y/m/d');

// تجميع المعايير حسب التصنيف
$criteria_by_category = [];
foreach ($criteria as $criterion) {
    $criteria_by_category[$criterion['category']][] = $criterion;
}

// تنظيف المخزن المؤقت وبدء عرض المحتوى
ob_end_clean();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طباعة تقييم نظام قطر - <?= htmlspecialchars($evaluation['teacher_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        @media print {
            body { 
                font-family: 'Arial', sans-serif;
                font-size: 10px; /* أصغر شوية */
                line-height: 1.2; /* تقليل المسافة بين الأسطر */
                margin: 0;
                padding: 10px; /* بدل 15px */
            }
            .no-print { display: none !important; }
            .print-break { page-break-after: always; }
            .shadow, .shadow-lg { box-shadow: none !important; }
            .bg-gray-50 { background-color: white !important; }
            .border { border: 1px solid #000 !important; }
            .bg-blue-50 { background-color: #f8f9ff !important; }
            .bg-green-50 { background-color: #f0f9f0 !important; }
            .bg-yellow-50 { background-color: #fffdf0 !important; }
            .bg-red-50 { background-color: #fff0f0 !important; }
            .bg-red-100 { background-color: #fef2f2 !important; }
            .bg-orange-100 { background-color: #fff7ed !important; }
            .bg-yellow-100 { background-color: #fefce8 !important; }
            .bg-blue-100 { background-color: #eff6ff !important; }
            .bg-green-100 { background-color: #f0fdf4 !important; }
            .text-blue-600 { color: #000 !important; }
            .text-green-600 { color: #000 !important; }
            .text-yellow-600 { color: #000 !important; }
            .text-red-600 { color: #000 !important; }
            .text-gray-600 { color: #333 !important; }
            .text-gray-700 { color: #000 !important; }
            
            /* إزالة قيود الارتفاع */
            .print-container { 
                max-height: none !important; 
                overflow: visible !important;
                padding: 0;
            }
            
            .print-header { 
                margin-bottom: 10px; 
                padding-bottom: 8px;
            }
            
            .info-grid, .grid { 
                gap: 6px !important; 
                margin-bottom: 8px !important; 
            }
            
            .info-item { 
                padding: 4px 6px !important; /* تقليل الهوامش داخل الصناديق */
                font-size: 9px !important;
            }
            
            .rating-badge { 
                padding: 2px 6px !important; 
                font-size: 10px !important; 
            }
            
            h1 { font-size: 14px !important; margin: 5px 0 !important; }
            h2 { font-size: 12px !important; margin: 4px 0 !important; }
            h3 { font-size: 11px !important; margin: 3px 0 !important; }
            
            /* مسافات مضغوطة */
            .mb-3 { margin-bottom: 8px !important; }
            .mb-2 { margin-bottom: 5px !important; }
            .mb-1 { margin-bottom: 3px !important; }
            .p-2 { padding: 5px !important; }
            .p-3 { padding: 6px !important; }
            .p-1 { padding: 2px !important; }
            
            /* السماح بكسر الصفحة للعناصر الكبيرة */
            .criteria-section { 
                page-break-inside: auto !important; 
                break-inside: auto !important;
            }
            
            /* تحسين الجداول والشبكات */
            .grid-cols-4 { grid-template-columns: repeat(4, 1fr) !important; }
            .grid-cols-3 { grid-template-columns: repeat(3, 1fr) !important; }
            .grid-cols-2 { grid-template-columns: repeat(2, 1fr) !important; }
            .grid-cols-5 { grid-template-columns: repeat(5, 1fr) !important; }
            
            /* تحسين صناديق المعلومات للـ 3 أعمدة */
            .grid-cols-3 > .info-item {
                display: flex !important;
                align-items: center !important;
                justify-content: flex-start !important;
                font-size: 8px !important;
                padding: 3px 5px !important;
            }
            
            /* تصغير صناديق مقياس التقييم */
            .grid-cols-5 > div {
                padding: 4px !important;
            }
            .grid-cols-5 .text-lg { font-size: 12px !important; }
            .grid-cols-5 .text-xs { font-size: 9px !important; }
            
            /* تحسين النصوص */
            .text-xs { font-size: 9px !important; }
            .text-sm { font-size: 10px !important; }
        }
        
        /* للعرض على الشاشة أيضًا */
        body { 
            font-family: 'Cairo', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 13px; /* كان 14 تقريبًا */
            line-height: 1.4; /* بدل 1.6 */
        }
        
        .print-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 15px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            padding: 6px 8px; /* أصغر من السابق */
            background-color: #f8f9fa;
            border-right: 3px solid #1e40af;
            border-radius: 4px;
        }
        
        .rating-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 15px;
            font-weight: bold;
            font-size: 11px;
        }
        
        .rating-excellent { background-color: #dcfce7; color: #166534; }
        .rating-very-good { background-color: #dbeafe; color: #1e40af; }
        .rating-good { background-color: #fef3c7; color: #92400e; }
        .rating-needs_improvement { background-color: #fed7aa; color: #9a3412; }
        .rating-poor { background-color: #fecaca; color: #991b1b; }
    </style>
</head>
<body class="bg-gray-50 p-6">
    <!-- أزرار الطباعة -->
    <div class="no-print mb-6 text-center">
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded ml-2">
            <i class="fas fa-print ml-2"></i>طباعة
        </button>
        <button onclick="window.close()" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-times ml-2"></i>إغلاق
        </button>
    </div>

    <!-- محتوى الطباعة -->
    <div class="max-w-4xl mx-auto bg-white shadow-lg rounded-lg p-4 print-container">
        <!-- رأس التقرير -->
        <div class="print-header">
            <h1 class="text-xl font-bold text-gray-800 mb-1">تقرير تقييم نظام قطر للتعليم -- منسق التعليم الإلكتروني</h1>
        </div>

        <!-- المعلومات الأساسية (6 عناصر في 3 أعمدة) -->
        <div class="grid grid-cols-3 gap-2 mb-3 text-sm">
            <div class="info-item">
                <i class="fas fa-user text-blue-600 w-4 ml-2 text-sm"></i>
                <div class="text-sm">
                    <span class="font-medium text-gray-700">المعلم:</span>
                    <span class="text-gray-900 mr-1 font-semibold"><?= htmlspecialchars($evaluation['teacher_name']) ?></span>
                </div>
            </div>
            
            <div class="info-item">
                <i class="fas fa-book text-blue-600 w-4 ml-2 text-sm"></i>
                <div class="text-sm">
                    <span class="font-medium text-gray-700">المادة:</span>
                    <span class="text-gray-900 mr-1 font-semibold"><?= htmlspecialchars($evaluation['subject_name']) ?></span>
                </div>
            </div>
            
            <div class="info-item">
                <i class="fas fa-calendar text-blue-600 w-4 ml-2 text-sm"></i>
                <div class="text-sm">
                    <span class="font-medium text-gray-700">تاريخ التقييم:</span>
                    <span class="text-gray-900 mr-1 font-semibold"><?= $date_formatted ?> (<?= $day_name ?>)</span>
                </div>
            </div>

            <div class="info-item">
                <i class="fas fa-graduation-cap text-blue-600 w-4 ml-2 text-sm"></i>
                <div class="text-sm">
                    <span class="font-medium text-gray-700">الفصل الدراسي:</span>
                    <span class="text-gray-900 mr-1 font-semibold"><?= get_term_text($evaluation['term']) ?></span>
                </div>
            </div>

            <div class="info-item">
                <i class="fas fa-calendar-alt text-blue-600 w-4 ml-2 text-sm"></i>
                <div class="text-sm">
                    <span class="font-medium text-gray-700">السنة الدراسية:</span>
                    <span class="text-gray-900 mr-1 font-semibold"><?= htmlspecialchars($evaluation['academic_year_name']) ?></span>
                </div>
            </div>

            <?php if ($evaluation['follow_up_date']): ?>
            <div class="info-item">
                <i class="fas fa-clock text-blue-600 w-4 ml-2 text-sm"></i>
                <div class="text-sm">
                    <span class="font-medium text-gray-700">تاريخ المتابعة:</span>
                    <span class="text-gray-900 mr-1 font-semibold"><?= date('Y/m/d', strtotime($evaluation['follow_up_date'])) ?></span>
                </div>
            </div>
            <?php else: ?>
            <div></div> <!-- مساحة فارغة للحفاظ على التخطيط -->
            <?php endif; ?>
        </div>

        <!-- معايير بناء الدروس -->
        <?php if (isset($criteria_by_category['lesson_building'])): ?>
        <div class="mb-3 criteria-section">
            <h3 class="text-sm font-bold text-gray-800 mb-2 bg-blue-50 p-2 rounded border-r-4 border-blue-400">
                <i class="fas fa-book-open ml-1 text-blue-600 text-xs"></i>
                معايير بناء الدروس على نظام قطر للتعليم
            </h3>
            
            <div class="grid grid-cols-1 gap-1">
                <?php foreach ($criteria_by_category['lesson_building'] as $criterion): ?>
                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded text-xs border">
                        <div class="flex-1">
                            <div class="font-medium text-gray-800 mb-1"><?= htmlspecialchars($criterion['criterion_name']) ?></div>
                            <div class="text-gray-600 text-xs leading-relaxed"><?= htmlspecialchars($criterion['description']) ?></div>
                        </div>
                        <div class="mr-3">
                            <?php 
                            $score = $criteria_scores[$criterion['id']] ?? 0;
                            $score_color = $score >= 4 ? 'text-green-600' : ($score >= 3 ? 'text-yellow-600' : 'text-red-600');
                            ?>
                            <div class="text-center">
                                <span class="font-bold <?= $score_color ?> text-lg"><?= $score ?></span>
                                <div class="text-gray-500 text-xs"><?= get_score_text($score) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- معايير إسناد التقييمات -->
        <?php if (isset($criteria_by_category['assessment_management'])): ?>
        <div class="mb-3 criteria-section">
            <h3 class="text-sm font-bold text-gray-800 mb-2 bg-green-50 p-2 rounded border-r-4 border-green-400">
                <i class="fas fa-tasks ml-1 text-green-600 text-xs"></i>
                معايير إسناد التقييمات على نظام قطر للتعليم
            </h3>
            
            <div class="grid grid-cols-1 gap-1">
                <?php foreach ($criteria_by_category['assessment_management'] as $criterion): ?>
                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded text-xs border">
                        <div class="flex-1">
                            <div class="font-medium text-gray-800 mb-1"><?= htmlspecialchars($criterion['criterion_name']) ?></div>
                            <div class="text-gray-600 text-xs leading-relaxed"><?= htmlspecialchars($criterion['description']) ?></div>
                        </div>
                        <div class="mr-3">
                            <?php 
                            $score = $criteria_scores[$criterion['id']] ?? 0;
                            $score_color = $score >= 4 ? 'text-green-600' : ($score >= 3 ? 'text-yellow-600' : 'text-red-600');
                            ?>
                            <div class="text-center">
                                <span class="font-bold <?= $score_color ?> text-lg"><?= $score ?></span>
                                <div class="text-gray-500 text-xs"><?= get_score_text($score) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- تقييم الأداء والنتيجة (في سطر واحد مضغوط) -->
        <div class="grid grid-cols-2 gap-2 mb-3 text-sm">
            <div class="p-2 bg-blue-50 rounded border flex flex-col items-center justify-center">
                <span class="font-semibold text-gray-800 text-xs mb-1">مستوى الأداء:</span>
                <div class="rating-badge rating-<?= $evaluation['performance_level'] ?> text-xs">
                    <?= get_performance_level_text($evaluation['performance_level']) ?>
                </div>
            </div>
            <div class="p-2 bg-green-50 rounded border flex flex-col items-center justify-center">
                <span class="font-semibold text-gray-800 text-xs mb-1">النتيجة الإجمالية:</span>
                <span class="text-green-600 font-bold text-lg"><?= number_format($evaluation['total_score'], 1) ?> / 5.0</span>
            </div>
        </div>

        <!-- التقييم النوعي -->
        <div class="mb-4">
            <h3 class="text-sm font-bold text-gray-800 mb-3 bg-yellow-50 p-3 rounded border-r-4 border-yellow-400">
                <i class="fas fa-clipboard-list ml-1 text-yellow-600"></i>
                التقييم النوعي
            </h3>
            
            <div class="space-y-3">
                <!-- نقاط القوة -->
                <div class="border rounded p-3">
                    <div class="font-bold text-gray-700 mb-2 text-sm">
                        <i class="fas fa-thumbs-up text-green-600 ml-1"></i>
                        نقاط القوة:
                    </div>
                    <div class="text-gray-900 leading-relaxed text-sm">
                        <?= $evaluation['strengths'] ? nl2br(htmlspecialchars($evaluation['strengths'])) : 'لا توجد ملاحظات' ?>
                    </div>
                </div>

                <!-- جوانب التحسين -->
                <div class="border rounded p-3">
                    <div class="font-bold text-gray-700 mb-2 text-sm">
                        <i class="fas fa-exclamation-triangle text-orange-600 ml-1"></i>
                        جوانب تحتاج للتحسين:
                    </div>
                    <div class="text-gray-900 leading-relaxed text-sm">
                        <?= $evaluation['improvement_areas'] ? nl2br(htmlspecialchars($evaluation['improvement_areas'])) : 'لا توجد ملاحظات' ?>
                    </div>
                </div>

                <!-- التوصيات -->
                <div class="border rounded p-3">
                    <div class="font-bold text-gray-700 mb-2 text-sm">
                        <i class="fas fa-lightbulb text-blue-600 ml-1"></i>
                        التوصيات:
                    </div>
                    <div class="text-gray-900 leading-relaxed text-sm">
                        <?= $evaluation['recommendations'] ? nl2br(htmlspecialchars($evaluation['recommendations'])) : 'لا توجد توصيات' ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- مقياس التقييم -->
        <div class="mb-4 border-t pt-4">
            <h3 class="text-sm font-semibold text-gray-800 mb-3">
                <i class="fas fa-ruler ml-1 text-purple-600"></i>
                مقياس التقييم:
            </h3>
            <div class="grid grid-cols-5 gap-2 text-sm">
                <div class="text-center p-3 bg-red-100 rounded border">
                    <div class="font-bold text-red-700 text-lg">1</div>
                    <div class="text-red-600 text-xs mt-1">غير متوفرة</div>
                </div>
                <div class="text-center p-3 bg-orange-100 rounded border">
                    <div class="font-bold text-orange-700 text-lg">2</div>
                    <div class="text-orange-600 text-xs mt-1">بعض الأدلة</div>
                </div>
                <div class="text-center p-3 bg-yellow-100 rounded border">
                    <div class="font-bold text-yellow-700 text-lg">3</div>
                    <div class="text-yellow-600 text-xs mt-1">معظم الأدلة</div>
                </div>
                <div class="text-center p-3 bg-blue-100 rounded border">
                    <div class="font-bold text-blue-700 text-lg">4</div>
                    <div class="text-blue-600 text-xs mt-1">مستكملة وفاعلة</div>
                </div>
                <div class="text-center p-3 bg-green-100 rounded border">
                    <div class="font-bold text-green-700 text-lg">5</div>
                    <div class="text-green-600 text-xs mt-1">ممتاز ومتقن</div>
                </div>
            </div>
        </div>

        <!-- ملاحظات إضافية -->
        <?php if (!empty($evaluation['notes'])): ?>
        <div class="mb-4">
            <h3 class="text-sm font-semibold text-gray-800 mb-2 border-b-2 pb-2">
                <i class="fas fa-sticky-note ml-1 text-orange-600"></i>
                ملاحظات إضافية
            </h3>
            <div class="bg-gray-50 rounded p-3 border text-sm">
                <p class="text-gray-900 leading-relaxed"><?= nl2br(htmlspecialchars($evaluation['notes'])) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- توقيع وتاريخ الطباعة -->
        <div class="border-t-2 border-gray-300 pt-4">
            <div class="grid grid-cols-2 gap-6 items-center text-sm">
                <div>
                    <p class="text-gray-600">
                        <i class="fas fa-print ml-1 text-blue-600"></i>
                        تاريخ الطباعة: <strong><?= date('Y/m/d H:i') ?></strong>
                    </p>
                    <p class="text-gray-600 mt-1">
                        <i class="fas fa-user ml-1 text-green-600"></i>
                        طُبع بواسطة: <strong>منسق التعليم الإلكتروني</strong>
                    </p>
                </div>
                <div class="text-center">
                    <div class="border-b-2 border-gray-500 w-40 mb-2 mx-auto"></div>
                    <p class="text-gray-700 font-medium">توقيع منسق التعليم الإلكتروني</p>
                    <p class="text-gray-500 text-xs mt-1">التوقيع والختم</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // طباعة تلقائية عند تحميل الصفحة (اختياري)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>