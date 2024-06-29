<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}
require 'config/db.php';

// Ensure the request is coming from the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the order ID from the POST data
    if (isset($_POST['order_id'])) {
        $order_id = $_POST['order_id'];
        
        // Fetch the order to ensure it belongs to the logged-in user
        $user_id = $_SESSION['user_id'];
        $order_query = $conn->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
        $order_query->bind_param('ii', $order_id, $user_id);
        $order_query->execute();
        $order_result = $order_query->get_result();
        
        if ($order_result->num_rows > 0) {
            // Order found, proceed with deletion
            $delete_query = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
            $delete_query->bind_param('i', $order_id);
            
            if ($delete_query->execute()) {
                $_SESSION['success_message'] = "Order deleted successfully.";
            } else {
                $_SESSION['error_message'] = "Failed to delete the order. Please try again.";
            }
        } else {
            $_SESSION['error_message'] = "Order not found or you don't have permission to delete this order.";
        }
    } else {
        $_SESSION['error_message'] = "Invalid request.";
    }
} else {
    $_SESSION['error_message'] = "Invalid request method.";
}

// Redirect back to the orders page
header("Location: user_orders.php");
exit();
?>
