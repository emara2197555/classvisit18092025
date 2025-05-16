<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نموذج تقييم زيارة صفية</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- خط Cairo من Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- تخصيص Tailwind -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                            950: '#082f49',
                        },
                        secondary: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            200: '#99f6e4',
                            300: '#5eead4',
                            400: '#2dd4bf',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                            950: '#042f2e',
                        }
                    },
                    fontFamily: {
                        'cairo': ['Cairo', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f9fafb;
            margin: 0;
            padding: 0;
            margin-top: 65px; /* لإفساح المجال للناف بار الثابت */
        }
        
        /* قواعد نمط إضافية للقوائم المنسدلة - تعديل لمشكلة الاختفاء السريع */
        .dropdown-menu {
            display: none;
            transition: visibility 0.3s, opacity 0.3s;
            visibility: hidden;
            opacity: 0;
        }
        
        .dropdown:hover .dropdown-menu {
            display: block;
            visibility: visible;
            opacity: 1;
            transition-delay: 0s;
        }
        
        /* إضافة تأخير للاختفاء */
        .dropdown-menu:hover {
            visibility: visible;
            opacity: 1;
        }
        
        .dropdown {
            position: relative;
        }
        
        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            z-index: 1000;
            min-width: 10rem;
            padding: 0.5rem 0;
            background-color: #fff;
            border-radius: 0.25rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .score-4 { background-color: rgba(5, 150, 105, 0.1); border-color: #059669; }
        .score-3 { background-color: rgba(2, 132, 199, 0.1); border-color: #0284c7; }
        .score-2 { background-color: rgba(245, 158, 11, 0.1); border-color: #f59e0b; }
        .score-1 { background-color: rgba(220, 38, 38, 0.1); border-color: #dc2626; }
        .score-0 { background-color: rgba(107, 114, 128, 0.1); border-color: #6b7280; }
        .scrollbar-thin::-webkit-scrollbar { width: 6px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 3px; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #0284c7; border-radius: 3px; }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover { background: #075985; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
<nav class="bg-primary-700">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center">
                <a href="index.php" class="text-white font-bold text-xl">نظام الزيارات الصفية</a>
            </div>
            <div class="block">
                <div class="flex items-center space-x-4 space-x-reverse">
                    <!-- روابط ثابتة -->
                    <a href="index.php" class="<br />
<b>Warning</b>:  Undefined variable $current_page in <b>C:\laragon\www\classvisit\includes\header.php</b> on line <b>136</b><br />
hover:bg-primary-600 text-white px-4 py-2 rounded-md">
                        الرئيسية
                    </a>

                    <a href="visits.php" class="<br />
<b>Warning</b>:  Undefined variable $current_page in <b>C:\laragon\www\classvisit\includes\header.php</b> on line <b>140</b><br />
hover:bg-primary-600 text-white px-4 py-2 rounded-md">
                        الزيارات الصفية
                    </a>

                    <a href="evaluation_form.php" class="<br />
<b>Warning</b>:  Undefined variable $current_page in <b>C:\laragon\www\classvisit\includes\header.php</b> on line <b>144</b><br />
hover:bg-primary-600 text-white px-4 py-2 rounded-md">
                        زيارة جديدة
                    </a>

                    <div class="relative group">
    <button class="text-white px-4 py-2 rounded-md flex items-center hover:bg-primary-600">
        الإدارة
        <svg class="mr-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
        </svg>
    </button>
    <div class="absolute right-0 top-full w-56 bg-white rounded-md shadow-lg py-1 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition duration-150 ease-in-out z-10">
        <a href="teachers_management.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">إدارة المعلمين</a>
        <a href="subjects_management.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">إدارة المواد</a>
        <a href="sections_management.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">إدارة الفصول</a>
        <a href="academic_years_management.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">إدارة الأعوام الدراسية</a>
        <div class="border-t my-1"></div>
        <a href="class_performance_report.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">تقرير أداء المعلمين</a>
        <a href="grades_performance_report.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">تقرير أداء الصفوف</a>
        <a href="subject_performance_report.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">تقرير أداء المواد</a>
    </div>
</div>

                    <!-- قائمة منسدلة: الاحتياجات التدريبية -->
                    <div class="relative group">
                        <button class="text-white px-4 py-2 rounded-md flex items-center hover:bg-primary-600">
                            الاحتياجات التدريبية
                            <svg class="mr-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                        <div class="absolute right-0 top-full w-48 bg-white rounded-md shadow-lg py-1 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition duration-150 ease-in-out z-10">
                            <a href="training_needs.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">الاحتياجات الفردية</a>
                            <a href="collective_training_needs.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">الاحتياجات الجماعية</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<main class="container mx-auto py-6 px-4">

<!-- نموذج اختيار المدرسة والمادة والمعلم -->
<div id="selection-form" class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">نموذج تقييم زيارة صفية</h1>
    
        
    <form action="evaluation_form.php" method="post" id="visit-form">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <!-- نوع الزائر -->
            <div>
                <label class="block mb-2 font-semibold">نوع الزائر:</label>
                <select id="visitor-type" name="visitor_type_id" class="w-full border p-2 rounded" required onchange="updateVisitorName()">
                    <option value="">اختر نوع الزائر...</option>
                                            <option value="17">النائب الأكاديمي</option>
                                            <option value="18">مدير</option>
                                            <option value="15">منسق المادة</option>
                                            <option value="16">موجه المادة</option>
                                    </select>
                <div id="visitor-name" class="mt-2 text-sm text-gray-600"></div>
                <input type="hidden" id="visitor-person-id" name="visitor_person_id" value="">
            </div>

            <!-- نوع الزيارة -->
            <div>
                <label class="block mb-2 font-semibold">نوع الزيارة:</label>
                <select id="visit-type" name="visit_type" class="w-full border p-2 rounded" required>
                    <option value="full">تقييم كلي</option>
                    <option value="partial">تقييم جزئي</option>
                </select>
            </div>

            <!-- طريقة الحضور -->
            <div>
                <label class="block mb-2 font-semibold">طريقة الحضور:</label>
                <select id="attendance-type" name="attendance_type" class="w-full border p-2 rounded" required>
                    <option value="physical">حضور</option>
                    <option value="remote">عن بعد</option>
                    <option value="hybrid">مدمج</option>
                </select>
            </div>

            <!-- المادة -->
            <div>
                <label class="block mb-2 font-semibold">المادة:</label>
                <select id="subject" name="subject_id" class="w-full border p-2 rounded" required onchange="loadTeachers()">
                    <option value="">اختر المادة...</option>
                                            <option value="7">الأحياء</option>
                                            <option value="8">التربية الإسلامية</option>
                                            <option value="11">التربية البدنية</option>
                                            <option value="10">التربية الفنية</option>
                                            <option value="14">التربية الموسيقية</option>
                                            <option value="16">التصميم والتكنولوجيا</option>
                                            <option value="12">الحاسب الآلي</option>
                                            <option value="9">الدراسات الاجتماعية</option>
                                            <option value="3">الرياضيات</option>
                                            <option value="4">العلوم</option>
                                            <option value="5">الفيزياء</option>
                                            <option value="6">الكيمياء</option>
                                            <option value="2">اللغة الإنجليزية</option>
                                            <option value="1">اللغة العربية</option>
                                            <option value="15">اللغة الفرنسية</option>
                                            <option value="17">المكتبة والبحث</option>
                                            <option value="13">المهارات الحياتية</option>
                                    </select>
            </div>

            <!-- المعلم -->
            <div>
                <label class="block mb-2 font-semibold">المعلم:</label>
                <select id="teacher" name="teacher_id" class="w-full border p-2 rounded" required>
                    <option value="">اختر المعلم...</option>
                    <!-- سيتم تحديث هذه القائمة ديناميكياً بناءً على المدرسة والمادة المختارة -->
                </select>
            </div>

            <!-- الصف -->
            <div>
                <label class="block mb-2 font-semibold">الصف:</label>
                <select id="grade" name="grade_id" class="w-full border p-2 rounded" required onchange="loadSections(this.value)">
                    <option value="">اختر الصف...</option>
                                            <option value="1" data-level-id="1">الصف الأول</option>
                                            <option value="2" data-level-id="1">الصف الثاني</option>
                                            <option value="3" data-level-id="1">الصف الثالث</option>
                                            <option value="4" data-level-id="1">الصف الرابع</option>
                                            <option value="5" data-level-id="1">الصف الخامس</option>
                                            <option value="6" data-level-id="1">الصف السادس</option>
                                            <option value="7" data-level-id="2">الصف السابع</option>
                                            <option value="8" data-level-id="2">الصف الثامن</option>
                                            <option value="9" data-level-id="2">الصف التاسع</option>
                                            <option value="10" data-level-id="3">الصف العاشر</option>
                                            <option value="11" data-level-id="3">الصف الحادي عشر</option>
                                            <option value="12" data-level-id="3">الصف الثاني عشر</option>
                                    </select>
            </div>

            <!-- الشعبة -->
            <div>
                <label class="block mb-2 font-semibold">الشعبة:</label>
                <select id="section" name="section_id" class="w-full border p-2 rounded" required>
                    <option value="">اختر الشعبة...</option>
                    <!-- سيتم تحديث هذه القائمة ديناميكياً بناءً على الصف المختار -->
                </select>
            </div>

            <!-- تقييم المعمل -->
            <div class="flex items-center">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="has-lab" name="has_lab" class="form-checkbox h-5 w-5 text-blue-600">
                    <span class="mr-2 font-semibold">إضافة تقييم المعمل</span>
                </label>
            </div>

            <!-- تاريخ الزيارة -->
            <div>
                <label class="block mb-2 font-semibold">تاريخ الزيارة:</label>
                <input type="date" id="visit-date" name="visit_date" class="w-full border p-2 rounded" required>
            </div>

            <!-- حذف حقل المدرسة - سنستخدم المدرسة الافتراضية -->
            <input type="hidden" id="school" name="school_id" value="1">
        </div>

        <button type="button" id="start-evaluation-btn" class="bg-primary-600 text-white px-6 py-2 rounded hover:bg-primary-700 transition">بدء التقييم</button>
    </form>
</div>

<!-- نموذج التقييم (يظهر بعد اختيار بيانات المعلم والمدرسة) -->
<div id="evaluation-form" class="bg-white rounded-lg shadow-md p-6" style="display: none;">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">تقييم الزيارة الصفية</h1>
        <button id="back-to-selection" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">تغيير المعلم/المدرسة</button>
    </div>

    <div id="evaluation-header" class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6"></div>
    
    <!-- معلومات الزيارات السابقة -->
    <div id="previous-visits-info" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6" style="display: none;">
        <h2 class="text-lg font-bold text-blue-700 mb-3">معلومات الزيارات السابقة</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="font-semibold">عدد الزيارات السابقة: <span id="visits-count" class="font-normal">-</span></p>
                <p class="font-semibold">تاريخ آخر زيارة: <span id="last-visit-date" class="font-normal">-</span></p>
                <p class="font-semibold">نسبة آخر زيارة: <span id="last-visit-percentage" class="font-normal">-</span></p>
            </div>
            <div>
                <p class="font-semibold">الصف والشعبة: <span id="last-visit-class" class="font-normal">-</span></p>
            </div>
        </div>
        <div class="mt-3">
            <p class="font-semibold">ملاحظات الزيارة السابقة:</p>
            <div id="last-visit-notes" class="mt-2 p-3 bg-white rounded border border-blue-200 text-sm max-h-32 overflow-y-auto">-</div>
        </div>
    </div>

    <form id="evaluation-save-form" action="evaluation_form.php" method="post">
    <!-- أقسام التقييم -->
                <div id="step-1" class="evaluation-section bg-white border border-gray-200 border-r-4 border-r-primary-600 rounded-lg p-6 mb-4 max-h-[70vh] overflow-y-auto scrollbar-thin" style="display: block;">
            <h2 class="text-xl font-bold text-primary-700 mb-6 pb-2 border-b border-gray-200">التخطيط للدرس</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div class="indicator-block ">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">خطة الدرس متوفرة وبنودها مستكملة ومناسبة.</label>
                        <select name="score_1" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_1[]" value="239" id="rec_239_1" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_239_1" class="mr-2 text-sm text-gray-700">يجب توفّر الخطّة على نظام قطر للتعليم.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_1[]" value="240" id="rec_240_1" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_240_1" class="mr-2 text-sm text-gray-700">يجب اتساق الخطّة زمنيا مع الخطة الفصلية.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_1[]" value="241" id="rec_241_1" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_241_1" class="mr-2 text-sm text-gray-700">يجب أن تكون الخطّة مكتوبة بلغة سليمة وتتسم بالدقة والوضوح.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_1[]" value="242" id="rec_242_1" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_242_1" class="mr-2 text-sm text-gray-700">يجب أن تتوافر التهيئة في الخطّة وأن تكون مرتبطة بموضوع الدرس وأهدافه.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_1[]" value="243" id="rec_243_1" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_243_1" class="mr-2 text-sm text-gray-700">يجب أن تتنوّع في الخطّة طرائق التدريس وإستراتيجياته.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_1[]" value="244" id="rec_244_1" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_244_1" class="mr-2 text-sm text-gray-700">يجب أن يحدّد المعلم في الخطّة التكامل مع المواد الأخرى بشكل واضح ومناسب.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_1[]" value="245" id="rec_245_1" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_245_1" class="mr-2 text-sm text-gray-700">يجب أن يكون غلق الدرس في الخطّة مناسبا للموضوع والأهداف.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_1[]" value="246" id="rec_246_1" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_246_1" class="mr-2 text-sm text-gray-700">يجب اختيار طرائق تقييم مناسبة ومتنوعة في الخطّة.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_1[]" value="247" id="rec_247_1" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_247_1" class="mr-2 text-sm text-gray-700">يجب استخدام النموذج المحدث للخطة</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_1" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                                    <div class="indicator-block ">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">أهداف التعلم مناسبة ودقيقة الصياغة وقابلة للقياس.</label>
                        <select name="score_2" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_2[]" value="248" id="rec_248_2" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_248_2" class="mr-2 text-sm text-gray-700">يجب أن تكون الأهداف مرتبطة بموضوع الدرس بما يتضمنه من معارف ومهارات.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_2[]" value="249" id="rec_249_2" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_249_2" class="mr-2 text-sm text-gray-700">يجب أن تكون الأهداف مصاغة بطريقة إجرائية سليمة وواضحة.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_2[]" value="250" id="rec_250_2" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_250_2" class="mr-2 text-sm text-gray-700">يجب أن تراعي الأهداف التنوع بين المستويات المعرفية والمهارية.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_2[]" value="251" id="rec_251_2" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_251_2" class="mr-2 text-sm text-gray-700">يجب أن تكون الأهداف قابلة للقياس.</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_2" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                                    <div class="indicator-block ">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">أنشطة الدرس الرئيسة واضحة ومتدرجة ومرتبطة بالأهداف.</label>
                        <select name="score_3" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_3[]" value="252" id="rec_252_3" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_252_3" class="mr-2 text-sm text-gray-700">يجب أن تكون الأنشطة مرتبطة بأهداف الدرس وتساعد على تحقيقها.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_3[]" value="253" id="rec_253_3" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_253_3" class="mr-2 text-sm text-gray-700">يجب أن تراعي الأنشطة التدرج والتسلسل في تحقيق أهداف الدرس.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_3[]" value="254" id="rec_254_3" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_254_3" class="mr-2 text-sm text-gray-700">يجب أن توضّح الأنشطة الرئيسة دور كل من المعلم والطالب.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_3[]" value="255" id="rec_255_3" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_255_3" class="mr-2 text-sm text-gray-700">يجب إن تعزّز الأنشطة الرئيسة الكفايات والقيم الأساسية ضمن السياق المعرفي.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_3[]" value="256" id="rec_256_3" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_256_3" class="mr-2 text-sm text-gray-700">يجب أن تراعي الأنشطة بوضوح الفروق الفردية بين الطلبة.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_3[]" value="257" id="rec_257_3" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_257_3" class="mr-2 text-sm text-gray-700">يجب أن يكون التوزيع الزمني محدد ومناسب للأنشطة.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_3[]" value="258" id="rec_258_3" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_258_3" class="mr-2 text-sm text-gray-700">يجب توضيح آلية توظيف أدوات التكنولوجيا في دور المعلم و المتعلم.</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_3" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                            </div>
            
            <div class="flex justify-between mt-6">
                                    <div></div>
                                
                                    <button type="button" class="next-step bg-primary-600 text-white px-4 py-2 rounded hover:bg-primary-700" data-step="1">التالي</button>
                            </div>
        </div>
                    <div id="step-2" class="evaluation-section bg-white border border-gray-200 border-r-4 border-r-primary-600 rounded-lg p-6 mb-4 max-h-[70vh] overflow-y-auto scrollbar-thin" style="display: none;">
            <h2 class="text-xl font-bold text-primary-700 mb-6 pb-2 border-b border-gray-200">تنفيذ الدرس</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div class="indicator-block ">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">أهداف التعلم معروضة ويتم مناقشتها.</label>
                        <select name="score_4" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_4[]" value="259" id="rec_259_4" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_259_4" class="mr-2 text-sm text-gray-700">يجب أن يستعرض المعلم أهداف الدرس المخطط لها بداية درسه بصورة واضحة ومناسبة.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_4[]" value="260" id="rec_260_4" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_260_4" class="mr-2 text-sm text-gray-700">يجب التحقق من وضوح أهداف الدرس لدى الطلبة.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_4[]" value="261" id="rec_261_4" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_261_4" class="mr-2 text-sm text-gray-700">يجب أن تكون الأهداف كما هي مدونة في الخطة</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_4" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                                    <div class="indicator-block ">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">أنشطة التمهيد مفعلة بشكل مناسب.</label>
                        <select name="score_5" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_5[]" value="262" id="rec_262_5" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_262_5" class="mr-2 text-sm text-gray-700">يجب تنفيذ أنشطة التمهيد بطريقة جاذبة وشائقة.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_5[]" value="263" id="rec_263_5" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_263_5" class="mr-2 text-sm text-gray-700">يجب أن تكون أنشطة التمهيد ممهّدة للأنشطة الرئيسة وترتبط بها.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_5[]" value="264" id="rec_264_5" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_264_5" class="mr-2 text-sm text-gray-700">يجب تنفيذ أنشطة التمهيد ضمن الزمن المحدد لها.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_5[]" value="265" id="rec_265_5" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_265_5" class="mr-2 text-sm text-gray-700">يجب أن ترتبط أنشطة التمهيد بخبرات الطلبة الحياتية وتجاربهم السابقة.</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_5" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                                    <div class="indicator-block ">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">محتوى الدرس واضح والعرض منظّم ومترابط.</label>
                        <select name="score_6" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_6[]" value="266" id="rec_266_6" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_266_6" class="mr-2 text-sm text-gray-700">يجب أن يكون محتوى الدرس معروضا بطريقة واضحة.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_6[]" value="267" id="rec_267_6" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_267_6" class="mr-2 text-sm text-gray-700">يجب أن يكون المحتوى مقدّما بصورة متدرجة ومنظمة وبأمثلة كافية.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_6[]" value="268" id="rec_268_6" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_268_6" class="mr-2 text-sm text-gray-700">يجب أن تكون خطوات تنفيذ الدرس مترابطة ومتصلة بالأهداف.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_6[]" value="269" id="rec_269_6" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_269_6" class="mr-2 text-sm text-gray-700">يجب ارتباط المحتوى بالبيئة والخبرات الحياتية.</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_6" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                                    <div class="indicator-block ">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">طرائق التدريس وإستراتيجياته متنوعه وتتمحور حول الطالب.</label>
                        <select name="score_7" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_7[]" value="270" id="rec_270_7" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_270_7" class="mr-2 text-sm text-gray-700">يجب تطبيق إستراتيجيات تدريس تناسب أهداف الدرس وتراعي المتعلمين.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_7[]" value="271" id="rec_271_7" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_271_7" class="mr-2 text-sm text-gray-700">يجب تطبيق إستراتيجيات تدريس تراعي المتعلمين.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_7[]" value="272" id="rec_272_7" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_272_7" class="mr-2 text-sm text-gray-700">يجب تنفيذ الإستراتيجية بطريقة صحيحة، ووفق ما ورد في خطة الدرس.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_7[]" value="273" id="rec_273_7" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_273_7" class="mr-2 text-sm text-gray-700">يجب أن تكون الإستراتيجيات المنفذة متنوعة وتفعل دور الطالب في عملية التعلم.</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_7" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                                    <div class="indicator-block ">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">مصادر التعلم الرئيسة والمساندة موظّفة بصورة واضحة وسليمة.</label>
                        <select name="score_8" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_8[]" value="274" id="rec_274_8" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_274_8" class="mr-2 text-sm text-gray-700">يجب توظيف مصدر التعلم الرئيس بصورة واضحة وسليمة.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_8[]" value="275" id="rec_275_8" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_275_8" class="mr-2 text-sm text-gray-700">يجب توظيف مصادر مساندة ورقية تثري الدرس وتساعد على تحقيق أهدافه.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_8[]" value="276" id="rec_276_8" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_276_8" class="mr-2 text-sm text-gray-700">يجب نشر مصادر تعلم مساندة إلكترونية للمادة على نظام قطر للتعليم.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_8[]" value="277" id="rec_277_8" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_277_8" class="mr-2 text-sm text-gray-700">يجب الحرص على استخدام الطلبة مصادر التعلم أثناء الدرس.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_8[]" value="278" id="rec_278_8" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_278_8" class="mr-2 text-sm text-gray-700">يجب استخدام المصادر المتنوعة لمراعاة الفروق الفردية بين الطلبة.</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_8" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                                    <div class="indicator-block ">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">الوسائل التعليميّة والتكنولوجيا موظّفة بصورة مناسبة.</label>
                        <select name="score_9" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_9[]" value="279" id="rec_279_9" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_279_9" class="mr-2 text-sm text-gray-700">يجب استخدام وسائل تعليمية متنوعة وفعالة.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_9[]" value="280" id="rec_280_9" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_280_9" class="mr-2 text-sm text-gray-700">يجب توظيف التكنولوجيا بما يخدم الموقف التعليمي والأهداف.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_9[]" value="281" id="rec_281_9" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_281_9" class="mr-2 text-sm text-gray-700">يجب تنظيم العرض السبوري.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_9[]" value="282" id="rec_282_9" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_282_9" class="mr-2 text-sm text-gray-700">يجب تفعيل السبورة التفاعلية بما يخدم الموقف التعليمي.</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_9" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                                    <div class="indicator-block ">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">الأسئلة الصفية ذات صياغة سليمة ومتدرجة ومثيرة للتفكير.</label>
                        <select name="score_10" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_10[]" value="283" id="rec_283_10" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_283_10" class="mr-2 text-sm text-gray-700">يجب أن تكون الأسئلة واضحة وصياغتها سليمة.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_10[]" value="284" id="rec_284_10" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_284_10" class="mr-2 text-sm text-gray-700">يجب أن تكون الأسئلة متنوعة ومتدرجة في مستوياتها.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_10[]" value="285" id="rec_285_10" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_285_10" class="mr-2 text-sm text-gray-700">يجب أن تكون الأسئلة مثيرة لاهتمام الطلبة وتحثهم على المشاركة وطرح الأسئلة.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_10[]" value="286" id="rec_286_10" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_286_10" class="mr-2 text-sm text-gray-700">يجب أن تعزّز الأسئلة الحوار والمناقشة بين الطالب والمعلم والطلبة فيما بينهم.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_10[]" value="287" id="rec_287_10" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_287_10" class="mr-2 text-sm text-gray-700">يجب أن تكون الأسئلة مثيرة للتفكير والتحدي لدى الطلبة.</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_10" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                                    <div class="indicator-block ">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">المادة العلمية دقيقة و مناسبة.</label>
                        <select name="score_11" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_11[]" value="288" id="rec_288_11" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_288_11" class="mr-2 text-sm text-gray-700">يجب أن تكون المادة العلمية مرتبطة بأهداف الدرس.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_11[]" value="289" id="rec_289_11" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_289_11" class="mr-2 text-sm text-gray-700">يجب أن تكون المادة العلمية متوافقة مع مصدر التعلم.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_11[]" value="290" id="rec_290_11" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_290_11" class="mr-2 text-sm text-gray-700">يجب أن تكون المادة العلمية المقدمة صحيحة وسليمة وتخلو من الأخطاء العلمية واللغوية.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_11[]" value="291" id="rec_291_11" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_291_11" class="mr-2 text-sm text-gray-700">يجب وضوح المادة العلمية ومناسبة مفرداتها للمرحلة الدراسية.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_11[]" value="292" id="rec_292_11" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_292_11" class="mr-2 text-sm text-gray-700">يجب أن تكون المادة العلمية الإثرائية مستندة إلى مراجع موثوقة.</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_11" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                                    <div class="indicator-block ">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">الكفايات الأساسية متضمنة في السياق المعرفي للدرس.</label>
                        <select name="score_12" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_12[]" value="293" id="rec_293_12" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_293_12" class="mr-2 text-sm text-gray-700">يجب أن يوفّر المعلم أنشطة تمكّن الطلبة من اقتراح البدائل وإنتاج أفكار بطرائق مبتكرة.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_12[]" value="294" id="rec_294_12" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_294_12" class="mr-2 text-sm text-gray-700">يجب أن يوفّر المعلم أنشطة تنمّي مهارات الطلبة اللغوية لتوظيفها في التعبير عن الآراء و الأفكار.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_12[]" value="295" id="rec_295_12" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_295_12" class="mr-2 text-sm text-gray-700">يجب أن يوفّر المعلم أنشطة تنمّي مهارات الطلبة العددية لتوظيفها في مواقف متنوعة.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_12[]" value="296" id="rec_296_12" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_296_12" class="mr-2 text-sm text-gray-700">يجب أن يوفّر المعلم أنشطة تمكّن الطلبة من التواصل استماعاً وتحدثاً وكتابة، وتوظيف ذلك لأغراض مختلفة.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_12[]" value="297" id="rec_297_12" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_297_12" class="mr-2 text-sm text-gray-700">يجب أن يوفّر المعلم للطلبة أنشطة العمل التشاركي واحترام الذات، وتقبّل التغير الإيجابي.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_12[]" value="298" id="rec_298_12" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_298_12" class="mr-2 text-sm text-gray-700">يجب أن يوفّر المعلم للطلبة أنشطة الاهتمام بالتقصي و توظيف التكنولوجيا في إعداد البحوث و مشاركتها.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_12[]" value="299" id="rec_299_12" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_299_12" class="mr-2 text-sm text-gray-700">يجب أن يوفّر المعلم للطلبة أنشطة تحديد المشكلات و التعاون مع الاخرين في اقتراح الحلول.</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_12" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                                    <div class="indicator-block ">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">القيم الأساسية متضمنة في السياق المعرفي للدرس.</label>
                        <select name="score_13" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_13[]" value="300" id="rec_300_13" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_300_13" class="mr-2 text-sm text-gray-700">يجب أن يوفّر المعلم أنشطة تساهم في اعتزاز الطلبة باللغة العربية والتاريخ و التقاليد القطرية.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_13[]" value="301" id="rec_301_13" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_301_13" class="mr-2 text-sm text-gray-700">يجب أن يوفّر المعلم أنشطة احترام الطلبة للأخرين وتقديرهم لذواتهم.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_13[]" value="302" id="rec_302_13" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_302_13" class="mr-2 text-sm text-gray-700">يجب أن يوفّر المعلم أنشطة تعزّز ثقة الطلبة بقدرتهم على التعلم وبذل الجهد في ذلك.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_13[]" value="303" id="rec_303_13" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_303_13" class="mr-2 text-sm text-gray-700">يجب أن يوفّر المعلم أنشطة تحث الطلبة على الالتزام بحقوقهم وواجباتهم.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_13[]" value="304" id="rec_304_13" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_304_13" class="mr-2 text-sm text-gray-700">يجب أن يوفّر المعلم أنشطة لتطوير الطلبة أنماط حياتهم الصحّية.</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_13" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                                    <div class="indicator-block ">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">التكامل بين محاور المادة ومع المواد الأخرى يتم بشكل مناسب.</label>
                        <select name="score_14" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_14[]" value="305" id="rec_305_14" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_305_14" class="mr-2 text-sm text-gray-700">يجب أن يربط المعلّم بين محاور المادة ومهاراتها بصورة فاعلة.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_14[]" value="306" id="rec_306_14" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_306_14" class="mr-2 text-sm text-gray-700">يجب أن يوظف المعلّم التكامل مع المواد الأخرى لتحقيق النمو المعرفي عند الطلاب.</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_14" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                                    <div class="indicator-block ">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">الفروق الفردية بين الطلبة يتم مراعاتها.</label>
                        <select name="score_15" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_15[]" value="307" id="rec_307_15" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_307_15" class="mr-2 text-sm text-gray-700">يجب توزيع الطلبة بطريقة مناسبة وفق مستوياتهم والنشاط المنفذ.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_15[]" value="308" id="rec_308_15" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_308_15" class="mr-2 text-sm text-gray-700">يجب تقديم أنشطة وتدريبات تراعي الفروق الفردية.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_15[]" value="309" id="rec_309_15" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_309_15" class="mr-2 text-sm text-gray-700">يجب تقديم أنشطة وتدريبات تراعي أنماط التعلم (سمعي، بصري، حركي...).</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_15[]" value="310" id="rec_310_15" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_310_15" class="mr-2 text-sm text-gray-700">يجب أن يتابع معلم الصف المواد التي يقدمها معلم الدعم للطلبة.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_15[]" value="311" id="rec_311_15" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_311_15" class="mr-2 text-sm text-gray-700">يجب تقديم التسهيلات والترتيبات اللازمة لطلاب الدعم.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_15[]" value="312" id="rec_312_15" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_312_15" class="mr-2 text-sm text-gray-700">يجب توظيف التكنولوجيا بما يراعي الفروق الفردية وطلاب الدعم.</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_15" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                                    <div class="indicator-block ">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">غلق الدرس يتم بشكل مناسب.</label>
                        <select name="score_16" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_16[]" value="313" id="rec_313_16" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_313_16" class="mr-2 text-sm text-gray-700">يجب أن يكون غلق الدرس مناسبا وشاملا.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_16[]" value="314" id="rec_314_16" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_314_16" class="mr-2 text-sm text-gray-700">يجب أن يعكس الغلق تحقق أهداف الدرس.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_16[]" value="315" id="rec_315_16" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_315_16" class="mr-2 text-sm text-gray-700">يجب أن يكون الدور الأكبر في الغلق للطلبة.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_16[]" value="316" id="rec_316_16" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_316_16" class="mr-2 text-sm text-gray-700">يجب تنفيذ الغلق في الزمن المحدد له.</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_16" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                            </div>
            
            <div class="flex justify-between mt-6">
                                    <button type="button" class="prev-step bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600" data-step="2">السابق</button>
                                
                                    <button type="button" class="next-step bg-primary-600 text-white px-4 py-2 rounded hover:bg-primary-700" data-step="2">التالي</button>
                            </div>
        </div>
                    <div id="step-3" class="evaluation-section bg-white border border-gray-200 border-r-4 border-r-primary-600 rounded-lg p-6 mb-4 max-h-[70vh] overflow-y-auto scrollbar-thin" style="display: none;">
            <h2 class="text-xl font-bold text-primary-700 mb-6 pb-2 border-b border-gray-200">التقويم</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div class="indicator-block ">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">أساليب التقويم (القبلي والبنائي والختامي) مناسبة ومتنوعة.</label>
                        <select name="score_17" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_17[]" value="317" id="rec_317_17" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_317_17" class="mr-2 text-sm text-gray-700">يجب أن يشمل التقويم جميع الأهداف المخطط لتحقيقها.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_17[]" value="318" id="rec_318_17" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_318_17" class="mr-2 text-sm text-gray-700">يجب التنويع في أساليب التقويم مراعاةً للفروق الفردية بين الطلبة.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_17[]" value="319" id="rec_319_17" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_319_17" class="mr-2 text-sm text-gray-700">يجب التنويع في استخدام أدوات التقويم بما يناسب الموقف التعليمي (ملاحظة المعلم ــ تقييم الذات ــ اختبارات ذهنية وشفوية ــ الأسئلة الشفوية – تطبيق إلكتروني – مناقشة إلكترونية ...).</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_17[]" value="320" id="rec_320_17" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_320_17" class="mr-2 text-sm text-gray-700">يجب أن تكون عملية التقويم مستمرة قبل الدرس وأثناءه وبعده (قبلي– بنائي – ختامي).</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_17[]" value="321" id="rec_321_17" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_321_17" class="mr-2 text-sm text-gray-700">يجب التنوع في أنماط ومستويات الأسئلة المدرجة في التقييمات وأوراق العمل الإلكترونية.</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_17" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                                    <div class="indicator-block ">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">التغذية الراجعة متنوعة ومستمرة.</label>
                        <select name="score_18" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_18[]" value="322" id="rec_322_18" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_322_18" class="mr-2 text-sm text-gray-700">يجب تنويع أساليب التغذية الراجعة بما يناسب المتعلمين (فورية مؤجلة – لفظية مكتوبة ...).</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_18[]" value="323" id="rec_323_18" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_323_18" class="mr-2 text-sm text-gray-700">يجب شمولية التغذية الراجعة واستمراريتها، بحيث تشمل جميع مراحل الدرس، وجميع المتعلمين على اختلاف مستوياتهم التحصيلية والعقلية والعمرية.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_18[]" value="324" id="rec_324_18" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_324_18" class="mr-2 text-sm text-gray-700">يجب تقييم إجابات الطلبة (الصحيحة والخاطئة) ومناقشتها، وربط إجاباتهم بمعارفهم السابقة.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_18[]" value="325" id="rec_325_18" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_325_18" class="mr-2 text-sm text-gray-700">يجب تشجيع الطلبة على تقديم التفسيرات والشروح المنطقية، ودعم إجاباتهم وأقوالهم بنصوص أو أمثلة أو بيانات.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_18[]" value="326" id="rec_326_18" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_326_18" class="mr-2 text-sm text-gray-700">يجب تحفيز الطلبة على تقييم استجابات بعضهم بعضا.</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_18" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                                    <div class="indicator-block ">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">أعمال الطلبة متابعة ومصححة بدقة ورقيًا وإلكترونيًا.</label>
                        <select name="score_19" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_19[]" value="327" id="rec_327_19" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_327_19" class="mr-2 text-sm text-gray-700">يجب تقديم التعليمات اللازمة لإنجاز الطلبة للأعمال الكتابية بدقة ووضوح والتأكد من فهمها.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_19[]" value="328" id="rec_328_19" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_328_19" class="mr-2 text-sm text-gray-700">يجب متابعة أعمال الطلبة بشكل دوري وتقويمها، سواء أكانت ورقية أم إلكترونية.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_19[]" value="329" id="rec_329_19" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_329_19" class="mr-2 text-sm text-gray-700">يجب تحري الدقة في تصحيح الأعمال التحريرية وتوجيه التغذية الراجعة المناسبة.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_19[]" value="330" id="rec_330_19" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_330_19" class="mr-2 text-sm text-gray-700">يجب إعلان الواجبات والاختبارات الورقية أو الإلكترونية للطلاب وأولياء الأمور بشكل دوري.</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_19" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                            </div>
            
            <div class="flex justify-between mt-6">
                                    <button type="button" class="prev-step bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600" data-step="3">السابق</button>
                                
                                    <button type="button" class="next-step bg-primary-600 text-white px-4 py-2 rounded hover:bg-primary-700" data-step="3">التالي</button>
                            </div>
        </div>
                    <div id="step-4" class="evaluation-section bg-white border border-gray-200 border-r-4 border-r-primary-600 rounded-lg p-6 mb-4 max-h-[70vh] overflow-y-auto scrollbar-thin" style="display: none;">
            <h2 class="text-xl font-bold text-primary-700 mb-6 pb-2 border-b border-gray-200">الإدارة الصفية وبيئة التعلم</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div class="indicator-block ">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">البيئة الصفية إيجابية وآمنة وداعمة للتعلّم.</label>
                        <select name="score_20" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_20[]" value="331" id="rec_331_20" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_331_20" class="mr-2 text-sm text-gray-700">يجب أن يكون مكان الدرس جاهزا للتعليم والتعلم (نظافة – ترتيب ــ تهوية ــ إضاءة...).</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_20[]" value="332" id="rec_332_20" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_332_20" class="mr-2 text-sm text-gray-700">يجب توجيه الطلبة إلى مراعاة قواعد الأمن والسلامة في الصف والمختبر ومعامل الحاسب.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_20[]" value="333" id="rec_333_20" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_333_20" class="mr-2 text-sm text-gray-700">يجب أن تكون طريقة جلوس الطلاب منظمة وتسهل التواصل والتعلم داخل الصف.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_20[]" value="334" id="rec_334_20" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_334_20" class="mr-2 text-sm text-gray-700">يجب أن تكون أعمال الطلبة معروضة ومحدثة داخل الصف.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_20[]" value="335" id="rec_335_20" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_335_20" class="mr-2 text-sm text-gray-700">يجب أن تكون أعمال الطلبة معروضة ومحدثة على نظام قطر للتعليم.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_20[]" value="336" id="rec_336_20" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_336_20" class="mr-2 text-sm text-gray-700">يجب بناء علاقات إيجابية وبناءة قائمة على الثقة والاحترام المتبادل بين الطالب والمعلم.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_20[]" value="337" id="rec_337_20" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_337_20" class="mr-2 text-sm text-gray-700">يجب تشجيع بناء علاقات إيجابية قائمة على الاحترام المتبادل والتعاون بين الطلاب.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_20[]" value="338" id="rec_338_20" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_338_20" class="mr-2 text-sm text-gray-700">يجب إثارة دافعية الطلبة للمشاركة في أنشطة التعلم بفاعلية.</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_20" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                                    <div class="indicator-block ">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">إدارة أنشطة التعلّم والمشاركات الصّفيّة تتم بصورة منظمة.</label>
                        <select name="score_21" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_21[]" value="339" id="rec_339_21" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_339_21" class="mr-2 text-sm text-gray-700">يجب تنظيم مشاركات الطلبة ومناقشاتهم الصفية.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_21[]" value="340" id="rec_340_21" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_340_21" class="mr-2 text-sm text-gray-700">يجب توجيه تعليمات واضحة ومحددة قبل بدء النشاط وأثناء تنفيذه.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_21[]" value="341" id="rec_341_21" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_341_21" class="mr-2 text-sm text-gray-700">يجب متابعة استجابة الطلبة للتوجيهات وتنفيذها.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_21[]" value="342" id="rec_342_21" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_342_21" class="mr-2 text-sm text-gray-700">يجب أن تسهم حركة المعلم بين الطلبة أثناء تنفيذ الأنشطة في متابعة الطلبة وتقديم الدعم المناسب لهم.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_21[]" value="343" id="rec_343_21" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_343_21" class="mr-2 text-sm text-gray-700">يجب إعطاء الفرصة للطلاب في التفكير في الحل</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_21" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                                    <div class="indicator-block ">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">قوانين إدارة الصف وإدارة السلوك مفعّلة.</label>
                        <select name="score_22" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_22[]" value="344" id="rec_344_22" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_344_22" class="mr-2 text-sm text-gray-700">يجب أن تكون القوانين الصفية ثابتة وواضحة ويعي الطلبة ما يترتب عليها من إجراءات في حالة المخالفة.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_22[]" value="345" id="rec_345_22" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_345_22" class="mr-2 text-sm text-gray-700">يجب استخدام وسائل وأساليب تربوية متنوعة لتعزيز السلوكيات الإيجابية.</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_22[]" value="346" id="rec_346_22" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_346_22" class="mr-2 text-sm text-gray-700">يجب استخدام وسائل وأساليب تربوية متنوعة لتقويم السلوكيات غير المرغوبة.</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_22" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                                    <div class="indicator-block ">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">الاستثمار الأمثل لزمن الحصة.</label>
                        <select name="score_23" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_23[]" value="347" id="rec_347_23" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_347_23" class="mr-2 text-sm text-gray-700">يجب مراعاة الوقت الكافي والمخصص لكل مراحل الدرس (التهيئة ــ العرض –الغلق).</label>
                                </div>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_23[]" value="348" id="rec_348_23" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_348_23" class="mr-2 text-sm text-gray-700">يجب استخدام وسائل مختلفة لضمان الالتزام بالزمن المحدد للأنشطة (مثل المؤقت أو العد التنازلي ــ الخ ...).</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_23" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                            </div>
            
            <div class="flex justify-between mt-6">
                                    <button type="button" class="prev-step bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600" data-step="4">السابق</button>
                                
                                    <button type="button" class="next-step bg-primary-600 text-white px-4 py-2 rounded hover:bg-primary-700" data-step="4">التالي</button>
                            </div>
        </div>
                    <div id="step-5" class="evaluation-section bg-white border border-gray-200 border-r-4 border-r-primary-600 rounded-lg p-6 mb-4 max-h-[70vh] overflow-y-auto scrollbar-thin" style="display: none;">
            <h2 class="text-xl font-bold text-primary-700 mb-6 pb-2 border-b border-gray-200">جزء خاص بمادة العلوم (النشاط العملي)</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div class="indicator-block lab-indicator">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">مدى صلاحية و توافر الأدوات اللازمة لتنفيذ النشاط العملي وبكميات مناسبة.</label>
                        <select name="score_24" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_24[]" value="349" id="rec_349_24" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_349_24" class="mr-2 text-sm text-gray-700">يجب التأكد من صلاحية الأدوات وتوافرها بالكميات الكافية لجميع الطلاب لتنفيذ التجربة دون عوائق.</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_24" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                                    <div class="indicator-block lab-indicator">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">شرح اجراءات الأمن والسلامة المناسبة للتجربة ومتابعة تفعيلها.</label>
                        <select name="score_25" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_25[]" value="350" id="rec_350_25" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_350_25" class="mr-2 text-sm text-gray-700">يجب شرح إجراءات الأمن والسلامة بوضوح، ومتابعة تنفيذها من قبل الطلاب أثناء التجربة.</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_25" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                                    <div class="indicator-block lab-indicator">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">اعطاء تعليمات واضحة وسليمة لأداء النشاط العملي قبل وأثناء التنفيذ.</label>
                        <select name="score_26" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_26[]" value="351" id="rec_351_26" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_351_26" class="mr-2 text-sm text-gray-700">يجب أن تكون التعليمات دقيقة ومبسطة، وتُعطى في الوقت المناسب قبل وأثناء النشاط العملي.</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_26" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                                    <div class="indicator-block lab-indicator">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">تسجيل الطلبة للملاحظات والنتائج أثناء تنفيذ النشاط العملي.</label>
                        <select name="score_27" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_27[]" value="352" id="rec_352_27" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_352_27" class="mr-2 text-sm text-gray-700">يجب تدريب الطلبة على تدوين الملاحظات والنتائج بشكل منظم أثناء أداء النشاط، لتعزيز مهارات الملاحظة والتوثيق.</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_27" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                                    <div class="indicator-block lab-indicator">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">تقويم مهارات الطلبة أثناء تنفيذ النشاط العملي.</label>
                        <select name="score_28" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_28[]" value="353" id="rec_353_28" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_353_28" class="mr-2 text-sm text-gray-700">يجب أن يتم تقويم أداء الطلبة بشكل فردي أو جماعي أثناء التجربة، ومراعاة تطبيق المهارات العلمية والعملية.</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_28" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                                    <div class="indicator-block lab-indicator">
                        <label class="block text-gray-700 font-medium mb-2 pr-3 border-r-2 border-primary-500">تنويع أساليب تقديم التغذية الراجعة للطلبة لتنمية مهاراتهم.</label>
                        <select name="score_29" class="w-full border p-2 rounded mb-2">
                            <option value="0">لم يتم قياسه</option>
                            <option value="4">الأدلة مستكملة</option>
                            <option value="3">معظم الأدلة</option>
                            <option value="2">بعض الأدلة</option>
                            <option value="1">الأدلة غير متوفرة</option>
                        </select>
                        
                                                    <div class="mb-2 bg-gray-50 p-3 border rounded">
                                <p class="text-sm font-semibold mb-2 text-gray-700">التوصيات المقترحة:</p>
                                                                <div class="flex items-center mb-1">
                                    <input type="checkbox" name="recommend_29[]" value="354" id="rec_354_29" class="form-checkbox h-4 w-4 text-primary-600">
                                    <label for="rec_354_29" class="mr-2 text-sm text-gray-700">يجب استخدام أساليب متنوعة للتغذية الراجعة (شفهية، كتابية، آنية أو مؤجلة) لمساعدة الطلبة على تحسين أدائهم وتطوير مهاراتهم.</label>
                                </div>
                                                            </div>
                                                
                        <input type="text" name="custom_recommend_29" placeholder="أدخل توصية مخصصة (اختياري)" class="w-full border p-2 rounded">
                    </div>
                            </div>
            
            <div class="flex justify-between mt-6">
                                    <button type="button" class="prev-step bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600" data-step="5">السابق</button>
                                
                                    <button type="button" class="notes-to-final-result bg-primary-600 text-white px-4 py-2 rounded hover:bg-primary-700">عرض النتيجة النهائية</button>
                            </div>
        </div>
            
    <!-- قسم الملاحظات والتوصيات -->
    <div id="step-6" class="evaluation-section bg-white border border-gray-200 border-r-4 border-r-primary-600 rounded-lg p-6 mb-4" style="display: none;">
        <h2 class="text-xl font-bold text-primary-700 mb-6 pb-2 border-b border-gray-200">ملاحظات وتوصيات عامة</h2>
        
        <div class="space-y-6">
            <div>
                <label class="block mb-3 font-semibold text-gray-700">أوصي بـ:</label>
                <textarea id="recommendation-notes" name="recommendation_notes" class="w-full border-2 border-gray-300 p-4 rounded-lg h-32 resize-none" placeholder="أدخل التوصيات هنا..."></textarea>
            </div>
            
            <div>
                <label class="block mb-3 font-semibold text-gray-700">أشكر المعلم على:</label>
                <textarea id="appreciation-notes" name="appreciation_notes" class="w-full border-2 border-gray-300 p-4 rounded-lg h-32 resize-none" placeholder="أدخل نقاط الشكر والتقدير هنا..."></textarea>
            </div>
        </div>
        
        <div class="flex justify-between mt-6">
            <button type="button" class="prev-step bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600" data-step="6">السابق</button>
            <button type="button" class="notes-to-final-result bg-primary-600 text-white px-4 py-2 rounded hover:bg-primary-700">عرض النتيجة النهائية</button>
        </div>
    </div>
    
    <!-- قسم النتيجة النهائية -->
    <div id="step-7" class="evaluation-section bg-white border border-gray-200 border-r-4 border-r-primary-600 rounded-lg p-6 mb-4" style="display: none;">
        <h2 class="text-xl font-bold text-primary-700 mb-6 pb-2 border-b border-gray-200">نتيجة التقييم النهائية</h2>
        
        <div class="grid grid-cols-1 gap-6">
            <div id="total-score" class="text-xl font-bold p-4 bg-gray-50 rounded-lg border border-gray-200"></div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-3 text-gray-700">نقاط القوة:</h3>
                    <ul id="strengths" class="list-disc list-inside space-y-2 text-gray-600"></ul>
                </div>
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-3 text-gray-700">نقاط تحتاج إلى تحسين:</h3>
                    <ul id="improvements" class="list-disc list-inside space-y-2 text-gray-600"></ul>
                </div>
            </div>

            <div id="recommendation-notes-display" class="bg-white p-4 rounded-lg border border-gray-200" style="display: none;">
                <h3 class="text-lg font-semibold mb-3 text-gray-700">أوصي بـ:</h3>
                <p class="text-gray-600 whitespace-pre-line"></p>
            </div>
            
            <div id="appreciation-notes-display" class="bg-white p-4 rounded-lg border border-gray-200" style="display: none;">
                <h3 class="text-lg font-semibold mb-3 text-gray-700">أشكر المعلم على:</h3>
                <p class="text-gray-600 whitespace-pre-line"></p>
            </div>
        </div>

        <div class="flex justify-between mt-6">
            <button type="button" class="prev-step bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600" data-step="7">العودة</button>
            <button type="submit" name="save_visit" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">حفظ التقييم</button>
        </div>
    </div>
    
    <!-- حقول مخفية لتخزين البيانات الضرورية -->
    <input type="hidden" name="school_id" id="hidden-school-id">
    <input type="hidden" name="level_id" id="hidden-level-id">
    <input type="hidden" name="grade_id" id="hidden-grade-id">
    <input type="hidden" name="section_id" id="hidden-section-id">
    <input type="hidden" name="subject_id" id="hidden-subject-id">
    <input type="hidden" name="teacher_id" id="hidden-teacher-id">
    <input type="hidden" name="visitor_type_id" id="hidden-visitor-type-id">
    <input type="hidden" name="visitor_person_id" id="hidden-visitor-person-id">
    <input type="hidden" name="visit_date" id="hidden-visit-date">
    <input type="hidden" name="visit_type" id="hidden-visit-type">
    <input type="hidden" name="attendance_type" id="hidden-attendance-type">
    <input type="hidden" name="has_lab" id="hidden-has-lab" value="0">
    <input type="hidden" name="total_score" id="hidden-total-score">
    <input type="hidden" name="average_score" id="hidden-average-score">
    <input type="hidden" name="grade" id="hidden-grade">
    </form>
</div>

<script>
// متغيرات عامة للتقييم
let indicators = [{"id":24,"domain_id":5,"name":"\u0645\u062f\u0649 \u0635\u0644\u0627\u062d\u064a\u0629 \u0648 \u062a\u0648\u0627\u0641\u0631 \u0627\u0644\u0623\u062f\u0648\u0627\u062a \u0627\u0644\u0644\u0627\u0632\u0645\u0629 \u0644\u062a\u0646\u0641\u064a\u0630 \u0627\u0644\u0646\u0634\u0627\u0637 \u0627\u0644\u0639\u0645\u0644\u064a \u0648\u0628\u0643\u0645\u064a\u0627\u062a \u0645\u0646\u0627\u0633\u0628\u0629.","description":null,"weight":"0.00","created_at":"2025-05-16 11:25:23","updated_at":"2025-05-16 12:28:22"},{"id":25,"domain_id":5,"name":"\u0634\u0631\u062d \u0627\u062c\u0631\u0627\u0621\u0627\u062a \u0627\u0644\u0623\u0645\u0646 \u0648\u0627\u0644\u0633\u0644\u0627\u0645\u0629 \u0627\u0644\u0645\u0646\u0627\u0633\u0628\u0629 \u0644\u0644\u062a\u062c\u0631\u0628\u0629 \u0648\u0645\u062a\u0627\u0628\u0639\u0629 \u062a\u0641\u0639\u064a\u0644\u0647\u0627.","description":null,"weight":"0.00","created_at":"2025-05-16 11:25:23","updated_at":"2025-05-16 12:28:25"},{"id":26,"domain_id":5,"name":"\u0627\u0639\u0637\u0627\u0621 \u062a\u0639\u0644\u064a\u0645\u0627\u062a \u0648\u0627\u0636\u062d\u0629 \u0648\u0633\u0644\u064a\u0645\u0629 \u0644\u0623\u062f\u0627\u0621 \u0627\u0644\u0646\u0634\u0627\u0637 \u0627\u0644\u0639\u0645\u0644\u064a \u0642\u0628\u0644 \u0648\u0623\u062b\u0646\u0627\u0621 \u0627\u0644\u062a\u0646\u0641\u064a\u0630.","description":null,"weight":"0.00","created_at":"2025-05-16 11:25:23","updated_at":"2025-05-16 12:28:29"},{"id":27,"domain_id":5,"name":"\u062a\u0633\u062c\u064a\u0644 \u0627\u0644\u0637\u0644\u0628\u0629 \u0644\u0644\u0645\u0644\u0627\u062d\u0638\u0627\u062a \u0648\u0627\u0644\u0646\u062a\u0627\u0626\u062c \u0623\u062b\u0646\u0627\u0621 \u062a\u0646\u0641\u064a\u0630 \u0627\u0644\u0646\u0634\u0627\u0637 \u0627\u0644\u0639\u0645\u0644\u064a.","description":null,"weight":"0.00","created_at":"2025-05-16 11:25:23","updated_at":"2025-05-16 12:28:32"},{"id":28,"domain_id":5,"name":"\u062a\u0642\u0648\u064a\u0645 \u0645\u0647\u0627\u0631\u0627\u062a \u0627\u0644\u0637\u0644\u0628\u0629 \u0623\u062b\u0646\u0627\u0621 \u062a\u0646\u0641\u064a\u0630 \u0627\u0644\u0646\u0634\u0627\u0637 \u0627\u0644\u0639\u0645\u0644\u064a.","description":null,"weight":"0.00","created_at":"2025-05-16 11:25:23","updated_at":"2025-05-16 12:28:35"},{"id":29,"domain_id":5,"name":"\u062a\u0646\u0648\u064a\u0639 \u0623\u0633\u0627\u0644\u064a\u0628 \u062a\u0642\u062f\u064a\u0645 \u0627\u0644\u062a\u063a\u0630\u064a\u0629 \u0627\u0644\u0631\u0627\u062c\u0639\u0629 \u0644\u0644\u0637\u0644\u0628\u0629 \u0644\u062a\u0646\u0645\u064a\u0629 \u0645\u0647\u0627\u0631\u0627\u062a\u0647\u0645.","description":null,"weight":"0.00","created_at":"2025-05-16 11:25:23","updated_at":"2025-05-16 12:28:38"}];
let currentStep = 1;
let isPartialEvaluation = false;

// إضافة متغير للتحكم بظهور مؤشرات المعمل
let hasLab = false;

// قائمة المؤشرات التفصيلية (لاستخدامها عند عرض النتائج)
const indicatorsDetails = [
  "خطة الدرس متوفرة وبنودها مستكملة ومناسبة.",
  "أهداف التعلم مناسبة ودقيقة الصياغة وقابلة للقياس.",
  "أنشطة الدرس الرئيسة واضحة ومتدرجة ومرتبطة بالأهداف.",
  "أهداف التعلم معروضة ويتم مناقشتها .",
  "أنشطة التمهيد مفعلة بشكل مناسب.",
  "محتوى الدرس واضح والعرض منظّم ومترابط.",
  "طرائق التدريس وإستراتيجياته متنوعه وتتمحور حول الطالب.",
  "مصادر التعلم الرئيسة والمساندة موظّفة بصورة واضحة وسليمة.",
  "الوسائل التعليميّة والتكنولوجيا موظّفة بصورة مناسبة.",
  "الأسئلة الصفية ذات صياغة سليمة ومتدرجة ومثيرة للتفكير .",
  "المادة العلمية دقيقة و مناسبة.",
  "الكفايات الأساسية متضمنة في السياق المعرفي للدرس.",
  "القيم الأساسية متضمنة في السياق المعرفي للدرس.",
  "التكامل بين محاور المادة ومع المواد الأخرى يتم بشكل مناسب.",
  "الفروق الفردية بين الطلبة يتم مراعاتها.",
  "غلق الدرس يتم بشكل مناسب.",
  "أساليب التقويم ( القبلي والبنائي والختامي ) مناسبة ومتنوعة.",
  "التغذية الراجعة متنوعة ومستمرة",
  "أعمال الطلبة متابعة ومصححة بدقة ورقيًا وإلكترونيًا .",
  "البيئة الصفية إيجابية وآمنة وداعمة للتعلّم.",
  "إدارة أنشطة التعلّم والمشاركات الصّفيّة تتم بصورة منظمة.",
  "قوانين إدارة الصف وإدارة السلوك مفعّلة.",
  "الاستثمار الأمثل لزمن الحصة",
  "مدى صلاحية وتوافر الأدوات اللازمة لتنفيذ النشاط العملي.",
  "شرح إجراءات الأمن والسلامة المناسبة للتجربة ومتابعة تفعيلها.",
  "إعطاء تعليمات واضحة وسليمة لأداء النشاط العملي قبل وأثناء التنفيذ.",
  "تسجيل الطلبة للملاحظات والنتائج أثناء تنفيذ النشاط العملي.",
  "تقويم مهارات الطلبة أثناء تنفيذ النشاط العملي.",
  "تنويع أساليب تقديم التغذية الراجعة للطلبة لتنمية مهاراتهم."
];

// دالة التحقق من صحة النموذج
function validateForm() {
    const requiredFields = ['visitor-type', 'grade', 'section', 'subject', 'teacher', 'visit-date'];
    let isValid = true;

    requiredFields.forEach(field => {
        const element = document.getElementById(field);
        if (!element.value) {
            element.classList.add('border-red-500');
            isValid = false;
        } else {
            element.classList.remove('border-red-500');
        }
    });
    
    // التحقق من اختيار الزائر الشخصي
    const visitorPersonId = document.getElementById('visitor-person-id').value;
    const visitorPersonElement = document.getElementById('visitor-person');
    
    if (visitorPersonElement && !visitorPersonId) {
        visitorPersonElement.classList.add('border-red-500');
        isValid = false;
    } else if (visitorPersonElement) {
        visitorPersonElement.classList.remove('border-red-500');
    }

    if (!isValid) {
        alert('يرجى ملء جميع الحقول المطلوبة');
        return false;
    }

    return isValid;
}

// عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    // وضع التاريخ الحالي كقيمة افتراضية
    document.getElementById('visit-date').value = new Date().toISOString().split('T')[0];
    
    // أزرار التنقل بين خطوات التقييم
    document.querySelectorAll('.next-step').forEach(button => {
        button.addEventListener('click', function() {
            const step = parseInt(this.getAttribute('data-step'));
            showStep(step + 1);
        });
    });
    
    document.querySelectorAll('.prev-step').forEach(button => {
        button.addEventListener('click', function() {
            const step = parseInt(this.getAttribute('data-step'));
            showStep(step - 1);
        });
    });
    
    // زر العودة إلى اختيار المعلم والمدرسة
    document.getElementById('back-to-selection').addEventListener('click', function() {
        document.getElementById('selection-form').style.display = 'block';
        document.getElementById('evaluation-form').style.display = 'none';
    });
    
    // عند ضغط زر بدء التقييم
    document.getElementById('start-evaluation-btn').addEventListener('click', function(e) {
        e.preventDefault();
        if (validateForm()) {
            // نقل المعلومات من نموذج الاختيار إلى نموذج التقييم
            const schoolId = document.getElementById('school').value;
            const gradeId = document.getElementById('grade').value;
            const sectionId = document.getElementById('section').value;
            const levelId = document.querySelector(`#grade option[value="${gradeId}"]`).getAttribute('data-level-id');
            const subjectId = document.getElementById('subject').value;
            const teacherId = document.getElementById('teacher').value;
            const visitorTypeId = document.getElementById('visitor-type').value;
            const visitorPersonId = document.getElementById('visitor-person-id').value;
            const visitDate = document.getElementById('visit-date').value;
            const visitType = document.getElementById('visit-type').value;
            const attendanceType = document.getElementById('attendance-type').value;
            
            // نقل القيم إلى الحقول المخفية
            document.getElementById('hidden-school-id').value = schoolId;
            document.getElementById('hidden-level-id').value = levelId;
            document.getElementById('hidden-grade-id').value = gradeId;
            document.getElementById('hidden-section-id').value = sectionId;
            document.getElementById('hidden-subject-id').value = subjectId;
            document.getElementById('hidden-teacher-id').value = teacherId;
            document.getElementById('hidden-visitor-type-id').value = visitorTypeId;
            document.getElementById('hidden-visitor-person-id').value = visitorPersonId;
            document.getElementById('hidden-visit-date').value = visitDate;
            document.getElementById('hidden-visit-type').value = visitType;
            document.getElementById('hidden-attendance-type').value = attendanceType;
            
            // تحديث معلومات نوع التقييم
            isPartialEvaluation = (visitType === 'partial');

            // تحديث قيمة خيار المعمل
            hasLab = document.getElementById('has-lab').checked;
            document.getElementById('hidden-has-lab').value = hasLab ? '1' : '0';

            // التحكم بظهور مؤشرات المعمل
            toggleLabIndicators();
            
            // تحديث عنوان التقييم
            const schoolName = document.querySelector(`#school option[value="${schoolId}"]`)?.textContent || '';
            const gradeName = document.querySelector(`#grade option[value="${gradeId}"]`)?.textContent || '';
            const sectionName = document.querySelector(`#section option[value="${sectionId}"]`)?.textContent || '';
            const subjectName = document.querySelector(`#subject option[value="${subjectId}"]`)?.textContent || '';
            const teacherName = document.querySelector(`#teacher option[value="${teacherId}"]`)?.textContent || '';
            const visitTypeName = document.querySelector(`#visit-type option[value="${visitType}"]`)?.textContent || '';
            
            const headerHtml = `
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="font-semibold">المعلم: <span class="font-normal">${teacherName}</span></p>
                        <p class="font-semibold">المادة: <span class="font-normal">${subjectName}</span></p>
                    </div>
                    <div>
                        <p class="font-semibold">الصف: <span class="font-normal">${gradeName}</span></p>
                        <p class="font-semibold">الشعبة: <span class="font-normal">${sectionName}</span></p>
                    </div>
                    <div>
                        <p class="font-semibold">نوع التقييم: <span class="font-normal">${visitTypeName}</span></p>
                        <p class="font-semibold">تاريخ الزيارة: <span class="font-normal">${formatDate(visitDate)}</span></p>
                    </div>
                </div>
            `;
            document.getElementById('evaluation-header').innerHTML = headerHtml;
            
            // إخفاء نموذج الاختيار وإظهار نموذج التقييم
            document.getElementById('selection-form').style.display = 'none';
            document.getElementById('evaluation-form').style.display = 'block';
            
            // تحميل معلومات الزيارات السابقة
            loadPreviousVisitsInfo(teacherId, visitorPersonId);
            
            // إظهار الخطوة الأولى
            showStep(1);
        }
    });
    
    // زر عرض النتيجة النهائية - تعديل ليعرض حقلي التوصيات والشكر قبل النتيجة النهائية
    document.querySelectorAll('.notes-to-final-result').forEach(button => {
        button.addEventListener('click', function() {
            // هنا نعرض صفحة الملاحظات والتوصيات أولاً (قبل الأخيرة)
            const totalSteps = document.querySelectorAll('.evaluation-section').length;
            const notesStep = totalSteps - 1; // الخطوة قبل الأخيرة (ملاحظات وتوصيات)
            showStep(notesStep);
        });
    });
    
    // إضافة زر لعرض النتيجة النهائية من صفحة الملاحظات والتوصيات
    const finalResultButtons = document.querySelectorAll('.notes-to-final-result');
    finalResultButtons.forEach(button => {
        button.addEventListener('click', function() {
            calculateAndShowFinalResult();
            const totalSteps = document.querySelectorAll('.evaluation-section').length;
            showStep(totalSteps); // الخطوة الأخيرة (النتيجة النهائية)
        });
    });
    
    // تعيين قيمة نوع التقييم
    document.getElementById('visit-type').addEventListener('change', function() {
        isPartialEvaluation = this.value === 'partial';
    });
    
    // تحديث اسم الزائر عند اختيار نوع الزائر
    document.getElementById('visitor-type').addEventListener('change', updateVisitorName);

    // عند تغيير حالة اختيار المعمل
    document.getElementById('has-lab').addEventListener('change', function() {
        hasLab = this.checked;
        document.getElementById('hidden-has-lab').value = hasLab ? '1' : '0';
        
        // التحكم بظهور مؤشرات المعمل
        toggleLabIndicators();
    });

    // تحميل المعلمين عند اختيار المادة
    document.getElementById('subject').addEventListener('change', loadTeachers);

    // تحميل الشعب عند اختيار الصف
    document.getElementById('grade').addEventListener('change', function() {
        loadSections(this.value);
    });
});

// تحديث اسم الزائر عند اختيار نوع الزائر
function updateVisitorName() {
    const visitorTypeSelect = document.getElementById('visitor-type');
    const visitorNameDiv = document.getElementById('visitor-name');
    const visitorPersonIdInput = document.getElementById('visitor-person-id');
    const subjectSelect = document.getElementById('subject');
    
    if (visitorTypeSelect.value) {
        // إرسال طلب AJAX للحصول على قائمة الزوار حسب النوع المختار
        fetch(`includes/get_visitors.php?type_id=${visitorTypeSelect.value}`)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    // إنشاء قائمة منسدلة للزوار
                    let select = document.createElement('select');
                    select.id = 'visitor-person';
                    select.className = 'w-full border p-2 rounded mt-2';
                    select.required = true;
                    
                    let defaultOption = document.createElement('option');
                    defaultOption.value = '';
                    defaultOption.textContent = 'اختر الزائر...';
                    select.appendChild(defaultOption);
                    
                    // تحديد ما إذا كان نوع الزائر منسق أو موجه
                    const visitorTypeName = visitorTypeSelect.options[visitorTypeSelect.selectedIndex].text;
                    const isCoordinatorOrSupervisor = visitorTypeName === 'منسق المادة' || visitorTypeName === 'موجه المادة';
                    
                    data.forEach(visitor => {
                        let option = document.createElement('option');
                        option.value = visitor.id;
                        option.textContent = visitor.name;
                        
                        // إضافة معلومات المواد كخاصية للعنصر
                        if (isCoordinatorOrSupervisor && visitor.subjects) {
                            option.dataset.subjects = JSON.stringify(visitor.subjects);
                        }
                        
                        select.appendChild(option);
                    });
                    
                    // تحديث عنصر اسم الزائر
                    visitorNameDiv.innerHTML = '';
                    visitorNameDiv.appendChild(select);
                    
                    // تحديث معرف الزائر وإظهار المادة المناسبة عند الاختيار
                    select.addEventListener('change', function() {
                        visitorPersonIdInput.value = this.value;
                        
                        // إذا كان منسق أو موجه مادة، نقوم بتحديث قائمة المواد
                        if (isCoordinatorOrSupervisor && this.value) {
                            const selectedOption = this.options[this.selectedIndex];
                            if (selectedOption.dataset.subjects) {
                                const subjects = JSON.parse(selectedOption.dataset.subjects);
                                
                                if (subjects.length > 0) {
                                    // تعديل قائمة المواد لإظهار فقط المواد التي يشرف عليها المنسق/الموجه
                                    subjectSelect.innerHTML = '<option value="">اختر المادة...</option>';
                                    
                                    subjects.forEach(subject => {
                                        let option = document.createElement('option');
                                        option.value = subject.id;
                                        option.textContent = subject.name;
                                        subjectSelect.appendChild(option);
                                    });
                                    
                                    // إذا كان هناك مادة واحدة فقط، نختارها تلقائياً
                                    if (subjects.length === 1) {
                                        subjectSelect.value = subjects[0].id;
                                        // تحميل المعلمين المتعلقين بهذه المادة
                                        loadTeachers();
                                    }
                                }
                            }
                        }
                    });
                } else {
                    visitorNameDiv.innerHTML = '<p class="text-red-500">لا يوجد زوار من هذا النوع</p>';
                    visitorPersonIdInput.value = '';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                visitorNameDiv.innerHTML = '<p class="text-red-500">حدث خطأ في تحميل بيانات الزوار</p>';
                visitorPersonIdInput.value = '';
            });
    } else {
        visitorNameDiv.innerHTML = '';
        visitorPersonIdInput.value = '';
    }
}

// تحميل المعلمين عند اختيار المادة
function loadTeachers() {
    const subjectSelect = document.getElementById('subject');
    const teacherSelect = document.getElementById('teacher');
    const schoolId = document.getElementById('school').value;
    const visitorTypeSelect = document.getElementById('visitor-type');
    const visitorPersonSelect = document.getElementById('visitor-person');
    
    if (!subjectSelect.value || !schoolId) {
        teacherSelect.innerHTML = '<option value="">اختر المعلم...</option>';
        return;
    }
    
    // تحديد نوع الزائر
    const visitorTypeName = visitorTypeSelect.options[visitorTypeSelect.selectedIndex]?.text || '';
    const isCoordinator = visitorTypeName === 'منسق المادة';
    const isSupervisor = visitorTypeName === 'موجه المادة';
    
    // إرسال طلب AJAX للحصول على قائمة المعلمين حسب المادة والمدرسة
    let url = `includes/get_teachers.php?subject_id=${subjectSelect.value}&school_id=${schoolId}`;
    
    // إذا كان الزائر منسق المادة أو موجه المادة، نضيف معلومات إضافية للتصفية
    if (isCoordinator || isSupervisor) {
        url += `&visitor_type=${encodeURIComponent(visitorTypeName)}`;
        if (visitorPersonSelect && visitorPersonSelect.value) {
            url += `&exclude_visitor=${visitorPersonSelect.value}`;
        }
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            // إعادة تعيين قائمة المعلمين
            teacherSelect.innerHTML = '<option value="">اختر المعلم...</option>';
            
            // إذا كان الزائر موجه، نضيف منسق المادة في بداية القائمة
            if (isSupervisor && subjectSelect.value) {
                // جلب منسق المادة
                fetch(`includes/get_subject_coordinator.php?subject_id=${subjectSelect.value}&school_id=${schoolId}`)
                    .then(response => response.json())
                    .then(coordinators => {
                        if (coordinators.length > 0) {
                            // إضافة منسقي المادة إلى القائمة
                            coordinators.forEach(coord => {
                                let option = document.createElement('option');
                                option.value = coord.id;
                                option.textContent = coord.name + ' (منسق المادة)';
                                teacherSelect.appendChild(option);
                            });
                            
                            // إضافة فاصل بين منسقي المادة والمعلمين العاديين
                            if (data.length > 0) {
                                let separator = document.createElement('option');
                                separator.disabled = true;
                                separator.textContent = '---------------------';
                                teacherSelect.appendChild(separator);
                            }
                        }
                        
                        // إضافة المعلمين إلى القائمة
                        data.forEach(teacher => {
                            let option = document.createElement('option');
                            option.value = teacher.id;
                            option.textContent = teacher.name;
                            teacherSelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error loading coordinators:', error);
                        // إضافة المعلمين فقط في حالة فشل جلب المنسقين
                        data.forEach(teacher => {
                            let option = document.createElement('option');
                            option.value = teacher.id;
                            option.textContent = teacher.name;
                            teacherSelect.appendChild(option);
                        });
                    });
            } else {
                // إضافة المعلمين إلى القائمة
                data.forEach(teacher => {
                    let option = document.createElement('option');
                    option.value = teacher.id;
                    option.textContent = teacher.name;
                    teacherSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            teacherSelect.innerHTML = '<option value="">حدث خطأ في تحميل المعلمين</option>';
        });
}

// تحميل الشعب عند اختيار الصف
function loadSections(gradeId) {
    const sectionSelect = document.getElementById('section');
    const schoolId = document.getElementById('school').value;
    
    if (gradeId && schoolId) {
        // إرسال طلب AJAX للحصول على قائمة الشعب حسب الصف والمدرسة
        fetch(`includes/get_sections.php?grade_id=${gradeId}&school_id=${schoolId}`)
            .then(response => response.json())
            .then(data => {
                // إعادة تعيين قائمة الشعب
                sectionSelect.innerHTML = '<option value="">اختر الشعبة...</option>';
                
                // إضافة الشعب إلى القائمة
                data.forEach(section => {
                    let option = document.createElement('option');
                    option.value = section.id;
                    option.textContent = section.name;
                    sectionSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                sectionSelect.innerHTML = '<option value="">حدث خطأ في تحميل الشعب</option>';
            });
    } else {
        sectionSelect.innerHTML = '<option value="">اختر الشعبة...</option>';
    }
}

// دالة عرض خطوة محددة من التقييم
function showStep(step) {
    const totalSteps = document.querySelectorAll('.evaluation-section').length;
    
    for (let i = 1; i <= totalSteps; i++) {
        const el = document.getElementById(`step-${i}`);
        if (el) {
            el.style.display = (i === step) ? 'block' : 'none';
        }
    }
    
    currentStep = step;
    window.scrollTo(0, 0);
}

// دالة حساب وعرض النتيجة النهائية
function calculateAndShowFinalResult() {
    // تحضير مصفوفات لتخزين النتائج
    const strengths = [];
    const improvements = [];
    let totalScore = 0;
    let totalItems = 0;
    
    // جمع نتائج التقييم
    document.querySelectorAll('.indicator-block').forEach((block, index) => {
        // لا نحسب المؤشرات المخفية (المعمل عندما يكون غير مُفعّل)
        if (block.style.display === 'none') {
            return;
        }
        
        const scoreSelect = block.querySelector('select[name^="score_"]');
        const score = parseInt(scoreSelect.value);
        const indicatorLabel = block.querySelector('label').textContent;
        
        // إذا كان التقييم جزئياً، نحسب فقط العناصر التي تم تقييمها
        // ونستثني مؤشرات "لم يتم قياسه" (score = 0) من الحساب
        if (score > 0) {
            // تصنيف نقاط القوة والتحسين
            if (score >= 3) {
                strengths.push(indicatorLabel);
            } else {
                improvements.push(indicatorLabel);
            }
            
            // إضافة النقاط للمجموع
            totalScore += score;
            totalItems++;
        }
    });
    
    // حساب المتوسط
    const average = totalItems > 0 ? (totalScore / totalItems).toFixed(2) : 0;
    
    // تحديد التقدير
    const grade = getGrade(average);
    
    // تحديث الحقول المخفية
    document.getElementById('hidden-total-score').value = totalScore;
    document.getElementById('hidden-average-score').value = average;
    document.getElementById('hidden-grade').value = grade;
    
    // حساب النسبة المئوية
    const percentage = (average * 25).toFixed(2);
    
    // عرض النتيجة الإجمالية
    const evaluationType = isPartialEvaluation ? 'تقييم جزئي' : 'تقييم كلي';
    document.getElementById('total-score').textContent = 
        `${evaluationType}: النتيجة ${totalScore} من ${totalItems * 4} (المتوسط: ${average} - النسبة: ${percentage}%)`;
    
    // عرض نقاط القوة
    const strengthsList = document.getElementById('strengths');
    strengthsList.innerHTML = '';
    if (strengths.length > 0) {
        strengths.forEach(strength => {
            strengthsList.innerHTML += `<li>${strength}</li>`;
        });
    } else {
        strengthsList.innerHTML = '<li class="text-gray-500">لم يتم تحديد نقاط قوة</li>';
    }
    
    // عرض نقاط التحسين
    const improvementsList = document.getElementById('improvements');
    improvementsList.innerHTML = '';
    if (improvements.length > 0) {
        improvements.forEach(improvement => {
            improvementsList.innerHTML += `<li>${improvement}</li>`;
        });
    } else {
        improvementsList.innerHTML = '<li class="text-gray-500">لم يتم تحديد نقاط تحتاج إلى تحسين</li>';
    }
    
    // جمع التوصيات المختارة
    const recommendationBoxes = document.querySelectorAll('input[type="checkbox"][name^="recommend_"]:checked');
    let selectedRecommendations = [];
    recommendationBoxes.forEach(box => {
        const label = document.querySelector(`label[for="${box.id}"]`);
        if (label) {
            selectedRecommendations.push(label.textContent.trim());
        }
    });
    
    // إضافة التوصيات المخصصة
    const customRecommendInputs = document.querySelectorAll('input[name^="custom_recommend_"]');
    customRecommendInputs.forEach(input => {
        if (input.value.trim()) {
            selectedRecommendations.push(input.value.trim());
        }
    });
    
    // تحديث حقل التوصيات إذا كان فارغاً
    const recommendationNotes = document.getElementById('recommendation-notes');
    if (!recommendationNotes.value.trim() && selectedRecommendations.length > 0) {
        recommendationNotes.value = selectedRecommendations.join('\n\n');
    }
    
    // عرض التوصيات
    const recommendationNotesValue = recommendationNotes.value;
    const recommendationNotesDisplay = document.getElementById('recommendation-notes-display');
    
    // دائماً نظهر حقل التوصيات حتى لو كان فارغاً
    recommendationNotesDisplay.querySelector('p').textContent = recommendationNotesValue || 'لم يتم إضافة توصيات';
    recommendationNotesDisplay.style.display = 'block';
    
    // عرض نقاط الشكر
    const appreciationNotes = document.getElementById('appreciation-notes').value;
    const appreciationNotesDisplay = document.getElementById('appreciation-notes-display');
    
    // دائماً نظهر حقل الشكر حتى لو كان فارغاً
    appreciationNotesDisplay.querySelector('p').textContent = appreciationNotes || 'لم يتم إضافة نقاط شكر';
    appreciationNotesDisplay.style.display = 'block';
    
    // عرض قسم النتيجة النهائية
    showStep(document.querySelectorAll('.evaluation-section').length);
}

// دالة الحصول على التقدير بناءً على المتوسط
function getGrade(average) {
    if (average >= 3.6) return 'ممتاز';
    if (average >= 3.2) return 'جيد جدًا';
    if (average >= 2.6) return 'جيد';
    if (average >= 2.0) return 'مقبول';
    return 'يحتاج إلى تحسين';
}

// دالة تحميل معلومات الزيارات السابقة
function loadPreviousVisitsInfo(teacherId, visitorPersonId) {
    if (!teacherId || !visitorPersonId) return;
    
    // جلب معلومات الزيارات السابقة من خلال API
    fetch(`api/get_previous_visits.php?teacher_id=${teacherId}&visitor_person_id=${visitorPersonId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const visitsInfo = data.data;
                const previousVisitsDiv = document.getElementById('previous-visits-info');
                
                // تحديث عدد الزيارات
                document.getElementById('visits-count').textContent = visitsInfo.visits_count;
                
                // إذا كان هناك زيارة سابقة، نعرض تفاصيلها
                if (visitsInfo.last_visit) {
                    const lastVisit = visitsInfo.last_visit;
                    const visitDate = new Date(lastVisit.date).toLocaleDateString('ar-EG');
                    document.getElementById('last-visit-date').textContent = visitDate;
                    document.getElementById('last-visit-class').textContent = 
                        `${lastVisit.grade || '-'} / ${lastVisit.section || '-'}`;
                    
                    // إضافة نسبة الزيارة السابقة
                    if (lastVisit.average_score !== undefined) {
                        const percentage = lastVisit.average_score * 25; // تحويل المتوسط إلى نسبة مئوية (4=100%)
                        document.getElementById('last-visit-percentage').textContent = `${percentage.toFixed(2)}%`;
                    } else {
                        document.getElementById('last-visit-percentage').textContent = 'غير متوفر';
                    }
                    
                    // عرض الملاحظات العامة من الزيارة السابقة
                    const notesElement = document.getElementById('last-visit-notes');
                    if (lastVisit.notes) {
                        notesElement.textContent = lastVisit.notes;
                    } else {
                        notesElement.textContent = 'لا توجد ملاحظات مسجلة';
                    }
                    
                    // إظهار قسم معلومات الزيارات السابقة
                    previousVisitsDiv.style.display = 'block';
                } else if (visitsInfo.visits_count > 0) {
                    // إذا كان هناك زيارات سابقة لكن بدون تفاصيل
                    document.getElementById('last-visit-date').textContent = 'غير متوفر';
                    document.getElementById('last-visit-class').textContent = 'غير متوفر';
                    document.getElementById('last-visit-percentage').textContent = 'غير متوفر';
                    document.getElementById('last-visit-notes').textContent = 'غير متوفر';
                    previousVisitsDiv.style.display = 'block';
                } else {
                    // لا توجد زيارات سابقة
                    previousVisitsDiv.style.display = 'none';
                }
            } else {
                console.error('Error loading previous visits:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading previous visits:', error);
        });
}

// وظيفة للتحكم بظهور/إخفاء مؤشرات المعمل
function toggleLabIndicators() {
    const labIndicators = document.querySelectorAll('.lab-indicator');
    labIndicators.forEach(indicator => {
        indicator.style.display = hasLab ? 'block' : 'none';
    });
}

// دالة تنسيق التاريخ بشكل صحيح
function formatDate(dateStr) {
    if (!dateStr) return '';
    
    const date = new Date(dateStr);
    // تنسيق التاريخ بالعربية (يوم/شهر/سنة)
    return date.toLocaleDateString('ar-EG');
}
</script>

    </main>

    <!-- ذيل الصفحة -->
    <footer class="bg-gray-800 text-white py-6 mt-12">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <p>&copy; 2025 نظام الزيارات الصفية - جميع الحقوق محفوظة</p>
                </div>
                <div>
                    <p>تم التطوير بواسطة فريق البرمجة</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- سكريبت مشترك -->
    <script>
        // دالة لتغيير لون حقل التقييم حسب القيمة المختارة
        function updateSelectColor() {
            const selects = document.querySelectorAll('select[name^="score_"]');
            selects.forEach(select => {
                select.addEventListener('change', function() {
                    // إزالة جميع الكلاسات السابقة
                    this.classList.remove('score-0', 'score-1', 'score-2', 'score-3', 'score-4');
                    // إضافة الكلاس الجديد
                    if(this.value !== '') {
                        this.classList.add(`score-${this.value}`);
                    }
                });

                // تطبيق الكلاس الحالي
                if(select.value !== '') {
                    select.classList.add(`score-${select.value}`);
                }
            });
        }

        // تحديث ألوان حقول التقييم عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            updateSelectColor();
        });
    </script>
</body>
</html>  