<?php
session_start();
include 'config/db.php'; // Database connection file

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare the SQL statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND password = ?");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        // User found, set session variables and redirect to the user's dashboard
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['user_id'];
        header('Location: user_dashboard.php');
        exit();
    } else {
        $error = "Invalid email or password.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
       body {
    background: url(user.png) fixed no-repeat center center;
    background-size: cover;
    width: 100%;
    height: 100%;
    margin: 0;
}

        
        .logo {
            max-width: 200px;
            margin-bottom: 20px;
        }
        .login-container {
            margin-top: 50px;
        }
        .form-title {
            margin-bottom: 30px;
        }
        .btn-block + .btn-block {
            margin-top: 10px;
        }
        .input-group-text {
            background-color: #fff;
        }
        .input-group-append .input-group-text {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center login-container">
            <div class="col-md-6">
                <div class="text-center">
                    <img src="Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png" alt="Logo" class="logo">
                </div>
                <h1 class="text-center form-title">User Login</h1>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">Email address</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            </div>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            </div>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="input-group-append">
                                <span class="input-group-text" id="toggle-password"><i class="fas fa-eye"></i></span>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success btn-lg btn-block"><i class="fas fa-sign-in-alt"></i> Login</button>
                    <a href="user_register.php" class="btn btn-primary btn-lg btn-block"><i class="fas fa-user-plus"></i> Register</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        document.getElementById('toggle-password').addEventListener('click', function () {
            var passwordInput = document.getElementById('password');
            var icon = this.querySelector('i');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>
