<?php
require_once __DIR__ . '/../connection.php';

// Sanitize inputs
$format = isset($_GET['format']) ? strtolower(preg_replace('/[^a-z]/', '', $_GET['format'])) : 'csv';
$start_date = isset($_GET['start_date']) ? date('Y-m-d', strtotime($_GET['start_date'])) : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? date('Y-m-d', strtotime($_GET['end_date'])) : date('Y-m-d');

// Ensure dates are valid
if ($start_date > $end_date) {
    $temp = $start_date;
    $start_date = $end_date;
    $end_date = $temp;
}

// Function to fetch report data
function getReportData($conn, $start_date, $end_date) {
    $query = "
        SELECT 
            ds.sales_date,
            ds.total_orders,
            ds.total_revenue,
            ds.total_items,
            ds.completed_orders,
            ds.avg_order_value
        FROM daily_sales ds
        WHERE ds.sales_date BETWEEN ? AND ?
        ORDER BY ds.sales_date DESC
    ";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    
    return $data;
}

// Get report data
$report_data = getReportData($conn, $start_date, $end_date);

if (!$report_data) {
    http_response_code(500);
    die('Error generating report');
}

// CSV Export
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sales_report_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header
    fputcsv($output, [
        'Date',
        'Total Orders',
        'Total Revenue',
        'Items Sold',
        'Completed Orders',
        'Average Order Value'
    ]);
    
    // Data
    foreach ($report_data as $row) {
        fputcsv($output, [
            $row['sales_date'],
            $row['total_orders'],
            number_format($row['total_revenue'], 2),
            $row['total_items'],
            $row['completed_orders'],
            number_format($row['avg_order_value'], 2)
        ]);
    }
    
    fclose($output);
    exit;
}

// Excel Export (XLSX using basic format)
if ($format === 'excel') {
    require_once __DIR__ . '/../vendor/xlsx_export.php';
    
    $filename = 'sales_report_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    $headers = ['Date', 'Total Orders', 'Total Revenue', 'Items Sold', 'Completed Orders', 'Average Order Value'];
    $rows = [];
    
    foreach ($report_data as $row) {
        $rows[] = [
            $row['sales_date'],
            $row['total_orders'],
            number_format($row['total_revenue'], 2),
            $row['total_items'],
            $row['completed_orders'],
            number_format($row['avg_order_value'], 2)
        ];
    }
    
    exportToExcel($filename, 'Sales Report', $headers, $rows);
    exit;
}

// PDF Export
if ($format === 'pdf') {
    // If TCPDF is not available, generate HTML for printing
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Sales Report</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: Arial, sans-serif;
                color: #333;
                line-height: 1.6;
            }
            .container {
                max-width: 900px;
                margin: 0 auto;
                padding: 40px 20px;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 3px solid #667eea;
                padding-bottom: 20px;
            }
            .header h1 {
                color: #667eea;
                margin-bottom: 5px;
            }
            .header p {
                color: #666;
                font-size: 14px;
            }
            .report-info {
                margin-bottom: 20px;
                padding: 15px;
                background: #f5f5f5;
                border-radius: 5px;
            }
            .report-info p {
                margin: 5px 0;
                font-size: 13px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 30px 0;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            table thead {
                background: #667eea;
                color: white;
            }
            table th {
                padding: 12px;
                text-align: left;
                font-weight: bold;
                border: 1px solid #ddd;
            }
            table td {
                padding: 10px 12px;
                border: 1px solid #eee;
            }
            table tbody tr:nth-child(even) {
                background: #f9f9f9;
            }
            table tbody tr:hover {
                background: #f0f0f0;
            }
            .summary {
                margin-top: 30px;
                padding: 20px;
                background: #f0f0f0;
                border-left: 4px solid #667eea;
            }
            .summary h3 {
                margin-bottom: 10px;
                color: #667eea;
            }
            .summary-row {
                display: flex;
                justify-content: space-between;
                margin: 8px 0;
                padding: 8px 0;
                border-bottom: 1px solid #ddd;
            }
            .summary-row:last-child {
                border-bottom: none;
            }
            .summary-label {
                font-weight: bold;
            }
            .summary-value {
                color: #667eea;
                font-weight: bold;
            }
            .footer {
                text-align: center;
                margin-top: 40px;
                padding-top: 20px;
                border-top: 1px solid #ddd;
                color: #666;
                font-size: 12px;
            }
            @media print {
                body { margin: 0; }
                .container { padding: 20px; }
            }
        </style>
    </head>
    <body onload="window.print();">
        <div class="container">
            <div class="header">
                <h1>📊 OLAP Sales Report</h1>
                <p>Analytical Report Generated on <?php echo date('F d, Y H:i:s'); ?></p>
            </div>

            <div class="report-info">
                <p><strong>Report Period:</strong> <?php echo date('F d, Y', strtotime($start_date)); ?> to <?php echo date('F d, Y', strtotime($end_date)); ?></p>
                <p><strong>Generated:</strong> <?php echo date('F d, Y H:i:s'); ?></p>
                <p><strong>System:</strong> MCCAT Ordering System - OLAP Analytics</p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Total Orders</th>
                        <th>Total Revenue</th>
                        <th>Items Sold</th>
                        <th>Completed Orders</th>
                        <th>Average Order Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_orders = 0;
                    $total_revenue = 0;
                    $total_items = 0;
                    $total_completed = 0;
                    
                    foreach ($report_data as $row) {
                        $total_orders += (int)$row['total_orders'];
                        $total_revenue += (float)$row['total_revenue'];
                        $total_items += (int)$row['total_items'];
                        $total_completed += (int)$row['completed_orders'];
                        
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['sales_date']) . '</td>';
                        echo '<td>' . number_format((int)$row['total_orders']) . '</td>';
                        echo '<td>₱' . number_format((float)$row['total_revenue'], 2) . '</td>';
                        echo '<td>' . number_format((int)$row['total_items']) . '</td>';
                        echo '<td>' . number_format((int)$row['completed_orders']) . '</td>';
                        echo '<td>₱' . number_format((float)$row['avg_order_value'], 2) . '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>

            <div class="summary">
                <h3>Summary Statistics</h3>
                <div class="summary-row">
                    <span class="summary-label">Total Orders:</span>
                    <span class="summary-value"><?php echo number_format($total_orders); ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Total Revenue:</span>
                    <span class="summary-value">₱<?php echo number_format($total_revenue, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Total Items Sold:</span>
                    <span class="summary-value"><?php echo number_format($total_items); ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Completed Orders:</span>
                    <span class="summary-value"><?php echo number_format($total_completed); ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Average Order Value:</span>
                    <span class="summary-value">₱<?php echo number_format($total_orders > 0 ? $total_revenue / $total_orders : 0, 2); ?></span>
                </div>
            </div>

            <div class="footer">
                <p>&copy; <?php echo date('Y'); ?> MCCAT Ordering System. All rights reserved.</p>
                <p>This is a confidential report for authorized users only.</p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Default: redirect to CSV if invalid format
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="sales_report.csv"');
?>
