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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    try {
        $pdo = new PDO("mysql:host=-;dbname=-", 
                       "-", "-");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $_POST['order_id']]);
        
        $_SESSION['success_message'] = "Order status updated successfully";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error updating order status";
        error_log("Error updating order status: " . $e->getMessage());
    }
}

// Redirect back to admin dashboard
header("Location: index.php?page=admin_dashboard");
exit();
?>