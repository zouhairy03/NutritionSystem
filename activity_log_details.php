<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require 'config/db.php';

if (!isset($_GET['id'])) {
    header("Location: activity_logs.php");
    exit();
}

$log_id = intval($_GET['id']);

// Fetch activity log details
$stmt = $conn->prepare("SELECT * FROM activity_logs WHERE id = ?");
$stmt->bind_param("i", $log_id);
$stmt->execute();
$result = $stmt->get_result();
$log = $result->fetch_assoc();

if (!$log) {
    header("Location: activity_logs.php");
    exit();
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log Details</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 50px;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .logo {
            display: block;
            margin: 0 auto 20px;
            height: 100px;
        }
    </style>
</head>
<body>
    <div class="container text-center">
        <img src="Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png" alt="NutriDaily Logo" class="logo" style="width: 40%; height:190px;">
        <h2>Activity Log Details</h2>
        <table class="table table-bordered">
            <tr>
                <th>Log ID</th>
                <td><?php echo htmlspecialchars($log['id']); ?></td>
            </tr>
            <tr>
                <th>User ID</th>
                <td><?php echo htmlspecialchars($log['user_id']); ?></td>
            </tr>
            <tr>
                <th>Action</th>
                <td><?php echo htmlspecialchars($log['action']); ?></td>
            </tr>
            <tr>
                <th>Timestamp</th>
                <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
            </tr>

        </table>
        <a href="activity_logs.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Activity Logs</a>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
