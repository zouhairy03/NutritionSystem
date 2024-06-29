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

// Handle search and filter
$search_query = isset($_POST['search_query']) ? $_POST['search_query'] : '';
$status_filter = isset($_POST['status_filter']) ? $_POST['status_filter'] : '';
$user_filter = isset($_POST['user_filter']) ? $_POST['user_filter'] : '';

// Fetch support tickets based on search and filter
$supportTicketsQuery = $conn->query("SELECT st.*, u.name as user_name FROM support_tickets st JOIN users u ON st.user_id = u.user_id WHERE (st.subject LIKE '%$search_query%' OR st.message LIKE '%$search_query%' OR u.name LIKE '%$search_query%') AND (st.status LIKE '%$status_filter%') AND (st.user_id LIKE '%$user_filter%') ORDER BY created_at DESC");
$supportTickets = [];
while ($row = $supportTicketsQuery->fetch_assoc()) {
    $supportTickets[] = $row;
}

// Fetch all users
$usersQuery = $conn->query("SELECT user_id, name FROM users ORDER BY name");
$users = [];
while ($row = $usersQuery->fetch_assoc()) {
    $users[] = $row;
}

// Handle ticket status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ticket_id']) && isset($_POST['status'])) {
    $ticket_id = $conn->real_escape_string($_POST['ticket_id']);
    $status = $conn->real_escape_string($_POST['status']);

    $updateQuery = "UPDATE support_tickets SET status='$status' WHERE ticket_id=$ticket_id";
    if ($conn->query($updateQuery) === TRUE) {
        $successMessage = "Ticket status updated successfully.";
    } else {
        $errorMessage = "Error updating ticket status: " . $conn->error;
    }
    // Refresh support tickets
    $supportTicketsQuery = $conn->query("SELECT st.*, u.name as user_name FROM support_tickets st JOIN users u ON st.user_id = u.user_id ORDER BY created_at DESC");
    $supportTickets = [];
    while ($row = $supportTicketsQuery->fetch_assoc()) {
        $supportTickets[] = $row;
    }
}

// Handle new ticket creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_ticket'])) {
    $user_id = $conn->real_escape_string($_POST['user_id']);
    $subject = $conn->real_escape_string($_POST['subject']);
    $message = $conn->real_escape_string($_POST['message']);
    $status = $conn->real_escape_string($_POST['status']);

    $insertQuery = "INSERT INTO support_tickets (user_id, subject, message, status) VALUES ('$user_id', '$subject', '$message', '$status')";
    if ($conn->query($insertQuery) === TRUE) {
        $successMessage = "New ticket created successfully.";
    } else {
        $errorMessage = "Error creating new ticket: " . $conn->error;
    }
    // Refresh support tickets
    $supportTicketsQuery = $conn->query("SELECT st.*, u.name as user_name FROM support_tickets st JOIN users u ON st.user_id = u.user_id ORDER BY created_at DESC");
    $supportTickets = [];
    while ($row = $supportTicketsQuery->fetch_assoc()) {
        $supportTickets[] = $row;
    }
}

// Handle ticket deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_ticket'])) {
    $ticket_id = $conn->real_escape_string($_POST['ticket_id']);

    $deleteQuery = "DELETE FROM support_tickets WHERE ticket_id=$ticket_id";
    if ($conn->query($deleteQuery) === TRUE) {
        $successMessage = "Ticket deleted successfully.";
    } else {
        $errorMessage = "Error deleting ticket: " . $conn->error;
    }
    // Refresh support tickets
    $supportTicketsQuery = $conn->query("SELECT st.*, u.name as user_name FROM support_tickets st JOIN users u ON st.user_id = u.user_id ORDER BY created_at DESC");
    $supportTickets = [];
    while ($row = $supportTicketsQuery->fetch_assoc()) {
        $supportTickets[] = $row;
    }
}

// Handle export to Excel
if (isset($_POST['export_excel'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename=support_tickets.xls');
    
    $output = fopen('php://output', 'w');
    fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));
    fputcsv($output, array('Ticket ID', 'User Name', 'Subject', 'Message', 'Status', 'Created At'));

    $result = $conn->query("SELECT st.*, u.name as user_name FROM support_tickets st JOIN users u ON st.user_id = u.user_id");
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Tickets</title>
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
            <?php if(isset($successMessage)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $successMessage; ?>
                </div>
            <?php endif; ?>
            <?php if(isset($errorMessage)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $errorMessage; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-ticket-alt"></i> Support Tickets
                </div>
                <div class="card-body">
                    <form method="POST" action="support_tickets.php" class="form-inline mb-4">
                        <input type="text" class="form-control mr-2" name="search_query" placeholder="Search" value="<?php echo $search_query; ?>">
                        <select class="form-control mr-2" name="status_filter">
                            <option value="">All Statuses</option>
                            <option value="Open" <?php if ($status_filter == 'Open') echo 'selected'; ?>>Open</option>
                            <option value="In Progress" <?php if ($status_filter == 'In Progress') echo 'selected'; ?>>In Progress</option>
                            <option value="Closed" <?php if ($status_filter == 'Closed') echo 'selected'; ?>>Closed</option>
                        </select>
                        <select class="form-control mr-2" name="user_filter">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['user_id']; ?>" <?php if ($user_filter == $user['user_id']) echo 'selected'; ?>><?php echo $user['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary mr-2"><i class="fas fa-search"></i> Search</button>
                        <button type="submit" class="btn btn-secondary" name="export_excel"><i class="fas fa-file-excel"></i> Export to Excel</button>
                        <button type="button" class="btn btn-success ml-auto" data-toggle="modal" data-target="#newTicketModal">
                    <i class="fas fa-plus"></i> Add New Ticket
                </button>
                    </form>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col">Ticket ID</th>
                                <th scope="col">User Name</th>
                                <th scope="col">Subject</th>
                                <th scope="col">Message</th>
                                <th scope="col">Status</th>
                                <th scope="col">Created At</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($supportTickets as $ticket): ?>
                                <tr>
                                    <td><?php echo $ticket['ticket_id']; ?></td>
                                    <td><?php echo $ticket['user_name']; ?></td>
                                    <td><?php echo $ticket['subject']; ?></td>
                                    <td><?php echo $ticket['message']; ?></td>
                                    <td><?php echo $ticket['status']; ?></td>
                                    <td><?php echo $ticket['created_at']; ?></td>
                                    <td>
                                        <form method="POST" action="support_tickets.php" class="d-inline">
                                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['ticket_id']; ?>">
                                            <select name="status" class="form-control form-control-sm d-inline" style="width: auto;">
                                                <option value="Open" <?php if($ticket['status'] == 'Open') echo 'selected'; ?>>Open</option>
                                                <option value="In Progress" <?php if($ticket['status'] == 'In Progress') echo 'selected'; ?>>In Progress</option>
                                                <option value="Closed" <?php if($ticket['status'] == 'Closed') echo 'selected'; ?>>Closed</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                        </form>
                                        <form method="POST" action="support_tickets.php" class="d-inline">
                                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['ticket_id']; ?>">
                                            <input type="hidden" name="delete_ticket" value="1">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($supportTickets)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No support tickets found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- New Ticket Modal -->
        <div class="modal fade" id="newTicketModal" tabindex="-1" role="dialog" aria-labelledby="newTicketModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="newTicketModalLabel">Add New Ticket</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="support_tickets.php">
                            <input type="hidden" name="new_ticket" value="1">
                            <div class="form-group">
                                <label for="user_id">User Name</label>
                                <select class="form-control" id="user_id" name="user_id" required>
                                    <option value="" disabled selected>Select User</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['user_id']; ?>"><?php echo $user['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="subject">Subject</label>
                                <input type="text" class="form-control" id="subject" name="subject" required>
                            </div>
                            <div class="form-group">
                                <label for="message">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="Open">Open</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Closed">Closed</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Ticket</button>
                        </form>
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
    });
</script>
</body>
</html>
