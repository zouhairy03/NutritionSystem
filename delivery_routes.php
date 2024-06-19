<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require 'config/db.php';

// Function to log activity
function logActivity($conn, $action, $admin_id) {
    $sql = "INSERT INTO activity_logs (admin_id, action) VALUES ('$admin_id', '$action')";
    $conn->query($sql);
}

// Function to notify delivery personnel
function notifyDeliveryPerson($conn, $delivery_person_id, $message) {
    $sql = "INSERT INTO notifications (user_id, message) VALUES ('$delivery_person_id', '$message')";
    $conn->query($sql);
}

// Search and pagination parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$results_per_page = 10;
$this_page_first_result = ($page - 1) * $results_per_page;

// Fetch delivery routes data with search and pagination
$sql = "SELECT delivery_routes.*, CONCAT(addresses.street, ', ', addresses.city, ', ', addresses.state, ', ', addresses.zip_code, ', ', addresses.country) AS address 
        FROM delivery_routes 
        JOIN orders ON delivery_routes.route_id = orders.delivery_route_id 
        JOIN addresses ON orders.address_id = addresses.address_id 
        WHERE delivery_routes.route_name LIKE '%$search%' 
        ORDER BY delivery_routes.route_name ASC 
        LIMIT $this_page_first_result, $results_per_page";
$deliveryRoutesQuery = $conn->query($sql);

// Fetch total results for pagination
$total_results = $conn->query("SELECT COUNT(*) AS count 
                               FROM delivery_routes 
                               JOIN orders ON delivery_routes.route_id = orders.delivery_route_id 
                               JOIN addresses ON orders.address_id = addresses.address_id 
                               WHERE delivery_routes.route_name LIKE '%$search%'")->fetch_assoc()['count'];
$total_pages = ceil($total_results / $results_per_page);

// Fetch delivery personnel data
$deliveryPersonsQuery = $conn->query("SELECT id, name FROM delivery_personnel");

// Handle Add Delivery Route
$error = '';
if (isset($_POST['add_route'])) {
    $route_name = $_POST['route_name'];
    $delivery_person_id = $_POST['delivery_person_id'];
    
    if (empty($route_name) || empty($delivery_person_id)) {
        $error = "All fields are required.";
    } else {
        $sql = "INSERT INTO delivery_routes (route_name, delivery_person_id) VALUES ('$route_name', '$delivery_person_id')";
        if ($conn->query($sql)) {
            logActivity($conn, "Added new route: $route_name", $_SESSION['admin_id']);
            notifyDeliveryPerson($conn, $delivery_person_id, "You have been assigned a new route: $route_name");
            header("Location: delivery_routes.php");
        } else {
            $error = "Failed to add route.";
        }
    }
}

// Handle Edit Delivery Route
if (isset($_POST['edit_route'])) {
    $route_id = $_POST['route_id'];
    $route_name = $_POST['route_name'];
    $delivery_person_id = $_POST['delivery_person_id'];
    
    if (empty($route_name) || empty($delivery_person_id)) {
        $error = "All fields are required.";
    } else {
        $sql = "UPDATE delivery_routes SET route_name='$route_name', delivery_person_id='$delivery_person_id' WHERE route_id='$route_id'";
        if ($conn->query($sql)) {
            logActivity($conn, "Edited route: $route_name", $_SESSION['admin_id']);
            notifyDeliveryPerson($conn, $delivery_person_id, "Your assigned route has been updated: $route_name");
            header("Location: delivery_routes.php");
        } else {
            $error = "Failed to update route.";
        }
    }
}

// Handle Delete Delivery Route
if (isset($_GET['delete_route'])) {
    $route_id = $_GET['delete_route'];
    $sql = "DELETE FROM delivery_routes WHERE route_id='$route_id'";
    if ($conn->query($sql)) {
        logActivity($conn, "Deleted route with ID: $route_id", $_SESSION['admin_id']);
        header("Location: delivery_routes.php");
    } else {
        $error = "Failed to delete route.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Delivery Routes</title>
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
            background: #343a40;
            color: #fff;
            transition: all 0.3s;
        }
        #sidebar.active {
            margin-left: -250px;
        }
        #sidebar .sidebar-header {
            padding: 20px;
            background: #343a40;
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
            color: #343a40;
            background: #fff;
        }
        #content {
            width: 100%;
            padding: 20px;
            min-height: 100vh;
        }
        .card {
            margin-bottom: 20px;
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .card i {
            font-size: 2em;
        }
        #sidebarCollapse {
            background: #343a40;
            border: none;
            color: #fff;
            padding: 10px;
            cursor: pointer;
        }
        .pagination {
            justify-content: center;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar">
    <div class="sidebar-header">
        <h3>Admin Dashboard</h3>
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
                    <span>Toggle Sidebar</span>
                </button>
            </div>
        </nav>

        <div class="container mt-5">
            <h1><i class="fas fa-route"></i> Manage Delivery Routes</h1>

            <!-- Search Form -->
            <form method="GET" class="mb-4">
                <input type="text" name="search" class="form-control" placeholder="Search routes..." value="<?php echo htmlspecialchars($search); ?>">
            </form>

            <!-- Display Error -->
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Add Delivery Route Button -->
            <button class="btn btn-success mb-4" data-toggle="modal" data-target="#addRouteModal"><i class="fas fa-plus"></i> Add Route</button>

            <!-- Delivery Routes Table -->
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Route ID</th>
                        <th>Route Name</th>
                        <th>Address</th>
                        <th>Delivery Person</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $deliveryRoutesQuery->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['route_id']; ?></td>
                            <td><?php echo $row['route_name']; ?></td>
                            <td><?php echo $row['address']; ?></td>
                            <td><?php
                                $delivery_person_id = $row['delivery_person_id'];
                                $delivery_person_name_query = $conn->query("SELECT name FROM delivery_personnel WHERE id = $delivery_person_id");
                                $delivery_person_name = $delivery_person_name_query->fetch_assoc()['name'];
                                echo $delivery_person_name;
                            ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning edit-btn" data-id="<?php echo $row['route_id']; ?>" data-name="<?php echo $row['route_name']; ?>" data-person="<?php echo $row['delivery_person_id']; ?>">Edit</button>
                                <a href="delivery_routes.php?delete_route=<?php echo $row['route_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this route?')"><i class="fas fa-trash"></i> Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- Pagination controls -->
            <nav>
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                            <a class="page-link" href="delivery_routes.php?page=<?php echo $i; ?>&search=<?php echo $search; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Add Route Modal -->
<div class="modal fade" id="addRouteModal" tabindex="-1" role="dialog" aria-labelledby="addRouteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="delivery_routes.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="addRouteModalLabel">Add Delivery Route</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="route_name">Route Name</label>
                        <input type="text" class="form-control" id="route_name" name="route_name" required>
                    </div>
                    <div class="form-group">
                        <label for="delivery_person_id">Delivery Person</label>
                        <select class="form-control" id="delivery_person_id" name="delivery_person_id" required>
                            <?php while ($person = $deliveryPersonsQuery->fetch_assoc()): ?>
                                <option value="<?php echo $person['id']; ?>"><?php echo $person['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" name="add_route">Add Route</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Route Modal -->
<div class="modal fade" id="editRouteModal" tabindex="-1" role="dialog" aria-labelledby="editRouteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="editRouteForm" action="delivery_routes.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRouteModalLabel">Edit Delivery Route</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="route_id" id="editRouteId">
                    <div class="form-group">
                        <label for="editRouteName">Route Name</label>
                        <input type="text" class="form-control" name="route_name" id="editRouteName" required>
                    </div>
                    <div class="form-group">
                        <label for="editDeliveryPersonId">Delivery Person</label>
                        <select class="form-control" name="delivery_person_id" id="editDeliveryPersonId" required>
                            <?php
                            $deliveryPersonsQuery->data_seek(0); // Reset the pointer to the beginning
                            while ($person = $deliveryPersonsQuery->fetch_assoc()): ?>
                                <option value="<?php echo $person['id']; ?>"><?php echo $person['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" name="edit_route">Save changes</button>
                </div>
            </form>
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

        // Edit button click handler
        $('.edit-btn').on('click', function () {
            var routeId = $(this).data('id');
            var routeName = $(this).data('name');
            var deliveryPersonId = $(this).data('person');

            $('#editRouteId').val(routeId);
            $('#editRouteName').val(routeName);
            $('#editDeliveryPersonId').val(deliveryPersonId);

            $('#editRouteModal').modal('show');
        });
    });
</script>
</body>
</html>
