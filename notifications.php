<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require 'config/db.php';

// Fetch users for the dropdown
$usersResult = $conn->query("SELECT user_id, name FROM users");

// Handle Add Notification
if (isset($_POST['add_notification'])) {
    $title = $_POST['title'];
    $message = $_POST['message'];
    $user_ids = $_POST['user_ids']; // Array of selected user IDs

    $sql = "INSERT INTO notifications (title, message, status, created_at, updated_at) VALUES (?, ?, 'unread', NOW(), NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $title, $message);
    $stmt->execute();
    $notification_id = $stmt->insert_id; // Get the last inserted ID

    foreach ($user_ids as $user_id) {
        $stmt = $conn->prepare("INSERT INTO user_notifications (user_id, notification_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $notification_id);
        $stmt->execute();
    }

    // Simulate sending email notifications
    foreach ($user_ids as $user_id) {
        $stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $email = $user['email'];
        // Simulate sending email
        mail($email, $title, $message);
    }

    header("Location: notifications.php");
    exit();
}

// Handle Edit Notification
if (isset($_POST['edit_notification'])) {
    $notification_id = $_POST['notification_id'];
    $title = $_POST['title'];
    $message = $_POST['message'];

    $sql = "UPDATE notifications SET title = ?, message = ?, updated_at = NOW() WHERE notification_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $title, $message, $notification_id);
    $stmt->execute();
    header("Location: notifications.php");
    exit();
}

// Handle Delete Notification
$delete_error = "";
if (isset($_GET['delete_notification'])) {
    $notification_id = $_GET['delete_notification'];

    // Check for related user notifications
    $related_user_notifications_query = "SELECT COUNT(*) AS count FROM user_notifications WHERE notification_id = ?";
    $stmt = $conn->prepare($related_user_notifications_query);
    $stmt->bind_param("i", $notification_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $related_user_notifications_count = $result->fetch_assoc()['count'];

    if ($related_user_notifications_count > 0) {
        $delete_error = "Cannot delete notification. There are users associated with this notification.";
    } else {
        $stmt = $conn->prepare("DELETE FROM user_notifications WHERE notification_id = ?");
        $stmt->bind_param("i", $notification_id);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM notifications WHERE notification_id = ?");
        $stmt->bind_param("i", $notification_id);
        $stmt->execute();
        header("Location: notifications.php");
        exit();
    }
}

// Handle Mark as Read
if (isset($_GET['mark_as_read'])) {
    $notification_id = $_GET['mark_as_read'];
    $stmt = $conn->prepare("UPDATE notifications SET status = 'read' WHERE notification_id = ?");
    $stmt->bind_param("i", $notification_id);
    $stmt->execute();
    header("Location: notifications.php");
    exit();
}

// Handle Mark as Unread
if (isset($_GET['mark_as_unread'])) {
    $notification_id = $_GET['mark_as_unread'];
    $stmt = $conn->prepare("UPDATE notifications SET status = 'unread' WHERE notification_id = ?");
    $stmt->bind_param("i", $notification_id);
    $stmt->execute();
    header("Location: notifications.php");
    exit();
}

// Handle Search and Filter
$search_query = isset($_POST['search_query']) ? $_POST['search_query'] : "";
$status_filter = isset($_POST['status_filter']) ? $_POST['status_filter'] : "";

// Handle Pagination
$limit = 10; // Number of entries per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Fetch notifications data with pagination and status filter
$search_sql = $search_query ? "AND (title LIKE ? OR message LIKE ?)" : "";
$status_sql = $status_filter ? "AND status = ?" : "";
$sql = "SELECT * FROM notifications WHERE 1=1 $search_sql $status_sql LIMIT ?, ?";
$stmt = $conn->prepare($sql);

if ($search_query && $status_filter) {
    $search_param = "%{$search_query}%";
    $stmt->bind_param("ssssi", $search_param, $search_param, $status_filter, $start, $limit);
} elseif ($search_query) {
    $search_param = "%{$search_query}%";
    $stmt->bind_param("ssii", $search_param, $search_param, $start, $limit);
} elseif ($status_filter) {
    $stmt->bind_param("sii", $status_filter, $start, $limit);
} else {
    $stmt->bind_param("ii", $start, $limit);
}

$stmt->execute();
$result = $stmt->get_result();

// Fetch total notifications count for pagination
$sql_count = "SELECT COUNT(*) AS total FROM notifications WHERE 1=1 $search_sql $status_sql";
$stmt = $conn->prepare($sql_count);

if ($search_query && $status_filter) {
    $stmt->bind_param("sss", $search_param, $search_param, $status_filter);
} elseif ($search_query) {
    $stmt->bind_param("ss", $search_param, $search_param);
} elseif ($status_filter) {
    $stmt->bind_param("s", $status_filter);
}

$stmt->execute();
$count_result = $stmt->get_result();
$total_notifications = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_notifications / $limit);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Notifications</title>
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
            background: #809B53; /* Green color */
            color: #fff;
            transition: all 0.3s;
        }
        #sidebar.active {
            margin-left: -250px;
        }
        #sidebar .sidebar-header {
            padding: 20px;
            background: #809B53; /* Green color */
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
            color: #3E8E41; /* Green color */
            background: #fff;
        }
        #content {
            width: 100%;
            padding: 20px;
            min-height: 100vh;
        }
        .card {
            margin-bottom: 20px;
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .card i {
            font-size: 2em;
        }
        #sidebarCollapse {
            background: #3E8E41; /* Green color */
            border: none;
            color: #fff;
            padding: 10px;
            cursor: pointer;
        }
        .modal .modal-dialog {
            max-width: 800px;
        }
        .table-search {
            margin-bottom: 20px;
        }
        .navbar {
            color: #fff;
        }
        .navbar .navbar-brand {
            color: #fff;
        }
        .navbar .navbar-brand:hover {
            color: #f8f9fa;
        }
        .navbar .logo {
            width: 150px;
            height: auto;
        }
        .navbar .ml-auto {
            margin-left: auto;
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
            <li><a href="activity_logs.php"><i class="fas fa-list"></i> Activity Logs</a></li>
            <li><a href="financial_overview.php"><i class="fas fa-dollar-sign"></i> Financial Overview</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <!-- Page Content -->
    <div id="content">
        <nav class="navbar navbar-expand-lg">
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
        
        <div class="container mt-5">
            <h1><i class="fas fa-bell"></i> Manage Notifications</h1>

            <?php if ($delete_error): ?>
                <div class="alert alert-danger"><?php echo $delete_error; ?></div>
            <?php endif; ?>

            <!-- Search and Filter Form -->
            <div class="table-search mb-4">
                <form action="notifications.php" method="POST" class="form-inline">
                    <div class="form-group mb-2">
                        <input type="text" name="search_query" class="form-control" placeholder="Search" value="<?php echo $_POST['search_query'] ?? ''; ?>">
                    </div>
                    <div class="form-group mx-sm-3 mb-2">
                        <select name="status_filter" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="unread" <?php if ($status_filter == 'unread') echo 'selected'; ?>>Unread</option>
                            <option value="read" <?php if ($status_filter == 'read') echo 'selected'; ?>>Read</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary mb-2" name="search"><i class="fas fa-search"></i> Search</button>
                    <a href="notifications.php?export=true" class="btn btn-success mb-2 ml-2"><i class="fas fa-file-excel"></i> Export to Excel</a>
                </form>
            </div>

            <!-- Add Notification Button -->
            <button class="btn btn-success mb-4" data-toggle="modal" data-target="#addNotificationModal"><i class="fas fa-plus"></i> Add Notification</button>

            <!-- Notifications Table -->
            <h2>Notifications List</h2>
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Notification ID</th>
                        <th>Title</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Updated At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['notification_id']; ?></td>
                            <td><?php echo $row['title']; ?></td>
                            <td><?php echo $row['message']; ?></td>
                            <td><?php echo $row['status']; ?></td>
                            <td><?php echo $row['created_at']; ?></td>
                            <td><?php echo $row['updated_at']; ?></td>
                            <td>
                                <!-- Edit Notification Button -->
                                <button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editNotificationModal<?php echo $row['notification_id']; ?>"><i class="fas fa-edit"></i> Edit</button>

                                <!-- Delete Notification Link -->
                                <a href="notifications.php?delete_notification=<?php echo $row['notification_id']; ?>" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</a>

                                <!-- Mark as Read/Unread Button -->
                                <?php if($row['status'] == 'unread'): ?>
                                <a href="notifications.php?mark_as_read=<?php echo $row['notification_id']; ?>" class="btn btn-sm btn-success"><i class="fas fa-envelope-open"></i> Mark as Read</a>
                                <?php else: ?>
                                <a href="notifications.php?mark_as_unread=<?php echo $row['notification_id']; ?>" class="btn btn-sm btn-secondary"><i class="fas fa-envelope"></i> Mark as Unread</a>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Edit Notification Modal -->
                        <div class="modal fade" id="editNotificationModal<?php echo $row['notification_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editNotificationModalLabel<?php echo $row['notification_id']; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editNotificationModalLabel<?php echo $row['notification_id']; ?>">Edit Notification</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <form action="notifications.php" method="POST">
                                            <input type="hidden" name="notification_id" value="<?php echo $row['notification_id']; ?>">
                                            <div class="form-group">
                                                <label for="title">Title:</label>
                                                <input type="text" class="form-control" id="title" name="title" value="<?php echo $row['title']; ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="message">Message:</label>
                                                <textarea class="form-control" id="message" name="message" rows="3" required><?php echo $row['message']; ?></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary" name="edit_notification"><i class="fas fa-save"></i> Save changes</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- Pagination Controls -->
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    <li class="page-item <?php if($page <= 1) echo 'disabled'; ?>">
                        <a class="page-link" href="<?php if($page <= 1) echo '#'; else echo "?page=" . ($page - 1); ?>">Previous</a>
                    </li>
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php if($page == $i) echo 'active'; ?>">
                        <a class="page-link" href="notifications.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?php if($page >= $total_pages) echo 'disabled'; ?>">
                        <a class="page-link" href="<?php if($page >= $total_pages) echo '#'; else echo "?page=" . ($page + 1); ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Add Notification Modal -->
<div class="modal fade" id="addNotificationModal" tabindex="-1" role="dialog" aria-labelledby="addNotificationModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addNotificationModalLabel">Add Notification</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="notifications.php" method="POST">
                    <div class="form-group">
                        <label for="title">Title:</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="message">Message:</label>
                        <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="user_ids">Select Users:</label>
                        <select multiple class="form-control" id="user_ids" name="user_ids[]" required>
                            <?php while ($user = $usersResult->fetch_assoc()): ?>
                                <option value="<?php echo $user['user_id']; ?>"><?php echo $user['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" name="add_notification"><i class="fas fa-plus"></i> Add Notification</button>
                </form>
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
    });
</script>
</body>
</html>
