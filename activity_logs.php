<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require 'config/db.php';

// Handle form submission to create a new activity log
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $action = $_POST['action'];
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $action);
    if ($stmt->execute()) {
        $message = "Activity log created successfully!";
    } else {
        $message = "Error: " . $stmt->error;
    }
}

// Fetch recent activity logs
$activityLogsQuery = $conn->query("SELECT * FROM activity_logs ORDER BY timestamp DESC");

// Fetch users for the dropdown
$usersQuery = $conn->query("SELECT user_id, name FROM users");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
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
        .container {
            margin-top: 50px;
        }
        .table {
            margin-top: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .btn-new-log {
            margin-bottom: 20px;
        }
        .message {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="wrapper">
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

        <div class="container">
            <h2 class="text-center">Activity Logs</h2>
            <?php if (isset($message)): ?>
                <div class="alert alert-info message"><?php echo $message; ?></div>
            <?php endif; ?>
            <button class="btn btn-primary btn-new-log" data-toggle="modal" data-target="#createActivityLogModal">
                <i class="fas fa-plus"></i> Create New Activity Log
            </button>
            <form class="form-inline mb-3">
                <input type="text" class="form-control mr-2" placeholder="Search by User ID or Action" name="search">
                <input type="text" class="form-control mr-2" id="start_date" placeholder="Start Date">
                <input type="text" class="form-control mr-2" id="end_date" placeholder="End Date">
                <button type="submit" class="btn btn-secondary">Search</button>
            </form>
            <table class="table table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th scope="col">Log ID</th>
                        <th scope="col">User ID</th>
                        <th scope="col">Action</th>
                        <th scope="col">Timestamp</th>
                        <th scope="col">Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($activity = $activityLogsQuery->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $activity['id']; ?></td>
                        <td><?php echo $activity['user_id']; ?></td>
                        <td><?php echo $activity['action']; ?></td>
                        <td><?php echo $activity['timestamp']; ?></td>
                        <td><a href="activity_log_details.php?id=<?php echo $activity['id']; ?>" class="btn btn-info btn-sm">View</a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal for Creating New Activity Log -->
<div class="modal fade" id="createActivityLogModal" tabindex="-1" aria-labelledby="createActivityLogModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="activity_logs.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="createActivityLogModalLabel">Create New Activity Log</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="user_id">User ID</label>
                        <select class="form-control" id="user_id" name="user_id" required>
                            <option value="" disabled selected>Select User</option>
                            <?php while ($user = $usersQuery->fetch_assoc()): ?>
                                <option value="<?php echo $user['user_id']; ?>"><?php echo $user['name']; ?> (ID: <?php echo $user['user_id']; ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="action">Action</label>
                        <input type="text" class="form-control" id="action" name="action" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script>
    $(document).ready(function () {
        $('#sidebarCollapse').on('click', function () {
            $('#sidebar').toggleClass('active');
        });
        
        $('#start_date').datepicker({
            format: 'yyyy-mm-dd'
        });
        $('#end_date').datepicker({
            format: 'yyyy-mm-dd'
        });
    });
</script>
</body>
</html>
