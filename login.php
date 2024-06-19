<?php
session_start();
require 'config/db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Using prepared statements to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            // Direct comparison since password is not hashed
            if ($password === $admin['password']) {
                $_SESSION['admin_id'] = $admin['admin_id'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
        $stmt->close();
    } else {
        $error = "Failed to prepare the statement.";
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Roboto', sans-serif;
            margin: 0;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.1);
            padding: 40px 60px;
            border-radius: 15px;
            color: black;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .login-container img {
            margin-bottom: 20px;
            height: 100px;
        }
        .login-container h2 {
            margin-bottom: 30px;
            font-size: 2em;
        }
        .form-control {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid #fff;
            color: #809B53;
            height: 50px;
            font-size: 1.2em;
        }
        .form-control::placeholder {
            color: #809B53;
        }
        .btn-primary {
            background: #007bff;
            border: none;
            height: 50px;
            font-size: 1.2em;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .alert {
            background: #ff4d4d;
            border: none;
            color: #ffff;
        }
        .icon {
            font-size: 4em;
            margin-bottom: 20px;
            color: #007bff;
        }
    </style>
</head>
<body>
<div class="login-container">
    <img src="Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png" style="width: 80%; height: 150px;" alt="NutriDaily Logo">
    <h2>Admin Login</h2>
    <?php if (!empty($error)): ?>
        <div class="alert"><?php echo $error; ?></div>
    <?php endif; ?>
    <form action="login.php" method="POST">
        <div class="form-group">
            <input type="email" name="email" class="form-control" required placeholder="Email">
        </div>
        <div class="form-group">
            <input type="password" name="password" class="form-control" required placeholder="Password">
        </div>
        <button type="submit" class="btn btn-success btn-block">Login</button>
    </form>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
