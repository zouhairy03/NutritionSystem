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

// Fetch feedback
$feedbackQuery = $conn->query("SELECT f.*, u.name as user_name FROM feedback f JOIN users u ON f.user_id = u.user_id ORDER BY created_at DESC");
$feedbacks = [];
while ($row = $feedbackQuery->fetch_assoc()) {
    $feedbacks[] = $row;
}

// Fetch all users
$usersQuery = $conn->query("SELECT user_id, name FROM users ORDER BY name");
$users = [];
while ($row = $usersQuery->fetch_assoc()) {
    $users[] = $row;
}

// Handle feedback deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_feedback'])) {
    $feedback_id = $conn->real_escape_string($_POST['feedback_id']);

    $deleteQuery = "DELETE FROM feedback WHERE feedback_id=$feedback_id";
    if ($conn->query($deleteQuery) === TRUE) {
        $successMessage = "Feedback deleted successfully.";
    } else {
        $errorMessage = "Error deleting feedback: " . $conn->error;
    }
    // Refresh feedback
    $feedbackQuery = $conn->query("SELECT f.*, u.name as user_name FROM feedback f JOIN users u ON f.user_id = u.user_id ORDER BY created_at DESC");
    $feedbacks = [];
    while ($row = $feedbackQuery->fetch_assoc()) {
        $feedbacks[] = $row;
    }
}

// Handle new feedback creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_feedback'])) {
    $user_id = $conn->real_escape_string($_POST['user_id']);
    $message = $conn->real_escape_string($_POST['message']);

    $insertQuery = "INSERT INTO feedback (user_id, message) VALUES ('$user_id', '$message')";
    if ($conn->query($insertQuery) === TRUE) {
        $successMessage = "New feedback created successfully.";
    } else {
        $errorMessage = "Error creating new feedback: " . $conn->error;
    }
    // Refresh feedback
    $feedbackQuery = $conn->query("SELECT f.*, u.name as user_name FROM feedback f JOIN users u ON f.user_id = u.user_id ORDER BY created_at DESC");
    $feedbacks = [];
    while ($row = $feedbackQuery->fetch_assoc()) {
        $feedbacks[] = $row;
    }
}

// Handle feedback update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_feedback'])) {
    $feedback_id = $conn->real_escape_string($_POST['feedback_id']);
    $user_id = $conn->real_escape_string($_POST['user_id']);
    $message = $conn->real_escape_string($_POST['message']);

    $updateQuery = "UPDATE feedback SET user_id='$user_id', message='$message' WHERE feedback_id=$feedback_id";
    if ($conn->query($updateQuery) === TRUE) {
        $successMessage = "Feedback updated successfully.";
    } else {
        $errorMessage = "Error updating feedback: " . $conn->error;
    }
    // Refresh feedback
    $feedbackQuery = $conn->query("SELECT f.*, u.name as user_name FROM feedback f JOIN users u ON f.user_id = u.user_id ORDER BY created_at DESC");
    $feedbacks = [];
    while ($row = $feedbackQuery->fetch_assoc()) {
        $feedbacks[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Feedback</title>
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
                    <i class="fas fa-comments"></i> User Feedback
                </div>

            
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col">Feedback ID</th>
                                <th scope="col">User Name</th>
                                <th scope="col">Message</th>
                                <th scope="col">Created At</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($feedbacks as $feedback): ?>
                                <tr>
                                    <td><?php echo $feedback['feedback_id']; ?></td>
                                    <td><?php echo $feedback['user_name']; ?></td>
                                    <td><?php echo $feedback['message']; ?></td>
                                    <td><?php echo $feedback['created_at']; ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#updateFeedbackModal" data-feedback-id="<?php echo $feedback['feedback_id']; ?>" data-user-id="<?php echo $feedback['user_id']; ?>" data-message="<?php echo htmlspecialchars($feedback['message']); ?>">
                                            Update
                                        </button>
                                        <form method="POST" action="feedback.php" class="d-inline">
                                            <input type="hidden" name="feedback_id" value="<?php echo $feedback['feedback_id']; ?>">
                                            <input type="hidden" name="delete_feedback" value="1">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this feedback?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($feedbacks)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No feedback found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- New Feedback Modal -->
        <div class="modal fade" id="newFeedbackModal" tabindex="-1" role="dialog" aria-labelledby="newFeedbackModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="newFeedbackModalLabel">Add New Feedback</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="feedback.php">
                            <input type="hidden" name="new_feedback" value="1">
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
                                <label for="message">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Feedback</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <button type="button" class="btn btn-success ml-auto" data-toggle="modal" data-target="#newFeedbackModal">
                    <i class="fas fa-plus"></i> Add New Feedback
                </button>
        <!-- Update Feedback Modal -->
        <div class="modal fade" id="updateFeedbackModal" tabindex="-1" role="dialog" aria-labelledby="updateFeedbackModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateFeedbackModalLabel">Update Feedback</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="feedback.php">
                            <input type="hidden" name="update_feedback" value="1">
                            <input type="hidden" id="update_feedback_id" name="feedback_id">
                            <div class="form-group">
                                <label for="update_user_id">User Name</label>
                                <select class="form-control" id="update_user_id" name="user_id" required>
                                    <option value="" disabled>Select User</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['user_id']; ?>"><?php echo $user['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="update_message">Message</label>
                                <textarea class="form-control" id="update_message" name="message" rows="3" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Feedback</button>
                        </form>
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

        $('#updateFeedbackModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var feedbackId = button.data('feedback-id');
            var userId = button.data('user-id');
            var message = button.data('message');

            var modal = $(this);
            modal.find('#update_feedback_id').val(feedbackId);
            modal.find('#update_user_id').val(userId);
            modal.find('#update_message').val(message);
        });
    });
</script>
</body>
</html>
