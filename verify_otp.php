<?php
session_start();
include("connection.php");
include("functions.php");

$statusMsg = '';

if (!isset($_COOKIE['pending_user_id'])) {
    header("Location: signup.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['otp'])) {
    $otp = $_POST['otp'];
    $pending_user_id = $_COOKIE['pending_user_id'];

    // Verify OTP
    $stmt = $con->prepare("SELECT * FROM pending_users WHERE id = ? AND otp_code = ? AND verified = 0 AND expires_at > NOW()");
    $stmt->bind_param("is", $pending_user_id, $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        
        // Create user in main users table
        $user_id = random_num(20);
        $query = "INSERT INTO users (user_id, user_name, password) VALUES (?, ?, ?)";
        $stmt = $con->prepare($query);
        $stmt->bind_param("sss", $user_id, $user_data['user_name'], $user_data['password']);
        
        if ($stmt->execute()) {
            // Mark OTP as verified
            $con->query("UPDATE pending_users SET verified = 1 WHERE id = " . $pending_user_id);
            
            // Clear cookie
            setcookie('pending_user_id', '', time() - 3600, '/');
            
            // Redirect to login
            $_SESSION['success_message'] = "Account created successfully! Please login.";
            header("Location: login.php");
            exit();
        } else {
            $statusMsg = "Error creating account. Please try again.";
        }
    } else {
        $statusMsg = "Invalid or expired OTP code.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Galactik</title>
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
            max-width: 400px;
            margin: 2rem auto;
            background: rgba(40, 40, 40, 0.15);
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: white;
        }
        
        .otp-input {
            width: 100%;
            padding: 15px;
            margin-bottom: 1rem;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            border-radius: 4px;
            font-size: 24px;
            letter-spacing: 8px;
            text-align: center;
        }
        
        .otp-input::placeholder {
            letter-spacing: 2px;
            font-size: 16px;
        }
        
        .timer {
            text-align: center;
            margin: 15px 0;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
            padding: 10px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 4px;
        }
        
        .btn {
            background: rgba(40, 40, 40, 0.3);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 300;
            width: 100%;
            margin-top: 10px;
        }

        .btn:hover {
            background: rgba(60, 60, 60, 0.4);
            transform: translateY(-2px);
        }
        
        .error-message {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid #dc3545;
            color: white;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .info-text {
            text-align: center;
            margin-bottom: 20px;
            color: rgba(255, 255, 255, 0.8);
        }
        
        a {
            color: white;
            text-decoration: none;
            display: block;
            text-align: center;
            margin-top: 15px;
            transition: opacity 0.3s ease;
        }
        
        a:hover {
            opacity: 0.8;
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
    </style>
</head>
<body>
    <header>
        <h1>Galactik</h1>
        <div style="width: 100%; height: 1px; background-color: white; margin: 10px auto;"></div>
    </header>

    <div class="container">
        <h2>Verify Your Email</h2>
        
        <?php if(!empty($statusMsg)){ ?>
            <div class="error-message"><?php echo $statusMsg; ?></div>
        <?php } ?>

        <p class="info-text">
            Please enter the verification code sent to your email.
        </p>

        <form method="post">
            <input type="text" name="otp" class="otp-input" 
                   maxlength="6" placeholder="Enter code" 
                   required autocomplete="off">
            
            <div class="timer" id="timer">Time remaining: 15:00</div>
            
            <button type="submit" class="btn">Verify Email</button>
            <a href="signup.php">Cancel verification</a>
        </form>
    </div>

    <footer>
        <p>&copy; <?= date('Y') ?> Galactik. All rights reserved. Site made by Stefan-Andrei Musat</p>
    </footer>

    <script>
        // Timer functionality
        let timeLeft = 15 * 60; // 15 minutes in seconds
        const timerDisplay = document.getElementById('timer');

        const timer = setInterval(() => {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerDisplay.textContent = `Time remaining: ${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft <= 0) {
                clearInterval(timer);
                timerDisplay.textContent = 'Code expired';
                timerDisplay.style.color = '#dc3545';
                setTimeout(() => {
                    window.location.href = 'signup.php';
                }, 2000);
            }
            timeLeft--;
        }, 1000);

        // Auto format OTP input
        document.querySelector('.otp-input').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>