<?php
// بدء التخزين المؤقت للمخرجات
ob_start();

// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// التحقق من وجود معرف الزيارة
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // إعادة التوجيه إلى صفحة التقارير
    header('Location: elearning_attendance_reports.php');
    exit;
}

$attendance_id = (int)$_GET['id'];

try {
    // جلب بيانات الزيارة الصفية
    $attendance_sql = "
        SELECT 
            ea.*,
            t.name as teacher_name,
            s.name as subject_name,
            ay.name as academic_year_name
        FROM 
            elearning_attendance ea
        LEFT JOIN 
            teachers t ON ea.teacher_id = t.id
        LEFT JOIN 
            subjects s ON ea.subject_id = s.id
        LEFT JOIN
            academic_years ay ON ea.academic_year_id = ay.id
        WHERE 
            ea.id = ?
    ";
    
    $attendance = query_row($attendance_sql, [$attendance_id]);

    if (!$attendance) {
        throw new Exception('الزيارة غير موجودة');
    }

} catch (Exception $e) {
    // في حالة وجود خطأ، إعادة التوجيه إلى صفحة التقارير
    header('Location: elearning_attendance_reports.php');
    exit;
}

// تحويل نوع الحضور إلى نص مفهوم
$attendance_type_text = $attendance['attendance_type'] == 'direct' ? 'حضور مباشر' : 'حضور عن بُعد';

// تحويل التقييم إلى نص مفهوم
function get_rating_text($rating) {
    switch ($rating) {
        case 'excellent': return 'ممتاز';
        case 'very_good': return 'جيد جداً';
        case 'good': return 'جيد';
        case 'acceptable': return 'مقبول';
        case 'poor': return 'ضعيف';
        default: return 'غير محدد';
    }
}

// حساب التقييم بناءً على عدد الأدوات المستخدمة
function get_tools_based_rating($tools_count) {
    if ($tools_count == 0) return 'poor';
    if ($tools_count == 1) return 'acceptable';
    if ($tools_count == 2) return 'good';
    if ($tools_count == 3) return 'very_good';
    if ($tools_count >= 4) return 'excellent';
    return 'poor';
}

// استخراج التاريخ واليوم
$date_obj = new DateTime($attendance['lesson_date']);
$day_names = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
$day_name = $day_names[$date_obj->format('w')];
$date_formatted = $date_obj->format('Y/m/d');

// تنظيف المخزن المؤقت وبدء عرض المحتوى
ob_end_clean();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طباعة الزيارة الصفية - <?= htmlspecialchars($attendance['teacher_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        @media print {
            body { 
                font-family: 'Arial', sans-serif;
                font-size: 11px;
                line-height: 1.3;
                margin: 0;
                padding: 10px;
            }
            .no-print { display: none !important; }
            .print-break { page-break-after: always; }
            .shadow, .shadow-lg { box-shadow: none !important; }
            .bg-gray-50 { background-color: white !important; }
            .border { border: 1px solid #000 !important; }
            .bg-blue-50 { background-color: #f8f9ff !important; }
            .text-blue-600 { color: #000 !important; }
            .text-gray-600 { color: #333 !important; }
            .text-gray-700 { color: #000 !important; }
            .bg-green-50 { background-color: #f0f9f0 !important; }
            .bg-yellow-50 { background-color: #fffdf0 !important; }
            .bg-red-50 { background-color: #fff0f0 !important; }
            
            /* تحسينات خاصة بصفحة واحدة */
            .print-container { 
                max-height: 100vh; 
                overflow: hidden;
                padding: 0;
            }
            
            .print-header { 
                margin-bottom: 15px; 
                padding-bottom: 10px;
            }
            
            .info-grid { 
                gap: 8px; 
                margin-bottom: 15px; 
            }
            
            .info-item { 
                padding: 4px 8px; 
                font-size: 10px;
            }
            
            .rating-badge { 
                padding: 4px 8px; 
                font-size: 12px; 
            }
            
            .tools-section { 
                margin-bottom: 10px; 
            }
            
            .tools-grid { 
                gap: 4px; 
            }
            
            .tool-item { 
                padding: 2px 6px; 
                font-size: 9px; 
            }
            
            .stats-section { 
                margin-bottom: 10px; 
            }
            
            .stats-grid { 
                gap: 8px; 
            }
            
            .stat-item { 
                padding: 8px; 
                font-size: 10px; 
            }
            
            .notes-section { 
                margin-bottom: 8px; 
            }
            
            .signature-section { 
                margin-top: 15px; 
                padding-top: 10px; 
            }
            
            h1 { font-size: 18px; margin: 8px 0; }
            h2 { font-size: 16px; margin: 6px 0; }
            h3 { font-size: 14px; margin: 4px 0; }
            
            /* منع كسر الصفحة */
            * { page-break-inside: avoid; }
            .no-break { page-break-inside: avoid; }
        }
        
        body { 
            font-family: 'Cairo', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .print-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 15px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            background-color: #f8f9fa;
            border-right: 3px solid #1e40af;
            border-radius: 4px;
        }
        
        .rating-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }
        
        .rating-excellent { background-color: #dcfce7; color: #166534; }
        .rating-very-good { background-color: #dbeafe; color: #1e40af; }
        .rating-good { background-color: #fef3c7; color: #92400e; }
        .rating-acceptable { background-color: #fed7aa; color: #9a3412; }
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
            <h1 class="text-xl font-bold text-gray-800 mb-1">تقرير الزيارة الصفية للتعليم الإلكتروني</h1>
            <p class="text-gray-600 text-sm">منسق التعليم الإلكتروني</p>
        </div>

        <!-- الصف الأول: المعلومات الأساسية -->
        <div class="grid grid-cols-3 gap-3 mb-4 no-break">
            <div class="info-item">
                <i class="fas fa-user text-blue-600 w-4 ml-2 text-sm"></i>
                <div class="text-xs">
                    <span class="font-medium text-gray-700">المعلم:</span>
                    <span class="text-gray-900 mr-1"><?= htmlspecialchars($attendance['teacher_name']) ?></span>
                </div>
            </div>
            
            <div class="info-item">
                <i class="fas fa-book text-blue-600 w-4 ml-2 text-sm"></i>
                <div class="text-xs">
                    <span class="font-medium text-gray-700">المادة:</span>
                    <span class="text-gray-900 mr-1"><?= htmlspecialchars($attendance['subject_name']) ?></span>
                </div>
            </div>
            
            <div class="info-item">
                <i class="fas fa-calendar text-blue-600 w-4 ml-2 text-sm"></i>
                <div class="text-xs">
                    <span class="font-medium text-gray-700">التاريخ:</span>
                    <span class="text-gray-900 mr-1"><?= $date_formatted ?> (<?= $day_name ?>)</span>
                </div>
            </div>
        </div>

        <!-- الصف الثاني: معلومات الحصة -->
        <div class="grid grid-cols-3 gap-3 mb-4 no-break">
            <div class="info-item">
                <i class="fas fa-clock text-blue-600 w-4 ml-2 text-sm"></i>
                <div class="text-xs">
                    <span class="font-medium text-gray-700">الحصة:</span>
                    <span class="text-gray-900 mr-1">الحصة <?= htmlspecialchars($attendance['lesson_number'] ?? '') ?></span>
                </div>
            </div>
            
            <div class="info-item">
                <i class="fas fa-chalkboard-teacher text-blue-600 w-4 ml-2 text-sm"></i>
                <div class="text-xs">
                    <span class="font-medium text-gray-700">نوع الحضور:</span>
                    <span class="text-gray-900 mr-1"><?= $attendance_type_text ?></span>
                </div>
            </div>
            
            <div class="info-item">
                <i class="fas fa-calendar-alt text-blue-600 w-4 ml-2 text-sm"></i>
                <div class="text-xs">
                    <span class="font-medium text-gray-700">السنة الدراسية:</span>
                    <span class="text-gray-900 mr-1"><?= htmlspecialchars($attendance['academic_year_name']) ?></span>
                </div>
            </div>
        </div>

        <!-- تقييم الزيارة والأدوات في صف واحد -->
        <div class="grid grid-cols-2 gap-4 mb-4 no-break">
            <!-- تقييم الزيارة -->
            <div class="p-3 bg-blue-50 rounded-lg border">
                <h3 class="text-sm font-semibold text-gray-800 mb-2 text-center">تقييم الزيارة</h3>
                <div class="text-center">
                    <div class="rating-badge rating-<?= $attendance['attendance_rating'] ?> text-xs">
                        <?= get_rating_text($attendance['attendance_rating']) ?>
                    </div>
                </div>
            </div>

            <!-- إحصائيات الحضور -->
            <div class="p-3 bg-green-50 rounded-lg border">
                <h3 class="text-sm font-semibold text-gray-800 mb-2 text-center">إحصائيات الحضور</h3>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="text-xs">
                        <div class="text-lg font-bold text-green-600"><?= $attendance['num_students'] ?></div>
                        <div class="text-gray-600">إجمالي</div>
                    </div>
                    <div class="text-xs">
                        <div class="text-lg font-bold text-blue-600"><?= $attendance['attendance_students'] ?></div>
                        <div class="text-gray-600">حاضر</div>
                    </div>
                    <div class="text-xs">
                        <div class="text-lg font-bold text-red-600"><?= $attendance['num_students'] - $attendance['attendance_students'] ?></div>
                        <div class="text-gray-600">غائب</div>
                    </div>
                </div>
                <?php
                $attendance_percentage = $attendance['num_students'] > 0 ? 
                    round(($attendance['attendance_students'] / $attendance['num_students']) * 100, 1) : 0;
                ?>
                <div class="mt-2 text-center text-xs">
                    <span class="font-medium text-blue-600"><?= $attendance_percentage ?>%</span>
                </div>
            </div>
        </div>

        <!-- أدوات التعليم الإلكتروني -->
        <?php 
        $tools = [];
        if ($attendance['elearning_tools']) {
            $tools = json_decode($attendance['elearning_tools'], true) ?? [];
        }
        
        $tool_names = [
            'qatar_system' => 'نظام قطر',
            'tablets' => 'الأجهزة اللوحية',
            'interactive_display' => 'العرض التفاعلي',
            'ai_applications' => 'الذكاء الاصطناعي',
            'interactive_websites' => 'المواقع التفاعلية'
        ];
        ?>
        
        <div class="mb-4 no-break tools-section">
            <h3 class="text-sm font-semibold text-gray-800 mb-2 border-b pb-1">أدوات التعليم الإلكتروني</h3>
            
            <?php if (empty($tools)): ?>
                <div class="text-center p-2 bg-red-50 rounded border text-xs">
                    <span class="text-red-600">لم يتم استخدام أي أدوات</span>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-5 gap-1 mb-2 tools-grid">
                    <?php foreach ($tool_names as $tool_key => $tool_name): ?>
                        <div class="tool-item flex items-center justify-center p-1 rounded border <?= in_array($tool_key, $tools) ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200' ?>">
                            <i class="fas <?= in_array($tool_key, $tools) ? 'fa-check text-green-600' : 'fa-times text-gray-400' ?> text-xs ml-1"></i>
                            <span class="<?= in_array($tool_key, $tools) ? 'text-green-800' : 'text-gray-500' ?> text-xs"><?= $tool_name ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- تقييم الأدوات -->
                <?php 
                $tools_rating = get_tools_based_rating(count($tools));
                $tools_rating_text = get_rating_text($tools_rating);
                ?>
                <div class="grid grid-cols-2 gap-2">
                    <div class="text-center p-2 bg-gray-50 rounded border text-xs">
                        <span class="font-medium">عدد الأدوات: </span>
                        <span class="font-bold text-blue-600"><?= count($tools) ?></span>
                    </div>
                    <div class="text-center p-2 bg-gray-50 rounded border text-xs">
                        <span class="font-medium">تقييم الأدوات: </span>
                        <span class="rating-badge rating-<?= $tools_rating ?> text-xs"><?= $tools_rating_text ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- ملاحظات -->
        <?php if ($attendance['notes']): ?>
        <div class="mb-3 notes-section">
            <h3 class="text-sm font-semibold text-gray-800 mb-2 border-b pb-1">ملاحظات</h3>
            <div class="bg-gray-50 rounded p-2 border text-xs">
                <p class="text-gray-900 leading-relaxed"><?= nl2br(htmlspecialchars($attendance['notes'])) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- توقيع وتاريخ الطباعة -->
        <div class="signature-section border-t border-gray-200">
            <div class="grid grid-cols-2 gap-4 items-center text-xs">
                <div>
                    <p class="text-gray-600">تاريخ الطباعة: <?= date('Y/m/d H:i') ?></p>
                </div>
                <div class="text-center">
                    <div class="border-b border-gray-400 w-32 mb-1 mx-auto"></div>
                    <p class="text-gray-600">توقيع منسق التعليم الإلكتروني</p>
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
