<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
require 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_notification'])) {
        $title = $_POST['title'];
        $message = $_POST['message'];
        $delivery_person_id = $_POST['delivery_person_id'];

        $insertQuery = $conn->prepare("INSERT INTO delivery_notifications (delivery_person_id, title, message, created_at) VALUES (?, ?, ?, NOW())");
        $insertQuery->bind_param('iss', $delivery_person_id, $title, $message);

        if ($insertQuery->execute()) {
            $_SESSION['message'] = "Notification sent successfully.";
        } else {
            $_SESSION['error'] = "Failed to send notification.";
        }
    } elseif (isset($_POST['edit_notification'])) {
        $id = $_POST['id'];
        $title = $_POST['title'];
        $message = $_POST['message'];

        $updateQuery = $conn->prepare("UPDATE delivery_notifications SET title = ?, message = ? WHERE id = ?");
        $updateQuery->bind_param('ssi', $title, $message, $id);

        if ($updateQuery->execute()) {
            $_SESSION['message'] = "Notification updated successfully.";
        } else {
            $_SESSION['error'] = "Failed to update notification.";
        }
    }
}

if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    $deleteQuery = $conn->prepare("DELETE FROM delivery_notifications WHERE id = ?");
    $deleteQuery->bind_param('i', $id);

    if ($deleteQuery->execute()) {
        $_SESSION['message'] = "Notification deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete notification.";
    }

    header("Location: send_delivery_notifications.php");
    exit();
}

$deliveryPersonsQuery = "SELECT id, name FROM delivery_personnel";
$deliveryPersons = $conn->query($deliveryPersonsQuery);

$notificationsQuery = "SELECT dn.id, dp.name AS delivery_person_name, dn.title, dn.message, dn.created_at 
                       FROM delivery_notifications dn 
                       JOIN delivery_personnel dp ON dn.delivery_person_id = dp.id 
                       ORDER BY dn.created_at DESC";
$notifications = $conn->query($notificationsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Delivery Notifications</title>
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
            transition: all 0.3s;
            margin-left: 250px;
        }
        #sidebarCollapse {
            background: #809B53;
            border: none;
            color: #fff;
            padding: 10px;
            cursor: pointer;
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
            <li class="active"><a href="send_delivery_notifications.php"><i class="fas fa-bell"></i> Delivery Notifications</a></li>
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
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-info">
                    <i class="fas fa-align-left"></i>
                    <span></span>
                </button>
                <div class="ml-auto">
                    <img src="Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png" style="margin-right: 230px; height: 250px; width: 60%;" alt="NutriDaily Logo" class="logo">
                </div>
            </div>
        </nav>

        <div class="container">
            <h2>Send Delivery Notifications</h2>
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success">
                    <?php
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>
            <form action="send_delivery_notifications.php" method="POST">
                <div class="form-group">
                    <label for="delivery_person_id">Delivery Personnel</label>
                    <select class="form-control" id="delivery_person_id" name="delivery_person_id" required>
                        <?php while ($person = $deliveryPersons->fetch_assoc()): ?>
                            <option value="<?php echo $person['id']; ?>"><?php echo htmlspecialchars($person['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary" name="add_notification">Send Notification</button>
            </form>

            <h2 class="mt-5">Manage Delivery Notifications</h2>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Delivery Personnel</th>
                            <th>Title</th>
                            <th>Message</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($notification = $notifications->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $notification['id']; ?></td>
                                <td><?php echo htmlspecialchars($notification['delivery_person_name']); ?></td>
                                <td><?php echo htmlspecialchars($notification['title']); ?></td>
                                <td><?php echo htmlspecialchars($notification['message']); ?></td>
                                <td><?php echo $notification['created_at']; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning edit-btn" data-id="<?php echo $notification['id']; ?>" data-title="<?php echo htmlspecialchars($notification['title']); ?>" data-message="<?php echo htmlspecialchars($notification['message']); ?>"><i class="fas fa-edit"></i> Edit</button>
                                    <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $notification['id']; ?>"><i class="fas fa-trash"></i> Delete</button>
                                    <button class="btn btn-sm btn-info read-btn" data-title="<?php echo htmlspecialchars($notification['title']); ?>" data-message="<?php echo htmlspecialchars($notification['message']); ?>" data-created_at="<?php echo $notification['created_at']; ?>"><i class="fas fa-eye"></i> Read</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<!-- Edit Notification Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="editForm" method="POST" action="send_delivery_notifications.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Notification</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="form-group">
                        <label for="edit_title">Title</label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_message">Message</label>
                        <textarea class="form-control" id="edit_message" name="message" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" name="edit_notification">Update Notification</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Notification Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Delete Notification</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this notification?
            </div>
            <div class="modal-footer">
                <form id="deleteForm" method="GET" action="send_delivery_notifications.php">
                    <input type="hidden" name="delete_id" id="delete_id">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Read Notification Modal -->
<div class="modal fade" id="readModal" tabindex="-1" role="dialog" aria-labelledby="readModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="readModalLabel">Notification Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h5 id="read_title"></h5>
                <p id="read_message"></p>
                <p><small id="read_created_at"></small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
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

    $('.edit-btn').on('click', function() {
        const id = $(this).data('id');
        const title = $(this).data('title');
        const message = $(this).data('message');
        $('#edit_id').val(id);
        $('#edit_title').val(title);
        $('#edit_message').val(message);
        $('#editModal').modal('show');
    });

    $('.delete-btn').on('click', function() {
        const id = $(this).data('id');
        $('#delete_id').val(id);
        $('#deleteModal').modal('show');
    });

    $('.read-btn').on('click', function() {
        const title = $(this).data('title');
        const message = $(this).data('message');
        const created_at = $(this).data('created_at');
        $('#read_title').text(title);
        $('#read_message').text(message);
        $('#read_created_at').text(created_at);
        $('#readModal').modal('show');
    });
});
</script>
</body>
</html>
