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
    
    // جلب تفاصيل التقييم لهذه الزيارة
    $evaluation_sql = "
        SELECT 
            ve.*,
            ei.name as indicator_text,
            ei.domain_id,
            ed.name as domain_name,
            r.text as recommendation_text
        FROM 
            visit_evaluations ve
        JOIN 
            evaluation_indicators ei ON ve.indicator_id = ei.id
        JOIN 
            evaluation_domains ed ON ei.domain_id = ed.id
        LEFT JOIN 
            recommendations r ON ve.recommendation_id = r.id
        WHERE 
            ve.visit_id = ?
        ORDER BY
            ei.domain_id, ei.id
    ";
    
    $evaluations = query($evaluation_sql, [$visit_id]);
    
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

// حساب متوسط الدرجات
$average_score = round($visit['total_score'], 2);
$grade = get_grade($average_score);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير الزيارة الصفية - <?= htmlspecialchars($visit['teacher_name']) ?></title>
    
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
                font-size: 12pt;
                color: #333;
                line-height: 1.5;
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
                border: 1px solid #ddd;
            }
            
            th, td {
                padding: 8px;
                text-align: right;
            }
            
            th {
                background-color: #f2f2f2;
            }
            
            .print-header {
                text-align: center;
                margin-bottom: 20px;
                border-bottom: 2px solid #333;
                padding-bottom: 10px;
            }
            
            .print-section {
                margin-bottom: 20px;
            }
            
            .print-footer {
                margin-top: 30px;
                text-align: center;
                border-top: 1px solid #ddd;
                padding-top: 10px;
                font-size: 10pt;
            }
            
            .score-box {
                display: inline-block;
                padding: 5px 10px;
                border-radius: 5px;
                font-weight: bold;
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
                background-color: #f3f4f6;
                color: #1f2937;
            }
            
            .notes-box {
                border: 1px solid #ddd;
                padding: 10px;
                margin-bottom: 15px;
                background-color: #f9f9f9;
                min-height: 50px;
            }
            
            .text-center {
                text-align: center;
            }
            
            .hidden-print {
                display: none;
            }
            
            .page-break {
                page-break-after: always;
            }
            
            .main-heading {
                font-size: 24pt;
                margin-bottom: 5px;
            }
            
            .sub-heading {
                font-size: 16pt;
                color: #666;
                margin-bottom: 20px;
            }
            
            .info-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
                margin-bottom: 20px;
            }
            
            .info-item {
                margin-bottom: 10px;
            }
            
            .info-label {
                font-weight: bold;
                color: #555;
                display: block;
                margin-bottom: 2px;
            }
            
            .info-value {
                font-weight: normal;
            }
            
            .indicator-table th {
                font-weight: bold;
                text-align: center;
            }
            
            .indicator-table td:nth-child(2) {
                text-align: center;
            }
            
            .final-score {
                font-size: 32pt;
                font-weight: bold;
                color: #0284c7;
                margin: 10px 0;
            }
            
            .final-grade {
                display: inline-block;
                padding: 5px 15px;
                border-radius: 20px;
                background-color: #dbeafe;
                color: #1e40af;
                font-weight: bold;
                margin-bottom: 20px;
            }
        }

        /* تنسيقات للشاشة فقط */
        @media screen {
            body {
                font-family: 'Cairo', sans-serif;
                color: #333;
                line-height: 1.5;
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
                border: 1px solid #ddd;
            }
            
            th, td {
                padding: 8px;
                text-align: right;
            }
            
            th {
                background-color: #f2f2f2;
            }
            
            .print-header {
                text-align: center;
                margin-bottom: 20px;
                border-bottom: 2px solid #333;
                padding-bottom: 10px;
            }
            
            .print-section {
                margin-bottom: 20px;
            }
            
            .print-footer {
                margin-top: 30px;
                text-align: center;
                border-top: 1px solid #ddd;
                padding-top: 10px;
                font-size: 10pt;
            }
            
            .score-box {
                display: inline-block;
                padding: 5px 10px;
                border-radius: 5px;
                font-weight: bold;
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
                background-color: #f3f4f6;
                color: #1f2937;
            }
            
            .notes-box {
                border: 1px solid #ddd;
                padding: 10px;
                margin-bottom: 15px;
                background-color: #f9f9f9;
                min-height: 50px;
            }
            
            .text-center {
                text-align: center;
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
                font-size: 14px;
            }
            
            .hidden-print button:hover {
                background-color: #0369a1;
            }
            
            .main-heading {
                font-size: 24pt;
                margin-bottom: 5px;
            }
            
            .sub-heading {
                font-size: 16pt;
                color: #666;
                margin-bottom: 20px;
            }
            
            .info-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
                margin-bottom: 20px;
            }
            
            .info-item {
                margin-bottom: 10px;
            }
            
            .info-label {
                font-weight: bold;
                color: #555;
                display: block;
                margin-bottom: 2px;
            }
            
            .info-value {
                font-weight: normal;
            }
            
            .indicator-table th {
                font-weight: bold;
                text-align: center;
            }
            
            .indicator-table td:nth-child(2) {
                text-align: center;
            }
            
            .final-score {
                font-size: 32pt;
                font-weight: bold;
                color: #0284c7;
                margin: 10px 0;
            }
            
            .final-grade {
                display: inline-block;
                padding: 5px 15px;
                border-radius: 20px;
                background-color: #dbeafe;
                color: #1e40af;
                font-weight: bold;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="hidden-print">
        <button onclick="window.print()">طباعة التقرير</button>
        <button onclick="window.location.href='visits.php'">العودة للزيارات</button>
    </div>
    
    <div class="print-container">
        <div class="print-header">
            <h1 class="main-heading">تقرير زيارة صفية</h1>
            <p class="sub-heading">نظام الزيارات الصفية</p>
        </div>
        
        <div class="print-section">
            <h2>المعلومات الأساسية</h2>
            
            <div class="info-grid">
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
        
        <div class="print-section">
            <h2>نتيجة التقييم</h2>
            
            <div class="text-center">
                <div class="final-score"><?= number_format($average_score, 2) ?></div>
                <div class="final-grade"><?= $grade ?></div>
            </div>
        </div>
        
        <div class="print-section">
            <h2>تفاصيل التقييم</h2>
            
            <?php foreach ($evaluations_by_domain as $domain_id => $domain_evaluations): ?>
            <div class="domain-section">
                <h3><?= htmlspecialchars($domains[$domain_id]) ?></h3>
                
                <table class="indicator-table">
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
                                $score = (int)$eval['score'];
                                $score_text = '';
                                $score_class = '';
                                
                                switch ($score) {
                                    case 4:
                                        $score_text = 'ممتاز';
                                        $score_class = 'score-4';
                                        break;
                                    case 3:
                                        $score_text = 'جيد جداً';
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
                                }
                                ?>
                                <span class="score-box <?= $score_class ?>">
                                    <?= $score ?> - <?= $score_text ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($eval['recommendation_text'])): ?>
                                    <?= htmlspecialchars($eval['recommendation_text']) ?>
                                <?php elseif (!empty($eval['custom_recommendation'])): ?>
                                    <?= htmlspecialchars($eval['custom_recommendation']) ?>
                                <?php else: ?>
                                    <em>لا توجد توصية</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="print-section">
            <h2>ملاحظات عامة</h2>
            <div class="notes-box">
                <?= nl2br(htmlspecialchars($visit['general_notes'] ?: 'لا توجد ملاحظات')) ?>
            </div>
        </div>
        
        <div class="print-section">
            <h2>توصيات الزيارة</h2>
            <div class="notes-box">
                <?= nl2br(htmlspecialchars($visit['recommendation_notes'] ?: 'لا توجد توصيات')) ?>
            </div>
        </div>
        
        <div class="print-section">
            <h2>ملاحظات التقدير</h2>
            <div class="notes-box">
                <?= nl2br(htmlspecialchars($visit['appreciation_notes'] ?: 'لا توجد ملاحظات')) ?>
            </div>
        </div>
        
        <div class="print-footer">
            <p>تم إنشاء هذا التقرير بواسطة نظام الزيارات الصفية &copy; <?= date('Y') ?></p>
            <p>تاريخ الطباعة: <?= date('d/m/Y H:i') ?></p>
        </div>
    </div>
</body>
</html> 