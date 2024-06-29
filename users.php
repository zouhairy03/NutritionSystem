<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require 'config/db.php';

// Handle Add User
if (isset($_POST['add_user'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $malady_id = $_POST['malady_id'];
    $password = $_POST['password'];

    $sql = "INSERT INTO users (name, email, phone, malady_id, password, created_at, updated_at) VALUES ('$name', '$email', '$phone', '$malady_id', '$password', NOW(), NOW())";
    $conn->query($sql);
    header("Location: users.php");
    exit();
}

// Handle Edit User
if (isset($_POST['edit_user'])) {
    $user_id = $_POST['user_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $malady_id = $_POST['malady_id'];

    $sql = "UPDATE users SET name='$name', email='$email', phone='$phone', malady_id='$malady_id', updated_at=NOW() WHERE user_id='$user_id'";
    $conn->query($sql);
    header("Location: users.php");
    exit();
}

// Handle Delete User
$delete_error = "";
if (isset($_POST['confirm_delete_user'])) {
    $user_id = $_POST['user_id'];

    // Check for related orders
    $related_orders_query = "SELECT COUNT(*) AS count FROM orders WHERE user_id='$user_id'";
    $related_orders_result = $conn->query($related_orders_query);
    $related_orders_count = $related_orders_result->fetch_assoc()['count'];

    // Check for related feedback
    $related_feedback_query = "SELECT COUNT(*) AS count FROM feedback WHERE user_id='$user_id'";
    $related_feedback_result = $conn->query($related_feedback_query);
    $related_feedback_count = $related_feedback_result->fetch_assoc()['count'];

    if ($related_orders_count > 0) {
        $delete_error = "Cannot delete user. There are orders associated with this user.";
    } elseif ($related_feedback_count > 0) {
        $delete_error = "Cannot delete user. There is feedback associated with this user.";
    } else {
        $sql = "DELETE FROM users WHERE user_id='$user_id'";
        $conn->query($sql);
        header("Location: users.php");
        exit();
    }
}

// Fetch users data
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'users.name';
$sql = "SELECT users.*, maladies.name AS malady_name FROM users LEFT JOIN maladies ON users.malady_id = maladies.malady_id WHERE $filter LIKE '%$search%'";
$result = $conn->query($sql);

// Fetch maladies data for the dropdown
$maladies_query = "SELECT * FROM maladies";
$maladies_result = $conn->query($maladies_query);

// Handle Excel download
if (isset($_GET['download'])) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=users.xls");
    $output = fopen("php://output", "w");
    // Use tab delimiter instead of comma
    fputcsv($output, array('User ID', 'Name', 'Email', 'Phone', 'Malady', 'Created At', 'Updated At'), "\t");
    $download_sql = "SELECT users.user_id, users.name, users.email, users.phone, maladies.name AS malady_name, users.created_at, users.updated_at FROM users LEFT JOIN maladies ON users.malady_id = maladies.malady_id";
    $download_result = $conn->query($download_sql);
    while ($row = $download_result->fetch_assoc()) {
        fputcsv($output, $row, "\t");
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
    <title>Manage Users</title>
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
    <li><a href="send_delivery_notifications.php"><i class="fas fa-bell"></i> Delivery Notifications</a></li> <!-- Added for delivery notifications -->
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
                    <img src="Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png" style="margin-right: 400px; height: 250px; width: 60%;" alt="NutriDaily Logo" class="logo">
                </div>
            </div>
        </nav>
        
        <div class="container mt-5">
            <h1><i class="fas fa-users"></i> Manage Users</h1>

            <?php if ($delete_error): ?>
                <div class="alert alert-danger"><?php echo $delete_error; ?></div>
            <?php endif; ?>

            <!-- Search and Filter -->
            <div class="table-search">
                <form action="users.php" method="GET" class="form-inline">
                    <div class="form-group mb-2">
                        <input type="text" name="search" class="form-control" placeholder="Search" value="<?php echo $search; ?>">
                    </div>
                    <div class="form-group mx-sm-3 mb-2">
                        <select name="filter" class="form-control">
                            <option value="users.name" <?php if ($filter == 'users.name') echo 'selected'; ?>>Name</option>
                            <option value="users.email" <?php if ($filter == 'users.email') echo 'selected'; ?>>Email</option>
                            <option value="users.phone" <?php if ($filter == 'users.phone') echo 'selected'; ?>>Phone</option>
                            <option value="maladies.name" <?php if ($filter == 'maladies.name') echo 'selected'; ?>>Malady</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary mb-2"><i class="fas fa-search"></i> Search</button>
                    <a href="users.php?download=true" class="btn btn-success mb-2 ml-2"><i class="fas fa-file-excel"></i> Download Excel</a>
                </form>
            </div>

            <!-- Add User Button -->
            <button class="btn btn-success mb-4" data-toggle="modal" data-target="#addUserModal"><i class="fas fa-plus"></i> Add User</button>

            <!-- Users Table -->
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Malady</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['user_id']; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['phone']; ?></td>
                            <td><?php echo $row['malady_name']; ?></td>
                            <td>
                                <!-- Edit User Button -->
                                <button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editUserModal<?php echo $row['user_id']; ?>"><i class="fas fa-edit"></i> Edit</button>

                                <!-- Delete User Button -->
                                <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#confirmDeleteModal" data-userid="<?php echo $row['user_id']; ?>"><i class="fas fa-trash"></i> Delete</button>
                            </td>
                        </tr>

                        <!-- Edit User Modal -->
                        <div class="modal fade" id="editUserModal<?php echo $row['user_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel<?php echo $row['user_id']; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editUserModalLabel<?php echo $row['user_id']; ?>">Edit User</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <form action="users.php" method="POST">
                                            <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                            <div class="form-group">
                                                <label for="name">Name:</label>
                                                <input type="text" class="form-control" id="name" name="name" value="<?php echo $row['name']; ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="email">Email:</label>
                                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $row['email']; ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="phone">Phone:</label>
                                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $row['phone']; ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="malady_id">Malady:</label>
                                                <select class="form-control" id="malady_id" name="malady_id" required>
                                                    <?php 
                                                    // Reset the result pointer and fetch maladies again
                                                    $maladies_result->data_seek(0);
                                                    while ($malady = $maladies_result->fetch_assoc()): ?>
                                                        <option value="<?php echo $malady['malady_id']; ?>" <?php if ($row['malady_id'] == $malady['malady_id']) echo 'selected'; ?>><?php echo $malady['name']; ?></option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn-primary" name="edit_user"><i class="fas fa-save"></i> Save changes</button>
                                        </form>
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

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Add User</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="users.php" method="POST">
                    <div class="form-group">
                        <label for="name">Name:</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone:</label>
                        <input type="text" class="form-control" id="phone" name="phone" required>
                    </div>
                    <div class="form-group">
                        <label for="malady_id">Malady:</label>
                        <select class="form-control" id="malady_id" name="malady_id" required>
                            <?php 
                            // Reset the result pointer and fetch maladies again
                            $maladies_result->data_seek(0);
                            while ($malady = $maladies_result->fetch_assoc()): ?>
                                <option value="<?php echo $malady['malady_id']; ?>"><?php echo $malady['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary" name="add_user"><i class="fas fa-plus"></i> Add User</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Delete Modal -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Confirm Delete</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this user?
                <form action="users.php" method="POST">
                    <input type="hidden" name="user_id" id="delete-user-id">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" name="confirm_delete_user">Delete</button>
                    </div>
                </form>
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

        $('#confirmDeleteModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var userId = button.data('userid');
            var modal = $(this);
            modal.find('#delete-user-id').val(userId);
        });
    });
</script>
</body>
</html>
