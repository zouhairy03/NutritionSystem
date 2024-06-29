<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: delivery_login.php");
    exit();
}
require 'config/db.php';
require 'vendor/autoload.php';

if (isset($_GET['delivery_id'])) {
    $delivery_id = $_GET['delivery_id'];

    // Fetch delivery details
    $invoiceQuery = "SELECT d.*, o.created_at AS order_date, a.street, a.city, a.state, a.zip_code, a.country,
                     u.name AS user_name, u.email AS user_email, u.phone AS user_phone,
                     m.meal_id, m.name AS meal_name, c.name AS category_name
                     FROM deliveries d 
                     JOIN orders o ON d.order_id = o.order_id 
                     JOIN addresses a ON o.address_id = a.address_id 
                     JOIN users u ON o.user_id = u.user_id
                     JOIN meals m ON o.meal_id = m.meal_id
                     JOIN categories c ON m.category_id = c.category_id
                     WHERE d.delivery_id = ?";
    $stmt = $conn->prepare($invoiceQuery);
    $stmt->bind_param('i', $delivery_id);
    $stmt->execute();
    $invoice = $stmt->get_result()->fetch_assoc();

    if ($invoice) {
        $invoiceDetails = [
            'Delivery ID' => $invoice['delivery_id'],
            'Order Date' => $invoice['order_date'],
            'Status' => $invoice['status'],
            'Address' => $invoice['street'] . ', ' . $invoice['city'] . ', ' . $invoice['state'] . ', ' . $invoice['zip_code'] . ', ' . $invoice['country'],
            'Customer Name' => $invoice['user_name'],
            'Customer Email' => $invoice['user_email'],
            'Customer Phone' => $invoice['user_phone'],
            'Meal ID' => $invoice['meal_id'],
            'Meal Name' => $invoice['meal_name'],
            'Meal Category' => $invoice['category_name']
        ];

        // Generate PDF
        $tcpdf = new TCPDF();

        // Set document information
        $tcpdf->SetCreator(PDF_CREATOR);
        $tcpdf->SetAuthor('NutriDaily');
        $tcpdf->SetTitle('Invoice');
        $tcpdf->SetSubject('Invoice');

        // Add a page
        $tcpdf->AddPage();

        // Add logo
        $logo = 'Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png'; // Replace with the correct path to your logo file
        $tcpdf->Image($logo, 10, 10, 50, 20, 'PNG');

        // Set content
        $tcpdf->Ln(30); // Add space after the logo
        $html = '<h1>Invoice</h1><table border="1" cellpadding="10">';
        foreach ($invoiceDetails as $key => $value) {
            $html .= "<tr><th>{$key}</th><td>{$value}</td></tr>";
        }
        $html .= '</table>';

        $tcpdf->writeHTML($html, true, false, true, false, '');

        // Close and output PDF document
        $tcpdf->Output("Invoice_Delivery_{$delivery_id}.pdf", 'D');
    } else {
        echo "No invoice found for this delivery.";
    }
} else {
    echo "Invalid request.";
}
?>
