<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}
require 'config/db.php';

// Fetch user details
$user_id = $_SESSION['user_id'];

// Fetch available coupons
$coupons_query = $conn->prepare("SELECT * FROM coupons WHERE expiry_date >= NOW() ORDER BY expiry_date ASC");
$coupons_query->execute();
$coupons_result = $coupons_query->get_result();

// Handle coupon application
if (isset($_POST['apply_coupon'])) {
    $coupon_code = $_POST['coupon_code'];
    $order_id = $_POST['order_id'];

    // Check if coupon is valid
    $coupon_query = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND expiry_date >= NOW()");
    $coupon_query->bind_param('s', $coupon_code);
    $coupon_query->execute();
    $coupon_result = $coupon_query->get_result();

    if ($coupon_result->num_rows > 0) {
        $coupon = $coupon_result->fetch_assoc();

        // Apply coupon to the order
        $apply_coupon_query = $conn->prepare("UPDATE orders SET coupon_id = ? WHERE order_id = ? AND user_id = ?");
        $apply_coupon_query->bind_param('iii', $coupon['coupon_id'], $order_id, $user_id);
        if ($apply_coupon_query->execute()) {
            $success_message = "Coupon applied successfully!";
        } else {
            $error_message = "Failed to apply coupon.";
        }
    } else {
        $error_message = "Invalid or expired coupon.";
    }
}

// Fetch user orders
$orders_query = $conn->prepare("SELECT * FROM orders WHERE user_id = ? AND coupon_id IS NULL ORDER BY created_at DESC");
$orders_query->bind_param('i', $user_id);
$orders_query->execute();
$orders_result = $orders_query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Coupons</title>
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
            background: #809B53;
            color: #fff;
            transition: all 0.3s;
        }
        #sidebar.active {
            margin-left: -250px;
        }
        #sidebar .sidebar-header {
            padding: 20px;
            background: #809B53;
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
            color: #3E8E41;
            background: #fff;
        }
        #content {
            width: 100%;
            padding: 20px;
            min-height: 100vh;
            background-color: #f1f2f6;
        }
        .container {
            margin-top: 50px;
        }
        .card {
            margin-bottom: 20px;
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .card .card-header {
            background-color: #809B53;
            color: #fff;
            border-bottom: none;
            border-radius: 15px 15px 0 0;
            font-size: 1.2em;
            padding: 15px;
        }
        .card .card-body {
            padding: 30px;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-user"></i> User Dashboard</h3>
        </div>
        <ul class="list-unstyled components">
            <li><a href="user_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="user_profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="user_favorites.php"><i class="fas fa-heart"></i> Favorites</a></li>
            <li><a href="user_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li><a href="user_order_history.php"><i class="fas fa-history"></i> Order History</a></li>
            <li><a href="user_notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
            <li><a href="user_addresses.php"><i class="fas fa-map-marker-alt"></i> Addresses</a></li>
            <li><a href="user_deliveries.php"><i class="fas fa-truck"></i> Deliveries</a></li>
            <!-- <li><a href="user_settings.php"><i class="fas fa-cogs"></i> Settings</a></li> -->
            <li><a href="user_help.php"><i class="fas fa-question-circle"></i> Help & Support</a></li>
            <li><a href="user_feedback.php"><i class="fas fa-comments"></i> Feedback & Ratings</a></li>
            <li><a href="user_community.php"><i class="fas fa-users"></i> Community</a></li>
            <li><a href="user_coupons.php"><i class="fas fa-tag"></i> Coupons & Offers</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <!-- Page Content -->
    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-info">
                    <i class="fas fa-align-left"></i>
                    <span></span>
                </button>
                <div class="ml-auto">
                    <img src="Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png" style="margin-right: 460px; height: 250px; width: 60%;" alt="NutriDaily Logo" class="logo">
                </div>
            </div>
        </nav>

        <div class="container">
            <h2>Coupons & Offers</h2>
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header"><i class="fas fa-tags"></i> Available Coupons</div>
                <div class="card-body">
                    <?php if ($coupons_result->num_rows > 0): ?>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Coupon Code</th>
                                    <th>Discount</th>
                                    <th>Expiration Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($coupon = $coupons_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($coupon['code'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($coupon['discount_percentage'] ?? ''); ?>%</td>
                                        <td><?php echo htmlspecialchars($coupon['expiry_date'] ?? ''); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No available coupons found.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><i class="fas fa-tag"></i> Apply Coupon</div>
                <div class="card-body">
                    <form method="POST" action="user_coupons.php">
                        <div class="form-group">
                            <label for="coupon_code">Coupon Code:</label>
                            <input type="text" class="form-control" id="coupon_code" name="coupon_code" required>
                        </div>
                        <div class="form-group">
                            <label for="order_id">Order ID:</label>
                            <select class="form-control" id="order_id" name="order_id" required>
                                <?php while ($order = $orders_result->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($order['order_id'] ?? ''); ?>"><?php echo htmlspecialchars($order['order_id'] ?? ''); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary" name="apply_coupon">Apply Coupon</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
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
