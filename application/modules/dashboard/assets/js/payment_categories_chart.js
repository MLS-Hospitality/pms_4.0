/**
 * Payment Categories Chart
 * Renders a pie/doughnut chart showing payments categorized by type:
 * - Booking
 * - Food & Beverage
 * - Pools
 * - Parking
 * - Other
 */
$(document).ready(function() {
    'use strict';
    
    // Get payment data from hidden inputs
    var bookingAmount = parseFloat($('#payment_booking').val()) || 0;
    var fbAmount = parseFloat($('#payment_f_b').val()) || 0;
    var poolsAmount = parseFloat($('#payment_pools').val()) || 0;
    var parkingAmount = parseFloat($('#payment_parking').val()) || 0;
    var otherAmount = parseFloat($('#payment_other').val()) || 0;
    
    // Prepare data arrays
    var paymentData = [bookingAmount, fbAmount, poolsAmount, parkingAmount, otherAmount];
    var paymentLabels = ['Booking', 'Food & Beverage', 'Pools', 'Parking', 'Other'];
    var paymentColors = [
        'rgba(102, 126, 234, 0.8)',  // Booking - Violet
        'rgba(16, 185, 129, 0.8)',   // F&B - Green
        'rgba(59, 130, 246, 0.8)',   // Pools - Blue
        'rgba(245, 158, 11, 0.8)',   // Parking - Orange
        'rgba(139, 92, 246, 0.8)'    // Other - Purple
    ];
    var paymentBorderColors = [
        'rgba(102, 126, 234, 1)',
        'rgba(16, 185, 129, 1)',
        'rgba(59, 130, 246, 1)',
        'rgba(245, 158, 11, 1)',
        'rgba(139, 92, 246, 1)'
    ];
    
    // Check if we have any data
    var totalAmount = paymentData.reduce(function(a, b) { return a + b; }, 0);
    
    if (totalAmount > 0) {
        // Get canvas element
        var ctx = document.getElementById('paymentCategoriesChart');
        
        if (ctx) {
            // Create the chart
            var paymentChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: paymentLabels,
                    datasets: [{
                        label: 'Payment Amount',
                        data: paymentData,
                        backgroundColor: paymentColors,
                        borderColor: paymentBorderColors,
                        borderWidth: 2,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1.5,
                    plugins: {
                        legend: {
                            display: false // We have custom legend in sidebar
                        },
                        tooltip: {
                            enabled: true,
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    var value = context.parsed || 0;
                                    var total = context.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                                    var percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return label + ': ' + formatCurrency(value) + ' (' + percentage + '%)';
                                }
                            },
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: true,
                            boxPadding: 6
                        }
                    },
                    animation: {
                        animateRotate: true,
                        animateScale: true,
                        duration: 1000,
                        easing: 'easeOutQuart'
                    },
                    cutout: '60%', // Makes it a doughnut chart
                    elements: {
                        arc: {
                            borderWidth: 2
                        }
                    }
                }
            });
            
            // Note: Center text plugin removed for compatibility with Chart.js 2.x
            // The total amount is displayed in the sidebar legend instead
        }
    } else {
        // Show message if no data
        var ctx = document.getElementById('paymentCategoriesChart');
        if (ctx) {
            var chartContainer = ctx.parentElement;
            chartContainer.innerHTML = '<div class="text-center p-5"><i class="material-icons" style="font-size: 48px; color: #cbd5e0;">pie_chart</i><p class="mt-3 text-muted">No payment data available</p></div>';
        }
    }
    
    /**
     * Format currency value
     * This function formats numbers as currency
     */
    function formatCurrency(value) {
        // Get currency settings from page if available
        var currencyIcon = '$'; // Default
        var currencyPosition = 1; // 1 = before, 2 = after
        
        // Try to get from page context
        if (typeof currency !== 'undefined') {
            currencyIcon = currency.curr_icon || '$';
            currencyPosition = currency.position || 1;
        }
        
        var formatted = parseFloat(value).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        
        if (currencyPosition == 1) {
            return currencyIcon + formatted;
        } else {
            return formatted + currencyIcon;
        }
    }
});

