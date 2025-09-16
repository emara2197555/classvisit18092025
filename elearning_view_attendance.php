<?php
/**
 * صفحة عرض تفاصيل سجل حضور التعليم الإلكتروني
 */

require_once 'includes/auth_functions.php';
require_once 'includes/functions.php';

// حماية الصفحة لمنسقي التعليم الإلكتروني
protect_page(['E-Learning Coordinator', 'Admin', 'Director', 'Academic Deputy']);

$page_title = 'عرض تفاصيل الحضور';

// التحقق من وجود معرف السجل
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: elearning_attendance_reports.php");
    exit;
}

$attendance_id = (int)$_GET['id'];

// جلب تفاصيل السجل
$attendance_result = query("
    SELECT 
        ea.*,
        t.name as teacher_name,
        t.email as teacher_email,
        s.name as subject_name,
        ay.name as academic_year_name,
        g.name as grade_name,
        sec.name as section_name,
        sch.name as school_name,
        (ea.attendance_students * 100.0 / ea.num_students) as attendance_percentage
    FROM elearning_attendance ea
    JOIN teachers t ON ea.teacher_id = t.id
    JOIN subjects s ON ea.subject_id = s.id
    JOIN academic_years ay ON ea.academic_year_id = ay.id
    LEFT JOIN grades g ON ea.grade_id = g.id
    LEFT JOIN sections sec ON ea.section_id = sec.id
    LEFT JOIN schools sch ON ea.school_id = sch.id
    WHERE ea.id = ?
", [$attendance_id]);

$attendance = !empty($attendance_result) ? $attendance_result[0] : null;

if (!$attendance) {
    header("Location: elearning_attendance_reports.php");
    exit;
}

// تحديد ألوان التقييمات
$rating_colors = [
    'excellent' => 'bg-green-100 text-green-800',
    'very_good' => 'bg-blue-100 text-blue-800',
    'good' => 'bg-yellow-100 text-yellow-800',
    'acceptable' => 'bg-orange-100 text-orange-800',
    'poor' => 'bg-red-100 text-red-800'
];

$rating_labels = [
    'excellent' => 'ممتاز',
    'very_good' => 'جيد جداً',
    'good' => 'جيد',
    'acceptable' => 'مقبول',
    'poor' => 'ضعيف'
];

$type_colors = [
    'live' => 'bg-green-100 text-green-800',
    'recorded' => 'bg-blue-100 text-blue-800',
    'interactive' => 'bg-purple-100 text-purple-800'
];

$type_labels = [
    'live' => 'مباشر',
    'recorded' => 'مسجل',
    'interactive' => 'تفاعلي'
];

// تحديد لون نسبة الحضور
$percentage = $attendance['attendance_percentage'];
$attendance_color = 'text-red-600';
if ($percentage >= 80) $attendance_color = 'text-green-600';
elseif ($percentage >= 60) $attendance_color = 'text-yellow-600';
elseif ($percentage >= 40) $attendance_color = 'text-orange-600';
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
                        تفاصيل سجل الحضور
                    </h1>
                    <nav class="text-sm text-gray-600">
                        <a href="elearning_coordinator_dashboard.php" class="hover:text-blue-600">لوحة التحكم</a>
                        <span class="mx-2">/</span>
                        <a href="elearning_attendance_reports.php" class="hover:text-blue-600">تقارير الحضور</a>
                        <span class="mx-2">/</span>
                        <span>عرض التفاصيل</span>
                    </nav>
                </div>
                <div class="flex space-x-2 rtl:space-x-reverse">
                    <a href="elearning_print_attendance.php?id=<?= $attendance['id'] ?>" 
                       target="_blank"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                        <i class="fas fa-print ml-2"></i>
                        طباعة
                    </a>
                    <a href="elearning_edit_attendance.php?id=<?= $attendance['id'] ?>" 
                       class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                        <i class="fas fa-edit ml-2"></i>
                        تعديل
                    </a>
                    <a href="elearning_attendance_reports.php" 
                       class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                        <i class="fas fa-arrow-right ml-2"></i>
                        العودة
                    </a>
                </div>
            </div>
        </div>

        <!-- معلومات السجل -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <!-- رأس البطاقة -->
            <div class="px-6 py-4 bg-blue-50 border-b border-blue-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-blue-800">
                        معلومات الحضور - <?= date('Y/m/d', strtotime($attendance['lesson_date'])) ?>
                    </h2>
                    <div class="flex items-center space-x-4 rtl:space-x-reverse">
                        <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full <?= $type_colors[$attendance['attendance_type']] ?? 'bg-gray-100 text-gray-800' ?>">
                            <?= $type_labels[$attendance['attendance_type']] ?? $attendance['attendance_type'] ?>
                        </span>
                        <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full <?= $rating_colors[$attendance['attendance_rating']] ?? 'bg-gray-100 text-gray-800' ?>">
                            <?= $rating_labels[$attendance['attendance_rating']] ?? $attendance['attendance_rating'] ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- محتوى البطاقة -->
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- المعلومات الأساسية -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">المعلومات الأساسية</h3>
                        
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <i class="fas fa-calendar-alt text-blue-600 w-5"></i>
                                <span class="font-medium text-gray-700 ml-3">التاريخ:</span>
                                <span class="text-gray-900"><?= date('Y/m/d', strtotime($attendance['lesson_date'])) ?></span>
                            </div>
                            
                            <div class="flex items-center">
                                <i class="fas fa-user text-blue-600 w-5"></i>
                                <span class="font-medium text-gray-700 ml-3">المعلم:</span>
                                <span class="text-gray-900"><?= htmlspecialchars($attendance['teacher_name']) ?></span>
                            </div>
                            
                            <div class="flex items-center">
                                <i class="fas fa-book text-blue-600 w-5"></i>
                                <span class="font-medium text-gray-700 ml-3">المادة:</span>
                                <span class="text-gray-900"><?= htmlspecialchars($attendance['subject_name']) ?></span>
                            </div>
                            
                            <div class="flex items-center">
                                <i class="fas fa-school text-blue-600 w-5"></i>
                                <span class="font-medium text-gray-700 ml-3">المدرسة:</span>
                                <span class="text-gray-900"><?= htmlspecialchars($attendance['school_name'] ?? 'غير محدد') ?></span>
                            </div>
                            
                            <div class="flex items-center">
                                <i class="fas fa-graduation-cap text-blue-600 w-5"></i>
                                <span class="font-medium text-gray-700 ml-3">الصف:</span>
                                <span class="text-gray-900">
                                    <?= htmlspecialchars(($attendance['grade_name'] ?? 'غير محدد') . ' - شعبة ' . ($attendance['section_name'] ?? 'غير محدد')) ?>
                                </span>
                            </div>
                            
                            <div class="flex items-center">
                                <i class="fas fa-calendar-year text-blue-600 w-5"></i>
                                <span class="font-medium text-gray-700 ml-3">السنة الدراسية:</span>
                                <span class="text-gray-900"><?= htmlspecialchars($attendance['academic_year_name']) ?></span>
                            </div>
                            
                            <?php if (isset($attendance['term']) && $attendance['term']): ?>
                            <div class="flex items-center">
                                <i class="fas fa-calendar-check text-blue-600 w-5"></i>
                                <span class="font-medium text-gray-700 ml-3">الفصل:</span>
                                <span class="text-gray-900"><?= htmlspecialchars($attendance['term']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- إحصائيات الحضور -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">إحصائيات الحضور</h3>
                        
                        <div class="space-y-4">
                            <!-- عدد الطلاب -->
                            <div class="bg-blue-50 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <i class="fas fa-users text-blue-600 text-xl"></i>
                                        <span class="font-medium text-gray-700 ml-3">إجمالي الطلاب</span>
                                    </div>
                                    <span class="text-2xl font-bold text-blue-600"><?= $attendance['num_students'] ?></span>
                                </div>
                            </div>
                            
                            <!-- الحضور -->
                            <div class="bg-green-50 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                                        <span class="font-medium text-gray-700 ml-3">الحاضرون</span>
                                    </div>
                                    <span class="text-2xl font-bold text-green-600"><?= $attendance['attendance_students'] ?></span>
                                </div>
                            </div>
                            
                            <!-- الغياب -->
                            <div class="bg-red-50 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <i class="fas fa-times-circle text-red-600 text-xl"></i>
                                        <span class="font-medium text-gray-700 ml-3">الغائبون</span>
                                    </div>
                                    <span class="text-2xl font-bold text-red-600"><?= $attendance['num_students'] - $attendance['attendance_students'] ?></span>
                                </div>
                            </div>
                            
                            <!-- نسبة الحضور -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <i class="fas fa-percentage text-gray-600 text-xl"></i>
                                        <span class="font-medium text-gray-700 ml-3">نسبة الحضور</span>
                                    </div>
                                    <span class="text-2xl font-bold <?= $attendance_color ?>"><?= number_format($percentage, 1) ?>%</span>
                                </div>
                                
                                <!-- شريط التقدم -->
                                <div class="mt-3">
                                    <div class="w-full bg-gray-200 rounded-full h-3">
                                        <div class="h-3 rounded-full <?= $percentage >= 80 ? 'bg-green-500' : ($percentage >= 60 ? 'bg-yellow-500' : ($percentage >= 40 ? 'bg-orange-500' : 'bg-red-500')) ?>" 
                                             style="width: <?= $percentage ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- أدوات التعليم الإلكتروني المستخدمة -->
                <?php 
                $tools = [];
                if ($attendance['elearning_tools']) {
                    $tools = json_decode($attendance['elearning_tools'], true) ?? [];
                }
                
                $tool_names = [
                    'qatar_system' => 'نظام قطر للتعليم',
                    'tablets' => 'الأجهزة اللوحية',
                    'interactive_display' => 'برامج أجهزة العرض التفاعلي',
                    'ai_applications' => 'تطبيقات الذكاء الاصطناعي',
                    'interactive_websites' => 'المواقع التفاعلية'
                ];
                ?>
                
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">أدوات التعليم الإلكتروني المستخدمة</h3>
                    
                    <?php if (empty($tools)): ?>
                        <div class="bg-red-50 rounded-lg p-4 text-center">
                            <i class="fas fa-exclamation-triangle text-red-500 text-xl mb-2"></i>
                            <p class="text-red-700 font-medium">لم يتم استخدام أي أدوات إلكترونية في هذه الزيارة</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                            <?php foreach ($tool_names as $tool_key => $tool_name): ?>
                                <div class="flex items-center p-3 rounded-lg border <?= in_array($tool_key, $tools) ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200' ?>">
                                    <i class="fas <?= in_array($tool_key, $tools) ? 'fa-check-circle text-green-600' : 'fa-times-circle text-gray-400' ?> w-5 ml-3"></i>
                                    <span class="<?= in_array($tool_key, $tools) ? 'text-green-800 font-medium' : 'text-gray-500' ?>">
                                        <?= $tool_name ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="flex items-center justify-center space-x-4 rtl:space-x-reverse">
                                <div class="text-center">
                                    <span class="text-2xl font-bold text-blue-600"><?= count($tools) ?></span>
                                    <p class="text-sm text-gray-600">أدوات مستخدمة</p>
                                </div>
                                <div class="text-center">
                                    <span class="text-2xl font-bold text-gray-600"><?= count($tool_names) - count($tools) ?></span>
                                    <p class="text-sm text-gray-600">أدوات غير مستخدمة</p>
                                </div>
                                <div class="text-center">
                                    <span class="text-2xl font-bold text-green-600"><?= round((count($tools) / count($tool_names)) * 100) ?>%</span>
                                    <p class="text-sm text-gray-600">نسبة الاستخدام</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- تقييم الحضور بناءً على الأدوات -->
                        <?php 
                        function get_tools_rating($tools_count) {
                            if ($tools_count == 0) return ['poor', 'ضعيف', 'لا توجد أدوات'];
                            if ($tools_count == 1) return ['acceptable', 'مقبول', 'أداة واحدة'];
                            if ($tools_count == 2) return ['good', 'جيد', 'أداتان'];
                            if ($tools_count == 3) return ['very_good', 'جيد جداً', '3 أدوات'];
                            if ($tools_count >= 4) return ['excellent', 'ممتاز', '4+ أدوات'];
                            return ['poor', 'ضعيف', 'لا توجد أدوات'];
                        }
                        
                        list($rating_class, $rating_text, $rating_desc) = get_tools_rating(count($tools));
                        $rating_colors = [
                            'excellent' => 'bg-green-100 text-green-800 border-green-200',
                            'very_good' => 'bg-blue-100 text-blue-800 border-blue-200',
                            'good' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                            'acceptable' => 'bg-orange-100 text-orange-800 border-orange-200',
                            'poor' => 'bg-red-100 text-red-800 border-red-200'
                        ];
                        ?>
                        
                        <div class="mt-4 p-4 border rounded-lg <?= $rating_colors[$rating_class] ?>">
                            <div class="text-center">
                                <p class="text-sm font-medium mb-2">تقييم الحضور بناءً على الأدوات المستخدمة:</p>
                                <div class="inline-block px-4 py-2 rounded-full bg-white/50 border">
                                    <span class="font-bold text-lg"><?= $rating_text ?></span>
                                </div>
                                <p class="text-xs mt-2 opacity-75"><?= $rating_desc ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- ملاحظات إضافية -->
                <?php if ($attendance['notes']): ?>
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">ملاحظات</h3>
                    <div class="bg-yellow-50 rounded-lg p-4">
                        <p class="text-gray-900"><?= htmlspecialchars($attendance['notes']) ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- معلومات السجل -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">معلومات السجل</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                        <div>
                            <span class="font-medium">تاريخ الإنشاء:</span>
                            <?= isset($attendance['created_at']) ? date('Y/m/d H:i', strtotime($attendance['created_at'])) : 'غير متوفر' ?>
                        </div>
                        <div>
                            <span class="font-medium">آخر تحديث:</span>
                            <?= isset($attendance['updated_at']) ? date('Y/m/d H:i', strtotime($attendance['updated_at'])) : 'غير متوفر' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/elearning_footer.php'; ?>
