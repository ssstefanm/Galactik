<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "connection.php";
require_once "functions.php";
require_once "email_config.php";

// PHPMailer includes
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

// Other includes
require_once 'send_order_confirmation.php';

$user_data = check_login($con);
$statusMsg = '';

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add to cart action
if (isset($_POST['add_to_cart'])) {
    if (!$user_data) {
        header("Location: login.php");
        exit();
    }

    $product_name = $_POST['name'];
    $product_price = floatval($_POST['price']);
    
    // Check if item already exists in cart
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['name'] === $product_name) {
            $item['quantity']++;
            $found = true;
            break;
        }
    }
    unset($item); // Break the reference

    if (!$found) {
        $_SESSION['cart'][] = [
            'name' => $product_name,
            'price' => $product_price,
            'quantity' => 1
        ];
    }

    $_SESSION['message'] = "Item added to cart successfully!";
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

// Remove from cart action
if (isset($_GET['remove']) && isset($_SESSION['cart'])) {
    $index_to_remove = $_GET['remove'];
    if (isset($_SESSION['cart'][$index_to_remove])) {
        unset($_SESSION['cart'][$index_to_remove]);
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex array
    }
    header("Location: cart.php");
    exit();
}

// Update quantity
if (isset($_POST['update_quantity'])) {
    $index = $_POST['item_index'];
    $new_quantity = (int)$_POST['quantity'];
    
    if ($new_quantity > 0 && isset($_SESSION['cart'][$index])) {
        $_SESSION['cart'][$index]['quantity'] = $new_quantity;
    } elseif ($new_quantity <= 0) {
        unset($_SESSION['cart'][$index]);
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex array
    }
    
    header("Location: cart.php");
    exit();
}

// Checkout process
if (isset($_POST['checkout']) && !empty($_SESSION['cart']) && $user_data) {
    try {
        error_log("Starting checkout process");
        
        $payment_method = $_POST['payment_method'];
        $user_id = $user_data['user_id'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $billing_address = $_POST['billing_address'];
        $billing_city = $_POST['billing_city'];
        $billing_state = $_POST['billing_state'];
        $billing_zip = $_POST['billing_zip'];
        $billing_country = $_POST['billing_country'];
        $phone = $_POST['phone'];
        
        // Calculate total
        $order_total = 0;
        foreach ($_SESSION['cart'] as $item) {
            $order_total += $item['price'] * $item['quantity'];
        }

        $con->begin_transaction();

        // Insert order
        $order_query = "INSERT INTO orders (user_id, total, payment_method, first_name, last_name, 
                       billing_address, billing_city, billing_state, billing_zip, billing_country, 
                       phone, status, created_at) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = $con->prepare($order_query);
        if (!$stmt) {
            throw new Exception('Failed to prepare order query: ' . $con->error);
        }

        $stmt->bind_param("sdsssssssss", 
            $user_id,
            $order_total,
            $payment_method,
            $first_name,
            $last_name,
            $billing_address,
            $billing_city,
            $billing_state,
            $billing_zip,
            $billing_country,
            $phone
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to execute order query: ' . $stmt->error);
        }

        $order_id = $stmt->insert_id;
        $stmt->close();

        // Insert order items
        $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
        $item_stmt = $con->prepare($item_query);

        if (!$item_stmt) {
            throw new Exception('Failed to prepare item query: ' . $con->error);
        }

        foreach ($_SESSION['cart'] as $item) {
            $product_id = md5($item['name']);
            $item_stmt->bind_param("isid", $order_id, $product_id, $item['quantity'], $item['price']);
            
            if (!$item_stmt->execute()) {
                throw new Exception('Failed to add order item: ' . $item_stmt->error);
            }
        }

        $item_stmt->close();
        $con->commit();
        
        // Send simple confirmation email
        $user_email = $user_data['user_name'];
        $email_sent = sendOrderConfirmation($user_email, $order_id, $_SESSION['cart'], $order_total);
        
        if (!$email_sent) {
            error_log("Failed to send order confirmation email for order #" . $order_id);
        }

        // Clear cart and set success message
        $_SESSION['cart'] = [];
        $_SESSION['message'] = "Order placed successfully! Order #" . $order_id;
        $_SESSION['last_order_id'] = $order_id;
        
        header("Location: order-success.php");
        exit();

    } catch (Exception $e) {
        $con->rollback();
        $statusMsg = "Error processing order: " . $e->getMessage();
        error_log("Order processing error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Galactik</title>
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
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
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
        
        nav a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            margin: 0 0.5rem;
        }
        
        nav a:hover {
            text-decoration: none;
            color: white;
            background: #050505;
        }
        
        nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        nav ul li {
            margin: 10px 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: #050505;
            border-radius: 8px;
            overflow: hidden;
        }
        
        table th, table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #050505;
        }
        
        table th {
            background: #222;
            font-weight: 600;
        }
        
        .btn, .product .btn {
            background: rgba(40, 40, 40, 0.3);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 10px 15px;
            border-radius: 8px;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 300;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
            width: 100%;
            margin-top: 10px;
        }

        .btn::before, .product .btn::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                45deg, 
                transparent, 
                rgba(255, 255, 255, 0.05), 
                transparent
            );
            transform: rotate(-45deg);
            transition: all 0.3s ease;
            opacity: 0;
        }

        .btn:hover::before, .product .btn:hover::before {
            opacity: 1;
        }

        .btn:hover, .product .btn:hover {
            background: rgba(60, 60, 60, 0.4);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.2);
            transform: translateY(-2px);
        }

        .btn:active, .product .btn:active {
            transform: translateY(1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .quantity-input {
            width: 60px;
            padding: 5px;
            border: 1px solid #050505;
            border-radius: 4px;
            background: #222;
            color: white;
        }
        
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: center;
        }
        
        .success {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid #28a745;
        }
        
        .error {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid #dc3545;
        }
        
        .billing-form {
            background: #050505;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #fff;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            background: #222;
            color: white;
            border: 1px solid #050505;
            border-radius: 4px;
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
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            table {
                font-size: 14px;
            }
            
            .btn {
                padding: 8px 15px;
                font-size: 14px;
            }
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
            <ul>
                <li><a href="/index.php?page=home">Home</a></li>
                <li><a href="/index.php?page=artists">Artists</a></li>
                <li><a href="music.php">Music</a></li>
                <li><a href="film.php">Film</a></li>
                <li><a href="/index.php?page=contact">Contact</a></li>
                <li><a href="/index.php?page=about">About</a></li>
                <?php if (isset($user_data) && $user_data !== false): ?>
                    <li><a href="/index.php?page=orders">Orders</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="/login.php">Login</a></li>
                    <li><a href="/signup.php">Signup</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        </nav>
    </header>

    <div class="container">
        <h1>Your Cart</h1>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($statusMsg)): ?>
            <div class="message error"><?php echo $statusMsg; ?></div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['cart'])): ?>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $cart_total = 0;
                    foreach ($_SESSION['cart'] as $index => $item): 
                        $item_total = $item['price'] * $item['quantity'];
                        $cart_total += $item_total;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="item_index" value="<?php echo $index; ?>">
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                           min="1" class="quantity-input">
                                    <button type="submit" name="update_quantity" class="btn">Update</button>
                                </form>
                            </td>
                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                            <td>$<?php echo number_format($item_total, 2); ?></td>
                            <td>
                                <a href="?remove=<?php echo $index; ?>" class="btn" 
                                   onclick="return confirm('Are you sure you want to remove this item?')">Remove</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="3" style="text-align: right;"><strong>Cart Total:</strong></td>
                        <td colspan="2"><strong>$<?php echo number_format($cart_total, 2); ?></strong></td>
                    </tr>
                </tbody>
            </table>

            <form method="POST" class="billing-form" id="checkout-form">
                <h2>Billing Details</h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" name="first_name" id="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" name="last_name" id="last_name" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="billing_address">Street Address *</label>
                    <input type="text" name="billing_address" id="billing_address" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="billing_city">City *</label>
                        <input type="text" name="billing_city" id="billing_city" required>
                    </div>
                    <div class="form-group">
                        <label for="billing_state">State/County *</label>
                        <input type="text" name="billing_state" id="billing_state" required>
                    </div>
                    <div class="form-group">
                        <label for="billing_zip">ZIP/Postal Code *</label>
                        <input type="text" name="billing_zip" id="billing_zip" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="billing_country">Country *</label>
                    <select name="billing_country" id="billing_country" required>
                        <option value="">Select a country</option>
                        <option value="RO">Romania</option>
                        <option value="UK">United Kingdom</option>
                        <option value="US">United States</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="phone">Phone *</label>
                    <input type="tel" name="phone" id="phone" required>
                </div>

                <div class="form-group">
                    <label for="payment_method">Payment Method *</label>
                    <select name="payment_method" id="payment_method" required>
                        <option value="card">Card Payment</option>
                        <option value="cash">Cash on Delivery</option>
                    </select>
                </div>

                <button type="submit" name="checkout" class="btn" style="width: 100%; margin-top: 20px;">Complete Order</button>
            </form>
        <?php else: ?>
            <div style="text-align: center; margin: 40px 0;">
                <p>Your cart is empty.</p>
                <a href="index.php" class="btn" style="margin-top: 20px;">Continue Shopping</a>
            </div>
        <?php endif; ?>
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