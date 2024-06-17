<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require 'config/db.php';

// Initialize variables
$user_id = $street = $city = $state = $zip_code = $country = "";
$user_id_err = $street_err = $city_err = $state_err = $zip_code_err = $country_err = "";

// Fetch users for the dropdown
$users_query = $conn->query("SELECT user_id, name FROM users");

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate user ID
    if (empty(trim($_POST["user_id"]))) {
        $user_id_err = "Please select a user.";
    } else {
        $user_id = trim($_POST["user_id"]);
    }

    // Validate street
    if (empty(trim($_POST["street"]))) {
        $street_err = "Please enter a street.";
    } else {
        $street = trim($_POST["street"]);
    }

    // Validate city
    if (empty(trim($_POST["city"]))) {
        $city_err = "Please enter a city.";
    } else {
        $city = trim($_POST["city"]);
    }

    // Validate state
    if (empty(trim($_POST["state"]))) {
        $state_err = "Please enter a state.";
    } else {
        $state = trim($_POST["state"]);
    }

    // Validate zip code
    if (empty(trim($_POST["zip_code"]))) {
        $zip_code_err = "Please enter a zip code.";
    } else {
        $zip_code = trim($_POST["zip_code"]);
    }

    // Validate country
    if (empty(trim($_POST["country"]))) {
        $country_err = "Please enter a country.";
    } else {
        $country = trim($_POST["country"]);
    }

    // Check for errors before inserting in database
    if (empty($user_id_err) && empty($street_err) && empty($city_err) && empty($state_err) && empty($zip_code_err) && empty($country_err)) {
        // Prepare an insert statement
        $sql = "INSERT INTO addresses (user_id, street, city, state, zip_code, country) VALUES (?, ?, ?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("isssss", $param_user_id, $param_street, $param_city, $param_state, $param_zip_code, $param_country);

            // Set parameters
            $param_user_id = $user_id;
            $param_street = $street;
            $param_city = $city;
            $param_state = $state;
            $param_zip_code = $zip_code;
            $param_country = $country;

            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Address added successfully
                header("Location: addresses.php");
                exit();
            } else {
                echo "Something went wrong. Please try again later.";
            }

            // Close statement
            $stmt->close();
        }
    }

    // Close connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Address</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
        }
        .wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
        }
        #sidebar {
            min-width: 250px;
            max-width: 250px;
            background: #343a40;
            color: #fff;
            transition: all 0.3s;
        }
        #sidebar.active {
            margin-left: -250px;
        }
        #sidebar .sidebar-header {
            padding: 20px;
            background: #343a40;
        }
        #sidebar ul.components {
            padding: 20px 0;
        }
        #sidebar ul li a {
            padding: 10px;
            font-size: 1.1em;
            display: block;
            color: #fff;
        }
        #sidebar ul li a:hover {
            color: #343a40;
            background: #fff;
        }
        #content {
            width: 100%;
            padding: 20px;
            min-height: 100vh;
            background-color: #f1f2f6;
        }
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-control {
            border-radius: 0.25rem;
        }
        .btn-primary {
            background-color: #343a40;
            border-color: #343a40;
        }
        .btn-primary:hover {
            background-color: #23272b;
            border-color: #1d2124;
        }
        .form-title {
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
        }
        .help-block {
            color: #dc3545;
        }
        .tooltip-inner {
            background-color: #343a40;
            color: #fff;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <h3>Admin Dashboard</h3>
        </div>
        <ul class="list-unstyled components">
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li><a href="coupons.php"><i class="fas fa-tags"></i> Coupons</a></li>
            <li><a href="maladies.php"><i class="fas fa-notes-medical"></i> Maladies</a></li>
            <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
            <li><a href="meals.php"><i class="fas fa-utensils"></i> Meals</a></li>
            <li><a href="payments.php"><i class="fas fa-dollar-sign"></i> Payments</a></li>
            <li><a href="deliveries.php"><i class="fas fa-truck"></i> Deliveries</a></li>
            <li><a href="delivers.php"><i class="fas fa-user-shield"></i> Deliver Personnel</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <!-- Page Content -->
    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-info">
                    <i class="fas fa-align-left"></i>
                    <span></span>
                </button>
            </div>
        </nav>

        <div class="container-fluid">
            <div class="form-container">
                <h2 class="form-title">Add New Address</h2>
                <p>Please fill this form to add a new address.</p>
                <form id="addressForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group <?php echo (!empty($user_id_err)) ? 'has-error' : ''; ?>">
                        <label for="user_id">User <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="right" title="Select the user associated with this address"></i></label>
                        <select name="user_id" id="user_id" class="form-control">
                            <option value="">Select a user</option>
                            <?php while ($user = $users_query->fetch_assoc()): ?>
                                <option value="<?php echo $user['user_id']; ?>" <?php echo $user['user_id'] == $user_id ? 'selected' : ''; ?>>
                                    <?php echo $user['name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <span class="help-block"><?php echo $user_id_err; ?></span>
                    </div>
                    <div class="form-group <?php echo (!empty($street_err)) ? 'has-error' : ''; ?>">
                        <label for="street">Street <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="right" title="Enter the street address"></i></label>
                        <input type="text" name="street" id="street" class="form-control" value="<?php echo $street; ?>">
                        <span class="help-block"><?php echo $street_err; ?></span>
                    </div>
                    <div class="form-group <?php echo (!empty($city_err)) ? 'has-error' : ''; ?>">
                        <label for="city">City <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="right" title="Enter the city"></i></label>
                        <input type="text" name="city" id="city" class="form-control" value="<?php echo $city; ?>">
                        <span class="help-block"><?php echo $city_err; ?></span>
                    </div>
                    <div class="form-group <?php echo (!empty($state_err)) ? 'has-error' : ''; ?>">
                        <label for="state">State <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="right" title="Enter the state"></i></label>
                        <input type="text" name="state" id="state" class="form-control" value="<?php echo $state; ?>">
                        <span class="help-block"><?php echo $state_err; ?></span>
                    </div>
                    <div class="form-group <?php echo (!empty($zip_code_err)) ? 'has-error' : ''; ?>">
                        <label for="zip_code">Zip Code <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="right" title="Enter the zip code"></i></label>
                        <input type="text" name="zip_code" id="zip_code" class="form-control" value="<?php echo $zip_code; ?>">
                        <span class="help-block"><?php echo $zip_code_err; ?></span>
                    </div>
                    <div class="form-group <?php echo (!empty($country_err)) ? 'has-error' : ''; ?>">
                        <label for="country">Country <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="right" title="Enter the country"></i></label>
                        <input type="text" name="country" id="country" class="form-control" value="<?php echo $country; ?>">
                        <span class="help-block"><?php echo $country_err; ?></span>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Submit">
                        <a href="addresses.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    $(document).ready(function () {
        $('#sidebarCollapse').on('click', function () {
            $('#sidebar').toggleClass('active');
        });

        $('[data-toggle="tooltip"]').tooltip();

        $('#addressForm').on('submit', function (e) {
            let isValid = true;

            if ($('#user_id').val() === '') {
                isValid = false;
                $('#user_id').next('.help-block').text('Please select a user.');
            }

            if ($('#street').val() === '') {
                isValid = false;
                $('#street').next('.help-block').text('Please enter a street.');
            }

            if ($('#city').val() === '') {
                isValid = false;
                $('#city').next('.help-block').text('Please enter a city.');
            }

            if ($('#state').val() === '') {
                isValid = false;
                $('#state').next('.help-block').text('Please enter a state.');
            }

            if ($('#zip_code').val() === '') {
                isValid = false;
                $('#zip_code').next('.help-block').text('Please enter a zip code.');
            }

            if ($('#country').val() === '') {
                isValid = false;
                $('#country').next('.help-block').text('Please enter a country.');
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    });
</script>
</body>
</html>
