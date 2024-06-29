<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
require 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $delivery_person_id = $_POST['delivery_person_id'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    $insertQuery = $conn->prepare("INSERT INTO delivery_support_tickets (delivery_person_id, subject, message, created_at) VALUES (?, ?, ?, NOW())");
    $insertQuery->bind_param('iss', $delivery_person_id, $subject, $message);

    if ($insertQuery->execute()) {
        $_SESSION['message'] = "Support ticket created successfully.";
    } else {
        $_SESSION['error'] = "Failed to create support ticket.";
    }

    header("Location: delivery_support_tickets.php");
    exit();
}
?>
