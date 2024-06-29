<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}
require 'config/db.php';

// Fetch user details
$user_id = $_SESSION['user_id'];

// Handle Add Address
if (isset($_POST['add_address'])) {
    $street = $_POST['street'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $zip_code = $_POST['zip_code'];
    $country = $_POST['country'];

    $stmt = $conn->prepare("INSERT INTO addresses (user_id, street, city, state, zip_code, country, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("isssss", $user_id, $street, $city, $state, $zip_code, $country);
    $stmt->execute();
    header("Location: user_addresses.php");
    exit();
}

// Handle Edit Address
if (isset($_POST['edit_address'])) {
    $address_id = $_POST['address_id'];
    $street = $_POST['street'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $zip_code = $_POST['zip_code'];
    $country = $_POST['country'];

    $stmt = $conn->prepare("UPDATE addresses SET street = ?, city = ?, state = ?, zip_code = ?, country = ? WHERE address_id = ? AND user_id = ?");
    $stmt->bind_param("ssssiii", $street, $city, $state, $zip_code, $country, $address_id, $user_id);
    $stmt->execute();
    header("Location: user_addresses.php");
    exit();
}

// Fetch user addresses
$userAddresses = $conn->prepare("SELECT * FROM addresses WHERE user_id = ?");
$userAddresses->bind_param('i', $user_id);
$userAddresses->execute();
$userAddressesResult = $userAddresses->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Addresses</title>
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
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card i {
            font-size: 1.5em;
        }
        #sidebarCollapse {
            background: #343a40;
            border: none;
            color: #fff;
            padding: 10px;
            cursor: pointer;
        }
        .welcome-message {
            background-color: whitesmoke;
            color: black;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        .data-section {
            margin-bottom: 40px;
        }
        .data-section h5 {
            font-size: 1.2em;
            margin-bottom: 20px;
        }
        .data-section ul {
            list-style: none;
            padding: 0;
        }
        .data-section ul li {
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .data-section ul li:last-child {
            border-bottom: none;
        }
        .actions {
            display: flex;
            gap: 10px;
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

        <div class="container-fluid">
            <div class="welcome-message">
                <h2>My Addresses</h2>
            </div>

            <!-- Add Address Button -->
            <button class="btn btn-success mb-4" data-toggle="modal" data-target="#addAddressModal"><i class="fas fa-plus"></i> Add Address</button>

            <!-- Addresses List -->
            <div class="row">
                <div class="col-lg-12 mb-4">
                    <div class="card data-section">
                        <div class="card-header">
                            <i class="fas fa-map-marker-alt"></i> My Addresses
                        </div>
                        <div class="card-body">
                            <ul>
                                <?php while ($address = $userAddressesResult->fetch_assoc()): ?>
                                    <li>
                                        <strong>Street:</strong> <?php echo htmlspecialchars($address['street'] ?? ''); ?><br>
                                        <strong>City:</strong> <?php echo htmlspecialchars($address['city'] ?? ''); ?><br>
                                        <strong>State:</strong> <?php echo htmlspecialchars($address['state'] ?? ''); ?><br>
                                        <strong>Zip Code:</strong> <?php echo htmlspecialchars($address['zip_code'] ?? ''); ?><br>
                                        <strong>Country:</strong> <?php echo htmlspecialchars($address['country'] ?? ''); ?><br>
                                        <strong>Date Added:</strong> <?php echo htmlspecialchars($address['created_at'] ?? ''); ?><br>
                                        <div class="actions">
                                            <button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editAddressModal<?php echo $address['address_id']; ?>"><i class="fas fa-edit"></i> Edit</button>
                                            <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#viewAddressModal<?php echo $address['address_id']; ?>"><i class="fas fa-eye"></i> View</button>
                                        </div>
                                    </li>

                                    <!-- Edit Address Modal -->
                                    <div class="modal fade" id="editAddressModal<?php echo $address['address_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editAddressModalLabel<?php echo $address['address_id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editAddressModalLabel<?php echo $address['address_id']; ?>">Edit Address</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <form action="user_addresses.php" method="POST">
                                                        <input type="hidden" name="address_id" value="<?php echo $address['address_id']; ?>">
                                                        <div class="form-group">
                                                            <label for="street">Street:</label>
                                                            <input type="text" class="form-control" id="street" name="street" value="<?php echo htmlspecialchars($address['street'] ?? ''); ?>" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="city">City:</label>
                                                            <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($address['city'] ?? ''); ?>" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="state">State:</label>
                                                            <input type="text" class="form-control" id="state" name="state" value="<?php echo htmlspecialchars($address['state'] ?? ''); ?>" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="zip_code">Zip Code:</label>
                                                            <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars($address['zip_code'] ?? ''); ?>" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="country">Country:</label>
                                                            <input type="text" class="form-control" id="country" name="country" value="<?php echo htmlspecialchars($address['country'] ?? ''); ?>" required>
                                                        </div>
                                                        <button type="submit" class="btn btn-primary" name="edit_address"><i class="fas fa-save"></i> Save changes</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- View Address Modal -->
                                    <div class="modal fade" id="viewAddressModal<?php echo $address['address_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="viewAddressModalLabel<?php echo $address['address_id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="viewAddressModalLabel<?php echo $address['address_id']; ?>">View Address</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <p><strong>Street:</strong> <?php echo htmlspecialchars($address['street'] ?? ''); ?></p>
                                                    <p><strong>City:</strong> <?php echo htmlspecialchars($address['city'] ?? ''); ?></p>
                                                    <p><strong>State:</strong> <?php echo htmlspecialchars($address['state'] ?? ''); ?></p>
                                                    <p><strong>Zip Code:</strong> <?php echo htmlspecialchars($address['zip_code'] ?? ''); ?></p>
                                                    <p><strong>Country:</strong> <?php echo htmlspecialchars($address['country'] ?? ''); ?></p>
                                                    <p><strong>Date Added:</strong> <?php echo htmlspecialchars($address['created_at'] ?? ''); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                <?php endwhile; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Add Address Modal -->
<div class="modal fade" id="addAddressModal" tabindex="-1" role="dialog" aria-labelledby="addAddressModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAddressModalLabel">Add Address</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="user_addresses.php" method="POST">
                    <div class="form-group">
                        <label for="street">Street:</label>
                        <input type="text" class="form-control" id="street" name="street" required>
                    </div>
                    <div class="form-group">
                        <label for="city">City:</label>
                        <input type="text" class="form-control" id="city" name="city" required>
                    </div>
                    <div class="form-group">
                        <label for="state">State:</label>
                        <input type="text" class="form-control" id="state" name="state" required>
                    </div>
                    <div class="form-group">
                        <label for="zip_code">Zip Code:</label>
                        <input type="text" class="form-control" id="zip_code" name="zip_code" required>
                    </div>
                    <div class="form-group">
                        <label for="country">Country:</label>
                        <input type="text" class="form-control" id="country" name="country" required>
                    </div>
                    <button type="submit" class="btn btn-primary" name="add_address"><i class="fas fa-save"></i> Add Address</button>
                </form>
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
