<?php
session_start();
include("connection.php");
include("functions.php");

$user_data = check_login($con);

// Redirect if no order was placed
if (!isset($_SESSION['last_order_id'])) {
    header("Location: cart.php");
    exit();
}

$order_id = $_SESSION['last_order_id'];

// Fetch order details for display
try {
    $db = new PDO('mysql:host=-;dbname=-', 
                  '-', '-');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $db->prepare("
        SELECT o.*, u.user_name 
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $orderData = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching order details: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Successful - Galactik</title>
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
            position: relative;
            padding-bottom: 60px;
        }
        
        header {
            font-family: "Montserrat";
            text-align: center;
            padding: 1rem 2rem;
            margin: 0;
            background: black;
            color: white;
            position: relative;
        }
        
        .menu-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 10px;
        }
        
        .menu-btn span {
            display: block;
            width: 25px;
            height: 3px;
            background: white;
            margin: 5px 0;
        }
        
        .nav-menu {
            display: none;
            position: absolute;
            top: 60px;
            left: 20px;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: white;
            padding: 1rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            border-radius: 4px;
            z-index: 1000;
        }
        
        .nav-menu.active {
            display: block;
        }
        
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .success-container {
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid #28a745;
            border-radius: 8px;
            padding: 40px 20px;
            text-align: center;
            margin-bottom: 40px;
        }
        
        .success-icon {
            font-size: 64px;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .order-number {
            font-size: 24px;
            margin: 20px 0;
            color: #28a745;
        }
        
        .order-details {
            margin: 30px 0;
            text-align: left;
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 8px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px;
            transition: background 0.3s ease;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        nav a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            margin: 0 0.5rem;
        }
        
        nav a:hover {
            text-decoration: none;
            color: white;
            background: #333;
        }
        
        footer {
            background: black;
            font-family: "Montserrat";
            color: white;
            text-align: center;
            padding: 1rem;
            position: absolute;
            bottom: 0;
            width: 100%;
        }

        .success-container {
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid #28a745;
            border-radius: 8px;
            padding: 40px 20px;
            text-align: center;
            margin-bottom: 40px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .actions-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: rgba(40, 40, 40, 0.3);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 10px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .btn:hover {
            background: rgba(60, 60, 60, 0.4);
            transform: translateY(-2px);
        }
        
        .btn.invoice {
            background: rgba(40, 167, 69, 0.3);
            border-color: rgba(40, 167, 69, 0.3);
        }
        
        .btn.invoice:hover {
            background: rgba(40, 167, 69, 0.4);
        }
        
        .order-summary {
            background: rgba(0, 0, 0, 0.2);
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            text-align: left;
        }
        
        .order-summary h3 {
            margin-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 0.5rem;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin: 0.5rem 0;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .summary-row:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <header>
        <h1>Galactik</h1>
        <div style="width: 100%; height: 1px; background-color: white; margin: 10px auto;"></div>
        <button class="menu-btn" onclick="toggleMenu()">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <nav class="nav-menu" id="nav-menu">
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li><a href="index.php?page=home">Home</a></li>
                <li><a href="index.php?page=artists">Artists</a></li>
                <li><a href="index.php?page=releases">Releases</a></li>
                <li><a href="index.php?page=contact">Contact</a></li>
                <li><a href="index.php?page=about">About</a></li>
                <?php if (isset($user_data) && $user_data !== false): ?>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="signup.php">Signup</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <div class="container">
        <div class="success-container">
            <div class="success-icon">✓</div>
            <h1>Thank You for Your Order!</h1>
            <p class="order-number">Order #<?php echo $order_id; ?></p>
            
            <?php if (isset($orderData)): ?>
                <div class="order-summary">
                    <h3>Order Summary</h3>
                    <div class="summary-row">
                        <span>Order Date:</span>
                        <span><?php echo date('F j, Y, g:i a', strtotime($orderData['created_at'])); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Order Total:</span>
                        <span>$<?php echo number_format($orderData['total'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Payment Method:</span>
                        <span><?php echo ucfirst($orderData['payment_method']); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping To:</span>
                        <span><?php echo htmlspecialchars($orderData['first_name'] . ' ' . $orderData['last_name']); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['message'])): ?>
                <p class="message"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></p>
            <?php endif; ?>
            
            <?php if ($user_data['user_name']): ?>
                <p>A confirmation email with your invoice has been sent to: <?php echo htmlspecialchars($user_data['user_name']); ?></p>
            <?php endif; ?>

            <div class="actions-container">
                <?php if (isset($_SESSION['invoice_path'])): ?>
                    <a href="download_invoice.php?order_id=<?php echo $order_id; ?>" class="btn invoice">
                        Download Invoice
                    </a>
                <?php endif; ?>
                
                <a href="index.php?page=orders" class="btn">View My Orders</a>
                <a href="music.php" class="btn">Continue Shopping</a>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; <?= date('Y') ?> Galactik. All rights reserved. Site made by Stefan-Andrei Musat</p>
    </footer>

    <script>
        function toggleMenu() {
            document.getElementById('nav-menu').classList.toggle('active');
        }
    </script>
</body>
</html>
<?php
// Clean up the session variables
unset($_SESSION['last_order_id']);
unset($_SESSION['invoice_path']);
?>