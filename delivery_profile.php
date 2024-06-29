<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: delivery_login.php");
    exit();
}
require 'config/db.php';

$delivery_id = $_SESSION['id'];

// Fetch delivery personnel details
$deliveryQuery = $conn->prepare("SELECT name, email, phone FROM delivery_personnel WHERE id = ?");
$deliveryQuery->bind_param('i', $delivery_id);
$deliveryQuery->execute();
$delivery = $deliveryQuery->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : null;

    if ($password) {
        $updateQuery = $conn->prepare("UPDATE delivery_personnel SET name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
        $updateQuery->bind_param('ssssi', $name, $email, $phone, $password, $delivery_id);
    } else {
        $updateQuery = $conn->prepare("UPDATE delivery_personnel SET name = ?, email = ?, phone = ? WHERE id = ?");
        $updateQuery->bind_param('sssi', $name, $email, $phone, $delivery_id);
    }

    if ($updateQuery->execute()) {
        $_SESSION['message'] = "Profile updated successfully.";
        header("Location: delivery_profile.php");
        exit();
    } else {
        $error = "Failed to update profile.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Profile</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
        }
        .wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
        }
        #sidebar {
            min-width: 250px;
            max-width: 250px;
            background: #809B53;
            color: #fff;
            transition: all 0.3s;
            position: fixed;
            height: 100%;
        }
        #sidebar.active {
            margin-left: -250px;
        }
        #sidebar .sidebar-header {
            padding: 20px;
            background: #809B53;
        }
        #sidebar ul.components {
            padding: 20px 0;
        }
        #sidebar ul li a {
            padding: 10px;
            font-size: 1.1em;
            display: block;
            color: #fff;
        }
        #sidebar ul li a:hover {
            color: #3E8E41;
            background: #fff;
        }
        #content {
            width: 100%;
            padding: 20px;
            min-height: 100vh;
            transition: all 0.3s;
            margin-left: 250px;
        }
        #sidebarCollapse {
            background: #809B53;
            border: none;
            color: #fff;
            padding: 10px;
            cursor: pointer;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-user-shield"></i> Delivery Dashboard</h3>
        </div>
        <ul class="list-unstyled components">
            <li><a href="delivery_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="delivery_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li><a href="delivery_deliveries.php"><i class="fas fa-truck"></i> Deliveries</a></li>
            <li class="active"><a href="delivery_profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="delivery_notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
            <!-- <li><a href="delivery_settings.php"><i class="fas fa-cogs"></i> Settings</a></li> -->
            <li><a href="delivery_support.php"><i class="fas fa-headset"></i> Support</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <!-- Page Content -->
    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-info">
                    <i class="fas fa-align-left"></i>
                    <span></span>
                </button>
                <div class="ml-auto">
                    <img src="Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png" style="margin-right: 230px; height: 380px; width: 60%;" alt="NutriDaily Logo" class="logo">
                </div>
            </div>
        </nav>

        <div class="container">
            <h2>Profile</h2>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success">
                    <?php
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    ?>
                </div>
            <?php endif; ?>
            <form action="delivery_profile.php" method="POST">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($delivery['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($delivery['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($delivery['phone']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password (leave blank if you don't want to change it)</label>
                    <input type="password" class="form-control" id="password" name="password">
                </div>
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
$(document).ready(function() {
    $('#sidebarCollapse').on('click', function () {
        $('#sidebar').toggleClass('active');
        if ($('#sidebar').hasClass('active')) {
            $('#content').css('margin-left', '0');
        } else {
            $('#content').css('margin-left', '250px');
        }
    });
});
</script>
</body>
</html>
