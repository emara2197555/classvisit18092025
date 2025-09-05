<?php
// بدء التخزين المؤقت للمخرجات - سيحل مشكلة Headers already sent
ob_start();

// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// تعيين عنوان الصفحة
$page_title = 'إدارة التوصيات';

// معالجة النموذج إذا تم تقديمه
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_recommendation'])) {
            // إضافة توصية جديدة
            $indicator_id = $_POST['indicator_id'] ?? null;
            $text = $_POST['text'] ?? '';
            $sort_order = $_POST['sort_order'] ?? 0;
            
            // التحقق من وجود البيانات الأساسية
            if (!$indicator_id || !$text) {
                throw new Exception("الرجاء إدخال النص والمؤشر للتوصية");
            }
            
            // إضافة التوصية إلى قاعدة البيانات
            $sql = "INSERT INTO recommendations (indicator_id, text, sort_order, created_at, updated_at) 
                    VALUES (?, ?, ?, NOW(), NOW())";
            execute($sql, [$indicator_id, $text, $sort_order]);
            
            $_SESSION['success_message'] = "تمت إضافة التوصية بنجاح";
            header('Location: add_recommendations.php');
            exit;
        }
        elseif (isset($_POST['delete_recommendation'])) {
            // حذف توصية
            $recommendation_id = $_POST['recommendation_id'] ?? null;
            
            if (!$recommendation_id) {
                throw new Exception("معرف التوصية غير صحيح");
            }
            
            // حذف التوصية من قاعدة البيانات
            $sql = "DELETE FROM recommendations WHERE id = ?";
            execute($sql, [$recommendation_id]);
            
            $_SESSION['success_message'] = "تم حذف التوصية بنجاح";
            header('Location: add_recommendations.php');
            exit;
        }
    } catch (Exception $e) {
        $error_message = "حدث خطأ: " . $e->getMessage();
    }
}

// جلب المؤشرات من قاعدة البيانات
try {
    $domains = query("SELECT * FROM evaluation_domains ORDER BY id");
    $indicators = query("SELECT i.*, d.name as domain_name 
                        FROM evaluation_indicators i 
                        JOIN evaluation_domains d ON i.domain_id = d.id 
                        ORDER BY d.id, i.id");
} catch (PDOException $e) {
    $error_message = "حدث خطأ أثناء جلب البيانات: " . $e->getMessage();
}

// تضمين ملف رأس الصفحة
require_once 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">إدارة التوصيات</h1>
    
    <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border-r-4 border-red-500 text-red-700 p-4 mb-6 rounded-md">
            <?= $error_message ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-100 border-r-4 border-green-500 text-green-700 p-4 mb-6 rounded-md">
            <?= $_SESSION['success_message'] ?>
            <?php unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    
    <!-- نموذج إضافة توصية جديدة -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-bold mb-4">إضافة توصية جديدة</h2>
        <form action="add_recommendations.php" method="post">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block mb-2 font-semibold">المؤشر:</label>
                    <select name="indicator_id" class="w-full border p-2 rounded" required>
                        <option value="">اختر المؤشر...</option>
                        <?php foreach ($indicators as $indicator): ?>
                            <option value="<?= $indicator['id'] ?>"><?= htmlspecialchars($indicator['name']) ?> (<?= htmlspecialchars($indicator['domain_name']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block mb-2 font-semibold">ترتيب العرض:</label>
                    <input type="number" name="sort_order" value="0" min="0" class="w-full border p-2 rounded">
                </div>
            </div>
            <div class="mb-4">
                <label class="block mb-2 font-semibold">نص التوصية:</label>
                <textarea name="text" rows="3" class="w-full border p-2 rounded" required></textarea>
            </div>
            <button type="submit" name="add_recommendation" class="bg-primary-600 text-white px-4 py-2 rounded hover:bg-primary-700">إضافة التوصية</button>
        </form>
    </div>
    
    <!-- عرض التوصيات الحالية مصنفة حسب المجال والمؤشر -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold mb-4">التوصيات الحالية</h2>
        
        <?php foreach ($domains as $domain): ?>
            <div class="mb-6">
                <h3 class="text-lg font-bold bg-gray-100 p-2 rounded"><?= htmlspecialchars($domain['name']) ?></h3>
                
                <?php 
                // جلب مؤشرات هذا المجال
                $domain_indicators = [];
                foreach ($indicators as $indicator) {
                    if ($indicator['domain_id'] == $domain['id']) {
                        $domain_indicators[] = $indicator;
                    }
                }
                
                // عرض المؤشرات
                foreach ($domain_indicators as $indicator): 
                    // جلب التوصيات لهذا المؤشر
                    $recommendations = get_recommendations_by_indicator($indicator['id']);
                ?>
                    <div class="mt-4 mb-6 border-r-2 border-primary-500 pr-4">
                        <h4 class="font-semibold"><?= htmlspecialchars($indicator['name']) ?></h4>
                        
                        <?php if (count($recommendations) > 0): ?>
                            <div class="mt-2 bg-gray-50 p-3 rounded">
                                <table class="w-full">
                                    <thead>
                                        <tr class="border-b">
                                            <th class="text-right py-2">التوصية</th>
                                            <th class="text-center py-2 w-24">ترتيب العرض</th>
                                            <th class="text-center py-2 w-24">إجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recommendations as $rec): ?>
                                            <tr class="border-b">
                                                <td class="py-2"><?= htmlspecialchars($rec['text']) ?></td>
                                                <td class="text-center py-2"><?= $rec['sort_order'] ?? 0 ?></td>
                                                <td class="text-center py-2">
                                                    <form action="add_recommendations.php" method="post" class="inline">
                                                        <input type="hidden" name="recommendation_id" value="<?= $rec['id'] ?>">
                                                        <button type="submit" name="delete_recommendation" class="text-red-500 hover:text-red-700" onclick="return confirm('هل أنت متأكد من حذف هذه التوصية؟')">
                                                            <i class="fas fa-trash"></i> حذف
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-500 mt-2">لا توجد توصيات لهذا المؤشر</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
// تضمين ملف ذيل الصفحة
require_once 'includes/footer.php';
?> 