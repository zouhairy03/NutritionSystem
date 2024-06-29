<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require 'config/db.php';

// Fetch orders for the dropdown
$ordersResult = $conn->query("SELECT order_id, users.name AS user_name FROM orders INNER JOIN users ON orders.user_id = users.user_id");

// Handle Add Payment
if (isset($_POST['add_payment'])) {
    $order_id = $_POST['order_id'];
    $payment_method = $_POST['payment_method'];
    $status = $_POST['status'];

    $sql = "INSERT INTO payments (order_id, payment_method, status, created_at, updated_at) VALUES ('$order_id', '$payment_method', '$status', NOW(), NOW())";
    $conn->query($sql);
    header("Location: payments.php");
}

// Handle Edit Payment
if (isset($_POST['edit_payment'])) {
    $payment_id = $_POST['payment_id'];
    $order_id = $_POST['order_id'];
    $payment_method = $_POST['payment_method'];
    $status = $_POST['status'];

    // Fetch current status
    $currentStatusResult = $conn->query("SELECT status FROM payments WHERE payment_id='$payment_id'");
    $currentStatus = $currentStatusResult->fetch_assoc()['status'];

    // Insert into payment history if status changes
    if ($currentStatus != $status) {
        $conn->query("INSERT INTO payment_history (payment_id, old_status, new_status, change_date) VALUES ('$payment_id', '$currentStatus', '$status', NOW())");
        
        // Fetch user email
        $userEmailResult = $conn->query("SELECT email FROM users INNER JOIN orders ON users.user_id=orders.user_id WHERE orders.order_id='$order_id'");
        $userEmail = $userEmailResult->fetch_assoc()['email'];
        
        // Send payment notification
        sendPaymentNotification($userEmail, $status);
    }

    $sql = "UPDATE payments SET order_id='$order_id', payment_method='$payment_method', status='$status', updated_at=NOW() WHERE payment_id='$payment_id'";
    $conn->query($sql);
    header("Location: payments.php");
}

// Handle Delete Payment
if (isset($_GET['delete_payment'])) {
    $payment_id = $_GET['delete_payment'];
    $sql = "DELETE FROM payments WHERE payment_id='$payment_id'";
    $conn->query($sql);
    header("Location: payments.php");
}

// Handle Export to Excel
if (isset($_POST['export'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename=payments.xls');
    $output = fopen('php://output', 'w');
    fputcsv($output, array('Payment ID', 'Order ID', 'User Name', 'Payment Method', 'Status', 'Order Total', 'Created At', 'Updated At'));

    $result = $conn->query("SELECT payments.*, orders.total AS order_total, users.name AS user_name FROM payments LEFT JOIN orders ON payments.order_id=orders.order_id LEFT JOIN users ON orders.user_id=users.user_id");
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

// Handle Search and Filter
$search_query = isset($_POST['search_query']) ? $_POST['search_query'] : "";
$order_filter = isset($_POST['order_filter']) ? $_POST['order_filter'] : "";

// Handle Pagination
$limit = 10; // Number of entries per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Fetch payments data with pagination and order filter
$search_sql = $search_query ? "AND (payments.payment_method LIKE '%$search_query%' OR payments.status LIKE '%$search_query%' OR users.name LIKE '%$search_query%')" : "";
$order_sql = $order_filter ? "AND payments.order_id='$order_filter'" : "";
$sql = "SELECT payments.*, orders.total AS order_total, users.name AS user_name FROM payments 
        LEFT JOIN orders ON payments.order_id=orders.order_id 
        LEFT JOIN users ON orders.user_id=users.user_id
        WHERE 1 $search_sql $order_sql 
        LIMIT $start, $limit";
$result = $conn->query($sql);

// Fetch total payments count for pagination
$sql_count = "SELECT COUNT(*) AS total FROM payments LEFT JOIN orders ON payments.order_id=orders.order_id LEFT JOIN users ON orders.user_id=users.user_id WHERE 1 $search_sql $order_sql";
$count_result = $conn->query($sql_count);
$total_payments = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_payments / $limit);

function sendPaymentNotification($email, $status) {
    $subject = "Payment Status Update";
    $message = "Your payment status has been updated to: $status";
    mail($email, $subject, $message);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Payments</title>
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
            <h1><i class="fas fa-dollar-sign"></i> Manage Payments</h1>

            <!-- Export to Excel -->
            <form method="POST" action="payments.php">
                <button type="submit" name="export" class="btn btn-success mb-4"><i class="fas fa-file-excel"></i> Export to Excel</button>
            </form>

            <!-- Search Form -->
            <form action="payments.php" method="POST" class="form-inline mb-4">
                <input type="text" class="form-control mr-sm-2" name="search_query" placeholder="Search" value="<?php echo $search_query; ?>">
                <button type="submit" class="btn btn-primary" name="search"><i class="fas fa-search"></i> Search</button>
            </form>

            <!-- Filter by Order Form -->
            <form action="payments.php" method="POST" class="form-inline mb-4">
                <div class="form-group">
                    <label for="order_filter">Order:</label>
                    <select class="form-control ml-2" id="order_filter" name="order_filter">
                        <option value="">All</option>
                        <?php
                        // Reset the orders result set
                        $ordersResult->data_seek(0);
                        while ($order = $ordersResult->fetch_assoc()): ?>
                            <option value="<?php echo $order['order_id']; ?>" <?php if ($order_filter == $order['order_id']) echo 'selected'; ?>>Order ID: <?php echo $order['order_id']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary ml-2" name="filter_order"><i class="fas fa-filter"></i> Filter</button>
            </form>

            <!-- Add Payment Form -->
            <h2>Add Payment</h2>
            <form action="payments.php" method="POST">
                <div class="form-group">
                    <label for="order_id">Order ID:</label>
                    <select class="form-control" id="order_id" name="order_id" required>
                        <?php
                        // Reset the orders result set again for the add form
                        $ordersResult->data_seek(0);
                        while ($order = $ordersResult->fetch_assoc()): ?>
                            <option value="<?php echo $order['order_id']; ?>"><?php echo $order['order_id']; ?> - <?php echo $order['user_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="payment_method">Payment Method:</label>
                    <input type="text" class="form-control" id="payment_method" name="payment_method" value="Cash on Delivery" required>
                </div>
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select class="form-control" id="status" name="status" required>
                        <option value="Pending">Pending</option>
                        <option value="Completed">Completed</option>
                        <option value="Failed">Failed</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" name="add_payment"><i class="fas fa-plus"></i> Add Payment</button>
            </form>

            <hr>

            <!-- Payments Table -->
            <h2>Payments List</h2>
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Payment ID</th>
                        <th>Order ID</th>
                        <th>User Name</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Order Total (MAD)</th>
                        <th>Created At</th>
                        <th>Updated At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['payment_id']; ?></td>
                            <td><?php echo $row['order_id']; ?></td>
                            <td><?php echo $row['user_name']; ?></td>
                            <td><?php echo $row['payment_method']; ?></td>
                            <td><?php echo $row['status']; ?></td>
                            <td><?php echo $row['order_total']; ?> </td>
                            <td><?php echo $row['created_at']; ?></td>
                            <td><?php echo $row['updated_at']; ?></td>
                            <td>
                                <!-- Edit Payment Button -->
                                <button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editPaymentModal<?php echo $row['payment_id']; ?>"><i class="fas fa-edit"></i> Edit</button>

                                <!-- Delete Payment Link -->
                                <a href="payments.php?delete_payment=<?php echo $row['payment_id']; ?>" class="btn btn-sm btn-danger delete-payment"><i class="fas fa-trash"></i> Delete</a>

                                <!-- Payment History Button -->
                                <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#paymentHistoryModal<?php echo $row['payment_id']; ?>"><i class="fas fa-history"></i> History</button>
                            </td>
                        </tr>

                        <!-- Edit Payment Modal -->
                        <div class="modal fade" id="editPaymentModal<?php echo $row['payment_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editPaymentModalLabel<?php echo $row['payment_id']; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editPaymentModalLabel<?php echo $row['payment_id']; ?>">Edit Payment</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <form action="payments.php" method="POST">
                                            <input type="hidden" name="payment_id" value="<?php echo $row['payment_id']; ?>">
                                            <div class="form-group">
                                                <label for="order_id">Order ID:</label>
                                                <select class="form-control" id="order_id" name="order_id" required>
                                                    <?php
                                                    // Reset the orders result set again for the edit modal
                                                    $ordersResultEdit = $conn->query("SELECT order_id, users.name AS user_name FROM orders INNER JOIN users ON orders.user_id = users.user_id");
                                                    while ($order = $ordersResultEdit->fetch_assoc()): ?>
                                                        <option value="<?php echo $order['order_id']; ?>" <?php if ($row['order_id'] == $order['order_id']) echo 'selected'; ?>><?php echo $order['order_id']; ?> - <?php echo $order['user_name']; ?></option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="payment_method">Payment Method:</label>
                                                <input type="text" class="form-control" id="payment_method" name="payment_method" value="<?php echo $row['payment_method']; ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="status">Status:</label>
                                                <select class="form-control" id="status" name="status" required>
                                                    <option value="Pending" <?php if ($row['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                                                    <option value="Completed" <?php if ($row['status'] == 'Completed') echo 'selected'; ?>>Completed</option>
                                                    <option value="Failed" <?php if ($row['status'] == 'Failed') echo 'selected'; ?>>Failed</option>
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn-primary" name="edit_payment"><i class="fas fa-save"></i> Save changes</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment History Modal -->
                        <div class="modal fade" id="paymentHistoryModal<?php echo $row['payment_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="paymentHistoryModalLabel<?php echo $row['payment_id']; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="paymentHistoryModalLabel<?php echo $row['payment_id']; ?>">Payment History</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <?php
                                        $historyResult = $conn->query("SELECT * FROM payment_history WHERE payment_id='$row[payment_id]' ORDER BY change_date DESC");
                                        if ($historyResult->num_rows > 0): ?>
                                            <ul class="list-group">
                                                <?php while ($history = $historyResult->fetch_assoc()): ?>
                                                    <li class="list-group-item">
                                                        <strong>Old Status:</strong> <?php echo $history['old_status']; ?><br>
                                                        <strong>New Status:</strong> <?php echo $history['new_status']; ?><br>
                                                        <strong>Change Date:</strong> <?php echo $history['change_date']; ?>
                                                    </li>
                                                <?php endwhile; ?>
                                            </ul>
                                        <?php else: ?>
                                            <p>No history available.</p>
                                        <?php endif; ?>
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
                        <a class="page-link" href="payments.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
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

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    $(document).ready(function () {
        $('#sidebarCollapse').on('click', function () {
            $('#sidebar').toggleClass('active');
        });

        // Confirmation for delete
        document.querySelectorAll('.delete-payment').forEach(function(element) {
            element.addEventListener('click', function(event) {
                if (!confirm('Are you sure you want to delete this payment?')) {
                    event.preventDefault();
                }
            });
        });
    });
</script>
</body>
</html>
