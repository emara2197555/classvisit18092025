<?php
// بداية ذيل الصفحة
?>
    </main>

    <!-- ذيل الصفحة -->
    <footer class="bg-gray-800 text-white py-6 mt-10">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <p>&copy; <?= date('Y') ?> نظام الزيارات الصفية - جميع الحقوق محفوظة</p>
                </div>
                <div class="text-center text-sm">
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

        // التحكم في القائمة المتنقلة
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        
        if (mobileMenuButton && mobileMenu) {
            mobileMenuButton.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
            });
        }

        // التحكم في القوائم الفرعية المتنقلة
        const mobileSubmenuToggles = document.querySelectorAll('.mobile-submenu-toggle');
        
        mobileSubmenuToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                const submenu = this.nextElementSibling;
                submenu.classList.toggle('hidden');
            });
        });
    </script>
</body>
</html><?php
// نهاية ذيل الصفحة
?> 