<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("connection.php");
include("functions.php");

$user_data = check_login($con);

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Fetch products from the database
$query = "SELECT id, name, price, image_url FROM products WHERE product_type = 'film'";
$result = $con->query($query);
$products = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Handle adding items to cart
if (isset($_POST['add_to_cart'])) {
    if (!$user_data) {
        header("Location: login.php");
        exit();
    }

    $product_id = $_POST['product_id'];
    $product_name = $_POST['name'];
    $product_price = floatval($_POST['price']);
    
    // Check if item already exists in cart
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] === $product_id) {
            $item['quantity']++;
            $found = true;
            break;
        }
    }
    unset($item); // Break the reference
    
    // If item wasn't found, add it as new
    if (!$found) {
        $_SESSION['cart'][] = [
            'product_id' => $product_id,
            'name' => $product_name,
            'price' => $product_price,
            'quantity' => 1
        ];
    }
    
    $_SESSION['message'] = "Item added to cart successfully!";
    header("Location: film.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Film - Galactik</title>
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
            min-height: calc(100vh - 200px);
        }
        
        .products {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 5.5rem;
        }
        
        .product {
            background: #050505;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .product img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 15px;
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
        
        .menu-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 10px;
        }
        
        .cart-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: 2px solid white;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-family: "Montserrat";
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .cart-btn:hover {
            background: rgba(255, 255, 255, 0.1);
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
        
        .responsive-iframe-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            max-width: 100%;
            background: black;
            margin-bottom: 20px;
        }
        
        .responsive-iframe-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .success-message {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid #28a745;
            color: white;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
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
            .products {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 10px;
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
        <a href="cart.php" class="cart-btn">
            🛒 Cart (<?php echo array_sum(array_column($_SESSION['cart'], 'quantity')); ?>)
        </a>
        <nav class="nav-menu" id="nav-menu">
            <ul>
                <li><a href="index.php?page=home">Home</a></li>
                <li><a href="index.php?page=artists">Artists</a></li>
                <li><a href="music.php">Music</a></li>
                <li><a href="film.php">Film</a></li>
                <li><a href="index.php?page=contact">Contact</a></li>
                <li><a href="index.php?page=about">About</a></li>
                <?php if (isset($user_data) && $user_data !== false): ?>
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
        <?php if (isset($_SESSION['message'])): ?>
            <div class="success-message">
                <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <h1>Film</h1>
        <section>
            <h2> </h2>
            <div class="responsive-iframe-container">
                <iframe width="560" height="315" src="https://www.youtube.com/embed/bE-UKZcIU-I?si=uWonEI_bfhqF1_Dn" start=1 ; allow="autoplay; encrypted-media" allowfullscreen></iframe>
            </div>
        </section>

        <section>
            <h2>Film Products</h2>
            <?php if (!empty($products)): ?>
                <div class="products">
                    <?php foreach ($products as $product): ?>
                        <div class="product">
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p>$<?php echo number_format($product['price'], 2); ?></p>
                            <form method="POST">
                                <input type="hidden" name="product_id" 
                                       value="<?php echo htmlspecialchars($product['id']); ?>">
                                <input type="hidden" name="name" 
                                       value="<?php echo htmlspecialchars($product['name']); ?>">
                                <input type="hidden" name="price" 
                                       value="<?php echo $product['price']; ?>">
                                <button type="submit" name="add_to_cart" class="btn">
                                    <?php echo $user_data ? 'Add to Cart' : 'Login to Buy'; ?>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No products available at the moment.</p>
            <?php endif; ?>
        </section>
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