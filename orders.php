<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require 'config/db.php';

// Fetch users, meals, and coupons for the dropdowns
$usersResult = $conn->query("SELECT user_id, name FROM users");
$mealsResult = $conn->query("SELECT meal_id, name, price FROM meals");
$couponsResult = $conn->query("SELECT coupon_id, code, discount_percentage FROM coupons");

// Order statuses
$orderStatuses = ['Pending', 'Completed', 'Cancelled'];

// Handle Add Order
if (isset($_POST['add_order'])) {
    $user_id = $_POST['user_id'];
    $meal_id = $_POST['meal_id'];
    $quantity = $_POST['quantity'];
    $status = $_POST['status'];
    $coupon_code = $_POST['coupon_code'];
    $payment_method = 'Cash on Delivery';

    // Start transaction
    $conn->begin_transaction();

    try {
        // Get coupon discount if applicable
        $discount = 0;
        if (!empty($coupon_code)) {
            $couponQuery = $conn->query("SELECT discount_percentage FROM coupons WHERE code='$coupon_code'");
            if ($couponQuery->num_rows > 0) {
                $coupon = $couponQuery->fetch_assoc();
                $discount = $coupon['discount_percentage'];
            }
        }

        // Calculate total after discount
        $mealPriceQuery = $conn->query("SELECT price FROM meals WHERE meal_id='$meal_id'");
        $mealPrice = $mealPriceQuery->fetch_assoc()['price'];
        $total = $mealPrice * $quantity * ((100 - $discount) / 100);

        // Insert order
        $sql = "INSERT INTO orders (user_id, meal_id, quantity, status, total, payment_method, coupon_id, created_at, updated_at) VALUES ('$user_id', '$meal_id', '$quantity', '$status', '$total', '$payment_method', (SELECT coupon_id FROM coupons WHERE code='$coupon_code'), NOW(), NOW()) ";
        $conn->query($sql);

        // Update stock
        $updateStockSql = "UPDATE meals SET stock = stock - $quantity WHERE meal_id = '$meal_id'";
        $conn->query($updateStockSql);

        // Commit transaction
        $conn->commit();
        
        header("Location: orders.php");
    } catch (Exception $e) {
        // Rollback transaction if something goes wrong
        $conn->rollback();
        echo "Failed to add order: " . $e->getMessage();
    }
}

// Handle Edit Order
if (isset($_POST['edit_order'])) {
    $order_id = $_POST['order_id'];
    $user_id = $_POST['user_id'];
    $meal_id = $_POST['meal_id'];
    $quantity = $_POST['quantity'];
    $status = $_POST['status'];
    $coupon_code = $_POST['coupon_code'];
    $payment_method = 'Cash on Delivery';

    // Get coupon discount if applicable
    $discount = 0;
    if (!empty($coupon_code)) {
        $couponQuery = $conn->query("SELECT discount_percentage FROM coupons WHERE code='$coupon_code'");
        if ($couponQuery->num_rows > 0) {
            $coupon = $couponQuery->fetch_assoc();
            $discount = $coupon['discount_percentage'];
        }
    }

    // Calculate total after discount
    $mealPriceQuery = $conn->query("SELECT price FROM meals WHERE meal_id='$meal_id'");
    $mealPrice = $mealPriceQuery->fetch_assoc()['price'];
    $total = $mealPrice * $quantity * ((100 - $discount) / 100);

    $sql = "UPDATE orders SET user_id='$user_id', meal_id='$meal_id', quantity='$quantity', status='$status', total='$total', payment_method='$payment_method', coupon_id=(SELECT coupon_id FROM coupons WHERE code='$coupon_code'), updated_at=NOW() WHERE order_id='$order_id'";
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
        // Delete related payments
        $delete_payments_sql = "DELETE FROM payments WHERE order_id='$order_id'";
        $conn->query($delete_payments_sql);

        // Delete the order
        $sql = "DELETE FROM orders WHERE order_id='$order_id'";
        $conn->query($sql);
        header("Location: orders.php");
    }
}

// Fetch orders data
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';
$filter_condition = '';

if (!empty($search) && !empty($filter)) {
    if ($filter == 'user_name') {
        $filter_condition = "WHERE users.name LIKE '%$search%'";
    } elseif ($filter == 'meal_name') {
        $filter_condition = "WHERE meals.name LIKE '%$search%'";
    } elseif ($filter == 'status') {
        $filter_condition = "WHERE orders.status LIKE '%$search%'";
    }
}

$sql = "SELECT orders.*, users.name as user_name, meals.name as meal_name, coupons.code as coupon_code 
        FROM orders 
        LEFT JOIN users ON orders.user_id = users.user_id 
        LEFT JOIN meals ON orders.meal_id = meals.meal_id 
        LEFT JOIN coupons ON orders.coupon_id = coupons.coupon_id 
        $filter_condition";
$result = $conn->query($sql);

// Handle Excel download
if (isset($_GET['download'])) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=orders.xls");
    $output = fopen("php://output", "w");
    fputcsv($output, array('Order ID', 'User Name', 'Meal Name', 'Quantity', 'Status', 'Total (MAD)', 'Coupon Code', 'Payment Method', 'Created At'), "\t");
    $download_sql = "SELECT orders.*, users.name as user_name, meals.name as meal_name, coupons.code as coupon_code FROM orders LEFT JOIN users ON orders.user_id = users.user_id LEFT JOIN meals ON orders.meal_id = meals.meal_id LEFT JOIN coupons ON orders.coupon_id = coupons.coupon_id $filter_condition";
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
            background:  #809B53 ;
            color: #fff;
            transition: all 0.3s;
        }
        #sidebar.active {
            margin-left: -250px;
        }
        #sidebar .sidebar-header {
            padding: 20px;
            background:  #809B53 ;
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
            color: #3E8E41;
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
            background: #3E8E41;
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
                    <img src="Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png" style="margin-right: 400px;height: 250px; width: 60%;" alt="NutriDaily Logo" class="logo">
                </div>
            </div>
        </nav>
        
        <div class="container mt-5">
            <h1><i class="fas fa-shopping-cart"></i> Manage Orders</h1>

            <?php if ($delete_error): ?>
                <div class="alert alert-danger"><?php echo $delete_error; ?></div>
            <?php endif; ?>

            <!-- Search, Filter, and Export Buttons -->
            <div class="table-search mb-4">
                <form action="orders.php" method="GET" class="form-inline">
                    <div class="form-group mb-2">
                        <input type="text" name="search" class="form-control" placeholder="Search" value="<?php echo $_GET['search'] ?? ''; ?>">
                    </div>
                    <div class="form-group mx-sm-3 mb-2">
                        <select name="filter" class="form-control">
                            <option value="users.name" <?php if ($_GET['filter'] ?? '' == 'users.name') echo 'selected'; ?>>User Name</option>
                            <option value="meals.name" <?php if ($_GET['filter'] ?? '' == 'meals.name') echo 'selected'; ?>>Meal Name</option>
                            <option value="orders.status" <?php if ($_GET['filter'] ?? '' == 'orders.status') echo 'selected'; ?>>Status</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary mb-2"><i class="fas fa-search"></i> Search</button>
                    <a href="orders.php?download=true" class="btn btn-success mb-2 ml-2"><i class="fas fa-file-excel"></i> Export to Excel</a>
                </form>
            </div>

            <!-- Add Order Button -->
            <button class="btn btn-success mb-4" data-toggle="modal" data-target="#addOrderModal"><i class="fas fa-plus"></i> Add Order</button>

            <!-- Orders Table -->
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Order ID</th>
                        <th>User Name</th>
                        <th>Meal Name</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Total (MAD)</th>
                        <th>Coupon Code</th>
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
                            <td><?php echo $row['meal_name']; ?></td>
                            <td><?php echo $row['quantity']; ?></td>
                            <td><?php echo $row['status']; ?></td>
                            <td><?php echo $row['total']; ?> </td>
                            <td><?php echo $row['coupon_code']; ?></td>
                            <td><?php echo $row['payment_method']; ?></td>
                            <td><?php echo $row['created_at']; ?></td>
                            <td>
                                <!-- Edit Order Button -->
                                <button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editOrderModal<?php echo $row['order_id']; ?>"><i class="fas fa-edit"></i> Edit</button>

                                <!-- Delete Order Link -->
                                <a href="orders.php?delete_order=<?php echo $row['order_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this order?')"><i class="fas fa-trash"></i> Delete</a>

                                <!-- Print Button -->
                                <button class="btn btn-sm btn-primary" onclick="printOrder(<?php echo $row['order_id']; ?>, '<?php echo $row['user_name']; ?>', '<?php echo $row['meal_name']; ?>', '<?php echo $row['quantity']; ?>', '<?php echo $row['status']; ?>', '<?php echo $row['total']; ?> MAD', '<?php echo $row['coupon_code']; ?>', '<?php echo $row['payment_method']; ?>', '<?php echo $row['created_at']; ?>')"><i class="fas fa-print"></i> Print</button>
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
                                                <label for="meal_id">Meal:</label>
                                                <select class="form-control" id="meal_id" name="meal_id" required onchange="calculateTotal()">
                                                    <?php
                                                    $mealsResult->data_seek(0); // Reset the pointer to the beginning
                                                    while ($meal = $mealsResult->fetch_assoc()): ?>
                                                        <option value="<?php echo $meal['meal_id']; ?>" data-price="<?php echo $meal['price']; ?>" <?php if ($meal['meal_id'] == $row['meal_id']) echo 'selected'; ?>><?php echo $meal['name']; ?></option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="quantity">Quantity:</label>
                                                <input type="number" class="form-control" id="quantity" name="quantity" value="<?php echo $row['quantity']; ?>" required onchange="calculateTotal()">
                                            </div>
                                            <div class="form-group">
                                                <label for="status">Status:</label>
                                                <select class="form-control" id="status" name="status" required>
                                                    <?php foreach ($orderStatuses as $orderStatus): ?>
                                                        <option value="<?php echo $orderStatus; ?>" <?php if ($orderStatus == $row['status']) echo 'selected'; ?>><?php echo $orderStatus; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="coupon_code">Coupon Code:</label>
                                                <input type="text" class="form-control" id="coupon_code" name="coupon_code" value="<?php echo $row['coupon_code']; ?>" onchange="calculateTotal()">
                                            </div>
                                            <div class="form-group">
                                                <label for="total">Total (MAD):</label>
                                                <input type="number" step="0.01" class="form-control" id="total" name="total" value="<?php echo $row['total']; ?>" required readonly>
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
                            <?php
                            $usersResult->data_seek(0); // Reset the pointer to the beginning
                            while ($user = $usersResult->fetch_assoc()): ?>
                                <option value="<?php echo $user['user_id']; ?>"><?php echo $user['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="meal_id">Meal:</label>
                        <select class="form-control" id="meal_id" name="meal_id" required onchange="calculateTotal()">
                            <?php
                            $mealsResult->data_seek(0); // Reset the pointer to the beginning
                            while ($meal = $mealsResult->fetch_assoc()): ?>
                                <option value="<?php echo $meal['meal_id']; ?>" data-price="<?php echo $meal['price']; ?>"><?php echo $meal['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quantity">Quantity:</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" required onchange="calculateTotal()">
                    </div>
                    <div class="form-group">
                        <label for="status">Status:</label>
                        <select class="form-control" id="status" name="status" required>
                            <?php foreach ($orderStatuses as $orderStatus): ?>
                                <option value="<?php echo $orderStatus; ?>"><?php echo $orderStatus; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="coupon_code">Coupon Code:</label>
                        <input type="text" class="form-control" id="coupon_code" name="coupon_code" onchange="calculateTotal()">
                    </div>
                    <div class="form-group">
                        <label for="total">Total (MAD):</label>
                        <input type="number" step="0.01" class="form-control" id="total" name="total" required readonly>
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

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    $(document).ready(function () {
        $('#sidebarCollapse').on('click', function () {
            $('#sidebar').toggleClass('active');
        });
    });

    function printOrder(orderId, userName, mealName, quantity, status, total, couponCode, paymentMethod, createdAt) {
        // Open a new window for printing
        var printWindow = window.open('', '_blank');
        printWindow.document.write('<html><head><title>Print Order</title>');
        printWindow.document.write('<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">');
        printWindow.document.write('</head><body>');
        printWindow.document.write('<div class="container mt-5">');
        printWindow.document.write('<h1>Order Details</h1>');
        printWindow.document.write('<p><strong>Order ID:</strong> ' + orderId + '</p>');
        printWindow.document.write('<p><strong>User Name:</strong> ' + userName + '</p>');
        printWindow.document.write('<p><strong>Meal Name:</strong> ' + mealName + '</p>');
        printWindow.document.write('<p><strong>Quantity:</strong> ' + quantity + '</p>');
        printWindow.document.write('<p><strong>Status:</strong> ' + status + '</p>');
        printWindow.document.write('<p><strong>Total:</strong> ' + total + '</p>');
        printWindow.document.write('<p><strong>Coupon Code:</strong> ' + couponCode + '</p>');
        printWindow.document.write('<p><strong>Payment Method:</strong> ' + paymentMethod + '</p>');
        printWindow.document.write('<p><strong>Created At:</strong> ' + createdAt + '</p>');
        printWindow.document.write('</div>');
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.print();
    }

    function calculateTotal() {
        var mealSelect = document.getElementById('meal_id');
        var quantity = document.getElementById('quantity').value;
        var couponCode = document.getElementById('coupon_code').value;

        if (!mealSelect || !quantity) return;

        var mealPrice = mealSelect.options[mealSelect.selectedIndex].getAttribute('data-price');

        $.ajax({
            url: 'calculate_total.php',
            type: 'POST',
            data: {
                meal_price: mealPrice,
                quantity: quantity,
                coupon_code: couponCode
            },
            success: function (response) {
                document.getElementById('total').value = response.total;
            }
        });
    }
</script>
</body>
</html>
