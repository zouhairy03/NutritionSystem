<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $scheduled_at = $_POST['scheduled_at'];
    $delivered_at = $_POST['delivered_at'];
    $delivery_person_id = $_POST['delivery_person_id'];

    $stmt = $conn->prepare("INSERT INTO deliveries (order_id, status, scheduled_at, delivered_at, delivery_person_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssi", $order_id, $status, $scheduled_at, $delivered_at, $delivery_person_id);
    $stmt->execute();
    $stmt->close();

    header("Location: deliveries.php");
    exit();
}

// Fetch orders to populate the order_id dropdown
$orders = $conn->query("SELECT order_id FROM orders");

// Fetch delivery personnel to populate the delivery_person_id dropdown
$delivery_personnel = $conn->query("SELECT id, name FROM delivery_personnel");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Delivery</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<div class="container">
    <h2 class="mt-4">Add Delivery</h2>
    <form method="POST">
        <div class="form-group">
            <label for="order_id">Order ID</label>
            <select id="order_id" name="order_id" class="form-control" required>
                <?php while ($order = $orders->fetch_assoc()): ?>
                    <option value="<?php echo $order['order_id']; ?>"><?php echo $order['order_id']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status" class="form-control" required>
                <option value="Pending">Pending</option>
                <option value="Completed">Completed</option>
            </select>
        </div>
        <div class="form-group">
            <label for="scheduled_at">Scheduled At</label>
            <input type="datetime-local" id="scheduled_at" name="scheduled_at" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="delivered_at">Delivered At</label>
            <input type="datetime-local" id="delivered_at" name="delivered_at" class="form-control">
        </div>
        <div class="form-group">
            <label for="delivery_person_id">Delivery Person</label>
            <select id="delivery_person_id" name="delivery_person_id" class="form-control" required>
                <?php while ($person = $delivery_personnel->fetch_assoc()): ?>
                    <option value="<?php echo $person['id']; ?>"><?php echo $person['name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Add Delivery</button>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
