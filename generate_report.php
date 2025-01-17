<?php
session_start();
require "connection.php";
require "functions.php";

$user_data = check_login($con);

// Check if user is admin
if (!isset($user_data['role']) || $user_data['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="report-' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

// Get report parameters
$report_type = $_POST['report_type'] ?? 'visits';
$date_range = $_POST['date_range'] ?? 'today';

// Set date range
switch ($date_range) {
    case 'today':
        $date_from = date('Y-m-d');
        break;
    case 'week':
        $date_from = date('Y-m-d', strtotime('-7 days'));
        break;
    case 'month':
        $date_from = date('Y-m-d', strtotime('-30 days'));
        break;
    case 'year':
        $date_from = date('Y-m-d', strtotime('-1 year'));
        break;
    default:
        $date_from = date('Y-m-d');
}

try {
    // Initialize PDO connection
    $pdo = new PDO("mysql:host=sql108.infinityfree.com;dbname=if0_37686894_planetar", 
                   "if0_37686894", "JXrWoYo3qo0Q");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare and execute query based on report type
    switch ($report_type) {
        case 'visits':
            $query = "SELECT 
                        page_name,
                        COUNT(*) as visit_count,
                        COUNT(DISTINCT user_id) as unique_visitors,
                        COUNT(DISTINCT ip_address) as unique_ips,
                        DATE(visit_time) as visit_date
                     FROM page_visits
                     WHERE DATE(visit_time) >= :date_from
                     GROUP BY page_name, DATE(visit_time)
                     ORDER BY visit_date DESC, visit_count DESC";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute(['date_from' => $date_from]);
            
            // Output Excel format
            echo "Page Name\tVisit Count\tUnique Visitors\tUnique IPs\tDate\n";
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "{$row['page_name']}\t{$row['visit_count']}\t{$row['unique_visitors']}\t{$row['unique_ips']}\t{$row['visit_date']}\n";
            }
            break;
            
        case 'orders':
            $query = "SELECT 
                        DATE(created_at) as order_date,
                        COUNT(*) as order_count,
                        SUM(total) as total_revenue,
                        AVG(total) as average_order_value,
                        COUNT(DISTINCT user_id) as unique_customers
                     FROM orders
                     WHERE DATE(created_at) >= :date_from
                     GROUP BY DATE(created_at)
                     ORDER BY order_date DESC";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute(['date_from' => $date_from]);
            
            echo "Date\tOrder Count\tTotal Revenue\tAverage Order Value\tUnique Customers\n";
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "{$row['order_date']}\t{$row['order_count']}\t{$row['total_revenue']}\t" . 
                     number_format($row['average_order_value'], 2) . "\t{$row['unique_customers']}\n";
            }
            break;
            
        case 'users':
            $query = "SELECT 
                        DATE(date) as signup_date,
                        COUNT(*) as new_users,
                        role
                     FROM users
                     WHERE DATE(date) >= :date_from
                     GROUP BY DATE(date), role
                     ORDER BY signup_date DESC";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute(['date_from' => $date_from]);
            
            echo "Date\tNew Users\tRole\n";
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "{$row['signup_date']}\t{$row['new_users']}\t{$row['role']}\n";
            }
            break;
    }

    // Log report generation
    $report_query = "INSERT INTO reports (report_type, date_range, generated_by) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($report_query);
    $stmt->execute([$report_type, $date_range, $user_data['user_id']]);

} catch (PDOException $e) {
    // Log error and return error message
    error_log("Report generation error: " . $e->getMessage());
    echo "Error\tAn error occurred while generating the report\n";
}
?>