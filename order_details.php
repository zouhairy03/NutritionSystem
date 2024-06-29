<?php
session_start();
include 'config/db.php'; // Database connection file

// Check if the user is logged in, otherwise redirect to the login page
if (!isset($_SESSION['user'])) {
    header('Location: user_login.php');
    exit();
}

// Retrieve user information from the session
$user = $_SESSION['user'];
$user_id = $user['user_id'];

// Get order ID from query parameter
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Fetch order details
$order_query = "
    SELECT o.order_id, o.quantity, o.status, o.total, o.payment_method, o.created_at, 
           m.name AS meal_name, m.price AS meal_price, 
           u.name AS user_name, u.email AS user_email, u.phone AS user_phone, 
           c.code AS coupon_code, c.discount_percentage AS coupon_discount
    FROM orders o
    LEFT JOIN meals m ON o.meal_id = m.meal_id
    LEFT JOIN users u ON o.user_id = u.user_id
    LEFT JOIN coupons c ON o.coupon_id = c.coupon_id
    WHERE o.order_id = ? AND o.user_id = ?
";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order_result = $stmt->get_result();
$order = $order_result->fetch_assoc();
$stmt->close();

// Redirect if the order does not exist or does not belong to the user
if (!$order) {
    header('Location: user_orders.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            display: flex;
        }
        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background-color: #343a40;
            padding-top: 20px;
            transition: transform 0.3s ease;
        }
        .sidebar.hidden {
            transform: translateX(-250px);
        }
        .sidebar a {
            color: white;
            padding: 15px;
            text-decoration: none;
            display: block;
        }
        .sidebar a:hover {
            background-color: #575d63;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
            width: 100%;
            transition: margin-left 0.3s ease;
        }
        .content.expanded {
            margin-left: 0;
        }
        .toggle-btn {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <button class="btn btn-primary toggle-btn" onclick="toggleSidebar()">☰</button>
    <div class="sidebar" id="sidebar">
        <a href="user_dashboard.php">Dashboard</a>
        <a href="user_profile.php">Profile</a>
        <a href="user_orders.php">Orders</a>
        <a href="user_meal_plans.php">Meal Plans</a>
        <a href="user_malady.php">Malady</a>
        <a href="logout.php">Logout</a>
    </div>
    <div class="content" id="content">
        <div class="container mt-5">
            <h1>Order Details</h1>
            <table class="table table-bordered">
                <tr>
                    <th>Order ID</th>
                    <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                </tr>
                <tr>
                    <th>Meal</th>
                    <td><?php echo htmlspecialchars($order['meal_name']); ?></td>
                </tr>
                <tr>
                    <th>Quantity</th>
                    <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                </tr>
                <tr>
                    <th>Price</th>
                    <td><?php echo htmlspecialchars($order['meal_price']); ?></td>
                </tr>
                <tr>
                    <th>Total</th>
                    <td><?php echo htmlspecialchars($order['total']); ?></td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td><?php echo htmlspecialchars($order['status']); ?></td>
                </tr>
                <tr>
                    <th>Payment Method</th>
                    <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                </tr>
                <tr>
                    <th>Order Date</th>
                    <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                </tr>
                <tr>
                    <th>Coupon Code</th>
                    <td><?php echo htmlspecialchars($order['coupon_code'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Discount Percentage</th>
                    <td><?php echo htmlspecialchars($order['coupon_discount'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>User Name</th>
                    <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                </tr>
                <tr>
                    <th>User Email</th>
                    <td><?php echo htmlspecialchars($order['user_email']); ?></td>
                </tr>
                <tr>
                    <th>User Phone</th>
                    <td><?php echo htmlspecialchars($order['user_phone']); ?></td>
                </tr>
            </table>
            <a href="user_orders.php" class="btn btn-secondary">Back to Orders</a>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            var sidebar = document.getElementById('sidebar');
            var content = document.getElementById('content');
            sidebar.classList.toggle('hidden');
            content.classList.toggle('expanded');
        }
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
