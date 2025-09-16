<?php
// بدء التخزين المؤقت للمخرجات
ob_start();

// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';
require_once 'includes/auth_functions.php';

// حماية الصفحة - جميع المستخدمين المسجلين يمكنهم عرض التقارير
protect_page();

// الحصول على معلومات المستخدم
$user_id = $_SESSION['user_id'];
$user_role_name = $_SESSION['role_name'];

// تعيين عنوان الصفحة
$page_title = 'تقرير أداء المادة';
$current_page = 'subject_detailed_report.php';

// تضمين ملف رأس الصفحة
require_once 'includes/header.php';

// تضمين مكون فلترة العام الأكاديمي والفصل الدراسي
require_once 'includes/academic_filter.php';

// التحقق من وجود معرف المادة
$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

if (!$subject_id) {
    echo show_alert("يرجى تحديد المادة لعرض التقرير", "error");
    require_once 'includes/footer.php';
    exit;
}

// استخدام العام الدراسي ومعلوماته من مكون الفلترة
$academic_year_id = $selected_year_id;
$academic_year_name = $current_year_data['name'] ?? '';

// جلب معلومات المادة
$subject = query_row("SELECT * FROM subjects WHERE id = ?", [$subject_id]);

if (!$subject) {
    echo show_alert("لم يتم العثور على المادة المطلوبة", "error");
    require_once 'includes/footer.php';
    exit;
}

// التحقق من صلاحيات الوصول لتقرير المادة
if ($user_role_name === 'Subject Coordinator') {
    // منسق المادة يمكنه رؤية تقرير المواد المرتبط بها فقط عبر جدول coordinator_supervisors
    $coordinator_subjects = query("
        SELECT subject_id
        FROM coordinator_supervisors
        WHERE user_id = ?
    ", [$user_id]);

    $allowed_subject_ids = array_column($coordinator_subjects, 'subject_id');

    if (empty($allowed_subject_ids) || !in_array($subject_id, $allowed_subject_ids)) {
        echo show_alert("غير مسموح لك بعرض تقرير هذه المادة", "error");
        require_once 'includes/footer.php';
        exit;
    }
}

// جلب المعلمين الذين يدرسون هذه المادة
$subject_teachers = query("
    SELECT DISTINCT t.id, t.name 
    FROM teacher_subjects ts 
    JOIN teachers t ON ts.teacher_id = t.id 
    WHERE ts.subject_id = ? 
    ORDER BY t.name", [$subject_id]);

// جلب زيارات المادة
$visits = query("
    SELECT 
        v.id,
        v.visit_date,
        t.name AS teacher_name,
        t.id AS teacher_id,
        g.name AS grade_name,
        sec.name AS section_name,
        sch.name AS school_name,
        vt.name AS visitor_type,
        CONCAT(vis.name, ' (', vt.name, ')') AS visitor_name,
        v.total_score,
        (SELECT AVG(ve.score) FROM visit_evaluations ve WHERE ve.visit_id = v.id AND ve.score IS NOT NULL) * (100/3) AS avg_percentage
    FROM 
        visits v
    JOIN 
        teachers t ON v.teacher_id = t.id
    JOIN 
        grades g ON v.grade_id = g.id
    JOIN 
        sections sec ON v.section_id = sec.id
    JOIN 
        schools sch ON v.school_id = sch.id
    JOIN 
        visitor_types vt ON v.visitor_type_id = vt.id
    JOIN 
        teachers vis ON v.visitor_person_id = vis.id
    WHERE 
        v.subject_id = ?
        AND v.academic_year_id = ?
        " . ($selected_term != 'all' ? $date_condition : "") . "
    ORDER BY 
        v.visit_date DESC
", [$subject_id, $academic_year_id]);

// جلب متوسطات المجالات للمادة
$domains_avg = query("
    SELECT 
        d.id,
        d.name,
        AVG(ve.score) * (100/3) AS avg_percentage
    FROM 
        evaluation_domains d
    JOIN 
        evaluation_indicators i ON i.domain_id = d.id
    JOIN 
        visit_evaluations ve ON ve.indicator_id = i.id
    JOIN 
        visits v ON ve.visit_id = v.id
    WHERE 
        v.subject_id = ?
        AND v.academic_year_id = ?
        " . ($selected_term != 'all' ? $date_condition : "") . "
        AND ve.score IS NOT NULL
    GROUP BY 
        d.id, d.name
    ORDER BY 
        d.id
", [$subject_id, $academic_year_id]);

// حساب الإحصائيات العامة
$total_visits = count($visits);
$total_teachers = count($subject_teachers);

// حساب المتوسط العام للمادة
$overall_avg = 0;
if (!empty($visits)) {
    $total_percentage = array_sum(array_column($visits, 'avg_percentage'));
    $overall_avg = $total_percentage / count($visits);
}

// حساب نقاط القوة (المجالات الأعلى من 80%)
$strengths = [];
$weaknesses = [];
foreach ($domains_avg as $domain) {
    if ($domain['avg_percentage'] >= 80) {
        $strengths[] = $domain;
    } elseif ($domain['avg_percentage'] < 70) {
        $weaknesses[] = $domain;
    }
}

// حساب إحصائيات التطور - مقارنة أول وآخر زيارة
$progress_stats = [];
if (count($visits) >= 2) {
    // ترتيب الزيارات حسب التاريخ
    usort($visits, function($a, $b) {
        return strtotime($a['visit_date']) - strtotime($b['visit_date']);
    });
    
    $first_visit_avg = $visits[0]['avg_percentage'];
    $last_visit_avg = $visits[count($visits) - 1]['avg_percentage'];
    
    $progress_stats = [
        'first_visit' => $first_visit_avg,
        'last_visit' => $last_visit_avg,
        'improvement' => $last_visit_avg - $first_visit_avg,
        'improvement_percentage' => $first_visit_avg > 0 ? (($last_visit_avg - $first_visit_avg) / $first_visit_avg) * 100 : 0
    ];
}

// حساب تطور المجالات
$domain_progress = [];
if (count($visits) >= 2) {
    foreach ($domains_avg as $domain) {
        // جلب متوسط أول وآخر زيارة لكل مجال
        $domain_trend = query("
            SELECT 
                AVG(ve.score) * (100/3) AS avg_score,
                v.visit_date
            FROM 
                visit_evaluations ve
            JOIN 
                visits v ON ve.visit_id = v.id
            JOIN 
                evaluation_indicators i ON ve.indicator_id = i.id
            WHERE 
                v.subject_id = ?
                AND v.academic_year_id = ?
                AND i.domain_id = ?
                AND ve.score IS NOT NULL
            GROUP BY 
                v.id, v.visit_date
            ORDER BY 
                v.visit_date
        ", [$subject_id, $academic_year_id, $domain['id']]);
        
        if (count($domain_trend) >= 2) {
            $first_score = $domain_trend[0]['avg_score'];
            $last_score = $domain_trend[count($domain_trend) - 1]['avg_score'];
            $domain_progress[$domain['id']] = [
                'name' => $domain['name'],
                'trend' => $last_score - $first_score,
                'first' => $first_score,
                'last' => $last_score
            ];
        }
    }
}

// حساب المجالات المتحسنة والمتراجعة
$improved_domains = 0;
$declined_domains = 0;
foreach ($domain_progress as $progress) {
    if ($progress['trend'] > 0) {
        $improved_domains++;
    } elseif ($progress['trend'] < 0) {
        $declined_domains++;
    }
}

// جلب أفضل المؤشرات أداءً في المادة
$best_indicators = query("
    SELECT 
        i.name,
        AVG(ve.score) * (100/3) AS avg_score
    FROM 
        evaluation_indicators i
    JOIN 
        visit_evaluations ve ON ve.indicator_id = i.id
    JOIN 
        visits v ON ve.visit_id = v.id
    WHERE 
        v.subject_id = ?
        AND v.academic_year_id = ?
        " . ($selected_term != 'all' ? $date_condition : "") . "
        AND ve.score IS NOT NULL
    GROUP BY 
        i.id, i.name
    HAVING 
        AVG(ve.score) >= 2.5
    ORDER BY 
        avg_score DESC
    LIMIT 5
", [$subject_id, $academic_year_id]);

// جلب أكثر التوصيات تكراراً للمادة
$common_recommendations = query("
    SELECT 
        r.text,
        COUNT(*) AS count
    FROM 
        visit_evaluations ve
    JOIN 
        visits v ON ve.visit_id = v.id
    JOIN 
        recommendations r ON ve.recommendation_id = r.id
    WHERE 
        v.subject_id = ?
        " . ($academic_year_id > 0 ? "AND v.academic_year_id = ?" : "") . "
        AND ve.recommendation_id IS NOT NULL
    GROUP BY 
        r.text
    ORDER BY 
        count DESC
    LIMIT 5
", $academic_year_id > 0 ? [$subject_id, $academic_year_id] : [$subject_id]);

?>

<div class="mb-6">
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold">تقرير أداء المادة: <?= htmlspecialchars($subject['name']) ?></h1>
            <div class="text-sm text-gray-600">
                العام الدراسي: <?= htmlspecialchars($academic_year_name) ?>
                <?php if ($selected_term != 'all'): ?>
                    - <?= $selected_term == 'first' ? 'الفصل الأول' : 'الفصل الثاني' ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- الإحصائيات السريعة -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg text-center">
                <div class="text-2xl font-bold text-blue-600"><?= $total_visits ?></div>
                <div class="text-sm text-gray-600">إجمالي الزيارات</div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg text-center">
                <div class="text-2xl font-bold text-green-600"><?= $total_teachers ?></div>
                <div class="text-sm text-gray-600">عدد المعلمين</div>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg text-center">
                <div class="text-2xl font-bold text-purple-600"><?= number_format($overall_avg, 1) ?>%</div>
                <div class="text-sm text-gray-600">المتوسط العام</div>
            </div>
            <div class="bg-orange-50 p-4 rounded-lg text-center">
                <div class="text-2xl font-bold text-orange-600"><?= count($domains_avg) ?></div>
                <div class="text-sm text-gray-600">مجالات التقييم</div>
            </div>
        </div>

        <!-- إحصائيات تطور الأداء -->
        <?php if (!empty($progress_stats)): ?>
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-4">📈 إحصائيات تطور الأداء</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div class="bg-gradient-to-r from-blue-50 to-blue-100 p-4 rounded-lg border-l-4 border-blue-500">
                    <div class="text-sm text-blue-600 font-medium">نسبة التحسن العامة</div>
                    <div class="text-2xl font-bold text-blue-700">
                        <?= $progress_stats['improvement'] >= 0 ? '+' : '' ?><?= number_format($progress_stats['improvement_percentage'], 1) ?>%
                    </div>
                    <div class="text-xs text-blue-600 mt-1">
                        من أول زيارة (<?= number_format($progress_stats['first_visit'], 1) ?>%) إلى آخر زيارة (<?= number_format($progress_stats['last_visit'], 1) ?>%)
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-green-50 to-green-100 p-4 rounded-lg border-l-4 border-green-500">
                    <div class="text-sm text-green-600 font-medium">المجالات المتحسنة</div>
                    <div class="text-2xl font-bold text-green-700"><?= $improved_domains ?></div>
                    <div class="text-xs text-green-600 mt-1">
                        من أصل <?= count($domain_progress) ?> مجال (<?= count($domain_progress) > 0 ? number_format(($improved_domains / count($domain_progress)) * 100, 0) : 0 ?>%)
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-red-50 to-red-100 p-4 rounded-lg border-l-4 border-red-500">
                    <div class="text-sm text-red-600 font-medium">المجالات المتراجعة</div>
                    <div class="text-2xl font-bold text-red-700"><?= $declined_domains ?></div>
                    <div class="text-xs text-red-600 mt-1">
                        من أصل <?= count($domain_progress) ?> مجال (<?= count($domain_progress) > 0 ? number_format(($declined_domains / count($domain_progress)) * 100, 0) : 0 ?>%)
                    </div>
                </div>
            </div>
            
            <!-- ملاحظة التطور -->
            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 p-4 rounded-lg border border-indigo-200">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-lightbulb text-indigo-500 text-lg"></i>
                    </div>
                    <div class="mr-3">
                        <div class="text-sm font-medium text-indigo-800">ملاحظة:</div>
                        <div class="text-sm text-indigo-700 mt-1">
                            <?php if ($progress_stats['improvement'] > 0): ?>
                                أداء المادة يظهر تحسناً ملحوظاً منذ بداية الزيارات. استمر في الاستراتيجيات الحالية.
                            <?php elseif ($progress_stats['improvement'] < 0): ?>
                                هناك تراجع في أداء المادة يتطلب مراجعة الاستراتيجيات المتبعة وتطوير خطط تحسين.
                            <?php else: ?>
                                أداء المادة مستقر، يُنصح بتطوير استراتيجيات جديدة لتحقيق نمو إضافي.
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- نقاط القوة والضعف -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- نقاط القوة -->
            <?php if (!empty($strengths)): ?>
            <div class="bg-gradient-to-br from-green-50 to-emerald-50 p-6 rounded-lg border border-green-200">
                <h3 class="text-lg font-semibold mb-4 text-green-800 flex items-center">
                    <i class="fas fa-trophy text-green-600 ml-2"></i>
                    نقاط القوة
                </h3>
                <div class="space-y-3">
                    <?php foreach ($strengths as $strength): ?>
                        <div class="flex items-center justify-between bg-white p-3 rounded-lg shadow-sm">
                            <span class="font-medium text-green-700"><?= htmlspecialchars($strength['name']) ?></span>
                            <div class="flex items-center">
                                <span class="text-lg font-bold text-green-600"><?= number_format($strength['avg_percentage'], 1) ?>%</span>
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- نقاط الضعف -->
            <?php if (!empty($weaknesses)): ?>
            <div class="bg-gradient-to-br from-red-50 to-orange-50 p-6 rounded-lg border border-red-200">
                <h3 class="text-lg font-semibold mb-4 text-red-800 flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-600 ml-2"></i>
                    نقاط تحتاج تحسين
                </h3>
                <div class="space-y-3">
                    <?php foreach ($weaknesses as $weakness): ?>
                        <div class="flex items-center justify-between bg-white p-3 rounded-lg shadow-sm">
                            <span class="font-medium text-red-700"><?= htmlspecialchars($weakness['name']) ?></span>
                            <div class="flex items-center">
                                <span class="text-lg font-bold text-red-600"><?= number_format($weakness['avg_percentage'], 1) ?>%</span>
                                <i class="fas fa-arrow-up text-red-500 mr-2"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- الرسوم البيانية -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-4">📊 الرسوم البيانية</h3>
            <div class="grid grid-cols-1 <?= count($visits) > 1 ? 'md:grid-cols-2' : '' ?> gap-6">
                <!-- الرسم البياني للمجالات -->
                <div class="bg-white p-6 rounded-lg border shadow-sm">
                    <h4 class="text-md font-medium mb-3">أداء المجالات</h4>
                    <canvas id="domainsChart" width="400" height="200"></canvas>
                </div>
                
                <!-- تطور الأداء بمرور الوقت -->
                <?php if (count($visits) > 1): ?>
                <div class="bg-white p-6 rounded-lg border shadow-sm">
                    <h4 class="text-md font-medium mb-3">تطور الأداء بمرور الوقت</h4>
                    <canvas id="progressChart" width="400" height="200"></canvas>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- متوسطات المجالات -->
        <?php if (!empty($domains_avg)): ?>
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-3">متوسطات أداء المجالات</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($domains_avg as $domain): ?>
                    <div class="border rounded-lg p-4">
                        <div class="flex justify-between items-center">
                            <span class="font-medium"><?= htmlspecialchars($domain['name']) ?></span>
                            <span class="text-lg font-bold <?= $domain['avg_percentage'] >= 80 ? 'text-green-600' : ($domain['avg_percentage'] >= 60 ? 'text-yellow-600' : 'text-red-600') ?>">
                                <?= number_format($domain['avg_percentage'], 1) ?>%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                            <div class="h-2 rounded-full <?= $domain['avg_percentage'] >= 80 ? 'bg-green-500' : ($domain['avg_percentage'] >= 60 ? 'bg-yellow-500' : 'bg-red-500') ?>" 
                                 style="width: <?= $domain['avg_percentage'] ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- جدول الزيارات -->
        <?php if (!empty($visits)): ?>
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-3">سجل الزيارات</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-3 px-4 border text-center font-semibold">تاريخ الزيارة</th>
                            <th class="py-3 px-4 border text-center font-semibold">المعلم</th>
                            <th class="py-3 px-4 border text-center font-semibold">الصف</th>
                            <th class="py-3 px-4 border text-center font-semibold">الشعبة</th>
                            <th class="py-3 px-4 border text-center font-semibold">المدرسة</th>
                            <th class="py-3 px-4 border text-center font-semibold">الزائر</th>
                            <th class="py-3 px-4 border text-center font-semibold">النتيجة</th>
                            <th class="py-3 px-4 border text-center font-semibold">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($visits as $visit): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-2 px-4 border text-center"><?= date('Y-m-d', strtotime($visit['visit_date'])) ?></td>
                                <td class="py-2 px-4 border">
                                    <a href="teacher_report.php?teacher_id=<?= $visit['teacher_id'] ?>" class="text-blue-600 hover:text-blue-800">
                                        <?= htmlspecialchars($visit['teacher_name']) ?>
                                    </a>
                                </td>
                                <td class="py-2 px-4 border text-center"><?= htmlspecialchars($visit['grade_name']) ?></td>
                                <td class="py-2 px-4 border text-center"><?= htmlspecialchars($visit['section_name']) ?></td>
                                <td class="py-2 px-4 border"><?= htmlspecialchars($visit['school_name']) ?></td>
                                <td class="py-2 px-4 border"><?= htmlspecialchars($visit['visitor_name']) ?></td>
                                <td class="py-2 px-4 border text-center">
                                    <span class="px-2 py-1 rounded-full text-sm <?= $visit['avg_percentage'] >= 80 ? 'bg-green-100 text-green-800' : ($visit['avg_percentage'] >= 60 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                        <?= number_format($visit['avg_percentage'], 1) ?>%
                                    </span>
                                </td>
                                <td class="py-2 px-4 border text-center">
                                    <a href="view_visit.php?id=<?= $visit['id'] ?>" class="text-blue-600 hover:text-blue-800 text-sm">
                                        عرض التفاصيل
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- أفضل المؤشرات -->
        <?php if (!empty($best_indicators)): ?>
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-3">أفضل المؤشرات أداءً</h3>
            <div class="bg-green-50 p-4 rounded-lg">
                <ul class="space-y-2">
                    <?php foreach ($best_indicators as $indicator): ?>
                        <li class="flex justify-between items-center">
                            <span><?= htmlspecialchars($indicator['name']) ?></span>
                            <span class="font-bold text-green-600"><?= number_format($indicator['avg_score'], 1) ?>%</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- أكثر التوصيات تكراراً -->
        <?php if (!empty($common_recommendations)): ?>
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-3">أكثر التوصيات تكراراً</h3>
            <div class="bg-blue-50 p-4 rounded-lg">
                <ul class="space-y-2">
                    <?php foreach ($common_recommendations as $recommendation): ?>
                        <li class="flex justify-between items-center">
                            <span><?= htmlspecialchars($recommendation['text']) ?></span>
                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm"><?= $recommendation['count'] ?> مرة</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- قائمة المعلمين -->
        <?php if (!empty($subject_teachers)): ?>
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-3">معلمو المادة</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($subject_teachers as $teacher): ?>
                    <div class="border rounded-lg p-4 hover:bg-gray-50">
                        <a href="teacher_report.php?teacher_id=<?= $teacher['id'] ?>" class="text-blue-600 hover:text-blue-800 font-medium">
                            <?= htmlspecialchars($teacher['name']) ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- أزرار الإجراءات -->
        <div class="flex gap-4 justify-center">
            <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                <i class="bi bi-printer ml-2"></i> طباعة التقرير
            </button>
            <a href="subject_performance_report.php" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">
                <i class="bi bi-arrow-left ml-2"></i> العودة للتقرير العام
            </a>
        </div>
    </div>
</div>

<style media="print">
    @page {
        size: A4;
        margin: 1cm;
    }
    
    header, nav, footer, button, .no-print {
        display: none !important;
    }
    
    body {
        background-color: white;
    }
    
    .grid {
        display: grid !important;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    th, td {
        border: 1px solid #000;
        padding: 8px;
        text-align: center;
    }
    
    .bg-green-100, .bg-yellow-100, .bg-red-100 {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
</style>

<!-- تضمين Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// إعداد البيانات للرسوم البيانية
<?php if (!empty($domains_avg)): ?>
// رسم بياني للمجالات
const domainsData = {
    labels: [<?php foreach ($domains_avg as $domain): ?>'<?= addslashes($domain['name']) ?>',<?php endforeach; ?>],
    datasets: [{
        label: 'متوسط الأداء (%)',
        data: [<?php foreach ($domains_avg as $domain): ?><?= number_format($domain['avg_percentage'], 1) ?>,<?php endforeach; ?>],
        backgroundColor: [
            <?php foreach ($domains_avg as $domain): ?>
                '<?= $domain['avg_percentage'] >= 80 ? 'rgba(34, 197, 94, 0.8)' : ($domain['avg_percentage'] >= 60 ? 'rgba(234, 179, 8, 0.8)' : 'rgba(239, 68, 68, 0.8)') ?>',
            <?php endforeach; ?>
        ],
        borderColor: [
            <?php foreach ($domains_avg as $domain): ?>
                '<?= $domain['avg_percentage'] >= 80 ? 'rgba(34, 197, 94, 1)' : ($domain['avg_percentage'] >= 60 ? 'rgba(234, 179, 8, 1)' : 'rgba(239, 68, 68, 1)') ?>',
            <?php endforeach; ?>
        ],
        borderWidth: 2
    }]
};

const domainsConfig = {
    type: 'bar',
    data: domainsData,
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'متوسطات أداء المجالات',
                font: {
                    size: 16,
                    weight: 'bold'
                }
            },
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                ticks: {
                    callback: function(value) {
                        return value + '%';
                    }
                }
            }
        }
    }
};

// إنشاء الرسم البياني للمجالات
const domainsChart = new Chart(
    document.getElementById('domainsChart'),
    domainsConfig
);
<?php endif; ?>

<?php if (count($visits) > 1): ?>
// رسم بياني للتطور
const progressData = {
    labels: [<?php foreach ($visits as $visit): ?>'<?= date('m/d', strtotime($visit['visit_date'])) ?>',<?php endforeach; ?>],
    datasets: [{
        label: 'متوسط الأداء (%)',
        data: [<?php foreach ($visits as $visit): ?><?= number_format($visit['avg_percentage'], 1) ?>,<?php endforeach; ?>],
        borderColor: 'rgba(59, 130, 246, 1)',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        borderWidth: 3,
        fill: true,
        tension: 0.4,
        pointBackgroundColor: 'rgba(59, 130, 246, 1)',
        pointBorderColor: '#ffffff',
        pointBorderWidth: 2,
        pointRadius: 6
    }]
};

const progressConfig = {
    type: 'line',
    data: progressData,
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'تطور أداء المادة بمرور الوقت',
                font: {
                    size: 16,
                    weight: 'bold'
                }
            },
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                ticks: {
                    callback: function(value) {
                        return value + '%';
                    }
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'تاريخ الزيارة'
                }
            }
        },
        interaction: {
            intersect: false,
            mode: 'index'
        }
    }
};

// إنشاء الرسم البياني للتطور
const progressChart = new Chart(
    document.getElementById('progressChart'),
    progressConfig
);
<?php endif; ?>

// إضافة تأثيرات حركية للإحصائيات
document.addEventListener('DOMContentLoaded', function() {
    // تأثير العد التصاعدي للأرقام
    const counters = document.querySelectorAll('.text-2xl.font-bold');
    
    counters.forEach(counter => {
        const text = counter.textContent;
        const number = parseFloat(text.replace(/[^\d.-]/g, ''));
        
        if (!isNaN(number)) {
            let current = 0;
            const increment = number / 50; // 50 خطوة للوصول للرقم النهائي
            const suffix = text.replace(number.toString(), '');
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= number) {
                    current = number;
                    clearInterval(timer);
                }
                counter.textContent = Math.round(current * 10) / 10 + suffix;
            }, 30);
        }
    });
});
</script>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?>
