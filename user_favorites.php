<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}
require 'config/db.php';

// Fetch user details
$user_id = $_SESSION['user_id'];
$user_query = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
$user_query->bind_param('i', $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
if ($user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
    $user_name = $user['name'];
} else {
    $user_name = "User";
}

// Fetch favorite meals
$favorites_query = $conn->prepare("SELECT f.favorite_id, m.meal_id, m.name, m.description FROM favorites f JOIN meals m ON f.meal_id = m.meal_id WHERE f.user_id = ?");
$favorites_query->bind_param('i', $user_id);
$favorites_query->execute();
$favorites_result = $favorites_query->get_result();
$favorites = [];
while ($row = $favorites_result->fetch_assoc()) {
    $favorites[] = $row;
}

// Fetch available meals for adding to favorites
$meals_query = $conn->query("SELECT meal_id, name FROM meals");
$meals = [];
while ($row = $meals_query->fetch_assoc()) {
    $meals[] = $row;
}

// Handle adding favorite
if (isset($_POST['add_favorite'])) {
    $meal_id = $_POST['meal_id'];
    $add_query = $conn->prepare("INSERT INTO favorites (user_id, meal_id) VALUES (?, ?)");
    $add_query->bind_param('ii', $user_id, $meal_id);
    if ($add_query->execute()) {
        header("Location: user_favorites.php");
        exit();
    } else {
        $add_error = "Failed to add favorite. Please try again.";
    }
}

// Handle removing favorite
if (isset($_POST['remove_favorite'])) {
    $favorite_id = $_POST['favorite_id'];
    $remove_query = $conn->prepare("DELETE FROM favorites WHERE favorite_id = ?");
    $remove_query->bind_param('i', $favorite_id);
    if ($remove_query->execute()) {
        header("Location: user_favorites.php");
        exit();
    } else {
        $remove_error = "Failed to remove favorite. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Favorites</title>
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
        .favorite-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e0e0e0;
            padding: 10px 0;
        }
        .favorite-item:last-child {
            border-bottom: none;
        }
        .remove-button {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
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
            <h2>Favorites</h2>
            <div class="card">
                <div class="card-header"><i class="fas fa-heart"></i> My Favorite Meals</div>
                <div class="card-body">
                    <?php if (isset($add_error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $add_error; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($remove_error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $remove_error; ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="user_favorites.php" class="mb-4">
                        <div class="form-group">
                            <label for="meal_id">Add Favorite Meal:</label>
                            <select class="form-control" id="meal_id" name="meal_id" required>
                                <option value="">Select Meal</option>
                                <?php foreach ($meals as $meal): ?>
                                    <option value="<?php echo $meal['meal_id']; ?>"><?php echo htmlspecialchars($meal['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="add_favorite" class="btn btn-primary">Add Favorite</button>
                    </form>
                    <ul class="list-unstyled">
                        <?php if (count($favorites) > 0): ?>
                            <?php foreach ($favorites as $favorite): ?>
                                <li class="favorite-item">
                                    <div>
                                        <strong><?php echo htmlspecialchars($favorite['name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($favorite['description']); ?></small>
                                    </div>
                                    <form method="POST" action="user_favorites.php">
                                        <input type="hidden" name="favorite_id" value="<?php echo $favorite['favorite_id']; ?>">
                                        <button type="submit" name="remove_favorite" class="remove-button"><i class="fas fa-times"></i></button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>You have no favorite meals yet.</p>
                        <?php endif; ?>
                    </ul>
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
