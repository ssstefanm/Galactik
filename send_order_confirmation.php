<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

function sendOrderConfirmation($user_email, $order_id, $order_items, $order_total) {
    if (empty($user_email)) {
        error_log("User email not provided for order #" . $order_id);
        return true;
    }

    try {
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
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($user_email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Order Confirmation #' . $order_id;

        // Create HTML email content with dark theme styling
        $message = "
        <html>
        <head>
            <style>
                body {
                    font-family: 'Montserrat', Arial, sans-serif;
                    line-height: 1.6;
                    background-color: #000000;
                    color: #ffffff;
                    margin: 0;
                    padding: 20px;
                }

                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    background-color: #111111;
                    padding: 20px;
                    border-radius: 8px;
                    border: 1px solid rgba(255, 255, 255, 0.1);
                }

                .header {
                    text-align: center;
                    margin-bottom: 30px;
                }

                .header h1 {
                    color: #ffffff;
                    margin: 0;
                    padding: 20px 0;
                }

                .divider {
                    width: 100%;
                    height: 1px;
                    background-color: rgba(255, 255, 255, 0.1);
                    margin: 10px 0;
                }

                .success-message {
                    background: rgba(40, 167, 69, 0.2);
                    border: 1px solid #28a745;
                    padding: 15px;
                    border-radius: 4px;
                    margin-bottom: 20px;
                    text-align: center;
                    color: #ffffff;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                    background: #050505;
                    border-radius: 8px;
                    overflow: hidden;
                }

                th {
                    background: rgba(40, 40, 40, 0.3);
                    color: #ffffff;
                    padding: 15px;
                    text-align: left;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                }

                td {
                    padding: 15px;
                    color: #ffffff;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                }

                h1, h2, h3, h4, h5, h6 {
                                            color: #ffffff !important;
                                            font-family: 'Montserrat', Arial, sans-serif;
                                            margin: 20px 0;
                                        }

                .section-title {
                    font-size: 24px;
                    font-weight: bold;
                    color: #ffffff !important;
                    margin: 30px 0 20px 0;
                    padding-bottom: 10px;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                }

                .total-row td {
                    background: rgba(40, 40, 40, 0.3);
                    font-weight: bold;
                }

                .ticket {
                    background: rgba(40, 40, 40, 0.3);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    border-radius: 8px;
                    padding: 20px;
                    margin: 20px 0;
                    color: #ffffff;
                }

                .ticket-header {
                    font-size: 18px;
                    font-weight: bold;
                    margin-bottom: 15px;
                    padding-bottom: 10px;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                }

                .ticket-details {
                    margin: 15px 0;
                }

                .qr-container {
                    background: #ffffff;
                    padding: 10px;
                    border-radius: 4px;
                    display: inline-block;
                    margin: 10px 0;
                }

                .footer {
                    text-align: center;
                    margin-top: 30px;
                    padding: 20px;
                    border-top: 1px solid rgba(255, 255, 255, 0.1);
                    color: #ffffff;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Galactik</h1>
                    <div class='divider'></div>
                </div>

                <div class='success-message'>
                    Order #${order_id} Confirmed
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>";

        // Get film products list
        $db = new PDO('mysql:host=-;dbname=-', 
                      '-', '-');
        $stmt = $db->query("SELECT name FROM products WHERE product_type = 'film'");
        $films = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($order_items as $item) {
            $item_total = $item['price'] * $item['quantity'];
            $message .= "
                <tr>
                    <td>{$item['name']}</td>
                    <td>{$item['quantity']}</td>
                    <td>\${$item['price']}</td>
                    <td>\${$item_total}</td>
                </tr>";
        }

        $message .= "
                <tr class='total-row'>
                    <td colspan='3' style='text-align: right;'><strong>Order Total:</strong></td>
                    <td><strong>\${$order_total}</strong></td>
                </tr>
            </tbody>
        </table>";

        // Add movie tickets section if there are any film products
        $hasFilms = false;
        foreach ($order_items as $item) {
            if (in_array($item['name'], $films)) {
                if (!$hasFilms) {
                    $message .= "<h2>Your Movie Tickets</h2>";
                    $hasFilms = true;
                }
                
                for ($i = 1; $i <= $item['quantity']; $i++) {
                    $ticket_id = $order_id . '-' . md5($item['name'] . $i);
                    // Create a simple but unique string for the QR code
                    $qr_data = base64_encode("GALACTIK-TICKET-{$order_id}-{$item['name']}-{$i}-{$ticket_id}");
                    // Use a different QR code service (QR Server)
                    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qr_data);

                    $message .= "
                    <div class='ticket'>
                        <div class='ticket-header'>
                            {$item['name']} - Ticket #{$i}
                        </div>
                        <div class='ticket-details'>
                            <p><strong>Order ID:</strong> {$order_id}</p>
                            <p><strong>Ticket:</strong> {$i} of {$item['quantity']}</p>
                            <p><strong>Ticket ID:</strong> {$ticket_id}</p>
                        </div>
                        <div class='qr-container'>
                            <img src='{$qr_url}' alt='Ticket QR Code' width='200' height='200'>
                        </div>
                    </div>";
                }
            }
        }

        $message .= "
                <div class='footer'>
                    <p>Thank you for shopping with us!</p>
                    <p>© " . date('Y') . " Galactik</p>
                </div>
            </div>
        </body>
        </html>";

        $mail->Body = $message;
        $mail->AltBody = strip_tags($message);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}