<?php
/**
 * ملف القوانين والدوال الموحدة لنظام الزيارات الصفية
 * Visit Rules & Common Functions - ClassVisit System
 * 
 * يحتوي على جميع القوانين والثوابت والدوال المشتركة المستخدمة في النظام
 * Contains all rules, constants, and shared functions used in the system
 * 
 * @author ClassVisit Team
 * @version 1.0
 * @date 2024-12-18
 */

// ===========================================
// 📊 ثوابت التقييم والدرجات - EVALUATION CONSTANTS
// ===========================================

// الحد الأقصى للدرجة في كل مؤشر
define('MAX_INDICATOR_SCORE', 3);

// عدد المؤشرات الأساسية (بدون مجال العلوم)
define('BASIC_INDICATORS_COUNT', 20);

// عدد مؤشرات مجال العلوم (النشاط العملي)
define('SCIENCE_INDICATORS_COUNT', 5);

// معرف مجال العلوم في قاعدة البيانات
define('SCIENCE_DOMAIN_ID', 5);

// ===========================================
// 🏆 مستويات الأداء - PERFORMANCE LEVELS
// ===========================================

// النسب المئوية لتحديد مستويات الأداء (موحدة من الملفات الحالية)
define('EXCELLENT_THRESHOLD', 90);      // ممتاز
define('VERY_GOOD_THRESHOLD', 80);      // جيد جداً  
define('GOOD_THRESHOLD', 65);           // جيد (من view_visit.php)
define('ACCEPTABLE_THRESHOLD', 50);     // مقبول (من view_visit.php)
// أقل من 50% = يحتاج تحسين

// ملاحظة: يوجد تضارب في evaluation_form.php حيث جيد=70% ومقبول=60%
// تم اعتماد القيم من view_visit.php لأنها الأكثر استخداماً

// ===========================================
// 📈 ثوابت التقارير والإحصائيات - REPORTS CONSTANTS
// ===========================================

// عدد أفضل المعلمين في التقارير
define('TOP_TEACHERS_LIMIT', 5);

// عدد المعلمين الذين يحتاجون تطوير
define('NEEDS_IMPROVEMENT_LIMIT', 5);

// عدد أفضل المواد في التقارير
define('TOP_SUBJECTS_LIMIT', 3);

// الحد الأدنى لعدد الزيارات للاعتبار في التقارير
define('MIN_VISITS_FOR_REPORTS', 1);

// عتبة الاحتياجات التدريبية (درجة من 3)
define('TRAINING_NEEDS_THRESHOLD', 2.0);

// ===========================================
// 🎯 أنواع الزيارات - VISIT TYPES
// ===========================================

// أنواع الزيارات المتاحة (من evaluation_form.php)
define('VISIT_TYPE_FULL', 'full');         // زيارة كاملة
define('VISIT_TYPE_PARTIAL', 'partial');   // زيارة جزئية

// أنواع الحضور (من evaluation_form.php و view_visit.php)
define('ATTENDANCE_PHYSICAL', 'physical'); // حضور مباشر/حضوري
define('ATTENDANCE_REMOTE', 'remote');     // حضور عن بعد/افتراضي
define('ATTENDANCE_HYBRID', 'hybrid');     // حضور مختلط

// أسماء أنواع الحضور للعرض
define('ATTENDANCE_TYPES_AR', [
    'physical' => 'حضوري',
    'remote' => 'عن بعد', 
    'hybrid' => 'مختلط'
]);

define('ATTENDANCE_TYPES_EN', [
    'physical' => 'Physical',
    'remote' => 'Remote',
    'hybrid' => 'Hybrid'
]);

// ===========================================
// 👥 أنواع الزائرين - VISITOR TYPES
// ===========================================

// معرفات أنواع الزائرين في قاعدة البيانات
define('VISITOR_TYPE_PRINCIPAL', 1);       // المدير
define('VISITOR_TYPE_VICE_PRINCIPAL', 2);  // النائب الأكاديمي
define('VISITOR_TYPE_COORDINATOR', 3);     // منسق المادة
define('VISITOR_TYPE_SUPERVISOR', 4);      // موجه المادة

// ===========================================
// 🔢 دوال حساب الدرجات الموحدة - UNIFIED SCORING FUNCTIONS
// ===========================================

/**
 * الدالة الموحدة لحساب أداء الزيارة الواحدة
 * UNIFIED function to calculate single visit performance
 * 
 * هذه هي الطريقة الوحيدة المعتمدة لحساب أداء أي زيارة في النظام
 * 
 * @param int $visit_id معرف الزيارة
 * @param bool $include_lab هل تشمل مجال العلوم
 * @return array [average_score, percentage, total_indicators, total_points]
 */
function calculateVisitPerformance($visit_id, $include_lab = false) {
    try {
        // استعلام موحد لجلب درجات الزيارة
        if (!$include_lab) {
            $sql = "
                SELECT ve.score
                FROM visit_evaluations ve
                JOIN evaluation_indicators ei ON ve.indicator_id = ei.id
                WHERE ve.visit_id = ? AND ei.domain_id <> ? AND ve.score IS NOT NULL
            ";
            $params = [$visit_id, SCIENCE_DOMAIN_ID];
        } else {
            $sql = "
                SELECT score 
                FROM visit_evaluations 
                WHERE visit_id = ? AND score IS NOT NULL
            ";
            $params = [$visit_id];
        }
        
        $scores = query($sql, $params);
        
        if (empty($scores)) {
            return [
                'average_score' => 0,
                'percentage' => 0,
                'total_indicators' => 0,
                'total_points' => 0
            ];
        }
        
        // الحساب الموحد الوحيد المعتمد في النظام
        $total_points = array_sum(array_column($scores, 'score'));
        $indicators_count = count($scores);
        
        // حساب المتوسط والنسبة المئوية بالطريقة الموحدة
        $average_score = round($total_points / $indicators_count, 2);
        $percentage = round(($total_points / ($indicators_count * MAX_INDICATOR_SCORE)) * 100, 2);
        
        return [
            'average_score' => $average_score,
            'percentage' => $percentage,
            'total_indicators' => $indicators_count,
            'total_points' => $total_points
        ];
        
    } catch (Exception $e) {
        error_log("خطأ في حساب أداء الزيارة: " . $e->getMessage());
        return [
            'average_score' => 0,
            'percentage' => 0,
            'total_indicators' => 0,
            'total_points' => 0
        ];
    }
}

/**
 * ⭐ الدالة الموحدة الوحيدة لحساب الأداء العام في النظام ⭐
 * UNIFIED function to calculate overall performance - THE ONLY METHOD TO USE
 * 
 * 🚨 تحذير: يجب استخدام هذه الدالة فقط في جميع الصفحات لضمان التوحيد الكامل
 * 
 * @param int $academic_year_id معرف السنة الدراسية
 * @param string $date_condition شرط التاريخ (اختياري)
 * @param array $additional_conditions شروط إضافية (اختياري)
 * @return float النسبة المئوية الموحدة
 */
function calculateUnifiedOverallPerformance($academic_year_id, $date_condition = '', $additional_conditions = []) {
    try {
        // الاستعلام الموحد الوحيد المعتمد في النظام
        $sql = "
            SELECT ve.score
            FROM visit_evaluations ve
            JOIN visits v ON ve.visit_id = v.id
            JOIN teachers t ON v.teacher_id = t.id
            WHERE v.academic_year_id = ? AND ve.score IS NOT NULL
        ";
        
        $params = [$academic_year_id];
        
        // إضافة شرط الوظيفة الافتراضي (معلم فقط) إذا لم يُحدد غير ذلك
        $has_job_title_condition = false;
        foreach ($additional_conditions as $condition => $value) {
            if (strpos($condition, 'job_title') !== false) {
                $has_job_title_condition = true;
                break;
            }
        }
        
        if (!$has_job_title_condition) {
            $sql .= " AND t.job_title = 'معلم'";
        }
        
        // إضافة شرط التاريخ إذا وُجد
        if (!empty($date_condition)) {
            $sql .= $date_condition;
        }
        
        // إضافة الشروط الإضافية
        foreach ($additional_conditions as $condition => $value) {
            $sql .= " AND $condition = ?";
            $params[] = $value;
        }
        
        $scores = query($sql, $params);
        
        if (empty($scores)) {
            return 0;
        }
        
        // 🎯 الحساب الموحد الوحيد: مجموع جميع النقاط ÷ (مجموع جميع المؤشرات × 3) × 100
        $total_points = array_sum(array_column($scores, 'score'));
        $total_indicators = count($scores);
        
        return round(($total_points / ($total_indicators * MAX_INDICATOR_SCORE)) * 100, 1);
        
    } catch (Exception $e) {
        error_log("خطأ في حساب الأداء العام الموحد: " . $e->getMessage());
        return 0;
    }
}

/**
 * ⭐ دالة موحدة لحساب أداء وظيفة معينة ⭐
 * UNIFIED function to calculate performance for specific job title
 * 
 * @param int $academic_year_id معرف السنة الدراسية
 * @param string $job_title الوظيفة (معلم، منسق المادة، إلخ)
 * @param string $date_condition شرط التاريخ (اختياري)
 * @return float النسبة المئوية الموحدة
 */
function calculateUnifiedJobPerformance($academic_year_id, $job_title, $date_condition = '') {
    return calculateUnifiedOverallPerformance($academic_year_id, $date_condition, ['t.job_title' => $job_title]);
}

/**
 * تحديد مستوى الأداء بناءً على النسبة المئوية
 * Determine performance level based on percentage
 * 
 * @param float $percentage النسبة المئوية
 * @return array [grade_ar, grade_en, color_class, bg_class]
 */
function getPerformanceLevel($percentage) {
    if ($percentage >= EXCELLENT_THRESHOLD) {
        return [
            'grade_ar' => 'ممتاز',
            'grade_en' => 'Excellent',
            'color_class' => 'text-green-600',
            'bg_class' => 'bg-green-100'
        ];
    } elseif ($percentage >= VERY_GOOD_THRESHOLD) {
        return [
            'grade_ar' => 'جيد جداً',
            'grade_en' => 'Very Good',
            'color_class' => 'text-blue-600',
            'bg_class' => 'bg-blue-100'
        ];
    } elseif ($percentage >= GOOD_THRESHOLD) {
        return [
            'grade_ar' => 'جيد',
            'grade_en' => 'Good',
            'color_class' => 'text-yellow-600',
            'bg_class' => 'bg-yellow-100'
        ];
    } elseif ($percentage >= ACCEPTABLE_THRESHOLD) {
        return [
            'grade_ar' => 'مقبول',
            'grade_en' => 'Acceptable',
            'color_class' => 'text-orange-600',
            'bg_class' => 'bg-orange-100'
        ];
    } else {
        return [
            'grade_ar' => 'يحتاج تحسين',
            'grade_en' => 'Needs Improvement',
            'color_class' => 'text-red-600',
            'bg_class' => 'bg-red-100'
        ];
    }
}

/**
 * ملاحظة: دالة get_grade() متوفرة في includes/functions.php
 * وتستخدم نفس القوانين الموحدة (90%, 80%, 65%, 50%)
 */

// ===========================================
// 📊 دوال الإحصائيات - STATISTICS FUNCTIONS
// ===========================================

/**
 * جلب أفضل المعلمين أداءً
 * Get top performing teachers
 * 
 * @param int $academic_year_id معرف السنة الدراسية
 * @param int $limit عدد المعلمين المطلوب
 * @return array
 */
function getTopTeachers($academic_year_id, $limit = TOP_TEACHERS_LIMIT) {
    $sql = "
        SELECT 
            t.id,
            t.name,
            t.job_title,
            COUNT(DISTINCT v.id) as visits_count,
            AVG(ve.score) as avg_score,
            ROUND((AVG(ve.score) / ?) * 100, 2) as percentage
        FROM teachers t
        JOIN visits v ON t.id = v.teacher_id
        JOIN visit_evaluations ve ON v.id = ve.visit_id
        WHERE v.academic_year_id = ?
        GROUP BY t.id, t.name, t.job_title
        HAVING COUNT(DISTINCT v.id) >= ?
        ORDER BY avg_score DESC, visits_count DESC
        LIMIT ?
    ";
    
    return query($sql, [
        MAX_INDICATOR_SCORE, 
        $academic_year_id, 
        MIN_VISITS_FOR_REPORTS, 
        $limit
    ]);
}

/**
 * جلب المعلمين الذين يحتاجون تطوير
 * Get teachers needing improvement
 * 
 * @param int $academic_year_id معرف السنة الدراسية
 * @param int $limit عدد المعلمين المطلوب
 * @return array
 */
function getTeachersNeedingImprovement($academic_year_id, $limit = NEEDS_IMPROVEMENT_LIMIT) {
    $sql = "
        SELECT 
            t.id,
            t.name,
            t.job_title,
            COUNT(DISTINCT v.id) as visits_count,
            AVG(ve.score) as avg_score,
            ROUND((AVG(ve.score) / ?) * 100, 2) as percentage
        FROM teachers t
        JOIN visits v ON t.id = v.teacher_id
        JOIN visit_evaluations ve ON v.id = ve.visit_id
        WHERE v.academic_year_id = ?
        GROUP BY t.id, t.name, t.job_title
        HAVING COUNT(DISTINCT v.id) >= ? AND AVG(ve.score) < ?
        ORDER BY avg_score ASC, visits_count DESC
        LIMIT ?
    ";
    
    return query($sql, [
        MAX_INDICATOR_SCORE,
        $academic_year_id,
        MIN_VISITS_FOR_REPORTS,
        TRAINING_NEEDS_THRESHOLD,
        $limit
    ]);
}

/**
 * حساب إحصائيات المعلمين حسب مستوى الأداء
 * Calculate teacher statistics by performance level
 * 
 * @param int $academic_year_id معرف السنة الدراسية
 * @return array
 */
function getTeachersByPerformanceLevel($academic_year_id) {
    $sql = "
        SELECT 
            t.job_title,
            COUNT(DISTINCT t.id) as total_teachers,
            COUNT(DISTINCT v.teacher_id) as evaluated_teachers,
            ROUND(AVG(ve.score), 2) as avg_score,
            ROUND((AVG(ve.score) / ?) * 100, 2) as avg_percentage,
            SUM(CASE WHEN (AVG(ve.score) / ?) * 100 >= ? THEN 1 ELSE 0 END) as excellent_count,
            SUM(CASE WHEN (AVG(ve.score) / ?) * 100 >= ? AND (AVG(ve.score) / ?) * 100 < ? THEN 1 ELSE 0 END) as very_good_count,
            SUM(CASE WHEN (AVG(ve.score) / ?) * 100 >= ? AND (AVG(ve.score) / ?) * 100 < ? THEN 1 ELSE 0 END) as good_count,
            SUM(CASE WHEN (AVG(ve.score) / ?) * 100 < ? THEN 1 ELSE 0 END) as needs_improvement_count
        FROM teachers t
        LEFT JOIN visits v ON t.id = v.teacher_id AND v.academic_year_id = ?
        LEFT JOIN visit_evaluations ve ON v.id = ve.visit_id
        WHERE t.job_title IN ('معلم', 'منسق المادة')
        GROUP BY t.job_title
    ";
    
    return query($sql, [
        MAX_INDICATOR_SCORE, MAX_INDICATOR_SCORE, EXCELLENT_THRESHOLD,
        MAX_INDICATOR_SCORE, VERY_GOOD_THRESHOLD, MAX_INDICATOR_SCORE, EXCELLENT_THRESHOLD,
        MAX_INDICATOR_SCORE, GOOD_THRESHOLD, MAX_INDICATOR_SCORE, VERY_GOOD_THRESHOLD,
        MAX_INDICATOR_SCORE, GOOD_THRESHOLD,
        $academic_year_id
    ]);
}

// ===========================================
// 🏫 دوال المدارس والمواد - SCHOOLS & SUBJECTS FUNCTIONS
// ===========================================

/**
 * جلب أفضل المدارس أداءً
 * Get top performing schools
 * 
 * @param int $academic_year_id معرف السنة الدراسية
 * @return array
 */
function getTopSchools($academic_year_id) {
    $sql = "
        SELECT 
            s.id,
            s.name,
            COUNT(DISTINCT v.id) as visits_count,
            COUNT(DISTINCT v.teacher_id) as teachers_count,
            AVG(ve.score) as avg_score,
            ROUND((AVG(ve.score) / ?) * 100, 2) as percentage
        FROM schools s
        JOIN visits v ON s.id = v.school_id
        JOIN visit_evaluations ve ON v.id = ve.visit_id
        WHERE v.academic_year_id = ?
        GROUP BY s.id, s.name
        ORDER BY avg_score DESC
    ";
    
    return query($sql, [MAX_INDICATOR_SCORE, $academic_year_id]);
}

/**
 * جلب إحصائيات المواد
 * Get subjects statistics
 * 
 * @param int $academic_year_id معرف السنة الدراسية
 * @return array
 */
function getSubjectsStatistics($academic_year_id) {
    $sql = "
        SELECT 
            subj.id,
            subj.name,
            COUNT(DISTINCT ts.teacher_id) as total_teachers,
            COUNT(DISTINCT v.teacher_id) as visited_teachers,
            COUNT(DISTINCT v.id) as visits_count,
            AVG(ve.score) as avg_score,
            ROUND((AVG(ve.score) / ?) * 100, 2) as percentage,
            ROUND((COUNT(DISTINCT v.teacher_id) / COUNT(DISTINCT ts.teacher_id)) * 100, 2) as coverage_percentage
        FROM subjects subj
        LEFT JOIN teacher_subjects ts ON subj.id = ts.subject_id
        LEFT JOIN visits v ON subj.id = v.subject_id AND v.academic_year_id = ?
        LEFT JOIN visit_evaluations ve ON v.id = ve.visit_id
        GROUP BY subj.id, subj.name
        ORDER BY avg_score DESC
    ";
    
    return query($sql, [MAX_INDICATOR_SCORE, $academic_year_id]);
}

// ===========================================
// 🎯 دوال التحقق من صحة البيانات - VALIDATION FUNCTIONS
// ===========================================

/**
 * التحقق من صحة درجة المؤشر
 * Validate indicator score
 * 
 * @param mixed $score الدرجة المدخلة
 * @return bool
 */
function isValidIndicatorScore($score) {
    return is_numeric($score) && $score >= 0 && $score <= MAX_INDICATOR_SCORE;
}

/**
 * التحقق من وجود الزيارة
 * Check if visit exists
 * 
 * @param int $visit_id معرف الزيارة
 * @return bool
 */
function visitExists($visit_id) {
    $result = query_row("SELECT id FROM visits WHERE id = ?", [$visit_id]);
    return !empty($result);
}

/**
 * التحقق من صلاحية المستخدم للوصول للزيارة
 * Check user permission to access visit
 * 
 * @param int $visit_id معرف الزيارة
 * @param int $user_id معرف المستخدم
 * @return bool
 */
function canUserAccessVisit($visit_id, $user_id) {
    // المديرين والنواب يمكنهم الوصول لجميع الزيارات
    $user_role = query_row("SELECT role_id FROM users WHERE id = ?", [$user_id]);
    
    if (!$user_role) return false;
    
    // دور المدير (1) والنائب (2) لهم صلاحية كاملة
    if (in_array($user_role['role_id'], [1, 2])) {
        return true;
    }
    
    // المنسقين يمكنهم الوصول لزيارات مادتهم فقط
    if ($user_role['role_id'] == 3) {
        $visit_subject = query_row("SELECT subject_id FROM visits WHERE id = ?", [$visit_id]);
        $user_subjects = query("SELECT subject_id FROM teacher_subjects ts JOIN teachers t ON ts.teacher_id = t.id WHERE t.user_id = ?", [$user_id]);
        
        if ($visit_subject && $user_subjects) {
            $user_subject_ids = array_column($user_subjects, 'subject_id');
            return in_array($visit_subject['subject_id'], $user_subject_ids);
        }
    }
    
    return false;
}

// ===========================================
// 📅 دوال السنة الدراسية - ACADEMIC YEAR FUNCTIONS
// ===========================================

/**
 * جلب معرف السنة الدراسية النشطة
 * Get active academic year ID
 * 
 * @return int|null
 */
function getActiveAcademicYearId() {
    $year = get_active_academic_year(); // استخدام الدالة الموجودة في functions.php
    return $year ? $year['id'] : null;
}

/**
 * ملاحظة: دالة get_active_academic_year() متوفرة في includes/functions.php
 */

// ===========================================
// 🔄 دوال مساعدة - HELPER FUNCTIONS
// ===========================================

/**
 * تنسيق النسبة المئوية للعرض
 * Format percentage for display
 * 
 * @param float $percentage النسبة
 * @param int $decimals عدد الخانات العشرية
 * @return string
 */
function formatPercentage($percentage, $decimals = 1) {
    return number_format($percentage, $decimals) . '%';
}

/**
 * تحويل نوع الزيارة إلى نص
 * Convert visit type to text
 * 
 * @param string $visit_type نوع الزيارة
 * @return string
 */
function getVisitTypeText($visit_type) {
    switch ($visit_type) {
        case VISIT_TYPE_FULL:
            return 'زيارة كاملة';
        case VISIT_TYPE_PARTIAL:
            return 'زيارة جزئية';
        default:
            return 'غير محدد';
    }
}

/**
 * تحويل نوع الحضور إلى نص
 * Convert attendance type to text
 * 
 * @param string $attendance_type نوع الحضور
 * @return string
 */
function getAttendanceTypeText($attendance_type) {
    switch ($attendance_type) {
        case ATTENDANCE_PHYSICAL:
            return 'حضور مباشر';
        case ATTENDANCE_REMOTE:
            return 'حضور عن بعد';
        case ATTENDANCE_HYBRID:
            return 'حضور مختلط';
        default:
            return 'حضور مباشر';
    }
}

/**
 * جلب لون الأداء للعرض
 * Get performance color for display
 * 
 * @param float $percentage النسبة المئوية
 * @return string
 */
function getPerformanceColor($percentage) {
    if ($percentage >= EXCELLENT_THRESHOLD) {
        return 'success'; // أخضر
    } elseif ($percentage >= VERY_GOOD_THRESHOLD) {
        return 'primary'; // أزرق
    } elseif ($percentage >= GOOD_THRESHOLD) {
        return 'warning'; // أصفر
    } elseif ($percentage >= ACCEPTABLE_THRESHOLD) {
        return 'info'; // سماوي
    } else {
        return 'danger'; // أحمر
    }
}

// ===========================================
// 📋 دوال التقارير المخصصة - CUSTOM REPORTS FUNCTIONS
// ===========================================

/**
 * جلب تقرير شامل للمعلم
 * Get comprehensive teacher report
 * 
 * @param int $teacher_id معرف المعلم
 * @param int $academic_year_id معرف السنة الدراسية
 * @return array
 */
function getTeacherComprehensiveReport($teacher_id, $academic_year_id) {
    // معلومات المعلم الأساسية
    $teacher_info = query_row("
        SELECT t.*, s.name as school_name 
        FROM teachers t 
        LEFT JOIN schools s ON t.school_id = s.id 
        WHERE t.id = ?
    ", [$teacher_id]);
    
    if (!$teacher_info) return null;
    
    // إحصائيات الزيارات
    $visit_stats = query_row("
        SELECT 
            COUNT(*) as total_visits,
            AVG(ve.score) as avg_score,
            ROUND((AVG(ve.score) / ?) * 100, 2) as avg_percentage,
            MIN(v.visit_date) as first_visit,
            MAX(v.visit_date) as last_visit
        FROM visits v
        JOIN visit_evaluations ve ON v.id = ve.visit_id
        WHERE v.teacher_id = ? AND v.academic_year_id = ?
    ", [MAX_INDICATOR_SCORE, $teacher_id, $academic_year_id]);
    
    // تفاصيل الزيارات
    $visits_details = query("
        SELECT 
            v.*,
            vt.name as visitor_type_name,
            visitor.name as visitor_name,
            subj.name as subject_name,
            AVG(ve.score) as avg_score,
            ROUND((AVG(ve.score) / ?) * 100, 2) as percentage
        FROM visits v
        LEFT JOIN visitor_types vt ON v.visitor_type_id = vt.id
        LEFT JOIN teachers visitor ON v.visitor_person_id = visitor.id
        LEFT JOIN subjects subj ON v.subject_id = subj.id
        LEFT JOIN visit_evaluations ve ON v.id = ve.visit_id
        WHERE v.teacher_id = ? AND v.academic_year_id = ?
        GROUP BY v.id
        ORDER BY v.visit_date DESC
    ", [MAX_INDICATOR_SCORE, $teacher_id, $academic_year_id]);
    
    return [
        'teacher_info' => $teacher_info,
        'visit_stats' => $visit_stats ?: [],
        'visits_details' => $visits_details ?: [],
        'performance_level' => $visit_stats ? getPerformanceLevel($visit_stats['avg_percentage']) : null
    ];
}

// ===========================================
// 🎨 دوال العرض والتنسيق - DISPLAY & FORMATTING FUNCTIONS
// ===========================================

/**
 * عرض شارة الأداء
 * Display performance badge
 * 
 * @param float $percentage النسبة المئوية
 * @return string HTML
 */
function displayPerformanceBadge($percentage) {
    $level = getPerformanceLevel($percentage);
    return sprintf(
        '<span class="badge %s">%s (%s)</span>',
        $level['bg_class'] . ' ' . $level['color_class'],
        $level['grade_ar'],
        formatPercentage($percentage)
    );
}

/**
 * عرض شريط التقدم
 * Display progress bar
 * 
 * @param float $percentage النسبة المئوية
 * @return string HTML
 */
function displayProgressBar($percentage) {
    $color = getPerformanceColor($percentage);
    return sprintf(
        '<div class="progress mb-2">
            <div class="progress-bar bg-%s" style="width: %s%%" role="progressbar">
                %s
            </div>
        </div>',
        $color,
        $percentage,
        formatPercentage($percentage)
    );
}

// ===========================================
// ⚠️ دوال معالجة الأخطاء - ERROR HANDLING FUNCTIONS
// ===========================================

/**
 * تسجيل خطأ في النظام
 * Log system error
 * 
 * @param string $message رسالة الخطأ
 * @param array $context السياق الإضافي
 */
function logSystemError($message, $context = []) {
    $log_message = date('Y-m-d H:i:s') . " - " . $message;
    if (!empty($context)) {
        $log_message .= " - Context: " . json_encode($context);
    }
    error_log($log_message);
}

/**
 * عرض رسالة خطأ مصممة
 * Display formatted error message
 * 
 * @param string $message رسالة الخطأ
 * @return string HTML
 */
function displayError($message) {
    return sprintf(
        '<div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle ml-2"></i>
            %s
        </div>',
        htmlspecialchars($message)
    );
}

// ===========================================
// 📊 إحصائيات نظام قطر للتعليم - QATAR SYSTEM STATISTICS
// ===========================================

// أدوات التعليم الإلكتروني المتاحة (من elearning_reports.php)
define('ELEARNING_TOOLS', [
    'qatar_system' => 'نظام قطر للتعليم',
    'tablets' => 'الأجهزة اللوحية',
    'interactive_display' => 'أجهزة العرض التفاعلي',
    'ai_applications' => 'تطبيقات الذكاء الاصطناعي',
    'interactive_websites' => 'المواقع التفاعلية'
]);

// مستويات تقييم الحضور (من elearning_reports.php)
define('ATTENDANCE_RATINGS', [
    'excellent' => 'ممتاز',
    'very_good' => 'جيد جداً',
    'good' => 'جيد', 
    'acceptable' => 'مقبول',
    'poor' => 'ضعيف'
]);

// مستويات الأداء لنظام قطر (من elearning_reports.php)
define('QATAR_PERFORMANCE_LEVELS', [
    'excellent' => 'ممتاز',
    'very_good' => 'جيد جداً',
    'good' => 'جيد',
    'needs_improvement' => 'يحتاج تحسين',
    'poor' => 'ضعيف'
]);

/**
 * حساب تقييم أدوات التعليم الإلكتروني
 * Calculate e-learning tools evaluation
 * 
 * @param array $tools الأدوات المستخدمة
 * @return array [score, level, tools_count]
 */
function calculateElearningToolsScore($tools) {
    $tools_count = count(array_filter($tools));
    
    if ($tools_count == 0) {
        $level = 'ضعيف';
        $score = 1;
    } elseif ($tools_count == 1) {
        $level = 'مقبول';
        $score = 2;
    } elseif ($tools_count == 2) {
        $level = 'جيد';
        $score = 3;
    } elseif ($tools_count == 3) {
        $level = 'جيد جداً';
        $score = 4;
    } else {
        $level = 'ممتاز';
        $score = 5;
    }
    
    return [
        'score' => $score,
        'level' => $level,
        'tools_count' => $tools_count,
        'percentage' => ($score / 5) * 100
    ];
}

// ===========================================
// 🔚 نهاية الملف - END OF FILE
// ===========================================

// ===========================================
// 📋 ملخص القوانين الموحدة النهائية - FINAL UNIFIED RULES SUMMARY
// ===========================================

/**
 * 🎯 القوانين الموحدة الوحيدة المعتمدة في النظام:
 * 
 * 1️⃣ حساب درجة الزيارة الواحدة:
 *    نسبة الزيارة = (مجموع نقاط المؤشرات ÷ (عدد المؤشرات × 3)) × 100
 *    
 * 2️⃣ حساب الأداء العام (متعدد الزيارات):
 *    النسبة العامة = (مجموع جميع النقاط ÷ (مجموع جميع المؤشرات × 3)) × 100
 *    
 * 3️⃣ مستويات الأداء الموحدة:
 *    - ممتاز: 90% فأكثر
 *    - جيد جداً: 80% - 89%
 *    - جيد: 65% - 79%
 *    - مقبول: 50% - 64%
 *    - يحتاج تحسين: أقل من 50%
 *    
 * 4️⃣ استبعاد مجال العلوم:
 *    - تلقائياً إذا has_lab = 0
 *    - domain_id = 5 يُستبعد إذا لم يكن معمل
 *    
 * 5️⃣ الدوال الموحدة الإجبارية:
 *    - calculateVisitPerformance() للزيارة الواحدة
 *    - calculateUnifiedOverallPerformance() للأداء العام
 *    - getPerformanceLevel() لتحديد المستوى
 * 
 * ⚠️ تحذير هام:
 * - يُمنع استخدام أي حسابات مباشرة في الصفحات
 * - يُمنع استخدام / 3 مباشرة - استخدم MAX_INDICATOR_SCORE
 * - يُمنع استخدام عتبات ثابتة - استخدم الثوابت المعرفة
 * - أي تعديل في القوانين يتم هنا فقط
 * 
 * معلومات الملف:
 * - تاريخ الإنشاء: 18 ديسمبر 2024
 * - الإصدار: 2.0 - موحد بالكامل
 * - المطور: فريق نظام الزيارات الصفية
 */

?>
