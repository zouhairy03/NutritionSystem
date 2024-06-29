<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: delivery_login.php");
    exit();
}
require 'config/db.php';

$delivery_id = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    $insertQuery = $conn->prepare("INSERT INTO delivery_support_tickets (delivery_person_id, subject, message, created_at) VALUES (?, ?, ?, NOW())");
    $insertQuery->bind_param('iss', $delivery_id, $subject, $message);

    if ($insertQuery->execute()) {
        $_SESSION['message'] = "Support ticket submitted successfully.";
    } else {
        $_SESSION['error'] = "Failed to submit support ticket.";
    }
}

$supportTicketsQuery = $conn->prepare("SELECT id, subject, message, created_at, status FROM delivery_support_tickets WHERE delivery_person_id = ? ORDER BY created_at DESC");
$supportTicketsQuery->bind_param('i', $delivery_id);
$supportTicketsQuery->execute();
$supportTickets = $supportTicketsQuery->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Support</title>
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
            <li><a href="delivery_notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
            <!-- <li><a href="delivery_settings.php"><i class="fas fa-cogs"></i> Settings</a></li> -->
            <li class="active"><a href="delivery_support.php"><i class="fas fa-headset"></i> Support</a></li>
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
                    <img src="Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png" style="margin-right: 340px; height: 250px; width: 60%;" alt="NutriDaily Logo" class="logo">
                </div>
            </div>
        </nav>

        <div class="container">
            <h2>Support</h2>
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
            <form action="delivery_support.php" method="POST">
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" class="form-control" id="subject" name="subject" required>
                </div>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Submit Support Ticket</button>
            </form>

            <h2 class="mt-5">Your Support Tickets</h2>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Created At</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($supportTickets->num_rows > 0): ?>
                            <?php while ($ticket = $supportTickets->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $ticket['id']; ?></td>
                                    <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                    <td><?php echo htmlspecialchars($ticket['message']); ?></td>
                                    <td><?php echo $ticket['created_at']; ?></td>
                                    <td><?php echo htmlspecialchars($ticket['status']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No support tickets found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
});
</script>
</body>
</html>
