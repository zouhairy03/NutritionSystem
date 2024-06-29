<?php
require 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $coupon_code = $_POST['coupon_code'];
    $total = $_POST['total'];

    $response = ['discount' => 0];

    if (!empty($coupon_code)) {
        $coupon_query = $conn->prepare("SELECT discount_percentage FROM coupons WHERE code = ? AND expiry_date >= CURDATE()");
        $coupon_query->bind_param('s', $coupon_code);
        $coupon_query->execute();
        $coupon_result = $coupon_query->get_result();
        if ($coupon_result->num_rows > 0) {
            $coupon = $coupon_result->fetch_assoc();
            $discount = ($total * $coupon['discount_percentage']) / 100;
            $response['discount'] = $discount;
        }
    }

    echo json_encode($response);
}
?>
