<?php
/**
 * Decision-Making & Recommendations Engine
 * Analyzes business data to provide intelligent recommendations
 * Component 2: OLAP & Decision-Making Logic
 */

require_once __DIR__ . '/../connection.php';

class BusinessIntelligence {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Get revenue trend analysis
     */
    public function getRevenueAnalysis($days = 30) {
        $query = "
            SELECT 
                sales_date,
                total_revenue,
                total_orders,
                avg_order_value,
                LAG(total_revenue) OVER (ORDER BY sales_date) as prev_day_revenue,
                ROUND(((total_revenue - LAG(total_revenue) OVER (ORDER BY sales_date)) / LAG(total_revenue) OVER (ORDER BY sales_date)) * 100, 2) as revenue_change_pct
            FROM daily_sales
            WHERE sales_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            ORDER BY sales_date DESC
        ";
        
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        $stmt->bind_param('i', $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Identify top performing products
     */
    public function getTopPerformingProducts($limit = 10) {
        $query = "
            SELECT 
                food_id,
                food_name,
                category,
                SUM(units_sold) as total_units,
                SUM(revenue) as total_revenue,
                AVG(revenue / units_sold) as avg_unit_price,
                COUNT(DISTINCT sales_date) as days_sold
            FROM product_daily_sales
            WHERE sales_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY food_id, food_name, category
            ORDER BY total_revenue DESC
            LIMIT ?
        ";
        
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Identify underperforming products
     */
    public function getUnderperformingProducts($limit = 5) {
        $query = "
            SELECT 
                f.id,
                f.name,
                f.category,
                f.price,
                IFNULL(SUM(pds.units_sold), 0) as units_sold_30days,
                IFNULL(SUM(pds.revenue), 0) as revenue_30days,
                (SELECT COUNT(*) FROM orders WHERE status = 'completed' AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as total_completed_orders_30days
            FROM foods f
            LEFT JOIN product_daily_sales pds ON f.id = pds.food_id AND pds.sales_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            WHERE f.is_available = 1
            GROUP BY f.id, f.name, f.category, f.price
            HAVING units_sold_30days < 5 OR units_sold_30days IS NULL
            ORDER BY units_sold_30days ASC
            LIMIT ?
        ";
        
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get peak sales hours
     */
    public function getPeakHours() {
        $query = "
            SELECT 
                HOUR(hour_start) as hour_of_day,
                DATE_FORMAT(hour_start, '%H:00') as time_slot,
                AVG(order_count) as avg_orders,
                AVG(revenue) as avg_revenue,
                MAX(revenue) as peak_revenue,
                COUNT(*) as sample_count
            FROM orders_by_hour
            WHERE hour_start >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY HOUR(hour_start)
            ORDER BY avg_revenue DESC
        ";
        
        return $this->conn->query($query)->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Generate business recommendations
     */
    public function getRecommendations() {
        $recommendations = [];
        
        // 1. Revenue trend recommendation
        $recent_revenue = $this->conn->query("
            SELECT AVG(total_revenue) as avg_revenue FROM daily_sales
            WHERE sales_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ")->fetch_assoc();
        
        $previous_revenue = $this->conn->query("
            SELECT AVG(total_revenue) as avg_revenue FROM daily_sales
            WHERE sales_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
            AND sales_date < DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ")->fetch_assoc();
        
        if ($recent_revenue['avg_revenue'] > $previous_revenue['avg_revenue'] * 1.1) {
            $recommendations[] = [
                'type' => 'POSITIVE',
                'category' => 'Revenue Growth',
                'message' => 'Revenue is trending upward! Continue current marketing strategies.',
                'priority' => 'HIGH'
            ];
        } elseif ($recent_revenue['avg_revenue'] < $previous_revenue['avg_revenue'] * 0.9) {
            $recommendations[] = [
                'type' => 'WARNING',
                'category' => 'Revenue Decline',
                'message' => 'Revenue is declining. Consider promotional activities or menu optimization.',
                'priority' => 'HIGH'
            ];
        }
        
        // 2. Inventory recommendations based on low performers
        $low_performers = $this->getUnderperformingProducts(3);
        if (count($low_performers) > 0) {
            $product_list = implode(', ', array_column($low_performers, 'name'));
            $recommendations[] = [
                'type' => 'SUGGESTION',
                'category' => 'Product Optimization',
                'message' => "Consider reviewing or removing underperforming items: $product_list",
                'priority' => 'MEDIUM'
            ];
        }
        
        // 3. Staff scheduling recommendation
        $peak_hours = $this->getPeakHours();
        if (!empty($peak_hours)) {
            $top_hour = $peak_hours[0];
            $recommendations[] = [
                'type' => 'INFO',
                'category' => 'Staff Scheduling',
                'message' => "Peak sales occur around {$top_hour['time_slot']}. Ensure sufficient staff during this period.",
                'priority' => 'MEDIUM'
            ];
        }
        
        // 4. Customer engagement
        $top_customers = $this->conn->query("
            SELECT COUNT(*) as repeat_customers FROM customer_analytics
            WHERE total_orders >= 3 AND last_order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ")->fetch_assoc();
        
        if ($top_customers['repeat_customers'] > 0) {
            $recommendations[] = [
                'type' => 'POSITIVE',
                'category' => 'Customer Loyalty',
                'message' => "You have {$top_customers['repeat_customers']} loyal customers. Implement a rewards program to retain them.",
                'priority' => 'MEDIUM'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Calculate KPIs and performance metrics
     */
    public function getPerformanceMetrics($days = 30) {
        $query = "
            SELECT 
                COUNT(*) as data_points,
                SUM(total_orders) as total_orders,
                SUM(total_revenue) as total_revenue,
                AVG(total_orders) as avg_daily_orders,
                AVG(avg_order_value) as avg_order_value,
                MAX(total_revenue) as peak_daily_revenue,
                MIN(total_revenue) as min_daily_revenue,
                STDDEV(total_revenue) as revenue_volatility,
                SUM(completed_orders) / SUM(total_orders) * 100 as completion_rate
            FROM daily_sales
            WHERE sales_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        ";
        
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        $stmt->bind_param('i', $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Forecast next day revenue using simple moving average
     */
    public function forecastNextDayRevenue() {
        $query = "
            SELECT AVG(total_revenue) as forecast_revenue
            FROM (
                SELECT total_revenue FROM daily_sales
                WHERE sales_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
                ORDER BY sales_date DESC
                LIMIT 7
            ) recent_data
        ";
        
        return $this->conn->query($query)->fetch_assoc();
    }
}

// API endpoint
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $bi = new BusinessIntelligence($conn);
    
    $action = preg_replace('/[^a-z_]/', '', strtolower($_GET['action']));
    
    switch ($action) {
        case 'recommendations':
            echo json_encode($bi->getRecommendations());
            break;
        case 'metrics':
            $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
            echo json_encode($bi->getPerformanceMetrics($days));
            break;
        case 'top_products':
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            echo json_encode($bi->getTopPerformingProducts($limit));
            break;
        case 'peak_hours':
            echo json_encode($bi->getPeakHours());
            break;
        case 'forecast':
            echo json_encode($bi->forecastNextDayRevenue());
            break;
        case 'revenue_analysis':
            $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
            echo json_encode($bi->getRevenueAnalysis($days));
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
    exit;
}
?>
