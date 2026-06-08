<?php
require_once __DIR__ . '/../connection.php';
header('Content-Type: text/html; charset=utf-8');
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { echo 'Invalid order id'; exit; }
$stmt = $conn->prepare("SELECT id,customer_name,phone,address,notes,subtotal,delivery_fee,grand_total,status,created_at FROM orders WHERE id = ?");
$stmt->bind_param('i',$id);
$stmt->execute();
$res = $stmt->get_result();
$order = $res->fetch_assoc();
if (!$order) { echo 'Order not found'; exit; }
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Order #<?php echo htmlspecialchars($order['id']);?></title>
<style>body{font-family:Arial,Helvetica,sans-serif;margin:20px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:8px}</style>
</head><body>
<h1>Order #<?php echo htmlspecialchars($order['id']);?></h1>
<p><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']);?><br>
<strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']);?><br>
<strong>Address:</strong> <?php echo nl2br(htmlspecialchars($order['address']));?><br>
<strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($order['notes']));?><br>
<strong>Created:</strong> <?php echo htmlspecialchars($order['created_at']);?></p>
<h2>Items</h2>
<?php
$items = $conn->query("SELECT food_name,unit_price,quantity,line_total FROM order_items WHERE order_id = " . intval($order['id']));
echo '<table><thead><tr><th>Item</th><th>Unit</th><th>Qty</th><th>Line</th></tr></thead><tbody>';
while ($it = $items->fetch_assoc()){
  echo '<tr><td>'.htmlspecialchars($it['food_name']).'</td><td>'.htmlspecialchars($it['unit_price']).'</td><td>'.htmlspecialchars($it['quantity']).'</td><td>'.htmlspecialchars($it['line_total']).'</td></tr>';
}
echo '</tbody></table>';
?>
<p><strong>Subtotal:</strong> <?php echo htmlspecialchars($order['subtotal']);?> &nbsp; <strong>Delivery:</strong> <?php echo htmlspecialchars($order['delivery_fee']);?> &nbsp; <strong>Total:</strong> <?php echo htmlspecialchars($order['grand_total']);?></p>
<p><a href="oltp.php">Back to orders</a></p>
</body></html>
