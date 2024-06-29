<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
require 'config/db.php';

$searchTerm = '';
if (isset($_GET['search'])) {
    $searchTerm = $_GET['search'];
    $supportTicketsQuery = $conn->prepare("SELECT dst.id, dp.name AS delivery_person_name, dst.subject, dst.message, dst.created_at, dst.status 
                                           FROM delivery_support_tickets dst 
                                           JOIN delivery_personnel dp ON dst.delivery_person_id = dp.id 
                                           WHERE dp.name LIKE ? OR dst.subject LIKE ? OR dst.message LIKE ?
                                           ORDER BY dst.created_at DESC");
    $searchTermWild = "%$searchTerm%";
    $supportTicketsQuery->bind_param('sss', $searchTermWild, $searchTermWild, $searchTermWild);
} else {
    $supportTicketsQuery = $conn->prepare("SELECT dst.id, dp.name AS delivery_person_name, dst.subject, dst.message, dst.created_at, dst.status 
                                           FROM delivery_support_tickets dst 
                                           JOIN delivery_personnel dp ON dst.delivery_person_id = dp.id 
                                           ORDER BY dst.created_at DESC");
}
$supportTicketsQuery->execute();
$supportTickets = $supportTicketsQuery->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Support Tickets</title>
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
            <li><a href="send_delivery_notifications.php"><i class="fas fa-bell"></i> Delivery Notifications</a></li>
            <li class="active"><a href="delivery_support_tickets.php"><i class="fas fa-ticket-alt"></i> Delivery Support Tickets</a></li> <!-- Active for delivery support tickets -->
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
            <h2>Delivery Support Tickets</h2>
            <form class="form-inline mb-3" method="GET" action="delivery_support_tickets.php">
                <input class="form-control mr-sm-2" type="search" name="search" placeholder="Search" aria-label="Search" value="<?php echo htmlspecialchars($searchTerm); ?>">
                <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button>
                <button type="button" class="btn btn-primary ml-2" data-toggle="modal" data-target="#createTicketModal"><i class="fas fa-plus"></i> Create Ticket</button>
            </form>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Delivery Person</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Created At</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($supportTickets->num_rows > 0): ?>
                            <?php while ($ticket = $supportTickets->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $ticket['id']; ?></td>
                                    <td><?php echo htmlspecialchars($ticket['delivery_person_name']); ?></td>
                                    <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                    <td><?php echo htmlspecialchars($ticket['message']); ?></td>
                                    <td><?php echo $ticket['created_at']; ?></td>
                                    <td><?php echo htmlspecialchars($ticket['status']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info read-btn" data-subject="<?php echo htmlspecialchars($ticket['subject']); ?>" data-message="<?php echo htmlspecialchars($ticket['message']); ?>" data-created_at="<?php echo $ticket['created_at']; ?>" data-status="<?php echo htmlspecialchars($ticket['status']); ?>"><i class="fas fa-eye"></i> View</button>
                                        <button class="btn btn-sm btn-success status-btn" data-id="<?php echo $ticket['id']; ?>" data-status="Closed"><i class="fas fa-check"></i> Close</button>
                                        <button class="btn btn-sm btn-warning status-btn" data-id="<?php echo $ticket['id']; ?>" data-status="Open"><i class="fas fa-redo"></i> Reopen</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No support tickets found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- View Support Ticket Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel">Support Ticket Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h5 id="view_subject"></h5>
                <p id="view_message"></p>
                <p><small id="view_created_at"></small></p>
                <p><strong>Status:</strong> <span id="view_status"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Create Support Ticket Modal -->
<div class="modal fade" id="createTicketModal" tabindex="-1" role="dialog" aria-labelledby="createTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="create_ticket.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="createTicketModalLabel">Create Support Ticket</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="delivery_person_id">Delivery Person</label>
                        <select class="form-control" id="delivery_person_id" name="delivery_person_id" required>
                            <?php
                            $deliveryPersonsQuery = $conn->query("SELECT id, name FROM delivery_personnel");
                            while ($person = $deliveryPersonsQuery->fetch_assoc()): ?>
                                <option value="<?php echo $person['id']; ?>"><?php echo htmlspecialchars($person['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create Ticket</button>
                </div>
            </form>
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
        const subject = $(this).data('subject');
        const message = $(this).data('message');
        const created_at = $(this).data('created_at');
        const status = $(this).data('status');
        $('#view_subject').text(subject);
        $('#view_message').text(message);
        $('#view_created_at').text(created_at);
        $('#view_status').text(status);
        $('#viewModal').modal('show');
    });

    $('.status-btn').on('click', function() {
        const id = $(this).data('id');
        const status = $(this).data('status');
        $.ajax({
            url: 'update_ticket_status.php',
            type: 'POST',
            data: { id: id, status: status },
            success: function(response) {
                location.reload();
            }
        });
    });
});
</script>
</body>
</html>
