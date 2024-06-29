<?php
session_start();

// Include the database configuration file
include('config/db.php');

// Initialize variables to store error messages and input values
$email = "";
$password = "";
$email_error = $password_error = $login_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    // Validate email
    if (empty($email)) {
        $email_error = "Please enter your email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email_error = "Please enter a valid email.";
    }

    // Validate password
    if (empty($password)) {
        $password_error = "Please enter your password.";
    }

    // Check credentials if there are no errors
    if (empty($email_error) && empty($password_error)) {
        // Prepare a select statement
        $sql = "SELECT id, email, password FROM delivery_personnel WHERE email = ?";

        if ($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_email);

            // Set parameters
            $param_email = $email;

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Store result
                mysqli_stmt_store_result($stmt);

                // Check if email exists, if yes then verify password
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $email, $stored_password);
                    if (mysqli_stmt_fetch($stmt)) {
                        if ($password === $stored_password) {
                            // Password is correct, so start a new session
                            session_start();

                            // Store data in session variables
                            $_SESSION["id"] = $id;
                            $_SESSION["email"] = $email;
                            $_SESSION["loggedin"] = true;

                            // Redirect user to the welcome page
                            header("location: delivery_dashboard.php");
                        } else {
                            // Display an error message if password is not valid
                            $login_error = "Invalid password.";
                        }
                    }
                } else {
                    // Display an error message if email doesn't exist
                    $login_error = "No account found with that email.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }

    // Close connection
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Login</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body{
            background:url(F7AED43C-2A3B-4DC6-AF79-B59741EE1433.jpeg) ;
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
                <h2 class="text-center form-title">Delivery Login</h2>
                <?php
                if (!empty($login_error)) {
                    echo '<div class="alert alert-danger">' . $login_error . '</div>';
                }
                ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            </div>
                            <input type="email" name="email" id="email" class="form-control <?php echo (!empty($email_error)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($email); ?>">
                            <span class="invalid-feedback"><?php echo $email_error; ?></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            </div>
                            <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_error)) ? 'is-invalid' : ''; ?>">
                            <div class="input-group-append">
                                <span class="input-group-text" id="toggle-password"><i class="fas fa-eye"></i></span>
                            </div>
                            <span class="invalid-feedback"><?php echo $password_error; ?></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary btn-lg btn-block" value="Login">
                        <a href="delivery_register.php" class="btn btn-success btn-lg btn-block"><i class="fas fa-user-plus"></i> Register</a>
                    </div>
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
