
<?php
session_start();
include 'config/db.php'; // Database connection file

// Check if the user is logged in, otherwise redirect to the login page
if (!isset($_SESSION['user'])) {
    header('Location: user_login.php');
    exit();
}

// Retrieve user information from the session
$user = $_SESSION['user'];
$user_id = $user['user_id'];

// Get order ID from query parameter
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Fetch order details
$order_query = "
    SELECT o.order_id, o.quantity, o.status, o.total, o.payment_method, 
           m.meal_id, m.name AS meal_name, m.price AS meal_price, 
           c.code AS coupon_code
    FROM orders o
    LEFT JOIN meals m ON o.meal_id = m.meal_id
    LEFT JOIN coupons c ON o.coupon_id = c.coupon_id
    WHERE o.order_id = ? AND o.user_id = ?
";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order_result = $stmt->get_result();
$order = $order_result->fetch_assoc();
$stmt->close();

// Redirect if the order does not exist or does not belong to the user
if (!$order) {
    header('Location: user_orders.php');
    exit();
}

// Fetch all meals
$meals_query = "SELECT meal_id, name, price FROM meals";
$stmt = $conn->prepare($meals_query);
$stmt->execute();
$meals_result = $stmt->get_result();
$stmt->close();

// Handle order update submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_order'])) {
    $meal_id = $_POST['meal_id'];
    $quantity = $_POST['quantity'];
    $status = $_POST['status'];
    $coupon_code = $_POST['coupon_code'];
    $payment_method = 'Cash on Delivery';

    // Calculate the total
    $meal_price_query = "SELECT price FROM meals WHERE meal_id = ?";
    $stmt = $conn->prepare($meal_price_query);
    $stmt->bind_param("i", $meal_id);
    $stmt->execute();
    $stmt->bind_result($price);
    $stmt->fetch();
    $stmt->close();

    $total = $price * $quantity;

    // Apply coupon discount if coupon code exists
    $coupon_id = NULL;
    if (!empty($coupon_code)) {
        $coupon_query = "SELECT coupon_id, discount_percentage FROM coupons WHERE code = ?";
        $stmt = $conn->prepare($coupon_query);
        $stmt->bind_param("s", $coupon_code);
        $stmt->execute();
        $stmt->bind_result($coupon_id, $discount);
        if ($stmt->fetch()) {
            $total -= $total * ($discount / 100);
        }
        $stmt->close();
    }

    // Update the order in the database
    $update_query = "UPDATE orders 
                     SET meal_id = ?, quantity = ?, status = ?, coupon_id = ?, total = ?, payment_method = ?, updated_at = NOW() 
                     WHERE order_id = ? AND user_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("iissdsii", $meal_id, $quantity, $status, $coupon_id, $total, $payment_method, $order_id, $user_id);
    $stmt->execute();
    $stmt->close();

    header('Location: user_orders.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Order</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<div class="container mt-5">
    <h1>Edit Order</h1>
    <form method="POST" action="edit_order.php?order_id=<?php echo $order_id; ?>">
        <div class="form-group">
            <label for="meal_id">Meal</label>
            <select name="meal_id" id="meal_id" class="form-control" required>
                <option value="">Select Meal</option>
                <?php
                // Fetch meals again for the dropdown
                if ($meals_result->num_rows > 0) {
                    while ($meal = $meals_result->fetch_assoc()) {
                        echo "<option value='" . $meal['meal_id'] . "' data-price='" . $meal['price'] . "' " . ($meal['meal_id'] == $order['meal_id'] ? 'selected' : '') . ">" . htmlspecialchars($meal['name']) . "</option>";
                    }
                } else {
                    echo "<option value=''>No meals available</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="quantity">Quantity</label>
            <input type="number" name="quantity" id="quantity" class="form-control" value="<?php echo htmlspecialchars($order['quantity']); ?>" required onchange="calculateTotal()">
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-control" required>
                <option value="Pending" <?php if ($order['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                <option value="Completed" <?php if ($order['status'] == 'Completed') echo 'selected'; ?>>Completed</option>
                <!-- Add more statuses as needed -->
            </select>
        </div>
        <div class="form-group">
            <label for="coupon_code">Coupon Code</label>
            <input type="text" name="coupon_code" id="coupon_code" class="form-control" value="<?php echo htmlspecialchars($order['coupon_code'] ?? ''); ?>" onchange="calculateTotal()">
        </div>
        <div class="form-group">
            <label for="total">Total</label>
            <input type="number" name="total" id="total" class="form-control" value="<?php echo htmlspecialchars($order['total']); ?>" required readonly>
        </div>
        <input type="hidden" name="payment_method" value="Cash on Delivery">
        <button type="submit" name="update_order" class="btn btn-primary"><i class="fas fa-save"></i> Update Order</button>
        <a href="user_orders.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </form>
</div>

<script>
    function calculateTotal() {
        var mealSelect = document.getElementById('meal_id');
        var quantity = document.getElementById('quantity').value;
        var couponCode = document.getElementById('coupon_code').value;

        if (!mealSelect || !quantity) return;

        var mealPrice = mealSelect.options[mealSelect.selectedIndex].getAttribute('data-price');

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'calculate_total.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                document.getElementById('total').value = xhr.responseText;
            }
        };
        xhr.send('meal_price=' + mealPrice + '&quantity=' + quantity + '&coupon_code=' + couponCode);
    }
</script>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
