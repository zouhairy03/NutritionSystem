<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: delivery_login.php");
    exit();
}
require 'config/db.php';

// Fetch delivery personnel details
$delivery_id = $_SESSION['id'];
$delivery_query = $conn->query("SELECT name FROM delivery_personnel WHERE id = $delivery_id");
if ($delivery_query === false) {
    die('Error fetching delivery person details: ' . $conn->error);
}
$delivery = $delivery_query->fetch_assoc();
$delivery_name = $delivery['name'];

// Fetch dashboard data
$totalOrders = $conn->query("SELECT COUNT(*) AS count FROM orders")->fetch_assoc()['count'];
$totalDeliveries = $conn->query("SELECT COUNT(*) AS count FROM deliveries WHERE delivery_person_id = $delivery_id")->fetch_assoc()['count'];
$pendingDeliveries = $conn->query("SELECT COUNT(*) AS count FROM deliveries WHERE delivery_person_id = $delivery_id AND status = 'Pending'")->fetch_assoc()['count'];
$completedDeliveries = $conn->query("SELECT COUNT(*) AS count FROM deliveries WHERE delivery_person_id = $delivery_id AND status = 'Completed'")->fetch_assoc()['count'];
$cancelledDeliveries = $conn->query("SELECT COUNT(*) AS count FROM deliveries WHERE delivery_person_id = $delivery_id AND status = 'Cancelled'")->fetch_assoc()['count'];

// Fetch recent deliveries
$recentDeliveries = $conn->query("SELECT d.*, o.created_at AS order_date, a.street, a.city, a.state, a.zip_code, a.country 
                                  FROM deliveries d 
                                  JOIN orders o ON d.order_id = o.order_id 
                                  JOIN addresses a ON o.address_id = a.address_id 
                                  WHERE d.delivery_person_id = $delivery_id 
                                  ORDER BY d.created_at DESC LIMIT 5");

// Fetch data for deliveries over time
$deliveryDates = [];
$deliveryCounts = [];
$deliveriesOverTimeQuery = $conn->query("
    SELECT DATE(created_at) as delivery_date, COUNT(*) as delivery_count
    FROM deliveries 
    WHERE delivery_person_id = $delivery_id
    GROUP BY DATE(created_at)
    ORDER BY delivery_date
");



while ($row = $deliveriesOverTimeQuery->fetch_assoc()) {
    $deliveryDates[] = $row['delivery_date'];
    $deliveryCounts[] = $row['delivery_count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Dashboard</title>
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
            position: fixed;
            height: 100%;
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
            margin-left: 250px;
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
        .table-responsive {
            margin-top: 20px;
        }
        .chart-container {
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-user-shield"></i> Delivery Dashboard</h3>
        </div>
        <ul class="list-unstyled components">
            <li><a href="delivery_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="delivery_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li><a href="delivery_deliveries.php"><i class="fas fa-truck"></i> Deliveries</a></li>
            <li><a href="delivery_profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="delivery_notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
            <!-- <li><a href="delivery_settings.php"><i class="fas fa-cogs"></i> Settings</a></li> -->
            <li><a href="delivery_support.php"><i class="fas fa-headset"></i> Support</a></li>
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
            <div class="welcome-message">
                <h2>Welcome, <?php echo $delivery_name; ?>!</h2>
            </div>

            <!-- Dashboard Overview Cards -->
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card text-white bg-primary">
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
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <div class="text">
                                <div class="card-title">Total Deliveries</div>
                                <h2><?php echo $totalDeliveries; ?></h2>
                            </div>
                            <i class="fas fa-truck"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <div class="text">
                                <div class="card-title">Pending Deliveries</div>
                                <h2><?php echo $pendingDeliveries; ?></h2>
                            </div>
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <div class="text">
                                <div class="card-title">Completed Deliveries</div>
                                <h2><?php echo $completedDeliveries; ?></h2>
                            </div>
                            <i class="fas fa-check"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card text-white bg-danger">
                        <div class="card-body">
                            <div class="text">
                                <div class="card-title">Cancelled Deliveries</div>
                                <h2><?php echo $cancelledDeliveries; ?></h2>
                            </div>
                            <i class="fas fa-times"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Deliveries Section -->
            <div class="row">
                <div class="col-lg-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-history"></i> Recent Deliveries
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Delivery ID</th>
                                            <th>Order Date</th>
                                            <th>Address</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($recentDeliveries->num_rows > 0): ?>
                                            <?php while ($delivery = $recentDeliveries->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo $delivery['delivery_id']; ?></td>
                                                    <td><?php echo $delivery['order_date']; ?></td>
                                                    <td><?php echo $delivery['street'] . ', ' . $delivery['city'] . ', ' . $delivery['state'] . ', ' . $delivery['zip_code'] . ', ' . $delivery['country']; ?></td>
                                                    <td><?php echo $delivery['status']; ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No recent deliveries found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delivery Performance Metrics -->
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-line"></i> Delivery Success Rate
                        </div>
                        <div class="card-body">
                            <!-- Calculate and display the success rate -->
                            <h2>
                                <?php
                                $successRate = $totalDeliveries > 0 ? ($completedDeliveries / $totalDeliveries) * 100 : 0;
                                echo number_format($successRate, 2) . '%';
                                ?>
                            </h2>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-clock"></i> Average Delivery Time
                        </div>
                        <div class="card-body">
                            <!-- Display average delivery time -->
                            <h2>
                                <?php
                                $avgTimeQuery = $conn->query("SELECT AVG(TIMESTAMPDIFF(MINUTE, d.scheduled_at, d.delivered_at)) AS avg_time FROM deliveries d WHERE d.delivery_person_id = $delivery_id AND d.status = 'Completed'");
                                $avgTime = $avgTimeQuery->fetch_assoc()['avg_time'];
                                echo number_format($avgTime, 2) . ' minutes';
                                ?>
                            </h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delivery Charts Section -->
            <div class="row chart-container">
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-bar"></i> Delivery Status Chart
                        </div>
                        <div class="card-body">
                            <canvas id="deliveryStatusChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-pie"></i> Deliveries Over Time
                        </div>
                        <div class="card-body">
                            <canvas id="deliveriesOverTimeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notifications Section -->
            <div class="row">
                <div class="col-lg-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-bell"></i> Notifications
                        </div>
                        <div class="card-body">
                            <!-- Display notifications -->
                            <p>No new notifications</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Section -->
            <div class="row">
                <div class="col-lg-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-bolt"></i> Quick Actions
                        </div>
                        <div class="card-body">
                            <!-- Add quick action buttons -->
                            <button class="btn btn-primary">Update Status</button>
                            <button class="btn btn-secondary">View Details</button>
                            <button class="btn btn-danger">Cancel Delivery</button>
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
$(document).ready(function() {
    $('#sidebarCollapse').on('click', function () {
        $('#sidebar').toggleClass('active');
        if ($('#sidebar').hasClass('active')) {
            $('#content').css('margin-left', '0');
        } else {
            $('#content').css('margin-left', '250px');
        }
    });

    // Delivery Status Chart
    const deliveryStatusChart = new Chart(document.getElementById('deliveryStatusChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Completed', 'Cancelled'],
            datasets: [{
                data: [<?php echo $pendingDeliveries; ?>, <?php echo $completedDeliveries; ?>, <?php echo $cancelledDeliveries; ?>],
                backgroundColor: ['#ffc107', '#28a745', '#dc3545']
            }]
        }
    });

    // Deliveries Over Time Chart
    const deliveriesOverTimeChart = new Chart(document.getElementById('deliveriesOverTimeChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($deliveryDates); ?>, // Generate labels dynamically based on the dates of deliveries
            datasets: [{
                label: 'Deliveries Over Time',
                data: <?php echo json_encode($deliveryCounts); ?>, // Generate data dynamically based on the number of deliveries over time
                backgroundColor: 'rgba(0, 123, 255, 0.5)',
                borderColor: 'rgba(0, 123, 255, 1)',
                fill: true
            }]
        }
    });
});
</script>
</body>
</html>
