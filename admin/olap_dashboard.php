<?php
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../scripts/olap_etl.php';

// Security headers
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Input validation for date range
$start_date = isset($_GET['start_date']) ? date('Y-m-d', strtotime($_GET['start_date'])) : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? date('Y-m-d', strtotime($_GET['end_date'])) : date('Y-m-d');

// Ensure dates are valid
if ($start_date > $end_date) {
    $temp = $start_date;
    $start_date = $end_date;
    $end_date = $temp;
}

// Fetch KPI data
$kpi_query = "
    SELECT 
        SUM(total_orders) as total_orders,
        SUM(total_revenue) as total_revenue,
        SUM(total_items) as total_items,
        COUNT(*) as days_counted,
        AVG(avg_order_value) as avg_order_value
    FROM daily_sales 
    WHERE sales_date BETWEEN ? AND ?
";

$stmt = $conn->prepare($kpi_query);
if (!$stmt) {
    die('Error: ' . htmlspecialchars($conn->error));
}
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$kpi = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch daily sales for chart
$daily_sales_query = "
    SELECT sales_date, total_revenue, total_orders, completed_orders
    FROM daily_sales
    WHERE sales_date BETWEEN ? AND ?
    ORDER BY sales_date ASC
";
$stmt = $conn->prepare($daily_sales_query);
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$daily_sales_result = $stmt->get_result();

$daily_dates = [];
$daily_revenue = [];
$daily_orders = [];

while ($row = $daily_sales_result->fetch_assoc()) {
    $daily_dates[] = $row['sales_date'];
    $daily_revenue[] = (float)$row['total_revenue'];
    $daily_orders[] = (int)$row['total_orders'];
}
$stmt->close();

// Fetch top products
$top_products_query = "
    SELECT food_name, SUM(units_sold) as total_units, SUM(revenue) as total_revenue
    FROM product_daily_sales
    WHERE sales_date BETWEEN ? AND ?
    GROUP BY food_id, food_name
    ORDER BY total_revenue DESC
    LIMIT 10
";
$stmt = $conn->prepare($top_products_query);
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$top_products_result = $stmt->get_result();

$product_names = [];
$product_revenue = [];

while ($row = $top_products_result->fetch_assoc()) {
    $product_names[] = $row['food_name'];
    $product_revenue[] = (float)$row['total_revenue'];
}
$stmt->close();

// Fetch category performance
$category_query = "
    SELECT category, total_revenue, total_units, order_count
    FROM category_performance
    ORDER BY total_revenue DESC
    LIMIT 8
";
$category_result = $conn->query($category_query);

$categories = [];
$cat_revenue = [];

while ($row = $category_result->fetch_assoc()) {
    $categories[] = $row['category'];
    $cat_revenue[] = (float)$row['total_revenue'];
}

// Convert data to JSON for charts
$json_dates = json_encode($daily_dates);
$json_revenue = json_encode($daily_revenue);
$json_orders = json_encode($daily_orders);
$json_products = json_encode($product_names);
$json_product_revenue = json_encode($product_revenue);
$json_categories = json_encode($categories);
$json_cat_revenue = json_encode($cat_revenue);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OLAP Analytics Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* OLAP Dashboard Overrides */
        body { background: var(--off-white); padding: 24px 0; }
        .dashboard-header {
            padding: 3rem 0;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            margin-bottom: 2rem;
        }
        .dashboard-header h1 { color: var(--white); margin: 0; font-size: 2.2rem; }
        .dashboard-header p { color: rgba(255,255,255,0.85); margin-top: 0.5rem; }
        .container { max-width: 1300px; margin: 0 auto; padding: 0 1.5rem; }
        
        /* Date Filter */
        .date-filter {
            background: var(--white);
            padding: 2rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            align-items: center;
            margin-bottom: 2rem;
        }
        .date-filter form { display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: center; width: 100%; }
        .date-filter label { color: var(--gray-600); font-weight: 600; font-size: 0.95rem; }
        .date-filter input {
            padding: 0.85rem 1.2rem;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            transition: var(--transition);
        }
        .date-filter input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(26, 122, 60, 0.1); }
        .date-filter button, .date-filter a.btn {
            padding: 0.85rem 1.5rem;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        .date-filter button { background: var(--primary); color: var(--white); }
        .date-filter button:hover { background: var(--primary-dark); }
        
        /* KPI Cards */
        .kpi-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        .kpi-card {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary);
        }
        .kpi-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-2px); }
        .kpi-card h3 {
            color: var(--gray-600);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin: 0 0 0.8rem;
            font-weight: 700;
        }
        .kpi-card .value { font-size: 2rem; font-weight: 800; color: var(--primary); }
        
        /* Charts Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(480px, 1fr));
            gap: 2rem;
            margin-bottom: 2.5rem;
        }
        .chart-container {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 1.8rem;
            box-shadow: var(--shadow-md);
            position: relative;
            height: 420px;
        }
        .chart-container h3 {
            color: var(--gray-900);
            margin: 0 0 1.5rem;
            font-size: 1.1rem;
            font-weight: 700;
        }
        .chart-wrapper { position: relative; height: 350px; width: 100%; }
        
        /* Action Buttons */
        .actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin: 2.5rem 0;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.95rem 1.8rem;
            border: none;
            border-radius: var(--radius-full);
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.95rem;
        }
        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-2px); box-shadow: var(--shadow-green); }
        .btn-secondary {
            background: var(--gray-200);
            color: var(--gray-800);
        }
        .btn-secondary:hover { background: var(--gray-300); }
        
        /* Footer */
        footer {
            text-align: center;
            margin-top: 2.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-200);
            color: var(--gray-500);
            font-size: 0.9rem;
        }
        
        /* Responsive */
        @media (max-width: 900px) {
            .charts-grid { grid-template-columns: 1fr; }
            .kpi-cards { grid-template-columns: repeat(2, 1fr); }
            .date-filter { flex-direction: column; align-items: stretch; }
            .date-filter form { flex-direction: column; }
            .date-filter input { width: 100%; }
        }
        @media (max-width: 640px) {
            .kpi-cards { grid-template-columns: 1fr; }
            .dashboard-header h1 { font-size: 1.8rem; }
            .date-filter { padding: 1.5rem; }
            .chart-container { height: 350px; padding: 1.2rem; }
            .chart-wrapper { height: 280px; }
        }
    </style>
</head>
<body>
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <h1>📊 Analytics Dashboard</h1>
            <p>Real-time OLAP insights and business intelligence</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Date Filter -->
        <div class="date-filter">
            <form method="GET">
                <label>Start Date:</label>
                <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                
                <label>End Date:</label>
                <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                
                <button type="submit">Apply Filter</button>
                <a href="?start_date=<?php echo date('Y-m-d', strtotime('-7 days')); ?>&end_date=<?php echo date('Y-m-d'); ?>" class="btn btn-secondary">Last 7 Days</a>
                <a href="?start_date=<?php echo date('Y-m-d', strtotime('-30 days')); ?>&end_date=<?php echo date('Y-m-d'); ?>" class="btn btn-secondary">Last 30 Days</a>
            </form>
        </div>

        <!-- KPI Cards -->
        <div class="kpi-cards">
            <div class="kpi-card">
                <h3>Total Orders</h3>
                <div class="value"><?php echo number_format((int)($kpi['total_orders'] ?? 0)); ?></div>
            </div>
            <div class="kpi-card">
                <h3>Total Revenue</h3>
                <div class="value">₱<?php echo number_format((float)($kpi['total_revenue'] ?? 0), 2); ?></div>
            </div>
            <div class="kpi-card">
                <h3>Items Sold</h3>
                <div class="value"><?php echo number_format((int)($kpi['total_items'] ?? 0)); ?></div>
            </div>
            <div class="kpi-card">
                <h3>Avg Order Value</h3>
                <div class="value">₱<?php echo number_format((float)($kpi['avg_order_value'] ?? 0), 2); ?></div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-grid">
            <div class="chart-container">
                <h3>📈 Daily Revenue Trend</h3>
                <div class="chart-wrapper">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
            
            <div class="chart-container">
                <h3>🛒 Daily Orders</h3>
                <div class="chart-wrapper">
                    <canvas id="ordersChart"></canvas>
                </div>
            </div>

            <div class="chart-container" style="grid-column: 1 / -1;">
                <h3>🏆 Top 10 Products by Revenue</h3>
                <div class="chart-wrapper">
                    <canvas id="productsChart"></canvas>
                </div>
            </div>

            <div class="chart-container">
                <h3>🍕 Revenue by Category</h3>
                <div class="chart-wrapper">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="actions">
            <a href="olap_export.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&format=pdf" class="btn btn-primary">📄 Export PDF</a>
            <a href="olap_export.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&format=csv" class="btn btn-primary">📊 Export CSV</a>
            <a href="olap_export.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&format=excel" class="btn btn-primary">📈 Export Excel</a>
            <a href="oltp.php" class="btn btn-secondary">View OLTP Orders</a>
            <a href="../index.php" class="btn btn-secondary">🏠 Back to Home</a>
        </div>

        <footer>
            <p>Last Updated: <?php echo date('Y-m-d H:i:s'); ?> | OLAP Dashboard v2.0 with Modern UI/UX</p>
        </footer>
    </div>

    <script>
        // Chart color scheme - matching front-page design
        const chartColors = {
            primary: 'rgba(26, 122, 60, 1)',
            primaryLight: 'rgba(39, 174, 96, 1)',
            accent: 'rgba(243, 156, 18, 1)',
            fill: 'rgba(26, 122, 60, 0.08)',
            gridLine: 'rgba(215, 216, 219, 0.3)'
        };

        const chartDefaults = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        font: { family: "'Poppins', sans-serif", size: 13, weight: 600 },
                        color: '#6b7280',
                        padding: 15,
                        usePointStyle: true
                    }
                }
            },
            scales: {
                x: { grid: { color: chartColors.gridLine }, ticks: { color: '#9ca3af' } },
                y: { grid: { color: chartColors.gridLine }, ticks: { color: '#9ca3af' } }
            }
        };

        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo $json_dates; ?>,
                datasets: [{
                    label: 'Daily Revenue (₱)',
                    data: <?php echo $json_revenue; ?>,
                    borderColor: chartColors.primary,
                    backgroundColor: chartColors.fill,
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: chartColors.primary,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                ...chartDefaults,
                scales: {
                    ...chartDefaults.scales,
                    y: {
                        ...chartDefaults.scales.y,
                        ticks: { 
                            callback: function(v) { return '₱' + v.toLocaleString(); },
                            color: '#9ca3af'
                        }
                    }
                }
            }
        });

        // Orders Chart
        const ordersCtx = document.getElementById('ordersChart').getContext('2d');
        new Chart(ordersCtx, {
            type: 'bar',
            data: {
                labels: <?php echo $json_dates; ?>,
                datasets: [{
                    label: 'Daily Orders',
                    data: <?php echo $json_orders; ?>,
                    backgroundColor: chartColors.primary,
                    borderColor: chartColors.primary,
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: chartDefaults
        });

        // Top Products Chart
        const productsCtx = document.getElementById('productsChart').getContext('2d');
        new Chart(productsCtx, {
            type: 'bar',
            data: {
                labels: <?php echo $json_products; ?>,
                datasets: [{
                    label: 'Revenue (₱)',
                    data: <?php echo $json_product_revenue; ?>,
                    backgroundColor: [
                        'rgba(26, 122, 60, 0.9)',
                        'rgba(39, 174, 96, 0.9)',
                        'rgba(243, 156, 18, 0.9)',
                        'rgba(26, 122, 60, 0.7)',
                        'rgba(39, 174, 96, 0.7)',
                        'rgba(243, 156, 18, 0.7)',
                        'rgba(26, 122, 60, 0.5)',
                        'rgba(39, 174, 96, 0.5)',
                        'rgba(243, 156, 18, 0.5)',
                        'rgba(26, 122, 60, 0.4)'
                    ],
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                ...chartDefaults,
                indexAxis: 'y',
                scales: {
                    ...chartDefaults.scales,
                    x: {
                        ...chartDefaults.scales.x,
                        ticks: {
                            callback: function(v) { return '₱' + v.toLocaleString(); },
                            color: '#9ca3af'
                        }
                    }
                }
            }
        });

        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo $json_categories; ?>,
                datasets: [{
                    data: <?php echo $json_cat_revenue; ?>,
                    backgroundColor: [
                        'rgba(26, 122, 60, 0.95)',
                        'rgba(39, 174, 96, 0.95)',
                        'rgba(243, 156, 18, 0.95)',
                        'rgba(26, 122, 60, 0.75)',
                        'rgba(39, 174, 96, 0.75)',
                        'rgba(243, 156, 18, 0.75)',
                        'rgba(26, 122, 60, 0.55)',
                        'rgba(39, 174, 96, 0.55)'
                    ],
                    borderColor: '#fff',
                    borderWidth: 3
                }]
            },
            options: {
                ...chartDefaults,
                plugins: {
                    ...chartDefaults.plugins,
                    legend: {
                        ...chartDefaults.plugins.legend,
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
