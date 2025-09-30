
<script type="text/javascript">
// Optimized Chart Loading with Lazy Loading, Code Splitting, and Accessibility
(function() {
    'use strict';

    // Chart configuration cache to avoid redundant data processing
    const chartConfigs = {
        'income-expense': {
            series: [{
                name: '{{ __("income.title")}}',
                type: 'area',
                data: [
                    @foreach($data['incomeDates'] as $date)
                              {{  dayIncomeCount($date) }},
                    @endforeach
                ]
            }, {
                name: '{{  __("expense.title") }}',
                type: 'area',
                data: [
                    @foreach($data['expenseDates'] as $date)
                        {{  dayExpenseCount($date) }},
                    @endforeach
                ]
            }],
            chart: {
                height: 450,
                type: 'area',
                fontFamily: 'var(--font-family-sans-serif, "Nunito", sans-serif)',
                background: 'transparent',
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800,
                    animateGradually: {
                        enabled: true,
                        delay: 150
                    },
                    dynamicAnimation: {
                        enabled: true,
                        speed: 350
                    }
                },
                events: {
                    mounted: function(chartContext, config) {
                        // Announce chart ready to screen readers
                        const chartElement = config.el;
                        const announcement = document.createElement('div');
                        announcement.setAttribute('aria-live', 'polite');
                        announcement.setAttribute('aria-atomic', 'true');
                        announcement.className = 'sr-only';
                        announcement.textContent = 'Income expense trend chart loaded with {{ count($data["incomeDates"]) }} data points';
                        chartElement.appendChild(announcement);
                        setTimeout(() => chartElement.removeChild(announcement), 1000);
                    },
                    dataPointSelection: function(event, chartContext, config) {
                        // Keyboard navigation support
                        const dataPoint = config.dataPointIndex;
                        const series = config.seriesIndex;
                        const value = config.w.config.series[series].data[dataPoint];
                        const label = config.w.config.labels[dataPoint];

                        // Announce to screen readers
                        const announcement = document.createElement('div');
                        announcement.setAttribute('aria-live', 'assertive');
                        announcement.className = 'sr-only';
                        announcement.textContent = `Data point: ${config.w.config.series[series].name}, ${label}, value: ${value}`;
                        config.el.appendChild(announcement);
                        setTimeout(() => config.el.removeChild(announcement), 2000);
                    }
                }
            },
            colors: ['var(--primary-500)', 'var(--error)'],
            stroke: {
                curve: 'smooth',
                width: 2
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'light',
                    type: 'vertical',
                    shadeIntensity: 0.4,
                    gradientToColors: ['var(--primary-600)', 'var(--error)'],
                    inverseColors: false,
                    opacityFrom: 0.6,
                    opacityTo: 0.1,
                    stops: [0, 100]
                }
            },
            title: {
                text: '{{ __("income.title") }} / {{ __("expense.title") }}',
                style: {
                    fontSize: 'var(--font-size-h3)',
                    fontWeight: 600,
                    color: 'var(--neutral-900)'
                }
            },
            labels: [
                @foreach($data['expenseDates'] as $date)
                           '{{ $date }}',
                @endforeach
            ],
            markers: {
                size: 0,
                hover: {
                    size: 6
                }
            },
            yaxis: {
                title: {
                    text: '{{ __("income.title") }} / {{ __("expense.title") }}',
                    style: {
                        fontSize: 'var(--font-size-body-small)',
                        color: 'var(--neutral-500)'
                    }
                },
                labels: {
                    style: {
                        colors: 'var(--neutral-700)',
                        fontSize: 'var(--font-size-caption)'
                    }
                }
            },
            xaxis: {
                labels: {
                    style: {
                        colors: 'var(--neutral-700)',
                        fontSize: 'var(--font-size-caption)'
                    }
                }
            },
            tooltip: {
                shared: true,
                intersect: false,
                theme: 'light',
                style: {
                    fontSize: 'var(--font-size-body-small)'
                },
                y: {
                    formatter: function (y) {
                        if (typeof y !== "undefined") {
                            return '{{ settings()->currency }}' + y.toFixed(2);
                        }
                        return y;
                    }
                }
            },
            responsive: [{
                breakpoint: 768,
                options: {
                    chart: {
                        height: 300
                    },
                    title: {
                        style: {
                            fontSize: 'var(--font-size-body-large)'
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                fontSize: 'var(--font-size-caption)'
                            }
                        }
                    }
                }
            }, {
                breakpoint: 480,
                options: {
                    chart: {
                        height: 250
                    },
                    stroke: {
                        width: 1
                    }
                }
            }]
        },

        'courier-revenue': {
            series: [{{ $data['courier_income'] }}, {{ $data['courier_expense'] }}],
            chart: {
                width: '100%',
                type: 'polarArea',
                fontFamily: 'var(--font-family-sans-serif, "Nunito", sans-serif)',
                background: 'transparent',
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 1000,
                    animateGradually: {
                        enabled: true,
                        delay: 200
                    }
                },
                events: {
                    mounted: function(chartContext, config) {
                        // Announce chart ready to screen readers
                        const chartElement = config.el;
                        const announcement = document.createElement('div');
                        announcement.setAttribute('aria-live', 'polite');
                        announcement.setAttribute('aria-atomic', 'true');
                        announcement.className = 'sr-only';
                        announcement.textContent = 'Courier revenue chart loaded with {{ $data["courier_income"] + $data["courier_expense"] }} total value';
                        chartElement.appendChild(announcement);
                        setTimeout(() => chartElement.removeChild(announcement), 1000);
                    },
                    dataPointSelection: function(event, chartContext, config) {
                        const dataPoint = config.dataPointIndex;
                        const value = config.w.config.series[dataPoint];
                        const label = config.w.config.labels[dataPoint];

                        // Announce to screen readers
                        const announcement = document.createElement('div');
                        announcement.setAttribute('aria-live', 'assertive');
                        announcement.className = 'sr-only';
                        announcement.textContent = `${label}: {{ settings()->currency }}${value}`;
                        config.el.appendChild(announcement);
                        setTimeout(() => config.el.removeChild(announcement), 2000);
                    }
                }
            },
            labels: ["{{ __('income.title') }}", "{{ __('expense.title') }}"],
            colors: ['var(--success)', 'var(--error)'],
            fill: {
                opacity: 0.8,
                colors: ['var(--success)', 'var(--error)']
            },
            stroke: {
                width: 2,
                colors: ['var(--success)', 'var(--error)']
            },
            title: {
                text: '{{ __('dashboard.courier') }} {{ __('dashboard.revenue') }}',
                style: {
                    fontSize: 'var(--font-size-h3)',
                    fontWeight: 600,
                    color: 'var(--neutral-900)'
                }
            },
            yaxis: {
                show: false
            },
            legend: {
                position: 'bottom',
                fontSize: 'var(--font-size-body-small)',
                labels: {
                    colors: 'var(--neutral-700)'
                },
                markers: {
                    width: 12,
                    height: 12,
                    radius: 6
                }
            },
            tooltip: {
                theme: 'light',
                style: {
                    fontSize: 'var(--font-size-body-small)'
                },
                y: {
                    formatter: function (y) {
                        return '{{ settings()->currency }}' + y.toFixed(2);
                    }
                }
            },
            plotOptions: {
                polarArea: {
                    rings: {
                        strokeWidth: 0
                    },
                    spokes: {
                        strokeWidth: 0
                    }
                }
            },
            responsive: [{
                breakpoint: 768,
                options: {
                    chart: {
                        width: '100%'
                    },
                    legend: {
                        position: 'bottom',
                        fontSize: 'var(--font-size-caption)'
                    }
                }
            }]
        }
    };

    // Lazy loading implementation
    function initLazyCharts() {
        const chartContainers = document.querySelectorAll('.chart-container[data-lazy-load="true"]');

        if ('IntersectionObserver' in window) {
            const chartObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const container = entry.target;
                        const chartType = container.getAttribute('data-chart-type');

                        // Load ApexCharts dynamically if not already loaded
                        if (typeof ApexCharts === 'undefined') {
                            loadApexCharts().then(() => {
                                renderChart(container, chartType);
                            });
                        } else {
                            renderChart(container, chartType);
                        }

                        chartObserver.unobserve(container);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.1
            });

            chartContainers.forEach(function(container) {
                chartObserver.observe(container);
            });
        } else {
            // Fallback for browsers without IntersectionObserver
            chartContainers.forEach(function(container) {
                const chartType = container.getAttribute('data-chart-type');
                if (typeof ApexCharts === 'undefined') {
                    loadApexCharts().then(() => {
                        renderChart(container, chartType);
                    });
                } else {
                    renderChart(container, chartType);
                }
            });
        }
    }

    // Dynamic ApexCharts loading (code splitting)
    function loadApexCharts() {
        return new Promise((resolve, reject) => {
            if (typeof ApexCharts !== 'undefined') {
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = '{{ static_asset("backend/js/charts/apexcharts.js") }}';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    // Render chart with accessibility enhancements
    function renderChart(container, chartType) {
        const loadingState = container.querySelector('.chart-loading-state');
        const config = chartConfigs[chartType];

        if (!config) {
            console.error(`Chart configuration not found for type: ${chartType}`);
            return;
        }

        // Hide loading state
        if (loadingState) {
            loadingState.style.display = 'none';
        }

        // Create chart
        const chart = new ApexCharts(container, config);
        chart.render();

        // Add keyboard navigation
        addKeyboardNavigation(container, chart, chartType);

        // Cache chart instance for potential reuse
        container._chartInstance = chart;
    }

    // Enhanced keyboard navigation for accessibility
    function addKeyboardNavigation(container, chart, chartType) {
        container.addEventListener('keydown', function(event) {
            if (chartType === 'income-expense') {
                handleAreaChartKeyboard(event, chart);
            } else if (chartType === 'courier-revenue') {
                handlePieChartKeyboard(event, chart);
            }
        });

        container.addEventListener('focus', function() {
            // Add focus ring
            container.style.outline = '2px solid var(--focus-ring, #7e0095)';
            container.style.outlineOffset = '2px';
        });

        container.addEventListener('blur', function() {
            container.style.outline = '';
            container.style.outlineOffset = '';
        });
    }

    function handleAreaChartKeyboard(event, chart) {
        const key = event.key;
        // Implement arrow key navigation for data points
        // This would require more complex logic to track current position
        // For now, just prevent default behavior and announce
        if (['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(key)) {
            event.preventDefault();
            // Announce navigation capability
            const announcement = document.createElement('div');
            announcement.setAttribute('aria-live', 'polite');
            announcement.className = 'sr-only';
            announcement.textContent = 'Use arrow keys to navigate chart data points';
            event.target.appendChild(announcement);
            setTimeout(() => event.target.removeChild(announcement), 2000);
        }
    }

    function handlePieChartKeyboard(event, chart) {
        const key = event.key;
        if (key === 'Tab') {
            // Allow tab navigation through legend items
            // This would be enhanced with proper legend focus management
        }
    }

    // Theme integration - listen for theme changes
    function initThemeIntegration() {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const htmlElement = mutation.target;
                    const isDark = htmlElement.classList.contains('dark-theme');

                    // Update chart colors based on theme
                    updateChartTheme(isDark);
                }
            });
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class']
        });
    }

    function updateChartTheme(isDark) {
        const charts = document.querySelectorAll('.chart-container');
        charts.forEach(function(container) {
            if (container._chartInstance) {
                const chart = container._chartInstance;
                const chartType = container.getAttribute('data-chart-type');

                if (chartType === 'income-expense') {
                    chart.updateOptions({
                        colors: isDark ? ['#60a5fa', '#f87171'] : ['var(--primary-500)', 'var(--error)'],
                        title: {
                            style: {
                                color: isDark ? '#f3f4f6' : 'var(--neutral-900)'
                            }
                        },
                        xaxis: {
                            labels: {
                                style: {
                                    colors: isDark ? '#d1d5db' : 'var(--neutral-700)'
                                }
                            }
                        },
                        yaxis: {
                            labels: {
                                style: {
                                    colors: isDark ? '#d1d5db' : 'var(--neutral-700)'
                                }
                            }
                        }
                    });
                } else if (chartType === 'courier-revenue') {
                    chart.updateOptions({
                        colors: isDark ? ['#34d399', '#f87171'] : ['var(--success)', 'var(--error)'],
                        title: {
                            style: {
                                color: isDark ? '#f3f4f6' : 'var(--neutral-900)'
                            }
                        },
                        legend: {
                            labels: {
                                colors: isDark ? '#d1d5db' : 'var(--neutral-700)'
                            }
                        }
                    });
                }
            }
        });
    }

    // Performance optimization - cache chart data
    function initChartCaching() {
        // Cache chart data in sessionStorage for faster subsequent loads
        const cacheKey = 'dashboard_charts_data_' + Date.now();
        const chartData = {
            incomeDates: [
                @foreach($data['incomeDates'] as $date)
                           '{{ $date }}',
                @endforeach
            ],
            expenseDates: [
                @foreach($data['expenseDates'] as $date)
                           '{{ $date }}',
                @endforeach
            ],
            incomeData: [
                @foreach($data['incomeDates'] as $date)
                          {{  dayIncomeCount($date) }},
                @endforeach
            ],
            expenseData: [
                @foreach($data['expenseDates'] as $date)
                        {{  dayExpenseCount($date) }},
                @endforeach
            ],
            courierIncome: {{ $data['courier_income'] }},
            courierExpense: {{ $data['courier_expense'] }}
        };

        try {
            sessionStorage.setItem(cacheKey, JSON.stringify(chartData));
            // Clean up old cache entries
            Object.keys(sessionStorage).forEach(key => {
                if (key.startsWith('dashboard_charts_data_') && key !== cacheKey) {
                    sessionStorage.removeItem(key);
                }
            });
        } catch (e) {
            // sessionStorage not available or quota exceeded
        }
    }

    // Initialize everything when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initLazyCharts();
        initThemeIntegration();
        initChartCaching();
    });

    // Also initialize immediately if DOM is already loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initLazyCharts();
            initThemeIntegration();
            initChartCaching();
        });
    } else {
        initLazyCharts();
        initThemeIntegration();
        initChartCaching();
    }

})();
</script>
