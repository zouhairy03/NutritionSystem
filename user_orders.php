<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}
require 'config/db.php';

// Fetch user details
$user_id = $_SESSION['user_id'];
$user_query = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
$user_query->bind_param('i', $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
if ($user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
    $user_name = $user['name'];
} else {
    $user_name = "User";
}

// Fetch user orders with JOINs
$orders_query = $conn->prepare("SELECT o.order_id, o.created_at, o.status, m.name as meal, o.quantity, o.total, o.payment_method 
                                FROM orders o 
                                JOIN meals m ON o.meal_id = m.meal_id 
                                WHERE o.user_id = ? 
                                ORDER BY o.created_at DESC");
$orders_query->bind_param('i', $user_id);
$orders_query->execute();
$orders_result = $orders_query->get_result();
$orders = [];
while ($row = $orders_result->fetch_assoc()) {
    $orders[] = $row;
}

// Fetch meal options
$meal_query = $conn->prepare("SELECT meal_id, name, price FROM meals");
$meal_query->execute();
$meal_result = $meal_query->get_result();

// Fetch user addresses
$address_query = $conn->prepare("SELECT address_id, street, city, state, zip_code, country FROM addresses WHERE user_id = ?");
$address_query->bind_param('i', $user_id);
$address_query->execute();
$address_result = $address_query->get_result();

// Handle new order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $meal_id = $_POST['meal_id'];
    $quantity = $_POST['quantity'];
    $address_id = $_POST['address_id'];
    $coupon_code = $_POST['coupon_code'];
    $total = 0;
    $discount = 0;

    // Fetch meal price
    $meal_query = $conn->prepare("SELECT price FROM meals WHERE meal_id = ?");
    $meal_query->bind_param('i', $meal_id);
    $meal_query->execute();
    $meal_result = $meal_query->get_result();
    if ($meal_result->num_rows > 0) {
        $meal = $meal_result->fetch_assoc();
        $total = $meal['price'] * $quantity;
    }

    // Apply coupon discount
    if (!empty($coupon_code)) {
        $coupon_query = $conn->prepare("SELECT discount_percentage FROM coupons WHERE code = ? AND expiry_date >= CURDATE()");
        $coupon_query->bind_param('s', $coupon_code);
        $coupon_query->execute();
        $coupon_result = $coupon_query->get_result();
        if ($coupon_result->num_rows > 0) {
            $coupon = $coupon_result->fetch_assoc();
            $discount = ($total * $coupon['discount_percentage']) / 100;
            $total -= $discount;
        }
    }

    $order_query = $conn->prepare("INSERT INTO orders (user_id, meal_id, quantity, address_id, total, status, payment_method, discount_amount) 
                                   VALUES (?, ?, ?, ?, ?, 'Pending', 'Cash on Delivery', ?)");
    $order_query->bind_param('iiisdi', $user_id, $meal_id, $quantity, $address_id, $total, $discount);
    $order_query->execute();
    header("Location: user_orders.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Orders</title>
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
        .card .card-header {
            background-color: #809B53;
            color: #fff;
            border-bottom: none;
            border-radius: 15px 15px 0 0;
            font-size: 1.2em;
            padding: 15px;
        }
        .table thead th {
            border-top: none;
        }
        .btn-view, .btn-cancel, .btn-invoice, .btn-new-order {
            margin-right: 10px;
        }
        .navbar-brand, .welcome-message h2 {
            color: #343a40;
        }
        .modal-content {
            border-radius: 15px;
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
            <li><a href="user_settings.php"><i class="fas fa-cogs"></i> Settings</a></li>
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

        <div class="container-fluid">
            <div class="welcome-message">
                <!-- <h2>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h2> -->
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-shopping-cart"></i> My Orders
                    <button class="btn btn-primary float-right btn-new-order" data-toggle="modal" data-target="#newOrderModal"><i class="fas fa-plus"></i> Add New Order</button>
                </div>
                <div class="card-body">
                    <?php if (count($orders) > 0): ?>
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Order Date</th>
                                    <th>Status</th>
                                    <th>Meal</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th>Payment Method</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                                        <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                                        <td><?php echo htmlspecialchars($order['status']); ?></td>
                                        <td><?php echo htmlspecialchars($order['meal']); ?></td>
                                        <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                                        <td><?php echo htmlspecialchars(number_format($order['total'], 2)); ?></td>
                                        <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                                        <td style="display: flex;">
                                            <button class="btn btn-primary btn-view" data-toggle="modal" data-target="#viewOrderModal<?php echo $order['order_id']; ?>"><i class="fas fa-eye"></i> View Deails</button>
                                            <button class="btn btn-danger btn-cancel" data-toggle="modal" data-target="#cancelOrderModal<?php echo $order['order_id']; ?>"><i class="fas fa-times"></i> Cancel Order</button>
                                            <!-- <button class="btn btn-secondary btn-invoice" data-toggle="modal" data-target="#downloadInvoiceModal<?php echo $order['order_id']; ?>"><i class="fas fa-file-download"></i> Download Invoice</button> -->
                                        </td>
                                    </tr>

                                    <!-- View Order Modal -->
                                    <div class="modal fade" id="viewOrderModal<?php echo $order['order_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="viewOrderModalLabel<?php echo $order['order_id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="viewOrderModalLabel<?php echo $order['order_id']; ?>">View Order Details</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order['order_id']); ?></p>
                                                    <p><strong>Order Date:</strong> <?php echo htmlspecialchars($order['created_at']); ?></p>
                                                    <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
                                                    <p><strong>Meal:</strong> <?php echo htmlspecialchars($order['meal']); ?></p>
                                                    <p><strong>Quantity:</strong> <?php echo htmlspecialchars($order['quantity']); ?></p>
                                                    <p><strong>Total:</strong> <?php echo htmlspecialchars(number_format($order['total'], 2)); ?></p>
                                                    <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Cancel Order Modal -->
                                    <div class="modal fade" id="cancelOrderModal<?php echo $order['order_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="cancelOrderModalLabel<?php echo $order['order_id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="cancelOrderModalLabel<?php echo $order['order_id']; ?>">Cancel Order</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to cancel this order?</p>
                                                    <form method="POST" action="cancel_order.php">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                        <button type="submit" class="btn btn-danger">Yes, Cancel Order</button>
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">No, Keep Order</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Download Invoice Modal -->
                                    <div class="modal fade" id="downloadInvoiceModal<?php echo $order['order_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="downloadInvoiceModalLabel<?php echo $order['order_id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="downloadInvoiceModalLabel<?php echo $order['order_id']; ?>">Download Invoice</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <!-- <div class="modal-body">
                                                    <form method="POST" action="download_invoice.php">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                        <button type="submit" class="btn btn-primary">Download Invoice</button>
                                                    </form>
                                                </div> -->
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>You have no orders yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for adding new order -->
<div class="modal fade" id="newOrderModal" tabindex="-1" role="dialog" aria-labelledby="newOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newOrderModalLabel">Add New Order</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="meal">Meal</label>
                        <select class="form-control" id="meal" name="meal_id" required>
                            <?php while ($meal = $meal_result->fetch_assoc()): ?>
                                <option value="<?php echo $meal['meal_id']; ?>" data-price="<?php echo $meal['price']; ?>"><?php echo htmlspecialchars($meal['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="price">Price</label>
                        <input type="text" class="form-control" id="price" name="price" readonly>
                    </div>
                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" value="1" required>
                    </div>
                    <div class="form-group">
                        <label for="address">Delivery Address</label>
                        <select class="form-control" id="address" name="address_id" required>
                            <?php while ($address = $address_result->fetch_assoc()): ?>
                                <option value="<?php echo $address['address_id']; ?>"><?php echo htmlspecialchars($address['street']) . ', ' . htmlspecialchars($address['city']) . ', ' . htmlspecialchars($address['state']) . ' ' . htmlspecialchars($address['zip_code']) . ', ' . htmlspecialchars($address['country']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="coupon_code">Coupon Code</label>
                        <input type="text" class="form-control" id="coupon_code" name="coupon_code">
                    </div>
                    <div class="form-group">
                        <label for="total">Total (MAD)</label>
                        <input type="text" class="form-control" id="total" name="total" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Order</button>
                </div>
            </form>
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

        $('#meal').on('change', function () {
            var price = $(this).find(':selected').data('price');
            $('#price').val(price);
            calculateTotal();
        });

        $('#quantity, #coupon_code').on('input', function () {
            calculateTotal();
        });

        function calculateTotal() {
            var price = parseFloat($('#price').val());
            var quantity = parseInt($('#quantity').val());
            var couponCode = $('#coupon_code').val();
            var total = price * quantity;

            if (couponCode) {
                // Perform AJAX request to validate coupon and apply discount
                $.ajax({
                    url: 'validate_coupon.php',
                    method: 'POST',
                    data: { coupon_code: couponCode, total: total },
                    dataType: 'json',
                    success: function (response) {
                        var discount = parseFloat(response.discount);
                        total -= discount;
                        $('#total').val(total.toFixed(2));
                    },
                    error: function () {
                        alert('Failed to apply coupon code.');
                        $('#total').val(total.toFixed(2));
                    }
                });
            } else {
                $('#total').val(total.toFixed(2));
            }
        }

        $('.btn-view').on('click', function () {
            const orderId = $(this).data('order-id');
            // Add your code to handle view order details
            // alert('View order details for Order ID: ' + orderId);
        });

        $('.btn-cancel').on('click', function () {
            const orderId = $(this).data('order-id');
            // Add your code to handle cancel order
            // alert('Cancel order for Order ID: ' + orderId);
        });

        $('.btn-invoice').on('click', function () {
            const orderId = $(this).data('order-id');
            // Add your code to handle download invoice
            alert('Download invoice for Order ID: ' + orderId);
        });
    });
</script>

</body>
</html>
