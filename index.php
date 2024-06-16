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
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                background: linear-gradient(to bottom, #0f2027, #203a43, #2c5364);
                font-family: 'Roboto', sans-serif;
                color: #fff;
            }
            .container {
                text-align: center;
                padding: 50px;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 15px;
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
                backdrop-filter: blur(10px);
                max-width: 400px;
                width: 100%;
            }
            .container h1 {
                margin-bottom: 20px;
                font-size: 2.5em;
            }
            .btn {
                margin: 10px;
                font-size: 1.2em;
                width: 100%;
                border-radius: 50px;
            }
            .btn-primary {
                background-color: #007bff;
                border: none;
            }
            .btn-primary:hover {
                background-color: #0056b3;
            }
            .btn-danger {
                background-color: #dc3545;
                border: none;
            }
            .btn-danger:hover {
                background-color: #c82333;
            }
        </style>
    </head>
    <body>
    <div class="container">
        <i class="fas fa-user-shield fa-4x mb-4"></i>
        <h1>Welcome to Admin Panel</h1>
        <?php if ($isLoggedIn): ?>
            <p>You are logged in.</p>
            <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        <?php else: ?>
            <p>Please log in to access the admin panel.</p>
            <a href="login.php" class="btn btn-primary">Login</a>
        <?php endif; ?>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    </body>
    </html>
