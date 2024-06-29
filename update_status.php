<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: delivery_login.php");
    exit();
}
require 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $delivery_id = $_POST['delivery_id'];
    $status = $_POST['status'];

    // Prepare and execute the update query
    $stmt = $conn->prepare("UPDATE deliveries SET status = ? WHERE delivery_id = ?");
    $stmt->bind_param('si', $status, $delivery_id);

    if ($stmt->execute()) {
        // Redirect back to the deliveries page with a success message
        $_SESSION['message'] = "Delivery status updated successfully.";
        header("Location: delivery_deliveries.php");
    } else {
        // Redirect back to the deliveries page with an error message
        $_SESSION['message'] = "Failed to update delivery status.";
        header("Location: delivery_deliveries.php");
    }
    $stmt->close();
    $conn->close();
} else {
    header("Location: delivery_deliveries.php");
    exit();
}
?>
