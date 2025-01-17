<?php
session_start();
include("connection.php");
include("functions.php");
require_once("track_visits.php");

$user_data = check_login($con);
$db = new PDO('mysql:host=-;dbname=-', '-', '-');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

$page = isset($_GET['page']) ? sanitize($_GET['page']) : 'home';

// Track the current page visit
track_page_visit($db, $page, $user_data);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galactik - Independent Music and Film Label</title>
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
            overflow-x: hidden;
        }

        .video-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
            opacity: 0.5;
        }

        .content-overlay {
            position: relative;
            z-index: 1;
            background: rgba(0,0,0,0.5);
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
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            min-height: calc(100vh - 100px);
        }
        
        .hero-section {
            height: calc(100vh - 180px);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            margin-bottom: 60px;
        }

        .hero-text {
            width: 40%;
            text-align: center;
        }
        
        .hero-text a {
            color: white;
            text-decoration: none;
            font-size: 5.5em;
            font-weight: bold;
            transition: opacity 0.3s ease;
        }
        
        .hero-text a:hover {
            opacity: 0.8;
        }

        .lightning-container {
            width: 20%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .lightning-svg {
            height: 80%;
            width: auto;
        }
        
        .lightning-bolt {
            fill: white;
            animation: glowStrike 4s ease-in-out infinite;
        }
        
        @keyframes glowStrike {
            0%, 100% {
                opacity: 0.3;
                filter: drop-shadow(0 0 3px rgba(255,255,255,0.3));
            }
            50% {
                opacity: 0.8;
                filter: drop-shadow(0 0 8px rgba(255,255,255,0.6));
            }
        }
        
        .content-section {
            margin-bottom: 60px;
        }
        
        .artist-card, .order-card {
            background: rgba(40, 40, 40, 0.15);
            padding: 1.5rem;
            margin: 1rem 0;
            border-radius: 8px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .contact-form {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .contact-form input,
        .contact-form textarea,
        .contact-form select {
            width: 100%;
            padding: 0.8rem;
            margin-bottom: 1rem;
            background: rgba(40, 40, 40, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            color: white;
        }

        .btn {
            background: rgba(40, 40, 40, 0.3);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .btn:hover {
            background: rgba(60, 60, 60, 0.4);
            transform: translateY(-2px);
        }

        /* Admin Dashboard Styles */
        .admin-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .admin-card {
            background: rgba(40, 40, 40, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 1.5rem;
            backdrop-filter: blur(10px);
            margin-bottom: 1.5rem;
        }

        .admin-card h3 {
            margin-bottom: 1rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .stat-item {
            background: rgba(0, 0, 0, 0.2);
            padding: 1rem;
            border-radius: 6px;
            text-align: center;
        }

        .stat-label {
            display: block;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 0.5rem;
        }

        .stat-value {
            display: block;
            font-size: 1.5rem;
            font-weight: bold;
            color: #fff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        th {
            background: rgba(0, 0, 0, 0.3);
            font-weight: bold;
        }

        .report-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .btn-small {
            padding: 0.25rem 0.5rem;
            font-size: 0.9rem;
        }

        .status-form select {
            padding: 0.25rem;
            font-size: 0.9rem;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
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
            .hero-text a {
                font-size: 2.5em;
            }
            
            .hero-section {
                padding: 0 10px;
            }
            
            .lightning-container svg {
                height: 60%;
            }

            .admin-stats-grid {
                grid-template-columns: 1fr;
            }

            .report-form {
                grid-template-columns: 1fr;
            }

            table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>

    <div class="content-overlay">
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
                    <li><a href="index.php?page=home">Home</a></li>
                    <li><a href="index.php?page=artists">Artists</a></li>
                    <li><a href="music.php">Music</a></li>
                    <li><a href="film.php">Film</a></li>
                    <li><a href="index.php?page=contact">Contact</a></li>
                    <li><a href="index.php?page=about">About</a></li>
                    <?php if (isset($user_data) && $user_data !== false): ?>
                        <?php if (isset($user_data['role']) && $user_data['role'] === 'admin'): ?>
                            <li><a href="index.php?page=admin_dashboard">Admin Dashboard</a></li>
                        <?php endif; ?>
                        <li><a href="index.php?page=orders">Orders</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="signup.php">Signup</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </header>

        <div class="container">
            <?php 
            // Display any success or error messages
            if (isset($_SESSION['success_message'])): ?>
                <div class="success-message">
                    <?php 
                        echo $_SESSION['success_message'];
                        unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="error-message">
                    <?php 
                        echo $_SESSION['error_message'];
                        unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php switch($page): 
                  case 'home': ?>
                <div class="hero-section">
                    <div class="hero-text">
                        <a href="/music.php">MUSIC</a>
                    </div>
                    <div class="lightning-container">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 82.1 122.88" class="lightning-svg">
                            <path class="lightning-bolt" d="M19.62,0h50.2l-17.5,33.88L82.1,34.4L9.53,122.88l13.96-58.21L0,64.67L19.62,0L19.62,0L19.62,0z M13.92,53.48 l14.65-41.7h22.75L39.49,43.53l17.85,0.3L27.31,88.79l8.95-35.31L13.92,53.48L13.92,53.48L13.92,53.48z"/>
                        </svg>
                    </div>
                <div class="hero-text">
                        <a href="/film.php">FILM</a>
                    </div>
                </div>
            <?php break; ?>
        
            <?php case 'artists': ?>
                <div class="content-section">
                    <h2>Our Artists</h2>
                    <?php
                    $stmt = $db->query("SELECT * FROM artists ORDER BY name");
                    while ($artist = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <div class="artist-card">
                            <h3><?= sanitize($artist['name']) ?></h3>
                            <p><?= sanitize($artist['bio']) ?></p>
                            <p>Genre: <?= sanitize($artist['genre']) ?></p>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php break; ?>
            
            <?php case 'admin_dashboard': 
                // Check if user is admin
                if (!isset($user_data['role']) || $user_data['role'] !== 'admin') {
                    header("Location: index.php");
                    exit();
                }
            ?>
                <div class="content-section">
                    <h2>Admin Dashboard</h2>
                    
                    <!-- Quick Stats Section -->
                    <div class="admin-stats-grid">
                        <div class="admin-card">
                            <h3>Quick Statistics</h3>
                            <?php
                            // Get total users
                            $stmt = $db->query("SELECT COUNT(*) as total FROM users");
                            $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                            
                            // Get total orders
                            $stmt = $db->query("SELECT COUNT(*) as total FROM orders");
                            $total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                            
                            // Get total revenue
                            $stmt = $db->query("SELECT SUM(total) as revenue FROM orders");
                            $total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;
                            ?>
                            <div class="stats-container">
                                <div class="stat-item">
                                    <span class="stat-label">Total Users</span>
                                    <span class="stat-value"><?php echo $total_users; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Total Orders</span>
                                    <span class="stat-value"><?php echo $total_orders; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Total Revenue</span>
                                    <span class="stat-value">$<?php echo number_format($total_revenue, 2); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Visits Section -->
                        <div class="admin-card">
                            <h3>Recent Page Visits</h3>
                            <?php
                            $stmt = $db->query("
                                SELECT pv.*, u.user_name 
                                FROM page_visits pv 
                                LEFT JOIN users u ON pv.user_id = u.user_id 
                                ORDER BY visit_time DESC 
                                LIMIT 10
                            ");
                            $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Page</th>
                                            <th>User</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($visits as $visit): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($visit['page_name']); ?></td>
                                                <td><?php echo htmlspecialchars($visit['user_name'] ?? 'Guest'); ?></td>
                                                <td><?php echo date('Y-m-d H:i:s', strtotime($visit['visit_time'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Generate Reports Section -->
                    <div class="admin-card">
                        <h3>Generate Reports</h3>
                        <form method="POST" action="generate_report.php" class="report-form">
                            <div class="form-group">
                                <label for="report_type">Report Type:</label>
                                <select name="report_type" id="report_type" required>
                                    <option value="visits">Page Visits</option>
                                    <option value="orders">Orders</option>
                                    <option value="users">Users</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="date_range">Date Range:</label>
                                <select name="date_range" id="date_range" required>
                                    <option value="today">Today</option>
                                    <option value="week">Last 7 Days</option>
                                    <option value="month">Last 30 Days</option>
                                    <option value="year">Last Year</option>
                                </select>
                            </div>
                            <button type="submit" class="btn">Generate Report</button>
                        </form>
                    </div>

                    <!-- All Orders Management -->
                    <div class="admin-card">
                        <h3>Recent Orders</h3>
                        <?php
                        $stmt = $db->query("
                            SELECT o.*, u.user_name 
                            FROM orders o 
                            JOIN users u ON o.user_id = u.user_id 
                            ORDER BY o.created_at DESC 
                            LIMIT 10
                        ");
                        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>User</th>
                                        <th>Total</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                                            <td>$<?php echo number_format($order['total'], 2); ?></td>
                                            <td><?php echo date('Y-m-d H:i:s', strtotime($order['created_at'])); ?></td>
                                            <td>
                                                <form method="POST" action="update_order_status.php" class="status-form">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                    <select name="status" onchange="this.form.submit()">
                                                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                        <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                        <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                    </select>
                                                </form>
                                            </td>
                                            <td>
                                                <a href="view_order.php?id=<?php echo $order['id']; ?>" class="btn btn-small">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php break; ?>

            <?php case 'about': ?>
                <div class="content-section">
                    <h2>Despre Proiect</h2>
                    <h3>Galactik - Proiect Educațional de Dezvoltare Web</h3>
                    
                    <div class="admin-card">
                        <h4>Context și Scop</h4>
                        <p>Acest proiect web a fost conceput și realizat de Musat Stefan Andrei, student în anul II la Universitatea din Bucuresti pentru materia "Dezvoltarea a Aplicațiilor Web". Scopul principal al proiectului este educațional, fiind menit să demonstreze competențele și cunoștințele dobândite în domeniul programării web.</p>
                    </div>
                    
                    <div class="admin-card">
                        <h4>Arhitectura Proiectului</h4>
                        <p>Platforma Galactik simulează o casă de producție modernă, combinând elemente de muzică și film într-o aplicație web complexă. Proiectul încorporează:</p>
                        <ul style="list-style-type: disc; padding-left: 30px; color: #ddd;">
                            <li>Sistem de autentificare utilizatori cu roluri (admin/user)</li>
                            <li>Sistem de mailing automat la plasarea unei comenzi utilizand PHPMailer</li>
                            <li>Gestionare comenzi și coș de cumpărături</li>
                            <li>Sistem de analytics și raportare</li>
                            <li>Interfață responsivă și design modern</li>
                            <li>Integrare bază de date</li>
                            <li>Funcționalități dinamice utilizând PHP</li>
                        </ul>
                    </div>
                    
                    <div class="admin-card">
                        <h4>Mențiune Importantă</h4>
                        <p><strong>Atenție!</strong> Acest site este un proiect educațional și <em>NU are scop comercial</em>.</p>
                        <p>Toate elementele sale sunt create exclusiv în scopuri de învățare și demonstrare a competențelor tehnice.</p>
                    </div>
                </div>
            <?php break; ?>
            
            <?php case 'contact': ?>
                <div class="content-section">
                    <h2>Contact Us</h2>
                    <?php
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $name = sanitize($_POST['name']);
                        $email = sanitize($_POST['email']);
                        $message = sanitize($_POST['message']);
                        
                        $stmt = $db->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
                        $stmt->execute([$name, $email, $message]);
                        
                        echo "<p class='success-message'>Thank you for your message. We'll get back to you soon!</p>";
                    }
                    ?>
                    <form class="contact-form" method="POST">
                        <div>
                            <label for="name">Name:</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        
                        <div>
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        
                        <div>
                            <label for="message">Message:</label>
                            <textarea id="message" name="message" rows="5" required></textarea>
                        </div>
                        
                        <button type="submit" class="btn">Send Message</button>
                    </form>
                </div>
            <?php break; ?>
            
            <?php case 'orders': 
                if (!$user_data) {
                    header("Location: login.php");
                    exit();
                }
            ?>
                <div class="content-section">
                    <h2>Your Order History</h2>
                    <?php
                    $stmt = $db->prepare("
                        SELECT o.*, oi.product_id, oi.quantity, oi.price as item_price,
                               p.name as product_name
                        FROM orders o
                        LEFT JOIN order_items oi ON o.id = oi.order_id
                        LEFT JOIN products p ON oi.product_id = p.id
                        WHERE o.user_id = ?
                        ORDER BY o.created_at DESC, o.id DESC
                    ");
                    $stmt->execute([$user_data['user_id']]);
                    
                    $orders = [];
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        if (!isset($orders[$row['id']])) {
                            $orders[$row['id']] = [
                                'order_id' => $row['id'],
                                'created_at' => $row['created_at'],
                                'total' => $row['total'],
                                'status' => $row['status'],
                                'payment_method' => $row['payment_method'],
                                'shipping_details' => [
                                    'first_name' => $row['first_name'],
                                    'last_name' => $row['last_name'],
                                    'address' => $row['billing_address'],
                                    'city' => $row['billing_city'],
                                    'state' => $row['billing_state'],
                                    'zip' => $row['billing_zip'],
                                    'country' => $row['billing_country'],
                                    'phone' => $row['phone']
                                ],
                                'items' => []
                            ];
                        }
                        if ($row['product_id']) {
                            $orders[$row['id']]['items'][] = [
                                'product_id' => $row['product_id'],
                                'product_name' => $row['product_name'],
                                'quantity' => $row['quantity'],
                                'price' => $row['item_price']
                            ];
                        }
                    }

                    if (empty($orders)): ?>
                        <div class="admin-card">
                            <p>You haven't placed any orders yet.</p>
                            <a href="music.php" class="btn">Start Shopping</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <div class="admin-card">
                                <div class="order-header">
                                    <div class="order-header-main">
                                        <h3>Order #<?php echo $order['order_id']; ?></h3>
                                        <span class="order-date">
                                            <?php echo date('F j, Y \a\t g:i A', strtotime($order ['created_at'])); ?>
                                        </span>
                                    </div>
                                    <div class="order-header-details">
                                        <span class="payment-method">
                                            <?php echo ucfirst($order['payment_method']); ?>
                                        </span>
                                        <span class="order-status">
                                            Status: <?php echo ucfirst($order['status'] ?? 'pending'); ?>
                                        </span>
                                        <span class="order-total">
                                            Total: $<?php echo number_format($order['total'], 2); ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- Shipping Details -->
                                <div class="shipping-details">
                                    <h4>Shipping Details</h4>
                                    <div class="shipping-grid">
                                        <div class="shipping-info">
                                            <p><strong>Name:</strong> 
                                                <?php echo htmlspecialchars($order['shipping_details']['first_name'] . ' ' . 
                                                                         $order['shipping_details']['last_name']); ?>
                                            </p>
                                            <p><strong>Phone:</strong> 
                                                <?php echo htmlspecialchars($order['shipping_details']['phone']); ?>
                                            </p>
                                        </div>
                                        <div class="shipping-address">
                                            <p><strong>Address:</strong><br>
                                                <?php echo htmlspecialchars($order['shipping_details']['address']); ?><br>
                                                <?php echo htmlspecialchars($order['shipping_details']['city'] . ', ' . 
                                                                         $order['shipping_details']['state'] . ' ' . 
                                                                         $order['shipping_details']['zip']); ?><br>
                                                <?php echo htmlspecialchars($order['shipping_details']['country']); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Order Items -->
                                <div class="order-items">
                                    <h4>Order Items</h4>
                                    <div class="items-table">
                                        <div class="item-header">
                                            <span>Product</span>
                                            <span>Quantity</span>
                                            <span>Price</span>
                                            <span>Total</span>
                                        </div>
                                        <?php foreach ($order['items'] as $item): ?>
                                            <div class="item-row">
                                                <span class="item-name">
                                                    <?php echo htmlspecialchars($item['product_name'] ?? 'Product #' . $item['product_id']); ?>
                                                </span>
                                                <span class="item-quantity">
                                                    <?php echo $item['quantity']; ?>
                                                </span>
                                                <span class="item-price">
                                                    $<?php echo number_format($item['price'], 2); ?>
                                                </span>
                                                <span class="item-total">
                                                    $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php break; ?>
            
            <?php endswitch; ?>
        </div>

        <footer>
            <p>&copy; <?= date('Y') ?> Galactik. All rights reserved. Site made by Stefan-Andrei Musat</p>
        </footer>
    </div>

    <script>
        function toggleMenu() {
            document.getElementById('nav-menu').classList.toggle('active');
        }

        // Add success message fade out
        document.addEventListener('DOMContentLoaded', function() {
            const successMessage = document.querySelector('.success-message');
            const errorMessage = document.querySelector('.error-message');
            
            if (successMessage) {
                setTimeout(() => {
                    successMessage.style.opacity = '0';
                    setTimeout(() => {
                        successMessage.style.display = 'none';
                    }, 500);
                }, 3000);
            }
            
            if (errorMessage) {
                setTimeout(() => {
                    errorMessage.style.opacity = '0';
                    setTimeout(() => {
                        errorMessage.style.display = 'none';
                    }, 500);
                }, 3000);
            }
        });
    </script>
</body>
</html>