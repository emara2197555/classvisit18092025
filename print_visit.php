<?php
// بدء التخزين المؤقت للمخرجات
ob_start();

// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// التحقق من وجود معرف الزيارة
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // إعادة التوجيه إلى صفحة الزيارات
    header('Location: visits.php');
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
            vp.name as visitor_person_name,
            ay.name as academic_year_name
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
        LEFT JOIN
            academic_years ay ON v.academic_year_id = ay.id
        WHERE 
            v.id = ?
    ";
    
    $visit = query_row($visit_sql, [$visit_id]);
    
    if (!$visit) {
        throw new Exception('الزيارة غير موجودة');
    }
    
    // جلب تفاصيل التقييم لهذه الزيارة (صف واحد لكل مؤشر)، مع استبعاد مجال المعمل إذا لم يكن مُفعلاً
    $evaluation_sql = "
        SELECT 
            ei.id as indicator_id,
            ei.name as indicator_text,
            ei.domain_id,
            ed.name as domain_name,
            MAX(ve.score) as score,
            MAX(ve.custom_recommendation) as custom_recommendation,
            MAX(r.text) as recommendation_text
        FROM 
            evaluation_indicators ei
        JOIN 
            evaluation_domains ed ON ei.domain_id = ed.id
        LEFT JOIN 
            visit_evaluations ve ON ve.indicator_id = ei.id AND ve.visit_id = ?
        LEFT JOIN 
            recommendations r ON ve.recommendation_id = r.id
        WHERE 
            EXISTS (SELECT 1 FROM visit_evaluations WHERE visit_id = ? AND indicator_id = ei.id)
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
} catch (Exception $e) {
    // في حالة وجود خطأ، إعادة التوجيه إلى صفحة الزيارات
    header('Location: visits.php');
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

// مزيج نوع الزيارة والحضور
$visit_attendance_type = $visit_type_text . '/' . $attendance_type_text;

// استخراج التاريخ واليوم
$date_obj = new DateTime($visit['visit_date']);
$day_names = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
$day_name = $day_names[$date_obj->format('w')];
$date_formatted = $date_obj->format('Y/m/d');

// حساب متوسط الدرجات مع استبعاد المؤشرات التي لم يتم قياسها
$total_scores = 0;
$valid_indicators_count = 0;

// استعلام لجلب جميع التقييمات لهذه الزيارة (مع استبعاد مجال المعمل إذا لم يكن مُفعلاً)
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
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استمارة ملاحظة صفية لأداء معلم - <?= htmlspecialchars($visit['teacher_name']) ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        @media print {
            @page {
                size: A4;
                margin: 1cm;
            }
            
            body {
                margin: 0;
                font-family: 'Cairo', sans-serif;
                font-size: 8pt; /* تكبير لتعبئة مساحة الصفحة */
                color: #333;
                line-height: 1.25;
            }
            
            h1, h2, h3, h4 {
                margin-top: 0;
                margin-bottom: 0;
                margin-right: 0;

            }
            
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 6px; /* فراغ بسيط بين الجداول */
            }
            
            table, th, td {
                border: 1px solid #000;
            }
            
            th, td {
                padding: 4px; /* زيادة الحشوة لتمدد الصفوف عمودياً */
                text-align: right;
            }
            
            th {
                background-color: #f2f2f2;
                color: #000;
            }
            
            .print-header {
                text-align: center;
                margin-bottom: 8px;
                padding-bottom: 6px;
                border-bottom: 1px solid #000;
            }
            
            .print-section {
                margin-bottom: 8px;
            }
            
            .print-footer {
                margin-top: 20px;
                text-align: center;
                border-top: 1px solid #000;
                padding-top: 10px;
                font-size: 6pt;
            }
            
            .score-box {
                display: inline-block;
                width: 15px;
                height: 15px;
                border: 1px solid #000;
                text-align: center;
                line-height: 15px;
            }
            
            .text-center {
                text-align: center;
            }
            
            .text-right {
                text-align: right;
            }
            
            .hidden-print {
                display: none;
            }
            
            .main-heading {
                font-size: 11pt;
                font-weight: bold;
                margin-bottom: 6px;
                text-align: center;
            }
            
            .info-table th, .info-table td {
                padding: 2px 3px;
            }
            
            .indicator-table th {
                text-align: center;
                font-weight: bold;
                padding: 4px;
            }
            
            .domain-heading {
                background-color: #ddd;
                font-weight: bold;
                padding: 2px;
                text-align: center;
            }
            
            .page-break {
                page-break-after: always;
            }
            
            .watermark {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) rotate(-45deg);
                font-size: 70pt;
                color: rgba(200, 200, 200, 0.2);
                z-index: -1;
            }
            
            .checkbox {
                width: 5px;
                height: 5px;
                border: 1px solid #000;
                margin: 0px;
                display: inline-block;
                text-align: center;
            }
            
            div[style*="font-size: 6pt"] {
                font-size: 4pt !important;
            }
        }

        /* تنسيقات للشاشة فقط */
        @media screen {
            body {
                font-family: 'Cairo', sans-serif;
                font-size: 10pt;
                color: #333;
                line-height: 1.3;
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
                background-color: #f0f0f0;
            }
            
            .print-container {
                background-color: white;
                padding: 30px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            }
            
            h1, h2, h3, h4 {
                margin-top: 0;
            }
            
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            
            table, th, td {
                border: 1px solid #000;
            }
            
            th, td {
                padding: 6px;
                text-align: right;
            }
            
            th {
                background-color: #f2f2f2;
                color: #000;
            }
            
            .print-header {
                text-align: center;
                margin-bottom: 10px;
                padding-bottom: 5px;
                border-bottom: 1px solid #000;
            }
            
            .print-section {
                margin-bottom: 15px;
            }
            
            .print-footer {
                margin-top: 20px;
                text-align: center;
                border-top: 1px solid #000;
                padding-top: 10px;
                font-size: 9pt;
            }
            
            .score-box {
                display: inline-block;
                width: 15px;
                height: 15px;
                border: 1px solid #000;
                text-align: center;
                line-height: 15px;
            }
            
            .text-center {
                text-align: center;
            }
            
            .text-right {
                text-align: right;
            }
            
            .hidden-print {
                display: block;
                text-align: center;
                margin: 20px 0;
            }
            
            .hidden-print button {
                background-color: #0284c7;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 5px;
                cursor: pointer;
                font-family: 'Cairo', sans-serif;
                font-size: 10pt;
                margin: 0 5px;
            }
            
            .hidden-print button:hover {
                background-color: #0369a1;
            }
            
            .main-heading {
                font-size: 13pt;
                font-weight: bold;
                margin-bottom: 5px;
                text-align: center;
            }
            
            .info-table th, .info-table td {
                padding: 3px 5px;
            }
            
            .indicator-table th {
                text-align: center;
                font-weight: bold;
            }
            
            .domain-heading {
                background-color: #ddd;
                font-weight: bold;
                padding: 5px;
                text-align: center;
            }
            
            .watermark {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) rotate(-45deg);
                font-size: 100pt;
                color: rgba(200, 200, 200, 0.2);
                z-index: -1;
            }
            
            .checkbox {
                width: 15px;
                height: 15px;
                border: 1px solid #000;
                margin: 2px;
                display: inline-block;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="hidden-print">
        <button onclick="window.print()">طباعة الاستمارة</button>
        <button onclick="window.location.href='visits.php'">العودة للزيارات</button>
        <button onclick="window.location.href='send_visit_email.php?id=<?= $visit_id ?>'">إرسال التقرير بالبريد الإلكتروني</button>
    </div>
    
    <div class="print-container">
        <div class="watermark">Page</div>
        
        <div class="print-header">
            <h1 class="main-heading">استمارة ملاحظة صفية لأداء معلم للعام الأكاديمي <?= htmlspecialchars($visit['academic_year_name']) ?></h1>
        </div>
        
        <div class="print-section">
            <table class="info-table">
                <tr>
                    <th colspan="7" style="text-align: center; background-color: #ddd;">أولاً : المعلومات الأساسية</th>
                </tr>
                <tr>
                    <th style="width: 15%;">المدرسة</th>
                    <td style="width: 35%;"><?= htmlspecialchars($visit['school_name']) ?></td>
                    <th style="width: 15%;">اليوم</th>
                    <td style="width: 15%;"><?= $day_name ?></td>
                    <th style="width: 10%;">التاريخ</th>
                    <td style="width: 10%;"><?= $date_formatted ?></td>
                </tr>
                <tr>
                    <th>المادة</th>
                    <td><?= htmlspecialchars($visit['subject_name']) ?></td>
                    <th>رقم الشعبة</th>
                    <td><?= htmlspecialchars($visit['section_name']) ?></td>
                    <th>الصف</th>
                    <td><?= htmlspecialchars($visit['grade_name']) ?></td>
                </tr>
                <tr>
                    <th>المعلم</th>
                    <td><?= htmlspecialchars($visit['teacher_name']) ?></td>
                    <th>الزائر</th>
                    <td colspan="3"><?= htmlspecialchars($visit['visitor_person_name']?? '0') ?></td>



                </tr>
                <tr>
                    <th>نوع الزيارة</th>
                    <td><?= $visit_attendance_type ?></td>
                    <th>الموضوع</th>
                    <td colspan="3"><?= htmlspecialchars($visit['topic'] ?? '0') ?></td>
                </tr>
                <tr>
                    <th colspan="6">متابعة توصية من الزيارة السابقة</th>
                </tr>
            </table>
        </div>
        
        <div class="print-section">
            <table class="indicator-table">
                <tr>
                    <th colspan="8" style="text-align: center; background-color: #ddd;">ثانياً : مجالات تقييم الأداء</th>
                </tr>
                <tr>
                   <th rowspan="2" style="writing-mode: vertical-rl; transform: rotate(180deg); text-align: center; width: 10%;">المجال</th>
                   <th rowspan="2" style="width: 30%;">مؤشرات الأداء</th>
                   <th colspan="5" style="width: 20%;">الدرجة التقييمية</th>
                   <th rowspan="2" style="width: 40%;">التوصية</th>
                </tr>
                <tr>
                    <th style="writing-mode: vertical-rl; transform: rotate(180deg); text-align: center; width: 30px;">ممتاز</th>
                    <th style="writing-mode: vertical-rl; transform: rotate(180deg); text-align: center; width: 30px;">جيد</th>
                    <th style="writing-mode: vertical-rl; transform: rotate(180deg); text-align: center; width: 30px;">مقبول</th>
                    <th style="writing-mode: vertical-rl; transform: rotate(180deg); text-align: center; width: 30px;">ضعيف</th>
                    <th style="writing-mode: vertical-rl; transform: rotate(180deg); text-align: center; width: 30px;">لم يتم قياسه</th>
                </tr>
                
                <?php 
                $current_domain = '';
                $previous_domain = '';
                $domain_count = 0;
                
                foreach ($evaluations as $index => $eval): 
                    $current_domain = $eval['domain_name'];
                    $is_last_in_domain = (!isset($evaluations[$index + 1]) || $evaluations[$index + 1]['domain_name'] != $current_domain);
                    
                    // إذا تغير المجال، نعرض صف جديد للمجال
                    if ($current_domain != $previous_domain):
                        $domain_count++;
                        $domain_rowspan = 0;
                        
                        // حساب عدد المؤشرات في هذا المجال
                        foreach ($evaluations as $count_eval) {
                            if ($count_eval['domain_name'] == $current_domain) {
                                $domain_rowspan++;
                            }
                        }
                ?>
                <tr>
                    <td rowspan="<?= $domain_rowspan ?>" class="domain-heading" style="writing-mode: vertical-rl; transform: rotate(180deg); text-align: center;"><?= htmlspecialchars($current_domain) ?></td>
                    <td><?= htmlspecialchars($eval['indicator_text']) ?></td>
                    <td class="text-center"><?= ($eval['score'] == 3) ? '✓' : '' ?></td>
                    <td class="text-center"><?= ($eval['score'] == 2) ? '✓' : '' ?></td>
                    <td class="text-center"><?= ($eval['score'] == 1) ? '✓' : '' ?></td>
                    <td class="text-center"><?= ($eval['score'] == 0 && $eval['score'] !== null) ? '✓' : '' ?></td>
                    <td class="text-center"><?= ($eval['score'] === null) ? '✓' : '' ?></td>
                    <td><?= htmlspecialchars(($eval['custom_recommendation'] ?? '') !== '' ? $eval['custom_recommendation'] : ($eval['recommendation_text'] ?? '')) ?></td>
                </tr>
                <?php if ((int)$eval['domain_id'] === 3 && $is_last_in_domain): ?>
                <tr>
                    <td colspan="8" style="border:0; padding:0; height:0;">
                        <div class="page-break" style="page-break-after: always;"></div>
                    </td>
                </tr>
                <?php endif; ?>
                <?php else: ?>
                <tr>
                    <td><?= htmlspecialchars($eval['indicator_text']) ?></td>
                    <td class="text-center"><?= ($eval['score'] == 3) ? '✓' : '' ?></td>
                    <td class="text-center"><?= ($eval['score'] == 2) ? '✓' : '' ?></td>
                    <td class="text-center"><?= ($eval['score'] == 1) ? '✓' : '' ?></td>
                    <td class="text-center"><?= ($eval['score'] == 0 && $eval['score'] !== null) ? '✓' : '' ?></td>
                    <td class="text-center"><?= ($eval['score'] === null) ? '✓' : '' ?></td>
                    <td><?= htmlspecialchars(($eval['custom_recommendation'] ?? '') !== '' ? $eval['custom_recommendation'] : ($eval['recommendation_text'] ?? '')) ?></td>
                </tr>
                <?php if ((int)$eval['domain_id'] === 3 && $is_last_in_domain): ?>
                <tr>
                    <td colspan="8" style="border:0; padding:0; height:0;">
                        <div class="page-break" style="page-break-after: always;"></div>
                    </td>
                </tr>
                <?php endif; ?>
                <?php 
                    endif;
                    $previous_domain = $current_domain;
                endforeach; 
                ?>
            </table>
        </div>
        
        <div class="print-section">
            <table>
                <tr>
                    <th colspan="2" style="background-color: #ddd;">ملاحظات وتوصيات عامة</th>
                </tr>
                <tr>
                </tr>
                <tr>
    <td style="height: 30px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;">
        أشكر المعلم على: <?= htmlspecialchars($visit['appreciation_notes'] ?: '') ?>
    </td>
</tr>
<tr>
    <td style="height: 30px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;">
        وأوصي بما يلي: <?= htmlspecialchars($visit['recommendation_notes'] ?: '') ?>
    </td>
</tr>
            </table>
        </div>
        
        <div class="print-footer">
            <table style="border: none;">
                <tr>
                    <td style="border: none; text-align: center; width: 50%;">توقيع المعلم</td>
                    <td style="border: none; text-align: center; width: 50%;">توقيع المنسق</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; height: 40px;"></td>
                    <td style="border: 1px solid #000; height: 40px;"></td>
                </tr>
            </table>
            
            <div style="margin-top: 10px; font-size: 8pt; text-align: center;">
                <p>الرؤية : متعلم ريادي لتنمية مستدامة</p>
<p>الرسالة: نرسي بيئة تعليمية شاملة ومبتكرة تعزز القيم والأخلاق وتؤهل المتعلم بمهارات عالية; لإعداد جيل واعٍ قادرٍ على بناء مجتمع متقدم واقتصاد مزدهر</p>
                </div>
        </div>
        
    </div>
</body>
</html> 