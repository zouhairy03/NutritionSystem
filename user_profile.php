<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}
require 'config/db.php';

// Fetch user details
$user_id = $_SESSION['user_id'];
$user_query = $conn->prepare("SELECT u.*, a.street, a.city FROM users u LEFT JOIN addresses a ON u.user_id = a.user_id WHERE u.user_id = ?");
$user_query->bind_param('i', $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
if ($user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
    $user_name = $user['name'] ?? '';
    $user_email = $user['email'] ?? '';
    $user_phone = $user['phone'] ?? '';
    $user_street = $user['street'] ?? '';
    $user_city = $user['city'] ?? '';
    $user_malady_id = $user['malady_id'] ?? null; // Assume malady_id is stored in users table
} else {
    $user_name = "User";
    $user_email = "";
    $user_phone = "";
    $user_street = "";
    $user_city = "";
    $user_malady_id = null;
}

// Fetch available maladies
$maladies_query = $conn->query("SELECT malady_id, name FROM maladies");
$maladies = [];
while ($row = $maladies_query->fetch_assoc()) {
    $maladies[] = $row;
}

// Handle form submission for profile update
$update_success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_name = $_POST['name'];
    $new_email = $_POST['email'];
    $new_phone = $_POST['phone'];
    $new_street = $_POST['street'];
    $new_city = $_POST['city'];
    $new_password = $_POST['password'];
    $new_malady_id = $_POST['malady_id'];

    // Update user information in the database
    if (!empty($new_password)) {
        $update_user_query = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, password = ?, malady_id = ? WHERE user_id = ?");
        $update_user_query->bind_param('sssisi', $new_name, $new_email, $new_phone, $new_password, $new_malady_id, $user_id);
    } else {
        $update_user_query = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, malady_id = ? WHERE user_id = ?");
        $update_user_query->bind_param('sssii', $new_name, $new_email, $new_phone, $new_malady_id, $user_id);
    }

    $update_address_query = $conn->prepare("REPLACE INTO addresses (user_id, street, city) VALUES (?, ?, ?)");
    $update_address_query->bind_param('iss', $user_id, $new_street, $new_city);

    if ($update_user_query->execute() && $update_address_query->execute()) {
        $update_success = "Profile updated successfully!";
        $user_name = $new_name;
        $user_email = $new_email;
        $user_phone = $new_phone;
        $user_street = $new_street;
        $user_city = $new_city;
        $user_malady_id = $new_malady_id;
    } else {
        $update_success = "Failed to update profile. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
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
            background-color: #f1f2f6;
        }
        .container {
            margin-top: 50px;
        }
        .card {
            margin-bottom: 20px;
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .card .card-header {
            background-color: #809B53;
            color: #fff;
            border-bottom: none;
            border-radius: 15px 15px 0 0;
            font-size: 1.2em;
            padding: 15px;
        }
        .card .card-body {
            padding: 30px;
        }
        .form-group label {
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-user"></i> User Dashboard</h3>
        </div>
        <ul class="list-unstyled components">
            <li><a href="user_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="user_profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="user_favorites.php"><i class="fas fa-heart"></i> Favorites</a></li>
            <li><a href="user_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li><a href="user_order_history.php"><i class="fas fa-history"></i> Order History</a></li>
            <li><a href="user_notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
            <li><a href="user_addresses.php"><i class="fas fa-map-marker-alt"></i> Addresses</a></li>
            <li><a href="user_deliveries.php"><i class="fas fa-truck"></i> Deliveries</a></li>
            <!-- <li><a href="user_settings.php"><i class="fas fa-cogs"></i> Settings</a></li> -->
            <li><a href="user_help.php"><i class="fas fa-question-circle"></i> Help & Support</a></li>
            <li><a href="user_feedback.php"><i class="fas fa-comments"></i> Feedback & Ratings</a></li>
            <li><a href="user_community.php"><i class="fas fa-users"></i> Community</a></li>
            <li><a href="user_coupons.php"><i class="fas fa-tag"></i> Coupons & Offers</a></li>
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
                    <img src="Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png" style="margin-right: 400px; height: 250px; width: 60%;" alt="NutriDaily Logo" class="logo">
                </div>
            </div>
        </nav>

        <div class="container">
            <h2>Profile</h2>
            <?php if ($update_success): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $update_success; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header"><i class="fas fa-user"></i> Update Profile</div>
                <div class="card-body">
                    <form method="POST" action="user_profile.php">
                        <div class="form-group">
                            <label for="name">Name:</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user_name ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_email ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone:</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user_phone ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="street">Street:</label>
                            <input type="text" class="form-control" id="street" name="street" value="<?php echo htmlspecialchars($user_street ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="city">City:</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($user_city ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="malady_id">Malady:</label>
                            <select class="form-control" id="malady_id" name="malady_id" required>
                                <option value="">Select Malady</option>
                                <?php foreach ($maladies as $malady): ?>
                                    <option value="<?php echo $malady['malady_id']; ?>" <?php if ($user_malady_id == $malady['malady_id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($malady['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="password">Password (leave blank to keep current password):</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    $(document).ready(function () {
        $('#sidebarCollapse').on('click', function () {
            $('#sidebar').toggleClass('active');
        });
    });
</script>
</body>
</html>
