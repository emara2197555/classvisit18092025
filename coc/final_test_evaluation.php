<?php
session_start();

// إعداد جلسة المنسق
$_SESSION['user_id'] = 343;
$_SESSION['role_name'] = 'Subject Coordinator';
$_SESSION['subject_id'] = 3;
$_SESSION['school_id'] = 1;

require_once 'includes/db_connection.php';

echo "<h1>اختبار شامل لمشاكل evaluation_form.php</h1>";

echo "<h2>1. التحقق من أنواع الزوار والبيانات المرتبطة</h2>";

$visitor_types = query('SELECT id, name FROM visitor_types ORDER BY id');
foreach ($visitor_types as $type) {
    echo "<h3>نوع الزائر: {$type['name']} (ID: {$type['id']})</h3>";
    
    // اختبار API لكل نوع
    $_GET = [
        'visitor_type_id' => $type['id'],
        'subject_id' => 3,
        'school_id' => 1
    ];
    
    ob_start();
    
    // محاكاة API call
    $visitor_type_id = (int)$_GET['visitor_type_id'];
    $subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : null;
    $school_id = isset($_GET['school_id']) ? (int)$_GET['school_id'] : null;
    
    $current_user_role = $_SESSION['role_name'] ?? 'admin';
    $current_user_id = $_SESSION['user_id'] ?? null;
    
    try {
        // جلب نوع الزائر أولاً للتحقق من الوظيفة
        $visitor_type = query_row("SELECT name FROM visitor_types WHERE id = ?", [$visitor_type_id]);
        
        if (!$visitor_type) {
            throw new Exception("نوع الزائر غير موجود");
        }
        
        // تحديد الوظيفة المطلوبة بناءً على نوع الزائر
        $job_title = '';
        $additional_condition = '';
        $params = [];
        
        switch ($visitor_type['name']) {
            case 'مدير المدرسة':
            case 'مدير':
                $job_title = 'مدير';
                if ($school_id) {
                    $additional_condition = "AND school_id = ?";
                    $params[] = $school_id;
                }
                break;
            case 'نائب المدير للشؤون الأكاديمية':
            case 'النائب الأكاديمي':
                $job_title = 'النائب الأكاديمي';
                if ($school_id) {
                    $additional_condition = "AND school_id = ?";
                    $params[] = $school_id;
                }
                break;
            case 'منسق المادة':
                $job_title = 'منسق المادة';
                
                if ($subject_id) {
                    $sql = "
                        SELECT t.id, t.name 
                        FROM teachers t
                        JOIN teacher_subjects ts ON t.id = ts.teacher_id
                        WHERE t.job_title = ? AND ts.subject_id = ?";
                    
                    if ($school_id) {
                        $sql .= " AND t.school_id = ?";
                        $visitors = query($sql, [$job_title, $subject_id, $school_id]);
                    } else {
                        $visitors = query($sql, [$job_title, $subject_id]);
                    }
                    
                    echo "النتيجة: " . json_encode(['success' => true, 'visitors' => $visitors]) . "<br>";
                    echo "عدد النتائج: " . count($visitors) . "<br>";
                    foreach ($visitors as $visitor) {
                        echo "- {$visitor['name']} (ID: {$visitor['id']})<br>";
                    }
                    continue 2;
                }
                break;
            case 'موجه المادة':
                $job_title = 'موجه المادة';
                
                if ($subject_id) {
                    $sql = "
                        SELECT t.id, t.name 
                        FROM teachers t
                        JOIN teacher_subjects ts ON t.id = ts.teacher_id
                        WHERE t.job_title = ? AND ts.subject_id = ?";
                    
                    $visitors = query($sql, [$job_title, $subject_id]);
                    
                    echo "النتيجة: " . json_encode(['success' => true, 'visitors' => $visitors]) . "<br>";
                    echo "عدد النتائج: " . count($visitors) . "<br>";
                    foreach ($visitors as $visitor) {
                        echo "- {$visitor['name']} (ID: {$visitor['id']})<br>";
                    }
                    continue 2;
                }
                break;
            default:
                $job_title = $visitor_type['name'];
                break;
        }
        
        // جلب المعلمين بالوظيفة المحددة
        $sql = "SELECT id, name FROM teachers WHERE job_title = ? $additional_condition ORDER BY name";
        array_unshift($params, $job_title);
        $visitors = query($sql, $params);
        
        echo "النتيجة: " . json_encode(['success' => true, 'visitors' => $visitors]) . "<br>";
        echo "عدد النتائج: " . count($visitors) . "<br>";
        foreach ($visitors as $visitor) {
            echo "- {$visitor['name']} (ID: {$visitor['id']})<br>";
        }
        
    } catch (Exception $e) {
        echo "خطأ: " . $e->getMessage() . "<br>";
    }
    
    echo "<hr>";
}

echo "<h2>2. اختبار تحميل منسق المادة من get_subject_coordinator.php</h2>";

$coordinators_response = file_get_contents("http://localhost/classvisit/includes/get_subject_coordinator.php?subject_id=3&school_id=1");
echo "استجابة منسق المادة: <pre>$coordinators_response</pre>";

echo "<h2>✅ الآن يمكنك اختبار evaluation_form.php</h2>";
echo "<a href='evaluation_form.php' target='_blank' style='background: #3b82f6; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;'>فتح نموذج التقييم</a>";
?>
