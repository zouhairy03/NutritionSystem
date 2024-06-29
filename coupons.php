<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require 'config/db.php';

// Handle Add Coupon
if (isset($_POST['add_coupon'])) {
    $code = $_POST['code'];
    $discount_percentage = $_POST['discount_percentage'];
    $expiry_date = $_POST['expiry_date'];

    $sql = "INSERT INTO coupons (code, discount_percentage, expiry_date, created_at, updated_at) VALUES ('$code', '$discount_percentage', '$expiry_date', NOW(), NOW())";
    $conn->query($sql);
    header("Location: coupons.php");
}

// Handle Edit Coupon
if (isset($_POST['edit_coupon'])) {
    $coupon_id = $_POST['coupon_id'];
    $code = $_POST['code'];
    $discount_percentage = $_POST['discount_percentage'];
    $expiry_date = $_POST['expiry_date'];

    $sql = "UPDATE coupons SET code='$code', discount_percentage='$discount_percentage', expiry_date='$expiry_date', updated_at=NOW() WHERE coupon_id='$coupon_id'";
    $conn->query($sql);
    header("Location: coupons.php");
}

// Handle Delete Coupon
$delete_error = "";
if (isset($_GET['delete_coupon'])) {
    $coupon_id = $_GET['delete_coupon'];

    // Check for related orders
    $related_orders_query = "SELECT COUNT(*) AS count FROM orders WHERE coupon_id='$coupon_id'";
    $related_orders_result = $conn->query($related_orders_query);
    $related_orders_count = $related_orders_result->fetch_assoc()['count'];

    if ($related_orders_count > 0) {
        $delete_error = "Cannot delete coupon. There are orders associated with this coupon.";
    } else {
        $sql = "DELETE FROM coupons WHERE coupon_id='$coupon_id'";
        $conn->query($sql);
        header("Location: coupons.php");
    }
}

// Handle search functionality
$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM coupons WHERE code LIKE '%$search%'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Coupons</title>
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
            background:    #809B53 ; /* Green color */
            color: #fff;
            transition: all 0.3s;
        }
        #sidebar.active {
            margin-left: -250px;
        }
        #sidebar .sidebar-header {
            padding: 20px;
            background:    #809B53 ; /* Green color */
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
            <h1><i class="fas fa-tags"></i> Manage Coupons</h1>

            <?php if ($delete_error): ?>
                <div class="alert alert-danger"><?php echo $delete_error; ?></div>
            <?php endif; ?>

            <!-- Search Button -->
            <div class="table-search mb-4">
                <form action="coupons.php" method="GET" class="form-inline">
                    <div class="form-group mb-2">
                        <input type="text" name="search" class="form-control" placeholder="Search" value="<?php echo $_GET['search'] ?? ''; ?>">
                    </div>
                    <button type="submit" class="btn btn-primary mb-2"><i class="fas fa-search"></i> Search</button>
                </form>
            </div>

            <!-- Add Coupon Button -->
            <button class="btn btn-success mb-4" data-toggle="modal" data-target="#addCouponModal"><i class="fas fa-plus"></i> Add Coupon</button>

            <!-- Coupons Table -->
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Coupon ID</th>
                        <th>Code</th>
                        <th>Discount Percentage</th>
                        <th>Expiry Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['coupon_id']; ?></td>
                            <td><?php echo $row['code']; ?></td>
                            <td><?php echo $row['discount_percentage']; ?></td>
                            <td><?php echo $row['expiry_date']; ?></td>
                            <td>
                                <!-- Edit Coupon Button -->
                                <button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editCouponModal<?php echo $row['coupon_id']; ?>"><i class="fas fa-edit"></i> Edit</button>

                                <!-- Delete Coupon Link -->
                                <a href="coupons.php?delete_coupon=<?php echo $row['coupon_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this coupon?')"><i class="fas fa-trash"></i> Delete</a>
                            </td>
                        </tr>

                        <!-- Edit Coupon Modal -->
                        <div class="modal fade" id="editCouponModal<?php echo $row['coupon_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editCouponModalLabel<?php echo $row['coupon_id']; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editCouponModalLabel<?php echo $row['coupon_id']; ?>">Edit Coupon</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <form action="coupons.php" method="POST">
                                            <input type="hidden" name="coupon_id" value="<?php echo $row['coupon_id']; ?>">
                                            <div class="form-group">
                                                <label for="code">Code:</label>
                                                <input type="text" class="form-control" id="code" name="code" value="<?php echo $row['code']; ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="discount_percentage">Discount Percentage:</label>
                                                <input type="number" step="0.01" class="form-control" id="discount_percentage" name="discount_percentage" value="<?php echo $row['discount_percentage']; ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="expiry_date">Expiry Date:</label>
                                                <input type="date" class="form-control" id="expiry_date" name="expiry_date" value="<?php echo $row['expiry_date']; ?>" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary" name="edit_coupon"><i class="fas fa-save"></i> Save changes</button>
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

<!-- Add Coupon Modal -->
<div class="modal fade" id="addCouponModal" tabindex="-1" role="dialog" aria-labelledby="addCouponModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCouponModalLabel">Add Coupon</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="coupons.php" method="POST">
                    <div class="form-group">
                        <label for="code">Code:</label>
                        <input type="text" class="form-control" id="code" name="code" required>
                    </div>
                    <div class="form-group">
                        <label for="discount_percentage">Discount Percentage:</label>
                        <input type="number" step="0.01" class="form-control" id="discount_percentage" name="discount_percentage" required>
                    </div>
                    <div class="form-group">
                        <label for="expiry_date">Expiry Date:</label>
                        <input type="date" class="form-control" id="expiry_date" name="expiry_date" required>
                    </div>
                    <button type="submit" class="btn btn-primary" name="add_coupon"><i class="fas fa-plus"></i> Add Coupon</button>
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
