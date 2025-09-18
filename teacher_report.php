<?php
// استخدام القوانين الموحدة لنظام الزيارات الصفية
require_once 'visit_rules.php';

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

// التحقق من صلاحيات الوصول لتقرير المعلم
if ($user_role_name === 'Teacher') {
    // المعلم يمكنه رؤية تقريره فقط
    $user_teacher_data = query_row("SELECT id FROM teachers WHERE user_id = ?", [$user_id]);
    if (!$user_teacher_data || $teacher_id != $user_teacher_data['id']) {
        echo show_alert("غير مسموح لك بعرض تقرير هذا المعلم", "error");
        require_once 'includes/footer.php';
        exit;
    }
} elseif ($user_role_name === 'Subject Coordinator') {
    // منسق المادة يمكنه رؤية تقارير معلمي مادته فقط
    $coordinator_data = query_row("
        SELECT subject_id 
        FROM coordinator_supervisors 
        WHERE user_id = ?
    ", [$user_id]);
    
    if ($coordinator_data) {
        $teacher_teaches_subject = query_row("
            SELECT 1 FROM teacher_subjects 
            WHERE teacher_id = ? AND subject_id = ?
        ", [$teacher_id, $coordinator_data['subject_id']]);
        
        if (!$teacher_teaches_subject) {
            echo show_alert("غير مسموح لك بعرض تقرير هذا المعلم", "error");
            require_once 'includes/footer.php';
            exit;
        }
    } else {
        echo show_alert("لم يتم تخصيص مادة لك", "error");
        require_once 'includes/footer.php';
        exit;
    }
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

// جلب بيانات العام الدراسي المحدد
$selected_academic_year = null;
if ($academic_year_id > 0) {
    $selected_academic_year = query_row("SELECT id, name FROM academic_years WHERE id = ?", [$academic_year_id]);
}
// إذا لم يتم العثور على العام المحدد، استخدم العام الأول من القائمة
if (!$selected_academic_year && !empty($academic_years)) {
    $selected_academic_year = $academic_years[0];
}

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
        (SELECT (AVG(ve.score) / " . MAX_INDICATOR_SCORE . ") * 100 FROM visit_evaluations ve WHERE ve.visit_id = v.id AND ve.score IS NOT NULL) AS avg_percentage
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

// جلب متوسطات المجالات لكل زيارة (استبعاد المعمل للمواد غير العلمية)
$domain_visits = query("
    SELECT 
        v.id AS visit_id,
        v.visit_date,
        d.id AS domain_id,
        d.name AS domain_name,
        (AVG(ve.score) / " . MAX_INDICATOR_SCORE . ") * 100 AS avg_percentage,
        COUNT(ve.score) AS evaluated_indicators
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
        AND ve.score > 0
        AND (
            d.id != 5 OR 
            (d.id = 5 AND v.has_lab = 1)
        )
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
        (AVG(ve.score) / " . MAX_INDICATOR_SCORE . ") * 100 AS avg_percentage
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

// جلب أضعف المؤشرات أداءً (من المجالات 1-4 فقط، واستبعاد المعمل)
$weakest_indicators = query("
    SELECT 
        i.id,
        i.name,
        d.name as domain_name,
        AVG(ve.score) AS avg_score,
        (AVG(ve.score) / " . MAX_INDICATOR_SCORE . ") * 100 AS avg_percentage,
        COUNT(DISTINCT v.id) AS visits_count
    FROM 
        evaluation_indicators i
    JOIN 
        evaluation_domains d ON i.domain_id = d.id
    JOIN 
        visit_evaluations ve ON ve.indicator_id = i.id
    JOIN 
        visits v ON ve.visit_id = v.id
    WHERE 
        v.teacher_id = ?
        AND v.academic_year_id = ?
        " . ($selected_term != 'all' ? $date_condition : "") . "
        AND ve.score IS NOT NULL
        AND ve.score > 0
        AND i.domain_id IN (1, 2, 3, 4)
    GROUP BY 
        i.id, i.name, d.name
    HAVING 
        AVG(ve.score) < 2.5
        AND COUNT(DISTINCT v.id) >= 2
    ORDER BY 
        avg_score ASC
    LIMIT 5
", [$teacher_id, $academic_year_id]);

// جلب أقوى المؤشرات أداءً (من جميع المجالات المُقيمة فعلياً)
$strongest_indicators = query("
    SELECT 
        i.id,
        i.name,
        d.name as domain_name,
        AVG(ve.score) AS avg_score,
        (AVG(ve.score) / " . MAX_INDICATOR_SCORE . ") * 100 AS avg_percentage,
        COUNT(DISTINCT v.id) AS visits_count
    FROM 
        evaluation_indicators i
    JOIN 
        evaluation_domains d ON i.domain_id = d.id
    JOIN 
        visit_evaluations ve ON ve.indicator_id = i.id
    JOIN 
        visits v ON ve.visit_id = v.id
    WHERE 
        v.teacher_id = ?
        AND v.academic_year_id = ?
        " . ($selected_term != 'all' ? $date_condition : "") . "
        AND ve.score IS NOT NULL
        AND ve.score > 0
    GROUP BY 
        i.id, i.name, d.name
    HAVING 
        AVG(ve.score) >= 2.8
        AND COUNT(DISTINCT v.id) >= 2
    ORDER BY 
        avg_score DESC, visits_count DESC
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
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold">تقرير أداء المعلم</h1>
        
        <!-- أزرار الطباعة -->
        <div class="flex gap-3 no-print">
            <button onclick="printReport()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg flex items-center gap-2 transition-colors duration-200">
                <i class="fas fa-print"></i>
                طباعة تقرير أداء المعلم
            </button>
            <button onclick="generatePDF()" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg flex items-center gap-2 transition-colors duration-200">
                <i class="fas fa-file-pdf"></i>
                حفظ كملف PDF
            </button>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        
        <!-- نموذج تحديد الفلاتر -->
        <form action="" method="get" class="mb-6 no-print">
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
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-6 mb-6">
            <div class="flex items-center mb-4">
                <div class="bg-blue-100 p-3 rounded-full ml-4">
                    <i class="fas fa-user-graduate text-blue-600 text-xl"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">معلومات المعلم</h2>
                    <p class="text-gray-600">البيانات الأساسية وإحصائيات الأداء</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- اسم المعلم -->
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-user text-blue-600 ml-2"></i>
                        <span class="text-sm font-medium text-gray-600">اسم المعلم</span>
                    </div>
                    <p class="text-lg font-bold text-gray-800"><?= htmlspecialchars($teacher['name']) ?></p>
                    <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($teacher['job_title']) ?></p>
                </div>

                <!-- المواد التي يدرسها -->
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-book text-green-600 ml-2"></i>
                        <span class="text-sm font-medium text-gray-600">المواد التدريسية</span>
                    </div>
                    <?php if (!empty($teacher_subjects)): ?>
                        <div class="space-y-1">
                            <?php foreach ($teacher_subjects as $subject): ?>
                                <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-medium">
                                    <?= htmlspecialchars($subject['name']) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 text-sm">لا توجد مواد محددة</p>
                    <?php endif; ?>
                </div>

                <!-- عدد الزيارات -->
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-clipboard-check text-purple-600 ml-2"></i>
                        <span class="text-sm font-medium text-gray-600">عدد الزيارات</span>
                    </div>
                    <p class="text-2xl font-bold text-purple-600"><?= count($visits) ?></p>
                    <p class="text-sm text-gray-500">زيارة صفية</p>
                </div>

                <!-- العام الدراسي -->
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-calendar-alt text-orange-600 ml-2"></i>
                        <span class="text-sm font-medium text-gray-600">العام الدراسي</span>
                    </div>
                    <p class="text-lg font-bold text-orange-600">
                        <?= $academic_year_id > 0 ? htmlspecialchars($academic_year_name) : 'جميع الأعوام' ?>
                    </p>
                    <?php if ($selected_term && $selected_term !== 'all'): ?>
                        <p class="text-sm text-gray-500">
                            <?= $selected_term === 'first' ? 'الفصل الأول' : 'الفصل الثاني' ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- ملخص الأداء -->
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg p-6 mb-6">
            <div class="flex items-center mb-4">
                <div class="bg-green-100 p-3 rounded-full ml-4">
                    <i class="fas fa-chart-line text-green-600 text-xl"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">ملخص الأداء العام</h2>
                    <p class="text-gray-600">المتوسطات والإحصائيات الرئيسية</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- المتوسط العام -->
                <div class="bg-white p-4 rounded-lg shadow-sm border-r-4 border-blue-500">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-percentage text-blue-600 ml-2"></i>
                        <span class="text-sm font-medium text-gray-600">المتوسط العام</span>
                    </div>
                    <p class="text-3xl font-bold text-blue-600"><?= number_format($overall_avg, 1) ?>%</p>
                    <p class="text-sm text-gray-500">من 100%</p>
                </div>

                <!-- عدد المجالات -->
                <div class="bg-white p-4 rounded-lg shadow-sm border-r-4 border-green-500">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-layer-group text-green-600 ml-2"></i>
                        <span class="text-sm font-medium text-gray-600">المجالات المقيمة</span>
                    </div>
                    <p class="text-3xl font-bold text-green-600"><?= count($domains_avg) ?></p>
                    <p class="text-sm text-gray-500">مجال تقييم</p>
                </div>

                <!-- أقوى المؤشرات -->
                <div class="bg-white p-4 rounded-lg shadow-sm border-r-4 border-emerald-500">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-arrow-up text-emerald-600 ml-2"></i>
                        <span class="text-sm font-medium text-gray-600">نقاط القوة</span>
                    </div>
                    <p class="text-3xl font-bold text-emerald-600"><?= count($strongest_indicators) ?></p>
                    <p class="text-sm text-gray-500">مؤشر قوي</p>
                </div>

                <!-- المؤشرات التي تحتاج تحسين -->
                <div class="bg-white p-4 rounded-lg shadow-sm border-r-4 border-amber-500">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-exclamation-triangle text-amber-600 ml-2"></i>
                        <span class="text-sm font-medium text-gray-600">يحتاج تحسين</span>
                    </div>
                    <p class="text-3xl font-bold text-amber-600"><?= count($weakest_indicators) ?></p>
                    <p class="text-sm text-gray-500">مؤشر ضعيف</p>
                </div>
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
                                أداء المعلم يظهر تحسناً ملحوظاً منذ بداية الزيارات. استمر في الاستراتيجيات الحالية.
                            <?php elseif ($progress_stats['improvement'] < 0): ?>
                                هناك تراجع في أداء المعلم يتطلب مراجعة الاستراتيجيات المتبعة وتطوير خطط تحسين.
                            <?php else: ?>
                                أداء المعلم مستقر، يُنصح بتطوير استراتيجيات جديدة لتحقيق نمو إضافي.
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- الرسوم البيانية -->
        <div class="mb-8">
            <h2 class="text-xl font-bold mb-4">الرسوم البيانية</h2>
            
            <!-- السطر الأول: مخطط المجالات + المتوسط العام -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- مخطط متوسطات المجالات -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-3">متوسط الأداء حسب المجال</h3>
                    <canvas id="domainsChart" width="400" height="300"></canvas>
                    
                    <!-- نسخة للطباعة -->
                    <div class="chart-print-version">
                        <h4 style="font-weight: bold; margin-bottom: 8px;">متوسط الأداء حسب المجال</h4>
                        <?php foreach ($domains_avg as $index => $domain): ?>
                            <div style="margin: 3px 0; padding: 2px; background: <?= $index % 2 == 0 ? '#f8fafc' : 'white' ?>;">
                                <strong><?= htmlspecialchars($domain['name']) ?>:</strong> 
                                <?php 
                                $performance_level = getPerformanceLevel($domain['avg_percentage']);
                                $color_style = '';
                                if (strpos($performance_level['color_class'], 'text-green') !== false) $color_style = '#16a34a';
                                elseif (strpos($performance_level['color_class'], 'text-blue') !== false) $color_style = '#2563eb';
                                elseif (strpos($performance_level['color_class'], 'text-yellow') !== false) $color_style = '#ca8a04';
                                elseif (strpos($performance_level['color_class'], 'text-orange') !== false) $color_style = '#ea580c';
                                else $color_style = '#dc2626';
                                ?>
                                <span style="color: <?= $color_style ?>;">
                                    <?= number_format($domain['avg_percentage'], 1) ?>%
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- المتوسط العام - الرسم الدائري -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-3">المتوسط العام للأداء</h3>
                    <div class="flex flex-col justify-center items-center h-64">
                        <div class="text-center mb-4">
                            <div class="text-5xl font-bold mb-2 text-blue-600"><?= !is_null($overall_avg) ? number_format($overall_avg, 1) : '-' ?>%</div>
                            <div class="text-xl text-gray-600"><?= !is_null($overall_avg) ? get_grade($overall_avg * 3 / 100) : '-' ?></div>
                        </div>
                        <div class="w-48 h-48">
                            <canvas id="pieChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- نسخة للطباعة -->
                    <div class="chart-print-version">
                        <h4 style="font-weight: bold; margin-bottom: 10px; text-align: center;">المتوسط العام للأداء</h4>
                        <div style="text-align: center; padding: 15px; background: linear-gradient(90deg, #dbeafe, #eff6ff); border: 2px solid #3b82f6; border-radius: 8px;">
                            <div style="font-size: 24px; font-weight: bold; color: #1e40af; margin-bottom: 5px;">
                                <?= !is_null($overall_avg) ? number_format($overall_avg, 1) : '-' ?>%
                            </div>
                            <div style="font-size: 14px; color: #374151; font-weight: bold;">
                                <?= !is_null($overall_avg) ? get_grade($overall_avg * 3 / 100) : '-' ?>
                            </div>
                            <div style="margin-top: 8px; font-size: 9px; color: #6b7280;">
                                من أصل <?= count($visits) ?> زيارة إشرافية
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- السطر الثاني: مخطط تطور الأداء -->
            <div class="grid grid-cols-1 gap-6">
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-3">تطور الأداء عبر الزيارات</h3>
                    <canvas id="progressChart" width="800" height="400"></canvas>
                    
                    <!-- نسخة للطباعة -->
                    <div class="chart-print-version">
                        <h4 style="font-weight: bold; margin-bottom: 8px;">تطور الأداء عبر الزيارات</h4>
                        <?php if (!empty($visits)): ?>
                            <table style="width: 100%; font-size: 8px; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: #f3f4f6;">
                                        <th style="border: 1px solid #d1d5db; padding: 3px;">التاريخ</th>
                                        <th style="border: 1px solid #d1d5db; padding: 3px;">المادة</th>
                                        <th style="border: 1px solid #d1d5db; padding: 3px;">النسبة</th>
                                        <th style="border: 1px solid #d1d5db; padding: 3px;">التقدير</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($visits as $visit): ?>
                                        <tr>
                                            <td style="border: 1px solid #d1d5db; padding: 2px;">
                                                <?= date('Y/m/d', strtotime($visit['visit_date'])) ?>
                                            </td>
                                            <td style="border: 1px solid #d1d5db; padding: 2px;">
                                                <?= htmlspecialchars($visit['subject_name']) ?>
                                            </td>
                                            <td style="border: 1px solid #d1d5db; padding: 2px; text-align: center; color: <?php 
                                                $performance_level = getPerformanceLevel($visit['avg_percentage']);
                                                $color_style = '';
                                                if (strpos($performance_level['color_class'], 'text-green') !== false) echo '#16a34a';
                                                elseif (strpos($performance_level['color_class'], 'text-blue') !== false) echo '#2563eb';
                                                elseif (strpos($performance_level['color_class'], 'text-yellow') !== false) echo '#ca8a04';
                                                elseif (strpos($performance_level['color_class'], 'text-orange') !== false) echo '#ea580c';
                                                else echo '#dc2626';
                                            ?>;">
                                                <?= number_format($visit['avg_percentage'], 1) ?>%
                                            </td>
                                            <td style="border: 1px solid #d1d5db; padding: 2px;">
                                                <?= get_grade($visit['avg_percentage']) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div style="text-align: center; padding: 20px; color: #6b7280;">
                                لا توجد زيارات مسجلة
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- متوسطات المجالات -->
        <div class="mb-8">
            <h2 class="text-xl font-bold mb-4">متوسطات المجالات</h2>
                
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
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 p-4 rounded-lg border border-green-200">
                    <div class="flex items-center mb-3">
                        <i class="fas fa-star text-green-600 ml-2"></i>
                        <h3 class="text-lg font-semibold text-green-700">نقاط القوة المميزة</h3>
                    </div>
                    <?php if (count($strongest_indicators) > 0): ?>
                        <div class="space-y-3">
                            <?php foreach ($strongest_indicators as $indicator): ?>
                                <div class="bg-white p-3 rounded-lg border-r-4 border-green-500 shadow-sm">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <p class="font-medium text-gray-800 text-sm leading-relaxed">
                                                <?= htmlspecialchars($indicator['name']) ?>
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                <i class="fas fa-layer-group ml-1"></i>
                                                <?= htmlspecialchars($indicator['domain_name']) ?>
                                                • <?= $indicator['visits_count'] ?> زيارة
                                            </p>
                                        </div>
                                        <div class="mr-3 text-left">
                                            <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded text-sm font-bold">
                                                <?= number_format($indicator['avg_percentage'], 1) ?>%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-info-circle text-gray-400 text-2xl mb-2"></i>
                            <p class="text-gray-500">لا توجد نقاط قوة مميزة بعد</p>
                            <p class="text-xs text-gray-400">يحتاج المعلم لمزيد من الزيارات للتقييم</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- أضعف المؤشرات -->
                <div class="bg-gradient-to-br from-amber-50 to-orange-50 p-4 rounded-lg border border-amber-200">
                    <div class="flex items-center mb-3">
                        <i class="fas fa-exclamation-triangle text-amber-600 ml-2"></i>
                        <h3 class="text-lg font-semibold text-amber-700">نقاط تحتاج لتطوير</h3>
                    </div>
                    <?php if (count($weakest_indicators) > 0): ?>
                        <div class="space-y-3">
                            <?php foreach ($weakest_indicators as $indicator): ?>
                                <div class="bg-white p-3 rounded-lg border-r-4 border-amber-500 shadow-sm">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <p class="font-medium text-gray-800 text-sm leading-relaxed">
                                                <?= htmlspecialchars($indicator['name']) ?>
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                <i class="fas fa-layer-group ml-1"></i>
                                                <?= htmlspecialchars($indicator['domain_name']) ?>
                                                • <?= $indicator['visits_count'] ?> زيارة
                                            </p>
                                        </div>
                                        <div class="mr-3 text-left">
                                            <span class="inline-block bg-amber-100 text-amber-800 px-2 py-1 rounded text-sm font-bold">
                                                <?= number_format($indicator['avg_percentage'], 1) ?>%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-thumbs-up text-green-400 text-2xl mb-2"></i>
                            <p class="text-green-600 font-medium">ممتاز! لا توجد نقاط ضعف</p>
                            <p class="text-xs text-gray-500">جميع المؤشرات المُقيمة في مستوى جيد</p>
                        </div>
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
        $datasets = [];
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
            
            $data_points = [];
            foreach ($visits_by_domain as $visit_id => $visit_data): 
                $found = false;
                foreach ($visit_data['domains'] as $domain_id => $domain) {
                    if ($domain['name'] == $domain_name) {
                        $data_points[] = number_format($domain['avg_percentage'], 1);
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $data_points[] = "null";
                }
            endforeach;
            
            $datasets[] = "{
                label: '" . htmlspecialchars($domain_name) . "',
                data: [" . implode(',', $data_points) . "],
                backgroundColor: '" . $background_colors[$color_idx] . "',
                borderColor: '" . $border_colors[$color_idx] . "',
                borderWidth: 2,
                tension: 0.3
            }";
        endforeach;
        
        // متوسط الأداء العام
        $avg_data_points = [];
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
            $avg_data_points[] = (!is_null($visit_avg) ? number_format($visit_avg, 1) : "0");
        endforeach;
        
        $datasets[] = "{
            label: 'المتوسط العام',
            data: [" . implode(',', $avg_data_points) . "],
            backgroundColor: 'rgba(0, 0, 0, 0.1)',
            borderColor: 'rgba(0, 0, 0, 0.8)',
            borderWidth: 3,
            borderDash: [5, 5],
            tension: 0.3
        }";
        
        echo implode(',', $datasets);
        ?>
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
</script>

<!-- CSS خاص بالطباعة المتقدمة -->
<style>
@media print {
    /* ضبط الصفحة - A4 */
    @page {
        size: A4;
        margin: 15mm 10mm;
        orientation: portrait;
    }
    
    /* إخفاء العناصر غير المرغوب فيها فقط */
    .no-print, form, .filter-form {
        display: none !important;
    }
    
    /* إظهار المحتوى الأساسي */
    body, .container, .max-w-7xl, .bg-white, .grid, .grid > div,
    h1, h2, h3, h4, table, thead, tbody, tr, td, th, div, span, p {
        display: block !important;
        visibility: visible !important;
    }
    
    /* CSS خاص بالطباعة المتقدمة */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    body {
        font-family: 'Arial', sans-serif !important;
        font-size: 11px !important;
        line-height: 1.3 !important;
        color: #000 !important;
        background: white !important;
        margin: 0 !important;
        padding: 10px !important;
    }
    
    .container, .max-w-7xl {
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    /* العناوين */
    h1 {
        font-size: 16px !important;
        font-weight: bold !important;
        text-align: center !important;
        margin: 0 0 15px 0 !important;
        border-bottom: 2px solid #2563eb !important;
        padding: 8px !important;
        background: linear-gradient(90deg, #3b82f6, #1d4ed8) !important;
        color: white !important;
        display: block !important;
    }
    
    h2 {
        font-size: 13px !important;
        font-weight: bold !important;
        margin: 12px 0 6px 0 !important;
        border-bottom: 1px solid #3b82f6 !important;
        padding-bottom: 3px !important;
        color: #1e40af !important;
        display: block !important;
    }
    
    h3 {
        font-size: 12px !important;
        font-weight: bold !important;
        margin: 8px 0 4px 0 !important;
        color: #1e40af !important;
        display: block !important;
    }
    
    /* معلومات المعلم */
    .teacher-info {
        background: #eff6ff !important;
        border: 1px solid #3b82f6 !important;
        padding: 8px !important;
        margin: 5px 0 !important;
        border-radius: 4px !important;
        display: block !important;
    }
    
    /* البطاقات والخلفيات */
    .bg-white {
        background: white !important;
        border: 1px solid #e5e7eb !important;
        margin: 4px 0 !important;
        padding: 6px !important;
        display: block !important;
    }
    
    /* الجداول */
    table {
        width: 100% !important;
        border-collapse: collapse !important;
        margin: 6px 0 !important;
        font-size: 10px !important;
        display: table !important;
    }
    
    thead {
        display: table-header-group !important;
    }
    
    tbody {
        display: table-row-group !important;
    }
    
    tr {
        display: table-row !important;
    }
    
    th, td {
        display: table-cell !important;
        border: 1px solid #374151 !important;
        padding: 3px !important;
        text-align: right !important;
    }
    
    th {
        background: #f3f4f6 !important;
        color: #1f2937 !important;
        font-weight: bold !important;
    }
    
    /* إخفاء الرسوم البيانية التفاعلية فقط */
    canvas {
        display: none !important;
    }
    
    /* إظهار النسخ المطبوعة للرسوم البيانية */
    .chart-print-version {
        display: block !important;
        visibility: visible !important;
        text-align: center !important;
        padding: 8px !important;
        background: #f8fafc !important;
        border: 1px solid #cbd5e1 !important;
        margin: 4px 0 !important;
        font-size: 9px !important;
    }
    
    /* تنسيق الشبكة */
    .grid {
        display: block !important;
    }
    
    .grid > div {
        margin-bottom: 6px !important;
        break-inside: avoid !important;
        display: block !important;
        width: 100% !important;
    }
    
    /* الألوان النصية */
    .text-blue-600 { color: #2563eb !important; }
    .text-green-600 { color: #16a34a !important; }
    .text-red-600 { color: #dc2626 !important; }
    .text-yellow-600 { color: #ca8a04 !important; }
    .text-purple-600 { color: #9333ea !important; }
    .text-gray-600 { color: #4b5563 !important; }
    
    /* تخفيض الهوامش */
    .mb-8, .mb-6, .mb-4 {
        margin-bottom: 4px !important;
    }
    
    .p-6, .p-4 {
        padding: 4px !important;
    }
    
    /* إزالة الظلال */
    .shadow-md, .shadow-lg, .shadow-sm {
        box-shadow: none !important;
    }
    
    /* التأكد من ظهور النصوص */
    strong, b {
        font-weight: bold !important;
        display: inline !important;
    }
    
    span, div {
        display: inline-block !important;
    }
    
    /* تنسيق خاص للمحتوى الداخلي */
    .teacher-info > div {
        display: block !important;
        margin: 2px 0 !important;
    }
}

/* للشاشة العادية - إخفاء النسخ المطبوعة */
@media screen {
    .chart-print-version {
        display: none !important;
    }
}
</style>

<script>
// دالة طباعة التقرير
function printReport() {
    // إزالة جميع كلاسات الإخفاء مؤقتاً
    const elementsToShow = document.querySelectorAll('*');
    elementsToShow.forEach(el => {
        if (el.style.display === 'none') {
            el.setAttribute('data-original-display', 'none');
            el.style.display = '';
        }
    });
    
    // إضافة معلومات إضافية للطباعة
    const printInfo = document.createElement('div');
    printInfo.className = 'print-info';
    printInfo.style.cssText = 'display: none;';
    printInfo.innerHTML = `
        <div style="text-align: center; margin: 15px 0; font-size: 9px; border-top: 1px solid #ccc; padding-top: 8px; page-break-inside: avoid;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>تم طباعة التقرير في: ${new Date().toLocaleDateString('ar-SA')} - ${new Date().toLocaleTimeString('ar-SA')}</div>
                <div>نظام زيارات المشرفين التربويين</div>
            </div>
        </div>
    `;
    
    document.body.appendChild(printInfo);
    
    // إظهار معلومات الطباعة فقط عند الطباعة
    const style = document.createElement('style');
    style.textContent = `
        @media print {
            .print-info { display: block !important; }
        }
    `;
    document.head.appendChild(style);
    
    // طباعة التقرير
    window.print();
    
    // إعادة الحالة الأصلية بعد الطباعة
    setTimeout(() => {
        if (printInfo && printInfo.parentNode) {
            printInfo.parentNode.removeChild(printInfo);
        }
        if (style && style.parentNode) {
            style.parentNode.removeChild(style);
        }
        
        // إعادة إخفاء العناصر التي كانت مخفية
        elementsToShow.forEach(el => {
            if (el.getAttribute('data-original-display') === 'none') {
                el.style.display = 'none';
                el.removeAttribute('data-original-display');
            }
        });
    }, 1000);
}

// دالة إنشاء PDF
function generatePDF() {
    // إشعار المستخدم
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed; top: 20px; right: 20px; z-index: 9999;
        background: #3b82f6; color: white; padding: 15px 20px;
        border-radius: 8px; font-size: 14px; font-weight: bold;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    notification.innerHTML = `
        <i class="fas fa-file-pdf" style="margin-left: 8px;"></i>
        اضغط Ctrl+P واختر "حفظ كـ PDF"
    `;
    notification.style.background = '#16a34a';
    document.body.appendChild(notification);
    
    // فتح نافذة الطباعة
    setTimeout(() => {
        printReport();
        
        // إزالة الإشعار
        setTimeout(() => {
            if (notification && notification.parentNode) {
                document.body.removeChild(notification);
            }
        }, 5000);
    }, 500);
}

// إضافة اختصارات لوحة المفاتيح
document.addEventListener('keydown', function(e) {
    // Ctrl+P للطباعة
    if (e.ctrlKey && e.key === 'p') {
        e.preventDefault();
        printReport();
    }
    
    // Ctrl+S لـ PDF
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        generatePDF();
    }
});
</script>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?> 