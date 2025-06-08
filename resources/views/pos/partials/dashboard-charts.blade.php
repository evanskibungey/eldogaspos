<!-- POS Dashboard Charts Section -->
<div class="dashboard-charts-section bg-white rounded-lg shadow-sm p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
        <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
        Dashboard Overview
    </h3>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Sales Trend Chart -->
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex justify-between items-center mb-4">
                <h4 class="text-md font-medium text-gray-700 flex items-center">
                    <svg class="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                    Sales Trend (Last 7 Days)
                </h4>
                <a href="{{ route('pos.sales.history') }}" class="text-sm text-orange-600 hover:text-orange-700 flex items-center">
                    View Details
                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
            <div class="h-64">
                <canvas id="posSalesTrendChart"></canvas>
            </div>
        </div>

        <!-- Payment Methods Chart -->
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex justify-between items-center mb-4">
                <h4 class="text-md font-medium text-gray-700 flex items-center">
                    <svg class="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2" />
                    </svg>
                    Payment Methods (Today)
                </h4>
            </div>
            <div class="h-64">
                <canvas id="posPaymentMethodsChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sales Trend Chart
    const salesTrendCtx = document.getElementById('posSalesTrendChart');
    if (salesTrendCtx) {
        const salesTrendData = @json($salesTrendData ?? []);
        
        new Chart(salesTrendCtx, {
            type: 'line',
            data: {
                labels: salesTrendData.labels || [],
                datasets: [
                    {
                        label: 'Number of Sales',
                        data: salesTrendData.counts || [],
                        backgroundColor: 'rgba(255, 105, 0, 0.1)',
                        borderColor: '#FF6900',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#FF6900',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            afterLabel: function(context) {
                                const index = context.dataIndex;
                                const amount = salesTrendData.amounts[index];
                                return 'Revenue: {{ $currencySymbol }} ' + amount.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            precision: 0,
                            font: {
                                size: 11
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Payment Methods Chart
    const paymentMethodsCtx = document.getElementById('posPaymentMethodsChart');
    if (paymentMethodsCtx) {
        const paymentMethodsData = @json($paymentMethodsData ?? []);
        
        const methodLabels = [];
        const methodAmounts = [];
        const methodCounts = [];
        const backgroundColors = [];
        const borderColors = [];
        
        if (paymentMethodsData.cash && paymentMethodsData.cash.total > 0) {
            methodLabels.push('Cash');
            methodAmounts.push(paymentMethodsData.cash.total);
            methodCounts.push(paymentMethodsData.cash.count);
            backgroundColors.push('#FF6900');
            borderColors.push('#FF6900');
        }
        
        if (paymentMethodsData.credit && paymentMethodsData.credit.total > 0) {
            methodLabels.push('Credit');
            methodAmounts.push(paymentMethodsData.credit.total);
            methodCounts.push(paymentMethodsData.credit.count);
            backgroundColors.push('#0077B5');
            borderColors.push('#0077B5');
        }
        
        // If no data, show a message
        if (methodLabels.length === 0) {
            const ctx = paymentMethodsCtx.getContext('2d');
            ctx.font = '16px Arial';
            ctx.fillStyle = '#999';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('No sales data for today', paymentMethodsCtx.width / 2, paymentMethodsCtx.height / 2);
        } else {
            new Chart(paymentMethodsCtx, {
                type: 'doughnut',
                data: {
                    labels: methodLabels,
                    datasets: [{
                        label: 'Sales by Payment Method',
                        data: methodAmounts,
                        backgroundColor: backgroundColors,
                        borderColor: borderColors,
                        borderWidth: 2,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14
                            },
                            bodyFont: {
                                size: 13
                            },
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const count = methodCounts[context.dataIndex];
                                    const percent = Math.round((value / methodAmounts.reduce((a, b) => a + b, 0)) * 100);
                                    return [
                                        label + ': {{ $currencySymbol }} ' + value.toFixed(2) + ' (' + percent + '%)',
                                        'Transactions: ' + count
                                    ];
                                }
                            }
                        }
                    },
                    cutout: '60%'
                }
            });
        }
    }
});
</script>
