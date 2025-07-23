<?php
include 'config/db.php';
session_start();
$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['send_otp']) || isset($_POST['resend_otp'])) {
        $email = $_POST['email'];
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $otp = rand(100000, 999999);
            $_SESSION['reset_email'] = $email;
            $_SESSION['otp'] = $otp;

            // Send OTP via mail
            $subject = "Your OTP for Password Reset";
            $message = "Your OTP to reset your password is: $otp";
            $headers = "From: noreply@reshu-ecommerce.com";

            if (mail($email, $subject, $message, $headers)) {
                $msg = isset($_POST['resend_otp']) ? "OTP resent successfully!" : "OTP sent successfully!";
            } else {
                $msg = "Failed to send OTP. Please try again.";
            }
        } else {
            $msg = "Email not found.";
        }
    } elseif (isset($_POST['verify_otp'])) {
        $entered_otp = $_POST['otp'];
        if (isset($_SESSION['otp']) && $_SESSION['otp'] == $entered_otp) {
            header("Location: reset_password.php");
            exit();
        } else {
            $msg = "Invalid OTP.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - Reshu Ecommerce</title>
    <style>
        body {
            background: linear-gradient(120deg, #c2e9fb, #a1c4fd);
            font-family: Arial, sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }
        .container {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            width: 90%;
            max-width: 320px;
        }
        h2 {
            text-align: center;
            color: #333;
            font-size: 20px;
            margin-bottom: 20px;
        }
        input[type="email"],
        input[type="text"] {
            width: 93.25%;
            padding: 10px;
            margin-top: 12px;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-size: 14px;
        }
        button {
            width: 100%;
            padding: 10px;
            margin-top: 12px;
            border: none;
            border-radius: 4px;
            background-color: #007bff;
            color: white;
            font-size: 14px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .message {
            color: red;
            text-align: center;
            margin-top: 10px;
            font-size: 13px;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Forgot Password</h2>

    <!-- Step 1: Send or Resend OTP -->
    <form method="POST">
        <input type="email" name="email" placeholder="Enter your registered email" 
                value="<?= isset($_SESSION['reset_email']) ? htmlspecialchars($_SESSION['reset_email']) : '' ?>" 
                required>
        <button type="submit" name="send_otp">Send OTP</button>
        <?php if (isset($_SESSION['reset_email'])): ?>
            <button type="submit" name="resend_otp">Resend OTP</button>
        <?php endif; ?>
    </form>

    <!-- Step 2: OTP Verification -->
    <?php if (isset($_SESSION['otp'])): ?>
    <form method="POST">
        <input type="text" name="otp" placeholder="Enter the OTP" required>
        <button type="submit" name="verify_otp">Verify OTP</button>
    </form>
    <?php endif; ?>

    <?php if (!empty($msg)): ?>
        <p class="message"><?= $msg; ?></p>
    <?php endif; ?>
</div>
</body>
</html>
