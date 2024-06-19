<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require 'config/db.php';

// Fetch addresses
$addresses_query = $conn->query("SELECT addresses.address_id, addresses.user_id, addresses.street, addresses.city, addresses.state, addresses.zip_code, addresses.country, users.name as user_name FROM addresses JOIN users ON addresses.user_id = users.user_id");

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $conn->query("DELETE FROM addresses WHERE address_id = $delete_id");
    header("Location: addresses.php");
    exit();
}

// Handle update request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_address_id'])) {
    $update_address_id = $_POST['update_address_id'];
    $user_id = $_POST['user_id'];
    $street = $_POST['street'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $zip_code = $_POST['zip_code'];
    $country = $_POST['country'];
    $conn->query("UPDATE addresses SET user_id='$user_id', street='$street', city='$city', state='$state', zip_code='$zip_code', country='$country' WHERE address_id = $update_address_id");
    header("Location: addresses.php");
    exit();
}

// Fetch users for the dropdown
$users_query = $conn->query("SELECT user_id, name FROM users");
$users = [];
while ($user = $users_query->fetch_assoc()) {
    $users[] = $user;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Addresses</title>
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
            background:    #809B53 ;
            color: #fff;
            transition: all 0.3s;
        }
        #sidebar.active {
            margin-left: -250px;
        }
        #sidebar .sidebar-header {
            padding: 20px;
            background:    #809B53 ;
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
        .card .card-body {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card i {
            font-size: 2.5em;
        }
        #sidebarCollapse {
            background: #343a40;
            border: none;
            color: #fff;
            padding: 10px;
            cursor: pointer;
        }
        .table-container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .table-container h2 {
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar">
    <div class="sidebar-header">
    <h3><i class="fas fa-user-shield"></i> Admin Dashboard</h3>
    </div>
    <ul class="list-unstyled components">
        <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
        <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
        <li><a href="coupons.php"><i class="fas fa-tags"></i> Coupons</a></li>
        <li><a href="maladies.php"><i class="fas fa-notes-medical"></i> Maladies</a></li>
        <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
        <li><a href="meals.php"><i class="fas fa-utensils"></i> Meals</a></li>
        <li><a href="payments.php"><i class="fas fa-dollar-sign"></i> Payments</a></li>
        <li><a href="deliveries.php"><i class="fas fa-truck"></i> Deliveries</a></li>
        <li><a href="delivers.php"><i class="fas fa-user-shield"></i> Delivery Personnel</a></li>
        <li><a href="reports.php"><i class="fas fa-chart-pie"></i> Reports</a></li>
        <li><a href="settings.php"><i class="fas fa-cogs"></i> Settings</a></li>
        <li><a href="support_tickets.php"><i class="fas fa-ticket-alt"></i> Support Tickets</a></li>
        <li><a href="feedback.php"><i class="fas fa-comments"></i> User Feedback</a></li>
        <li><a href="inventory.php"><i class="fas fa-boxes"></i> Inventory</a></li>
        <!-- <li><a href="delivery_routes.php"><i class="fas fa-route"></i> Delivery Routes</a></li> -->
        <!-- <li><a href="marketing.php"><i class="fas fa-bullhorn"></i> Marketing Campaigns</a></li> -->
        <li><a href="activity_logs.php"><i class="fas fa-list"></i> Activity Logs</a></li>
        <li><a href="financial_overview.php"><i class="fas fa-dollar-sign"></i> Financial Overview</a></li>

        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</nav>
    <!-- Page Content -->
    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-info">
                    <i class="fas fa-align-left"></i>
                    <span></span>
                </button>
                <div class="ml-auto">
                    <img src="Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png" style="margin-right: 230px;height: 250px; width: 60%;" alt="NutriDaily Logo" class="logo">
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <div class="table-container">
                <h2>Manage Addresses</h2>
                <a href="add_address.php" class="btn btn-primary mb-3"><i class="fas fa-plus"></i> Add New Address</a>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Address ID</th>
                            <th>User Name</th>
                            <th>Street</th>
                            <th>City</th>
                            <th>State</th>
                            <th>Zip Code</th>
                            <th>Country</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($address = $addresses_query->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $address['address_id']; ?></td>
                                <td><?php echo $address['user_name']; ?></td>
                                <td><?php echo $address['street']; ?></td>
                                <td><?php echo $address['city']; ?></td>
                                <td><?php echo $address['state']; ?></td>
                                <td><?php echo $address['zip_code']; ?></td>
                                <td><?php echo $address['country']; ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm edit-btn" data-toggle="modal" data-target="#editModal" data-id="<?php echo $address['address_id']; ?>" data-user_id="<?php echo $address['user_id']; ?>" data-street="<?php echo htmlspecialchars($address['street'], ENT_QUOTES); ?>" data-city="<?php echo htmlspecialchars($address['city'], ENT_QUOTES); ?>" data-state="<?php echo htmlspecialchars($address['state'], ENT_QUOTES); ?>" data-zip_code="<?php echo htmlspecialchars($address['zip_code'], ENT_QUOTES); ?>" data-country="<?php echo htmlspecialchars($address['country'], ENT_QUOTES); ?>"><i class="fas fa-edit"></i> Edit</button>
                                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="display:inline-block;">
                                        <input type="hidden" name="delete_id" value="<?php echo $address['address_id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="editModalLabel">Edit Address</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="update_address_id" id="editAddressId">
            <div class="form-group">
                <label>User</label>
                <select name="user_id" id="editUserId" class="form-control">
                    <option value="">Select a user</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['user_id']; ?>"><?php echo $user['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Street</label>
                <input type="text" name="street" id="editStreet" class="form-control">
            </div>
            <div class="form-group">
                <label>City</label>
                <input type="text" name="city" id="editCity" class="form-control">
            </div>
            <div class="form-group">
                <label>State</label>
                <input type="text" name="state" id="editState" class="form-control">
            </div>
            <div class="form-group">
                <label>Zip Code</label>
                <input type="text" name="zip_code" id="editZipCode" class="form-control">
            </div>
            <div class="form-group">
                <label>Country</label>
                <input type="text" name="country" id="editCountry" class="form-control">
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Save changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    $(document).ready(function () {
        $('#sidebarCollapse').on('click', function () {
            $('#sidebar').toggleClass('active');
        });

        $('.edit-btn').on('click', function () {
            var addressId = $(this).data('id');
            var userId = $(this).data('user_id');
            var street = $(this).data('street');
            var city = $(this).data('city');
            var state = $(this).data('state');
            var zipCode = $(this).data('zip_code');
            var country = $(this).data('country');

            $('#editAddressId').val(addressId);
            $('#editUserId').val(userId);
            $('#editStreet').val(street);
            $('#editCity').val(city);
            $('#editState').val(state);
            $('#editZipCode').val(zipCode);
            $('#editCountry').val(country);
        });
    });
</script>
</body>
</html>
