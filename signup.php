<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("connection.php");
include("functions.php");

// PHPMailer includes with proper namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'email_config.php';

$user_data = check_login($con);
$statusMsg = '';

function generateOTP() {
    return sprintf('%06d', mt_rand(0, 999999));
}

function sendOTPEmail($email, $otp) {
    try {
        // Create new PHPMailer instance with proper namespace
        $mail = new PHPMailer(true);

        // Server settings
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, 'Galactik');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Email - Galactik';
        
        // Email template
        $mail->Body = "
            <html>
            <body style='font-family: Arial, sans-serif; color: white; background: #111;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2>Verify Your Email</h2>
                    <p>Your verification code is: <strong style='font-size: 24px; letter-spacing: 5px;'>{$otp}</strong></p>
                    <p>This code will expire in 15 minutes.</p>
                </div>
            </body>
            </html>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail error: " . $e->getMessage());
        return false;
    }
}

if($_SERVER['REQUEST_METHOD'] == "POST")
{
    // Verify reCAPTCHA first
    $secretKey = '-';
    
    if(!empty($_POST['g-recaptcha-response'])) {
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

        if(!empty($responseData) && $responseData->success && $responseData->score >= 0.5) {
            $user_name = $_POST['user_name'];
            $password = $_POST['password'];

            if(!empty($user_name) && !empty($password) && !is_numeric($user_name))
            {
                // Check if email already exists in users table
                $stmt = $con->prepare("SELECT id FROM users WHERE user_name = ?");
                $stmt->bind_param("s", $user_name);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $statusMsg = "Email already registered!";
                } else {
                    // Generate OTP and expiration time
                    $otp = generateOTP();
                    $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                    
                    // Store in pending_users
                    $stmt = $con->prepare("INSERT INTO pending_users (user_name, password, otp_code, expires_at) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $user_name, $password, $otp, $expires_at);
                    
                    if($stmt->execute() && sendOTPEmail($user_name, $otp)) {
                        // Set cookie with pending user ID
                        setcookie('pending_user_id', $stmt->insert_id, time() + (15 * 60), '/');
                        
                        // Redirect to verification page
                        header("Location: verify_otp.php");
                        exit();
                    } else {
                        $statusMsg = "Error sending verification email. Please try again.";
                    }
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
    <title>Signup - Galactik</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
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
        <h2>Signup</h2>
        <?php if(!empty($statusMsg)){ ?>
            <div class="error-message"><?php echo $statusMsg; ?></div>
        <?php } ?>
        <form method="post" id="signup-form">
            <input type="text" name="user_name" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="hidden" name="g-recaptcha-response" id="recaptchaResponse">
            <button type="submit" class="btn">Signup</button>
            <a href="login.php">Click to Login</a>
        </form>
    </div>

    <script>
        function toggleMenu() {
            document.getElementById('nav-menu').classList.toggle('active');
        }

        // Wait for grecaptcha to be ready
        window.onload = function() {
            grecaptcha.ready(function() {
                // Add submit event listener to the form
                document.getElementById('signup-form').addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    grecaptcha.execute('-', {action: 'signup'})
                        .then(function(token) {
                            // Set the token in the hidden input
                            document.getElementById('recaptchaResponse').value = token;
                            // Submit the form
                            document.getElementById('signup-form').submit();
                        })
                        .catch(function(error) {
                            console.error('reCAPTCHA error:', error);
                        });
                });
            });
        };
    </script>
</body>
</html>