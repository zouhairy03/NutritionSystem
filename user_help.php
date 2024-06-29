<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}
require 'config/db.php';

// Fetch user details
$user_id = $_SESSION['user_id'];

// Handle form submission for creating a support ticket
if (isset($_POST['submit_ticket'])) {
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    $insert_query = $conn->prepare("INSERT INTO support_tickets (user_id, subject, message, status, created_at) VALUES (?, ?, ?, 'open', NOW())");
    $insert_query->bind_param('iss', $user_id, $subject, $message);
    if ($insert_query->execute()) {
        $success_message = "Support ticket submitted successfully!";
    } else {
        $error_message = "Failed to submit support ticket.";
    }
}

// Fetch user support tickets
$tickets_query = $conn->prepare("SELECT * FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC");
$tickets_query->bind_param('i', $user_id);
$tickets_query->execute();
$tickets_result = $tickets_query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Help & Support</title>
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
        .container {
            margin-top: 50px;
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
            padding: 30px;
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
                    <img src="Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png" style="margin-right: 460px; height: 250px; width: 60%;" alt="NutriDaily Logo" class="logo">
                </div>
            </div>
        </nav>

        <div class="container">
            <h2>Help & Support</h2>
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header"><i class="fas fa-life-ring"></i> Submit a Support Ticket</div>
                <div class="card-body">
                    <form method="POST" action="user_help.php">
                        <div class="form-group">
                            <label for="subject">Subject:</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Message:</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" name="submit_ticket">Submit Ticket</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><i class="fas fa-history"></i> My Support Tickets</div>
                <div class="card-body">
                    <?php if ($tickets_result->num_rows > 0): ?>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Ticket ID</th>
                                    <th>Subject</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($ticket = $tickets_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($ticket['ticket_id']); ?></td>
                                        <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                        <td><?php echo htmlspecialchars($ticket['message']); ?></td>
                                        <td><?php echo htmlspecialchars($ticket['status']); ?></td>
                                        <td><?php echo htmlspecialchars($ticket['created_at']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#viewTicketModal<?php echo $ticket['ticket_id']; ?>"><i class="fas fa-eye"></i> View</button>
                                        </td>
                                    </tr>

                                    <!-- View Ticket Modal -->
                                    <div class="modal fade" id="viewTicketModal<?php echo $ticket['ticket_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="viewTicketModalLabel<?php echo $ticket['ticket_id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="viewTicketModalLabel<?php echo $ticket['ticket_id']; ?>">View Ticket</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <p><strong>Ticket ID:</strong> <?php echo htmlspecialchars($ticket['ticket_id']); ?></p>
                                                    <p><strong>Subject:</strong> <?php echo htmlspecialchars($ticket['subject']); ?></p>
                                                    <p><strong>Message:</strong> <?php echo htmlspecialchars($ticket['message']); ?></p>
                                                    <p><strong>Status:</strong> <?php echo htmlspecialchars($ticket['status']); ?></p>
                                                    <p><strong>Created At:</strong> <?php echo htmlspecialchars($ticket['created_at']); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No support tickets found.</p>
                    <?php endif; ?>
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
