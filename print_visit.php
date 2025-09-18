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
            vt.name_en as visitor_type_name_en,
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
    
    // جلب جميع المؤشرات أولاً
    $indicators_sql = "
        SELECT 
            ei.id as indicator_id,
            ei.name as indicator_text,
            ei.name_en as indicator_text_en,
            ei.domain_id,
            ed.name as domain_name,
            ed.name_en as domain_name_en
        FROM 
            evaluation_indicators ei
        JOIN 
            evaluation_domains ed ON ei.domain_id = ed.id
        ORDER BY
            ei.domain_id, ei.sort_order, ei.id
    ";
    
    $indicators = query($indicators_sql);
    
    // الآن جلب التقييمات لكل مؤشر
    $evaluations = [];
    foreach ($indicators as $indicator) {
        $indicator_eval = $indicator;
        
        // جلب النقاط
        $score_sql = "SELECT score FROM visit_evaluations WHERE visit_id = ? AND indicator_id = ? LIMIT 1";
        $score_result = query($score_sql, [$visit_id, $indicator['indicator_id']]);
        $indicator_eval['score'] = !empty($score_result) ? $score_result[0]['score'] : null;
        
        // جلب التوصيات الجاهزة
        $recommendations_sql = "
            SELECT DISTINCT r.text, r.text_en 
            FROM visit_evaluations ve 
            JOIN recommendations r ON ve.recommendation_id = r.id 
            WHERE ve.visit_id = ? AND ve.indicator_id = ? AND ve.recommendation_id IS NOT NULL
        ";
        $recommendations = query($recommendations_sql, [$visit_id, $indicator['indicator_id']]);
        $indicator_eval['all_recommendations'] = $recommendations;
        
        // جلب التوصيات المخصصة
        $custom_recommendations_sql = "
            SELECT DISTINCT custom_recommendation 
            FROM visit_evaluations 
            WHERE visit_id = ? AND indicator_id = ? AND custom_recommendation IS NOT NULL AND custom_recommendation != ''
        ";
        $custom_recommendations = query($custom_recommendations_sql, [$visit_id, $indicator['indicator_id']]);
        $indicator_eval['all_custom_recommendations'] = array_column($custom_recommendations, 'custom_recommendation');
        
        $evaluations[] = $indicator_eval;
    }
    
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

// تحديد ما إذا كانت المادة إنجليزية (نقل هذا قبل استخدامه)
$subject_is_english = preg_match('/(english|انج|إنج|الإنج|الانجليزية|الإنجليزية)/i', $visit['subject_name'] ?? '');

// تحويل نوع الزيارة إلى نص مفهوم
if ($subject_is_english) {
    $visit_type_text = $visit['visit_type'] == 'full' ? 'Full' : 'Partial';
} else {
    $visit_type_text = $visit['visit_type'] == 'full' ? 'كاملة' : 'جزئية';
}

// تحويل نوع الحضور إلى نص مفهوم
if ($subject_is_english) {
    $attendance_type_text = 'In-person';
    if ($visit['attendance_type'] == 'remote') {
        $attendance_type_text = 'Remote';
    } else if ($visit['attendance_type'] == 'hybrid') {
        $attendance_type_text = 'Hybrid';
    }
} else {
    $attendance_type_text = 'حضوري';
    if ($visit['attendance_type'] == 'remote') {
        $attendance_type_text = 'عن بعد';
    } else if ($visit['attendance_type'] == 'hybrid') {
        $attendance_type_text = 'مدمج';
    }
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

// متغير اللغة تم تعريفه مسبقاً

// إضافة ترجمة النصوص للطباعة
$print_texts = [
    'form_title' => $subject_is_english ? 'Classroom Observation Form for Teacher Performance' : 'استمارة ملاحظة صفية لأداء معلم',
    'basic_info' => $subject_is_english ? 'Basic Information' : 'المعلومات الأساسية',
    'school' => $subject_is_english ? 'School' : 'المدرسة',
    'day' => $subject_is_english ? 'Day' : 'اليوم',
    'date' => $subject_is_english ? 'Date' : 'التاريخ',
    'subject' => $subject_is_english ? 'Subject' : 'المادة',
    'section_number' => $subject_is_english ? 'Section No.' : 'رقم الشعبة',
    'grade' => $subject_is_english ? 'Grade' : 'الصف',
    'teacher' => $subject_is_english ? 'Teacher' : 'المعلم',
    'visitor' => $subject_is_english ? 'Visitor' : 'الزائر',
    'teacher_signature' => $subject_is_english ? 'Teacher Signature' : 'توقيع المعلم',
    'academic_year' => $subject_is_english ? 'for Academic Year' : 'للعام الأكاديمي'
];
?>
<!DOCTYPE html>
<html lang="<?= $subject_is_english ? 'en' : 'ar' ?>" dir="<?= $subject_is_english ? 'ltr' : 'rtl' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $print_texts['form_title'] ?> - <?= htmlspecialchars($visit['teacher_name']) ?></title>
    
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
                table-layout: fixed; /* تخطيط ثابت لمنع تمدد الأعمدة */
                max-width: 100%;
            }
            
            table, th, td {
                border: 1px solid #000;
            }
            
            th, td {
                padding: 4px; /* زيادة الحشوة لتمدد الصفوف عمودياً */
                text-align: right;
                word-wrap: break-word;
                overflow-wrap: break-word;
                vertical-align: top;
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
            
            .indicator-table td {
                vertical-align: top;
                padding: 3px;
            }
            
            .indicator-table {
                table-layout: fixed;
                width: 100%;
            }
            
            .indicator-table td:last-child {
                max-width: 25% !important;
                width: 25% !important;
                word-wrap: break-word;
                word-break: break-word;
                overflow-wrap: break-word;
                white-space: normal;
                font-size: 8px;
                line-height: 1.2;
                padding: 2px;
            }
            
            .indicator-table th:last-child {
                width: 25% !important;
                max-width: 25% !important;
            }
            
            /* تحسينات إضافية للطباعة */
            @media print {
                .indicator-table {
                    table-layout: fixed !important;
                    width: 100% !important;
                }
                
                .indicator-table td:last-child {
                    max-width: 22% !important;
                    width: 22% !important;
                    font-size: 7px !important;
                    line-height: 1.1 !important;
                    padding: 1px !important;
                    overflow: hidden;
                }
                
                .indicator-table th:last-child {
                    width: 22% !important;
                    max-width: 22% !important;
                    font-size: 8px !important;
                }
                
                /* منع خروج النص عن حدود الصفحة */
                * {
                    box-sizing: border-box;
                }
                
                body {
                    overflow-x: hidden;
                }
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
            <h1 class="main-heading">
                <?= $print_texts['form_title'] ?> 
                <?= $print_texts['academic_year'] ?> 
                <?= htmlspecialchars($visit['academic_year_name']) ?>
            </h1>
        </div>
        
        <div class="print-section">
            <table class="info-table">
                <tr>
                    <th colspan="7" style="text-align: center; background-color: #ddd;">
                        <?= $subject_is_english ? 'First: Basic Information' : 'أولاً : المعلومات الأساسية' ?>
                    </th>
                </tr>
                <tr>
                    <th style="width: 15%;"><?= $print_texts['school'] ?></th>
                    <td style="width: 35%;"><?= htmlspecialchars($visit['school_name']) ?></td>
                    <th style="width: 15%;"><?= $print_texts['day'] ?></th>
                    <td style="width: 15%;"><?= $day_name ?></td>
                    <th style="width: 10%;"><?= $print_texts['date'] ?></th>
                    <td style="width: 10%;"><?= $date_formatted ?></td>
                </tr>
                <tr>
                    <th><?= $print_texts['subject'] ?></th>
                    <td><?= htmlspecialchars($visit['subject_name']) ?></td>
                    <th><?= $print_texts['section_number'] ?></th>
                    <td><?= htmlspecialchars($visit['section_name']) ?></td>
                    <th><?= $print_texts['grade'] ?></th>
                    <td><?= htmlspecialchars($visit['grade_name']) ?></td>
                </tr>
                <tr>
                    <th><?= $print_texts['teacher'] ?></th>
                    <td><?= htmlspecialchars($visit['teacher_name']) ?></td>
                    <th><?= $print_texts['visitor'] ?></th>
                    <td colspan="3"><?= htmlspecialchars($visit['visitor_person_name']?? '0') ?></td>



                </tr>
                <tr>
                    <th><?= $subject_is_english ? 'Visit Type' : 'نوع الزيارة' ?></th>
                    <td><?= $visit_attendance_type ?></td>
                    <th><?= $subject_is_english ? 'Topic' : 'الموضوع' ?></th>
                    <td colspan="3"><?= htmlspecialchars($visit['topic'] ?? '0') ?></td>
                </tr>
                <tr>
                    <th colspan="6"><?= $subject_is_english ? 'Follow-up recommendation from previous visit' : 'متابعة توصية من الزيارة السابقة' ?></th>
                </tr>
            </table>
        </div>
        
        <div class="print-section">
            <table class="indicator-table">
                <tr>
                    <th colspan="8" style="text-align: center; background-color: #ddd;">
                        <?= $subject_is_english ? 'Second: Performance Evaluation Domains' : 'ثانياً : مجالات تقييم الأداء' ?>
                    </th>
                </tr>
                <tr>
                   <th rowspan="2" style="writing-mode: vertical-rl; transform: rotate(180deg); text-align: center; width: 10%;">
                       <?= $subject_is_english ? 'Domain' : 'المجال' ?>
                   </th>
                   <th rowspan="2" style="width: 30%;">
                       <?= $subject_is_english ? 'Performance Indicators' : 'مؤشرات الأداء' ?>
                   </th>
                   <th colspan="5" style="width: 20%;">
                       <?= $subject_is_english ? 'Evaluation Grade' : 'الدرجة التقييمية' ?>
                   </th>
                   <th rowspan="2" style="width: 40%;">
                       <?= $subject_is_english ? 'Recommendation' : 'التوصية' ?>
                   </th>
                </tr>
                <tr>
                    <th style="writing-mode: vertical-rl; transform: rotate(180deg); text-align: center; width: 30px; font-size: 10px;">
                        <?= $subject_is_english ? 'Complete' : 'الأدلة مستكملة وفاعلة' ?>
                    </th>
                    <th style="writing-mode: vertical-rl; transform: rotate(180deg); text-align: center; width: 30px; font-size: 10px;">
                        <?= $subject_is_english ? 'Most' : 'تتوفر معظم الأدلة' ?>
                    </th>
                    <th style="writing-mode: vertical-rl; transform: rotate(180deg); text-align: center; width: 30px; font-size: 10px;">
                        <?= $subject_is_english ? 'Some' : 'تتوفر بعض الأدلة' ?>
                    </th>
                    <th style="writing-mode: vertical-rl; transform: rotate(180deg); text-align: center; width: 30px; font-size: 10px;">
                        <?= $subject_is_english ? 'Limited' : 'الأدلة غير متوفرة أو محدودة' ?>
                    </th>
                    <th style="writing-mode: vertical-rl; transform: rotate(180deg); text-align: center; width: 30px; font-size: 10px;">
                        <?= $subject_is_english ? 'Not measured' : 'لم يتم قياسه' ?>
                    </th>
                </tr>
                
                <?php 
                // استخدام الترجمات من قاعدة البيانات
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
                    <td rowspan="<?= $domain_rowspan ?>" class="domain-heading" style="writing-mode: vertical-rl; transform: rotate(180deg); text-align: center;">
                        <?php 
                        // استخدام الترجمة من قاعدة البيانات
                        $domain_display_name = $subject_is_english && !empty($eval['domain_name_en']) ? $eval['domain_name_en'] : $eval['domain_name'];
                        ?>
                        <?= htmlspecialchars($domain_display_name) ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($subject_is_english && !empty($eval['indicator_text_en']) ? $eval['indicator_text_en'] : $eval['indicator_text']) ?>
                    </td>
                    <td class="text-center"><?= ($eval['score'] == 3) ? '✓' : '' ?></td>
                    <td class="text-center"><?= ($eval['score'] == 2) ? '✓' : '' ?></td>
                    <td class="text-center"><?= ($eval['score'] == 1) ? '✓' : '' ?></td>
                    <td class="text-center"><?= ($eval['score'] == 0 && $eval['score'] !== null) ? '✓' : '' ?></td>
                    <td class="text-center"><?= ($eval['score'] === null) ? '✓' : '' ?></td>
                    <td style="max-width: 180px; word-wrap: break-word; word-break: break-word; overflow-wrap: break-word; white-space: normal; font-size: 9px; line-height: 1.3; padding: 3px;">
                        <?php 
                        $all_recommendations_text = [];
                        
                        // إضافة التوصيات الجاهزة
                        if (!empty($eval['all_recommendations'])) {
                            foreach ($eval['all_recommendations'] as $rec) {
                                $rec_text = $subject_is_english && !empty($rec['text_en']) ? $rec['text_en'] : $rec['text'];
                                $all_recommendations_text[] = $rec_text;
                            }
                        }
                        
                        // إضافة التوصيات المخصصة
                        if (!empty($eval['all_custom_recommendations'])) {
                            foreach ($eval['all_custom_recommendations'] as $custom_rec) {
                                $custom_prefix = $subject_is_english ? "Custom recommendation: " : "توصية مخصصة: ";
                                $all_recommendations_text[] = $custom_prefix . $custom_rec;
                            }
                        }
                        
                        // عرض جميع التوصيات مع ترقيم
                        if (!empty($all_recommendations_text)) {
                            // تقسيم التوصيات الطويلة
                            $formatted_recommendations = [];
                            $counter = 1;
                            foreach ($all_recommendations_text as $rec) {
                                // قطع النص الطويل إذا تجاوز 120 حرف للطباعة
                                if (strlen($rec) > 120) {
                                    $rec = substr($rec, 0, 117) . "...";
                                }
                                // إضافة ترقيم إذا كان هناك أكثر من توصية
                                if (count($all_recommendations_text) > 1) {
                                    $formatted_recommendations[] = $counter . ") " . $rec;
                                    $counter++;
                                } else {
                                    $formatted_recommendations[] = $rec;
                                }
                            }
                            // عرض التوصيات مع فواصل مرئية
                            echo nl2br(htmlspecialchars(implode("\n", $formatted_recommendations)));
                        }
                        ?>
                    </td>
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
                    <td>
                        <?= htmlspecialchars($subject_is_english && !empty($eval['indicator_text_en']) ? $eval['indicator_text_en'] : $eval['indicator_text']) ?>
                    </td>
                    <td class="text-center"><?= ($eval['score'] == 3) ? '✓' : '' ?></td>
                    <td class="text-center"><?= ($eval['score'] == 2) ? '✓' : '' ?></td>
                    <td class="text-center"><?= ($eval['score'] == 1) ? '✓' : '' ?></td>
                    <td class="text-center"><?= ($eval['score'] == 0 && $eval['score'] !== null) ? '✓' : '' ?></td>
                    <td class="text-center"><?= ($eval['score'] === null) ? '✓' : '' ?></td>
                    <td style="max-width: 180px; word-wrap: break-word; word-break: break-word; overflow-wrap: break-word; white-space: normal; font-size: 9px; line-height: 1.3; padding: 3px;">
                        <?php 
                        $all_recommendations_text = [];
                        
                        // إضافة التوصيات الجاهزة
                        if (!empty($eval['all_recommendations'])) {
                            foreach ($eval['all_recommendations'] as $rec) {
                                $rec_text = $subject_is_english && !empty($rec['text_en']) ? $rec['text_en'] : $rec['text'];
                                $all_recommendations_text[] = $rec_text;
                            }
                        }
                        
                        // إضافة التوصيات المخصصة
                        if (!empty($eval['all_custom_recommendations'])) {
                            foreach ($eval['all_custom_recommendations'] as $custom_rec) {
                                $custom_prefix = $subject_is_english ? "Custom recommendation: " : "توصية مخصصة: ";
                                $all_recommendations_text[] = $custom_prefix . $custom_rec;
                            }
                        }
                        
                        // عرض جميع التوصيات مع ترقيم
                        if (!empty($all_recommendations_text)) {
                            // تقسيم التوصيات الطويلة
                            $formatted_recommendations = [];
                            $counter = 1;
                            foreach ($all_recommendations_text as $rec) {
                                // قطع النص الطويل إذا تجاوز 120 حرف للطباعة
                                if (strlen($rec) > 120) {
                                    $rec = substr($rec, 0, 117) . "...";
                                }
                                // إضافة ترقيم إذا كان هناك أكثر من توصية
                                if (count($all_recommendations_text) > 1) {
                                    $formatted_recommendations[] = $counter . ") " . $rec;
                                    $counter++;
                                } else {
                                    $formatted_recommendations[] = $rec;
                                }
                            }
                            // عرض التوصيات مع فواصل مرئية
                            echo nl2br(htmlspecialchars(implode("\n", $formatted_recommendations)));
                        }
                        ?>
                    </td>
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
                    <th colspan="2" style="background-color: #ddd;">
                        <?= $subject_is_english ? 'General Notes and Recommendations' : 'ملاحظات وتوصيات عامة' ?>
                    </th>
                </tr>
                <tr>
                </tr>
                <tr>
    <td style="height: 30px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;">
        <?= $subject_is_english ? 'I thank the teacher for:' : 'أشكر المعلم على:' ?> <?= htmlspecialchars($visit['appreciation_notes'] ?: '') ?>
    </td>
</tr>
<tr>
    <td style="height: 30px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;">
        <?= $subject_is_english ? 'I recommend the following:' : 'وأوصي بما يلي:' ?> <?= htmlspecialchars($visit['recommendation_notes'] ?: '') ?>
    </td>
</tr>
            </table>
        </div>
        
        <div class="print-footer">
            <table style="border: none;">
                <tr>
                    <td style="border: none; text-align: center; width: 50%;"><?= $print_texts['teacher_signature'] ?></td>
                    <td style="border: none; text-align: center; width: 50%;">
                        <?php 
                        // تحديد نوع التوقيع حسب نوع الزائر
                        $signature_text = $subject_is_english ? 'Coordinator Signature' : 'توقيع المنسق'; // افتراضي
                        if (isset($visit['visitor_type_name'])) {
                            switch ($visit['visitor_type_name']) {
                                case 'مدير':
                                    $signature_text = $subject_is_english ? 'Principal Signature' : 'توقيع المدير';
                                    break;
                                case 'النائب الأكاديمي':
                                    $signature_text = $subject_is_english ? 'Academic Deputy Signature' : 'توقيع النائب الأكاديمي';
                                    break;
                                case 'موجه المادة':
                                    $signature_text = $subject_is_english ? 'Supervisor Signature' : 'توقيع الموجه';
                                    break;
                                case 'منسق المادة':
                                default:
                                    $signature_text = $subject_is_english ? 'Coordinator Signature' : 'توقيع المنسق';
                                    break;
                            }
                        }
                        echo $signature_text;
                        ?>
                    </td>
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