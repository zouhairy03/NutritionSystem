<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: delivery_login.php");
    exit();
}
require 'config/db.php';

if (!isset($_GET['order_id'])) {
    die('Order ID is required.');
}

$order_id = $_GET['order_id'];

// Check if the order exists
$order_query = $conn->query("SELECT * FROM orders WHERE order_id = $order_id");
if ($order_query === false) {
    die('Error fetching order: ' . $conn->error);
}
$order = $order_query->fetch_assoc();
if (!$order) {
    die('Order not found.');
}

// Check if the user exists
$user_query = $conn->query("SELECT * FROM users WHERE user_id = " . $order['user_id']);
if ($user_query === false) {
    die('Error fetching user: ' . $conn->error);
}
$user = $user_query->fetch_assoc();
if (!$user) {
    die('User not found.');
}

// Check if the meal exists
$meal_query = $conn->query("SELECT * FROM meals WHERE meal_id = " . $order['meal_id']);
if ($meal_query === false) {
    die('Error fetching meal: ' . $conn->error);
}
$meal = $meal_query->fetch_assoc();
if (!$meal) {
    die('Meal not found.');
}

// Check if the address exists
$address = null;
if (!empty($order['address_id'])) {
    $address_query = $conn->query("SELECT * FROM addresses WHERE address_id = " . $order['address_id']);
    if ($address_query === false) {
        die('Error fetching address: ' . $conn->error);
    }
    $address = $address_query->fetch_assoc();
    if (!$address) {
        die('Address not found.');
    }
}

// If all queries passed, display the order details
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <title>Order Details</title>
</head>
<body>
<div class="container">
    <h2>Order Details</h2>
    <table class="table table-bordered">
        <tr>
            <th>Order ID</th>
            <td><?php echo isset($order['order_id']) ? $order['order_id'] : 'N/A'; ?></td>
        </tr>
        <tr>
            <th>User Name</th>
            <td><?php echo isset($user['name']) ? $user['name'] : 'N/A'; ?></td>
        </tr>
        <tr>
            <th>User Email</th>
            <td><?php echo isset($user['email']) ? $user['email'] : 'N/A'; ?></td>
        </tr>
        <tr>
            <th>Meal</th>
            <td><?php echo isset($meal['name']) ? $meal['name'] : 'N/A'; ?></td>
        </tr>
        <tr>
            <th>Quantity</th>
            <td><?php echo isset($order['quantity']) ? $order['quantity'] : 'N/A'; ?></td>
        </tr>
        <tr>
            <th>Address</th>
            <td>
                <?php
                if ($address) {
                    echo $address['street'] . ', ' . $address['city'] . ', ' . $address['state'] . ', ' . $address['zip_code'] . ', ' . $address['country'];
                } else {
                    echo 'Address information is not available';
                }
                ?>
            </td>
        </tr>
        <tr>
            <th>Status</th>
            <td><?php echo isset($order['status']) ? $order['status'] : 'N/A'; ?></td>
        </tr>
        <tr>
            <th>Total Amount</th>
            <td><?php echo isset($order['total']) ? $order['total'] : 'N/A'; ?></td>
        </tr>
        <tr>
            <th>Payment Method</th>
            <td><?php echo isset($order['payment_method']) ? $order['payment_method'] : 'N/A'; ?></td>
        </tr>
        <tr>
            <th>Discount Amount</th>
            <td><?php echo isset($order['discount_amount']) ? $order['discount_amount'] : 'N/A'; ?></td>
        </tr>
        <tr>
            <th>Cost</th>
            <td><?php echo isset($order['cost']) ? $order['cost'] : 'N/A'; ?></td>
        </tr>
        <tr>
            <th>Created At</th>
            <td><?php echo isset($order['created_at']) ? $order['created_at'] : 'N/A'; ?></td>
        </tr>
        <tr>
            <th>Updated At</th>
            <td><?php echo isset($order['updated_at']) ? $order['updated_at'] : 'N/A'; ?></td>
        </tr>
    </table>
</div>
</body>
</html>
