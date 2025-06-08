    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Ensure Chart.js is loaded before initializing
        function initializeAdminCharts() {
            console.log('Initializing Admin Dashboard Charts...');
            
            try {
                // Sales Trend Chart
                const salesTrendCanvas = document.getElementById('salesTrendChart');
                if (salesTrendCanvas) {
                    const salesTrendData = @json($salesTrendData ?? []);
                    
                    // Ensure data has required structure
                    const chartData = {
                        labels: salesTrendData.labels || [],
                        counts: salesTrendData.counts || [],
                        amounts: salesTrendData.amounts || []
                    };
                    
                    // Only create chart if we have data
                    if (chartData.labels.length > 0) {
                        const salesTrendChart = new Chart(salesTrendCanvas.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: chartData.labels,
                                datasets: [
                                    {
                                        label: 'Number of Sales',
                                        data: chartData.counts,
                                        backgroundColor: 'rgba(0, 119, 181, 0.1)',
                                        borderColor: '#0077B5',
                                        borderWidth: 2,
                                        tension: 0.4,
                                        fill: true,
                                        pointBackgroundColor: '#0077B5',
                                        pointBorderColor: '#fff',
                                        pointBorderWidth: 2,
                                        pointRadius: 4,
                                        pointHoverRadius: 6,
                                        yAxisID: 'y'
                                    },
                                    {
                                        label: 'Revenue ({{ $currencySymbol }})',
                                        data: chartData.amounts,
                                        backgroundColor: 'rgba(255, 105, 0, 0.1)',
                                        borderColor: '#FF6900',
                                        borderWidth: 2,
                                        tension: 0.4,
                                        fill: true,
                                        pointBackgroundColor: '#FF6900',
                                        pointBorderColor: '#fff',
                                        pointBorderWidth: 2,
                                        pointRadius: 4,
                                        pointHoverRadius: 6,
                                        yAxisID: 'y1'
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: {
                                    mode: 'index',
                                    intersect: false,
                                },
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'top',
                                        labels: {
                                            usePointStyle: true,
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
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        type: 'linear',
                                        display: true,
                                        position: 'left',
                                        beginAtZero: true,
                                        grid: {
                                            color: 'rgba(0, 0, 0, 0.05)'
                                        },
                                        ticks: {
                                            precision: 0,
                                            font: {
                                                size: 11
                                            }
                                        },
                                        title: {
                                            display: true,
                                            text: 'Number of Sales',
                                            font: {
                                                size: 12
                                            }
                                        }
                                    },
                                    y1: {
                                        type: 'linear',
                                        display: true,
                                        position: 'right',
                                        beginAtZero: true,
                                        grid: {
                                            drawOnChartArea: false,
                                        },
                                        ticks: {
                                            font: {
                                                size: 11
                                            },
                                            callback: function(value) {
                                                return '{{ $currencySymbol }} ' + value.toFixed(0);
                                            }
                                        },
                                        title: {
                                            display: true,
                                            text: 'Revenue ({{ $currencySymbol }})',
                                            font: {
                                                size: 12
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
                    } else {
                        // Show no data message
                        const ctx = salesTrendCanvas.getContext('2d');
                        ctx.font = '16px Arial';
                        ctx.fillStyle = '#999';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillText('No sales data available', salesTrendCanvas.width / 2, salesTrendCanvas.height / 2);
                    }
                }
                
                // Payment Methods Chart
                const paymentMethodsCanvas = document.getElementById('paymentMethodsChart');
                if (paymentMethodsCanvas) {
                    const paymentMethodsData = @json($salesByPaymentMethod ?? []);
                    
                    const methodLabels = [];
                    const methodAmounts = [];
                    const backgroundColors = [];
                    const borderColors = [];
                    
                    // Process payment methods data
                    if (paymentMethodsData.cash) {
                        methodLabels.push('Cash');
                        methodAmounts.push(paymentMethodsData.cash.total || 0);
                        backgroundColors.push('rgba(255, 105, 0, 0.8)');
                        borderColors.push('#FF6900');
                    }
                    
                    if (paymentMethodsData.credit) {
                        methodLabels.push('Credit');
                        methodAmounts.push(paymentMethodsData.credit.total || 0);
                        backgroundColors.push('rgba(0, 119, 181, 0.8)');
                        borderColors.push('#0077B5');
                    }
                    
                    // Only create chart if we have data
                    if (methodLabels.length > 0 && methodAmounts.some(amount => amount > 0)) {
                        const paymentMethodsChart = new Chart(paymentMethodsCanvas.getContext('2d'), {
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
                                cutout: '60%',
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            padding: 15,
                                            font: {
                                                size: 12
                                            },
                                            generateLabels: function(chart) {
                                                const data = chart.data;
                                                if (data.labels.length && data.datasets.length) {
                                                    const sum = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                                    return data.labels.map((label, i) => {
                                                        const value = data.datasets[0].data[i];
                                                        const percentage = sum > 0 ? Math.round((value / sum) * 100) : 0;
                                                        return {
                                                            text: `${label} (${percentage}%)`,
                                                            fillStyle: data.datasets[0].backgroundColor[i],
                                                            strokeStyle: data.datasets[0].borderColor[i],
                                                            lineWidth: data.datasets[0].borderWidth,
                                                            hidden: false,
                                                            index: i
                                                        };
                                                    });
                                                }
                                                return [];
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
                                                const sum = context.dataset.data.reduce((a, b) => a + b, 0);
                                                const percentage = sum > 0 ? Math.round((value / sum) * 100) : 0;
                                                
                                                // Get count from original data
                                                let count = 0;
                                                if (label.toLowerCase().includes('cash') && paymentMethodsData.cash) {
                                                    count = paymentMethodsData.cash.count || 0;
                                                } else if (label.toLowerCase().includes('credit') && paymentMethodsData.credit) {
                                                    count = paymentMethodsData.credit.count || 0;
                                                }
                                                
                                                return [
                                                    `${label}: {{ $currencySymbol }} ${value.toFixed(2)} (${percentage}%)`,
                                                    `Transactions: ${count}`
                                                ];
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    } else {
                        // Show no data message
                        const ctx = paymentMethodsCanvas.getContext('2d');
                        ctx.font = '16px Arial';
                        ctx.fillStyle = '#999';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillText('No payment data for this month', paymentMethodsCanvas.width / 2, paymentMethodsCanvas.height / 2);
                    }
                }
                
                // Top Categories Chart
                const topCategoriesCanvas = document.getElementById('topCategoriesChart');
                if (topCategoriesCanvas) {
                    const topCategories = @json($topCategories ?? []);
                    
                    if (topCategories && topCategories.length > 0) {
                        const topCategoriesChart = new Chart(topCategoriesCanvas.getContext('2d'), {
                            type: 'pie',
                            data: {
                                labels: topCategories.map(category => category.name),
                                datasets: [{
                                    label: 'Items Sold',
                                    data: topCategories.map(category => category.total_quantity),
                                    backgroundColor: [
                                        'rgba(255, 105, 0, 0.8)',    // Orange
                                        'rgba(0, 119, 181, 0.8)',    // Blue
                                        'rgba(75, 192, 192, 0.8)',   // Teal
                                        'rgba(153, 102, 255, 0.8)',  // Purple
                                        'rgba(255, 159, 64, 0.8)',   // Light Orange
                                    ],
                                    borderColor: [
                                        '#FF6900',
                                        '#0077B5',
                                        '#4BC0C0',
                                        '#9966FF',
                                        '#FF9F40',
                                    ],
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
                                            padding: 10,
                                            font: {
                                                size: 11
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
                                                const sum = context.dataset.data.reduce((a, b) => a + b, 0);
                                                const percentage = sum > 0 ? Math.round((value / sum) * 100) : 0;
                                                return `${label}: ${value} items (${percentage}%)`;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    } else {
                        // Show no data message
                        const ctx = topCategoriesCanvas.getContext('2d');
                        ctx.font = '16px Arial';
                        ctx.fillStyle = '#999';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillText('No category data available', topCategoriesCanvas.width / 2, topCategoriesCanvas.height / 2);
                    }
                }
                
                console.log('Admin Dashboard Charts initialized successfully');
                
            } catch (error) {
                console.error('Error initializing admin charts:', error);
            }
        }

        // Sync Status Admin Function
        function syncStatusAdmin() {
            return {
                isOnline: navigator.onLine,
                showSyncStatus: false,
                pendingSyncCount: 0,
                offlineSalesSummary: null,
                offlineManager: null,

                async init() {
                    console.log('Initializing Sync Status for Admin Dashboard...');
                    
                    try {
                        // Wait for offline manager if available
                        await this.waitForOfflineManager();
                        
                        // Setup connection monitoring
                        this.setupConnectionMonitoring();
                        
                        // Update sync status
                        await this.updateSyncStatus();
                        
                        console.log('Sync Status initialized successfully');
                    } catch (error) {
                        console.error('Failed to initialize Sync Status:', error);
                    }
                },

                async waitForOfflineManager() {
                    return new Promise((resolve, reject) => {
                        let attempts = 0;
                        const maxAttempts = 20; // 2 seconds max wait
                        
                        const checkManager = () => {
                            attempts++;
                            
                            if (window.offlinePOS && window.offlinePOS.db) {
                                this.offlineManager = window.offlinePOS;
                                resolve();
                            } else if (attempts >= maxAttempts) {
                                console.warn('Offline manager not available, continuing without it');
                                resolve();
                            } else {
                                setTimeout(checkManager, 100);
                            }
                        };
                        checkManager();
                    });
                },

                setupConnectionMonitoring() {
                    // Monitor online/offline status
                    window.addEventListener('online', () => {
                        this.isOnline = true;
                        this.updateSyncStatus();
                    });
                    
                    window.addEventListener('offline', () => {
                        this.isOnline = false;
                        this.updateSyncStatus();
                    });
                    
                    // Listen for sync status updates if available
                    window.addEventListener('sync-status-updated', (event) => {
                        this.updateSyncStatus();
                    });
                    
                    // Update periodically
                    setInterval(() => {
                        this.updateSyncStatus();
                    }, 30000); // Every 30 seconds
                },

                async updateSyncStatus() {
                    if (!this.offlineManager) {
                        // Try to get data from API if offline manager is not available
                        try {
                            const response = await fetch('/api/v1/offline/sync-status');
                            if (response.ok) {
                                const data = await response.json();
                                this.pendingSyncCount = data.pendingCount || 0;
                                this.offlineSalesSummary = data.summary || null;
                            }
                        } catch (error) {
                            console.error('Error fetching sync status:', error);
                        }
                    } else {
                        // Use offline manager if available
                        try {
                            this.pendingSyncCount = await this.offlineManager.getPendingOperationsCount();
                            this.offlineSalesSummary = await this.offlineManager.getOfflineSalesSummary();
                        } catch (error) {
                            console.error('Error updating sync status:', error);
                        }
                    }
                },

                toggleSyncStatus() {
                    this.showSyncStatus = !this.showSyncStatus;
                    if (this.showSyncStatus) {
                        this.updateSyncStatus();
                    }
                },

                async forceSyncNow() {
                    if (!this.isOnline) {
                        alert('Cannot sync while offline');
                        return;
                    }

                    try {
                        // Show loading state
                        const button = event.target.closest('button');
                        const originalText = button.innerHTML;
                        button.innerHTML = '<svg class="animate-spin h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Syncing...';
                        button.disabled = true;

                        if (this.offlineManager) {
                            await this.offlineManager.startBackgroundSync();
                        } else {
                            // Trigger sync via API
                            const response = await fetch('/api/v1/offline/sync-all', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                                }
                            });
                            
                            if (!response.ok) {
                                throw new Error('Sync failed');
                            }
                        }
                        
                        await this.updateSyncStatus();
                        
                        // Restore button state
                        button.innerHTML = originalText;
                        button.disabled = false;
                        
                        // Show success message
                        this.showNotification('Synchronization completed successfully', 'success');
                    } catch (error) {
                        console.error('Manual sync failed:', error);
                        alert('Synchronization failed: ' + error.message);
                    }
                },

                showNotification(message, type = 'info') {
                    // Create notification element
                    const notification = document.createElement('div');
                    notification.className = `fixed top-4 right-4 px-4 py-3 rounded-lg text-white font-medium shadow-lg transition-all duration-300 z-50`;
                    
                    // Set background color based on type
                    const bgColors = {
                        success: 'bg-green-500',
                        error: 'bg-red-500',
                        warning: 'bg-yellow-500',
                        info: 'bg-blue-500'
                    };
                    
                    notification.classList.add(bgColors[type] || bgColors.info);
                    notification.textContent = message;
                    
                    document.body.appendChild(notification);
                    
                    // Remove after 3 seconds
                    setTimeout(() => {
                        notification.classList.add('opacity-0');
                        setTimeout(() => notification.remove(), 300);
                    }, 3000);
                }
            }
        }

        // Initialize charts when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeAdminCharts);
        } else {
            // DOM is already loaded
            initializeAdminCharts();
        }
    </script>
    @endpush
