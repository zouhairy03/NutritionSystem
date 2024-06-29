<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require 'config/db.php';

// Fetch admin details
$admin_id = $_SESSION['admin_id'];
$admin_query = $conn->query("SELECT name FROM admins WHERE admin_id = $admin_id");
$admin = $admin_query->fetch_assoc();
$admin_name = $admin['name'];

// Fetch the necessary data for dashboard cards
$totalUsers = $conn->query("SELECT COUNT(*) AS count FROM users")->fetch_assoc()['count'];
$totalOrders = $conn->query("SELECT COUNT(*) AS count FROM orders")->fetch_assoc()['count'];
$totalCoupons = $conn->query("SELECT COUNT(*) AS count FROM coupons")->fetch_assoc()['count'];
$totalMaladies = $conn->query("SELECT COUNT(*) AS count FROM maladies")->fetch_assoc()['count'];
$totalNotifications = $conn->query("SELECT COUNT(*) AS count FROM notifications")->fetch_assoc()['count'];
$totalMeals = $conn->query("SELECT COUNT(*) AS count FROM meals")->fetch_assoc()['count'];
$totalPayments = $conn->query("SELECT COUNT(*) AS count FROM payments")->fetch_assoc()['count'];
$totalAddresses = $conn->query("SELECT COUNT(*) AS count FROM addresses")->fetch_assoc()['count'];
$totalAdmins = $conn->query("SELECT COUNT(*) AS count FROM admins")->fetch_assoc()['count'];
$newUsersThisMonth = $conn->query("SELECT COUNT(*) AS count FROM users WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")->fetch_assoc()['count'];

// Fetch recent activities
$recentOrders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
$recentUsers = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
$recentNotifications = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5");
$recentActivities = $conn->query("SELECT * FROM activity_logs ORDER BY timestamp DESC LIMIT 5");
$recentAddresses = $conn->query("SELECT * FROM addresses ORDER BY created_at DESC LIMIT 5");
$recentAdmins = $conn->query("SELECT * FROM admins ORDER BY created_at DESC LIMIT 5");

// Fetch monthly sales data
$monthlySalesData = [];
$monthlySalesQuery = $conn->query("SELECT MONTH(created_at) AS month, SUM(total) AS total_sales FROM orders GROUP BY MONTH(created_at)");
while ($row = $monthlySalesQuery->fetch_assoc()) {
    $monthlySalesData[$row['month']] = $row['total_sales'];
}

// Fetch top-selling meals data
$topMealsData = [];
$topMealsQuery = $conn->query("SELECT meals.name AS meal_name, COUNT(orders.meal_id) AS meal_count FROM orders JOIN meals ON orders.meal_id = meals.meal_id GROUP BY orders.meal_id ORDER BY meal_count DESC LIMIT 5");
while ($row = $topMealsQuery->fetch_assoc()) {
    $topMealsData[] = ['name' => $row['meal_name'], 'count' => $row['meal_count']];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
            background:    #809B53 ;
            color: #fff;
            transition: all 0.3s;
        }
        #sidebar.active {
            margin-left: -250px;
        }
        #sidebar .sidebar-header {
            padding: 20px;
            background:    #809B53 ;
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
            font-size: 2.5em;
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
    </style>
</head>
<body>
<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar">
    <div class="sidebar-header">
    <h3><i class="fas fa-user-shield"></i> Admin Dashboard</h3>
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
    <li><a href="delivers.php"><i class="fas fa-user-shield"></i> Delivery Personnel</a></li>
    <li><a href="send_delivery_notifications.php"><i class="fas fa-bell"></i> Delivery Notifications</a></li>
    <li><a href="delivery_support_tickets.php"><i class="fas fa-ticket-alt"></i> Delivery Support Tickets</a></li> <!-- Added for delivery support tickets -->
    <li><a href="reports.php"><i class="fas fa-chart-pie"></i> Reports</a></li>
    <li><a href="settings.php"><i class="fas fa-cogs"></i> Settings</a></li>
    <li><a href="support_tickets.php"><i class="fas fa-ticket-alt"></i> Support Tickets</a></li>
    <li><a href="feedback.php"><i class="fas fa-comments"></i> User Feedback</a></li>
    <li><a href="inventory.php"><i class="fas fa-boxes"></i> Inventory</a></li>
    <li><a href="activity_logs.php"><i class="fas fa-list"></i> Activity Logs</a></li>
    <li><a href="financial_overview.php"><i class="fas fa-dollar-sign"></i> Financial Overview</a></li>
    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
</ul>



</nav>


    <!-- Page Content -->
    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-light ">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-info">
                    <i class="fas fa-align-left"></i>
                    <span></span>
                    
                </button>
                <div class="ml-auto">
                    <img src="Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png" style="margin-right: 400px;height: 250px; width: 60%;" alt="NutriDaily Logo" class="logo">
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <div class="welcome-message">
                <h2>Welcome, <?php echo $admin_name; ?>!</h2>
            </div>

            <!-- Dashboard Cards -->
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <div class="text">
                                <div class="card-title">Total Users</div>
                                <h2><?php echo $totalUsers; ?></h2>
                            </div>
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <div class="text">
                                <div class="card-title">Total Orders</div>
                                <h2><?php echo $totalOrders; ?></h2>
                            </div>
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <div class="text">
                                <div class="card-title">Total Coupons</div>
                                <h2><?php echo $totalCoupons; ?></h2>
                            </div>
                            <i class="fas fa-tags"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <div class="text">
                                <div class="card-title">Total Maladies</div>
                                <h2><?php echo $totalMaladies; ?></h2>
                            </div>
                            <i class="fas fa-notes-medical"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card text-white bg-danger">
                        <div class="card-body">
                            <div class="text">
                                <div class="card-title">Total Notifications</div>
                                <h2><?php echo $totalNotifications; ?></h2>
                            </div>
                            <i class="fas fa-bell"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card text-white bg-secondary">
                        <div class="card-body">
                            <div class="text">
                                <div class="card-title">Total Meals</div>
                                <h2><?php echo $totalMeals; ?></h2>
                            </div>
                            <i class="fas fa-utensils"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card text-white bg-dark">
                        <div class="card-body">
                            <div class="text">
                                <div class="card-title">Total Payments</div>
                                <h2><?php echo $totalPayments; ?></h2>
                            </div>
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <div class="text">
                                <div class="card-title">Total Addresses</div>
                                <h2><?php echo $totalAddresses; ?></h2>
                            </div>
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <div class="text">
                                <div class="card-title">Total Admins</div>
                                <h2><?php echo $totalAdmins; ?></h2>
                            </div>
                            <i class="fas fa-user-shield"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <div class="text">
                                <div class="card-title">New Users This Month</div>
                                <h2><?php echo $newUsersThisMonth; ?></h2>
                            </div>
                            <i class="fas fa-user-plus"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Metrics -->
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-line"></i> Monthly Sales
                        </div>
                        <div class="card-body">
                            <canvas id="monthlySalesChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-bar"></i> Top-Selling Meals
                        </div>
                        <div class="card-body">
                            <canvas id="topMealsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card data-section">
                        <div class="card-header">
                            <i class="fas fa-list"></i> Recent Activities
                        </div>
                        <div class="card-body">
                            <ul>
                                <?php while ($activity = $recentActivities->fetch_assoc()): ?>
                                    <li>
                                        <strong>User ID:</strong> <?php echo $activity['user_id']; ?><br>
                                        <strong>Action:</strong> <?php echo $activity['action']; ?><br>
                                        <strong>Date:</strong> <?php echo $activity['timestamp']; ?>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Addresses -->
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card data-section">
                        <div class="card-header">
                            <i class="fas fa-map-marker-alt"></i> Recent Addresses
                        </div>
                        <div class="card-body">
                            <ul>
                                <?php while ($address = $recentAddresses->fetch_assoc()): ?>
                                    <li>
                                        <strong>Address ID:</strong> <?php echo $address['address_id']; ?><br>
                                        <strong>Street:</strong> <?php echo $address['street']; ?><br>
                                        <strong>City:</strong> <?php echo $address['city']; ?><br>
                                        <strong>Date:</strong> <?php echo $address['created_at']; ?>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Recent Admins -->
                <div class="col-lg-6 mb-4">
                    <div class="card data-section">
                        <div class="card-header">
                            <i class="fas fa-user-shield"></i> Recent Admins
                        </div>
                        <div class="card-body">
                            <ul>
                                <?php while ($admin = $recentAdmins->fetch_assoc()): ?>
                                    <li>
                                        <strong>Admin ID:</strong> <?php echo $admin['admin_id']; ?><br>
                                        <strong>Name:</strong> <?php echo $admin['name']; ?><br>
                                        <strong>Email:</strong> <?php echo $admin['email']; ?><br>
                                        <strong>Joined:</strong> <?php echo $admin['created_at']; ?>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notifications Panel -->
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card data-section">
                        <div class="card-header">
                            <i class="fas fa-bell"></i> Notifications
                        </div>
                        <div class="card-body">
                            <ul>
                                <?php while ($notification = $recentNotifications->fetch_assoc()): ?>
                                    <li>
                                        <strong>Notification:</strong> <?php echo $notification['message']; ?><br>
                                        <strong>Date:</strong> <?php echo $notification['created_at']; ?>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-bolt"></i> Quick Actions
                        </div>
                        <div class="card-body quick-actions">
                            <a href="users.php" class="btn btn-primary">Add User</a>
                            <a href="orders.php" class="btn btn-success">Add Order</a>
                            <a href="coupons.php" class="btn btn-info">Add Coupon</a>
                            <a href="meals.php" class="btn btn-secondary">Add Meal</a>
                            <a href="addresses.php" class="btn btn-warning">Add Address</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function () {
        $('#sidebarCollapse').on('click', function () {
            $('#sidebar').toggleClass('active');
        });

        // Monthly Sales Chart
        const monthlySalesCtx = document.getElementById('monthlySalesChart').getContext('2d');
        new Chart(monthlySalesCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Sales',
                    data: <?php echo json_encode(array_values($monthlySalesData)); ?>,
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

        // Top-Selling Meals Chart
        const topMealsCtx = document.getElementById('topMealsChart').getContext('2d');
        new Chart(topMealsCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($topMealsData, 'name')); ?>,
                datasets: [{
                    label: 'Top-Selling Meals',
                    data: <?php echo json_encode(array_column($topMealsData, 'count')); ?>,
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
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>
</body>
</html>
