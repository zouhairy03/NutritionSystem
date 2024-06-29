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

// Fetch data for reports
// User registrations per month
$userRegistrationsQuery = $conn->query("SELECT MONTH(created_at) AS month, COUNT(*) AS count FROM users GROUP BY MONTH(created_at)");
$userRegistrationsData = [];
while ($row = $userRegistrationsQuery->fetch_assoc()) {
    $userRegistrationsData[$row['month']] = $row['count'];
}

// Orders per month
$ordersQuery = $conn->query("SELECT MONTH(created_at) AS month, COUNT(*) AS count FROM orders GROUP BY MONTH(created_at)");
$ordersData = [];
while ($row = $ordersQuery->fetch_assoc()) {
    $ordersData[$row['month']] = $row['count'];
}

// Sales per month
$salesQuery = $conn->query("SELECT MONTH(created_at) AS month, SUM(total) AS total_sales FROM orders GROUP BY MONTH(created_at)");
$salesData = [];
while ($row = $salesQuery->fetch_assoc()) {
    $salesData[$row['month']] = $row['total_sales'];
}

// Order status
$orderStatusQuery = $conn->query("SELECT status, COUNT(*) AS count FROM orders GROUP BY status");
$orderStatusData = [];
while ($row = $orderStatusQuery->fetch_assoc()) {
    $orderStatusData[$row['status']] = $row['count'];
}

// Meal popularity
$mealPopularityQuery = $conn->query("SELECT meals.name AS meal_name, COUNT(orders.meal_id) AS meal_count FROM orders JOIN meals ON orders.meal_id = meals.meal_id GROUP BY orders.meal_id ORDER BY meal_count DESC");
$mealPopularityData = [];
while ($row = $mealPopularityQuery->fetch_assoc()) {
    $mealPopularityData[] = ['name' => $row['meal_name'], 'count' => $row['meal_count']];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .card-header {
            background:    #809B53 ;
            color: #fff;
            border-bottom: none;
            border-radius: 15px 15px 0 0;
        }
        .tab-content > .tab-pane {
            display: none;
        }
        .tab-content > .active {
            display: block;
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
        <li><a href="reports.php"><i class="fas fa-chart-pie"></i> Reports</a></li>
        <li><a href="settings.php"><i class="fas fa-cogs"></i> Settings</a></li>
        <li><a href="support_tickets.php"><i class="fas fa-ticket-alt"></i> Support Tickets</a></li>
        <li><a href="feedback.php"><i class="fas fa-comments"></i> User Feedback</a></li>
        <li><a href="inventory.php"><i class="fas fa-boxes"></i> Inventory</a></li>
        <!-- <li><a href="delivery_routes.php"><i class="fas fa-route"></i> Delivery Routes</a></li> -->
        <!-- <li><a href="marketing.php"><i class="fas fa-bullhorn"></i> Marketing Campaigns</a></li> -->
        <li><a href="activity_logs.php"><i class="fas fa-list"></i> Activity Logs</a></li>
        <li><a href="financial_overview.php"><i class="fas fa-dollar-sign"></i> Financial Overview</a></li>

        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</nav>

    <!-- Page Content -->
    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-success">
                    <i class="fas fa-align-left"></i>
                    <span></span>
                </button>
                <div class="ml-auto">
                    <img src="Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png" style="margin-right: 400px;height: 250px; width: 60%;" alt="NutriDaily Logo" class="logo">
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <ul class="nav nav-tabs" id="reportTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="registrations-tab" data-toggle="tab" href="#registrations" role="tab" aria-controls="registrations" aria-selected="true">User Registrations</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="orders-tab" data-toggle="tab" href="#orders" role="tab" aria-controls="orders" aria-selected="false">Orders</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="sales-tab" data-toggle="tab" href="#sales" role="tab" aria-controls="sales" aria-selected="false">Sales</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="order-status-tab" data-toggle="tab" href="#order-status" role="tab" aria-controls="order-status" aria-selected="false">Order Status</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="meal-popularity-tab" data-toggle="tab" href="#meal-popularity" role="tab" aria-controls="meal-popularity" aria-selected="false">Meal Popularity</a>
                </li>
            </ul>
            <div class="tab-content" id="reportTabsContent">
                <div class="tab-pane fade show active" id="registrations" role="tabpanel" aria-labelledby="registrations-tab">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-line"></i> User Registrations per Month
                        </div>
                        <div class="card-body">
                            <canvas id="userRegistrationsChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="orders" role="tabpanel" aria-labelledby="orders-tab">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-bar"></i> Orders per Month
                        </div>
                        <div class="card-body">
                            <canvas id="ordersChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="sales" role="tabpanel" aria-labelledby="sales-tab">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-dollar-sign"></i> Sales per Month
                        </div>
                        <div class="card-body">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="order-status" role="tabpanel" aria-labelledby="order-status-tab">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-list-alt"></i> Order Status
                        </div>
                        <div class="card-body">
                            <canvas id="orderStatusChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="meal-popularity" role="tabpanel" aria-labelledby="meal-popularity-tab">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-utensils"></i> Meal Popularity
                        </div>
                        <div class="card-body">
                            <canvas id="mealPopularityChart"></canvas>
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
<script>
    $(document).ready(function () {
        $('#sidebarCollapse').on('click', function () {
            $('#sidebar').toggleClass('active');
        });

        // User Registrations Chart
        const userRegistrationsCtx = document.getElementById('userRegistrationsChart').getContext('2d');
        new Chart(userRegistrationsCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'User Registrations',
                    data: <?php echo json_encode(array_values($userRegistrationsData)); ?>,
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

        // Orders Chart
        const ordersCtx = document.getElementById('ordersChart').getContext('2d');
        new Chart(ordersCtx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Orders',
                    data: <?php echo json_encode(array_values($ordersData)); ?>,
                    backgroundColor: 'rgba(255, 206, 86, 0.2)',
                    borderColor: 'rgba(255, 206, 86, 1)',
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

        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Sales',
                    data: <?php echo json_encode(array_values($salesData)); ?>,
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

        // Order Status Chart
        const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
        new Chart(orderStatusCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_keys($orderStatusData)); ?>,
                datasets: [{
                    label: 'Order Status',
                    data: <?php echo json_encode(array_values($orderStatusData)); ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true
            }
        });

        // Meal Popularity Chart
        const mealPopularityCtx = document.getElementById('mealPopularityChart').getContext('2d');
        new Chart(mealPopularityCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($mealPopularityData, 'name')); ?>,
                datasets: [{
                    label: 'Meals Ordered',
                    data: <?php echo json_encode(array_column($mealPopularityData, 'count')); ?>,
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    borderColor: 'rgba(153, 102, 255, 1)',
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
