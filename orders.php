<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require 'config/db.php';

// Fetch users and coupons for the dropdowns
$usersResult = $conn->query("SELECT user_id, name FROM users");
$couponsResult = $conn->query("SELECT coupon_id, code, discount_percentage FROM coupons");

// Handle Add Order
if (isset($_POST['add_order'])) {
    $user_id = $_POST['user_id'];
    $coupon_id = $_POST['coupon_id'] ? $_POST['coupon_id'] : 'NULL';
    $status = $_POST['status'];
    $total = $_POST['total'];
    $payment_method = 'Cash on Delivery';

    $discount_amount = 0;
    if ($coupon_id != 'NULL') {
        $coupon = $conn->query("SELECT discount_percentage FROM coupons WHERE coupon_id = $coupon_id")->fetch_assoc();
        $discount = $coupon['discount_percentage'];
        $discount_amount = $total * ($discount / 100);
        $total = $total - $discount_amount;
    }

    $sql = "INSERT INTO orders (user_id, coupon_id, status, total, discount_amount, payment_method, created_at, updated_at) VALUES ('$user_id', $coupon_id, '$status', '$total', '$discount_amount', '$payment_method', NOW(), NOW())";
    $conn->query($sql);
    header("Location: orders.php");
}

// Handle Edit Order
if (isset($_POST['edit_order'])) {
    $order_id = $_POST['order_id'];
    $user_id = $_POST['user_id'];
    $coupon_id = $_POST['coupon_id'] ? $_POST['coupon_id'] : 'NULL';
    $status = $_POST['status'];
    $total = $_POST['total'];
    $payment_method = 'Cash on Delivery';

    $discount_amount = 0;
    if ($coupon_id != 'NULL') {
        $coupon = $conn->query("SELECT discount_percentage FROM coupons WHERE coupon_id = $coupon_id")->fetch_assoc();
        $discount = $coupon['discount_percentage'];
        $discount_amount = $total * ($discount / 100);
        $total = $total - $discount_amount;
    }

    $sql = "UPDATE orders SET user_id='$user_id', coupon_id=$coupon_id, status='$status', total='$total', discount_amount='$discount_amount', payment_method='$payment_method', updated_at=NOW() WHERE order_id='$order_id'";
    $conn->query($sql);
    header("Location: orders.php");
}

// Handle Delete Order
$delete_error = "";
if (isset($_GET['delete_order'])) {
    $order_id = $_GET['delete_order'];

    // Check for related deliveries
    $related_deliveries_query = "SELECT COUNT(*) AS count FROM deliveries WHERE order_id='$order_id'";
    $related_deliveries_result = $conn->query($related_deliveries_query);
    $related_deliveries_count = $related_deliveries_result->fetch_assoc()['count'];

    if ($related_deliveries_count > 0) {
        $delete_error = "Cannot delete order. There are deliveries associated with this order.";
    } else {
        $sql = "DELETE FROM orders WHERE order_id='$order_id'";
        $conn->query($sql);
        header("Location: orders.php");
    }
}

// Fetch orders data
$sql = "SELECT orders.*, users.name as user_name, coupons.code as coupon_code, coupons.discount_percentage
        FROM orders
        LEFT JOIN users ON orders.user_id = users.user_id
        LEFT JOIN coupons ON orders.coupon_id = coupons.coupon_id";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders</title>
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
            background: #343a40;
            color: #fff;
            transition: all 0.3s;
        }
        #sidebar.active {
            margin-left: -250px;
        }
        #sidebar .sidebar-header {
            padding: 20px;
            background: #343a40;
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
            color: #343a40;
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
            background: #343a40;
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
            <li><a href="delivers.php"><i class="fas fa-people-carry"></i> Deliver Personnel</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <!-- Page Content -->
    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-info">
                    <i class="fas fa-align-left"></i>
                    <span>Toggle Sidebar</span>
                </button>
            </div>
        </nav>
        
        <div class="container mt-5">
            <h1><i class="fas fa-shopping-cart"></i> Manage Orders</h1>

            <?php if ($delete_error): ?>
                <div class="alert alert-danger"><?php echo $delete_error; ?></div>
            <?php endif; ?>

            <!-- Add Order Button -->
            <button class="btn btn-success mb-4" data-toggle="modal" data-target="#addOrderModal"><i class="fas fa-plus"></i> Add Order</button>

            <!-- Orders Table -->
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Order ID</th>
                        <th>User Name</th>
                        <th>Coupon Code</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Discount Amount</th>
                        <th>Payment Method</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['order_id']; ?></td>
                            <td><?php echo $row['user_name']; ?></td>
                            <td><?php echo $row['coupon_code']; ?></td>
                            <td><?php echo $row['status']; ?></td>
                            <td><?php echo $row['total']; ?></td>
                            <td><?php echo $row['discount_amount']; ?></td>
                            <td><?php echo $row['payment_method']; ?></td>
                            <td><?php echo $row['created_at']; ?></td>
                            <td>
                                <!-- Edit Order Button -->
                                <button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editOrderModal<?php echo $row['order_id']; ?>"><i class="fas fa-edit"></i> Edit</button>

                                <!-- Delete Order Link -->
                                <a href="orders.php?delete_order=<?php echo $row['order_id']; ?>" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</a>

                                <!-- Print Button -->
                                <button class="btn btn-sm btn-primary" onclick="printOrder(<?php echo $row['order_id']; ?>, '<?php echo $row['user_name']; ?>', '<?php echo $row['coupon_code']; ?>', '<?php echo $row['status']; ?>', '<?php echo $row['total']; ?>', '<?php echo $row['discount_amount']; ?>', '<?php echo $row['payment_method']; ?>', '<?php echo $row['created_at']; ?>')"><i class="fas fa-print"></i> Print</button>
                            </td>
                        </tr>

                        <!-- Edit Order Modal -->
                        <div class="modal fade" id="editOrderModal<?php echo $row['order_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editOrderModalLabel<?php echo $row['order_id']; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editOrderModalLabel<?php echo $row['order_id']; ?>">Edit Order</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <form action="orders.php" method="POST">
                                            <input type="hidden" name="order_id" value="<?php echo $row['order_id']; ?>">
                                            <div class="form-group">
                                                <label for="user_id">User:</label>
                                                <select class="form-control" id="user_id" name="user_id" required>
                                                    <?php
                                                    $usersResult->data_seek(0); // Reset the pointer to the beginning
                                                    while ($user = $usersResult->fetch_assoc()): ?>
                                                        <option value="<?php echo $user['user_id']; ?>" <?php if ($user['user_id'] == $row['user_id']) echo 'selected'; ?>><?php echo $user['name']; ?></option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="coupon_id">Coupon:</label>
                                                <select class="form-control" id="coupon_id" name="coupon_id">
                                                    <option value="">No Coupon</option>
                                                    <?php
                                                    $couponsResult->data_seek(0); // Reset the pointer to the beginning
                                                    while ($coupon = $couponsResult->fetch_assoc()): ?>
                                                        <option value="<?php echo $coupon['coupon_id']; ?>" <?php if ($coupon['coupon_id'] == $row['coupon_id']) echo 'selected'; ?>><?php echo $coupon['code']; ?></option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="status">Status:</label>
                                                <input type="text" class="form-control" id="status" name="status" value="<?php echo $row['status']; ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="total">Total:</label>
                                                <input type="number" step="0.01" class="form-control" id="total" name="total" value="<?php echo $row['total']; ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="payment_method">Payment Method:</label>
                                                <input type="text" class="form-control" id="payment_method" name="payment_method" value="<?php echo $row['payment_method']; ?>" readonly>
                                            </div>
                                            <button type="submit" class="btn btn-primary" name="edit_order"><i class="fas fa-save"></i> Save changes</button>
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

<!-- Add Order Modal -->
<div class="modal fade" id="addOrderModal" tabindex="-1" role="dialog" aria-labelledby="addOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addOrderModalLabel">Add Order</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="orders.php" method="POST">
                    <div class="form-group">
                        <label for="user_id">User:</label>
                        <select class="form-control" id="user_id" name="user_id" required>
                            <?php while ($user = $usersResult->fetch_assoc()): ?>
                                <option value="<?php echo $user['user_id']; ?>"><?php echo $user['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="coupon_id">Coupon:</label>
                        <select class="form-control" id="coupon_id" name="coupon_id">
                            <option value="">No Coupon</option>
                            <?php while ($coupon = $couponsResult->fetch_assoc()): ?>
                                <option value="<?php echo $coupon['coupon_id']; ?>"><?php echo $coupon['code']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Status:</label>
                        <input type="text" class="form-control" id="status" name="status" required>
                    </div>
                    <div class="form-group">
                        <label for="total">Total:</label>
                        <input type="number" step="0.01" class="form-control" id="total" name="total" required>
                    </div>
                    <div class="form-group">
                        <label for="payment_method">Payment Method:</label>
                        <input type="text" class="form-control" id="payment_method" name="payment_method" value="Cash on Delivery" readonly>
                    </div>
                    <button type="submit" class="btn btn-primary" name="add_order"><i class="fas fa-plus"></i> Add Order</button>
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

    function printOrder(orderId, userName, couponCode, status, total, discountAmount, paymentMethod, createdAt) {
        // Open a new window for printing
        var printWindow = window.open('', '_blank');
        printWindow.document.write('<html><head><title>Print Order</title>');
        printWindow.document.write('<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">');
        printWindow.document.write('</head><body>');
        printWindow.document.write('<div class="container mt-5">');
        printWindow.document.write('<h1>Order Details</h1>');
        printWindow.document.write('<p><strong>Order ID:</strong> ' + orderId + '</p>');
        printWindow.document.write('<p><strong>User Name:</strong> ' + userName + '</p>');
        printWindow.document.write('<p><strong>Coupon Code:</strong> ' + couponCode + '</p>');
        printWindow.document.write('<p><strong>Status:</strong> ' + status + '</p>');
        printWindow.document.write('<p><strong>Total:</strong> ' + total + '</p>');
        printWindow.document.write('<p><strong>Discount Amount:</strong> ' + discountAmount + '</p>');
        printWindow.document.write('<p><strong>Payment Method:</strong> ' + paymentMethod + '</p>');
        printWindow.document.write('<p><strong>Created At:</strong> ' + createdAt + '</p>');
        printWindow.document.write('</div>');
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.print();
    }
</script>
</body>
</html>
