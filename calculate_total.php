<?php
require 'config/db.php';

$meal_price = $_POST['meal_price'];
$quantity = $_POST['quantity'];
$coupon_code = $_POST['coupon_code'];

$discount = 0;
if (!empty($coupon_code)) {
    $couponQuery = $conn->query("SELECT discount_percentage FROM coupons WHERE code='$coupon_code'");
    if ($couponQuery->num_rows > 0) {
        $coupon = $couponQuery->fetch_assoc();
        $discount = $coupon['discount_percentage'];
    }
}

$total = $meal_price * $quantity * ((100 - $discount) / 100);
echo json_encode(['total' => $total]);
?>
