<?php
require 'vendor/autoload.php';
require 'config/db.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    die('Order ID not provided');
}

$order_id = intval($_GET['order_id']);

// Fetch order details
$order_query = $conn->prepare("
    SELECT 
        o.order_id, 
        o.created_at, 
        o.status, 
        m.name as meal, 
        COALESCE(cat.name, 'N/A') as category, 
        m.price, 
        o.quantity, 
        o.total, 
        o.payment_method, 
        COALESCE(c.code, 'N/A') as coupon_code, 
        u.name as user_name, 
        u.email as user_email, 
        COALESCE(u.phone, 'N/A') as phone, 
        COALESCE(a.street, 'N/A') as street, 
        COALESCE(a.city, 'N/A') as city, 
        COALESCE(a.state, 'N/A') as state, 
        COALESCE(a.zip_code, 'N/A') as zip_code, 
        COALESCE(a.country, 'N/A') as country 
    FROM orders o 
    JOIN meals m ON o.meal_id = m.meal_id 
    LEFT JOIN coupons c ON o.coupon_id = c.coupon_id 
    JOIN users u ON o.user_id = u.user_id 
    LEFT JOIN addresses a ON o.address_id = a.address_id 
    LEFT JOIN categories cat ON m.category_id = cat.category_id 
    WHERE o.order_id = ?
");
$order_query->bind_param('i', $order_id);
$order_query->execute();
$order_result = $order_query->get_result();

if ($order_result->num_rows == 0) {
    die('Order not found');
}

$order = $order_result->fetch_assoc();

// Check if the image file exists
$imagePath = 'Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png';
if (!file_exists($imagePath)) {
    die('Logo image not found');
}

// Embed image as base64
$imageData = base64_encode(file_get_contents($imagePath));
$logo_src = 'data:image/png;base64,' . $imageData;

// Configure Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Generate PDF
$html = '<html><body>';
$html .= '<div style="text-align: center;"><img src="' . $logo_src . '" alt="NutriDaily Logo" style="width: 200px; margin-bottom: 20px;"></div>';
$html .= '<h1 style="text-align: center;">Invoice</h1>';
$html .= '<table style="width: 100%; border-collapse: collapse; border: 1px solid #000;">';
$html .= '<tr><th style="border: 1px solid #000; padding: 8px;">Order ID</th><td style="border: 1px solid #000; padding: 8px;">' . htmlspecialchars($order['order_id']) . '</td></tr>';
$html .= '<tr><th style="border: 1px solid #000; padding: 8px;">Order Date</th><td style="border: 1px solid #000; padding: 8px;">' . htmlspecialchars($order['created_at']) . '</td></tr>';
$html .= '<tr><th style="border: 1px solid #000; padding: 8px;">Status</th><td style="border: 1px solid #000; padding: 8px;">' . htmlspecialchars($order['status']) . '</td></tr>';
$html .= '<tr><th style="border: 1px solid #000; padding: 8px;">Meal</th><td style="border: 1px solid #000; padding: 8px;">' . htmlspecialchars($order['meal']) . '</td></tr>';
$html .= '<tr><th style="border: 1px solid #000; padding: 8px;">Category</th><td style="border: 1px solid #000; padding: 8px;">' . htmlspecialchars($order['category']) . '</td></tr>';
$html .= '<tr><th style="border: 1px solid #000; padding: 8px;">Quantity</th><td style="border: 1px solid #000; padding: 8px;">' . htmlspecialchars($order['quantity']) . '</td></tr>';
$html .= '<tr><th style="border: 1px solid #000; padding: 8px;">Total</th><td style="border: 1px solid #000; padding: 8px;">' . htmlspecialchars(number_format($order['total'], 2)) . ' MAD</td></tr>';
$html .= '<tr><th style="border: 1px solid #000; padding: 8px;">Coupon Code</th><td style="border: 1px solid #000; padding: 8px;">' . htmlspecialchars($order['coupon_code']) . '</td></tr>';
$html .= '<tr><th style="border: 1px solid #000; padding: 8px;">Payment Method</th><td style="border: 1px solid #000; padding: 8px;">' . htmlspecialchars($order['payment_method']) . '</td></tr>';
$html .= '</table>';
$html .= '<h2 style="text-align: center;">User Details</h2>';
$html .= '<table style="width: 100%; border-collapse: collapse; border: 1px solid #000;">';
$html .= '<tr><th style="border: 1px solid #000; padding: 8px;">Name</th><td style="border: 1px solid #000; padding: 8px;">' . htmlspecialchars($order['user_name']) . '</td></tr>';
$html .= '<tr><th style="border: 1px solid #000; padding: 8px;">Email</th><td style="border: 1px solid #000; padding: 8px;">' . htmlspecialchars($order['user_email']) . '</td></tr>';
$html .= '<tr><th style="border: 1px solid #000; padding: 8px;">Phone</th><td style="border: 1px solid #000; padding: 8px;">' . htmlspecialchars($order['phone']) . '</td></tr>';
$html .= '<tr><th style="border: 1px solid #000; padding: 8px;">Address</th><td style="border: 1px solid #000; padding: 8px;">' . htmlspecialchars($order['street']) . ', ' . htmlspecialchars($order['city']) . ', ' . htmlspecialchars($order['state']) . ', ' . htmlspecialchars($order['zip_code']) . ', ' . htmlspecialchars($order['country']) . '</td></tr>';
$html .= '</table>';
$html .= '<p style="text-align: center; margin-top: 20px;">Thank you for your purchase, ' . htmlspecialchars($order['user_name']) . '!</p>';
$html .= '</body></html>';

try {
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream('invoice_' . $order['order_id'] . '.pdf', ['Attachment' => 1]);
} catch (Exception $e) {
    echo 'Error generating PDF: ' . $e->getMessage();
}
?>
