<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}
require 'config/db.php';

// Fetch user details
$user_id = $_SESSION['user_id'];

// Fetch user deliveries with search, filter, and pagination
$search_query = isset($_GET['search_query']) ? $_GET['search_query'] : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'created_at';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Handle search, filter, and sorting
$search_sql = $search_query ? "AND (deliveries.delivery_id LIKE '%$search_query%' OR orders.order_id LIKE '%$search_query%')" : "";
$status_sql = $status_filter ? "AND deliveries.status = '$status_filter'" : "";
$sort_order = in_array($sort_by, ['created_at', 'status', 'order_id']) ? $sort_by : 'created_at';

// Fetch deliveries
$userDeliveries = $conn->prepare("
    SELECT deliveries.*, orders.order_id
    FROM deliveries
    LEFT JOIN orders ON deliveries.order_id = orders.order_id
    WHERE orders.user_id = ? $search_sql $status_sql
    ORDER BY deliveries.$sort_order DESC
    LIMIT ? OFFSET ?
");
$userDeliveries->bind_param('iii', $user_id, $limit, $offset);
$userDeliveries->execute();
$userDeliveriesResult = $userDeliveries->get_result();

// Fetch total number of records to calculate total pages
$totalDeliveriesQuery = $conn->prepare("
    SELECT COUNT(*) as total
    FROM deliveries
    LEFT JOIN orders ON deliveries.order_id = orders.order_id
    WHERE orders.user_id = ? $search_sql $status_sql
");
$totalDeliveriesQuery->bind_param('i', $user_id);
$totalDeliveriesQuery->execute();
$totalDeliveriesResult = $totalDeliveriesQuery->get_result();
$totalDeliveries = $totalDeliveriesResult->fetch_assoc()['total'];
$totalPages = ceil($totalDeliveries / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Deliveries</title>
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
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 15px;
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        .data-section ul li .actions {
            position: absolute;
            top: 20px;
            right: 20px;
        }
        .modal .modal-dialog {
            max-width: 800px;
        }
        .actions {
            display: flex;
            /* gap: 10px; */
        }
        .btn-sm {
            padding: 5px 10px;
        }
        .btn-warning, .btn-primary {
            color: #fff;
        }
        .search-filter-form {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .search-filter-form .form-group {
            margin-right: 10px;
        }
        .delivered {
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .pending {
            background-color: #fff3cd;
            border-color: #ffeeba;
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
                    <img src="Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png" style="margin-right: 460px; height: 250px; width: 60%;" alt="NutriDaily Logo" class="logo">
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <div class="welcome-message">
                <h2>My Deliveries</h2>
            </div>

            <!-- Search and Filter Form -->
            <form class="search-filter-form row" action="user_deliveries.php" method="GET">
                <div class="form-group col-md-3">
                    <input type="text" class="form-control" name="search_query" placeholder="Search Deliveries" value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <div class="form-group col-md-3">
                    <select name="status_filter" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="delivered" <?php if ($status_filter == 'delivered') echo 'selected'; ?>>Delivered</option>
                        <option value="pending" <?php if ($status_filter == 'pending') echo 'selected'; ?>>Pending</option>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <select name="sort_by" class="form-control">
                        <option value="created_at" <?php if ($sort_by == 'created_at') echo 'selected'; ?>>Date</option>
                        <option value="status" <?php if ($sort_by == 'status') echo 'selected'; ?>>Status</option>
                        <option value="order_id" <?php if ($sort_by == 'order_id') echo 'selected'; ?>>Order ID</option>
                    </select>
                </div>
                <div class="form-group col-md-3 d-flex align-items-center">
                    <button type="submit" class="btn btn-primary mr-2"><i class="fas fa-search"></i> Search</button>
                    <a href="generate_delivery_pdf.php?search_query=<?php echo htmlspecialchars($search_query); ?>&status_filter=<?php echo htmlspecialchars($status_filter); ?>&sort_by=<?php echo htmlspecialchars($sort_by); ?>" class="btn btn-success"><i class="fas fa-file-pdf"></i> Download PDF</a>
                </div>
            </form>

            <!-- Deliveries List -->
            <div class="row">
                <div class="col-lg-12 mb-4">
                    <div class="card data-section">
                        <div class="card-header">
                            <i class="fas fa-truck"></i> Recent Deliveries
                        </div>
                        <div class="card-body">
                            <ul>
                                <?php while ($delivery = $userDeliveriesResult->fetch_assoc()): ?>
                                    <li class="<?php echo htmlspecialchars($delivery['status'] ?? ''); ?>">
                                        <strong>Delivery ID:</strong> <?php echo htmlspecialchars($delivery['delivery_id'] ?? ''); ?><br>
                                        <strong>Order ID:</strong> <?php echo htmlspecialchars($delivery['order_id'] ?? ''); ?><br>
                                        <strong>Status:</strong> <?php echo htmlspecialchars($delivery['status'] ?? ''); ?><br>
                                        <strong>Delivered At:</strong> <?php echo htmlspecialchars($delivery['delivered_at'] ?? ''); ?><br>
                                        <strong>Date Added:</strong> <?php echo htmlspecialchars($delivery['created_at'] ?? ''); ?><br>
                                        <div class="actions">
                                            <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#viewDeliveryModal<?php echo $delivery['delivery_id']; ?>"><i class="fas fa-eye"></i> View</button>
                                        </div>
                                    </li>

                                    <!-- View Delivery Modal -->
                                    <div class="modal fade" id="viewDeliveryModal<?php echo $delivery['delivery_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="viewDeliveryModalLabel<?php echo $delivery['delivery_id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="viewDeliveryModalLabel<?php echo $delivery['delivery_id']; ?>">View Delivery</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <p><strong>Delivery ID:</strong> <?php echo htmlspecialchars($delivery['delivery_id'] ?? ''); ?></p>
                                                    <p><strong>Order ID:</strong> <?php echo htmlspecialchars($delivery['order_id'] ?? ''); ?></p>
                                                    <p><strong>Status:</strong> <?php echo htmlspecialchars($delivery['status'] ?? ''); ?></p>
                                                    <p><strong>Delivered At:</strong> <?php echo htmlspecialchars($delivery['delivered_at'] ?? ''); ?></p>
                                                    <p><strong>Date Added:</strong> <?php echo htmlspecialchars($delivery['created_at'] ?? ''); ?></p>
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

            <!-- Pagination Controls -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                            <a class="page-link" href="user_deliveries.php?page=<?php echo $i; ?>&search_query=<?php echo htmlspecialchars($search_query); ?>&status_filter=<?php echo htmlspecialchars($status_filter); ?>&sort_by=<?php echo htmlspecialchars($sort_by); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>

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
