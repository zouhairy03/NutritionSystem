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
    $user_name = "User"; // Default name if no record found
}

// Fetch user-specific data for dashboard cards
function fetchCount($conn, $table, $user_id) {
    $query = $conn->prepare("SELECT COUNT(*) AS count FROM $table WHERE user_id = ?");
    $query->bind_param('i', $user_id);
    $query->execute();
    $result = $query->get_result();
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['count'];
    } else {
        return 0;
    }
}

$totalOrdersCount = fetchCount($conn, 'orders', $user_id);
$totalNotificationsCount = fetchCount($conn, 'notifications', $user_id);

// Fetch recent user orders
$recentOrders = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$recentOrders->bind_param('i', $user_id);
$recentOrders->execute();
$recentOrdersResult = $recentOrders->get_result();

// Fetch recent notifications
$recentNotifications = $conn->prepare("
    SELECT n.notification_id, n.title, n.message, n.status, n.created_at 
    FROM notifications n 
    JOIN user_notifications un ON n.notification_id = un.notification_id 
    WHERE un.user_id = ? 
    ORDER BY n.created_at DESC LIMIT 5
");
$recentNotifications->bind_param('i', $user_id);
$recentNotifications->execute();
$recentNotificationsResult = $recentNotifications->get_result();

// Fetch user addresses
$userAddresses = $conn->prepare("SELECT * FROM addresses WHERE user_id = ?");
$userAddresses->bind_param('i', $user_id);
$userAddresses->execute();
$userAddressesResult = $userAddresses->get_result();

// Fetch delivery data
$deliveriesQuery = $conn->prepare("
    SELECT deliveries.*, orders.order_id 
    FROM deliveries
    LEFT JOIN orders ON deliveries.order_id = orders.order_id
    WHERE orders.user_id = ?
    ORDER BY deliveries.created_at DESC
    LIMIT 5
");
$deliveriesQuery->bind_param('i', $user_id);
$deliveriesQuery->execute();
$deliveriesResult = $deliveriesQuery->get_result();

// Fetch order history data
$orderHistoryQuery = $conn->prepare("SELECT DATE(created_at) as date, COUNT(*) as count FROM orders WHERE user_id = ? GROUP BY DATE(created_at)");
$orderHistoryQuery->bind_param('i', $user_id);
$orderHistoryQuery->execute();
$orderHistoryResult = $orderHistoryQuery->get_result();

$orderHistoryLabels = [];
$orderHistoryData = [];

while ($row = $orderHistoryResult->fetch_assoc()) {
    $orderHistoryLabels[] = $row['date'];
    $orderHistoryData[] = $row['count'];
}

// Fetch monthly order data
$monthlyOrdersQuery = $conn->prepare("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
    FROM orders 
    WHERE user_id = ? 
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month
");
$monthlyOrdersQuery->bind_param('i', $user_id);
$monthlyOrdersQuery->execute();
$monthlyOrdersResult = $monthlyOrdersQuery->get_result();

$monthlyOrderLabels = [];
$monthlyOrderData = [];

while ($row = $monthlyOrdersResult->fetch_assoc()) {
    $monthlyOrderLabels[] = $row['month'];
    $monthlyOrderData[] = $row['count'];
}

// Fetch top-selling meals data
$topMealsQuery = $conn->prepare("
    SELECT meals.name AS meal_name, COUNT(orders.meal_id) AS count 
    FROM orders 
    JOIN meals ON orders.meal_id = meals.meal_id 
    WHERE orders.user_id = ? 
    GROUP BY orders.meal_id 
    ORDER BY count DESC 
    LIMIT 5
");
$topMealsQuery->bind_param('i', $user_id);
$topMealsQuery->execute();
$topMealsResult = $topMealsQuery->get_result();

$topMealsLabels = [];
$topMealsData = [];

while ($row = $topMealsResult->fetch_assoc()) {
    $topMealsLabels[] = $row['meal_name'];
    $topMealsData[] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
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
        .card .card-body {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card i {
            font-size: 1.5em;
        }
        #sidebarCollapse {
            background: #343a40;
            border: none;
            color: #fff;
            padding: 10px;
            cursor: pointer;
        }
        .welcome-message {
            background-color: whitesmoke;
            color: black;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        .data-section {
            margin-bottom: 40px;
        }
        .data-section h5 {
            font-size: 1.2em;
            margin-bottom: 20px;
        }
        .data-section ul {
            list-style: none;
            padding: 0;
        }
        .data-section ul li {
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .data-section ul li:last-child {
            border-bottom: none;
        }
        .actions {
            display: flex;
            gap: 10px;
        }
        .profile-picture {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }
        .upload-btn {
            display: block;
            margin-top: 10px;
        }
        .recent-item {
            margin-bottom: 10px;
        }
        .recent-item strong {
            display: block;
            font-size: 1.1em;
        }
        .chart-container {
            position: relative;
            margin: auto;
            height: 400px;
            width: 100%;
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
                    <img src="Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png" style="margin-right: 400px; height: 300px; width: 60%;" alt="NutriDaily Logo" class="logo">
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <div class="welcome-message">
                <h2>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h2>
            </div>

            <!-- Dashboard Cards -->
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <div class="text">
                                <div class="card-title">Total Orders</div>
                                <h2><?php echo $totalOrdersCount; ?></h2>
                            </div>
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <div class="text">
                                <div class="card-title">Total Notifications</div>
                                <h2><?php echo $totalNotificationsCount; ?></h2>
                            </div>
                            <i class="fas fa-bell"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <div class="text">
                                <div class="card-title">Total Addresses</div>
                                <h2><?php echo $userAddressesResult->num_rows; ?></h2>
                            </div>
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card text-white bg-danger">
                        <div class="card-body">
                            <div class="text">
                                <div class="card-title">Total Deliveries</div>
                                <h2><?php echo $deliveriesResult->num_rows; ?></h2>
                            </div>
                            <i class="fas fa-truck"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card data-section">
                        <div class="card-header">
                            <i class="fas fa-list"></i> Recent Orders
                        </div>
                        <div class="card-body">
                            <ul>
                                <?php while ($order = $recentOrdersResult->fetch_assoc()): ?>
                                    <li class="recent-item">
                                        <strong>Order ID:</strong> <?php echo htmlspecialchars($order['order_id'] ?? 'N/A'); ?>
                                        <span><strong>Date:</strong> <?php echo htmlspecialchars($order['created_at'] ?? 'N/A'); ?></span>
                                        <span><strong>Status:</strong> <?php echo htmlspecialchars($order['status'] ?? 'N/A'); ?></span>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Recent Notifications -->
                <div class="col-lg-6 mb-4">
                    <div class="card data-section">
                        <div class="card-header">
                            <i class="fas fa-bell"></i> Recent Notifications
                        </div>
                        <div class="card-body">
                            <ul>
                                <?php while ($notification = $recentNotificationsResult->fetch_assoc()): ?>
                                    <li class="recent-item">
                                        <strong>Notification:</strong> <?php echo htmlspecialchars($notification['message'] ?? 'N/A'); ?>
                                        <span><strong>Date:</strong> <?php echo htmlspecialchars($notification['created_at'] ?? 'N/A'); ?></span>
                                        <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#viewNotificationModal<?php echo $notification['notification_id']; ?>"><i class="fas fa-eye"></i> View</button>
                                    </li>

                                    <!-- View Notification Modal -->
                                    <div class="modal fade" id="viewNotificationModal<?php echo $notification['notification_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="viewNotificationModalLabel<?php echo $notification['notification_id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="viewNotificationModalLabel<?php echo $notification['notification_id']; ?>">Notification Details</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <p><strong>Title:</strong> <?php echo htmlspecialchars($notification['title'] ?? 'N/A'); ?></p>
                                                    <p><strong>Message:</strong> <?php echo htmlspecialchars($notification['message'] ?? 'N/A'); ?></p>
                                                    <p><strong>Status:</strong> <?php echo htmlspecialchars($notification['status'] ?? 'N/A'); ?></p>
                                                    <p><strong>Date:</strong> <?php echo htmlspecialchars($notification['created_at'] ?? 'N/A'); ?></p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Deliveries -->
            <div class="row">
                <div class="col-lg-12 mb-4">
                    <div class="card data-section">
                        <div class="card-header">
                            <i class="fas fa-truck"></i> Recent Deliveries
                        </div>
                        <div class="card-body">
                            <ul>
                                <?php while ($delivery = $deliveriesResult->fetch_assoc()): ?>
                                    <li class="recent-item">
                                        <strong>Delivery ID:</strong> <?php echo htmlspecialchars($delivery['id'] ?? 'N/A'); ?>
                                        <span><strong>Date:</strong> <?php echo htmlspecialchars($delivery['created_at'] ?? 'N/A'); ?></span>
                                        <span><strong>Status:</strong> <?php echo htmlspecialchars($delivery['status'] ?? 'N/A'); ?></span>
                                        <span><strong>Order ID:</strong> <?php echo htmlspecialchars($delivery['order_id'] ?? 'N/A'); ?></span>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Addresses -->
            <div class="row">
                <div class="col-lg-12 mb-4">
                    <div class="card data-section">
                        <div class="card-header">
                            <i class="fas fa-map-marker-alt"></i> My Addresses
                        </div>
                        <div class="card-body">
                            <ul>
                                <?php while ($address = $userAddressesResult->fetch_assoc()): ?>
                                    <li class="recent-item">
                                        <strong>Address ID:</strong> <?php echo htmlspecialchars($address['address_id'] ?? 'N/A'); ?>
                                        <span><strong>Street:</strong> <?php echo htmlspecialchars($address['street'] ?? 'N/A'); ?></span>
                                        <span><strong>City:</strong> <?php echo htmlspecialchars($address['city'] ?? 'N/A'); ?></span>
                                        <span><strong>Date:</strong> <?php echo htmlspecialchars($address['created_at'] ?? 'N/A'); ?></span>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row">
                <div class="col-lg-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-bolt"></i> Quick Actions
                        </div>
                        <div class="card-body quick-actions">
                            <a href="user_orders.php" class="btn btn-primary">View Orders</a>
                            <a href="user_notifications.php" class="btn btn-warning">View Notifications</a>
                            <a href="user_addresses.php" class="btn btn-secondary">Manage Addresses</a>
                            <a href="user_settings.php" class="btn btn-info">Account Settings</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-line"></i> Order History
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="orderHistoryChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-bar"></i> Monthly Order Volume
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="monthlyOrderChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-pie"></i> Top Selling Meals
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="topMealsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function () {
        $('#sidebarCollapse').on('click', function () {
            $('#sidebar').toggleClass('active');
        });

        // Order History Chart
        const ctx = document.getElementById('orderHistoryChart').getContext('2d');
        const orderHistoryChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($orderHistoryLabels); ?>,
                datasets: [{
                    label: 'Orders',
                    data: <?php echo json_encode($orderHistoryData); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Monthly Order Volume Chart
        const ctxMonthlyOrder = document.getElementById('monthlyOrderChart').getContext('2d');
        const monthlyOrderChart = new Chart(ctxMonthlyOrder, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($monthlyOrderLabels); ?>,
                datasets: [{
                    label: 'Orders',
                    data: <?php echo json_encode($monthlyOrderData); ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Top Selling Meals Chart
        const ctxTopMeals = document.getElementById('topMealsChart').getContext('2d');
        const topMealsChart = new Chart(ctxTopMeals, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($topMealsLabels); ?>,
                datasets: [{
                    label: 'Meals',
                    data: <?php echo json_encode($topMealsData); ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return tooltipItem.label + ': ' + tooltipItem.raw;
                            }
                        }
                    }
                }
            }
        });
    });
</script>
</body>
</html>
