<?php
include 'config/db.php';
$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $check = $conn->query("SELECT * FROM users WHERE email='$email'");
    if ($check->num_rows > 0) {
        $msg = "Email already exists!";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $password);
        if ($stmt->execute()) {
            $msg = "Registration successful. <a href='login.php'>Login here</a>";
        } else {
            $msg = "Error: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Reshu eCommerce</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(120deg, #84fab0, #8fd3f4);
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .register-box {
            background: white;
            padding: 10px 20px;
            width: 350px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .register-box h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }

        .register-box input {
            width: 93%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        .register-box button {
            width: 100%;
            padding: 12px;
            background-color: #2b6777;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }

        .register-box button:hover {
            background-color: #1f4f5a;
        }

        .message {
            margin-top: 10px;
            text-align: center;
            color: red;
        }
    </style>
</head>
<body>
    <div class="register-box">
        <h2>Register</h2>
        <form method="POST">
            <input type="text" name="name" placeholder="Your Name" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Create Password" required>
            <button type="submit">Sign Up</button>
        </form>
        <p class="message"><?php echo $msg; ?></p>
        <p style="text-align:center;">Already have an account? <a href="login.php">Login</a></p>
    </div>
</body>
</html>
