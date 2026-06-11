<?php
require_once __DIR__ . '/../connection.php';
header('Content-Type: text/html; charset=utf-8');

$summarySql = "SELECT COUNT(*) AS total_orders, IFNULL(SUM(grand_total),0) AS total_revenue, COUNT(DISTINCT phone) AS total_customers FROM orders";
$summary = $conn->query($summarySql)->fetch_assoc() ?: ['total_orders' => 0, 'total_revenue' => 0, 'total_customers' => 0];
$productCount = $conn->query("SELECT COUNT(DISTINCT food_name) AS total_products FROM order_items")->fetch_assoc()['total_products'] ?? 0;
$dwExists = false;
$dwCounts = ['total_dates' => 0, 'total_products' => 0, 'total_customers' => 0, 'total_facts' => 0];
$dwStatus = $conn->query("SHOW DATABASES LIKE 'ordering_dw'");
if ($dwStatus && $dwStatus->num_rows > 0) {
    $dwExists = true;
    $dwQuery = $conn->query(
        "SELECT
             (SELECT COUNT(*) FROM ordering_dw.dim_date) AS total_dates,
             (SELECT COUNT(*) FROM ordering_dw.dim_product) AS total_products,
             (SELECT COUNT(*) FROM ordering_dw.dim_customer) AS total_customers,
             (SELECT COUNT(*) FROM ordering_dw.fact_sales) AS total_facts"
    );
    if ($dwQuery) {
        $dwCounts = $dwQuery->fetch_assoc();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Dashboard — MCCAT</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="page">
    <div class="topbar">
      <div>
        <h1 class="page-title">MCCAT Admin Dashboard</h1>
        <p class="subtitle">OLTP transaction monitoring, OLAP analytics, and ETL orchestration in one interface.</p>
      </div>
      <div>
        <a class="btn secondary" href="oltp.php">View OLTP Orders</a>
        <a class="btn" href="olap.php">View OLAP Insights</a>
      </div>
    </div>

    <div class="card-grid">
      <div class="card">
        <small>OLTP orders</small>
        <h2><?php echo number_format((int)$summary['total_orders']); ?></h2>
        <p>Total transactional orders recorded in the operational database.</p>
      </div>
      <div class="card">
        <small>OLTP revenue</small>
        <h2>₱<?php echo number_format((float)$summary['total_revenue'], 2); ?></h2>
        <p>Sum of completed order values captured in the live system.</p>
      </div>
      <div class="card">
        <small>Customers</small>
        <h2><?php echo number_format((int)$summary['total_customers']); ?></h2>
        <p>Unique buyer phone numbers in the transactional dataset.</p>
      </div>
      <div class="card">
        <small>Menu items sold</small>
        <h2><?php echo number_format((int)$productCount); ?></h2>
        <p>Distinct products found in order item records.</p>
      </div>
    </div>

    <div class="topbar" style="margin-bottom: 16px;">
      <div>
        <h2 class="page-title">OLAP Warehouse Status</h2>
      </div>
      <div>
        <a class="btn outline" href="run_etl.php">Run ETL</a>
      </div>
    </div>

    <?php if (!$dwExists): ?>
      <div class="alert">The OLAP database <strong>ordering_dw</strong> is not initialized yet. Run the ETL process to create the warehouse tables and load analytics data.</div>
    <?php endif; ?>

    <div class="card-grid">
      <div class="card">
        <small>Dates loaded</small>
        <h2><?php echo number_format((int)$dwCounts['total_dates']); ?></h2>
        <p>Distinct date dimension rows available for roll-up and drill-down.</p>
      </div>
      <div class="card">
        <small>Products modeled</small>
        <h2><?php echo number_format((int)$dwCounts['total_products']); ?></h2>
        <p>Product dimension rows derived from transactional order items.</p>
      </div>
      <div class="card">
        <small>Customers modeled</small>
        <h2><?php echo number_format((int)$dwCounts['total_customers']); ?></h2>
        <p>Buyer dimension rows created from transactional orders.</p>
      </div>
      <div class="card">
        <small>Fact rows</small>
        <h2><?php echo number_format((int)$dwCounts['total_facts']); ?></h2>
        <p>Fact table rows that power OLAP aggregation queries.</p>
      </div>
    </div>

    <div class="card" style="margin-top: 24px;">
      <h2>System coverage</h2>
      <p>This admin interface supports transactional order management (OLTP), fact and dimension modeling for analytics (OLAP), and report-ready dashboard summaries.</p>
      <ul>
        <li>Component 1: OLTP & transaction tracking</li>
        <li>Component 2: Multidimensional OLAP data warehouse</li>
        <li>Component 3: Visual analytics and reporting</li>
        <li>Component 4: Technical documentation through visible schema and process status</li>
      </ul>
    </div>

    <p class="footer">Tip: Seed sample orders with <code>php scripts/sample_data.php</code> and then run the ETL at <code>admin/run_etl.php</code>.</p>
  </div>
</body>
</html>
