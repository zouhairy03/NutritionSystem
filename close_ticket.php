<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
require 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $updateQuery = $conn->prepare("UPDATE delivery_support_tickets SET status = 'Closed' WHERE id = ?");
    $updateQuery->bind_param('i', $id);
    if ($updateQuery->execute()) {
        echo 'Success';
    } else {
        echo 'Error';
    }
}
?>
