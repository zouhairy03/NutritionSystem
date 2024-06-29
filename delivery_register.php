<?php
session_start();
include 'config/db.php'; // Database connection file

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];

    // Check if the name or email already exists
    $stmt = $conn->prepare("SELECT * FROM delivery_personnel WHERE name = ? OR email = ?");
    $stmt->bind_param("ss", $name, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $error = "Name or email already exists. Please choose another.";
    } else {
        // Prepare the SQL statement to prevent SQL injection
        $stmt = $conn->prepare("INSERT INTO delivery_personnel (name, email, phone, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $phone, $password);

        if ($stmt->execute()) {
            $success = "Registration successful. You can now log in.";
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Personnel Registration</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>

        /* body{
            background: url(bg.webp);
        } */
        .logo {
            max-width: 150px;
            margin-bottom: 20px;
        }
        .register-container {
            margin-top: 50px;
        }
        .form-title {
            margin-bottom: 30px;
            text-align: center;
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
        <div class="row justify-content-center register-container">
            <div class="col-md-6">
                <div class="text-center">
                    <img src="Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png" alt="Logo" class="logo">
                </div>
                <h1 class="text-center form-title">Delivery  Registration</h1>
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php elseif (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                    </div>
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
                        <label for="phone">Phone Number</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            </div>
                            <input type="text" class="form-control" id="phone" name="phone" required>
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
                    <button type="submit" class="btn btn-primary btn-lg btn-block"><i class="fas fa-user-plus"></i> Register</button>
                    <a href="delivery_login.php" class="btn btn-success btn-lg btn-block"><i class="fas fa-sign-in-alt"></i> Login</a>
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
