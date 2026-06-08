<?php
// ETL script to populate the OLAP data warehouse `ordering_dw`
// Usage: php scripts/olap_etl.php

require_once __DIR__ . '/../connection.php';

// connection.php connects to `ordering_system` by default; use fully-qualified names
// Create DW database if missing
$conn->query("CREATE DATABASE IF NOT EXISTS ordering_dw CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

// Create dw tables if not exists (delegated to olap_dw.sql is acceptable, but we ensure here too)
$conn->query("USE ordering_dw");

$createDaily = "CREATE TABLE IF NOT EXISTS daily_sales (
  sales_date DATE NOT NULL,
  total_orders INT NOT NULL DEFAULT 0,
  total_revenue DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total_items INT NOT NULL DEFAULT 0,
  PRIMARY KEY (sales_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($createDaily);

$createProd = "CREATE TABLE IF NOT EXISTS product_daily_sales (
  sales_date DATE NOT NULL,
  food_id INT UNSIGNED NOT NULL,
  food_name VARCHAR(150) NOT NULL,
  units_sold INT NOT NULL DEFAULT 0,
  revenue DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (sales_date, food_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($createProd);

$createHour = "CREATE TABLE IF NOT EXISTS orders_by_hour (
  hour_start DATETIME NOT NULL,
  order_count INT NOT NULL DEFAULT 0,
  revenue DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (hour_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($createHour);

// Populate daily_sales using aggregated data from ordering_system.orders and order_items
// We'll upsert using INSERT ... ON DUPLICATE KEY UPDATE

$dailySql = "INSERT INTO ordering_dw.daily_sales (sales_date, total_orders, total_revenue, total_items)
SELECT
  d.day AS sales_date,
  IFNULL(o.cnt,0) AS total_orders,
  IFNULL(o.rev,0.00) AS total_revenue,
  IFNULL(i.items,0) AS total_items
FROM (
  SELECT DISTINCT DATE(created_at) AS day FROM ordering_system.orders
) d
LEFT JOIN (
  SELECT DATE(created_at) AS day, COUNT(*) AS cnt, SUM(grand_total) AS rev
  FROM ordering_system.orders
  GROUP BY DATE(created_at)
) o ON o.day = d.day
LEFT JOIN (
  SELECT DATE(o.created_at) AS day, SUM(oi.quantity) AS items
  FROM ordering_system.order_items oi
  JOIN ordering_system.orders o ON o.id = oi.order_id
  GROUP BY DATE(o.created_at)
) i ON i.day = d.day
ON DUPLICATE KEY UPDATE
  total_orders = VALUES(total_orders),
  total_revenue = VALUES(total_revenue),
  total_items = VALUES(total_items)";

if (!$conn->query($dailySql)) {
    echo "Failed to populate daily_sales: " . $conn->error . PHP_EOL;
} else {
    echo "daily_sales populated/updated." . PHP_EOL;
}

// Populate product_daily_sales
$prodSql = "INSERT INTO ordering_dw.product_daily_sales (sales_date, food_id, food_name, units_sold, revenue)
SELECT
  DATE(o.created_at) AS sales_date,
  oi.food_id,
  oi.food_name,
  SUM(oi.quantity) AS units_sold,
  SUM(oi.line_total) AS revenue
FROM ordering_system.order_items oi
JOIN ordering_system.orders o ON o.id = oi.order_id
GROUP BY DATE(o.created_at), oi.food_id
ON DUPLICATE KEY UPDATE
  units_sold = VALUES(units_sold),
  revenue = VALUES(revenue)";

if (!$conn->query($prodSql)) {
    echo "Failed to populate product_daily_sales: " . $conn->error . PHP_EOL;
} else {
    echo "product_daily_sales populated/updated." . PHP_EOL;
}

// Populate orders_by_hour
$hourSql = "INSERT INTO ordering_dw.orders_by_hour (hour_start, order_count, revenue)
SELECT
  DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') AS hour_start,
  COUNT(*) AS order_count,
  SUM(grand_total) AS revenue
FROM ordering_system.orders
GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H')
ON DUPLICATE KEY UPDATE
  order_count = VALUES(order_count),
  revenue = VALUES(revenue)";

if (!$conn->query($hourSql)) {
    echo "Failed to populate orders_by_hour: " . $conn->error . PHP_EOL;
} else {
    echo "orders_by_hour populated/updated." . PHP_EOL;
}

// Analyze tables for better statistics
$conn->query('ANALYZE TABLE ordering_dw.daily_sales');
$conn->query('ANALYZE TABLE ordering_dw.product_daily_sales');
$conn->query('ANALYZE TABLE ordering_dw.orders_by_hour');

echo "ETL completed." . PHP_EOL;

?>
