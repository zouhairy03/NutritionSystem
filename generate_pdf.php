<?php
require_once('vendor/autoload.php'); // Adjust the path if necessary
require 'config/db.php';

session_start();
if (!isset($_SESSION['id'])) {
    header("Location: delivery_login.php");
    exit();
}

// Fetch orders assigned to this delivery personnel with status 'Pending' or 'Completed'
$delivery_id = $_SESSION['id'];
$sql = "SELECT DISTINCT o.order_id, o.user_id, o.meal_id, o.quantity, o.address_id, o.coupon_id, o.status, o.total, o.created_at, o.updated_at, o.payment_method, o.discount_amount, o.cost, u.name AS user_name, u.email AS user_email 
        FROM orders o
        JOIN deliveries d ON o.order_id = d.order_id
        JOIN users u ON o.user_id = u.user_id
        WHERE d.delivery_person_id = $delivery_id AND (o.status = 'Pending' OR o.status = 'Completed')";
$orders_query = $conn->query($sql);
if ($orders_query === false) {
    die('Error fetching orders: ' . $conn->error);
}

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('NutriDaily');
$pdf->SetTitle('Delivery Orders');
$pdf->SetSubject('Delivery Orders');
$pdf->SetKeywords('TCPDF, PDF, orders, delivery, nutriDaily');

// Add a page
$pdf->AddPage();

// Set logo at the top
$pdf->Image('Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png', 15, 10, 60, '', '', '', 'T', false, 300, '', false, false, 0, false, false, false);

// Set title below the logo
$pdf->Ln(20); // Adjust the value as necessary to create space between the logo and title
$pdf->SetFont('helvetica', 'B', 20);
$pdf->Cell(0, 15, 'Delivery Orders', 0, 1, 'C', 0, '', 0, false, 'T', 'M');

// Line break
$pdf->Ln(10);

// Set table header
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(20, 10, 'Order ID', 1, 0, 'C');
$pdf->Cell(30, 10, 'User Name', 1, 0, 'C');
$pdf->Cell(50, 10, 'User Email', 1, 0, 'C');
$pdf->Cell(30, 10, 'Order Date', 1, 0, 'C');
$pdf->Cell(30, 10, 'Status', 1, 0, 'C');
$pdf->Cell(30, 10, 'Total', 1, 1, 'C');

// Set table content
$pdf->SetFont('helvetica', '', 10);
while ($order = $orders_query->fetch_assoc()) {
    $pdf->Cell(20, 10, $order['order_id'], 1, 0, 'C');
    $pdf->Cell(30, 10, $order['user_name'], 1, 0, 'C');
    $pdf->Cell(50, 10, $order['user_email'], 1, 0, 'C');
    $pdf->Cell(30, 10, $order['created_at'], 1, 0, 'C');
    $pdf->Cell(30, 10, $order['status'], 1, 0, 'C');
    $pdf->Cell(30, 10, $order['total'], 1, 1, 'C');
}

// Output PDF
$pdf->Output('delivery_orders.pdf', 'D');
?>
