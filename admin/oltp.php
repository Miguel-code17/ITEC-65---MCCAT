<?php
require_once __DIR__ . '/../connection.php';
// View recent orders
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>OLTP - Recent Orders</title>
  <style>body{font-family:Arial,Helvetica,sans-serif;margin:20px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:8px}</style>
</head>
<body>
<h1>Recent Orders (OLTP)</h1>
<?php
$res = $conn->query("SELECT o.id,o.customer_name,o.phone,o.created_at,o.grand_total,o.status FROM orders o ORDER BY o.created_at DESC LIMIT 50");
if (!$res) { echo '<p>Error: '.htmlspecialchars($conn->error).'</p>'; exit; }
echo '<table><thead><tr><th>Order</th><th>Customer</th><th>Phone</th><th>Created</th><th>Total</th><th>Status</th></tr></thead><tbody>';
while ($row = $res->fetch_assoc()) {
  echo '<tr>';
  echo '<td><a href="oltp_order.php?id='.urlencode($row['id']).'">#'.htmlspecialchars($row['id']).'</a></td>';
  echo '<td>'.htmlspecialchars($row['customer_name']).'</td>';
  echo '<td>'.htmlspecialchars($row['phone']).'</td>';
  echo '<td>'.htmlspecialchars($row['created_at']).'</td>';
  echo '<td>'.htmlspecialchars($row['grand_total']).'</td>';
  echo '<td>'.htmlspecialchars($row['status']).'</td>';
  echo '</tr>';
}
echo '</tbody></table>';
?>
<p><a href="olap.php">View OLAP summary</a></p>
</body>
</html>
