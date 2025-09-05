<?php
// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// تضمين ملفات Composer Autoload
require 'vendor/autoload.php';

// استيراد الفئات المطلوبة
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use Mpdf\Mpdf;

// تعيين عنوان الصفحة
$page_title = 'إرسال التقرير بالبريد الإلكتروني';

// تضمين ملف رأس الصفحة
require_once 'includes/header.php';

// التحقق من وجود معرف الزيارة
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // إعادة التوجيه إلى صفحة الزيارات
    header('Location: visits.php');
    exit;
}

$visit_id = (int)$_GET['id'];
$success_message = '';
$error_message = '';
$form_data = [];

// التحقق مما إذا تم إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // جمع بيانات النموذج
    $form_data['teacher_email'] = isset($_POST['teacher_email']) ? trim($_POST['teacher_email']) : '';
    $form_data['coordinator_email'] = isset($_POST['coordinator_email']) ? trim($_POST['coordinator_email']) : '';
    $form_data['academic_deputy_email'] = isset($_POST['academic_deputy_email']) ? trim($_POST['academic_deputy_email']) : '';
    $form_data['message'] = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    // قائمة البريد الإلكتروني
    $emails = [];
    
    // التحقق من البريد الإلكتروني للمعلم
    if (!empty($form_data['teacher_email'])) {
        if (filter_var($form_data['teacher_email'], FILTER_VALIDATE_EMAIL)) {
            $emails[] = ['email' => $form_data['teacher_email'], 'name' => 'المعلم'];
        } else {
            $error_message = 'البريد الإلكتروني للمعلم غير صحيح.';
        }
    }
    
    // التحقق من البريد الإلكتروني للمنسق
    if (!empty($form_data['coordinator_email']) && !$error_message) {
        if (filter_var($form_data['coordinator_email'], FILTER_VALIDATE_EMAIL)) {
            $emails[] = ['email' => $form_data['coordinator_email'], 'name' => 'المنسق'];
        } else {
            $error_message = 'البريد الإلكتروني للمنسق غير صحيح.';
        }
    }
    
    // التحقق من البريد الإلكتروني للنائب الأكاديمي
    if (!empty($form_data['academic_deputy_email']) && !$error_message) {
        if (filter_var($form_data['academic_deputy_email'], FILTER_VALIDATE_EMAIL)) {
            $emails[] = ['email' => $form_data['academic_deputy_email'], 'name' => 'النائب الأكاديمي'];
        } else {
            $error_message = 'البريد الإلكتروني للنائب الأكاديمي غير صحيح.';
        }
    }
    
    // التحقق من وجود بريد إلكتروني واحد على الأقل
    if (empty($emails) && !$error_message) {
        $error_message = 'يرجى إدخال بريد إلكتروني واحد على الأقل.';
    }
    
    // معالجة إرسال البريد الإلكتروني إذا لم يكن هناك أخطاء
    if (!$error_message) {
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
                    ed.id, ei.id
            ";
            
            $evaluations = query($evaluation_sql, [$visit_id]);
            
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

            // استعلام لجلب جميع التقييمات لهذه الزيارة
            $scores_sql = "
                SELECT score 
                FROM visit_evaluations 
                WHERE visit_id = ?
            ";
            $scores = query($scores_sql, [$visit_id]);

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
            
            // إنشاء محتوى التقرير
            $pdf_content = '
            <!DOCTYPE html>
            <html lang="ar" dir="rtl">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>تقرير زيارة صفية - ' . htmlspecialchars($visit['teacher_name']) . '</title>
                
                <style>
                    body {
                        font-family: DejaVuSans, sans-serif;
                        padding: 20px;
                        line-height: 1.5;
                        font-size: 10pt;
                    }
                    h1 {
                        text-align: center;
                        font-size: 16pt;
                        font-weight: bold;
                        margin-bottom: 20px;
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
                    }
                    .text-center {
                        text-align: center;
                    }
                    .domain-heading {
                        background-color: #ddd;
                        font-weight: bold;
                    }
                </style>
            </head>
            <body>
                <h1>استمارة ملاحظة صفية لأداء معلم للعام الأكاديمي ' . htmlspecialchars($visit['academic_year_name']) . '</h1>
                
                <table>
                    <tr>
                        <th colspan="7" class="text-center">أولاً : المعلومات الأساسية</th>
                    </tr>
                    <tr>
                        <th>المدرسة</th>
                        <td>' . htmlspecialchars($visit['school_name']) . '</td>
                        <th>اليوم</th>
                        <td>' . $day_name . '</td>
                        <th>التاريخ</th>
                        <td>' . $date_formatted . '</td>
                    </tr>
                    <tr>
                        <th>المادة</th>
                        <td>' . htmlspecialchars($visit['subject_name']) . '</td>
                        <th>رقم الشعبة</th>
                        <td>' . htmlspecialchars($visit['section_name']) . '</td>
                        <th>الصف</th>
                        <td>' . htmlspecialchars($visit['grade_name']) . '</td>
                    </tr>
                    <tr>
                        <th>المعلم</th>
                        <td>' . htmlspecialchars($visit['teacher_name']) . '</td>
                        <th>الزائر</th>
                        <td colspan="3">' . htmlspecialchars($visit['visitor_person_name']) . '</td>
                    </tr>
                    <tr>
                        <th>نوع الزيارة</th>
                        <td>' . $visit_attendance_type . '</td>
                        <th>الموضوع</th>
                        <td colspan="3">' . htmlspecialchars($visit['topic'] ?? '') . '</td>
                    </tr>
                </table>
                
                <table>
                    <tr>
                        <th colspan="8" class="text-center">ثانياً : مجالات تقييم الأداء</th>
                    </tr>
                    <tr>
                        <th rowspan="2">المجال</th>
                        <th rowspan="2">مؤشرات الأداء</th>
                        <th colspan="5">الدرجة التقييمية</th>
                        <th rowspan="2">التوصية</th>
                    </tr>
                    <tr>
                        <th>الأدلة مستكملة وفاعلة</th>
                        <th>تتوفر معظم الأدلة</th>
                        <th>تتوفر بعض الأدلة</th>
                        <th>الأدلة غير متوفرة أو محدودة</th>
                        <th>لم يتم قياسه</th>
                    </tr>';
                    
                    $current_domain = '';
                    $previous_domain = '';
                    $domain_count = 0;
                    
                    foreach ($evaluations as $index => $eval) {
                        $current_domain = $eval['domain_name'];
                        
                        // إذا تغير المجال، نعرض صف جديد للمجال
                        if ($current_domain != $previous_domain) {
                            $domain_count++;
                            $domain_rowspan = 0;
                            
                            // حساب عدد المؤشرات في هذا المجال
                            foreach ($evaluations as $count_eval) {
                                if ($count_eval['domain_name'] == $current_domain) {
                                    $domain_rowspan++;
                                }
                            }
                            
                            $pdf_content .= '
                            <tr>
                                <td rowspan="' . $domain_rowspan . '" class="domain-heading">' . htmlspecialchars($current_domain) . '</td>
                                <td>' . htmlspecialchars($eval['indicator_text']) . '</td>
                                <td class="text-center">' . ($eval['score'] == 3 ? '✓' : '') . '</td>
                                <td class="text-center">' . ($eval['score'] == 2 ? '✓' : '') . '</td>
                                <td class="text-center">' . ($eval['score'] == 1 ? '✓' : '') . '</td>
                                <td class="text-center">' . ($eval['score'] == 0 ? '✓' : '') . '</td>
                                <td class="text-center">' . ($eval['score'] === null ? '✓' : '') . '</td>
                                <td>' . htmlspecialchars($eval['recommendation_text'] ?: '') . '</td>
                            </tr>';
                        } else {
                            $pdf_content .= '
                            <tr>
                                <td>' . htmlspecialchars($eval['indicator_text']) . '</td>
                                <td class="text-center">' . ($eval['score'] == 3 ? '✓' : '') . '</td>
                                <td class="text-center">' . ($eval['score'] == 2 ? '✓' : '') . '</td>
                                <td class="text-center">' . ($eval['score'] == 1 ? '✓' : '') . '</td>
                                <td class="text-center">' . ($eval['score'] == 0 ? '✓' : '') . '</td>
                                <td class="text-center">' . ($eval['score'] === null ? '✓' : '') . '</td>
                                <td>' . htmlspecialchars($eval['recommendation_text'] ?: '') . '</td>
                            </tr>';
                        }
                        
                        $previous_domain = $current_domain;
                    }
                    
                    $pdf_content .= '
                </table>
                
                <table>
                    <tr>
                        <th colspan="2">ملاحظات وتوصيات عامة</th>
                    </tr>
                    <tr>
                        <td>أشكر المعلم على: ' . htmlspecialchars($visit['appreciation_notes'] ?: '') . '</td>
                    </tr>
                    <tr>
                        <td>وأوصي بما يلي: ' . htmlspecialchars($visit['recommendation_notes'] ?: '') . '</td>
                    </tr>
                </table>
                
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
                
                <div style="margin-top: 20px; font-size: 9pt; text-align: center;">
                    <p>الرؤية : متعلم ريادي لتنمية مستدامة</p>
                    <p>الرسالة: نرسي بيئة تعليمية شاملة ومبتكرة تعزز القيم والأخلاق وتؤهل المتعلم بمهارات عالية; لإعداد جيل واعٍ قادرٍ على بناء مجتمع متقدم واقتصاد مزدهر</p>
                </div>
            </body>
            </html>';
            
            // إنشاء كائن MPDF
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_header' => 0,
                'margin_footer' => 0,
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 16,
                'margin_bottom' => 16,
                'tempDir' => __DIR__ . '/uploads/temp'
            ]);
            
            // تعيين محتوى PDF
            $mpdf->WriteHTML($pdf_content);
            
            // حفظ الملف في المجلد المؤقت
            $pdf_file_name = 'تقرير_زيارة_' . $visit_id . '_' . date('Ymd_His') . '.pdf';
            $pdf_path = __DIR__ . '/uploads/temp/' . $pdf_file_name;
            
            // التأكد من وجود المجلد المؤقت
            if (!file_exists(__DIR__ . '/uploads/temp')) {
                mkdir(__DIR__ . '/uploads/temp', 0777, true);
            }
            
            // حفظ ملف PDF
            $mpdf->Output($pdf_path, 'F');
            
            // إنشاء نص البريد الإلكتروني
            $email_subject = 'تقرير زيارة صفية للمعلم: ' . $visit['teacher_name'] . ' - ' . $visit['subject_name'];
            $email_body = "
            <html>
            <head>
                <title>{$email_subject}</title>
                <style>
                    body { font-family: 'Arial', sans-serif; direction: rtl; }
                    h2 { color: #0284c7; }
                </style>
            </head>
            <body>
                <h2>تقرير زيارة صفية</h2>
                <p>مرفق تقرير الزيارة الصفية للمعلم <strong>{$visit['teacher_name']}</strong> بتاريخ {$visit['visit_date']}.</p>
                <p>المادة: {$visit['subject_name']}</p>
                <p>الصف: {$visit['grade_name']}</p>
                <p>الشعبة: {$visit['section_name']}</p>
                <p>المدرسة: {$visit['school_name']}</p>
                <p>الزائر: {$visit['visitor_person_name']}</p>
                <hr>
                <p>{$form_data['message']}</p>
            </body>
            </html>";
            
            // إنشاء كائن PHPMailer
            $mail = new PHPMailer(true);
            
            try {
                // إعدادات الخادم
                $mail->CharSet = 'UTF-8';
                
                $mail->isSMTP();
                $mail->Host       = 'mail.haclinic.net';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'classvisit@haclinic.net';
                $mail->Password   = '$mpm=io+T{+*';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
                $mail->Port       = 465;                
                // المرسل
                $mail->setFrom('classvisit@example.com', 'نظام الزيارات الصفية');
                
                // إضافة المستلمين
                foreach ($emails as $recipient) {
                    $mail->addAddress($recipient['email'], $recipient['name']);
                }
                
                // إرفاق ملف PDF
                $mail->addAttachment($pdf_path, $pdf_file_name);
                
                // محتوى البريد الإلكتروني
                $mail->isHTML(true);
                $mail->Subject = $email_subject;
                $mail->Body    = $email_body;
                $mail->AltBody = strip_tags(str_replace('<br>', "\n", $email_body));
                
                // إرسال البريد الإلكتروني
                $mail->send();
                
                // حذف ملف PDF المؤقت
                @unlink($pdf_path);
                
                $success_message = 'تم إرسال التقرير بنجاح إلى البريد الإلكتروني.';
                $form_data = []; // مسح البيانات بعد الإرسال الناجح
            } catch (Exception $e) {
                // حذف ملف PDF المؤقت في حالة الفشل
                @unlink($pdf_path);
                
                $error_message = 'حدث خطأ أثناء إرسال البريد الإلكتروني: ' . $mail->ErrorInfo;
            }
            
        } catch (Exception $e) {
            $error_message = 'حدث خطأ أثناء معالجة الطلب: ' . $e->getMessage();
        }
    }
}

// جلب بيانات المعلم والزائر للاقتراح التلقائي للبريد الإلكتروني
try {
    $emails_sql = "
        SELECT 
            t.email as teacher_email,
            v.email as visitor_email
        FROM 
            visits vs
        LEFT JOIN 
            teachers t ON vs.teacher_id = t.id
        LEFT JOIN 
            teachers v ON vs.visitor_person_id = v.id
        WHERE 
            vs.id = ?
    ";
    
    $emails_data = query_row($emails_sql, [$visit_id]);
    
    if (!isset($form_data['teacher_email']) && !empty($emails_data['teacher_email'])) {
        $form_data['teacher_email'] = $emails_data['teacher_email'];
    }
    
    if (!isset($form_data['academic_deputy_email']) && !empty($emails_data['visitor_email'])) {
        $form_data['academic_deputy_email'] = $emails_data['visitor_email'];
    }
    
} catch (Exception $e) {
    // لا شيء للقيام به - فقط استمر
}
?>

<div class="bg-white shadow-md rounded p-6 mb-6">
    <h1 class="text-2xl font-bold mb-4">إرسال تقرير الزيارة بالبريد الإلكتروني</h1>
    
    <?php if ($success_message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($success_message) ?>
            
            <div class="mt-4">
                <a href="view_visit.php?id=<?= $visit_id ?>" class="bg-primary-600 text-white px-4 py-2 rounded-md mr-2">
                    العودة لعرض الزيارة
                </a>
                <a href="visits.php" class="bg-gray-600 text-white px-4 py-2 rounded-md">
                    العودة لقائمة الزيارات
                </a>
            </div>
        </div>
    <?php elseif ($error_message): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>
    
    <?php if (!$success_message): ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
            <p>ملاحظة: يجب تكوين إعدادات خادم SMTP في الكود لإرسال البريد الإلكتروني بشكل صحيح.</p>
            <p>سيتم إرسال التقرير كملف PDF مرفق بالبريد الإلكتروني.</p>
        </div>
        
        <form method="post" action="" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="teacher_email" class="block mb-1">البريد الإلكتروني للمعلم</label>
                    <input type="email" id="teacher_email" name="teacher_email" 
                        value="<?= htmlspecialchars($form_data['teacher_email'] ?? '') ?>" 
                        class="w-full border border-gray-300 rounded-md px-3 py-2">
                </div>
                
                <div>
                    <label for="coordinator_email" class="block mb-1">البريد الإلكتروني للمنسق</label>
                    <input type="email" id="coordinator_email" name="coordinator_email" 
                        value="<?= htmlspecialchars($form_data['coordinator_email'] ?? '') ?>" 
                        class="w-full border border-gray-300 rounded-md px-3 py-2">
                </div>
                
                <div>
                    <label for="academic_deputy_email" class="block mb-1">البريد الإلكتروني للنائب الأكاديمي</label>
                    <input type="email" id="academic_deputy_email" name="academic_deputy_email" 
                        value="<?= htmlspecialchars($form_data['academic_deputy_email'] ?? '') ?>" 
                        class="w-full border border-gray-300 rounded-md px-3 py-2">
                </div>
            </div>
            
            <div>
                <label for="message" class="block mb-1">رسالة إضافية (اختياري)</label>
                <textarea id="message" name="message" rows="4" 
                    class="w-full border border-gray-300 rounded-md px-3 py-2"><?= htmlspecialchars($form_data['message'] ?? '') ?></textarea>
            </div>
            
            <div class="mt-4">
                <button type="submit" class="bg-primary-600 text-white px-4 py-2 rounded-md mr-2 hover:bg-primary-700">
                    إرسال التقرير
                </button>
                <a href="print_visit.php?id=<?= $visit_id ?>" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                    العودة للتقرير
                </a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?> 