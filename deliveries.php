<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require 'config/db.php';

// Handle form submission for adding or editing delivery
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_delivery'])) {
    $delivery_id = $_POST['delivery_id'];
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $scheduled_at = $_POST['scheduled_at'];
    $delivered_at = $_POST['delivered_at'];
    $delivery_person_id = $_POST['delivery_person_id'];

    if ($delivery_id) {
        // Edit existing delivery
        $stmt = $conn->prepare("UPDATE deliveries SET order_id = ?, status = ?, scheduled_at = ?, delivered_at = ?, delivery_person_id = ? WHERE delivery_id = ?");
        $stmt->bind_param("isssii", $order_id, $status, $scheduled_at, $delivered_at, $delivery_person_id, $delivery_id);
    } else {
        // Add new delivery
        $stmt = $conn->prepare("INSERT INTO deliveries (order_id, status, scheduled_at, delivered_at, delivery_person_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $order_id, $status, $scheduled_at, $delivered_at, $delivery_person_id);
    }
    $stmt->execute();
    $stmt->close();

    header("Location: deliveries.php");
    exit();
}

// Handle form submission for feedback
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_feedback'])) {
    $delivery_id = $_POST['delivery_id'];
    $rating = $_POST['rating'];
    $comments = $_POST['comments'];

    $stmt = $conn->prepare("INSERT INTO delivery_feedback (delivery_id, rating, comments) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $delivery_id, $rating, $comments);
    $stmt->execute();
    $stmt->close();
}

// Handle search and filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_person = isset($_GET['delivery_person_id']) ? $_GET['delivery_person_id'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build query
$query = "SELECT deliveries.*, orders.order_id, users.name AS user_name, users.email AS user_email, delivery_personnel.name AS delivery_person, addresses.street, addresses.city, addresses.state, addresses.zip_code, addresses.country 
FROM deliveries
JOIN orders ON deliveries.order_id = orders.order_id
JOIN users ON orders.user_id = users.user_id
LEFT JOIN delivery_personnel ON deliveries.delivery_person_id = delivery_personnel.id
JOIN addresses ON users.user_id = addresses.user_id
WHERE (users.name LIKE '%$search%' OR users.email LIKE '%$search%' OR deliveries.status LIKE '%$search%')
AND ('$filter_status' = '' OR deliveries.status = '$filter_status')
AND ('$filter_person' = '' OR deliveries.delivery_person_id = '$filter_person')
AND ('$start_date' = '' OR DATE(deliveries.scheduled_at) >= '$start_date')
AND ('$end_date' = '' OR DATE(deliveries.scheduled_at) <= '$end_date')
ORDER BY deliveries.created_at DESC
LIMIT $limit OFFSET $offset";
$deliveries = $conn->query($query);

// Fetch total records for pagination
$total_query = "SELECT COUNT(*) AS total FROM deliveries
JOIN orders ON deliveries.order_id = orders.order_id
JOIN users ON orders.user_id = users.user_id
WHERE (users.name LIKE '%$search%' OR users.email LIKE '%$search%' OR deliveries.status LIKE '%$search%')
AND ('$filter_status' = '' OR deliveries.status = '$filter_status')
AND ('$filter_person' = '' OR deliveries.delivery_person_id = '$filter_person')
AND ('$start_date' = '' OR DATE(deliveries.scheduled_at) >= '$start_date')
AND ('$end_date' = '' OR DATE(deliveries.scheduled_at) <= '$end_date')";
$total_result = $conn->query($total_query)->fetch_assoc();
$total = $total_result['total'];
$total_pages = ceil($total / $limit);

// Fetch delivery personnel for the dropdown
$delivery_personnel = $conn->query("SELECT id, name FROM delivery_personnel");
$personnel_options = '';
while ($person = $delivery_personnel->fetch_assoc()) {
    $personnel_options .= "<option value='{$person['id']}'>{$person['name']}</option>";
}

// Fetch users and their addresses
$user_addresses_query = $conn->query("SELECT orders.order_id, users.name, addresses.street, addresses.city, addresses.state, addresses.zip_code, addresses.country 
                                      FROM users 
                                      JOIN addresses ON users.user_id = addresses.user_id
                                      JOIN orders ON users.user_id = orders.user_id");
$user_addresses = [];
while ($row = $user_addresses_query->fetch_assoc()) {
    $user_addresses[] = $row;
}

// Handle export to Excel
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename=deliveries.xls');

    $output = fopen('php://output', 'w');
    fputcsv($output, array('Delivery ID', 'Order ID', 'User Name', 'User Email', 'Status', 'Scheduled At', 'Delivered At', 'Delivery Person', 'Street', 'City', 'State', 'Zip Code', 'Country'), "\t");

    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row, "\t");
    }
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Deliveries</title>
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
            background: #809B53; /* Green color */
            color: #fff;
            transition: all 0.3s;
        }
        #sidebar.active {
            margin-left: -250px;
        }
        #sidebar .sidebar-header {
            padding: 20px;
            background: #809B53; /* Green color */
        }
        #sidebar ul.components {
            padding: 20px 0;
        }
        #sidebar ul p {
            color: #fff;
            padding: 10px;
        }
        #sidebar ul li a {
            padding: 10px;
            font-size: 1.1em;
            display: block;
            color: #fff;
        }
        #sidebar ul li a:hover {
            color: #3E8E41; /* Green color */
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
            background: #3E8E41; /* Green color */
            border: none;
            color: #fff;
            padding: 10px;
            cursor: pointer;
        }
        .quick-actions .btn {
            margin-right: 10px;
            margin-bottom: 10px;
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
                    <img src="Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png" style="margin-right: 400px;height: 250px; width: 60%;" alt="NutriDaily Logo" class="logo">
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <h2 class="mt-4">Manage Deliveries</h2>
            <button type="button" class="btn btn-success mb-4" data-toggle="modal" data-target="#addDeliveryModal"><i class="fas fa-plus"></i> Add Delivery</button>
            <form method="GET" class="form-inline mb-4">
                <input type="text" name="search" class="form-control mr-2" placeholder="Search" value="<?php echo $search; ?>">
                <select name="status" class="form-control mr-2">
                    <option value="">All Statuses</option>
                    <option value="Pending" <?php if ($filter_status == 'Pending') echo 'selected'; ?>>Pending</option>
                    <option value="Completed" <?php if ($filter_status == 'Completed') echo 'selected'; ?>>Completed</option>
                </select>
                <select name="delivery_person_id" class="form-control mr-2">
                    <option value="">All Delivery Personnel</option>
                    <?php while ($person = $delivery_personnel->fetch_assoc()): ?>
                        <option value="<?php echo $person['id']; ?>" <?php if ($filter_person == $person['id']) echo 'selected'; ?>><?php echo $person['name']; ?></option>
                    <?php endwhile; ?>
                </select>
                <input type="date" name="start_date" class="form-control mr-2" value="<?php echo $start_date; ?>">
                <input type="date" name="end_date" class="form-control mr-2" value="<?php echo $end_date; ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                <a href="deliveries.php?export=excel" class="btn btn-secondary ml-2"><i class="fas fa-file-excel"></i> Export to Excel</a>
            </form>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Delivery ID</th>
                            <th>Order ID</th>
                            <th>User Name</th>
                            <th>User Email</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th>Scheduled At</th>
                            <th>Delivered At</th>
                            <th>Delivery Person</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($delivery = $deliveries->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $delivery['delivery_id']; ?></td>
                                <td><?php echo $delivery['order_id']; ?></td>
                                <td><?php echo $delivery['user_name']; ?></td>
                                <td><?php echo $delivery['user_email']; ?></td>
                                <td><?php echo $delivery['street'] . ', ' . $delivery['city'] . ', ' . $delivery['state'] . ', ' . $delivery['zip_code'] . ', ' . $delivery['country']; ?></td>
                                <td><?php echo $delivery['status']; ?></td>
                                <td><?php echo $delivery['scheduled_at']; ?></td>
                                <td><?php echo $delivery['delivered_at']; ?></td>
                                <td><?php echo $delivery['delivery_person']; ?></td>
                                <td>
                                    <button type="button" class="btn btn-primary btn-sm edit-btn" data-toggle="modal" data-target="#editDeliveryModal"
                                        data-id="<?php echo $delivery['delivery_id']; ?>"
                                        data-order-id="<?php echo $delivery['order_id']; ?>"
                                        data-status="<?php echo $delivery['status']; ?>"
                                        data-scheduled="<?php echo $delivery['scheduled_at']; ?>"
                                        data-delivered="<?php echo $delivery['delivered_at']; ?>"
                                        data-delivery-person-id="<?php echo $delivery['delivery_person_id']; ?>"><i class="fas fa-edit"></i> Edit</button>
                                    <a href="delete_delivery.php?id=<?php echo $delivery['delivery_id']; ?>" class="btn btn-danger btn-sm delete-delivery"><i class="fas fa-trash-alt"></i> Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>&status=<?php echo $filter_status; ?>&delivery_person_id=<?php echo $filter_person; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Edit Delivery Modal -->
<div class="modal fade" id="editDeliveryModal" tabindex="-1" role="dialog" aria-labelledby="editDeliveryModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDeliveryModalLabel">Edit Delivery</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editDeliveryForm">
                    <input type="hidden" id="edit_delivery_id" name="delivery_id">
                    <div class="form-group">
                        <label for="edit_order_id">Order</label>
                        <select id="edit_order_id" name="order_id" class="form-control" required>
                            <?php foreach ($user_addresses as $user): ?>
                                <option value="<?php echo $user['order_id']; ?>" data-address="<?php echo htmlspecialchars($user['street'] . ', ' . $user['city'] . ', ' . $user['state'] . ', ' . $user['zip_code'] . ', ' . $user['country']); ?>"><?php echo $user['name']; ?> - Order #<?php echo $user['order_id']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_address">Address</label>
                        <input type="text" id="edit_address" name="address" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label for="edit_status">Status</label>
                        <select id="edit_status" name="status" class="form-control" required>
                            <option value="Pending">Pending</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_scheduled_at">Scheduled At</label>
                        <input type="datetime-local" id="edit_scheduled_at" name="scheduled_at" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_delivered_at">Delivered At</label>
                        <input type="datetime-local" id="edit_delivered_at" name="delivered_at" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="edit_delivery_person_id">Delivery Person</label>
                        <select id="edit_delivery_person_id" name="delivery_person_id" class="form-control" required>
                            <?php echo $personnel_options; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" name="save_delivery">Save changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Delivery Modal -->
<div class="modal fade" id="addDeliveryModal" tabindex="-1" role="dialog" aria-labelledby="addDeliveryModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDeliveryModalLabel">Add Delivery</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" id="addDeliveryForm">
                    <div class="form-group">
                        <label for="add_order_id">Order</label>
                        <select id="add_order_id" name="order_id" class="form-control" required>
                            <?php foreach ($user_addresses as $user): ?>
                                <option value="<?php echo $user['order_id']; ?>" data-address="<?php echo htmlspecialchars($user['street'] . ', ' . $user['city'] . ', ' . $user['state'] . ', ' . $user['zip_code'] . ', ' . $user['country']); ?>"><?php echo $user['name']; ?> - Order #<?php echo $user['order_id']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="add_address">Address</label>
                        <input type="text" id="add_address" name="address" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label for="add_status">Status</label>
                        <select id="add_status" name="status" class="form-control" required>
                            <option value="Pending">Pending</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="add_scheduled_at">Scheduled At</label>
                        <input type="datetime-local" id="add_scheduled_at" name="scheduled_at" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="add_delivered_at">Delivered At</label>
                        <input type="datetime-local" id="add_delivered_at" name="delivered_at" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="add_delivery_person_id">Delivery Person</label>
                        <select id="add_delivery_person_id" name="delivery_person_id" class="form-control" required>
                            <?php echo $personnel_options; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" name="save_delivery">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    $(document).on("click", ".edit-btn", function () {
        var deliveryId = $(this).data('id');
        var orderId = $(this).data('order-id');
        var status = $(this).data('status');
        var scheduled = $(this).data('scheduled');
        var delivered = $(this).data('delivered');
        var deliveryPersonId = $(this).data('delivery-person-id');

        $("#edit_delivery_id").val(deliveryId);
        $("#edit_order_id").val(orderId);
        $("#edit_status").val(status);
        $("#edit_scheduled_at").val(scheduled);
        $("#edit_delivered_at").val(delivered);
        $("#edit_delivery_person_id").val(deliveryPersonId);

        var address = $('#edit_order_id option:selected').data('address');
        $("#edit_address").val(address);
    });

    $('#editDeliveryModal').on('hidden.bs.modal', function () {
        $("#edit_delivery_id").val('');
        $("#edit_order_id").val('');
        $("#edit_status").val('');
        $("#edit_scheduled_at").val('');
        $("#edit_delivered_at").val('');
        $("#edit_delivery_person_id").val('');
        $("#edit_address").val('');
    });

    $('#add_order_id').on('change', function () {
        var address = $(this).find('option:selected').data('address');
        $('#add_address').val(address);
    });

    $('#edit_order_id').on('change', function () {
        var address = $(this).find('option:selected').data('address');
        $('#edit_address').val(address);
    });

    $(document).ready(function () {
        $('#sidebarCollapse').on('click', function () {
            $('#sidebar').toggleClass('active');
        });

        // Confirmation for delete
        document.querySelectorAll('.delete-delivery').forEach(function(element) {
            element.addEventListener('click', function(event) {
                if (!confirm('Are you sure you want to delete this delivery?')) {
                    event.preventDefault();
                }
            });
        });
    });
</script>
</body>
</html>
