<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: delivery_login.php");
    exit();
}
require 'config/db.php';

$delivery_id = $_SESSION['id'];

// Fetch notifications for the delivery personnel
$notificationsQuery = $conn->prepare("SELECT * FROM delivery_notifications WHERE delivery_person_id = ? ORDER BY created_at DESC");
$notificationsQuery->bind_param('i', $delivery_id);
$notificationsQuery->execute();
$notifications = $notificationsQuery->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Notifications</title>
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
            <h3><i class="fas fa-user-shield"></i> Delivery Dashboard</h3>
        </div>
        <ul class="list-unstyled components">
            <li><a href="delivery_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="delivery_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li><a href="delivery_deliveries.php"><i class="fas fa-truck"></i> Deliveries</a></li>
            <li><a href="delivery_profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li class="active"><a href="delivery_notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
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
                    <img src="Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png" style="margin-right: 230px; height: 350px; width: 60%;" alt="NutriDaily Logo" class="logo">
                </div>
            </div>
        </nav>

        <div class="container">
            <h2>Delivery Notifications</h2>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Message</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($notifications->num_rows > 0): ?>
                            <?php while ($notification = $notifications->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $notification['id']; ?></td>
                                    <td><?php echo htmlspecialchars($notification['title']); ?></td>
                                    <td><?php echo htmlspecialchars($notification['message']); ?></td>
                                    <td><?php echo $notification['created_at']; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info read-btn" data-title="<?php echo htmlspecialchars($notification['title']); ?>" data-message="<?php echo htmlspecialchars($notification['message']); ?>" data-created_at="<?php echo $notification['created_at']; ?>"><i class="fas fa-eye"></i> Read</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No notifications found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
