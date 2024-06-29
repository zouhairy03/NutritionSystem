<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}
require 'config/db.php';

// Fetch user details
$user_id = $_SESSION['user_id'];

// Handle Mark as Read
if (isset($_GET['mark_as_read'])) {
    $notification_id = $_GET['mark_as_read'];
    $stmt = $conn->prepare("UPDATE notifications SET status = 'read' WHERE notification_id = ?");
    $stmt->bind_param("i", $notification_id);
    $stmt->execute();
    header("Location: user_notifications.php");
    exit();
}

// Handle Mark as Unread
if (isset($_GET['mark_as_unread'])) {
    $notification_id = $_GET['mark_as_unread'];
    $stmt = $conn->prepare("UPDATE notifications SET status = 'unread' WHERE notification_id = ?");
    $stmt->bind_param("i", $notification_id);
    $stmt->execute();
    header("Location: user_notifications.php");
    exit();
}

// Handle Search
$search_query = isset($_POST['search_query']) ? $_POST['search_query'] : "";

// Fetch user notifications
$sql = "
    SELECT n.notification_id, n.title, n.message, n.status, n.created_at, n.updated_at
    FROM notifications n
    JOIN user_notifications un ON n.notification_id = un.notification_id
    WHERE un.user_id = ? AND (n.title LIKE ? OR n.message LIKE ?)
    ORDER BY n.created_at DESC";
$stmt = $conn->prepare($sql);
$search_param = "%{$search_query}%";
$stmt->bind_param('iss', $user_id, $search_param, $search_param);
$stmt->execute();
$notifications_result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Notifications</title>
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
        .modal-header, .modal-body, .modal-footer {
            padding: 20px;
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
            <div class="welcome-message">
                <h2>Notifications</h2>
            </div>

            <!-- Search Form -->
            <form action="user_notifications.php" method="POST" class="form-inline mb-4">
                <div class="form-group">
                    <input type="text" name="search_query" class="form-control" placeholder="Search" value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <button type="submit" class="btn btn-primary ml-2"><i class="fas fa-search"></i> Search</button>
            </form>

            <!-- Notifications Table -->
            <div class="row">
                <div class="col-lg-12 mb-4">
                    <div class="card data-section">
                        <div class="card-header">
                            <i class="fas fa-bell"></i> Recent Notifications
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Title</th>
                                        <th>Message</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($notification = $notifications_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($notification['title'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($notification['message'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($notification['status'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($notification['created_at'] ?? 'N/A'); ?></td>
                                            <td>
                                                <div class="actions">
                                                    <a href="user_notifications.php?mark_as_read=<?php echo $notification['notification_id']; ?>" class="btn btn-sm btn-success"><i class="fas fa-envelope-open"></i> Mark as Read</a>
                                                    <a href="user_notifications.php?mark_as_unread=<?php echo $notification['notification_id']; ?>" class="btn btn-sm btn-secondary"><i class="fas fa-envelope"></i> Mark as Unread</a>
                                                    <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#viewNotificationModal<?php echo $notification['notification_id']; ?>"><i class="fas fa-eye"></i> View</button>
                                                </div>
                                            </td>
                                        </tr>

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
                                </tbody>
                            </table>
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
                            <a href="user_addresses.php" class="btn btn-secondary">Manage Addresses</a>
                            <a href="user_settings.php" class="btn btn-info">Account Settings</a>
                            <a href="user_help.php" class="btn btn-warning">Help & Support</a>
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
<script>
    $(document).ready(function () {
        $('#sidebarCollapse').on('click', function () {
            $('#sidebar').toggleClass('active');
        });
    });
</script>
</body>
</html>
