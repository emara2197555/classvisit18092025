-- ملف ترجمات التوصيات للإنجليزية
-- هذا الملف يحتوي على ترجمات التوصيات الموجودة في قاعدة البيانات للإنجليزية
-- يمكن تشغيله بعد إضافة عمود text_en إلى جدول recommendations

-- تحديث التوصيات للمؤشرات المختلفة
-- المؤشر 1: خطة الدرس متوفرة وبنودها مستكملة ومناسبة
UPDATE recommendations SET text_en = 'The lesson plan should be available on Qatar Education System.' WHERE id = 239;
UPDATE recommendations SET text_en = 'The lesson plan should be consistent with the semester plan.' WHERE id = 240;
UPDATE recommendations SET text_en = 'The lesson plan should be written in proper language and characterized by accuracy and clarity.' WHERE id = 241;
UPDATE recommendations SET text_en = 'The lesson plan should include warm-up activities and be linked to the lesson topic and objectives.' WHERE id = 242;
UPDATE recommendations SET text_en = 'The lesson plan should include diverse teaching methods and strategies.' WHERE id = 243;
UPDATE recommendations SET text_en = 'The teacher should clearly and appropriately specify integration with other subjects in the lesson plan.' WHERE id = 244;
UPDATE recommendations SET text_en = 'The lesson closure in the plan should be appropriate for the topic and objectives.' WHERE id = 245;
UPDATE recommendations SET text_en = 'Appropriate and diverse assessment methods should be chosen in the lesson plan.' WHERE id = 246;
UPDATE recommendations SET text_en = 'The updated lesson plan template should be used.' WHERE id = 247;

-- المؤشر 2: أهداف التعلم مناسبة ودقيقة الصياغة وقابلة للقياس
UPDATE recommendations SET text_en = 'Learning objectives should be linked to the lesson topic including knowledge and skills.' WHERE id = 248;
UPDATE recommendations SET text_en = 'Learning objectives should be formulated in a proper and clear procedural manner.' WHERE id = 249;
UPDATE recommendations SET text_en = 'Learning objectives should consider diversity between cognitive and skill levels.' WHERE id = 250;
UPDATE recommendations SET text_en = 'Learning objectives should be measurable.' WHERE id = 251;

-- المؤشر 3: أنشطة الدرس الرئيسة واضحة ومتدرجة ومرتبطة بالأهداف
UPDATE recommendations SET text_en = 'Activities should be linked to lesson objectives and help achieve them.' WHERE id = 252;
UPDATE recommendations SET text_en = 'Activities should consider progression and sequence in achieving lesson objectives.' WHERE id = 253;
UPDATE recommendations SET text_en = 'Main activities should clearly specify the role of both teacher and student.' WHERE id = 254;
UPDATE recommendations SET text_en = 'Main activities should enhance core competencies and values within the cognitive context.' WHERE id = 255;
UPDATE recommendations SET text_en = 'Activities should clearly consider individual differences among students.' WHERE id = 256;
UPDATE recommendations SET text_en = 'Time allocation should be specified and appropriate for activities.' WHERE id = 257;
UPDATE recommendations SET text_en = 'The mechanism for employing technology tools in teacher and learner roles should be clarified.' WHERE id = 258;

-- المؤشر 4: أهداف التعلم معروضة ويتم مناقشتها
UPDATE recommendations SET text_en = 'The teacher should clearly and appropriately present the planned lesson objectives at the beginning of the lesson.' WHERE id = 259;
UPDATE recommendations SET text_en = 'The clarity of lesson objectives among students should be verified.' WHERE id = 260;
UPDATE recommendations SET text_en = 'Objectives should be as documented in the lesson plan.' WHERE id = 261;

-- المؤشر 5: أنشطة التمهيد مفعلة بشكل مناسب
UPDATE recommendations SET text_en = 'Warm-up activities should be implemented in an attractive and interesting way.' WHERE id = 262;
UPDATE recommendations SET text_en = 'Warm-up activities should prepare for main activities and be linked to them.' WHERE id = 263;
UPDATE recommendations SET text_en = 'Warm-up activities should be implemented within the allocated time.' WHERE id = 264;
UPDATE recommendations SET text_en = 'Warm-up activities should be linked to students\' life experiences and previous knowledge.' WHERE id = 265;

-- المؤشر 6: محتوى الدرس واضح والعرض منظّم ومترابط
UPDATE recommendations SET text_en = 'Lesson content should be presented in a clear manner.' WHERE id = 266;
UPDATE recommendations SET text_en = 'Content should be presented progressively and organized with sufficient examples.' WHERE id = 267;
UPDATE recommendations SET text_en = 'Lesson implementation steps should be interconnected and connected to objectives.' WHERE id = 268;
UPDATE recommendations SET text_en = 'Content should be linked to environment and life experiences.' WHERE id = 269;

-- المؤشر 7: طرائق التدريس وإستراتيجياته متنوعه وتتمحور حول الطالب
UPDATE recommendations SET text_en = 'Teaching strategies should be applied that suit lesson objectives and consider learners.' WHERE id = 270;
UPDATE recommendations SET text_en = 'Teaching strategies should be applied that consider learners.' WHERE id = 271;
UPDATE recommendations SET text_en = 'The strategy should be implemented correctly and according to what is stated in the lesson plan.' WHERE id = 272;
UPDATE recommendations SET text_en = 'Implemented strategies should be diverse and activate the student\'s role in the learning process.' WHERE id = 273;

-- المؤشر 8: مصادر التعلم الرئيسة والمساندة موظّفة بصورة واضحة وسليمة
UPDATE recommendations SET text_en = 'The main learning resource should be employed clearly and properly.' WHERE id = 274;
UPDATE recommendations SET text_en = 'Supporting paper resources should be employed to enrich the lesson and help achieve its objectives.' WHERE id = 275;
UPDATE recommendations SET text_en = 'Supporting electronic learning resources for the subject should be published on Qatar Education System.' WHERE id = 276;
UPDATE recommendations SET text_en = 'Students should be encouraged to use learning resources during the lesson.' WHERE id = 277;
UPDATE recommendations SET text_en = 'Diverse resources should be used to consider individual differences among students.' WHERE id = 278;

-- المؤشر 9: الوسائل التعليميّة والتكنولوجيا موظّفة بصورة مناسبة
UPDATE recommendations SET text_en = 'Diverse and effective educational tools should be used.' WHERE id = 279;
UPDATE recommendations SET text_en = 'Technology should be employed to serve the educational situation and objectives.' WHERE id = 280;
UPDATE recommendations SET text_en = 'The whiteboard display should be organized.' WHERE id = 281;
UPDATE recommendations SET text_en = 'The interactive whiteboard should be activated to serve the educational situation.' WHERE id = 282;

-- المؤشر 10: الأسئلة الصفية ذات صيغة سليمة ومتدرجة ومثيرة للتفكير
UPDATE recommendations SET text_en = 'Questions should be clear and properly formulated.' WHERE id = 283;
UPDATE recommendations SET text_en = 'Questions should be diverse and graduated in their levels.' WHERE id = 284;
UPDATE recommendations SET text_en = 'Questions should arouse students\' interest and encourage them to participate and ask questions.' WHERE id = 285;
UPDATE recommendations SET text_en = 'Questions should enhance dialogue and discussion between student and teacher and among students.' WHERE id = 286;
UPDATE recommendations SET text_en = 'Questions should arouse thinking and challenge among students.' WHERE id = 287;

-- المؤشر 11: المادة العلمية دقيقة و مناسبة
UPDATE recommendations SET text_en = 'Scientific content should be linked to lesson objectives.' WHERE id = 288;
UPDATE recommendations SET text_en = 'Scientific content should be consistent with the learning resource.' WHERE id = 289;
UPDATE recommendations SET text_en = 'Presented scientific content should be correct and sound, free from scientific and linguistic errors.' WHERE id = 290;
UPDATE recommendations SET text_en = 'Scientific content should be clear and its vocabulary appropriate for the educational level.' WHERE id = 291;
UPDATE recommendations SET text_en = 'Enrichment scientific content should be based on reliable references.' WHERE id = 292;

-- المؤشر 12: الكفايات الأساسية متضمنة في السياق المعرفي للدرس
UPDATE recommendations SET text_en = 'The teacher should provide activities that enable students to suggest alternatives and produce ideas in innovative ways.' WHERE id = 293;
UPDATE recommendations SET text_en = 'The teacher should provide activities that develop students\' language skills to employ them in expressing opinions and ideas.' WHERE id = 294;
UPDATE recommendations SET text_en = 'The teacher should provide activities that develop students\' numerical skills to employ them in various situations.' WHERE id = 295;
UPDATE recommendations SET text_en = 'The teacher should provide activities that enable students to communicate through listening, speaking, and writing, and employ this for different purposes.' WHERE id = 296;
UPDATE recommendations SET text_en = 'The teacher should provide students with collaborative work activities and respect for self, and accept positive change.' WHERE id = 297;
UPDATE recommendations SET text_en = 'The teacher should provide students with activities for inquiry interest and employing technology in preparing and sharing research.' WHERE id = 298;
UPDATE recommendations SET text_en = 'The teacher should provide students with activities for problem identification and cooperation with others in suggesting solutions.' WHERE id = 299;

-- المؤشر 13: القيم الأساسية متضمنة في السياق المعرفي للدرس
UPDATE recommendations SET text_en = 'The teacher should provide activities that contribute to students\' pride in Arabic language, history, and Qatari traditions.' WHERE id = 300;
UPDATE recommendations SET text_en = 'The teacher should provide activities for students to respect others and appreciate themselves.' WHERE id = 301;
UPDATE recommendations SET text_en = 'The teacher should provide activities that enhance students\' confidence in their ability to learn and exert effort in doing so.' WHERE id = 302;
UPDATE recommendations SET text_en = 'The teacher should provide activities that encourage students to commit to their rights and duties.' WHERE id = 303;
UPDATE recommendations SET text_en = 'The teacher should provide activities to develop students\' healthy lifestyle patterns.' WHERE id = 304;

-- المؤشر 14: التكامل بين محاور المادة ومع المواد الأخرى يتم بشكل مناسب
UPDATE recommendations SET text_en = 'The teacher should effectively link between subject areas and skills.' WHERE id = 305;
UPDATE recommendations SET text_en = 'The teacher should employ integration with other subjects to achieve cognitive growth among students.' WHERE id = 306;

-- المؤشر 15: الفروق الفردية بين الطلبة يتم مراعاتها
UPDATE recommendations SET text_en = 'Students should be distributed appropriately according to their levels and the implemented activity.' WHERE id = 307;
UPDATE recommendations SET text_en = 'Activities and exercises that consider individual differences should be provided.' WHERE id = 308;
UPDATE recommendations SET text_en = 'Activities and exercises that consider learning styles (auditory, visual, kinesthetic...) should be provided.' WHERE id = 309;
UPDATE recommendations SET text_en = 'The class teacher should follow up on materials provided by the support teacher for students.' WHERE id = 310;
UPDATE recommendations SET text_en = 'Necessary accommodations and arrangements for support students should be provided.' WHERE id = 311;
UPDATE recommendations SET text_en = 'Technology should be employed to consider individual differences and support students.' WHERE id = 312;

-- المؤشر 16: غلق الدرس يتم بشكل مناسب
UPDATE recommendations SET text_en = 'Lesson closure should be appropriate and comprehensive.' WHERE id = 313;
UPDATE recommendations SET text_en = 'Closure should reflect the achievement of lesson objectives.' WHERE id = 314;
UPDATE recommendations SET text_en = 'Students should have the major role in closure.' WHERE id = 315;
UPDATE recommendations SET text_en = 'Closure should be implemented within the allocated time.' WHERE id = 316;

-- المؤشر 17: أساليب التقويم (القبلي والبنائي والختامي) مناسبة ومتنوعة
UPDATE recommendations SET text_en = 'Assessment methods should be appropriate and diverse.' WHERE id = 317;
UPDATE recommendations SET text_en = 'Assessment tools should be used to measure student achievement.' WHERE id = 318;
UPDATE recommendations SET text_en = 'Assessment should be linked to lesson objectives.' WHERE id = 319;
UPDATE recommendations SET text_en = 'Assessment should consider individual differences among students.' WHERE id = 320;
UPDATE recommendations SET text_en = 'Assessment should be implemented within the allocated time.' WHERE id = 321;

-- المؤشر 18: التغذية الراجعة متنوعة ومستمرة
UPDATE recommendations SET text_en = 'Feedback should be provided to students continuously.' WHERE id = 322;
UPDATE recommendations SET text_en = 'Feedback should be constructive and help improve performance.' WHERE id = 323;
UPDATE recommendations SET text_en = 'Feedback should be provided in a timely manner.' WHERE id = 324;
UPDATE recommendations SET text_en = 'Feedback should consider individual differences among students.' WHERE id = 325;

-- المؤشر 19: أعمال الطلبة متابعة ومصححة بدقة ورقيًا وإلكترونيًا
UPDATE recommendations SET text_en = 'Student work should be followed up and corrected accurately.' WHERE id = 326;
UPDATE recommendations SET text_en = 'Student work should be corrected both on paper and electronically.' WHERE id = 327;
UPDATE recommendations SET text_en = 'Student work should be corrected in a timely manner.' WHERE id = 328;
UPDATE recommendations SET text_en = 'Student work should be corrected according to clear criteria.' WHERE id = 329;

-- المؤشر 20: البيئة الصفية إيجابية وآمنة وداعمة للتعلّم
UPDATE recommendations SET text_en = 'The classroom environment should be positive and safe.' WHERE id = 330;
UPDATE recommendations SET text_en = 'The classroom environment should be supportive of learning.' WHERE id = 331;
UPDATE recommendations SET text_en = 'The classroom environment should encourage student participation.' WHERE id = 332;
UPDATE recommendations SET text_en = 'The classroom environment should be organized and clean.' WHERE id = 333;

-- المؤشر 21: إدارة أنشطة التعلّم والمشاركات الصّفيّة تتم بصورة منظمة
UPDATE recommendations SET text_en = 'Learning activities and classroom participation should be managed in an organized manner.' WHERE id = 334;
UPDATE recommendations SET text_en = 'Learning activities should be managed according to the allocated time.' WHERE id = 335;
UPDATE recommendations SET text_en = 'Classroom participation should be managed to ensure equal opportunities for all students.' WHERE id = 336;
UPDATE recommendations SET text_en = 'Learning activities should be managed to achieve lesson objectives.' WHERE id = 337;

-- المؤشر 22: قوانين إدارة الصف وإدارة السلوك مفعّلة
UPDATE recommendations SET text_en = 'Classroom management and behavior rules should be activated.' WHERE id = 338;
UPDATE recommendations SET text_en = 'Classroom management rules should be clear and known to all students.' WHERE id = 339;
UPDATE recommendations SET text_en = 'Behavior management rules should be applied consistently.' WHERE id = 340;
UPDATE recommendations SET text_en = 'Classroom management rules should be applied fairly to all students.' WHERE id = 341;

-- المؤشر 23: الاستثمار الأمثل لزمن الحصة
UPDATE recommendations SET text_en = 'Class time should be optimally utilized.' WHERE id = 342;
UPDATE recommendations SET text_en = 'Time should be distributed appropriately among lesson activities.' WHERE id = 343;
UPDATE recommendations SET text_en = 'Time should be managed to achieve lesson objectives.' WHERE id = 344;
UPDATE recommendations SET text_en = 'Time should be managed to ensure student participation.' WHERE id = 345;

-- المؤشر 24: (للمواد التي تحتوي على معمل) استخدام المعمل بشكل مناسب
UPDATE recommendations SET text_en = 'The laboratory should be used appropriately.' WHERE id = 346;
UPDATE recommendations SET text_en = 'Laboratory equipment should be used safely and correctly.' WHERE id = 347;
UPDATE recommendations SET text_en = 'Laboratory activities should be linked to lesson objectives.' WHERE id = 348;
UPDATE recommendations SET text_en = 'Laboratory safety rules should be followed.' WHERE id = 349;
UPDATE recommendations SET text_en = 'Laboratory activities should be managed in an organized manner.' WHERE id = 350;

-- رسالة تأكيد
SELECT 'تم تحديث جميع التوصيات بالترجمات الإنجليزية بنجاح' AS message;
