<?php
// بداية ذيل الصفحة
?>
    </main>

    <!-- ذيل الصفحة -->
    <footer class="bg-gray-800 text-white py-6 mt-12">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <p>&copy; <?= date('Y') ?> نظام الزيارات الصفية - جميع الحقوق محفوظة</p>
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
</html><?php
// نهاية ذيل الصفحة
?> 