<?php
// بدء التخزين المؤقت للمخرجات
ob_start();

// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// تعيين عنوان الصفحة
$page_title = 'تقرير أداء المعلم';
$current_page = 'teacher_report.php';

// تضمين ملف رأس الصفحة
require_once 'includes/header.php';

// تضمين مكون فلترة العام الأكاديمي والفصل الدراسي
require_once 'includes/academic_filter.php';

// التحقق من وجود معرف المعلم
$teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;

if (!$teacher_id) {
    echo show_alert("يرجى تحديد المعلم لعرض التقرير", "error");
    require_once 'includes/footer.php';
    exit;
}

// استخدام العام الدراسي ومعلوماته من مكون الفلترة
$academic_year_id = $selected_year_id;
$academic_year_name = $current_year_data['name'] ?? '';

// جلب معلومات المعلم
$teacher = query_row("SELECT * FROM teachers WHERE id = ?", [$teacher_id]);

if (!$teacher) {
    echo show_alert("لم يتم العثور على المعلم المطلوب", "error");
    require_once 'includes/footer.php';
    exit;
}

// جلب المواد التي يدرسها المعلم
$teacher_subjects = query("
    SELECT s.id, s.name 
    FROM teacher_subjects ts 
    JOIN subjects s ON ts.subject_id = s.id 
    WHERE ts.teacher_id = ? 
    ORDER BY s.name", [$teacher_id]);

// جلب الأعوام الدراسية للاختيار
$academic_years = query("SELECT id, name, is_active FROM academic_years ORDER BY is_active DESC, name DESC");

// جلب زيارات المعلم
$visits = query("
    SELECT 
        v.id,
        v.visit_date,
        s.name AS subject_name,
        g.name AS grade_name,
        sec.name AS section_name,
        vt.name AS visitor_type,
        CONCAT(t.name, ' (', vt.name, ')') AS visitor_name,
        v.total_score,
        (SELECT AVG(ve.score) FROM visit_evaluations ve WHERE ve.visit_id = v.id AND ve.score IS NOT NULL) * (100/3) AS avg_percentage
    FROM 
        visits v
    JOIN 
        subjects s ON v.subject_id = s.id
    JOIN 
        grades g ON v.grade_id = g.id
    JOIN 
        sections sec ON v.section_id = sec.id
    JOIN 
        visitor_types vt ON v.visitor_type_id = vt.id
    JOIN 
        teachers t ON v.visitor_person_id = t.id
    WHERE 
        v.teacher_id = ?
        AND v.academic_year_id = ?
        " . ($selected_term != 'all' ? $date_condition : "") . "
    ORDER BY 
        v.visit_date ASC
", [$teacher_id, $academic_year_id]);

// جلب متوسطات المجالات لكل زيارة
$domain_visits = query("
    SELECT 
        v.id AS visit_id,
        v.visit_date,
        d.id AS domain_id,
        d.name AS domain_name,
        AVG(ve.score) * (100/3) AS avg_percentage
    FROM 
        visits v
    JOIN 
        visit_evaluations ve ON ve.visit_id = v.id
    JOIN 
        evaluation_indicators i ON ve.indicator_id = i.id
    JOIN 
        evaluation_domains d ON i.domain_id = d.id
    WHERE 
        v.teacher_id = ?
        AND v.academic_year_id = ?
        " . ($selected_term != 'all' ? $date_condition : "") . "
        AND ve.score IS NOT NULL
    GROUP BY 
        v.id, v.visit_date, d.id, d.name
    ORDER BY 
        v.visit_date ASC, d.id ASC
", [$teacher_id, $academic_year_id]);

// تنظيم بيانات المجالات حسب الزيارة
$visits_by_domain = [];
$domains_list = [];

foreach ($domain_visits as $visit_domain) {
    if (!in_array($visit_domain['domain_name'], $domains_list)) {
        $domains_list[] = $visit_domain['domain_name'];
    }
    
    if (!isset($visits_by_domain[$visit_domain['visit_id']])) {
        $visits_by_domain[$visit_domain['visit_id']] = [
            'visit_date' => $visit_domain['visit_date'],
            'domains' => []
        ];
    }
    
    $visits_by_domain[$visit_domain['visit_id']]['domains'][$visit_domain['domain_id']] = [
        'name' => $visit_domain['domain_name'],
        'avg_percentage' => $visit_domain['avg_percentage']
    ];
}

// حساب متوسطات المجالات
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
        v.teacher_id = ?
        AND v.academic_year_id = ?
        " . ($selected_term != 'all' ? $date_condition : "") . "
        AND ve.score IS NOT NULL
    GROUP BY 
        d.id, d.name
    ORDER BY 
        d.id
", [$teacher_id, $academic_year_id]);

// حساب المتوسط العام
$overall_avg = 0;
$total_domains = count($domains_avg);
if ($total_domains > 0) {
    $sum_avg = 0;
    foreach ($domains_avg as $domain) {
        $sum_avg += $domain['avg_percentage'];
    }
    $overall_avg = $sum_avg / $total_domains;
}

// جلب أضعف المؤشرات أداءً
$weakest_indicators = query("
    SELECT 
        i.id,
        i.name,
        AVG(ve.score) AS avg_score,
        AVG(ve.score) * (100/3) AS avg_percentage,
        COUNT(DISTINCT v.id) AS visits_count
    FROM 
        evaluation_indicators i
    JOIN 
        visit_evaluations ve ON ve.indicator_id = i.id
    JOIN 
        visits v ON ve.visit_id = v.id
    WHERE 
        v.teacher_id = ?
        AND v.academic_year_id = ?
        " . ($selected_term != 'all' ? $date_condition : "") . "
        AND ve.score IS NOT NULL
    GROUP BY 
        i.id, i.name
    HAVING 
        AVG(ve.score) < 2
    ORDER BY 
        avg_score ASC
    LIMIT 5
", [$teacher_id, $academic_year_id]);

// جلب أقوى المؤشرات أداءً
$strongest_indicators = query("
    SELECT 
        i.id,
        i.name,
        AVG(ve.score) AS avg_score,
        AVG(ve.score) * (100/3) AS avg_percentage,
        COUNT(DISTINCT v.id) AS visits_count
    FROM 
        evaluation_indicators i
    JOIN 
        visit_evaluations ve ON ve.indicator_id = i.id
    JOIN 
        visits v ON ve.visit_id = v.id
    WHERE 
        v.teacher_id = ?
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
", [$teacher_id, $academic_year_id]);

// جلب أكثر التوصيات تكراراً
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
        v.teacher_id = ?
        " . ($academic_year_id > 0 ? "AND v.academic_year_id = ?" : "") . "
        AND ve.recommendation_id IS NOT NULL
    GROUP BY 
        r.text
    ORDER BY 
        count DESC
    LIMIT 5
", $academic_year_id > 0 ? [$teacher_id, $academic_year_id] : [$teacher_id]);

?>

<div class="mb-6">
    <h1 class="text-2xl font-bold mb-4">تقرير أداء المعلم</h1>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <!-- نموذج تحديد الفلاتر -->
        <form action="" method="get" class="mb-6">
            <input type="hidden" name="teacher_id" value="<?= $teacher_id ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="academic_year_id" class="block mb-1">العام الدراسي</label>
                    <select id="academic_year_id" name="academic_year_id" class="w-full rounded border-gray-300" onchange="this.form.submit()">
                        <option value="0">جميع الأعوام الدراسية</option>
                        <?php foreach ($academic_years as $year): ?>
                            <option value="<?= $year['id'] ?>" <?= $academic_year_id == $year['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($year['name']) ?> <?= $year['is_active'] ? '(نشط)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </form>
        
        <!-- معلومات المعلم -->
        <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <h2 class="text-xl font-bold mb-3">معلومات المعلم</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <p class="font-semibold">اسم المعلم: <span class="font-normal"><?= htmlspecialchars($teacher['name']) ?></span></p>
                    <p class="font-semibold">المسمى الوظيفي: <span class="font-normal"><?= htmlspecialchars($teacher['job_title']) ?></span></p>
                </div>
                <div>
                    <p class="font-semibold">المواد التي يدرسها:</p>
                    <ul class="list-disc list-inside">
                        <?php foreach ($teacher_subjects as $subject): ?>
                            <li><?= htmlspecialchars($subject['name']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div>
                    <p class="font-semibold">عدد الزيارات: <span class="font-normal"><?= count($visits) ?></span></p>
                    <?php if ($academic_year_id > 0): ?>
                        <p class="font-semibold">العام الدراسي: <span class="font-normal"><?= htmlspecialchars($academic_year_name) ?></span></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- ملخص الأداء -->
        <div class="mb-8">
            <h2 class="text-xl font-bold mb-4">ملخص الأداء</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- مخطط متوسطات المجالات -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-3">متوسط الأداء حسب المجال</h3>
                    <canvas id="domainsChart" width="400" height="300"></canvas>
                </div>
                
                <!-- المتوسط العام -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-3">المتوسط العام للأداء</h3>
                    <div class="flex flex-col justify-center items-center h-64">
                        <div class="text-center mb-4">
                            <div class="text-5xl font-bold mb-2"><?= !is_null($overall_avg) ? number_format($overall_avg, 1) : '-' ?>%</div>
                            <div class="text-xl"><?= !is_null($overall_avg) ? get_grade($overall_avg * 3 / 100) : '-' ?></div>
                        </div>
                        <div class="w-48 h-48">
                            <canvas id="pieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- تطور أداء المعلم -->
        <div class="mb-8">
            <h2 class="text-xl font-bold mb-4">تطور أداء المعلم</h2>
            
            <div class="grid grid-cols-1 gap-6">
                <!-- رسم بياني لتطور الأداء -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-3">مخطط تطور الأداء عبر الزيارات</h3>
                    <canvas id="progressChart" width="800" height="400"></canvas>
                </div>
                
                <!-- إحصائيات تحسن الأداء -->
                <?php if (count($visits_by_domain) >= 2): ?>
                <?php
                    // حساب نسبة التحسن من أول زيارة لآخر زيارة
                    $first_visit = reset($visits_by_domain);
                    $last_visit = end($visits_by_domain);
                    
                    $first_avg = 0;
                    $first_domains_count = 0;
                    foreach ($first_visit['domains'] as $domain) {
                        $first_avg += $domain['avg_percentage'];
                        $first_domains_count++;
                    }
                    $first_avg = $first_domains_count > 0 ? $first_avg / $first_domains_count : 0;
                    
                    $last_avg = 0;
                    $last_domains_count = 0;
                    foreach ($last_visit['domains'] as $domain) {
                        $last_avg += $domain['avg_percentage'];
                        $last_domains_count++;
                    }
                    $last_avg = $last_domains_count > 0 ? $last_avg / $last_domains_count : 0;
                    
                    $improvement = $last_avg - $first_avg;
                    $improvement_percentage = $first_avg > 0 ? ($improvement / $first_avg) * 100 : 0;
                    
                    // حساب عدد المجالات التي تحسنت
                    $improved_domains = 0;
                    $declined_domains = 0;
                    $stable_domains = 0;
                    
                    foreach ($domains_list as $domain_name) {
                        $first_score = null;
                        $last_score = null;
                        
                        // البحث عن المجال في الزيارة الأولى
                        foreach ($first_visit['domains'] as $domain_id => $domain) {
                            if ($domain['name'] == $domain_name) {
                                $first_score = $domain['avg_percentage'];
                                break;
                            }
                        }
                        
                        // البحث عن المجال في الزيارة الأخيرة
                        foreach ($last_visit['domains'] as $domain_id => $domain) {
                            if ($domain['name'] == $domain_name) {
                                $last_score = $domain['avg_percentage'];
                                break;
                            }
                        }
                        
                        if ($first_score !== null && $last_score !== null) {
                            $change = $last_score - $first_score;
                            if ($change > 5) {
                                $improved_domains++;
                            } elseif ($change < -5) {
                                $declined_domains++;
                            } else {
                                $stable_domains++;
                            }
                        }
                    }
                ?>
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-3">إحصائيات تطور الأداء</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="text-lg font-semibold mb-2">نسبة التحسن العامة</div>
                            <div class="text-3xl font-bold <?= $improvement >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                                <?= $improvement >= 0 ? '+' : '' ?><?= number_format($improvement, 1) ?>%
                            </div>
                            <div class="text-sm text-gray-600 mt-2">
                                من أول زيارة (<?= number_format($first_avg, 1) ?>%) إلى آخر زيارة (<?= number_format($last_avg, 1) ?>%)
                            </div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="text-lg font-semibold mb-2">المجالات المتحسنة</div>
                            <div class="text-3xl font-bold text-green-600"><?= $improved_domains ?></div>
                            <div class="text-sm text-gray-600 mt-2">
                                من أصل <?= count($domains_list) ?> مجال
                                (<?= count($domains_list) > 0 ? number_format(($improved_domains / count($domains_list)) * 100, 0) : 0 ?>%)
                            </div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="text-lg font-semibold mb-2">المجالات المتراجعة</div>
                            <div class="text-3xl font-bold text-red-600"><?= $declined_domains ?></div>
                            <div class="text-sm text-gray-600 mt-2">
                                من أصل <?= count($domains_list) ?> مجال
                                (<?= count($domains_list) > 0 ? number_format(($declined_domains / count($domains_list)) * 100, 0) : 0 ?>%)
                            </div>
                        </div>
                    </div>
                    <?php if ($improvement >= 10): ?>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-green-800">
                            <strong>ملاحظة:</strong> أداء المعلم يظهر تحسناً ملحوظاً منذ بداية الزيارات. استمر في الاستراتيجيات الحالية.
                        </div>
                    <?php elseif ($improvement >= 0): ?>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-blue-800">
                            <strong>ملاحظة:</strong> أداء المعلم يظهر تحسناً طفيفاً. ركز على المجالات التي تحتاج إلى تطوير.
                        </div>
                    <?php else: ?>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-red-800">
                            <strong>ملاحظة:</strong> أداء المعلم يظهر تراجعاً عن الزيارة الأولى. يجب مراجعة التوصيات السابقة ووضع خطة تحسين.
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- جدول تفاصيل تطور الأداء حسب المجال -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-3">تفاصيل تطور الأداء حسب المجال</h3>
                    
                    <?php if (count($visits_by_domain) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاريخ الزيارة</th>
                                        <?php foreach ($domains_list as $domain_name): ?>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"><?= htmlspecialchars($domain_name) ?></th>
                                        <?php endforeach; ?>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المتوسط العام</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php 
                                    $previous_visit = null;
                                    foreach ($visits_by_domain as $visit_id => $visit_data): 
                                        // حساب متوسط الزيارة
                                        $visit_avg = 0;
                                        $domains_count = 0;
                                        foreach ($visit_data['domains'] as $domain) {
                                            $visit_avg += $domain['avg_percentage'];
                                            $domains_count++;
                                        }
                                        $visit_avg = $domains_count > 0 ? $visit_avg / $domains_count : 0;
                                    ?>
                                        <tr>
                                            <td class="px-4 py-4 whitespace-nowrap"><?= format_date_ar($visit_data['visit_date']) ?></td>
                                            <?php 
                                            foreach ($domains_list as $idx => $domain_name): 
                                                // البحث عن المجال في هذه الزيارة
                                                $domain_score = null;
                                                $prev_domain_score = null;
                                                
                                                foreach ($visit_data['domains'] as $domain_id => $domain) {
                                                    if ($domain['name'] == $domain_name) {
                                                        $domain_score = $domain['avg_percentage'];
                                                        break;
                                                    }
                                                }
                                                
                                                // البحث عن نفس المجال في الزيارة السابقة إن وجدت
                                                if ($previous_visit) {
                                                    foreach ($previous_visit['domains'] as $prev_domain_id => $prev_domain) {
                                                        if ($prev_domain['name'] == $domain_name) {
                                                            $prev_domain_score = $prev_domain['avg_percentage'];
                                                            break;
                                                        }
                                                    }
                                                }
                                                
                                                // تحديد اللون بناء على التطور
                                                $colorClass = '';
                                                $change_icon = '';
                                                $change_text = '';
                                                
                                                if ($domain_score !== null) {
                                                    if ($prev_domain_score !== null) {
                                                        $change = $domain_score - $prev_domain_score;
                                                        if ($change > 5) {
                                                            $colorClass = 'text-green-600';
                                                            $change_icon = '↑';
                                                            $change_text = '(' . number_format($change, 1) . '%)';
                                                        } elseif ($change < -5) {
                                                            $colorClass = 'text-red-600';
                                                            $change_icon = '↓';
                                                            $change_text = '(' . number_format($change, 1) . '%)';
                                                        } else {
                                                            $colorClass = 'text-gray-600';
                                                            $change_icon = '↔';
                                                        }
                                                    } else {
                                                        $colorClass = 'text-blue-600';
                                                    }
                                                }
                                            ?>
                                                <td class="px-4 py-4 whitespace-nowrap">
                                                    <?php if ($domain_score !== null): ?>
                                                        <span class="font-bold <?= $colorClass ?>">
                                                            <?= number_format($domain_score, 1) ?>% <?= $change_icon ?> <?= $change_text ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-gray-400">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                            
                                            <?php
                                            // حساب نسبة التغيير في المتوسط العام
                                            $avg_change_class = '';
                                            $avg_change_icon = '';
                                            $avg_change_text = '';
                                            
                                            if ($previous_visit) {
                                                $prev_visit_avg = 0;
                                                $prev_domains_count = 0;
                                                foreach ($previous_visit['domains'] as $prev_domain) {
                                                    $prev_visit_avg += $prev_domain['avg_percentage'];
                                                    $prev_domains_count++;
                                                }
                                                $prev_visit_avg = $prev_domains_count > 0 ? $prev_visit_avg / $prev_domains_count : 0;
                                                
                                                $avg_change = $visit_avg - $prev_visit_avg;
                                                if ($avg_change > 5) {
                                                    $avg_change_class = 'text-green-600';
                                                    $avg_change_icon = '↑';
                                                    $avg_change_text = '(' . number_format($avg_change, 1) . '%)';
                                                } elseif ($avg_change < -5) {
                                                    $avg_change_class = 'text-red-600';
                                                    $avg_change_icon = '↓';
                                                    $avg_change_text = '(' . number_format($avg_change, 1) . '%)';
                                                } else {
                                                    $avg_change_class = 'text-gray-600';
                                                    $avg_change_icon = '↔';
                                                }
                                            } else {
                                                $avg_change_class = 'text-blue-600';
                                            }
                                            ?>
                                            
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <span class="font-bold <?= $avg_change_class ?>">
                                                    <?= number_format($visit_avg, 1) ?>% <?= $avg_change_icon ?> <?= $avg_change_text ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php 
                                        $previous_visit = $visit_data;
                                    endforeach; 
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500">لا توجد بيانات كافية لعرض تطور الأداء</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- نقاط القوة والضعف -->
        <div class="mb-8">
            <h2 class="text-xl font-bold mb-4">نقاط القوة والضعف</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- أقوى المؤشرات -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-3 text-green-700">أقوى المؤشرات أداءً</h3>
                    <?php if (count($strongest_indicators) > 0): ?>
                        <ul class="space-y-2">
                            <?php foreach ($strongest_indicators as $indicator): ?>
                                <li class="border-b pb-2">
                                    <div class="flex justify-between">
                                        <span><?= htmlspecialchars($indicator['name']) ?></span>
                                        <span class="font-bold"><?= number_format($indicator['avg_percentage'], 1) ?>%</span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-gray-500">لا توجد بيانات كافية</p>
                    <?php endif; ?>
                </div>
                
                <!-- أضعف المؤشرات -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-3 text-red-700">مؤشرات تحتاج إلى تحسين</h3>
                    <?php if (count($weakest_indicators) > 0): ?>
                        <ul class="space-y-2">
                            <?php foreach ($weakest_indicators as $indicator): ?>
                                <li class="border-b pb-2">
                                    <div class="flex justify-between">
                                        <span><?= htmlspecialchars($indicator['name']) ?></span>
                                        <span class="font-bold"><?= number_format($indicator['avg_percentage'], 1) ?>%</span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-gray-500">لا توجد مؤشرات ضعيفة</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- أكثر التوصيات تكراراً -->
        <div class="mb-8">
            <h2 class="text-xl font-bold mb-4">أكثر التوصيات تكراراً</h2>
            
            <div class="bg-white p-4 rounded-lg border border-gray-200">
                <?php if (count($common_recommendations) > 0): ?>
                    <ul class="space-y-2">
                        <?php foreach ($common_recommendations as $recommendation): ?>
                            <li class="border-b pb-2">
                                <div class="flex justify-between">
                                    <span><?= htmlspecialchars($recommendation['text']) ?></span>
                                    <span class="font-bold"><?= $recommendation['count'] ?> مرات</span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-gray-500">لا توجد توصيات مسجلة</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- سجل الزيارات -->
        <div class="mb-8">
            <h2 class="text-xl font-bold mb-4">سجل الزيارات</h2>
            
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <?php if (count($visits) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاريخ الزيارة</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المادة</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الصف/الشعبة</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الزائر</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">النسبة</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($visits as $visit): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap"><?= format_date_ar($visit['visit_date']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($visit['subject_name']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($visit['grade_name']) ?> / <?= htmlspecialchars($visit['section_name']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($visit['visitor_name']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php 
                                            $percentage = number_format($visit['avg_percentage'], 1);
                                            $colorClass = '';
                                            if ($percentage >= 90) $colorClass = 'text-green-600';
                                            elseif ($percentage >= 75) $colorClass = 'text-blue-600';
                                            elseif ($percentage >= 60) $colorClass = 'text-yellow-600';
                                            else $colorClass = 'text-red-600';
                                            ?>
                                            <span class="font-medium <?= $colorClass ?>"><?= $percentage ?>%</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="view_visit.php?id=<?= $visit['id'] ?>" class="text-primary-600 hover:text-primary-900">عرض</a>
                                            <a href="print_visit.php?id=<?= $visit['id'] ?>" class="text-primary-600 hover:text-primary-900 mr-3">طباعة</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="p-4 text-gray-500">لا توجد زيارات مسجلة لهذا المعلم</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- إضافة مكتبة Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// بيانات المخطط
const domainsData = {
    labels: [
        <?php foreach ($domains_avg as $domain): ?>
            '<?= htmlspecialchars($domain['name']) ?>',
        <?php endforeach; ?>
    ],
    datasets: [{
        label: 'متوسط الأداء (%)',
        data: [
            <?php foreach ($domains_avg as $domain): ?>
                <?= number_format($domain['avg_percentage'], 1) ?>,
            <?php endforeach; ?>
        ],
        backgroundColor: [
            'rgba(54, 162, 235, 0.5)',
            'rgba(255, 99, 132, 0.5)',
            'rgba(255, 206, 86, 0.5)',
            'rgba(75, 192, 192, 0.5)',
            'rgba(153, 102, 255, 0.5)'
        ],
        borderColor: [
            'rgba(54, 162, 235, 1)',
            'rgba(255, 99, 132, 1)',
            'rgba(255, 206, 86, 1)',
            'rgba(75, 192, 192, 1)',
            'rgba(153, 102, 255, 1)'
        ],
        borderWidth: 1
    }]
};

// إنشاء المخطط
const ctx = document.getElementById('domainsChart').getContext('2d');
const domainsChart = new Chart(ctx, {
    type: 'bar',
    data: domainsData,
    options: {
        indexAxis: 'y',
        scales: {
            x: {
                beginAtZero: true,
                max: 100,
                title: {
                    display: true,
                    text: 'النسبة المئوية (%)'
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// بيانات ومخطط تطور الأداء
const progressData = {
    labels: [
        <?php 
        foreach ($visits_by_domain as $visit_id => $visit_data): 
            echo "'" . format_date_ar($visit_data['visit_date']) . "',";
        endforeach;
        ?>
    ],
    datasets: [
        <?php
        // إنشاء مجموعة بيانات لكل مجال
        foreach ($domains_list as $domain_idx => $domain_name):
            $color_idx = $domain_idx % 5;
            $background_colors = [
                'rgba(54, 162, 235, 0.2)',
                'rgba(255, 99, 132, 0.2)',
                'rgba(255, 206, 86, 0.2)',
                'rgba(75, 192, 192, 0.2)',
                'rgba(153, 102, 255, 0.2)'
            ];
            $border_colors = [
                'rgba(54, 162, 235, 1)',
                'rgba(255, 99, 132, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)'
            ];
        ?>
        {
            label: '<?= htmlspecialchars($domain_name) ?>',
            data: [
                <?php
                foreach ($visits_by_domain as $visit_id => $visit_data): 
                    $found = false;
                    foreach ($visit_data['domains'] as $domain_id => $domain) {
                        if ($domain['name'] == $domain_name) {
                            echo number_format($domain['avg_percentage'], 1) . ",";
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        echo "null,";
                    }
                endforeach;
                ?>
            ],
            backgroundColor: '<?= $background_colors[$color_idx] ?>',
            borderColor: '<?= $border_colors[$color_idx] ?>',
            borderWidth: 2,
            tension: 0.3
        },
        <?php endforeach; ?>
        
        // متوسط الأداء العام
        {
            label: 'المتوسط العام',
            data: [
                <?php
                foreach ($visits_by_domain as $visit_id => $visit_data): 
                    $visit_avg = 0;
                    $domains_count = 0;
                    foreach ($visit_data['domains'] as $domain) {
                        if (!is_null($domain['avg_percentage'])) {
                            $visit_avg += $domain['avg_percentage'];
                            $domains_count++;
                        }
                    }
                    $visit_avg = $domains_count > 0 ? $visit_avg / $domains_count : 0;
                    echo (!is_null($visit_avg) ? number_format($visit_avg, 1) : "0") . ",";
                endforeach;
                ?>
            ],
            backgroundColor: 'rgba(0, 0, 0, 0.1)',
            borderColor: 'rgba(0, 0, 0, 0.8)',
            borderWidth: 3,
            borderDash: [5, 5],
            tension: 0.3
        }
    ]
};

// إنشاء مخطط تطور الأداء
const progressCtx = document.getElementById('progressChart').getContext('2d');
const progressChart = new Chart(progressCtx, {
    type: 'line',
    data: progressData,
    options: {
        scales: {
            y: {
                beginAtZero: false,
                suggestedMin: 50,
                suggestedMax: 100,
                title: {
                    display: true,
                    text: 'النسبة المئوية (%)'
                }
            }
        },
        plugins: {
            legend: {
                position: 'top',
                align: 'start',
                rtl: true
            },
            tooltip: {
                rtl: true,
                textDirection: 'rtl'
            }
        },
        elements: {
            line: {
                fill: false
            }
        }
    }
});

// إنشاء مخطط دائري للمتوسط العام
const pieCtx = document.getElementById('pieChart').getContext('2d');
const pieChart = new Chart(pieCtx, {
    type: 'doughnut',
    data: {
        labels: ['نسبة الأداء', 'متبقي'],
        datasets: [{
            data: [
                <?= !is_null($overall_avg) ? number_format($overall_avg, 1) : 0 ?>,
                <?= !is_null($overall_avg) ? number_format(100 - $overall_avg, 1) : 100 ?>
            ],
            backgroundColor: [
                'rgba(75, 192, 192, 0.7)',
                'rgba(220, 220, 220, 0.5)'
            ],
            borderColor: [
                'rgba(75, 192, 192, 1)',
                'rgba(220, 220, 220, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                enabled: false
            }
        }
    }
});
</script>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?> 