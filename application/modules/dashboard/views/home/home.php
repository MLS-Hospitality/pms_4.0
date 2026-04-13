<!-- Professional Dashboard with Enhanced UI/UX -->
<style>
/* Professional Design System */
:root {
    --card-radius: 16px;
    --border-color: rgba(0,0,0,0.06);
    --card-bg: #ffffff;
    --shadow-sm: 0 2px 4px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.06);
    --shadow-md: 0 4px 12px rgba(0,0,0,0.08), 0 2px 4px rgba(0,0,0,0.06);
    --shadow-lg: 0 10px 30px rgba(0,0,0,0.12), 0 4px 8px rgba(0,0,0,0.08);
    --shadow-hover: 0 8px 24px rgba(0,0,0,0.12), 0 4px 8px rgba(0,0,0,0.08);
    --text-primary: #1a202c;
    --text-secondary: #4a5568;
    --text-muted: #718096;
    --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --gradient-success: linear-gradient(135deg, #10b981 0%, #059669 100%);
    --gradient-warning: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    --gradient-info: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    --gradient-danger: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

/* Enhanced Widget Cards */
.widget-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid var(--border-color);
    border-radius: var(--card-radius);
    background: var(--card-bg);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    position: relative;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
}

.widget-card::before {
    display: none;
}

.widget-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-hover);
    border-color: rgba(0,0,0,0.1);
}

/* Clickable Widget Links */
a.widget-link {
    display: block;
    text-decoration: none;
    color: inherit;
    cursor: pointer;
}

a.widget-link:hover {
    text-decoration: none;
    color: inherit;
}

a.widget-link .widget-card {
    cursor: pointer;
}

.widget-card:hover::before {
    display: none;
}

/* Enhanced Widget Icons */
.widget-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

.widget-icon::before {
    display: none;
}

.widget-card:hover .widget-icon {
    transform: scale(1.1) rotate(5deg);
    box-shadow: 0 6px 20px rgba(0,0,0,0.2);
}

.widget-card:hover .widget-icon::before {
    display: none;
}

/* Trend Indicators */
.trend-indicator {
    font-size: 11px;
    padding: 4px 10px;
    border-radius: 8px;
    font-weight: 600;
    letter-spacing: 0.025em;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
}

.trend-up {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
    border: 1px solid #6ee7b7;
}

.trend-down {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #991b1b;
    border: 1px solid #fca5a5;
}

/* Activity Items */
.activity-item {
    padding: 16px;
    border-left: 3px solid #e5e7eb;
    margin-bottom: 12px;
    background: linear-gradient(90deg, #f9fafb 0%, #ffffff 100%);
    border-radius: 0 10px 10px 0;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.activity-item::before {
    display: none;
}

.activity-item:hover {
    transform: translateX(4px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border-left-color: #667eea;
}

.activity-item:hover::before {
    display: none;
}

/* Room Type Items */
.room-type-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 0;
    border-bottom: 1px solid #f3f4f6;
    transition: all 0.2s ease;
}

.room-type-item:hover {
    background: #f9fafb;
    padding-left: 12px;
    padding-right: 12px;
    margin-left: -12px;
    margin-right: -12px;
    border-radius: 8px;
}

/* Enhanced Gradient Colors */
.bg-gradient-violet {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.bg-gradient-green {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.bg-gradient-pink {
    background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
}

.bg-gradient-blue {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.bg-gradient-soft {
    background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
}

.bg-gradient-rose {
    background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);
}

.bg-gradient-peach {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.bg-gradient-lavender {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
}

/* List Enhancements */
.inbox-item {
    padding: 16px;
    margin-bottom: 12px;
    background: linear-gradient(90deg, #ffffff 0%, #f9fafb 100%);
    border-radius: 12px;
    border: 1px solid #f3f4f6;
    transition: all 0.3s ease;
}

.inbox-item:hover {
    transform: translateX(4px);
    box-shadow: var(--shadow-md);
    border-color: #e5e7eb;
}

.inbox-item-text i.material-icons {
    font-size: 16px;
    vertical-align: middle;
    margin-right: 8px;
    color: var(--text-muted);
    opacity: 0.7;
}

.kpi-empty {
    padding: 40px 24px;
    text-align: center;
    color: var(--text-muted);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.kpi-empty i.material-icons {
    font-size: 48px;
    opacity: 0.3;
}

/* Enhanced KPI Header Layout */
.widget-card .card-header {
    display: grid;
    grid-template-columns: auto 1fr;
    grid-column-gap: 20px;
    align-items: center;
    padding: 24px 28px;
    text-align: left !important;
    border: none;
    background: transparent;
    position: relative;
}

.widget-card .card-header .card-icon {
    margin: 0;
}

.widget-card .card-category {
    margin: 0;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--text-secondary);
    opacity: 0.8;
}

.widget-card .card-title {
    margin: 6px 0 0 0;
    font-size: clamp(22px, 2.8vw, 32px);
    font-weight: 800;
    color: var(--text-primary);
    font-variant-numeric: tabular-nums;
    letter-spacing: -0.5px;
    background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.widget-card .card-footer {
    border-top: 1px solid #f1f5f9;
    background: linear-gradient(135deg, #fafbfc 0%, #f8f9fa 100%);
    padding: 14px 28px;
}

.widget-card .stats {
    display: flex;
    align-items: center;
    font-size: 13px;
    color: var(--text-muted);
    font-weight: 500;
}

.widget-card .stats i {
    margin-right: 8px;
    font-size: 18px;
    opacity: 0.7;
}

/* Enhanced Card Styling */
.card {
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-sm);
    background: var(--card-bg);
    border-radius: var(--card-radius);
    transition: all 0.3s ease;
    overflow: hidden;
}

.card:hover {
    box-shadow: var(--shadow-md);
    border-color: rgba(0,0,0,0.1);
}

.card-header {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-bottom: 1px solid #f1f5f9;
    padding: 20px 24px;
    position: relative;
}

.card-header::after {
    display: none;
}

.card:hover .card-header::after {
    display: none;
}

.card-header h6 {
    font-weight: 700;
    color: var(--text-primary);
    letter-spacing: -0.3px;
}

.card-body {
    background: var(--card-bg);
    padding: 24px;
}

/* Chart Container Enhancements */
#apexMixedChart,
#apexPieCharts {
    position: relative;
}

/* Responsive Design */
@media (max-width: 768px) {
    .widget-icon {
        width: 48px;
        height: 48px;
        font-size: 20px;
    }

    .widget-card .card-title {
        font-size: clamp(20px, 2.4vw, 26px) !important;
    }

    .widget-card .card-header {
        padding: 20px 24px;
        grid-column-gap: 16px;
    }

    .widget-card .card-footer {
        padding: 12px 24px;
    }

    .card-body {
        padding: 20px;
    }
}

@media (max-width: 480px) {
    .widget-card .card-header {
        padding: 16px 20px;
    }

    .widget-icon {
        width: 44px;
        height: 44px;
        font-size: 18px;
    }
}

/* Smooth Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.widget-card {
    animation: fadeInUp 0.6s ease-out;
}

.widget-card:nth-child(1) { animation-delay: 0.1s; }
.widget-card:nth-child(2) { animation-delay: 0.2s; }
.widget-card:nth-child(3) { animation-delay: 0.3s; }
.widget-card:nth-child(4) { animation-delay: 0.4s; }

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
    .widget-card,
    .activity-item,
    .inbox-item {
        transition: none;
        animation: none;
    }

    .widget-card:hover {
        transform: none;
    }
}

/* Scrollbar Styling */
.message_widgets::-webkit-scrollbar,
.message_widgets2::-webkit-scrollbar,
.message_widgets3::-webkit-scrollbar {
    width: 6px;
}

.message_widgets::-webkit-scrollbar-track,
.message_widgets2::-webkit-scrollbar-track,
.message_widgets3::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}

.message_widgets::-webkit-scrollbar-thumb,
.message_widgets2::-webkit-scrollbar-thumb,
.message_widgets3::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
}

.message_widgets::-webkit-scrollbar-thumb:hover,
.message_widgets2::-webkit-scrollbar-thumb:hover,
.message_widgets3::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
}

/* Minimal Chart Cards */
.chart-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 0;
    box-shadow: none;
    transition: none;
    overflow: hidden;
    position: relative;
}

.chart-card::before {
    display: none;
}

.chart-card:hover {
    transform: none;
    box-shadow: none;
    border-color: #e5e7eb;
}

.chart-card .card-header {
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
    padding: 20px 24px;
    position: relative;
}

.chart-card .card-header h6 {
    font-size: 14px;
    font-weight: 500;
    color: #4a5568;
    letter-spacing: 0;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
    text-transform: uppercase;
}

.chart-card .card-header h6 i {
    font-size: 18px;
    color: #718096;
    opacity: 0.7;
}

.chart-card .card-body {
    padding: 20px 24px;
    background: #ffffff;
}

/* Grid Payment Categories Layout */
.payment-categories-legend {
    padding: 0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
}

.payment-category-item {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-left: 3px solid;
    border-radius: 0;
    padding: 16px;
    transition: background-color 0.2s ease;
    display: flex;
    flex-direction: column;
}

.payment-category-item:hover {
    background: #f8f9fa;
}

.payment-category-item i.material-icons {
    font-size: 20px;
    margin-bottom: 8px;
    opacity: 0.7;
}

.payment-category-item strong {
    font-size: 13px;
    font-weight: 500;
    color: #4a5568;
    letter-spacing: 0;
    margin-bottom: 8px;
    text-transform: uppercase;
}

.payment-category-item .category-amount {
    font-size: 16px;
    font-weight: 600;
    letter-spacing: 0;
    margin-bottom: 4px;
}

.payment-category-item .category-percentage {
    font-size: 11px;
    font-weight: 500;
    opacity: 0.6;
    display: inline-block;
    padding: 2px 6px;
    background: transparent;
    border-radius: 4px;
}

/* Enhanced Total Payments Box */
.payment-total-box {
    background: #1a202c;
    border-radius: 0;
    padding: 20px 24px;
    color: white;
    margin-top: 16px;
    box-shadow: none;
    border: none;
    grid-column: 1 / -1;
}

.payment-total-box::before {
    display: none;
}

.payment-total-box i.material-icons {
    font-size: 20px;
    vertical-align: middle;
    margin-right: 10px;
    opacity: 0.8;
}

.payment-total-box strong {
    font-size: 14px;
    font-weight: 500;
    letter-spacing: 0;
    opacity: 0.9;
}

.payment-total-box h3,
.payment-total-box h4 {
    font-size: 22px;
    font-weight: 600;
    margin: 0;
    letter-spacing: 0;
    text-shadow: none;
}

/* Chart Container Enhancements */
#apexPieCharts,
#paymentCategoriesChart {
    position: relative;
}

.chart-container-wrapper {
    min-height: 320px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: #ffffff;
    border-radius: 0;
    position: relative;
}

.payment-chart-wrapper {
    min-height: 320px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: #ffffff;
    border-radius: 0;
    position: relative;
}

.chart-container-wrapper::after {
    display: none;
}

/* Responsive Chart Improvements */
@media (max-width: 992px) {
    .chart-card .card-header {
        padding: 20px 24px;
    }

    .chart-card .card-body {
        padding: 24px;
    }

    .payment-categories-legend {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 10px;
    }

    .payment-category-item {
        padding: 14px;
    }

    .payment-total-box {
        padding: 20px 24px;
    }

    .payment-chart-wrapper {
        min-height: 350px;
        padding: 30px 20px;
    }
}

@media (max-width: 768px) {
    .chart-card .card-header h6 {
        font-size: 16px;
    }

    .payment-categories-legend {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }

    .payment-category-item {
        padding: 12px;
    }

    .payment-category-item strong {
        font-size: 12px;
    }

    .payment-category-item .category-amount {
        font-size: 14px;
    }

    .payment-category-item i.material-icons {
        font-size: 18px;
        margin-bottom: 6px;
    }

    .payment-total-box {
        padding: 18px 20px;
    }

    .payment-total-box h3,
    .payment-total-box h4 {
        font-size: 20px;
    }

    .payment-total-box strong {
        font-size: 14px;
    }

    .payment-chart-wrapper {
        min-height: 300px;
        padding: 20px 15px;
    }

    .chart-container-wrapper {
        min-height: 300px;
        padding: 20px 15px;
    }
}

@media (max-width: 480px) {
    .payment-categories-legend {
        grid-template-columns: 1fr;
        gap: 8px;
    }
}
</style>

<!-- Primary KPI Widgets Row -->
<div class="row">
    <!-- Today's Paid Amount -->
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <a href="<?php echo base_url('reports/payment-today'); ?>" class="widget-link">
            <div class="card widget-card mb-4">
                <div class="card-header card-header-success card-header-icon position-relative border-0 text-right px-3 py-0">
                    <div class="card-icon d-flex align-items-center justify-content-center">
                        <div class="widget-icon bg-gradient-green">
                            <i class="material-icons">attach_money</i>
                        </div>
                    </div>
                    <p class="card-category text-uppercase fs-10 font-weight-bold text-muted">Today's Paid Amount</p>
                    <h3 class="card-title fs-18 font-weight-bold">
                        <?php if($currency->position==1){echo $currency->curr_icon;}?>
                        <?php echo html_escape(number_format($todayPaidAmount ?? 0, 2));?>
                        <?php if($currency->position==2){echo $currency->curr_icon;}?>
                    </h3>
                </div>
                <div class="card-footer p-3">
                    <div class="stats">
                        <i class="material-icons text-success">trending_up</i>
                        <span class="text-muted">Daily revenue</span>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Pending Amount -->
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <a href="<?php echo base_url('room_reservation/booking-pending'); ?>" class="widget-link">
            <div class="card widget-card mb-4">
                <div class="card-header card-header-warning card-header-icon position-relative border-0 text-right px-3 py-0">
                    <div class="card-icon d-flex align-items-center justify-content-center">
                        <div class="widget-icon bg-gradient-pink">
                            <i class="material-icons">hourglass_empty</i>
                        </div>
                    </div>
                    <p class="card-category text-uppercase fs-10 font-weight-bold text-muted">Pending Amount</p>
                    <h3 class="card-title fs-18 font-weight-bold">
                        <?php if($currency->position==1){echo $currency->curr_icon;}?>
                        <?php echo html_escape(number_format($pendingAmount ?? 0, 2));?>
                        <?php if($currency->position==2){echo $currency->curr_icon;}?>
                    </h3>
                </div>
                <div class="card-footer p-3">
                    <div class="stats">
                        <i class="material-icons text-warning">schedule</i>
                        <span class="text-muted">Awaiting payment</span>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Total Completed Bookings -->
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <a href="<?php echo base_url('room_reservation/booking-list'); ?>" class="widget-link">
            <div class="card widget-card mb-4">
                <div class="card-header card-header-info card-header-icon position-relative border-0 text-right px-3 py-0">
                    <div class="card-icon d-flex align-items-center justify-content-center">
                        <div class="widget-icon bg-gradient-blue">
                            <i class="material-icons">check_circle</i>
                        </div>
                    </div>
                    <p class="card-category text-uppercase fs-10 font-weight-bold text-muted">Total Bookings</p>
                    <h3 class="card-title fs-21 font-weight-bold"><?php echo html_escape($totalCompletedBookings);?></h3>
                </div>
                <div class="card-footer p-3">
                    <div class="stats">
                        <i class="material-icons text-info">done_all</i>
                        <span class="text-muted">All time</span>
                    </div>
                </div>
            </div>
        </a>
    </div>
	 <!-- Current Month Revenue -->
	 <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <a href="<?php echo base_url('reports/booking-report'); ?>" class="widget-link">
            <div class="card widget-card mb-4">
                <div class="card-header card-header-success card-header-icon position-relative border-0 text-right px-3 py-0">
                    <div class="card-icon d-flex align-items-center justify-content-center">
                        <div class="widget-icon bg-gradient-soft">
                            <i class="material-icons">account_balance_wallet</i>
                        </div>
                    </div>
                    <p class="card-category text-uppercase fs-10 font-weight-bold text-muted">Monthly Revenue</p>
                    <h3 class="card-title fs-18 font-weight-bold">
                        <?php if($currency->position==1){echo $currency->curr_icon;}?>
                        <?php echo html_escape(number_format($currentMonthRevenue ?? 0, 2));?>
                        <?php if($currency->position==2){echo $currency->curr_icon;}?>
                    </h3>
                </div>
                <div class="card-footer p-3">
                    <div class="stats">
                        <?php
                        $comparison = $revenueComparison;
                        $trend_class = $comparison['percentage'] >= 0 ? 'trend-up' : 'trend-down';
                        $trend_icon = $comparison['percentage'] >= 0 ? 'trending_up' : 'trending_down';
                        ?>
                        <span class="trend-indicator <?php echo $trend_class; ?>">
                            <?php echo ($comparison['percentage'] >= 0 ? '+' : '') . $comparison['percentage']; ?>%
                        </span>
                        <span class="text-muted ml-2">vs last month</span>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Secondary KPI Widgets Row -->
<div class="row">


    <!-- Occupancy Rate -->
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <a href="<?php echo base_url('room_reservation/room-status'); ?>" class="widget-link">
            <div class="card widget-card mb-4">
                <div class="card-header card-header-primary card-header-icon position-relative border-0 text-right px-3 py-0">
                    <div class="card-icon d-flex align-items-center justify-content-center">
                        <div class="widget-icon bg-gradient-rose">
                            <i class="material-icons">hotel</i>
                        </div>
                    </div>
                    <p class="card-category text-uppercase fs-10 font-weight-bold text-muted">Occupancy Rate</p>
                    <h3 class="card-title fs-21 font-weight-bold"><?php echo html_escape($todayOccupancyRate);?>%</h3>
                </div>
                <div class="card-footer p-3">
                    <div class="stats">
                        <i class="material-icons text-primary">business</i>
                        <span class="text-muted">Today's rate</span>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Today's Check-ins/Check-outs -->
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <a href="<?php echo base_url('room_reservation/checkin-list'); ?>" class="widget-link">
            <div class="card widget-card mb-4">
                <div class="card-header card-header-info card-header-icon position-relative border-0 text-right px-3 py-0">
                    <div class="card-icon d-flex align-items-center justify-content-center">
                        <div class="widget-icon bg-gradient-lavender">
                            <i class="material-icons">swap_horiz</i>
                        </div>
                    </div>
                    <p class="card-category text-uppercase fs-10 font-weight-bold text-muted">Check-ins / Check-outs</p>
                    <h3 class="card-title fs-21 font-weight-bold"><?php echo html_escape($todayCheckIns);?> / <?php echo html_escape($todayCheckOuts);?></h3>
                </div>
                <div class="card-footer p-3">
                    <div class="stats">
                        <i class="material-icons text-info">today</i>
                        <span class="text-muted">Today's activity</span>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Combined Restaurant & F&B Operations Section -->
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="fs-17 font-weight-600 mb-0">Restaurant & F&B Operations</h6>
            </div>
        </div>
    </div>
</div>

<!-- Combined Operations Overview Widgets -->
<div class="row">
    <!-- Today's Total Orders -->
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <a href="<?php echo base_url('ordermanage/order-today'); ?>" class="widget-link">
            <div class="card widget-card mb-4">
                <div class="card-header card-header-primary card-header-icon position-relative border-0 text-right px-3 py-0">
                    <div class="card-icon d-flex align-items-center justify-content-center">
                        <div class="widget-icon bg-gradient-violet">
                            <i class="material-icons">restaurant_menu</i>
                        </div>
                    </div>
                    <p class="card-category text-uppercase fs-10 font-weight-bold text-muted">Today's Orders</p>
                    <h3 class="card-title fs-21 font-weight-bold"><?php echo html_escape($todayRestaurantOrdersAll ?? 0);?></h3>
                </div>
                <div class="card-footer p-3">
                    <div class="stats">
                        <i class="material-icons text-primary">today</i>
                        <span class="text-muted">Active + Pending + Paid</span>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Today's Total Revenue (Orders + F&B) -->
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <a href="<?php echo base_url('ordermanage/order-list'); ?>" class="widget-link">
            <div class="card widget-card mb-4">
                <div class="card-header card-header-success card-header-icon position-relative border-0 text-right px-3 py-0">
                    <div class="card-icon d-flex align-items-center justify-content-center">
                        <div class="widget-icon bg-gradient-green">
                            <i class="material-icons">attach_money</i>
                        </div>
                    </div>
                    <p class="card-category text-uppercase fs-10 font-weight-bold text-muted">Today's Total Revenue</p>
                    <h3 class="card-title fs-18 font-weight-bold">
                        <?php
                        // Only show completed/paid orders for today
                        $todayTotalRevenue = ($todayPaidRestaurantRevenue ?? 0) + ($todayPaidFoodRevenue ?? 0) + ($todayPaidBeverageRevenue ?? 0);
                        if($currency->position==1){echo $currency->curr_icon;}?>
                        <?php echo html_escape(number_format($todayTotalRevenue, 2));?>
                        <?php if($currency->position==2){echo $currency->curr_icon;}?>
                    </h3>
                </div>
                <div class="card-footer p-3">
                    <div class="stats">
                        <i class="material-icons text-success">trending_up</i>
                        <span class="text-muted">Completed paid orders only</span>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Total Orders for the Month -->
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <a href="<?php echo base_url('ordermanage/order-list'); ?>" class="widget-link">
            <div class="card widget-card mb-4">
                <div class="card-header card-header-warning card-header-icon position-relative border-0 text-right px-3 py-0">
                    <div class="card-icon d-flex align-items-center justify-content-center">
                        <div class="widget-icon bg-gradient-pink">
                            <i class="material-icons">calendar_today</i>
                        </div>
                    </div>
                    <p class="card-category text-uppercase fs-10 font-weight-bold text-muted">Total Orders for the Month</p>
                    <h3 class="card-title fs-21 font-weight-bold"><?php echo html_escape($currentMonthRestaurantOrders ?? 0);?></h3>
                </div>
                <div class="card-footer p-3">
                    <div class="stats">
                        <i class="material-icons text-warning">date_range</i>
                        <span class="text-muted">Past orders this month</span>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Monthly Total Revenue -->
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <a href="<?php echo base_url('ordermanage/order-list'); ?>" class="widget-link">
            <div class="card widget-card mb-4">
                <div class="card-header card-header-info card-header-icon position-relative border-0 text-right px-3 py-0">
                    <div class="card-icon d-flex align-items-center justify-content-center">
                        <div class="widget-icon bg-gradient-blue">
                            <i class="material-icons">calendar_month</i>
                        </div>
                    </div>
                    <p class="card-category text-uppercase fs-10 font-weight-bold text-muted">Monthly Revenue</p>
                    <h3 class="card-title fs-18 font-weight-bold">
                        <?php
                        // Total sales for the whole month
                        $monthlyTotalRevenue = ($currentMonthRestaurantRevenue ?? 0) + ($currentMonthFoodRevenue ?? 0) + ($currentMonthBeverageRevenue ?? 0);
                        if($currency->position==1){echo $currency->curr_icon;}?>
                        <?php echo html_escape(number_format($monthlyTotalRevenue, 2));?>
                        <?php if($currency->position==2){echo $currency->curr_icon;}?>
                    </h3>
                </div>
                <div class="card-footer p-3">
                    <div class="stats">
                        <i class="material-icons text-info">date_range</i>
                        <span class="text-muted">Total sales this month</span>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>


<!-- Charts and Analytics Row -->
<div class="row">
    <div class="col-lg-8">
        <!--Basic apexMixedChart Chart-->
        <div class="card chart-card mb-4">
            <div class="card-header">
                <h6 class="fs-17 font-weight-600 mb-0">
                    <i class="material-icons">show_chart</i>
                    Revenue & Booking Trends
                </h6>
            </div>
            <div class="card-body">
                <div id="apexMixedChart"></div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Top Room Types -->
        <div class="card chart-card mb-4">
            <div class="card-header">
                <h6 class="fs-17 font-weight-600 mb-0">
                    <i class="material-icons">star</i>
                    Top Performing Room Types
                </h6>
            </div>
            <div class="card-body">
                <?php if(!empty($topRoomTypes)): ?>
                    <?php foreach($topRoomTypes as $roomType): ?>
                        <div class="room-type-item">
                            <div>
                                <strong><?php echo html_escape($roomType->roomtype ?: 'Standard'); ?></strong>
                                <br><small class="text-muted"><?php echo html_escape($roomType->bookings); ?> bookings</small>
                            </div>
                            <div class="text-right">
                                <strong>
                            <?php if($currency->position==1){echo $currency->curr_icon;}?>
                            <?php echo html_escape(number_format($roomType->revenue ?? 0, 0)); ?>
                            <?php if($currency->position==2){echo $currency->curr_icon;}?>
                        </strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No data available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Total Booking History and Payment Categories Analytics Row -->
<div class="row">
    <!-- Total Booking History -->
    <div class="col-lg-6 col-md-6 col-xl-6">
        <div class="card chart-card height_400 mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fs-17 font-weight-600 mb-0">
                            <i class="material-icons">bar_chart</i>
                            <?php echo display('total_booking_history')?>
                        </h6>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-container-wrapper">
                    <div id="apexPieCharts"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Categories Analytics Chart -->
    <div class="col-lg-6 col-md-6 col-xl-6">
        <div class="card chart-card mb-4" style="min-height: 500px;">
            <div class="card-header">
                <h6 class="fs-17 font-weight-600 mb-0">
                    <i class="material-icons">pie_chart</i>
                    Payment Categories Analytics
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Chart Section - Full Width on Mobile, Half on Desktop -->
                    <div class="col-lg-12 col-md-12 mb-4 mb-lg-0">
                        <div class="payment-chart-wrapper">
                            <canvas id="paymentCategoriesChart" style="max-width: 100%; height: auto;"></canvas>
                        </div>
                    </div>

                    <!-- Category Breakdown - Full Width Below Chart -->
                    <div class="col-lg-12 col-md-12">
                        <div class="payment-categories-legend">
                            <?php
                            $payments = isset($categorizedPayments) ? $categorizedPayments : ['booking' => 0, 'f_b' => 0, 'pools' => 0, 'parking' => 0, 'other' => 0];
                            $total = isset($categorizedPayments['total']) ? $categorizedPayments['total'] : 0;
                            $currency = isset($currency) ? $currency : (object)['curr_icon' => '$', 'position' => 1];

                            $categories = [
                                'booking' => ['label' => 'Booking', 'icon' => 'hotel', 'color' => '#667eea'],
                                'f_b' => ['label' => 'Food & Beverage', 'icon' => 'restaurant', 'color' => '#10b981'],
                                'pools' => ['label' => 'Pools', 'icon' => 'pool', 'color' => '#3b82f6'],
                                'parking' => ['label' => 'Parking', 'icon' => 'local_parking', 'color' => '#f59e0b'],
                                'other' => ['label' => 'Other', 'icon' => 'more_horiz', 'color' => '#8b5cf6']
                            ];

                            foreach ($categories as $key => $cat):
                                $amount = isset($payments[$key]) ? floatval($payments[$key]) : 0;
                                $percentage = $total > 0 ? round(($amount / $total) * 100, 1) : 0;
                            ?>
                            <div class="payment-category-item" style="border-left-color: <?php echo $cat['color']; ?>;">
                                <i class="material-icons" style="color: <?php echo $cat['color']; ?>;"><?php echo $cat['icon']; ?></i>
                                <strong><?php echo $cat['label']; ?></strong>
                                <div class="category-amount" style="color: <?php echo $cat['color']; ?>;">
                                    <?php if($currency->position==1){echo $currency->curr_icon;}?>
                                    <?php echo number_format($amount, 2); ?>
                                    <?php if($currency->position==2){echo $currency->curr_icon;}?>
                                </div>
                                <div class="category-percentage" style="color: <?php echo $cat['color']; ?>;">
                                    <?php echo $percentage; ?>%
                                </div>
                            </div>
                            <?php endforeach; ?>

                            <div class="payment-total-box">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <i class="material-icons">account_balance_wallet</i>
                                        <strong style="font-size: 18px;">Total Payments</strong>
                                    </div>
                                    <div class="text-right">
                                        <h3 class="mb-0 font-weight-bold" style="font-size: 28px;">
                                            <?php if($currency->position==1){echo $currency->curr_icon;}?>
                                            <?php echo number_format($total, 2); ?>
                                            <?php if($currency->position==2){echo $currency->curr_icon;}?>
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden inputs for chart data -->
<input type="hidden" id="monthlytotalamount" value="<?php echo html_escape($monthlytotalamount);?>">
<input type="hidden" id="monthlytotalorder" value="<?php echo html_escape($monthlytotalorder);?>">
<input type="hidden" id="monthlytotalpending" value="<?php echo html_escape($monthlytotalpending);?>">
<input type="hidden" id="monthlytotal" value="<?php echo html_escape($monthlytotal);?>">
<input type="hidden" id="monthname" value='<?php echo html_escape($monthname);?>'>
<input type="hidden" id="shortmonthname" value='<?php echo html_escape($shortmonthname);?>'>
<input type="hidden" id="totalorder" value='<?php echo html_escape($totalorder);?>'>
<input type="hidden" id="totalcheckin" value='<?php echo html_escape($totalcheckin);?>'>
<input type="hidden" id="totalpending" value='<?php echo html_escape($totalpending);?>'>
<input type="hidden" id="totalcancel" value='<?php echo html_escape($totalcancel);?>'>

<!-- Payment Categories Chart Data -->
<?php
$paymentData = isset($categorizedPayments) ? $categorizedPayments : ['booking' => 0, 'f_b' => 0, 'pools' => 0, 'parking' => 0, 'other' => 0];
?>
<input type="hidden" id="payment_booking" value="<?php echo html_escape($paymentData['booking'] ?? 0); ?>">
<input type="hidden" id="payment_f_b" value="<?php echo html_escape($paymentData['f_b'] ?? 0); ?>">
<input type="hidden" id="payment_pools" value="<?php echo html_escape($paymentData['pools'] ?? 0); ?>">
<input type="hidden" id="payment_parking" value="<?php echo html_escape($paymentData['parking'] ?? 0); ?>">
<input type="hidden" id="payment_other" value="<?php echo html_escape($paymentData['other'] ?? 0); ?>">

<script src="<?php echo MOD_URL.$module;?>/assets/js/Chart.min.js" type="text/javascript"></script>
<script src="<?php echo MOD_URL.$module;?>/assets/js/barchart.js"></script>
<script src="<?php echo MOD_URL.$module;?>/assets/js/payment_categories_chart.js"></script>
<script src="<?php echo MOD_URL.$module;?>/assets/js/apexcharts.min.js"></script>
<script src="<?php echo MOD_URL.$module;?>/assets/js/apexcharts.active.js"></script>
