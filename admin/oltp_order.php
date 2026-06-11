<?php
require_once __DIR__ . '/../connection.php';
header('Content-Type: text/html; charset=utf-8');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo 'Invalid order id';
    exit;
}

$allowedStatuses = ['pending', 'completed', 'cancelled'];
$updateMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status']) && in_array($_POST['status'], $allowedStatuses, true)) {
    $newStatus = $_POST['status'];
    $conn->begin_transaction();
    $stmt = $conn->prepare('UPDATE orders SET status = ? WHERE id = ?');
    $stmt->bind_param('si', $newStatus, $id);
    if ($stmt->execute()) {
        $conn->commit();
        $updateMessage = 'Order status updated successfully.';
    } else {
        $conn->rollback();
        $updateMessage = 'Failed to update order status: ' . htmlspecialchars($stmt->error);
    }
    $stmt->close();
}

$paymentColumnRes = $conn->query("SHOW COLUMNS FROM orders LIKE 'payment_method'");
$paymentSelect = $paymentColumnRes && $paymentColumnRes->num_rows > 0 ? "COALESCE(o.payment_method, 'Cash on Delivery') AS payment_method" : "'Cash on Delivery' AS payment_method";

$orderStmt = $conn->prepare("SELECT o.id, o.customer_name, o.phone, o.address, o.notes, o.subtotal, o.delivery_fee, o.grand_total, o.status, o.created_at, $paymentSelect FROM orders o WHERE o.id = ?");
$orderStmt->bind_param('i', $id);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();
$order = $orderResult->fetch_assoc();
$orderStmt->close();

if (!$order) {
    echo 'Order not found';
    exit;
}

$itemsStmt = $conn->prepare('SELECT food_name, unit_price, quantity, line_total FROM order_items WHERE order_id = ?');
$itemsStmt->bind_param('i', $id);
$itemsStmt->execute();
$items = $itemsStmt->get_result();
$itemsStmt->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Order #<?php echo htmlspecialchars($order['id']); ?> — OLTP Detail</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="page">
    <div class="topbar">
      <div>
        <h1 class="page-title">Order #<?php echo htmlspecialchars($order['id']); ?></h1>
        <p class="subtitle">Detailed transaction record and status management for this order.</p>
      </div>
      <div>
        <a class="btn" href="oltp.php">Back to Orders</a>
        <a class="btn secondary" href="index.php">Dashboard</a>
      </div>
    </div>

    <?php if ($updateMessage): ?>
      <div class="alert"><?php echo htmlspecialchars($updateMessage); ?></div>
    <?php endif; ?>

    <div class="card-grid">
      <div class="card">
        <h2>Buyer & Delivery</h2>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
        <p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($order['address'])); ?></p>
        <p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
        <p><strong>Payment:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
        <p><strong>Placed:</strong> <?php echo htmlspecialchars($order['created_at']); ?></p>
      </div>
      <div class="card">
        <h2>Order Summary</h2>
        <p><strong>Status:</strong> <span class="badge <?php echo $order['status'] === 'completed' ? 'badge-green' : ($order['status'] === 'pending' ? 'badge-yellow' : 'badge-red'); ?>"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span></p>
        <p><strong>Subtotal:</strong> ₱<?php echo number_format((float)$order['subtotal'], 2); ?></p>
        <p><strong>Delivery:</strong> ₱<?php echo number_format((float)$order['delivery_fee'], 2); ?></p>
        <p><strong>Total:</strong> ₱<?php echo number_format((float)$order['grand_total'], 2); ?></p>
        <form method="post">
          <label for="status">Update Status</label>
          <select id="status" name="status">
            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
          </select>
          <button class="btn" type="submit">Save status</button>
        </form>
      </div>
    </div>

    <div class="card">
      <h2>Items</h2>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Item</th>
              <th>Unit Price</th>
              <th>Qty</th>
              <th>Line Total</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($item = $items->fetch_assoc()): ?>
              <tr>
                <td><?php echo htmlspecialchars($item['food_name']); ?></td>
                <td>₱<?php echo number_format((float)$item['unit_price'], 2); ?></td>
                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                <td>₱<?php echo number_format((float)$item['line_total'], 2); ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <p class="footer">This page uses transactional updates and rollback protection for safe order management.</p>
  </div>
</body>
</html>
