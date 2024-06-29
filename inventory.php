<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require 'config/db.php';

// Search and filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

// Fetch meals data with category name
$sql = "SELECT meals.meal_id, meals.name, meals.stock, categories.category_id, categories.name AS category_name 
        FROM meals 
        JOIN categories ON meals.category = categories.category_id 
        WHERE meals.name LIKE ? AND (categories.category_id = ? OR ? = '')
        ORDER BY meals.name ASC";
$stmt = $conn->prepare($sql);
$search_param = "%$search%";
$stmt->bind_param("sis", $search_param, $category_filter, $category_filter);
$stmt->execute();
$result = $stmt->get_result();

// Handle export to Excel
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="inventory.xls"');
    echo "Meal ID\tName\tStock\tCategory\n";

    $result->data_seek(0);
    while ($row = $result->fetch_assoc()) {
        echo "{$row['meal_id']}\t{$row['name']}\t{$row['stock']}\t{$row['category_name']}\n";
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management</title>
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
        .card-header {
            background:    #809B53 ;
            color: #fff;
            border-bottom: none;
            border-radius: 15px 15px 0 0;
        }
        .stock-status {
            padding: 5px 10px;
            border-radius: 5px;
            color: #fff;
        }
        .in-stock {
            background-color: #28a745;
        }
        .low-stock {
            background-color: #ffc107;
        }
        .out-of-stock {
            background-color: #dc3545;
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
                <button type="button" id="sidebarCollapse" class="btn btn-success">
                    <i class="fas fa-align-left"></i>
                    <span></span>
                </button>
                <div class="ml-auto">
                    <img src="Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png" style="margin-right: 400px;height: 250px; width: 60%;" alt="NutriDaily Logo" class="logo">
                </div>
            </div>
        </nav>

        <div class="container mt-5">
            <h1><i class="fas fa-boxes"></i> Inventory Management</h1>

            <!-- Search and Filter Form -->
            <form method="GET" class="mb-4">
                <div class="form-row">
                    <div class="col">
                        <input type="text" name="search" class="form-control" placeholder="Search by name" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col">
                        <select name="category" class="form-control">
                            <option value="">All Categories</option>
                            <?php
                            $category_sql = "SELECT * FROM categories";
                            $category_result = $conn->query($category_sql);
                            while ($category_row = $category_result->fetch_assoc()) {
                                $selected = $category_row['category_id'] == $category_filter ? 'selected' : '';
                                echo "<option value=\"{$category_row['category_id']}\" $selected>{$category_row['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="inventory.php?export=excel" class="btn btn-success"><i class="fas fa-file-excel"></i> Export to Excel</a>
                    </div>
                </div>
            </form>

            <!-- Inventory Table -->
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Meal ID</th>
                        <th>Name</th>
                        <th>Stock Left</th>
                        <th>Category</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['meal_id']; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td>
                                <?php
                                $stock = $row['stock'];
                                $stock_class = $stock > 10 ? 'in-stock' : ($stock > 0 ? 'low-stock' : 'out-of-stock');
                                ?>
                                <span class="stock-status <?php echo $stock_class; ?>" id="stock-<?php echo $row['meal_id']; ?>"><?php echo $stock; ?></span>
                            </td>
                            <td><?php echo $row['category_name']; ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning edit-btn" data-id="<?php echo $row['meal_id']; ?>" data-name="<?php echo $row['name']; ?>" data-stock="<?php echo $row['stock']; ?>" data-category="<?php echo $row['category_id']; ?>">Edit</button>
                                <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row['meal_id']; ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="editMealForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Meal</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="meal_id" id="editMealId">
                    <div class="form-group">
                        <label for="editName">Name</label>
                        <input type="text" class="form-control" name="name" id="editName" required>
                    </div>
                    <div class="form-group">
                        <label for="editStock">Stock</label>
                        <input type="number" class="form-control" name="stock" id="editStock" required>
                    </div>
                    <div class="form-group">
                        <label for="editCategory">Category</label>
                        <select class="form-control" name="category" id="editCategory" required>
                            <?php
                            $category_sql = "SELECT * FROM categories";
                            $category_result = $conn->query($category_sql);
                            while ($category_row = $category_result->fetch_assoc()) {
                                echo "<option value=\"{$category_row['category_id']}\">{$category_row['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save changes</button>
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
            var mealId = $(this).data('id');
            var mealName = $(this).data('name');
            var mealStock = $(this).data('stock');
            var mealCategory = $(this).data('category');

            $('#editMealId').val(mealId);
            $('#editName').val(mealName);
            $('#editStock').val(mealStock);
            $('#editCategory').val(mealCategory);

            $('#editModal').modal('show');
        });

        // Delete button click handler
        $('.delete-btn').on('click', function () {
            var mealId = $(this).data('id');
            if (confirm('Are you sure you want to delete this meal?')) {
                $.ajax({
                    url: 'delete_meal.php',
                    method: 'POST',
                    data: { meal_id: mealId },
                    success: function (response) {
                        if (response.success) {
                            alert('Meal deleted successfully.');
                            location.reload();
                        } else {
                            alert('Failed to delete meal.');
                        }
                    }
                });
            }
        });

        // Edit meal form submit handler
        $('#editMealForm').on('submit', function (e) {
            e.preventDefault();
            $.ajax({
                url: 'update_meal.php',
                method: 'POST',
                data: $(this).serialize(),
                success: function (response) {
                    if (response.success) {
                        alert('Meal updated successfully.');
                        location.reload();
                    } else {
                        alert('Failed to update meal.');
                    }
                }
            });
        });
    });
</script>
</body>
</html>
