<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require 'config/db.php';

// Handle form submission
if (isset($_POST['add_order'])) {
    $user_id = $_POST['user_id'];
    $meal_id = $_POST['meal_id'];
    $address_id = $_POST['address_id'];
    $coupon_id = $_POST['coupon_id'];
    $status = $_POST['status'];
    $total = $_POST['total'];

    // Insert order
    $sql_order = "INSERT INTO orders (user_id, meal_id, address_id, coupon_id, status, total, created_at, updated_at) 
                  VALUES ('$user_id', '$meal_id', '$address_id', '$coupon_id', '$status', '$total', NOW(), NOW())";
    $conn->query($sql_order);
    header("Location: orders.php");
}

// Fetch users, meals, addresses, and coupons for dropdowns
$usersResult = $conn->query("SELECT * FROM users");
$mealsResult = $conn->query("SELECT * FROM meals");
$addressesResult = $conn->query("SELECT * FROM addresses");
$couponsResult = $conn->query("SELECT * FROM coupons");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Order</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
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
        #sidebar ul p {
            color: #fff;
            padding: 10px;
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
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="users.php">Users</a></li>
            <li><a href="orders.php">Orders</a></li>
            <li><a href="coupons.php">Coupons</a></li>
            <li><a href="maladies.php">Maladies</a></li>
            <li><a href="notifications.php">Notifications</a></li>
            <li><a href="meals.php">Meals</a></li>
            <li><a href="payments.php">Payments</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <!-- Page Content -->
    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-info">
                    <i class="fas fa-align-left"></i>
                    <span>Toggle Sidebar</span>
                </button>
            </div>
        </nav>

        <div class="container mt-5">
            <h2>Add New Order</h2>
            <form action="add_order.php" method="POST">
                <div class="form-group">
                    <label for="user_id">User:</label>
                    <select class="form-control" id="user_id" name="user_id" required>
                        <?php while ($user = $usersResult->fetch_assoc()): ?>
                            <option value="<?php echo $user['user_id']; ?>"><?php echo $user['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="meal_id">Meal:</label>
                    <select class="form-control" id="meal_id" name="meal_id" required>
                        <?php while ($meal = $mealsResult->fetch_assoc()): ?>
                            <option value="<?php echo $meal['meal_id']; ?>"><?php echo $meal['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="address_id">Address:</label>
                    <select class="form-control" id="address_id" name="address_id" required>
                        <?php while ($address = $addressesResult->fetch_assoc()): ?>
                            <option value="<?php echo $address['address_id']; ?>"><?php echo $address['street']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="coupon_id">Coupon:</label>
                    <select class="form-control" id="coupon_id" name="coupon_id">
                        <option value="">None</option>
                        <?php while ($coupon = $couponsResult->fetch_assoc()): ?>
                            <option value="<?php echo $coupon['coupon_id']; ?>"><?php echo $coupon['code']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Status:</label>
                    <input type="text" class="form-control" id="status" name="status" required>
                </div>
                <div class="form-group">
                    <label for="total">Total:</label>
                    <input type="text" class="form-control" id="total" name="total" required>
                </div>
                <button type="submit" class="btn btn-primary" name="add_order">Add Order</button>
            </form>
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
    });
</script>
</body>
</html>
