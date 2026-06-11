<?php
require_once __DIR__ . '/../connection.php';
header('Content-Type: text/html; charset=utf-8');

function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

$start_date = isset($_GET['start_date']) && validateDate($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) && validateDate($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$status = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : '';
$allowedStatuses = ['pending', 'completed', 'cancelled'];
if (!in_array($status, $allowedStatuses, true)) {
    $status = '';
}

$filters = [];
if ($status !== '') {
    $filters[] = "o.status = '" . $conn->real_escape_string($status) . "'";
}
$filters[] = "DATE(o.created_at) BETWEEN '" . $conn->real_escape_string($start_date) . "' AND '" . $conn->real_escape_string($end_date) . "'";
$where = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';

$summarySql = "SELECT
    COUNT(*) AS total_orders,
    IFNULL(SUM(grand_total),0) AS total_revenue,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_orders,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_orders
    FROM orders o $where";
$summary = $conn->query($summarySql)->fetch_assoc() ?: ['total_orders' => 0, 'total_revenue' => 0, 'pending_orders' => 0, 'completed_orders' => 0];

$paymentColumnRes = $conn->query("SHOW COLUMNS FROM orders LIKE 'payment_method'");
$paymentSelect = $paymentColumnRes && $paymentColumnRes->num_rows > 0 ? "COALESCE(o.payment_method, 'Cash on Delivery') AS payment_method" : "'Cash on Delivery' AS payment_method";

$ordersSql = "SELECT o.id, o.customer_name, o.phone, o.created_at, o.grand_total, o.status, $paymentSelect
    FROM orders o $where
    ORDER BY o.created_at DESC
    LIMIT 100";
$orders = $conn->query($ordersSql);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>OLTP — Order Management</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="page">
    <div class="topbar">
      <div>
        <h1 class="page-title">OLTP Order Management</h1>
        <p class="subtitle">View and manage transactional orders with date filtering and status tracking.</p>
      </div>
      <div>
        <a class="btn" href="index.php">Dashboard</a>
        <a class="btn secondary" href="olap.php">OLAP Insights</a>
      </div>
    </div>

    <div class="filter-panel">
      <div class="input-group">
        <label for="start_date">Start Date</label>
        <input id="start_date" type="date" name="start_date" form="filterForm" value="<?php echo htmlspecialchars($start_date); ?>">
      </div>
      <div class="input-group">
        <label for="end_date">End Date</label>
        <input id="end_date" type="date" name="end_date" form="filterForm" value="<?php echo htmlspecialchars($end_date); ?>">
      </div>
      <div class="input-group">
        <label for="status">Order Status</label>
        <select id="status" name="status" form="filterForm">
          <option value="">All</option>
          <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
          <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
          <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
        </select>
      </div>
      <div class="input-group" style="align-self:end;">
        <button class="btn" form="filterForm" type="submit">Apply filter</button>
      </div>
    </div>

    <form id="filterForm" action="oltp.php" method="get" style="display:none;"></form>

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
        <small>Pending</small>
        <h2><?php echo number_format((int)$summary['pending_orders']); ?></h2>
      </div>
      <div class="card">
        <small>Completed</small>
        <h2><?php echo number_format((int)$summary['completed_orders']); ?></h2>
      </div>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Order</th>
            <th>Customer</th>
            <th>Phone</th>
            <th>Created</th>
            <th>Payment</th>
            <th>Status</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$orders || $orders->num_rows === 0): ?>
            <tr><td colspan="7">No orders found for the selected range.</td></tr>
          <?php else: ?>
            <?php while ($order = $orders->fetch_assoc()): ?>
              <?php
                $statusClass = 'badge-yellow';
                if ($order['status'] === 'completed') { $statusClass = 'badge-green'; }
                if ($order['status'] === 'cancelled') { $statusClass = 'badge-red'; }
              ?>
              <tr>
                <td><a class="badge badge-blue" href="oltp_order.php?id=<?php echo urlencode($order['id']); ?>">#<?php echo htmlspecialchars($order['id']); ?></a></td>
                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                <td><?php echo htmlspecialchars($order['phone']); ?></td>
                <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                <td><span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span></td>
                <td>₱<?php echo number_format((float)$order['grand_total'], 2); ?></td>
              </tr>
            <?php endwhile; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <p class="footer">Order list is limited to the most recent 100 records for performance. Use filters to narrow results.</p>
  </div>
</body>
</html>
