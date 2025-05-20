<?php
// بدء التخزين المؤقت للمخرجات
ob_start();

// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// تعيين عنوان الصفحة
$page_title = 'تقرير أداء الشعبة الدراسية';
$current_page = 'section_report.php';

// تضمين ملف رأس الصفحة
require_once 'includes/header.php';

// التحقق من وجود معرف الشعبة
$section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
$grade_id = isset($_GET['grade_id']) ? (int)$_GET['grade_id'] : 0;

if (!$section_id || !$grade_id) {
    echo show_alert("يرجى تحديد الصف والشعبة لعرض التقرير", "error");
    require_once 'includes/footer.php';
    exit;
}

// تحديد العام الدراسي
$academic_year_id = isset($_GET['academic_year_id']) ? (int)$_GET['academic_year_id'] : 0;

// جلب العام الدراسي النشط إذا لم يتم تحديد عام
if (!$academic_year_id) {
    $active_year = get_active_academic_year();
    $academic_year_id = $active_year ? $active_year['id'] : 0;
    $academic_year_name = $active_year ? $active_year['name'] : '';
} else {
    $year = query_row("SELECT name FROM academic_years WHERE id = ?", [$academic_year_id]);
    $academic_year_name = $year ? $year['name'] : '';
}

// جلب معلومات الشعبة
$section = query_row("
    SELECT s.*, g.name as grade_name
    FROM sections s
    JOIN grades g ON s.grade_id = g.id
    WHERE s.id = ? AND g.id = ?", 
    [$section_id, $grade_id]
);

if (!$section) {
    echo show_alert("لم يتم العثور على الشعبة المطلوبة", "error");
    require_once 'includes/footer.php';
    exit;
}

// جلب الأعوام الدراسية للاختيار
$academic_years = query("SELECT id, name, is_active FROM academic_years ORDER BY is_active DESC, name DESC");

// جلب المواد الدراسية للشعبة
// نجلب كل المواد التي يوجد لها زيارات لهذه الشعبة
$subjects = query("
    SELECT DISTINCT s.id, s.name
    FROM subjects s
    JOIN visits v ON s.id = v.subject_id
    WHERE v.section_id = ? AND v.grade_id = ?
    ORDER BY s.name", 
    [$section_id, $grade_id]
);

// جلب تقييمات الشعبة حسب المواد الدراسية - نركز فقط على مجالي تنفيذ الدرس والإدارة الصفية

if ($academic_year_id > 0) {
    // استعلام مع فلتر العام الدراسي
    $sql = "
        SELECT 
            s.id AS subject_id,
            s.name AS subject_name,
            COUNT(DISTINCT v.id) AS visits_count,
            COUNT(DISTINCT v.teacher_id) AS teachers_count,
            
            -- متوسط تنفيذ الدرس (مجال رقم 2)
            (SELECT AVG(ve.score) * 25
             FROM visit_evaluations ve 
             JOIN visits vs ON ve.visit_id = vs.id
             JOIN evaluation_indicators ei ON ve.indicator_id = ei.id
             WHERE vs.section_id = ?
               AND vs.grade_id = ?
               AND vs.subject_id = s.id
               AND vs.academic_year_id = ?
               AND ei.domain_id = 2
               AND ve.score > 0) AS lesson_execution_avg,
               
            -- متوسط الإدارة الصفية (مجال رقم 3)
            (SELECT AVG(ve.score) * 25
             FROM visit_evaluations ve 
             JOIN visits vs ON ve.visit_id = vs.id
             JOIN evaluation_indicators ei ON ve.indicator_id = ei.id
             WHERE vs.section_id = ?
               AND vs.grade_id = ?
               AND vs.subject_id = s.id
               AND vs.academic_year_id = ?
               AND ei.domain_id = 3
               AND ve.score > 0) AS classroom_management_avg,
               
            -- المتوسط العام للمجالين
            (SELECT AVG(ve.score) * 25
             FROM visit_evaluations ve 
             JOIN visits vs ON ve.visit_id = vs.id
             JOIN evaluation_indicators ei ON ve.indicator_id = ei.id
             WHERE vs.section_id = ?
               AND vs.grade_id = ?
               AND vs.subject_id = s.id
               AND vs.academic_year_id = ?
               AND (ei.domain_id = 2 OR ei.domain_id = 3)
               AND ve.score > 0) AS overall_avg
        FROM 
            subjects s
        JOIN 
            visits v ON s.id = v.subject_id
        WHERE 
            v.section_id = ?
            AND v.grade_id = ?
            AND v.academic_year_id = ?
        GROUP BY 
            s.id, s.name
        ORDER BY 
            overall_avg DESC, s.name
    ";
    
    // تحضير المعلمات للاستعلام
    $query_params = [
        $section_id, $grade_id, $academic_year_id,  // تنفيذ الدرس
        $section_id, $grade_id, $academic_year_id,  // الإدارة الصفية
        $section_id, $grade_id, $academic_year_id,  // المتوسط العام للمجالين
        $section_id, $grade_id, $academic_year_id   // شرط WHERE الرئيسي
    ];
} else {
    // استعلام بدون فلتر العام الدراسي
    $sql = "
        SELECT 
            s.id AS subject_id,
            s.name AS subject_name,
            COUNT(DISTINCT v.id) AS visits_count,
            COUNT(DISTINCT v.teacher_id) AS teachers_count,
            
            -- متوسط تنفيذ الدرس (مجال رقم 2)
            (SELECT AVG(ve.score) * 25
             FROM visit_evaluations ve 
             JOIN visits vs ON ve.visit_id = vs.id
             JOIN evaluation_indicators ei ON ve.indicator_id = ei.id
             WHERE vs.section_id = ?
               AND vs.grade_id = ?
               AND vs.subject_id = s.id
               AND ei.domain_id = 2
               AND ve.score > 0) AS lesson_execution_avg,
               
            -- متوسط الإدارة الصفية (مجال رقم 3)
            (SELECT AVG(ve.score) * 25
             FROM visit_evaluations ve 
             JOIN visits vs ON ve.visit_id = vs.id
             JOIN evaluation_indicators ei ON ve.indicator_id = ei.id
             WHERE vs.section_id = ?
               AND vs.grade_id = ?
               AND vs.subject_id = s.id
               AND ei.domain_id = 3
               AND ve.score > 0) AS classroom_management_avg,
               
            -- المتوسط العام للمجالين
            (SELECT AVG(ve.score) * 25
             FROM visit_evaluations ve 
             JOIN visits vs ON ve.visit_id = vs.id
             JOIN evaluation_indicators ei ON ve.indicator_id = ei.id
             WHERE vs.section_id = ?
               AND vs.grade_id = ?
               AND vs.subject_id = s.id
               AND (ei.domain_id = 2 OR ei.domain_id = 3)
               AND ve.score > 0) AS overall_avg
        FROM 
            subjects s
        JOIN 
            visits v ON s.id = v.subject_id
        WHERE 
            v.section_id = ?
            AND v.grade_id = ?
        GROUP BY 
            s.id, s.name
        ORDER BY 
            overall_avg DESC, s.name
    ";
    
    // تحضير المعلمات للاستعلام - بدون معلمة العام الدراسي
    $query_params = [
        $section_id, $grade_id,  // تنفيذ الدرس
        $section_id, $grade_id,  // الإدارة الصفية
        $section_id, $grade_id,  // المتوسط العام للمجالين
        $section_id, $grade_id   // شرط WHERE الرئيسي
    ];
}

$subjects_data = query($sql, $query_params);

// حساب المتوسطات العامة لجميع المواد
$total_lesson_execution = 0;
$total_classroom_management = 0;
$total_overall = 0;

$valid_subjects_count = 0;
foreach ($subjects_data as $subject) {
    if ($subject['overall_avg'] !== null) {
        $valid_subjects_count++;
        $total_lesson_execution += $subject['lesson_execution_avg'] ?? 0;
        $total_classroom_management += $subject['classroom_management_avg'] ?? 0;
        $total_overall += $subject['overall_avg'] ?? 0;
    }
}

$avg_lesson_execution = $valid_subjects_count > 0 ? ($total_lesson_execution / $valid_subjects_count) : 0;
$avg_classroom_management = $valid_subjects_count > 0 ? ($total_classroom_management / $valid_subjects_count) : 0;
$avg_overall = $valid_subjects_count > 0 ? ($total_overall / $valid_subjects_count) : 0;

// حساب أفضل وأضعف أداء للمواد
$best_subject = $valid_subjects_count > 0 ? $subjects_data[0]['subject_name'] : '';
$worst_subject = $valid_subjects_count > 0 ? $subjects_data[count($subjects_data)-1]['subject_name'] : '';

// جلب أضعف المؤشرات أداءً في الشعبة (فقط من المجالين 2 و 3)
$weakest_query = "
    SELECT 
        i.id,
        i.name,
        AVG(ve.score) AS avg_score,
        AVG(ve.score) * 25 AS avg_percentage,
        COUNT(DISTINCT v.id) AS visits_count
    FROM 
        evaluation_indicators i
    JOIN 
        visit_evaluations ve ON ve.indicator_id = i.id
    JOIN 
        visits v ON ve.visit_id = v.id
    WHERE 
        v.section_id = ?
        AND v.grade_id = ?
        " . ($academic_year_id > 0 ? "AND v.academic_year_id = ?" : "") . "
        AND ve.score > 0
        AND (i.domain_id = 2 OR i.domain_id = 3)
    GROUP BY 
        i.id, i.name
    HAVING 
        AVG(ve.score) < 3
    ORDER BY 
        avg_score ASC
    LIMIT 5
";

$weakest_params = [$section_id, $grade_id];
if ($academic_year_id > 0) {
    $weakest_params[] = $academic_year_id;
}
$weakest_indicators = query($weakest_query, $weakest_params);

// جلب أقوى المؤشرات أداءً (فقط من المجالين 2 و 3)
$strongest_query = "
    SELECT 
        i.id,
        i.name,
        AVG(ve.score) AS avg_score,
        AVG(ve.score) * 25 AS avg_percentage,
        COUNT(DISTINCT v.id) AS visits_count
    FROM 
        evaluation_indicators i
    JOIN 
        visit_evaluations ve ON ve.indicator_id = i.id
    JOIN 
        visits v ON ve.visit_id = v.id
    WHERE 
        v.section_id = ?
        AND v.grade_id = ?
        " . ($academic_year_id > 0 ? "AND v.academic_year_id = ?" : "") . "
        AND ve.score > 0
        AND (i.domain_id = 2 OR i.domain_id = 3)
    GROUP BY 
        i.id, i.name
    HAVING 
        AVG(ve.score) >= 3
    ORDER BY 
        avg_score DESC
    LIMIT 5
";

$strongest_params = [$section_id, $grade_id];
if ($academic_year_id > 0) {
    $strongest_params[] = $academic_year_id;
}
$strongest_indicators = query($strongest_query, $strongest_params);

// جلب أكثر التوصيات تكراراً (فقط من المجالين 2 و 3)
$recommendations_query = "
    SELECT 
        r.text,
        COUNT(*) AS count
    FROM 
        visit_evaluations ve
    JOIN 
        visits v ON ve.visit_id = v.id
    JOIN 
        recommendations r ON ve.recommendation_id = r.id
    JOIN
        evaluation_indicators i ON ve.indicator_id = i.id
    WHERE 
        v.section_id = ?
        AND v.grade_id = ?
        " . ($academic_year_id > 0 ? "AND v.academic_year_id = ?" : "") . "
        AND ve.recommendation_id IS NOT NULL
        AND (i.domain_id = 2 OR i.domain_id = 3)
    GROUP BY 
        r.text
    ORDER BY 
        count DESC
    LIMIT 5
";

$recommendations_params = [$section_id, $grade_id];
if ($academic_year_id > 0) {
    $recommendations_params[] = $academic_year_id;
}
$common_recommendations = query($recommendations_query, $recommendations_params);

?>

<div class="mb-6">
    <h1 class="text-2xl font-bold mb-4">تقرير أداء الشعبة الدراسية</h1>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <!-- نموذج تحديد الفلاتر -->
        <form action="" method="get" class="mb-6">
            <input type="hidden" name="section_id" value="<?= $section_id ?>">
            <input type="hidden" name="grade_id" value="<?= $grade_id ?>">
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
        
        <!-- معلومات الشعبة -->
        <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <h2 class="text-xl font-bold mb-3">معلومات الشعبة</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <p class="font-semibold">الصف: <span class="font-normal"><?= htmlspecialchars($section['grade_name']) ?></span></p>
                    <p class="font-semibold">الشعبة: <span class="font-normal"><?= htmlspecialchars($section['name']) ?></span></p>
                </div>
                <div>
                    <p class="font-semibold">عدد المواد: <span class="font-normal"><?= count($subjects) ?></span></p>
                    <p class="font-semibold">عدد الزيارات الإجمالي: <span class="font-normal"><?= array_sum(array_column($subjects_data, 'visits_count')) ?></span></p>
                </div>
                <div>
                    <p class="font-semibold">المتوسط العام للأداء: <span class="font-normal"><?= number_format($avg_overall, 1) ?>%</span></p>
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
                    <h3 class="text-lg font-semibold mb-3">مقارنة أداء المواد</h3>
                    <canvas id="subjectsChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- تفاصيل أداء المواد -->
        <div class="mb-8">
            <h2 class="text-xl font-bold mb-4">تفاصيل أداء المواد الدراسية</h2>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المادة</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عدد الزيارات</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عدد المعلمين</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تنفيذ الدرس</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإدارة الصفية</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المتوسط العام</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($subjects_data)): ?>
                            <tr>
                                <td colspan="6" class="px-4 py-3 text-center text-gray-500">لا توجد بيانات</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($subjects_data as $subject): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2"><?= htmlspecialchars($subject['subject_name']) ?></td>
                                    <td class="px-4 py-2"><?= $subject['visits_count'] ?></td>
                                    <td class="px-4 py-2"><?= $subject['teachers_count'] ?></td>
                                    <td class="px-4 py-2"><?= $subject['lesson_execution_avg'] ? number_format($subject['lesson_execution_avg'], 1) . '%' : '-' ?></td>
                                    <td class="px-4 py-2"><?= $subject['classroom_management_avg'] ? number_format($subject['classroom_management_avg'], 1) . '%' : '-' ?></td>
                                    <td class="px-4 py-2 font-semibold">
                                        <?= $subject['overall_avg'] ? number_format($subject['overall_avg'], 1) . '%' : '-' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="bg-gray-100 font-semibold">
                                <td class="px-4 py-2">المتوسط العام</td>
                                <td colspan="2" class="px-4 py-2"></td>
                                <td class="px-4 py-2"><?= number_format($avg_lesson_execution, 1) ?>%</td>
                                <td class="px-4 py-2"><?= number_format($avg_classroom_management, 1) ?>%</td>
                                <td class="px-4 py-2"><?= number_format($avg_overall, 1) ?>%</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- المؤشرات والتوصيات -->
        <div class="mb-8">
            <h2 class="text-xl font-bold mb-4">نقاط القوة والضعف</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- أضعف المؤشرات -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-3 text-red-600">أضعف مجالات الأداء</h3>
                    
                    <?php if (empty($weakest_indicators)): ?>
                        <p class="text-gray-500">لا توجد بيانات كافية</p>
                    <?php else: ?>
                        <ul class="list-disc list-inside space-y-2">
                            <?php foreach ($weakest_indicators as $indicator): ?>
                                <li>
                                    <?= htmlspecialchars($indicator['name']) ?>
                                    <span class="text-gray-500">
                                        (<?= number_format($indicator['avg_percentage'], 1) ?>%)
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                
                <!-- أقوى المؤشرات -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-3 text-green-600">أقوى مجالات الأداء</h3>
                    
                    <?php if (empty($strongest_indicators)): ?>
                        <p class="text-gray-500">لا توجد بيانات كافية</p>
                    <?php else: ?>
                        <ul class="list-disc list-inside space-y-2">
                            <?php foreach ($strongest_indicators as $indicator): ?>
                                <li>
                                    <?= htmlspecialchars($indicator['name']) ?>
                                    <span class="text-gray-500">
                                        (<?= number_format($indicator['avg_percentage'], 1) ?>%)
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                
                <!-- أكثر التوصيات تكراراً -->
                <div class="bg-white p-4 rounded-lg border border-gray-200 md:col-span-2">
                    <h3 class="text-lg font-semibold mb-3">أكثر التوصيات شيوعاً</h3>
                    
                    <?php if (empty($common_recommendations)): ?>
                        <p class="text-gray-500">لا توجد توصيات متكررة</p>
                    <?php else: ?>
                        <ul class="list-decimal list-inside space-y-2">
                            <?php foreach ($common_recommendations as $recommendation): ?>
                                <li>
                                    <?= htmlspecialchars($recommendation['text']) ?>
                                    <span class="text-gray-500">
                                        (تكررت <?= $recommendation['count'] ?> مرات)
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- زر الطباعة -->
        <div class="mt-6 text-center">
            <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                <i class="bi bi-printer ml-2"></i> طباعة التقرير
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // مخطط متوسطات المجالات
    const domainsCtx = document.getElementById('domainsChart').getContext('2d');
    new Chart(domainsCtx, {
        type: 'bar',
        data: {
            labels: ['تنفيذ الدرس', 'الإدارة الصفية'],
            datasets: [{
                label: 'متوسط الأداء (%)',
                data: [
                    <?= number_format($avg_lesson_execution, 1) ?>,
                    <?= number_format($avg_classroom_management, 1) ?>
                ],
                backgroundColor: [
                    'rgba(75, 192, 192, 0.5)',
                    'rgba(255, 159, 64, 0.5)'
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: {
                        display: true
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
    
    // مخطط مقارنة المواد - تم تعديله ليكون طوليًا
    const subjectsCtx = document.getElementById('subjectsChart').getContext('2d');
    new Chart(subjectsCtx, {
        type: 'bar',
        data: {
            labels: [
                <?php foreach ($subjects_data as $subject): ?>
                    '<?= htmlspecialchars($subject['subject_name']) ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                label: 'متوسط الأداء (%)',
                data: [
                    <?php foreach ($subjects_data as $subject): ?>
                        <?= $subject['overall_avg'] ? number_format($subject['overall_avg'], 1) : 0 ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: 'rgba(75, 192, 192, 0.5)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y', // هذا يجعل المخطط طوليًا بدلاً من عرضي
            scales: {
                x: {
                    beginAtZero: true,
                    max: 100,
                    grid: {
                        display: true
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
</script>

<?php require_once 'includes/footer.php'; ?> 