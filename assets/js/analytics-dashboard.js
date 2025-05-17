/**
 * Brew & Bake Analytics Dashboard
 * JavaScript for charts and analytics functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Date Range Picker
    if ($('#dateRangePicker').length) {
        $('#dateRangePicker').daterangepicker({
            opens: 'left',
            maxDate: new Date(),
            ranges: {
               'Today': [moment(), moment()],
               'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
               'Last 7 Days': [moment().subtract(6, 'days'), moment()],
               'Last 30 Days': [moment().subtract(29, 'days'), moment()],
               'This Month': [moment().startOf('month'), moment().endOf('month')],
               'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            locale: {
                format: 'YYYY-MM-DD'
            }
        });
    }
    
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded. Please include the Chart.js library.');
        return;
    }
    
    // Chart.js Global Configuration
    Chart.defaults.font.family = "'Poppins', sans-serif";
    Chart.defaults.color = '#6c757d';
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(17, 24, 39, 0.8)';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    
    // Initialize charts if elements exist
    initSalesTrendChart();
    initCategoryChart();
    initPaymentMethodChart();
    initOrderStatusChart();
    
    // Set up export functionality
    setupExportFunctions();
});

/**
 * Initialize Sales Trend Chart
 */
function initSalesTrendChart() {
    const salesTrendCtx = document.getElementById('salesTrendChart');
    if (!salesTrendCtx) return;
    
    // Get data from data attributes
    const labels = JSON.parse(salesTrendCtx.getAttribute('data-labels') || '[]');
    const revenueData = JSON.parse(salesTrendCtx.getAttribute('data-revenue') || '[]');
    const ordersData = JSON.parse(salesTrendCtx.getAttribute('data-orders') || '[]');
    
    const salesTrendData = {
        labels: labels,
        datasets: [
            {
                label: 'Revenue (₱)',
                data: revenueData,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                yAxisID: 'y'
            },
            {
                label: 'Orders',
                data: ordersData,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                yAxisID: 'y1'
            }
        ]
    };
    
    new Chart(salesTrendCtx, {
        type: 'line',
        data: salesTrendData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Revenue (₱)'
                    },
                    grid: {
                        borderDash: [2, 2]
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Orders'
                    },
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.datasetIndex === 0) {
                                label += '₱' + new Intl.NumberFormat().format(context.raw);
                            } else {
                                label += new Intl.NumberFormat().format(context.raw);
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Initialize Category Chart
 */
function initCategoryChart() {
    const categoryChartCtx = document.getElementById('categoryChart');
    if (!categoryChartCtx) return;
    
    // Get data from data attributes
    const labels = JSON.parse(categoryChartCtx.getAttribute('data-labels') || '[]');
    const data = JSON.parse(categoryChartCtx.getAttribute('data-values') || '[]');
    const colors = JSON.parse(categoryChartCtx.getAttribute('data-colors') || '[]');
    
    const categoryData = {
        labels: labels,
        datasets: [{
            data: data,
            backgroundColor: colors,
            borderWidth: 0
        }]
    };
    
    new Chart(categoryChartCtx, {
        type: 'doughnut',
        data: categoryData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = '₱' + new Intl.NumberFormat().format(context.raw);
                            const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((context.raw / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });
}

/**
 * Initialize Payment Method Chart
 */
function initPaymentMethodChart() {
    const paymentMethodChartCtx = document.getElementById('paymentMethodChart');
    if (!paymentMethodChartCtx) return;
    
    // Get data from data attributes
    const labels = JSON.parse(paymentMethodChartCtx.getAttribute('data-labels') || '[]');
    const data = JSON.parse(paymentMethodChartCtx.getAttribute('data-values') || '[]');
    
    const paymentMethodData = {
        labels: labels,
        datasets: [{
            data: data,
            backgroundColor: [
                '#f59e0b', // Amber/Gold
                '#3b82f6', // Blue
                '#10b981', // Green
                '#8b5cf6'  // Purple
            ],
            borderWidth: 0
        }]
    };
    
    new Chart(paymentMethodChartCtx, {
        type: 'pie',
        data: paymentMethodData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = '₱' + new Intl.NumberFormat().format(context.raw);
                            const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((context.raw / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Initialize Order Status Chart
 */
function initOrderStatusChart() {
    const orderStatusChartCtx = document.getElementById('orderStatusChart');
    if (!orderStatusChartCtx) return;
    
    // Get data from data attributes
    const data = JSON.parse(orderStatusChartCtx.getAttribute('data-values') || '[]');
    
    const orderStatusData = {
        labels: ['Pending', 'Completed', 'Cancelled'],
        datasets: [{
            data: data,
            backgroundColor: [
                '#f59e0b', // Amber/Gold (Warning)
                '#10b981', // Green (Success)
                '#ef4444'  // Red (Danger)
            ],
            borderWidth: 0
        }]
    };
    
    new Chart(orderStatusChartCtx, {
        type: 'polarArea',
        data: orderStatusData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    ticks: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw;
                            const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Set up export functionality
 */
function setupExportFunctions() {
    // CSV Export
    const exportCSVBtn = document.getElementById('exportCSV');
    if (exportCSVBtn) {
        exportCSVBtn.addEventListener('click', function(e) {
            e.preventDefault();
            exportTableToCSV('brew-and-bake-analytics-report.csv');
        });
    }
    
    // PDF Export
    const exportPDFBtn = document.getElementById('exportPDF');
    if (exportPDFBtn) {
        exportPDFBtn.addEventListener('click', function(e) {
            e.preventDefault();
            exportToPDF();
        });
    }
    
    // Print Report
    const printReportBtn = document.getElementById('printReport');
    if (printReportBtn) {
        printReportBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.print();
        });
    }
}
