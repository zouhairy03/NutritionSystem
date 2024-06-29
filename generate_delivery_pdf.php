<?php
require 'vendor/autoload.php';
require 'config/db.php';
session_start();

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch deliveries
$search_query = isset($_GET['search_query']) ? $_GET['search_query'] : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';

$search_sql = $search_query ? "AND (deliveries.delivery_id LIKE '%$search_query%' OR orders.order_id LIKE '%$search_query%')" : "";
$status_sql = $status_filter ? "AND deliveries.status = '$status_filter'" : "";

$userDeliveries = $conn->prepare("
    SELECT deliveries.*, orders.order_id
    FROM deliveries
    LEFT JOIN orders ON deliveries.order_id = orders.order_id
    WHERE orders.user_id = ? $search_sql $status_sql
    ORDER BY deliveries.created_at DESC
");
$userDeliveries->bind_param('i', $user_id);
$userDeliveries->execute();
$userDeliveriesResult = $userDeliveries->get_result();

// Path to the logo
$logoPath = 'Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png';
$logoData = base64_encode(file_get_contents($logoPath));

// Create HTML content for the PDF
$html = '<h1 style="text-align:center;">Delivery Report</h1>';
$html .= '<div style="text-align:center;"><img src="data:image/png;base64,' . $logoData . '" style="width:150px;"></div>';
$html .= '<table border="1" cellspacing="0" cellpadding="5" style="width:100%; margin-top:20px;">';
$html .= '<thead><tr><th>Delivery ID</th><th>Order ID</th><th>Status</th><th>Delivered At</th><th>Date Added</th></tr></thead>';
$html .= '<tbody>';

while ($row = $userDeliveriesResult->fetch_assoc()) {
    $html .= '<tr>';
    $html .= '<td>' . htmlspecialchars($row['delivery_id']) . '</td>';
    $html .= '<td>' . htmlspecialchars($row['order_id']) . '</td>';
    $html .= '<td>' . htmlspecialchars($row['status']) . '</td>';
    $html .= '<td>' . htmlspecialchars($row['delivered_at']) . '</td>';
    $html .= '<td>' . htmlspecialchars($row['created_at']) . '</td>';
    $html .= '</tr>';
}

$html .= '</tbody></table>';

// Setup Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream('delivery_report.pdf');
?>
