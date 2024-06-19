<?php
session_start();

$isLoggedIn = isset($_SESSION['admin_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
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
            /* background: linear-gradient(to bottom, #0f2027, #203a43, #2c5364); */
        }
        .container {
            background: rgba(255, 255, 255, 0.1);
            padding: 40px 60px;
            border-radius: 15px;
            color:    #809B53 ;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .container img {
            margin-bottom: 20px;
            height: 100px;
        }
        .container h1 {
            margin-bottom: 30px;
            font-size: 2em;
        }
        .btn-primary {
            background: #007bff;
            border: none;
            height: 50px;
            font-size: 1.2em;
            border-radius: 50px;
            width: 100%;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .btn-danger {
            background: #dc3545;
            border: none;
            height: 50px;
            font-size: 1.2em;
            border-radius: 50px;
            width: 100%;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .icon {
            font-size: 4em;
            margin-bottom: 20px;
            color: #007bff;
        }
    </style>
</head>
<body>
<div class="container">
    <img src="Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png" style="width: 80%; height: 250px;" alt="NutriDaily Logo">
    <h1>Welcome to Admin Panel</h1>
    <?php if ($isLoggedIn): ?>
        <p>You are logged in.</p>
        <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    <?php else: ?>
        <p>Please log in to access the admin panel.</p>
        <a href="login.php" class="btn btn-success">Login</a>
    <?php endif; ?>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
