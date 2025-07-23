<?php
include 'includes/header.php';
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// Handle update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
    $new_address = mysqli_real_escape_string($conn, $_POST['address']);
    $new_city = mysqli_real_escape_string($conn, $_POST['city']);
    $new_pincode = mysqli_real_escape_string($conn, $_POST['pincode']);
    $new_state = mysqli_real_escape_string($conn, $_POST['state']);

    $update_sql = "UPDATE users 
                   SET mobile = '$new_mobile', 
                       address = '$new_address', 
                       city = '$new_city', 
                       pincode = '$new_pincode', 
                       state = '$new_state' 
                   WHERE id = $user_id";

    if (mysqli_query($conn, $update_sql)) {
        $message = "Profile updated successfully.";
    } else {
        $message = "Error updating profile: " . mysqli_error($conn);
    }
}

$sql = "SELECT name, email, mobile, address, city, pincode, state FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .profile-wrapper {
            max-width: 600px;
            margin: 40px auto;
            background-color: #fff;
            padding: 18px 22px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
            font-size: 14px;
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            font-size: 22px;
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: 4px;
        }

        .form-control {
            width: 100%;
            padding: 6px 8px;
            font-size: 13px;
            margin-bottom: 12px;
            box-sizing: border-box;
        }

        .readonly {
            background-color: #eee;
        }

        .btn {
            padding: 8px 16px;
            background: #2b6777;
            border: none;
            color: white;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            border-radius: 4px;
            margin-top: 10px;
        }

        .btn:hover {
            background: #1c4a5a;
        }

        .message {
            margin-top: 10px;
            font-weight: bold;
            color: green;
            font-size: 13px;
            text-align: center;
        }
    </style>
</head>
<body>

<!-- Profile Content -->
<div class="profile-wrapper">
    <h2>My Profile</h2>

    <?php if ($message): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Name</label>
        <input type="text" class="form-control readonly" value="<?= htmlspecialchars($user['name']) ?>" readonly>

        <label>Email</label>
        <input type="email" class="form-control readonly" value="<?= htmlspecialchars($user['email']) ?>" readonly>

        <label>Mobile Number</label>
        <input type="text" name="mobile" class="form-control" value="<?= htmlspecialchars($user['mobile']) ?>" required>

        <label>Address</label>
        <textarea name="address" class="form-control" required><?= htmlspecialchars($user['address']) ?></textarea>

        <label>City/District</label>
        <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($user['city']) ?>" required>

        <label>Pin Code</label>
        <input type="text" name="pincode" class="form-control" value="<?= htmlspecialchars($user['pincode']) ?>" required>

        <label>State</label>
        <input type="text" name="state" class="form-control" value="<?= htmlspecialchars($user['state']) ?>" required>

        <button type="submit" class="btn">Update Profile</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
