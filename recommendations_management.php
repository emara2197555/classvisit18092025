<?php
// بدء التخزين المؤقت للمخرجات - سيحل مشكلة Headers already sent
ob_start();

// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';
require_once 'includes/auth_functions.php';

// حماية الصفحة - للمديرين فقط
protect_page(['Admin', 'Director', 'Academic Deputy']);

// بدء الجلسة إذا لم تكن مبدوءة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// تعيين عنوان الصفحة
$page_title = 'إدارة التوصيات';

// معالجة النموذج إذا تم تقديمه
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_recommendation'])) {
            // إضافة توصية جديدة
            $indicator_id = $_POST['indicator_id'] ?? null;
            $text = trim($_POST['text'] ?? '');
            $text_en = trim($_POST['text_en'] ?? '');
            $sort_order = $_POST['sort_order'] ?? 0;
            
            // التحقق من وجود البيانات الأساسية
            if (!$indicator_id || !$text) {
                throw new Exception("الرجاء إدخال النص والمؤشر للتوصية");
            }
            
            // التحقق من عدم وجود توصية مكررة
            $existing = query_row("SELECT id FROM recommendations WHERE indicator_id = ? AND text = ?", [$indicator_id, $text]);
            if ($existing) {
                throw new Exception("هذه التوصية موجودة مسبقاً لنفس المؤشر");
            }
            
            // إضافة التوصية إلى قاعدة البيانات
            $sql = "INSERT INTO recommendations (indicator_id, text, text_en, sort_order, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, NOW(), NOW())";
            execute($sql, [$indicator_id, $text, $text_en, $sort_order]);
            
            $_SESSION['success_message'] = "تمت إضافة التوصية بنجاح";
            header('Location: recommendations_management.php');
            exit;
        }
        elseif (isset($_POST['edit_recommendation'])) {
            // تعديل توصية موجودة
            $recommendation_id = $_POST['recommendation_id'] ?? null;
            $indicator_id = $_POST['indicator_id'] ?? null;
            $text = trim($_POST['text'] ?? '');
            $text_en = trim($_POST['text_en'] ?? '');
            $sort_order = $_POST['sort_order'] ?? 0;
            
            if (!$recommendation_id || !$indicator_id || !$text) {
                throw new Exception("الرجاء إدخال جميع البيانات المطلوبة");
            }
            
            // التحقق من عدم وجود توصية مكررة (عدا التوصية الحالية)
            $existing = query_row("SELECT id FROM recommendations WHERE indicator_id = ? AND text = ? AND id != ?", [$indicator_id, $text, $recommendation_id]);
            if ($existing) {
                throw new Exception("هذه التوصية موجودة مسبقاً لنفس المؤشر");
            }
            
            // تحديث التوصية في قاعدة البيانات
            $sql = "UPDATE recommendations 
                    SET indicator_id = ?, text = ?, text_en = ?, sort_order = ?, updated_at = NOW() 
                    WHERE id = ?";
            execute($sql, [$indicator_id, $text, $text_en, $sort_order, $recommendation_id]);
            
            $_SESSION['success_message'] = "تم تعديل التوصية بنجاح";
            header('Location: recommendations_management.php');
            exit;
        }
        elseif (isset($_POST['delete_recommendation'])) {
            // حذف توصية
            $recommendation_id = $_POST['recommendation_id'] ?? null;
            
            if (!$recommendation_id) {
                throw new Exception("معرف التوصية غير صحيح");
            }
            
            // التحقق من أن التوصية ليست مستخدمة في أي زيارة
            $usage_check = query_row("SELECT COUNT(*) as count FROM visit_evaluations WHERE recommendation_id = ?", [$recommendation_id]);
            if ($usage_check['count'] > 0) {
                throw new Exception("لا يمكن حذف هذه التوصية لأنها مستخدمة في " . $usage_check['count'] . " زيارة");
            }
            
            // حذف التوصية من قاعدة البيانات
            $sql = "DELETE FROM recommendations WHERE id = ?";
            execute($sql, [$recommendation_id]);
            
            $_SESSION['success_message'] = "تم حذف التوصية بنجاح";
            header('Location: recommendations_management.php');
            exit;
        }
    } catch (Exception $e) {
        $error_message = "حدث خطأ: " . $e->getMessage();
    }
}

// جلب المؤشرات والمجالات من قاعدة البيانات
try {
    $domains = query("SELECT * FROM evaluation_domains ORDER BY sort_order, id");
    $indicators = query("SELECT i.*, d.name as domain_name 
                        FROM evaluation_indicators i 
                        LEFT JOIN evaluation_domains d ON i.domain_id = d.id 
                        ORDER BY COALESCE(d.sort_order, 999), d.id, i.sort_order, i.id");
    
    // جلب جميع التوصيات (مع حماية من التكرار)
    $all_recommendations_raw = query("
        SELECT r.*, i.name as indicator_name, d.name as domain_name, d.id as domain_id
        FROM recommendations r
        LEFT JOIN evaluation_indicators i ON r.indicator_id = i.id
        LEFT JOIN evaluation_domains d ON i.domain_id = d.id
        GROUP BY r.id
        ORDER BY COALESCE(d.sort_order, 999), COALESCE(i.sort_order, 999), COALESCE(r.sort_order, 999), r.id
    ");
    
    // فلترة إضافية لضمان عدم وجود تكرار في الواجهة
    $all_recommendations = [];
    $seen_combinations = [];
    
    foreach ($all_recommendations_raw as $rec) {
        $key = $rec['indicator_id'] . '_' . md5($rec['text']);
        if (!isset($seen_combinations[$key])) {
            $seen_combinations[$key] = true;
            $all_recommendations[] = $rec;
        }
    }
    
} catch (PDOException $e) {
    $error_message = "حدث خطأ أثناء جلب البيانات: " . $e->getMessage();
    // تعيين قيم افتراضية لمنع الأخطاء
    $domains = [];
    $indicators = [];
    $all_recommendations = [];
}

// تضمين ملف رأس الصفحة
require_once 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">إدارة التوصيات</h1>
        <button onclick="showAddModal()" class="bg-primary-600 text-white px-6 py-2 rounded-lg hover:bg-primary-700 transition duration-300">
            <i class="fas fa-plus mr-2"></i>إضافة توصية جديدة
        </button>
    </div>
    
    <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border-r-4 border-red-500 text-red-700 p-4 mb-6 rounded-md">
            <i class="fas fa-exclamation-triangle mr-2"></i><?= $error_message ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-100 border-r-4 border-green-500 text-green-700 p-4 mb-6 rounded-md">
            <i class="fas fa-check-circle mr-2"></i><?= $_SESSION['success_message'] ?>
            <?php unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    
    <!-- إحصائيات سريعة -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="text-primary-600 text-3xl mr-4">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">إجمالي التوصيات</p>
                    <p class="text-2xl font-bold"><?= count($all_recommendations) ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="text-blue-600 text-3xl mr-4">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">المجالات</p>
                    <p class="text-2xl font-bold"><?= count($domains) ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="text-green-600 text-3xl mr-4">
                    <i class="fas fa-tasks"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">المؤشرات</p>
                    <p class="text-2xl font-bold"><?= count($indicators) ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- فلترة التوصيات -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h3 class="text-lg font-semibold mb-4">فلترة التوصيات</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">المجال</label>
                <select id="domain_filter" class="w-full border border-gray-300 rounded-md px-3 py-2">
                    <option value="">جميع المجالات</option>
                    <?php foreach ($domains as $domain): ?>
                        <option value="<?= $domain['id'] ?>"><?= htmlspecialchars($domain['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">المؤشر</label>
                <select id="indicator_filter" class="w-full border border-gray-300 rounded-md px-3 py-2">
                    <option value="">جميع المؤشرات</option>
                    <?php foreach ($indicators as $indicator): ?>
                        <option value="<?= $indicator['id'] ?>" data-domain="<?= $indicator['domain_id'] ?>">
                            <?= htmlspecialchars($indicator['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">البحث في النص</label>
                <input type="text" id="text_search" placeholder="ابحث في نص التوصيات..." 
                       class="w-full border border-gray-300 rounded-md px-3 py-2">
            </div>
        </div>
    </div>
    
    <!-- عرض التوصيات -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b">
            <h2 class="text-xl font-semibold text-gray-800">قائمة التوصيات</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200" id="recommendations_table">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المجال</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المؤشر</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نص التوصية (عربي)</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نص التوصية (إنجليزي)</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">ترتيب العرض</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($all_recommendations as $rec): ?>
                        <tr class="recommendation-row hover:bg-gray-50" 
                            data-domain-id="<?= $rec['domain_id'] ?>" 
                            data-indicator-id="<?= $rec['indicator_id'] ?>">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?= htmlspecialchars($rec['domain_name']) ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?= htmlspecialchars($rec['indicator_name']) ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 recommendation-text">
                                <?= htmlspecialchars($rec['text']) ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 recommendation-text-en">
                                <?= htmlspecialchars($rec['text_en'] ?? '') ?>
                                <?php if (empty($rec['text_en'])): ?>
                                    <span class="text-gray-400 italic">غير متوفر</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                <?= $rec['sort_order'] ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <button onclick="showEditModal(<?= htmlspecialchars(json_encode($rec)) ?>)" 
                                        class="text-indigo-600 hover:text-indigo-900 mr-3">
                                    <i class="fas fa-edit"></i> تعديل
                                </button>
                                <button onclick="confirmDelete(<?= $rec['id'] ?>, '<?= htmlspecialchars($rec['text'], ENT_QUOTES) ?>')" 
                                        class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i> حذف
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal إضافة/تعديل التوصية -->
<div id="recommendation_modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900" id="modal_title">إضافة توصية جديدة</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="recommendation_form" method="post">
                <input type="hidden" id="recommendation_id" name="recommendation_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">المجال</label>
                    <select id="modal_domain" class="w-full border border-gray-300 rounded-md px-3 py-2" onchange="updateModalIndicators()">
                        <option value="">اختر المجال...</option>
                        <?php foreach ($domains as $domain): ?>
                            <option value="<?= $domain['id'] ?>"><?= htmlspecialchars($domain['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">المؤشر *</label>
                    <select id="modal_indicator" name="indicator_id" class="w-full border border-gray-300 rounded-md px-3 py-2" required>
                        <option value="">اختر المؤشر...</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">نص التوصية (عربي) *</label>
                    <textarea id="modal_text" name="text" rows="4" 
                              class="w-full border border-gray-300 rounded-md px-3 py-2" 
                              placeholder="أدخل نص التوصية باللغة العربية..." required></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">نص التوصية (إنجليزي)</label>
                    <textarea id="modal_text_en" name="text_en" rows="4" 
                              class="w-full border border-gray-300 rounded-md px-3 py-2" 
                              placeholder="Enter recommendation text in English..."></textarea>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">ترتيب العرض</label>
                    <input type="number" id="modal_sort_order" name="sort_order" value="0" min="0" 
                           class="w-full border border-gray-300 rounded-md px-3 py-2">
                </div>
                
                <div class="flex justify-end space-x-3 space-x-reverse">
                    <button type="button" onclick="closeModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        إلغاء
                    </button>
                    <button type="submit" id="submit_button" name="add_recommendation"
                            class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700">
                        إضافة التوصية
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Form حذف التوصية (مخفي) -->
<form id="delete_form" method="post" class="hidden">
    <input type="hidden" id="delete_recommendation_id" name="recommendation_id">
    <input type="hidden" name="delete_recommendation" value="1">
</form>

<script>
// بيانات المؤشرات لاستخدامها في JavaScript
const indicators = <?= json_encode($indicators) ?>;

// فلترة المؤشرات حسب المجال
function updateModalIndicators() {
    const domainSelect = document.getElementById('modal_domain');
    const indicatorSelect = document.getElementById('modal_indicator');
    const selectedDomain = domainSelect.value;
    
    // مسح المؤشرات الحالية
    indicatorSelect.innerHTML = '<option value="">اختر المؤشر...</option>';
    
    // إضافة المؤشرات المطابقة للمجال المختار
    indicators.forEach(function(indicator) {
        if (!selectedDomain || indicator.domain_id == selectedDomain) {
            const option = document.createElement('option');
            option.value = indicator.id;
            option.textContent = indicator.name;
            indicatorSelect.appendChild(option);
        }
    });
}

// عرض modal الإضافة
function showAddModal() {
    document.getElementById('modal_title').textContent = 'إضافة توصية جديدة';
    document.getElementById('recommendation_form').reset();
    document.getElementById('recommendation_id').value = '';
    document.getElementById('submit_button').name = 'add_recommendation';
    document.getElementById('submit_button').textContent = 'إضافة التوصية';
    updateModalIndicators();
    document.getElementById('recommendation_modal').classList.remove('hidden');
}

// عرض modal التعديل
function showEditModal(recommendation) {
    document.getElementById('modal_title').textContent = 'تعديل التوصية';
    document.getElementById('recommendation_id').value = recommendation.id;
    document.getElementById('modal_domain').value = recommendation.domain_id;
    updateModalIndicators();
    document.getElementById('modal_indicator').value = recommendation.indicator_id;
    document.getElementById('modal_text').value = recommendation.text;
    document.getElementById('modal_text_en').value = recommendation.text_en || '';
    document.getElementById('modal_sort_order').value = recommendation.sort_order;
    document.getElementById('submit_button').name = 'edit_recommendation';
    document.getElementById('submit_button').textContent = 'حفظ التعديل';
    document.getElementById('recommendation_modal').classList.remove('hidden');
}

// إغلاق Modal
function closeModal() {
    document.getElementById('recommendation_modal').classList.add('hidden');
}

// تأكيد الحذف
function confirmDelete(id, text) {
    if (confirm('هل أنت متأكد من حذف التوصية التالية؟\n\n"' + text + '"')) {
        document.getElementById('delete_recommendation_id').value = id;
        document.getElementById('delete_form').submit();
    }
}

// فلترة التوصيات
function filterRecommendations() {
    const domainFilter = document.getElementById('domain_filter').value;
    const indicatorFilter = document.getElementById('indicator_filter').value;
    const textSearch = document.getElementById('text_search').value.toLowerCase();
    const rows = document.querySelectorAll('.recommendation-row');
    
    rows.forEach(function(row) {
        let show = true;
        
        // فلترة المجال
        if (domainFilter && row.dataset.domainId != domainFilter) {
            show = false;
        }
        
        // فلترة المؤشر
        if (indicatorFilter && row.dataset.indicatorId != indicatorFilter) {
            show = false;
        }
        
        // فلترة النص
        if (textSearch) {
            const text = row.querySelector('.recommendation-text').textContent.toLowerCase();
            if (!text.includes(textSearch)) {
                show = false;
            }
        }
        
        row.style.display = show ? '' : 'none';
    });
}

// ربط أحداث الفلترة
document.getElementById('domain_filter').addEventListener('change', function() {
    // تحديث فلتر المؤشرات
    const indicatorFilter = document.getElementById('indicator_filter');
    const selectedDomain = this.value;
    
    // حفظ المؤشر المختار حالياً
    const currentIndicator = indicatorFilter.value;
    
    // مسح المؤشرات
    indicatorFilter.innerHTML = '<option value="">جميع المؤشرات</option>';
    
    // إضافة المؤشرات المطابقة
    indicators.forEach(function(indicator) {
        if (!selectedDomain || indicator.domain_id == selectedDomain) {
            const option = document.createElement('option');
            option.value = indicator.id;
            option.textContent = indicator.name;
            option.setAttribute('data-domain', indicator.domain_id);
            indicatorFilter.appendChild(option);
        }
    });
    
    // إعادة تعيين المؤشر إذا كان لا يزال متاحاً
    if (currentIndicator) {
        const option = indicatorFilter.querySelector(`option[value="${currentIndicator}"]`);
        if (option) {
            indicatorFilter.value = currentIndicator;
        }
    }
    
    filterRecommendations();
});

document.getElementById('indicator_filter').addEventListener('change', filterRecommendations);
document.getElementById('text_search').addEventListener('input', filterRecommendations);

// إغلاق Modal عند النقر خارجه
window.onclick = function(event) {
    const modal = document.getElementById('recommendation_modal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?>
