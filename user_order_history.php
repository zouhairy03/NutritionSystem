<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}
require 'config/db.php';

// Fetch user details
$user_id = $_SESSION['user_id'];
$user_query = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
$user_query->bind_param('i', $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
if ($user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
    $user_name = $user['name'];
} else {
    $user_name = "User";
}

// Fetch user orders with JOINs
$orders_query = $conn->prepare("SELECT o.order_id, o.created_at, o.status, m.name as meal, o.quantity, o.total, o.payment_method, IFNULL(c.code, '') as coupon_code 
                                FROM orders o 
                                JOIN meals m ON o.meal_id = m.meal_id 
                                LEFT JOIN coupons c ON o.coupon_id = c.coupon_id 
                                WHERE o.user_id = ? 
                                ORDER BY o.created_at DESC");
$orders_query->bind_param('i', $user_id);
$orders_query->execute();
$orders_result = $orders_query->get_result();
$orders = [];
while ($row = $orders_result->fetch_assoc()) {
    $orders[] = $row;
}

// Function to format date
function formatDate($date) {
    return date("Y-m-d H:i:s", strtotime($date));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Order History</title>
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
        .table thead th {
            border-top: none;
        }
        .navbar-brand, .welcome-message h2 {
            color: #343a40;
        }
        .modal-content {
            border-radius: 15px;
        }
        .search-form {
            margin-bottom: 20px;
        }
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .status-pending {
            background-color: #f0ad4e;
        }
        .status-completed {
            background-color: #5cb85c;
        }
        .status-cancelled {
            background-color: #d9534f;
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
                    <img src="Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png" style="margin-right: 400px; height: 250px; width: 60%;" alt="NutriDaily Logo" class="logo">
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <!-- <div class="welcome-message">
                <h2>Welcome, <?php echo htmlspecialchars($user_name ?? 'User', ENT_QUOTES, 'UTF-8'); ?>!</h2>
            </div> -->

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-history"></i> Order History
                </div>
                <div class="card-body">
                    <form class="search-form" method="GET" action="user_order_history.php">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Search orders..." name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i> Search</button>
                            </div>
                        </div>
                    </form>

                    <?php if (count($orders) > 0): ?>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Order Date</th>
                                    <th>Status</th>
                                    <th>Meal</th>
                                    <th>Quantity</th>
                                    <th>Total (MAD)</th>
                                    <th>Coupon Code</th>
                                    <th>Payment Method</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['order_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars(formatDate($order['created_at']) ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <span class="status-indicator status-<?php echo strtolower(htmlspecialchars($order['status'] ?? '', ENT_QUOTES, 'UTF-8')); ?>"></span>
                                            <?php echo htmlspecialchars($order['status'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($order['meal'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($order['quantity'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars(number_format($order['total'], 2) ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($order['coupon_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($order['payment_method'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <button class="btn btn-primary btn-view-details" data-toggle="modal" data-target="#orderDetailsModal<?php echo $order['order_id']; ?>"><i class="fas fa-eye"></i> View Details</button>
                                            <a href="generate_invoice.php?order_id=<?php echo $order['order_id']; ?>" class="btn btn-secondary"><i class="fas fa-file-download"></i> Download Invoice</a>
                                        </td>
                                    </tr>

                                    <!-- Order Details Modal -->
                                    <div class="modal fade" id="orderDetailsModal<?php echo $order['order_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="orderDetailsModalLabel<?php echo $order['order_id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="orderDetailsModalLabel<?php echo $order['order_id']; ?>">Order Details</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order['order_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                                    <p><strong>Order Date:</strong> <?php echo htmlspecialchars(formatDate($order['created_at']) ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                                    <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                                    <p><strong>Meal:</strong> <?php echo htmlspecialchars($order['meal'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                                    <p><strong>Quantity:</strong> <?php echo htmlspecialchars($order['quantity'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                                    <p><strong>Total (MAD):</strong> <?php echo htmlspecialchars(number_format($order['total'], 2) ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                                    <p><strong>Coupon Code:</strong> <?php echo htmlspecialchars($order['coupon_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                                    <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- Pagination (Assuming you have a pagination system) -->
                        <!-- <nav aria-label="Page navigation example">
                            <ul class="pagination justify-content-center">
                                <li class="page-item"><a class="page-link" href="#">Previous</a></li>
                                <li class="page-item"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item"><a class="page-link" href="#">Next</a></li>
                            </ul>
                        </nav> -->
                    <?php else: ?>
                        <p>You have no orders yet.</p>
                    <?php endif; ?>
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
