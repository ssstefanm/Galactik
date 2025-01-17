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

// Check if order ID is provided
if (!isset($_GET['id'])) {
    header("Location: index.php?page=admin_dashboard");
    exit();
}

$order_id = $_GET['id'];

try {
    $db = new PDO('mysql:host=-;dbname=-', 
                  '-', '-');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch order details with user information
    $stmt = $db->prepare("
    SELECT 
        o.*,
        u.user_name,
        oi.product_id,
        oi.quantity,
        oi.price as item_price,
        p.name as product_name,
        p.image_url,
        p.product_type
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON MD5(p.name) = oi.product_id
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
    
    $orderData = [];
    $orderItems = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (empty($orderData)) {
            $orderData = [
                'id' => $row['id'],
                'user_id' => $row['user_id'],
                'user_name' => $row['user_name'],
                'created_at' => $row['created_at'],
                'total' => $row['total'],
                'status' => $row['status'],
                'payment_method' => $row['payment_method'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'billing_address' => $row['billing_address'],
                'billing_city' => $row['billing_city'],
                'billing_state' => $row['billing_state'],
                'billing_zip' => $row['billing_zip'],
                'billing_country' => $row['billing_country'],
                'phone' => $row['phone']
            ];
        }
        if ($row['product_id']) {
            $orderItems[] = [
                'product_id' => $row['product_id'],
                'product_name' => $row['product_name'],
                'quantity' => $row['quantity'],
                'price' => $row['item_price'],
                'image_url' => $row['image_url']
            ];
        }
    }

    if (empty($orderData)) {
        throw new Exception("Order not found");
    }

} catch (Exception $e) {
    $_SESSION['error_message'] = "Error loading order: " . $e->getMessage();
    header("Location: index.php?page=admin_dashboard");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $order_id; ?> - Galactik Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            color: white;
            line-height: 1.6;
            background: black;
            min-height: 100vh;
        }
        
        header {
            font-family: "Montserrat";
            text-align: center;
            padding: 1rem 2rem;
            margin: 0;
            background: rgba(0,0,0,0.3);
            color: white;
            position: relative;
            backdrop-filter: blur(10px);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            min-height: calc(100vh - 130px);
        }
        
        .back-link {
            display: inline-block;
            color: white;
            text-decoration: none;
            margin-bottom: 20px;
            padding: 8px 16px;
            background: rgba(40, 40, 40, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            background: rgba(60, 60, 60, 0.4);
            transform: translateY(-2px);
        }

        .order-container {
            background: rgba(40, 40, 40, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .order-status {
            padding: 5px 15px;
            border-radius: 20px;
            background: rgba(0, 0, 0, 0.2);
            font-size: 0.9em;
        }

        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .detail-section {
            background: rgba(0, 0, 0, 0.2);
            padding: 15px;
            border-radius: 8px;
        }

        .detail-section h3 {
            margin-bottom: 10px;
            color: rgba(255, 255, 255, 0.9);
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            overflow: hidden;
        }

        .items-table th,
        .items-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .items-table th {
            background: rgba(0, 0, 0, 0.3);
            font-weight: 600;
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }

        .status-form {
            margin-top: 20px;
            padding: 20px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
        }

        .status-form select {
            padding: 8px;
            background: rgba(40, 40, 40, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            color: white;
            margin-right: 10px;
        }

        .status-form button {
            padding: 8px 16px;
            background: rgba(40, 40, 40, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .status-form button:hover {
            background: rgba(60, 60, 60, 0.4);
        }

        footer {
            background: black;
            font-family: "Montserrat";
            color: white;
            text-align: center;
            padding: 1rem;
            position: fixed;
            bottom: 0;
            width: 100%;
        }

        @media (max-width: 768px) {
            .order-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .order-details {
                grid-template-columns: 1fr;
            }
            
            .items-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Galactik Admin</h1>
        <div style="width: 100%; height: 1px; background-color: white; margin: 10px auto;"></div>
    </header>

    <div class="container">
        <a href="index.php?page=admin_dashboard" class="back-link">← Back to Dashboard</a>
        
        <div class="order-container">
            <div class="order-header">
                <div>
                    <h2>Order #<?php echo htmlspecialchars($orderData['id']); ?></h2>
                    <p>Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($orderData['created_at'])); ?></p>
                </div>
                <span class="order-status">
                    Status: <?php echo ucfirst(htmlspecialchars($orderData['status'] ?? 'pending')); ?>
                </span>
            </div>

            <div class="order-details">
                <div class="detail-section">
                    <h3>Customer Information</h3>
                    <p><strong>Customer ID:</strong> <?php echo htmlspecialchars($orderData['user_id']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($orderData['user_name']); ?></p>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($orderData['first_name'] . ' ' . $orderData['last_name']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($orderData['phone']); ?></p>
                </div>

                <div class="detail-section">
                    <h3>Shipping Address</h3>
                    <p><?php echo htmlspecialchars($orderData['billing_address']); ?></p>
                    <p><?php echo htmlspecialchars($orderData['billing_city'] . ', ' . $orderData['billing_state'] . ' ' . $orderData['billing_zip']); ?></p>
                    <p><?php echo htmlspecialchars($orderData['billing_country']); ?></p>
                </div>

                <div class="detail-section">
                    <h3>Order Summary</h3>
                    <p><strong>Payment Method:</strong> <?php echo ucfirst(htmlspecialchars($orderData['payment_method'])); ?></p>
                    <p><strong>Total Amount:</strong> $<?php echo number_format($orderData['total'], 2); ?></p>
                    <p><strong>Items:</strong> <?php echo count($orderItems); ?></p>
                </div>
            </div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td>
                                <div class="product-info">
                                    <?php if ($item['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                             class="product-image">
                                    <?php endif; ?>
                                    <span><?php echo htmlspecialchars($item['product_name']); ?></span>
                                </div>
                            </td>
                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                        <td><strong>$<?php echo number_format($orderData['total'], 2); ?></strong></td>
                    </tr>
                </tfoot>
            </table>

            <form method="POST" action="update_order_status.php" class="status-form">
                <input type="hidden" name="order_id" value="<?php echo $orderData['id']; ?>">
                <label for="status"><strong>Update Order Status:</strong></label>
                <select name="status" id="status">
                    <option value="pending" <?php echo $orderData['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="processing" <?php echo $orderData['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="shipped" <?php echo $orderData['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="delivered" <?php echo $orderData['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                </select>
                <button type="submit">Update Status</button>
            </form>
        </div>
    </div>

    <footer>
        <p>&copy; <?= date('Y') ?> Galactik. All rights reserved. Site made by Stefan-Andrei Musat</p>
    </footer>
</body>
</html>