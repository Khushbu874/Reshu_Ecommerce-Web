<?php
include 'config/db.php';
session_start();
$msg = "";

if (!isset($_SESSION['reset_email'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $email = $_SESSION['reset_email'];

    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $new_pass, $email);

    if ($stmt->execute()) {
        $msg = "✅ Password updated successfully!";
        session_unset();
        session_destroy();
        header("Refresh: 2; url=login.php");
        exit();
    } else {
        $msg = "❌ Failed to update password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <style>
        body {
            background: linear-gradient(120deg, #c2e9fb, #a1c4fd);
            font-family: Arial, sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            background: white;
            padding: 30px 40px;
            box-shadow: 0 0 10px rgba(0,0,0,0.15);
            border-radius: 8px;
            width: 100%;
            max-width: 400px;
        }
        h2 {
            text-align: center;
            margin-bottom: 25px;
        }
        input[type="password"] {
            width: 93.25%;
            padding: 12px;
            margin: 12px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: #28a745;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #218838;
        }
        .msg {
            margin-top: 15px;
            color: red;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Reset Your Password</h2>
    <form method="POST">
        <input type="password" name="new_password" placeholder="Enter New Password" required>
        <button type="submit">Set New Password</button>
    </form>
    <div class="msg"><?php echo $msg; ?></div>
</div>
</body>
</html>
