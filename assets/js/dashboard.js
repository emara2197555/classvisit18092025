/**
 * ملف جافاسكريبت لإنشاء وتفعيل الرسوم البيانية في لوحة التحكم
 */

document.addEventListener('DOMContentLoaded', function() {
    // تهيئة الرسوم البيانية عند تحميل الصفحة
    setupCharts();
    
    // إضافة تفاعلية لبطاقات القياس
    setupCards();
});

/**
 * دالة تهيئة الرسوم البيانية
 */
function setupCharts() {
    // إعداد رسم بياني للأداء حسب المجالات
    if (document.getElementById('domainsChart') && typeof domainsChartData !== 'undefined') {
        const domainsCtx = document.getElementById('domainsChart').getContext('2d');
        new Chart(domainsCtx, {
            type: 'bar',
            data: domainsChartData,
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'متوسط الأداء حسب المجالات'
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'نسبة الأداء (%)'
                        }
                    }
                }
            }
        });
    }

    // إعداد رسم بياني لتطور الأداء عبر الزمن
    if (document.getElementById('performanceOverTimeChart') && typeof performanceOverTimeChartData !== 'undefined') {
        const performanceCtx = document.getElementById('performanceOverTimeChart').getContext('2d');
        new Chart(performanceCtx, {
            type: 'line',
            data: performanceOverTimeChartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15
                        }
                    },
                    title: {
                        display: true,
                        text: 'تطور متوسط الأداء',
                        font: {
                            size: 14
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 13
                        },
                        padding: 10,
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.parsed.y}%`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'نسبة الأداء (%)',
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        },
                        grid: {
                            color: 'rgba(200, 200, 200, 0.2)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(200, 200, 200, 0.2)'
                        }
                    }
                },
                elements: {
                    line: {
                        tension: 0.3, // جعل الخط أكثر انسيابية
                    },
                    point: {
                        hoverRadius: 8,
                        hoverBorderWidth: 2
                    }
                }
            }
        });
    }

    // إعداد رسم بياني للأداء حسب المراحل التعليمية
    if (document.getElementById('levelsChart') && typeof levelChartData !== 'undefined') {
        const levelsCtx = document.getElementById('levelsChart').getContext('2d');
        new Chart(levelsCtx, {
            type: 'pie',
            data: levelChartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'متوسط الأداء حسب المراحل التعليمية'
                    }
                }
            }
        });
    }

    // إعداد رسم بياني للمؤشرات الضعيفة
    if (document.getElementById('weakIndicatorsChart') && typeof weakIndicatorsChartData !== 'undefined') {
        const weakCtx = document.getElementById('weakIndicatorsChart').getContext('2d');
        new Chart(weakCtx, {
            type: 'bar',
            data: weakIndicatorsChartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'المؤشرات الأكثر ضعفاً'
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true,
                        max: 4,
                        title: {
                            display: true,
                            text: 'متوسط الدرجة'
                        }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false
                        },
                        title: {
                            display: true,
                            text: 'عدد المعلمين'
                        }
                    }
                }
            }
        });
    }
}

/**
 * دالة تهيئة تفاعلية البطاقات
 */
function setupCards() {
    // إضافة تأثير تفاعلي عند المرور على البطاقات
    const cards = document.querySelectorAll('.dashboard-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.classList.add('shadow-lg');
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.classList.remove('shadow-lg');
            this.style.transform = 'translateY(0)';
        });
    });
    
    // إضافة تفاعلية للتنبيهات
    const alerts = document.querySelectorAll('.alert-item');
    alerts.forEach(alert => {
        alert.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(-5px)';
        });
        
        alert.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
}

/**
 * دالة لتحديث بيانات لوحة التحكم بشكل ديناميكي (إذا تم تفعيل التحديث التلقائي)
 */
function updateDashboard() {
    // يمكن تنفيذ طلب AJAX لتحديث البيانات دون إعادة تحميل الصفحة
    fetch('api/get_dashboard_data.php')
        .then(response => response.json())
        .then(data => {
            // تحديث قيم الإحصائيات
            document.getElementById('visitsCount').textContent = data.visitsCount;
            document.getElementById('teachersCount').textContent = data.teachersCount;
            document.getElementById('avgPerformance').textContent = data.avgPerformance + '%';
            document.getElementById('pendingRecommendations').textContent = data.pendingRecommendations;
            
            // تحديث الرسوم البيانية
            // لاحظ أنه سيتطلب تهيئة الرسوم البيانية مرة أخرى مع البيانات الجديدة
        })
        .catch(error => console.error('خطأ في تحديث لوحة التحكم:', error));
}

// يمكن استدعاء updateDashboard كل فترة زمنية محددة للتحديث التلقائي
// setInterval(updateDashboard, 60000); // تحديث كل دقيقة 