<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}
require 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $meal_id = $_POST['meal'];
    $quantity = $_POST['quantity'];
    $coupon_code = $_POST['coupon_code'];
    $payment_method = 'Cash on Delivery';
    $status = 'Processing'; // Default status for new orders

    // Fetch the price of the selected meal
    $meal_query = $conn->prepare("SELECT price FROM meals WHERE meal_id = ?");
    $meal_query->bind_param("i", $meal_id);
    $meal_query->execute();
    $meal_result = $meal_query->get_result();
    $meal_data = $meal_result->fetch_assoc();

    if (!$meal_data) {
        die("Error: Meal not found.");
    }

    $meal_price = $meal_data['price'];

    // Apply coupon code discount if applicable
    $discount = 0;
    if (!empty($coupon_code)) {
        $coupon_query = $conn->prepare("SELECT discount_percentage FROM coupons WHERE code = ?");
        $coupon_query->bind_param("s", $coupon_code);
        $coupon_query->execute();
        $coupon_result = $coupon_query->get_result();
        if ($coupon_result->num_rows > 0) {
            $coupon_data = $coupon_result->fetch_assoc();
            $discount = $coupon_data['discount_percentage'];
        }
    }

    // Calculate total price
    $total = ($meal_price * $quantity) - $discount;
    if ($total < 0) {
        $total = 0;
    }

    // Prepare and execute the SQL statement to insert the order
    $stmt = $conn->prepare("INSERT INTO orders (user_id, meal_id, quantity, total, payment_method, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iiisss", $user_id, $meal_id, $quantity, $total, $payment_method, $status);

    if ($stmt->execute()) {
        header("Location: user_orders.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
?>
