    <!-- تذييل النظام -->
    <footer class="bg-gray-800 text-white py-6 mt-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-sm">
                © 2024 نظام منسق التعليم الإلكتروني - جميع الحقوق محفوظة
            </p>
            <p class="text-xs text-gray-400 mt-1">
                مصمم خصيصاً لإدارة ومتابعة التعليم الرقمي
            </p>
        </div>
    </footer>

    <!-- سكريبتات عامة -->
    <script>
        // تأكيد العمليات المهمة
        function confirmAction(message) {
            return confirm(message || 'هل أنت متأكد من هذا الإجراء؟');
        }

        // إظهار رسائل التنبيه
        function showAlert(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `fixed top-4 left-1/2 transform -translate-x-1/2 px-6 py-3 rounded-lg text-white z-50 ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 
                type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'
            }`;
            alertDiv.textContent = message;
            
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

        // تحديث التاريخ والوقت
        function updateDateTime() {
            const now = new Date();
            const dateString = now.toLocaleDateString('ar-SA');
            const timeString = now.toLocaleTimeString('ar-SA');
            
            const dateTimeElement = document.getElementById('current-datetime');
            if (dateTimeElement) {
                dateTimeElement.textContent = `${dateString} - ${timeString}`;
            }
        }

        // تشغيل تحديث الوقت كل دقيقة
        setInterval(updateDateTime, 60000);
        updateDateTime();
    </script>
</body>
</html>
