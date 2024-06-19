<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require 'config/db.php';

if (isset($_GET['id'])) {
    $delivery_id = $_GET['id'];

    // Delete the delivery record
    $stmt = $conn->prepare("DELETE FROM deliveries WHERE delivery_id = ?");
    $stmt->bind_param("i", $delivery_id);
    $stmt->execute();
    $stmt->close();

    header("Location: deliveries.php");
    exit();
}
?>
