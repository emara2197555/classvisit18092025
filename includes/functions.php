<?php
/**
 * ملف الوظائف المشتركة للنظام
 * 
 * يحتوي على الوظائف المساعدة المستخدمة في أجزاء مختلفة من النظام
 */

/**
 * دالة لعرض رسالة تنبيه
 *
 * @param string $message نص الرسالة
 * @param string $type نوع الرسالة (نجاح، خطأ، تحذير، معلومات)
 * @return string HTML للرسالة
 */
function show_alert($message, $type = 'info') {
    $class = 'bg-blue-100 border-blue-500 text-blue-700';
    
    if ($type === 'success') {
        $class = 'bg-green-100 border-green-500 text-green-700';
    } elseif ($type === 'error') {
        $class = 'bg-red-100 border-red-500 text-red-700';
    } elseif ($type === 'warning') {
        $class = 'bg-yellow-100 border-yellow-500 text-yellow-700';
    }
    
    return "<div class='{$class} px-4 py-3 rounded relative mb-4 border-r-4' role='alert'>
                <span class='block sm:inline'>{$message}</span>
            </div>";
}

/**
 * دالة للتحقق من وجود قيمة فارغة
 *
 * @param mixed $value القيمة المراد التحقق منها
 * @return bool هل القيمة فارغة أم لا
 */
function is_empty($value) {
    return empty($value) && $value !== '0' && $value !== 0;
}

/**
 * دالة للتحويل من التنسيق العربي للأرقام إلى التنسيق اللاتيني
 *
 * @param string $string النص المحتوي على أرقام عربية
 * @return string النص بعد تبديل الأرقام العربية بأرقام لاتينية
 */
function ar_to_en($string) {
    $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    $english = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
    return str_replace($arabic, $english, $string);
}

/**
 * دالة لتنسيق التاريخ بالتنسيق العربي
 *
 * @param string $date التاريخ بالتنسيق الإنجليزي (YYYY-MM-DD)
 * @return string التاريخ بالتنسيق العربي
 */
function format_date_ar($date) {
    if (empty($date)) return '';
    
    $date_obj = new DateTime($date);
    $months = [
        'يناير', 'فبراير', 'مارس', 'إبريل', 'مايو', 'يونيو',
        'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
    ];
    
    $day = $date_obj->format('j');
    $month = $months[$date_obj->format('n') - 1];
    $year = $date_obj->format('Y');
    
    return "{$day} {$month} {$year}";
}

/**
 * دالة للحصول على مؤشرات التقييم حسب المجال
 *
 * @param int $domain_id معرف المجال
 * @return array مصفوفة تحتوي على مؤشرات التقييم
 */
function get_indicators_by_domain($domain_id) {
    $sql = "SELECT * FROM evaluation_indicators WHERE domain_id = ? ORDER BY id ASC";
    return query($sql, [$domain_id]);
}

/**
 * دالة للحصول على التوصيات حسب المؤشر
 * 
 * @param int $indicator_id معرف المؤشر
 * @return array مصفوفة التوصيات
 */
function get_recommendations_by_indicator($indicator_id) {
    $indicator_id = (int)$indicator_id;
    
    // جلب التوصيات المرتبطة بمؤشر معين
    $sql = "SELECT id, text, sort_order FROM recommendations
            WHERE indicator_id = ?
            ORDER BY sort_order, text";
    
    return query($sql, [$indicator_id]);
}

/**
 * دالة للحصول على تقدير التقييم بناءً على المتوسط
 *
 * @param float $average متوسط الدرجات
 * @return string التقدير المقابل للمتوسط
 */
function get_grade($average) {
    // تحويل المتوسط إلى نسبة مئوية (من 3 إلى 100%)
    $percentage = ($average / 3) * 100;
    
    if ($percentage >= 90) return 'ممتاز';
    if ($percentage >= 80) return 'جيد جداً';
    if ($percentage >= 65) return 'جيد';
    if ($percentage >= 50) return 'مقبول';
    return 'يحتاج إلى تحسين';
}

/**
 * دالة لتحويل قيمة لوجيكية إلى نعم/لا
 *
 * @param bool $value القيمة المنطقية
 * @return string نعم أو لا
 */
function bool_to_ar($value) {
    return $value ? 'نعم' : 'لا';
}

/**
 * دالة للحصول على اسم المدرسة من معرفها
 *
 * @param int $school_id معرف المدرسة
 * @return string اسم المدرسة
 */
function get_school_name($school_id) {
    $sql = "SELECT name FROM schools WHERE id = ?";
    $result = query_row($sql, [$school_id]);
    return $result ? $result['name'] : '';
}

/**
 * دالة للحصول على اسم المعلم من معرفه
 *
 * @param int $teacher_id معرف المعلم
 * @return string اسم المعلم
 */
function get_teacher_name($teacher_id) {
    $sql = "SELECT name FROM teachers WHERE id = ?";
    $result = query_row($sql, [$teacher_id]);
    return $result ? $result['name'] : '';
}

/**
 * دالة للحصول على اسم المادة من معرفها
 *
 * @param int $subject_id معرف المادة
 * @return string اسم المادة
 */
function get_subject_name($subject_id) {
    $sql = "SELECT name FROM subjects WHERE id = ?";
    $result = query_row($sql, [$subject_id]);
    return $result ? $result['name'] : '';
}

/**
 * دالة للحصول على اسم نوع الزائر من معرفه
 *
 * @param int $visitor_type_id معرف نوع الزائر
 * @return string اسم نوع الزائر
 */
function get_visitor_type_name($visitor_type_id) {
    $sql = "SELECT name FROM visitor_types WHERE id = ?";
    $result = query_row($sql, [$visitor_type_id]);
    return $result ? $result['name'] : '';
}

/**
 * دالة للحصول على اسم المرحلة التعليمية من معرفها
 *
 * @param int $level_id معرف المرحلة التعليمية
 * @return string اسم المرحلة
 */
function get_level_name($level_id) {
    $sql = "SELECT name FROM educational_levels WHERE id = ?";
    $result = query_row($sql, [$level_id]);
    return $result ? $result['name'] : '';
}

/**
 * دالة للحصول على اسم الصف من معرفه
 *
 * @param int $grade_id معرف الصف
 * @return string اسم الصف
 */
function get_grade_name($grade_id) {
    $sql = "SELECT name FROM grades WHERE id = ?";
    $result = query_row($sql, [$grade_id]);
    return $result ? $result['name'] : '';
}

/**
 * دالة للحصول على اسم الشعبة من معرفها
 *
 * @param int $section_id معرف الشعبة
 * @return string اسم الشعبة
 */
function get_section_name($section_id) {
    $sql = "SELECT name FROM sections WHERE id = ?";
    $result = query_row($sql, [$section_id]);
    return $result ? $result['name'] : '';
}

/**
 * دالة لحساب متوسط درجات المؤشرات للمعلم حسب نوع الزائر
 * 
 * @param int $teacher_id معرف المعلم
 * @param int $visitor_type_id معرف نوع الزائر (اختياري)
 * @param string $semester الفصل الدراسي (اختياري)
 * @return array مصفوفة بمتوسط درجات المؤشرات
 */
function get_teacher_indicators_avg_by_visitor($teacher_id, $visitor_type_id = null, $semester = null) {
    $params = [$teacher_id];
    $visitor_condition = '';
    $semester_condition = '';
    
    if ($visitor_type_id) {
        $visitor_condition = "AND v.visitor_type_id = ?";
        $params[] = $visitor_type_id;
    }
    
    if ($semester) {
        if ($semester == 'first') {
            $semester_condition = "AND (MONTH(v.visit_date) BETWEEN 9 AND 12 OR MONTH(v.visit_date) BETWEEN 1 AND 2)";
        } else if ($semester == 'second') {
            $semester_condition = "AND MONTH(v.visit_date) BETWEEN 3 AND 8";
        }
    }
    
    $sql = "
        SELECT 
            ei.id AS indicator_id,
            ei.name AS indicator_name,
            AVG(ve.score) AS avg_score,
            (AVG(ve.score) * (100/3)) AS percentage_score
        FROM 
            visit_evaluations ve
        JOIN 
            visits v ON ve.visit_id = v.id
        JOIN 
            evaluation_indicators ei ON ve.indicator_id = ei.id
        WHERE 
            v.teacher_id = ? 
            {$visitor_condition}
            {$semester_condition}
            AND ve.score IS NOT NULL -- استثناء المؤشرات غير المقاسة
        GROUP BY 
            ei.id, ei.name
        ORDER BY 
            ei.id
    ";
    
    return query($sql, $params);
}

/**
 * دالة لاستخراج أضعف المؤشرات أداءً لمعلم
 * 
 * @param int $teacher_id معرف المعلم
 * @param float $threshold_score عتبة الدرجة المقبولة (الافتراضي 2.5)
 * @param int $limit عدد المؤشرات المراد استرجاعها (الافتراضي 5)
 * @return array مصفوفة بأضعف المؤشرات
 */
function get_teacher_weakest_indicators($teacher_id, $threshold_score = 2.5, $limit = 5) {
    $sql = "
        SELECT 
            ei.id AS indicator_id,
            ei.name AS indicator_name,
            ed.id AS domain_id,
            ed.name AS domain_name,
            AVG(ve.score) AS avg_score,
            (AVG(ve.score) * (100/3)) AS percentage_score
        FROM 
            visit_evaluations ve
        JOIN 
            visits v ON ve.visit_id = v.id
        JOIN 
            evaluation_indicators ei ON ve.indicator_id = ei.id
        JOIN 
            evaluation_domains ed ON ei.domain_id = ed.id
        WHERE 
            v.teacher_id = ? 
            AND ve.score IS NOT NULL -- استثناء المؤشرات غير المقاسة
        GROUP BY 
            ei.id, ei.name, ed.id, ed.name
        HAVING 
            AVG(ve.score) < ?
        ORDER BY 
            avg_score ASC
        LIMIT ?
    ";
    
    return query($sql, [$teacher_id, $threshold_score, $limit]);
}

/**
 * الحصول على العام الدراسي النشط
 *
 * @return array|null بيانات العام الدراسي النشط أو null إذا لم يوجد
 */
function get_active_academic_year() {
    return query_row("SELECT * FROM academic_years WHERE is_active = 1 LIMIT 1");
}

/**
 * دالة للحصول على اسم العام الدراسي من معرفه
 *
 * @param int $year_id معرف العام الدراسي
 * @return string اسم العام الدراسي
 */
function get_academic_year_name($year_id) {
    $year = query_row("SELECT name FROM academic_years WHERE id = ?", [$year_id]);
    return $year ? $year['name'] : '';
} 