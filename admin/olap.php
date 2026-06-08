<?php
require_once __DIR__ . '/../connection.php';
// View OLAP summary
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>OLAP Summary</title>
<style>body{font-family:Arial,Helvetica,sans-serif;margin:20px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:6px}</style>
</head><body>
<h1>OLAP Summary</h1>
<h2>Daily Sales (last 30 days)</h2>
<?php
$ds = $conn->query("SELECT sales_date,total_orders,total_revenue,total_items FROM ordering_dw.daily_sales ORDER BY sales_date DESC LIMIT 30");
if ($ds && $ds->num_rows) {
  echo '<table><thead><tr><th>Date</th><th>Orders</th><th>Revenue</th><th>Items</th></tr></thead><tbody>';
  while ($r = $ds->fetch_assoc()) {
    echo '<tr><td>'.htmlspecialchars($r['sales_date']).'</td><td>'.htmlspecialchars($r['total_orders']).'</td><td>'.htmlspecialchars($r['total_revenue']).'</td><td>'.htmlspecialchars($r['total_items']).'</td></tr>';
  }
  echo '</tbody></table>';
} else { echo '<p>No daily sales data found.</p>'; }

echo '<h2>Top Products (last 30 days)</h2>';
$tp = $conn->query("SELECT sales_date,food_id,food_name,units_sold,revenue FROM ordering_dw.product_daily_sales ORDER BY sales_date DESC, units_sold DESC LIMIT 50");
if ($tp && $tp->num_rows){
  echo '<table><thead><tr><th>Date</th><th>Food ID</th><th>Food</th><th>Units</th><th>Revenue</th></tr></thead><tbody>';
  while($p=$tp->fetch_assoc()){
    echo '<tr><td>'.htmlspecialchars($p['sales_date']).'</td><td>'.htmlspecialchars($p['food_id']).'</td><td>'.htmlspecialchars($p['food_name']).'</td><td>'.htmlspecialchars($p['units_sold']).'</td><td>'.htmlspecialchars($p['revenue']).'</td></tr>';
  }
  echo '</tbody></table>';
} else { echo '<p>No product sales data found.</p>'; }

echo '<h2>Orders by Hour (recent)</h2>';
$oh = $conn->query("SELECT hour_start,order_count,revenue FROM ordering_dw.orders_by_hour ORDER BY hour_start DESC LIMIT 48");
if ($oh && $oh->num_rows){
  echo '<table><thead><tr><th>Hour Start</th><th>Orders</th><th>Revenue</th></tr></thead><tbody>';
  while($h=$oh->fetch_assoc()){
    echo '<tr><td>'.htmlspecialchars($h['hour_start']).'</td><td>'.htmlspecialchars($h['order_count']).'</td><td>'.htmlspecialchars($h['revenue']).'</td></tr>';
  }
  echo '</tbody></table>';
} else { echo '<p>No hourly data found.</p>'; }

?>
<p><a href="oltp.php">View OLTP orders</a></p>
</body></html>
