<?php
// بدء التخزين المؤقت للمخرجات
ob_start();

// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';
require_once 'includes/auth_functions.php';

// حماية الصفحة - جميع المستخدمين المسجلين يمكنهم عرض الزيارات
protect_page();

// الحصول على معلومات المستخدم
$user_id = $_SESSION['user_id'];
$user_role_name = $_SESSION['role_name'];

// تعيين عنوان الصفحة
$page_title = 'عرض تفاصيل الزيارة الصفية';

// تضمين ملف رأس الصفحة
require_once 'includes/header.php';

// التحقق من وجود معرف الزيارة
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo show_alert('معرف الزيارة غير صحيح', 'error');
    require_once 'includes/footer.php';
    exit;
}

$visit_id = (int)$_GET['id'];

try {
    // جلب بيانات الزيارة
    $visit_sql = "
        SELECT 
            v.*,
            s.name as school_name,
            t.name as teacher_name,
            sub.name as subject_name,
            g.name as grade_name,
            sec.name as section_name,
            el.name as level_name,
            vt.name as visitor_type_name,
            vp.name as visitor_person_name
        FROM 
            visits v
        LEFT JOIN 
            schools s ON v.school_id = s.id
        LEFT JOIN 
            teachers t ON v.teacher_id = t.id
        LEFT JOIN 
            subjects sub ON v.subject_id = sub.id
        LEFT JOIN 
            grades g ON v.grade_id = g.id
        LEFT JOIN 
            sections sec ON v.section_id = sec.id
        LEFT JOIN 
            educational_levels el ON v.level_id = el.id
        LEFT JOIN 
            visitor_types vt ON v.visitor_type_id = vt.id
        LEFT JOIN 
            teachers vp ON v.visitor_person_id = vp.id
        WHERE 
            v.id = ?
    ";
    
    $visit = query_row($visit_sql, [$visit_id]);
    
    if (!$visit) {
        throw new Exception('الزيارة غير موجودة');
    }
    
    // التحقق من صلاحيات الوصول للزيارة
    $access_denied = false;
    
    if ($user_role_name === 'Teacher') {
        // المعلم يمكنه رؤية زياراته فقط
        $teacher_data = query_row("SELECT id FROM teachers WHERE user_id = ?", [$user_id]);
        if (!$teacher_data || $visit['teacher_id'] != $teacher_data['id']) {
            $access_denied = true;
        }
    } elseif ($user_role_name === 'Subject Coordinator') {
        // منسق المادة يمكنه رؤية زيارات مادته فقط
        $coordinator_data = query_row("
            SELECT subject_id 
            FROM coordinator_supervisors 
            WHERE user_id = ?
        ", [$user_id]);
        
        if (!$coordinator_data || $visit['subject_id'] != $coordinator_data['subject_id']) {
            $access_denied = true;
        }
    }
    
    if ($access_denied) {
        echo show_alert('غير مسموح لك بعرض هذه الزيارة', 'error');
        require_once 'includes/footer.php';
        exit;
    }
    
    // جلب تفاصيل التقييم لهذه الزيارة
    $evaluation_sql = "
        SELECT 
            ei.id as indicator_id,
            ei.name as indicator_text,
            ei.domain_id,
            ed.name as domain_name,
            MAX(ve.score) as score
        FROM 
            evaluation_indicators ei
        JOIN 
            evaluation_domains ed ON ei.domain_id = ed.id
        LEFT JOIN 
            visit_evaluations ve ON ve.indicator_id = ei.id AND ve.visit_id = ?
        WHERE 
            EXISTS (SELECT 1 FROM visit_evaluations WHERE visit_id = ? AND indicator_id = ei.id)
            " . (($visit['has_lab'] ?? 0) == 0 ? " AND ei.domain_id <> 5" : "") . "
        GROUP BY
            ei.id, ei.name, ei.domain_id, ed.name
        ORDER BY
            ei.domain_id, ei.id
    ";
    
    $evaluations = query($evaluation_sql, [$visit_id, $visit_id]);
    
    // تجميع التقييمات حسب المجال
    $evaluations_by_domain = [];
    $domains = [];
    
    foreach ($evaluations as $eval) {
        $domain_id = $eval['domain_id'];
        
        if (!isset($evaluations_by_domain[$domain_id])) {
            $evaluations_by_domain[$domain_id] = [];
            $domains[$domain_id] = $eval['domain_name'];
        }
        
        $evaluations_by_domain[$domain_id][] = $eval;
    }

    // حساب متوسط الدرجات مع استبعاد المؤشرات التي لم يتم قياسها
    $total_scores = 0;
    $valid_indicators_count = 0;

    // استعلام لجلب جميع التقييمات لهذه الزيارة
    if (($visit['has_lab'] ?? 0) == 0) {
        $scores_sql = "
            SELECT ve.score
            FROM visit_evaluations ve
            JOIN evaluation_indicators ei ON ve.indicator_id = ei.id
            WHERE ve.visit_id = ? AND ei.domain_id <> 5
        ";
        $scores = query($scores_sql, [$visit_id]);
    } else {
        $scores_sql = "
            SELECT score 
            FROM visit_evaluations 
            WHERE visit_id = ?
        ";
        $scores = query($scores_sql, [$visit_id]);
    }

    foreach ($scores as $score_item) {
        // نستثني المؤشرات غير المقاسة (score = NULL)
        if ($score_item['score'] !== null) {
            $total_scores += (float)$score_item['score'];
            $valid_indicators_count++;
        }
    }

    // حساب المتوسط فقط للمؤشرات المقاسة
    $average_score = $valid_indicators_count > 0 ? round($total_scores / $valid_indicators_count, 2) : 0;
    $grade = get_grade($average_score);

    // تحويل الدرجة إلى نسبة مئوية (من 3 إلى 100%)
    $percentage_score = $valid_indicators_count > 0 ? round(($total_scores / ($valid_indicators_count * 3)) * 100, 2) : 0;
} catch (Exception $e) {
    echo show_alert('حدث خطأ أثناء استرجاع بيانات الزيارة: ' . $e->getMessage(), 'error');
    require_once 'includes/footer.php';
    exit;
}

// تحويل نوع الزيارة إلى نص مفهوم
$visit_type_text = $visit['visit_type'] == 'full' ? 'كاملة' : 'جزئية';

// تحويل نوع الحضور إلى نص مفهوم
$attendance_type_text = 'حضوري';
if ($visit['attendance_type'] == 'remote') {
    $attendance_type_text = 'عن بعد';
} else if ($visit['attendance_type'] == 'hybrid') {
    $attendance_type_text = 'مدمج';
}
?>

<style>
    .info-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .info-item {
        margin-bottom: 12px;
        padding: 12px;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        background-color: #f9fafb;
    }
    
    .info-label {
        font-weight: 600;
        color: #374151;
        display: block;
        margin-bottom: 4px;
        font-size: 0.875rem;
    }
    
    .info-value {
        font-weight: 500;
        color: #111827;
        font-size: 0.95rem;
    }
    
    .notes-box {
        border: 1px solid #ddd;
        padding: 10px;
        margin-bottom: 15px;
        background-color: #f9f9f9;
        min-height: 50px;
        border-radius: 6px;
    }
    
    .final-score {
        font-size: 3rem;
        font-weight: bold;
        color: #0284c7;
        margin: 10px 0;
        text-align: center;
    }
    
    .final-grade {
        display: inline-block;
        padding: 6px 18px;
        border-radius: 20px;
        background-color: #dbeafe;
        color: #1e40af;
        font-weight: bold;
        margin-bottom: 20px;
        font-size: 1.2rem;
    }
    
    .score-box {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 5px;
        font-weight: bold;
        min-width: 80px;
        text-align: center;
    }
    
    .score-4 {
        background-color: #d1fae5;
        color: #065f46;
    }
    
    .score-3 {
        background-color: #dbeafe;
        color: #1e40af;
    }
    
    .score-2 {
        background-color: #fef3c7;
        color: #92400e;
    }
    
    .score-1 {
        background-color: #fee2e2;
        color: #b91c1c;
    }
    
    .score-0 {
        background-color: #fee2e2;
        color: #b91c1c;
    }
    
    .score-null {
        background-color: #f3f4f6;
        color: #6b7280;
        border: 2px dashed #d1d5db;
    }
    
    .indicator-table {
        border-collapse: collapse;
        width: 100%;
        background-color: white;
    }
    
    .indicator-table th {
        background-color: #f9fafb;
        color: #374151;
        text-align: center;
        padding: 12px;
        font-weight: 600;
        border: 1px solid #e5e7eb;
        font-size: 14px;
    }
    
    .indicator-table th:first-child {
        text-align: right;
        width: 50%;
    }
    
    .indicator-table th:nth-child(2) {
        width: 15%;
    }
    
    .indicator-table th:last-child {
        text-align: right;
        width: 35%;
    }
    
    .indicator-table td {
        padding: 12px;
        border: 1px solid #e5e7eb;
        vertical-align: top;
    }
    
    .indicator-table td:first-child {
        text-align: right;
        font-weight: 500;
        color: #374151;
        line-height: 1.5;
    }
    
    .indicator-table td:nth-child(2) {
        text-align: center;
    }
    
    .indicator-table td:last-child {
        text-align: right;
        color: #374151;
        line-height: 1.5;
    }
    
    .indicator-table tr:hover {
        background-color: #f9fafb;
    }
    
    .domain-section {
        margin-bottom: 30px;
        border: 1px solid #e5e7eb;
        background-color: white;
    }
    
    .domain-heading {
        background-color: #f3f4f6;
        color: #374151;
        padding: 12px 20px;
        margin-bottom: 0;
        border-radius: 0;
        border: 1px solid #e5e7eb;
        font-size: 16px;
        font-weight: 600;
        text-align: center;
    }
    
    .section-heading {
        border-bottom: 2px solid #0284c7;
        padding-bottom: 8px;
        margin-bottom: 20px;
        color: #0284c7;
    }
    
    .visit-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .tab-buttons {
        display: flex;
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 20px;
    }
    
    .tab-button {
        padding: 10px 20px;
        background-color: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-bottom: none;
        border-radius: 6px 6px 0 0;
        margin-left: 5px;
        cursor: pointer;
        font-weight: 500;
    }
    
    .tab-button.active {
        background-color: #fff;
        border-bottom: 1px solid #fff;
        margin-bottom: -1px;
        font-weight: bold;
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .section-separator {
        height: 1px;
        background-color: #e5e7eb;
        margin: 30px 0;
    }
    
    .result-box {
        text-align: center;
        padding: 20px;
        margin: 20px 0;
        border-top: 1px solid #e5e7eb;
        border-bottom: 1px solid #e5e7eb;
    }
</style>

<!-- إضافة مكتبة Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="visit-header">
    <h1 class="text-2xl font-bold">تقرير الزيارة الصفية</h1>
    <div class="flex space-x-2 space-x-reverse">
        <a href="visits.php" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">
            العودة للزيارات
        </a>
        <a href="print_visit.php?id=<?= $visit_id ?>" class="bg-primary-600 text-white px-4 py-2 rounded-md hover:bg-primary-700 transition-colors">
            طباعة التقرير
        </a>
    </div>
</div>

<div class="tab-buttons">
    <div class="tab-button active" onclick="openTab('basic-info')">المعلومات الأساسية</div>
    <div class="tab-button" onclick="openTab('details')">تفاصيل التقييم</div>
</div>

<div id="basic-info" class="tab-content active">
    <!-- معلومات الزيارة الأساسية -->
    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4 pb-2 border-b border-gray-200">معلومات الزيارة</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="info-item">
                <span class="info-label">المدرسة:</span>
                <span class="info-value"><?= htmlspecialchars($visit['school_name']) ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">المعلم:</span>
                <span class="info-value"><?= htmlspecialchars($visit['teacher_name']) ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">المادة:</span>
                <span class="info-value"><?= htmlspecialchars($visit['subject_name']) ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">الصف:</span>
                <span class="info-value"><?= htmlspecialchars($visit['grade_name']) ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">الشعبة:</span>
                <span class="info-value"><?= htmlspecialchars($visit['section_name']) ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">المرحلة:</span>
                <span class="info-value"><?= htmlspecialchars($visit['level_name']) ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">نوع الزائر:</span>
                <span class="info-value"><?= htmlspecialchars($visit['visitor_type_name']) ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">اسم الزائر:</span>
                <span class="info-value"><?= htmlspecialchars($visit['visitor_person_name']) ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">تاريخ الزيارة:</span>
                <span class="info-value"><?= format_date_ar($visit['visit_date']) ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">نوع الزيارة:</span>
                <span class="info-value"><?= $visit_type_text ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">نوع الحضور:</span>
                <span class="info-value"><?= $attendance_type_text ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">استخدام المعمل:</span>
                <span class="info-value"><?= bool_to_ar($visit['has_lab']) ?></span>
            </div>
        </div>
    </div>

    <!-- نتيجة التقييم -->
    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4 pb-2 border-b border-gray-200">نتيجة التقييم</h2>
        
        <div class="flex flex-col lg:flex-row items-center justify-center space-y-6 lg:space-y-0 lg:space-x-8 lg:space-x-reverse">
            <div class="w-full lg:w-1/3">
                <?php
                $bg_color = 'bg-gray-100';
                $text_color = 'text-gray-800';
                
                // تحديد الألوان بناءً على النسبة المئوية بدلاً من المتوسط
                if ($percentage_score >= 90) {
                    $bg_color = 'bg-green-100';
                    $text_color = 'text-green-800';
                } else if ($percentage_score >= 80) {
                    $bg_color = 'bg-blue-100';
                    $text_color = 'text-blue-800';
                } else if ($percentage_score >= 65) {
                    $bg_color = 'bg-yellow-100';
                    $text_color = 'text-yellow-800';
                } else if ($percentage_score >= 50) {
                    $bg_color = 'bg-orange-100';
                    $text_color = 'text-orange-800';
                } else {
                    $bg_color = 'bg-red-100';
                    $text_color = 'text-red-800';
                }
                ?>
                <div class="text-center">
                    <div class="final-score text-3xl font-bold <?= $text_color ?> mb-2">
                        <?= number_format($average_score, 2) ?> (<?= $percentage_score ?>%)
                    </div>
                    <div class="final-grade <?= $bg_color ?> <?= $text_color ?> px-4 py-2 rounded-lg font-semibold">
                        <?= $grade ?>
                    </div>
                </div>
            </div>
            
            <div class="w-full lg:w-2/3">
                <canvas id="scoreChart" width="400" height="250"></canvas>
            </div>
        </div>
    </div>

    <!-- الملاحظات والتوصيات -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- ملاحظات عامة -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h3 class="text-md font-semibold mb-3 pb-2 border-b border-gray-200 text-gray-700">ملاحظات عامة</h3>
            <div class="text-sm text-gray-600 bg-gray-50 p-3 rounded border">
                <?= nl2br(htmlspecialchars($visit['general_notes'] ?: 'لا توجد ملاحظات')) ?>
            </div>
        </div>

        <!-- توصيات الزيارة -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h3 class="text-md font-semibold mb-3 pb-2 border-b border-gray-200 text-gray-700">توصيات الزيارة</h3>
            <div class="text-sm text-gray-600 bg-blue-50 p-3 rounded border border-blue-200">
                <?= nl2br(htmlspecialchars($visit['recommendation_notes'] ?: 'لا توجد توصيات')) ?>
            </div>
        </div>

        <!-- ملاحظات التقدير -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h3 class="text-md font-semibold mb-3 pb-2 border-b border-gray-200 text-gray-700">ملاحظات التقدير</h3>
            <div class="text-sm text-gray-600 bg-green-50 p-3 rounded border border-green-200">
                <?= nl2br(htmlspecialchars($visit['appreciation_notes'] ?: 'لا توجد ملاحظات')) ?>
            </div>
        </div>
    </div>
</div>

<div id="details" class="tab-content">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-6">تفاصيل التقييم حسب المجال</h2>
        
        <div class="space-y-6">
            <?php foreach ($evaluations_by_domain as $domain_id => $domain_evaluations): ?>
                <div class="domain-section">
                    <h3 class="domain-heading">
                        <?= htmlspecialchars($domains[$domain_id]) ?>
                    </h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full indicator-table">
                            <thead>
                                <tr>
                                    <th>المؤشر</th>
                                    <th>التقييم</th>
                                    <th>التوصية</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($domain_evaluations as $eval): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($eval['indicator_text']) ?></td>
                                        <td>
                                            <?php
                                            // التعامل مع القيم NULL والرقمية بشكل صحيح
                                            $score = $eval['score']; // الاحتفاظ بالقيمة كما هي (NULL أو رقم)
                                            $score_text = '';
                                            $score_class = '';
                                            
                                            // فحص القيمة NULL أولاً قبل التحويل إلى رقم
                                            if ($score === null) {
                                                $score_text = 'لم يتم قياسه';
                                                $score_class = 'score-null';
                                            } else {
                                                $score = (int)$score; // تحويل إلى رقم فقط إذا لم تكن NULL
                                                switch ($score) {
                                                    case 3:
                                                        $score_text = 'ممتاز';
                                                        $score_class = 'score-3';
                                                        break;
                                                    case 2:
                                                        $score_text = 'جيد';
                                                        $score_class = 'score-2';
                                                        break;
                                                    case 1:
                                                        $score_text = 'مقبول';
                                                        $score_class = 'score-1';
                                                        break;
                                                    case 0:
                                                        $score_text = 'ضعيف';
                                                        $score_class = 'score-0';
                                                        break;
                                                    default:
                                                        $score_text = 'غير مقاس';
                                                        $score_class = 'score-null';
                                                        break;
                                                }
                                            }
                                            ?>
                                            <span class="score-box <?= $score_class ?>">
                                                <?= $score_text ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            // استرجاع كل التوصيات المختارة للمؤشر بدون تكرار
                                            $recommendations_sql = "
                                                SELECT DISTINCT r.text as recommendation_text 
                                                FROM visit_evaluations ve
                                                JOIN recommendations r ON ve.recommendation_id = r.id
                                                WHERE ve.visit_id = ? AND ve.indicator_id = ? AND ve.recommendation_id IS NOT NULL
                                            ";
                                            $all_recommendations = query($recommendations_sql, [$visit_id, $eval['indicator_id']]);
                                            
                                            // استرجاع التوصيات النصية المخصصة
                                            $custom_recommendations_sql = "
                                                SELECT DISTINCT custom_recommendation 
                                                FROM visit_evaluations 
                                                WHERE visit_id = ? AND indicator_id = ? 
                                                AND custom_recommendation IS NOT NULL AND custom_recommendation != ''
                                            ";
                                            $custom_recommendations = query($custom_recommendations_sql, [$visit_id, $eval['indicator_id']]);
                                            
                                            if (!empty($all_recommendations)): 
                                            ?>
                                                <div class="space-y-2">
                                                    <?php foreach ($all_recommendations as $rec): ?>
                                                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-2 text-sm">
                                                            <span class="text-blue-800">• <?= htmlspecialchars($rec['recommendation_text']) ?></span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($custom_recommendations)): ?>
                                                <div class="mt-2 space-y-2">
                                                    <?php foreach ($custom_recommendations as $rec): ?>
                                                        <div class="bg-green-50 border border-green-200 rounded-lg p-2 text-sm">
                                                            <span class="text-green-800">• <?= htmlspecialchars($rec['custom_recommendation']) ?></span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php elseif (empty($all_recommendations) && empty($custom_recommendations)): ?>
                                                <span class="text-gray-400 text-sm">لا توجد توصية</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function openTab(tabName) {
    // إخفاء كل المحتويات
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(content => {
        content.classList.remove('active');
    });
    
    // إزالة التنشيط من كل الأزرار
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.classList.remove('active');
    });
    
    // تنشيط المحتوى والزر المطلوب
    document.getElementById(tabName).classList.add('active');
    const activeButton = document.querySelector(`.tab-button[onclick="openTab('${tabName}')"]`);
    if (activeButton) {
        activeButton.classList.add('active');
    }
}

// إنشاء الرسم البياني الدائري عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    const scorePercentage = <?= $percentage_score ?>;
    const remainingPercentage = 100 - scorePercentage;
    
    // تحديد اللون بناءً على النتيجة
    let chartColor = '#ef4444'; // أحمر للنتيجة المنخفضة
    
    if (scorePercentage >= 90) {
        chartColor = '#10b981'; // أخضر للممتاز
    } else if (scorePercentage >= 80) {
        chartColor = '#3b82f6'; // أزرق للجيد جدًا
    } else if (scorePercentage >= 65) {
        chartColor = '#f59e0b'; // أصفر ذهبي للجيد
    } else if (scorePercentage >= 50) {
        chartColor = '#f97316'; // برتقالي للمقبول
    }
    
    const ctx = document.getElementById('scoreChart').getContext('2d');
    const scoreChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['نسبة التقييم', 'المتبقي'],
            datasets: [{
                data: [scorePercentage, remainingPercentage],
                backgroundColor: [
                    chartColor,
                    '#e5e7eb'
                ],
                borderColor: [
                    chartColor,
                    '#e5e7eb'
                ],
                borderWidth: 1,
                cutout: '70%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    rtl: true,
                    labels: {
                        font: {
                            family: 'Tajawal, sans-serif'
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.raw + '%';
                        }
                    }
                }
            }
        }
    });
    
    // إضافة نص في وسط الرسم البياني
    Chart.register({
        id: 'centerText',
        beforeDraw: function(chart) {
            const width = chart.width;
            const height = chart.height;
            const ctx = chart.ctx;

            ctx.restore();
            const fontSize = (height / 140).toFixed(2);
            ctx.font = fontSize + 'em Tajawal, sans-serif';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = '#333';

            const text = scorePercentage + '%';
            const textX = Math.round((width - ctx.measureText(text).width) / 2);
            const textY = height / 2;

            ctx.fillText(text, textX, textY);
            ctx.save();
        }
    });
});
</script>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?> 