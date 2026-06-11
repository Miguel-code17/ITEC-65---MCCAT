<?php
require_once __DIR__ . '/../connection.php';
header('Content-Type: text/html; charset=utf-8');

$hasPaymentMethod = false;
$colRes = $conn->query("SHOW COLUMNS FROM orders LIKE 'payment_method'");
if ($colRes && $colRes->num_rows > 0) {
    $hasPaymentMethod = true;
}
$paymentSelect = $hasPaymentMethod ? "COALESCE(o.payment_method, 'Cash on Delivery') AS payment_method" : "'Cash on Delivery' AS payment_method";

$summaryRes = $conn->query(
    "SELECT COUNT(*) AS total_orders,
            SUM(grand_total) AS total_revenue,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_orders,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_orders
     FROM orders"
);
$summary = $summaryRes ? $summaryRes->fetch_assoc() : ['total_orders' => 0, 'total_revenue' => 0, 'pending_orders' => 0, 'completed_orders' => 0];

$res = $conn->query(
    "SELECT o.id, o.customer_name, o.phone, o.created_at, o.grand_total, o.status, $paymentSelect
     FROM orders o
     ORDER BY o.created_at DESC
     LIMIT 50"
);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>OLTP - Recent Orders</title>
  <style>
    :root {
      font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      color: #1f2937;
      background: #f8fafc;
      line-height: 1.6;
    }
    body { margin: 0; padding: 0; }
    .page { max-width: 1200px; margin: 0 auto; padding: 24px; }
    .topbar { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 16px; align-items: center; margin-bottom: 24px; }
    .page-title { margin: 0; font-size: clamp(1.8rem, 2.5vw, 2.4rem); }
    .subtitle { margin: 8px 0 0; color: #4b5563; }
    .cards { display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 24px; }
    .card { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 18px; padding: 20px; box-shadow: 0 16px 40px rgba(15, 23, 42, 0.05); }
    .card strong { display: block; font-size: 0.9rem; color: #6b7280; margin-bottom: 8px; }
    .card span { display: block; font-size: 1.65rem; font-weight: 700; color: #111827; }
    .table-container { overflow-x: auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 20px; }
    table { width: 100%; border-collapse: collapse; min-width: 800px; }
    th, td { text-align: left; padding: 16px; }
    th { background: #f9fafb; color: #4b5563; text-transform: uppercase; letter-spacing: 0.03em; font-size: 0.8rem; border-bottom: 1px solid #e5e7eb; }
    tr { border-bottom: 1px solid #f3f4f6; }
    tr:last-child { border-bottom: none; }
    td { color: #374151; }
    a.order-link { color: #2563eb; text-decoration: none; font-weight: 600; }
    a.order-link:hover { text-decoration: underline; }
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      padding: 6px 10px;
      border-radius: 999px;
      font-size: 0.8rem;
      font-weight: 700;
    }
    .badge-pending { background: #fef3c7; color: #92400e; }
    .badge-completed { background: #d1fae5; color: #166534; }
    .badge-cancelled { background: #fee2e2; color: #991b1b; }
    .controls { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 12px; align-items: center; }
    .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: none; border-radius: 999px; padding: 12px 20px; font-weight: 700; text-decoration: none; color: #ffffff; background: #2563eb; cursor: pointer; }
    .btn.secondary { background: #475569; }
    .btn:hover { opacity: 0.95; }
    .page-footer { margin-top: 28px; color: #6b7280; font-size: 0.95rem; }
    @media (max-width: 820px) {
      th, td { padding: 12px; }
      .topbar { flex-direction: column; align-items: flex-start; }
    }
  </style>
</head>
<body>
  <div class="page">
    <div class="topbar">
      <div>
        <h1 class="page-title">Recent Orders (OLTP)</h1>
        <p class="subtitle">Track buyer transactions, payment method, and delivery status at a glance.</p>
      </div>
      <div class="controls">
        <a class="btn secondary" href="olap.php">View OLAP summary</a>
      </div>
    </div>

    <div class="cards">
      <div class="card">
        <strong>Total orders</strong>
        <span><?php echo number_format((int)$summary['total_orders'], 0); ?></span>
      </div>
      <div class="card">
        <strong>Revenue</strong>
        <span>₱<?php echo number_format((float)$summary['total_revenue'], 2); ?></span>
      </div>
      <div class="card">
        <strong>Pending</strong>
        <span><?php echo number_format((int)$summary['pending_orders'], 0); ?></span>
      </div>
      <div class="card">
        <strong>Completed</strong>
        <span><?php echo number_format((int)$summary['completed_orders'], 0); ?></span>
      </div>
    </div>

    <div class="table-container">
      <?php if (!$res): ?>
        <p style="padding: 20px; color: #991b1b;">Error loading orders: <?php echo htmlspecialchars($conn->error); ?></p>
      <?php else: ?>
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
            <?php while ($row = $res->fetch_assoc()): ?>
              <?php
                $status = strtolower($row['status']);
                $badgeClass = 'badge-pending';
                if ($status === 'completed') { $badgeClass = 'badge-completed'; }
                if ($status === 'cancelled') { $badgeClass = 'badge-cancelled'; }
              ?>
              <tr>
                <td><a class="order-link" href="oltp_order.php?id=<?php echo urlencode($row['id']); ?>">#<?php echo htmlspecialchars($row['id']); ?></a></td>
                <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars(ucfirst($row['status'])); ?></span></td>
                <td>₱<?php echo number_format((float)$row['grand_total'], 2); ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <p class="page-footer">Delivery history and transaction details are available when you open an order.</p>
  </div>
</body>
</html>
