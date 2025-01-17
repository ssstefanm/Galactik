<?php
session_start();

include("connection.php");
include("functions.php");

$user_data = check_login($con);
$statusMsg = '';

if($_SERVER['REQUEST_METHOD'] == "POST")
{
    // Verify reCAPTCHA first
    $secretKey = '-';
    
    if(!empty($_POST['g-recaptcha-response'])) {
        // Google reCAPTCHA v3 verification API Request
        $api_url = 'https://www.google.com/recaptcha/api/siteverify';
        $resq_data = array(
            'secret' => $secretKey,
            'response' => $_POST['g-recaptcha-response']
        );

        $curlConfig = array(
            CURLOPT_URL => $api_url,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $resq_data,
            CURLOPT_SSL_VERIFYPEER => false
        );

        $ch = curl_init();
        curl_setopt_array($ch, $curlConfig);
        $response = curl_exec($ch);
        curl_close($ch);

        $responseData = json_decode($response);

        // For v3, verify the score is above a threshold (e.g., 0.5)
        if(!empty($responseData) && $responseData->success && $responseData->score >= 0.5) {
            // reCAPTCHA verified, now process login
            $user_name = $_POST['user_name'];
            $password = $_POST['password'];

            if(!empty($user_name) && !empty($password) && !is_numeric($user_name))
            {
                // Use prepared statements to prevent SQL injection
                $query = "SELECT * FROM users WHERE user_name = ? LIMIT 1";
                $stmt = mysqli_prepare($con, $query);
                mysqli_stmt_bind_param($stmt, "s", $user_name);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                if($result && mysqli_num_rows($result) > 0)
                {
                    $user_data = mysqli_fetch_assoc($result);
                    
                    // Verify password (assuming it's already hashed in the database)
                    if($user_data['password'] === $password) // In production, use password_verify()
                    {
                        $_SESSION['user_id'] = $user_data['user_id'];
                        header("Location: index.php");
                        die;
                    } else {
                        $statusMsg = "Wrong username or password!";
                    }
                } else {
                    $statusMsg = "Wrong username or password!";
                }
            } else {
                $statusMsg = "Please enter valid information!";
            }
        } else {
            $statusMsg = "Security check failed, please try again.";
        }
    } else {
        $statusMsg = "Security check failed, please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Galactik</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <!-- Updated to reCAPTCHA v3 -->
    <script src="https://www.google.com/recaptcha/api.js?render=-"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            color:white;
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
        }
        
        nav {
            background: black;
            padding: 1rem;
        }
        
        nav a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            margin: 0 0.5rem;
        }
        
        nav a:hover {
            background: #555;
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
        }
        .nav-menu.active {
            z-index: 10;
            display: block;
        }

        .container {
            max-width: 400px;
            margin: 2rem auto;
            background: rgba(40, 40, 40, 0.15);
            padding: 2rem;
            border-radius: 5px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        input {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 1rem;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            border-radius: 4px;
        }
        
        .btn {
            background: #333;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            cursor: pointer;
            width: 100%;
            margin-bottom: 1rem;
        }
        
        .btn:hover {
            background: #555;
        }
        
        a {
            color: white;
            text-decoration: none;
        }
        
        a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            color: #ff4444;
            margin-bottom: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <header style="text-align: center;">
        <h1>Galactik</h1>
        <div style="width: 100%; height: 1px; background-color: white; margin: 10px auto;"></div>
        <button class="menu-btn" onclick="toggleMenu()">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <nav class="nav-menu" id="nav-menu">
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li><a href="/index.php?page=home">Home</a></li>
                <li><a href="/index.php?page=artists">Artists</a></li>
                <li><a href="/index.php?page=releases">Releases</a></li>
                <li><a href="/index.php?page=contact">Contact</a></li>
                <li><a href="/index.php?page=about">About</a></li>
                
                <?php if (isset($user_data) && $user_data !== false): ?>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="/login.php">Login</a></li>
                    <li><a href="/signup.php">Signup</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <div class="container">
        <h2>Login</h2>
        <?php if(!empty($statusMsg)){ ?>
            <div class="error-message"><?php echo $statusMsg; ?></div>
        <?php } ?>
        <form method="post" id="login-form">
            <input type="text" name="user_name" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="hidden" name="g-recaptcha-response" id="recaptchaResponse">
            <button type="submit" class="btn">Login</button>
            <a href="signup.php">Click to Signup</a>
        </form>
    </div>

    <script>
        function toggleMenu() {
            document.getElementById('nav-menu').classList.toggle('active');
        }

        // reCAPTCHA v3
        grecaptcha.ready(function() {
            // Add submit event listener to the form
            document.getElementById('login-form').addEventListener('submit', function(e) {
                e.preventDefault();
                grecaptcha.execute('-', {action: 'login'}).then(function(token) {
                    document.getElementById('recaptchaResponse').value = token;
                    document.getElementById('login-form').submit();
                });
            });
        });
    </script>
</body>
</html>