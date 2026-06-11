<?php
require_once __DIR__ . '/../connection.php';
header('Content-Type: text/html; charset=utf-8');

function safeDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d ? $d->format('Y-m-d') : date('Y-m-d');
}

$start_date = isset($_GET['start_date']) ? safeDate($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? safeDate($_GET['end_date']) : date('Y-m-d');

$dwCheck = $conn->query("SHOW DATABASES LIKE 'ordering_dw'");
$dwReady = $dwCheck && $dwCheck->num_rows > 0;

$summary = [
    'total_orders' => 0,
    'total_revenue' => 0,
    'total_items' => 0,
    'avg_order' => 0,
];
$topProducts = [];
$byHour = [];
$byDate = [];

if ($dwReady) {
    $summarySql = $conn->prepare(
        "SELECT
            SUM(total_orders) AS total_orders,
            SUM(total_revenue) AS total_revenue,
            SUM(total_items) AS total_items,
            AVG(avg_order_value) AS avg_order
         FROM ordering_dw.daily_sales
         WHERE sales_date BETWEEN ? AND ?"
    );
    $summarySql->bind_param('ss', $start_date, $end_date);
    $summarySql->execute();
    $summary = $summarySql->get_result()->fetch_assoc() ?: $summary;
    $summarySql->close();

    $topSql = $conn->prepare(
        "SELECT food_name, SUM(units_sold) AS units_sold, SUM(revenue) AS revenue
         FROM ordering_dw.product_daily_sales
         WHERE sales_date BETWEEN ? AND ?
         GROUP BY food_id, food_name
         ORDER BY revenue DESC
         LIMIT 10"
    );
    $topSql->bind_param('ss', $start_date, $end_date);
    $topSql->execute();
    $topProducts = $topSql->get_result()->fetch_all(MYSQLI_ASSOC);
    $topSql->close();

    $hourSql = $conn->prepare(
        "SELECT hour_start, order_count, revenue
         FROM ordering_dw.orders_by_hour
         WHERE hour_start BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
         ORDER BY hour_start ASC
         LIMIT 72"
    );
    $hourSql->bind_param('ss', $start_date, $end_date);
    $hourSql->execute();
    $byHour = $hourSql->get_result()->fetch_all(MYSQLI_ASSOC);
    $hourSql->close();

    $dateSql = $conn->prepare(
        "SELECT sales_date, total_orders, total_revenue, total_items
         FROM ordering_dw.daily_sales
         WHERE sales_date BETWEEN ? AND ?
         ORDER BY sales_date ASC"
    );
    $dateSql->bind_param('ss', $start_date, $end_date);
    $dateSql->execute();
    $byDate = $dateSql->get_result()->fetch_all(MYSQLI_ASSOC);
    $dateSql->close();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>OLAP Insights — MCCAT</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="page">
    <div class="topbar">
      <div>
        <h1 class="page-title">OLAP Insights</h1>
        <p class="subtitle">Multidimensional analytics for sales, products, and time-based reporting.</p>
      </div>
      <div>
        <a class="btn" href="index.php">Dashboard</a>
        <a class="btn secondary" href="oltp.php">OLTP Orders</a>
        <a class="btn outline" href="run_etl.php">Refresh ETL</a>
      </div>
    </div>

    <?php if (!$dwReady): ?>
      <div class="alert">OLAP warehouse not available. Run the ETL process to create <strong>ordering_dw</strong> and load data.</div>
    <?php endif; ?>

    <div class="filter-panel">
      <div class="input-group">
        <label for="start_date">Start Date</label>
        <input id="start_date" type="date" form="rangeForm" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
      </div>
      <div class="input-group">
        <label for="end_date">End Date</label>
        <input id="end_date" type="date" form="rangeForm" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
      </div>
      <div class="input-group" style="align-self:end;">
        <button class="btn" form="rangeForm">Apply range</button>
      </div>
    </div>

    <form id="rangeForm" action="olap.php" method="get" style="display:none;"></form>

    <div class="card-grid">
      <div class="card">
        <small>Total orders</small>
        <h2><?php echo number_format((int)$summary['total_orders']); ?></h2>
      </div>
      <div class="card">
        <small>Total revenue</small>
        <h2>₱<?php echo number_format((float)$summary['total_revenue'], 2); ?></h2>
      </div>
      <div class="card">
        <small>Total items</small>
        <h2><?php echo number_format((int)$summary['total_items']); ?></h2>
      </div>
      <div class="card">
        <small>Avg order value</small>
        <h2>₱<?php echo number_format((float)$summary['avg_order'], 2); ?></h2>
      </div>
    </div>

    <div class="card">
      <h2>Top products</h2>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Item</th><th>Units sold</th><th>Revenue</th></tr>
          </thead>
          <tbody>
            <?php if (!$topProducts): ?>
              <tr><td colspan="3">No OLAP product data found.</td></tr>
            <?php else: ?>
              <?php foreach ($topProducts as $product): ?>
                <tr>
                  <td><?php echo htmlspecialchars($product['food_name']); ?></td>
                  <td><?php echo number_format((int)$product['units_sold']); ?></td>
                  <td>₱<?php echo number_format((float)$product['revenue'], 2); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card-grid">
      <div class="card">
        <h2>Daily time series</h2>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Date</th><th>Orders</th><th>Revenue</th><th>Items</th></tr>
            </thead>
            <tbody>
              <?php if (!$byDate): ?>
                <tr><td colspan="4">No daily sales data found.</td></tr>
              <?php else: ?>
                <?php foreach ($byDate as $row): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($row['sales_date']); ?></td>
                    <td><?php echo number_format((int)$row['total_orders']); ?></td>
                    <td>₱<?php echo number_format((float)$row['total_revenue'], 2); ?></td>
                    <td><?php echo number_format((int)$row['total_items']); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card">
        <h2>Orders by hour</h2>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Hour start</th><th>Orders</th><th>Revenue</th></tr>
            </thead>
            <tbody>
              <?php if (!$byHour): ?>
                <tr><td colspan="3">No hourly data found.</td></tr>
              <?php else: ?>
                <?php foreach ($byHour as $item): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($item['hour_start']); ?></td>
                    <td><?php echo number_format((int)$item['order_count']); ?></td>
                    <td>₱<?php echo number_format((float)$item['revenue'], 2); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <p class="footer">The OLAP warehouse supports roll-up over dates, drill-down into hourly detail, and slicing by selected date ranges.</p>
  </div>
</body>
</html>
