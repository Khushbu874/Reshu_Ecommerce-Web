<?php
include 'config/db.php';
session_start();
$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            // $_SESSION['is_admin'] = ($user['role'] === 'admin'); // Add this
            header("Location: index.php");
            exit();
        } else {
            $msg = "Incorrect password.";
        }
    } else {
        $msg = "No account found with that email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Reshu eCommerce</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(120deg, #c2e9fb, #a1c4fd);
            font-family: Arial, sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-box {
            background: white;
            padding: 10px 20px;
            width: 350px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .login-box h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }

        .login-box input {
            width: 93%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        .login-box button {
            width: 100%;
            padding: 12px;
            background-color: #3b5998;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }

        .login-box button:hover {
            background-color: #2d4373;
        }

        .message {
            margin-top: 10px;
            text-align: center;
            color: red;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Login</h2>
        <form method="POST" onsubmit="return validateForm();">
            <input type="email" id="email" name="email" placeholder="Email Address" required>
            <input type="password" id="password" name="password" placeholder="Your Password" required>
            <button type="submit">Login</button>
        </form>
        <p class="message"><?php echo $msg; ?></p>
        <p style="text-align:center;">Don't have an account? <a href="register.php">Register</a></p>
        <!-- <p style="text-align:center;"><a href="forgot_password.php">Forgot Password?</a></p> -->

    </div>

    <script>
        function validateForm() {
            let email = document.getElementById("email").value;
            let pass = document.getElementById("password").value;

            if (!email || !pass) {
                alert("All fields are required!");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
