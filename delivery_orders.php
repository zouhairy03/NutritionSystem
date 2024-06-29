<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: delivery_login.php");
    exit();
}
require 'config/db.php';

// Fetch delivery personnel details
$delivery_id = $_SESSION['id'];
$delivery_query = $conn->query("SELECT name FROM delivery_personnel WHERE id = $delivery_id");
if ($delivery_query === false) {
    die('Error fetching delivery person details: ' . $conn->error);
}
$delivery = $delivery_query->fetch_assoc();
$delivery_name = $delivery['name'];

// Update order status if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $update_query = $conn->query("UPDATE orders SET status = '$status' WHERE order_id = $order_id");
    if ($update_query === false) {
        die('Error updating order status: ' . $conn->error);
    }
}

// Fetch orders assigned to this delivery personnel with status 'Pending' or 'Completed'
$search_query = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = "AND (o.order_id LIKE '%" . $_GET['search'] . "%' OR u.name LIKE '%" . $_GET['search'] . "%' OR u.email LIKE '%" . $_GET['search'] . "%')";
}

$sql = "SELECT DISTINCT o.order_id, o.user_id, o.meal_id, o.quantity, o.address_id, o.coupon_id, o.status, o.total, o.created_at, o.updated_at, o.payment_method, o.discount_amount, o.cost, u.name AS user_name, u.email AS user_email 
        FROM orders o
        JOIN deliveries d ON o.order_id = d.order_id
        JOIN users u ON o.user_id = u.user_id
        WHERE d.delivery_person_id = $delivery_id AND (o.status = 'Pending' OR o.status = 'Completed') $search_query";
$orders_query = $conn->query($sql);
if ($orders_query === false) {
    die('Error fetching orders: ' . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Orders</title>
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
        .card {
            margin-bottom: 20px;
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .card .card-body {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card i {
            font-size: 2.5em;
        }
        #sidebarCollapse {
            background: #343a40;
            border: none;
            color: #fff;
            padding: 10px;
            cursor: pointer;
        }
        .welcome-message {
            background-color: whitesmoke;
            color: black;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        .table-responsive {
            margin-top: 20px;
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
            <li><a href="delivery_support.php"><i class="fas fa-headset"></i> Support</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <!-- Page Content -->
    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-light ">
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

        <div class="container-fluid">
            <!-- Search Form -->
            <div class="mb-4">
                <form class="form-inline" action="" method="GET">
                    <input class="form-control mr-sm-2" type="search" name="search" placeholder="Search by Order ID, User Name, Email" aria-label="Search">
                    <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button>
                </form>
                <form action="generate_pdf.php" method="post" style="text-align:center; margin-top: 20px;">
                    <button type="submit" class="btn btn-danger"><i class="fas fa-file-pdf"></i> Download PDF</button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>User Name</th>
                            <th>User Email</th>
                            <th>Order Date</th>
                            <th>Status</th>
                            <th>Total Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders_query->num_rows > 0): ?>
                            <?php while ($order = $orders_query->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $order['order_id']; ?></td>
                                    <td><?php echo $order['user_name']; ?></td>
                                    <td><?php echo $order['user_email']; ?></td>
                                    <td><?php echo $order['created_at']; ?></td>
                                    <td><?php echo $order['status']; ?></td>
                                    <td><?php echo number_format($order['total'], 2); ?> MAD</td>
                                    <td>
                                        <!-- View Order Details -->
                                        <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#orderModal" data-order-id="<?php echo $order['order_id']; ?>">View</button>
                                        <!-- Manage Order Status -->
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                            <select name="status" onchange="this.form.submit()" class="form-control form-control-sm">
                                                <option value="Pending" <?php if ($order['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                                                <option value="Completed" <?php if ($order['status'] == 'Completed') echo 'selected'; ?>>Completed</option>
                                                <option value="Cancelled" <?php if ($order['status'] == 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No orders found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderModal" tabindex="-1" role="dialog" aria-labelledby="orderModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderModalLabel">Order Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Order details will be loaded here with AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    $('#sidebarCollapse').on('click', function () {
        $('#sidebar').toggleClass('active');
    });

    $('#orderModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); 
        var orderId = button.data('order-id');
        
        var modal = $(this);
        modal.find('.modal-body').html('<p>Loading...</p>');
        
        $.ajax({
            url: 'view_order.php',
            type: 'GET',
            data: { order_id: orderId },
            success: function (response) {
                modal.find('.modal-body').html(response);
            },
            error: function () {
                modal.find('.modal-body').html('<p>An error has occurred</p>');
            }
        });
    });
</script>
</body>
</html>
