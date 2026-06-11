<?php
/**
 * OLAP ETL Script - Enhanced Version
 * Extracts transactional data and loads into data warehouse for analysis
 * Usage: php scripts/olap_etl.php or trigger via web: olap_etl.php?run_etl=1
 */

require_once __DIR__ . '/../connection.php';

// Log function
function logETL($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logFile = __DIR__ . '/etl.log';
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    if (php_sapi_name() === 'cli') {
        echo "$message\n";
    }
}

// Function to populate daily sales
function populateDailySales($conn) {
    $query = "INSERT INTO daily_sales (sales_date, total_orders, total_revenue, total_items, avg_order_value, completed_orders, pending_orders)
    SELECT 
        DATE(o.created_at) as sales_date,
        COUNT(DISTINCT o.id) as total_orders,
        IFNULL(SUM(o.grand_total), 0) as total_revenue,
        IFNULL(SUM(oi.quantity), 0) as total_items,
        IFNULL(AVG(o.grand_total), 0) as avg_order_value,
        SUM(CASE WHEN o.status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN o.status = 'pending' THEN 1 ELSE 0 END) as pending_orders
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
    GROUP BY DATE(o.created_at)
    ON DUPLICATE KEY UPDATE
        total_orders = VALUES(total_orders),
        total_revenue = VALUES(total_revenue),
        total_items = VALUES(total_items),
        avg_order_value = VALUES(avg_order_value),
        completed_orders = VALUES(completed_orders),
        pending_orders = VALUES(pending_orders),
        last_updated = NOW()";
    
    return $conn->query($query);
}

// Function to populate product daily sales
function populateProductDailySales($conn) {
    $query = "INSERT INTO product_daily_sales (sales_date, food_id, food_name, category, units_sold, revenue, avg_price)
    SELECT 
        DATE(o.created_at) as sales_date,
        oi.food_id,
        oi.food_name,
        f.category,
        SUM(oi.quantity) as units_sold,
        IFNULL(SUM(oi.line_total), 0) as revenue,
        IFNULL(AVG(oi.unit_price), 0) as avg_price
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    LEFT JOIN foods f ON oi.food_id = f.id
    WHERE DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
    GROUP BY DATE(o.created_at), oi.food_id, oi.food_name
    ON DUPLICATE KEY UPDATE
        units_sold = VALUES(units_sold),
        revenue = VALUES(revenue),
        avg_price = VALUES(avg_price),
        last_updated = NOW()";
    
    return $conn->query($query);
}

// Function to populate orders by hour
function populateOrdersByHour($conn) {
    $query = "INSERT INTO orders_by_hour (hour_start, order_count, revenue, avg_order_value)
    SELECT 
        DATE_FORMAT(DATE_SUB(o.created_at, INTERVAL MINUTE(o.created_at) MINUTE, INTERVAL SECOND(o.created_at) SECOND), '%Y-%m-%d %H:00:00') as hour_start,
        COUNT(DISTINCT o.id) as order_count,
        IFNULL(SUM(o.grand_total), 0) as revenue,
        IFNULL(AVG(o.grand_total), 0) as avg_order_value
    FROM orders o
    WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
    GROUP BY YEAR(o.created_at), MONTH(o.created_at), DAY(o.created_at), HOUR(o.created_at)
    ON DUPLICATE KEY UPDATE
        order_count = VALUES(order_count),
        revenue = VALUES(revenue),
        avg_order_value = VALUES(avg_order_value),
        last_updated = NOW()";
    
    return $conn->query($query);
}

// Function to populate category performance
function populateCategoryPerformance($conn) {
    $query = "INSERT INTO category_performance (category, total_revenue, total_units, order_count, avg_revenue)
    SELECT 
        IFNULL(f.category, 'Uncategorized'),
        IFNULL(SUM(oi.line_total), 0) as total_revenue,
        IFNULL(SUM(oi.quantity), 0) as total_units,
        COUNT(DISTINCT oi.order_id) as order_count,
        IFNULL(AVG(oi.line_total), 0) as avg_revenue
    FROM order_items oi
    LEFT JOIN foods f ON oi.food_id = f.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY IFNULL(f.category, 'Uncategorized')
    ON DUPLICATE KEY UPDATE
        total_revenue = VALUES(total_revenue),
        total_units = VALUES(total_units),
        order_count = VALUES(order_count),
        avg_revenue = VALUES(avg_revenue),
        last_updated = NOW()";
    
    return $conn->query($query);
}

// Main ETL function
function runETL($conn) {
    logETL("=== ETL Process Started ===");
    logETL("Database: " . $conn->get_server_info());
    
    $results = [];
    
    logETL("Populating daily_sales...");
    $results['daily_sales'] = populateDailySales($conn) ? 'SUCCESS' : 'FAILED: ' . $conn->error;
    logETL("daily_sales: " . $results['daily_sales']);
    
    logETL("Populating product_daily_sales...");
    $results['product_daily_sales'] = populateProductDailySales($conn) ? 'SUCCESS' : 'FAILED: ' . $conn->error;
    logETL("product_daily_sales: " . $results['product_daily_sales']);
    
    logETL("Populating orders_by_hour...");
    $results['orders_by_hour'] = populateOrdersByHour($conn) ? 'SUCCESS' : 'FAILED: ' . $conn->error;
    logETL("orders_by_hour: " . $results['orders_by_hour']);
    
    logETL("Populating category_performance...");
    $results['category_performance'] = populateCategoryPerformance($conn) ? 'SUCCESS' : 'FAILED: ' . $conn->error;
    logETL("category_performance: " . $results['category_performance']);
    
    logETL("=== ETL Process Completed ===\n");
    
    return $results;
}

// Run ETL if called directly from CLI or web
if (php_sapi_name() === 'cli' || isset($_GET['run_etl'])) {
    $results = runETL($conn);
    
    if (isset($_GET['run_etl'])) {
        header('Content-Type: application/json');
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => 'ETL process completed successfully',
            'results' => $results
        ]);
    }
}
?>
