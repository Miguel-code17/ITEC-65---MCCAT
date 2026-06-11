<?php
require_once __DIR__ . '/../connection.php';
header('Content-Type: text/html; charset=utf-8');
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { echo 'Invalid order id'; exit; }

$hasPaymentMethod = false;
$colRes = $conn->query("SHOW COLUMNS FROM orders LIKE 'payment_method'");
if ($colRes && $colRes->num_rows > 0) {
    $hasPaymentMethod = true;
}

$query = "SELECT id,customer_name,phone,address,notes,subtotal,delivery_fee,grand_total,status,created_at";
if ($hasPaymentMethod) {
    $query .= ",COALESCE(payment_method, 'Cash on Delivery') AS payment_method";
} else {
    $query .= ", 'Cash on Delivery' AS payment_method";
}
$query .= " FROM orders WHERE id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param('i',$id);
$stmt->execute();
$res = $stmt->get_result();
$order = $res->fetch_assoc();
if (!$order) { echo 'Order not found'; exit; }

$items = $conn->query("SELECT food_name,unit_price,quantity,line_total FROM order_items WHERE order_id = " . intval($order['id']));
$events = [
    [ 'time' => $order['created_at'], 'label' => 'Order placed', 'status' => 'placed' ],
];
if ($order['status'] === 'completed') {
    $events[] = [ 'time' => date('Y-m-d H:i:s', strtotime($order['created_at'] . ' +30 minutes')), 'label' => 'Order delivered', 'status' => 'completed' ];
} elseif ($order['status'] === 'pending') {
    $events[] = [ 'time' => date('Y-m-d H:i:s', strtotime($order['created_at'] . ' +35 minutes')), 'label' => 'Out for delivery', 'status' => 'shipping' ];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Order #<?php echo htmlspecialchars($order['id']);?></title>
  <style>
    :root { font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #111827; background: #f8fafc; }
    body { margin: 0; padding: 0; }
    .page { max-width: 1100px; margin: 0 auto; padding: 24px; }
    .header { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 16px; align-items: flex-start; margin-bottom: 24px; }
    .header-title { margin: 0; font-size: clamp(1.8rem, 2.4vw, 2.6rem); }
    .status-pill { padding: 10px 16px; border-radius: 999px; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; }
    .status-pending { background: #fef3c7; color: #92400e; }
    .status-completed { background: #d1fae5; color: #166534; }
    .status-cancelled { background: #fee2e2; color: #991b1b; }
    .section { display: grid; grid-template-columns: 1.9fr 1fr; gap: 24px; }
    .card { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 24px; padding: 24px; box-shadow: 0 20px 45px rgba(15, 23, 42, 0.05); }
    .card h2 { margin-top: 0; font-size: 1.25rem; color: #111827; }
    .info-grid { display: grid; gap: 16px; }
    .info-row { display: flex; justify-content: space-between; gap: 12px; padding: 16px; background: #f8fafc; border-radius: 16px; border: 1px solid #e5e7eb; }
    .info-label { color: #6b7280; font-weight: 700; }
    .info-value { color: #111827; }
    .timeline { list-style: none; margin: 0; padding: 0; }
    .timeline-item { display: grid; grid-template-columns: 1.4fr 1fr; gap: 16px; align-items: center; padding: 18px 0; border-bottom: 1px solid #e5e7eb; }
    .timeline-item:last-child { border-bottom: none; }
    .timeline-dot { width: 14px; height: 14px; border-radius: 999px; background: #2563eb; position: relative; }
    .timeline-item::before { content: ''; position: absolute; left: 6px; top: 24px; bottom: -24px; width: 2px; background: #d1d5db; }
    .timeline-entry { position: relative; padding-left: 28px; }
    .timeline-entry h3 { margin: 0 0 4px; font-size: 1rem; }
    .timeline-entry p { margin: 0; color: #6b7280; font-size: 0.95rem; }
    table { width: 100%; border-collapse: collapse; margin-top: 16px; }
    th, td { text-align: left; padding: 14px; border-bottom: 1px solid #e5e7eb; }
    th { background: #f3f4f6; color: #4b5563; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.03em; }
    td { color: #374151; }
    .summary-grid { display: grid; gap: 12px; margin-top: 18px; }
    .summary-row { display: flex; justify-content: space-between; padding: 16px; border-radius: 16px; background: #f8fafc; border: 1px solid #e5e7eb; }
    .summary-row strong { color: #374151; }
    .back-link { display: inline-flex; align-items: center; gap: 8px; margin-top: 20px; color: #2563eb; text-decoration: none; font-weight: 700; }
    @media (max-width: 900px) { .section { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <div class="page">
    <div class="header">
      <div>
        <h1 class="header-title">Order #<?php echo htmlspecialchars($order['id']); ?></h1>
        <p style="color:#6b7280; margin-top: 10px;">Full transaction and delivery timeline for this buyer order.</p>
      </div>
      <div>
        <?php $statusClass = 'status-pending'; if ($order['status'] === 'completed') $statusClass = 'status-completed'; elseif ($order['status'] === 'cancelled') $statusClass = 'status-cancelled'; ?>
        <span class="status-pill <?php echo $statusClass; ?>"><?php echo htmlspecialchars(strtoupper($order['status'])); ?></span>
      </div>
    </div>

    <div class="section">
      <div class="card">
        <h2>Buyer & Delivery</h2>
        <div class="info-grid">
          <div class="info-row"><span class="info-label">Customer</span><span class="info-value"><?php echo htmlspecialchars($order['customer_name']); ?></span></div>
          <div class="info-row"><span class="info-label">Phone</span><span class="info-value"><?php echo htmlspecialchars($order['phone']); ?></span></div>
          <div class="info-row"><span class="info-label">Payment</span><span class="info-value"><?php echo htmlspecialchars($order['payment_method']); ?></span></div>
          <div class="info-row"><span class="info-label">Address</span><span class="info-value"><?php echo nl2br(htmlspecialchars($order['address'])); ?></span></div>
          <div class="info-row"><span class="info-label">Notes</span><span class="info-value"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></span></div>
          <div class="info-row"><span class="info-label">Placed</span><span class="info-value"><?php echo htmlspecialchars($order['created_at']); ?></span></div>
        </div>

        <h2 style="margin-top: 28px;">Transaction History</h2>
        <ul class="timeline">
          <?php foreach ($events as $event): ?>
            <li class="timeline-item">
              <div class="timeline-dot"></div>
              <div class="timeline-entry">
                <h3><?php echo htmlspecialchars($event['label']); ?></h3>
                <p><?php echo htmlspecialchars($event['time']); ?></p>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="card">
        <h2>Order Summary</h2>
        <div class="summary-grid">
          <div class="summary-row"><strong>Subtotal</strong><span>₱<?php echo number_format((float)$order['subtotal'], 2); ?></span></div>
          <div class="summary-row"><strong>Delivery Fee</strong><span>₱<?php echo number_format((float)$order['delivery_fee'], 2); ?></span></div>
          <div class="summary-row"><strong>Total</strong><span>₱<?php echo number_format((float)$order['grand_total'], 2); ?></span></div>
        </div>

        <h2 style="margin-top: 28px;">Items</h2>
        <table>
          <thead>
            <tr><th>Item</th><th>Unit</th><th>Qty</th><th>Total</th></tr>
          </thead>
          <tbody>
            <?php while ($it = $items->fetch_assoc()): ?>
              <tr>
                <td><?php echo htmlspecialchars($it['food_name']); ?></td>
                <td>₱<?php echo number_format((float)$it['unit_price'], 2); ?></td>
                <td><?php echo htmlspecialchars($it['quantity']); ?></td>
                <td>₱<?php echo number_format((float)$it['line_total'], 2); ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>

        <a class="back-link" href="oltp.php">← Back to order list</a>
      </div>
    </div>
  </div>
</body>
</html>
