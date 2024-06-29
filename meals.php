<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require 'config/db.php';

// Ensure the uploads directory exists
if (!file_exists('uploads')) {
    mkdir('uploads', 0777, true);
}

// Fetch maladies and categories for the dropdown
$maladiesResult = $conn->query("SELECT malady_id, name FROM maladies");
$categoriesResult = $conn->query("SELECT category_id, name FROM categories");

// Handle Add Meal
if (isset($_POST['add_meal'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $malady_id = $_POST['malady_id'];
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    $stock = $_POST['stock']; // Add stock
    $image = '';

    // Handle image upload
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "uploads/";
        $image = $target_dir . basename($_FILES["image"]["name"]);
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $image)) {
            // File uploaded successfully
        } else {
            // Error uploading file
            echo "Error uploading the file.";
        }
    }

    $sql = "INSERT INTO meals (name, description, malady_id, category, price, stock, image, created_at, updated_at) VALUES ('$name', '$description', '$malady_id', '$category_id', '$price', '$stock', '$image', NOW(), NOW())";
    $conn->query($sql);
    header("Location: meals.php");
}

// Handle Edit Meal
if (isset($_POST['edit_meal'])) {
    $meal_id = $_POST['meal_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $malady_id = $_POST['malady_id'];
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    $stock = $_POST['stock']; // Add stock
    $image = $_POST['existing_image'];

    // Handle image upload
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "uploads/";
        $image = $target_dir . basename($_FILES["image"]["name"]);
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $image)) {
            // File uploaded successfully
        } else {
            // Error uploading file
            echo "Error uploading the file.";
        }
    }

    $sql = "UPDATE meals SET name='$name', description='$description', malady_id='$malady_id', category='$category_id', price='$price', stock='$stock', image='$image', updated_at=NOW() WHERE meal_id='$meal_id'";
    $conn->query($sql);
    header("Location: meals.php");
}

// Handle Delete Meal
$delete_error = "";
if (isset($_GET['delete_meal'])) {
    $meal_id = $_GET['delete_meal'];

    // Check for related orders
    $related_orders_query = "SELECT COUNT(*) AS count FROM orders WHERE meal_id='$meal_id'";
    $related_orders_result = $conn->query($related_orders_query);
    $related_orders_count = $related_orders_result->fetch_assoc()['count'];

    if ($related_orders_count > 0) {
        $delete_error = "Cannot delete meal. There are orders associated with this meal.";
    } else {
        $sql = "DELETE FROM meals WHERE meal_id='$meal_id'";
        $conn->query($sql);
        header("Location: meals.php");
    }
}

// Handle Search and Filter
$search_query = isset($_POST['search_query']) ? $_POST['search_query'] : "";
$malady_filter = isset($_POST['malady_filter']) ? $_POST['malady_filter'] : "";
$category_filter = isset($_POST['category_filter']) ? $_POST['category_filter'] : "";

// Handle Pagination
$limit = 10; // Number of entries per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Fetch meals data with pagination, malady filter, and category filter
$search_sql = $search_query ? "WHERE (meals.name LIKE '%$search_query%' OR meals.description LIKE '%$search_query%')" : "WHERE 1";
$malady_sql = $malady_filter ? "AND meals.malady_id='$malady_filter'" : "";
$category_sql = $category_filter ? "AND meals.category='$category_filter'" : "";
$sql = "SELECT meals.*, maladies.name AS malady_name, categories.name AS category_name FROM meals 
        LEFT JOIN maladies ON meals.malady_id=maladies.malady_id 
        LEFT JOIN categories ON meals.category=categories.category_id 
        $search_sql $malady_sql $category_sql 
        LIMIT $start, $limit";
$result = $conn->query($sql);

// Fetch total meals count for pagination
$sql_count = "SELECT COUNT(*) AS total FROM meals $search_sql $malady_sql $category_sql";
$count_result = $conn->query($sql_count);
$total_meals = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_meals / $limit);

// Handle Excel Export
if (isset($_GET['export'])) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=meals.xls");
    $output = fopen("php://output", "w");
    fputcsv($output, array('Meal ID', 'Name', 'Description', 'Malady', 'Category', 'Price', 'Stock', 'Image', 'Created At', 'Updated At'), "\t");
    $export_sql = "SELECT meals.*, maladies.name AS malady_name, categories.name AS category_name FROM meals 
                   LEFT JOIN maladies ON meals.malady_id=maladies.malady_id 
                   LEFT JOIN categories ON meals.category=categories.category_id";
    $export_result = $conn->query($export_sql);
    while ($row = $export_result->fetch_assoc()) {
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
    <title>Manage Meals</title>
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
        .modal .modal-dialog {
            max-width: 800px;
        }
        .navbar {
            color: #fff;
        }
        .navbar .navbar-brand {
            color: #fff;
        }
        .navbar .navbar-brand:hover {
            color: #f8f9fa;
        }
        .navbar .logo {
            width: 150px;
            height: auto;
        }
        .navbar .ml-auto {
            margin-left: auto;
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
            <li><a href="activity_logs.php"><i class="fas fa-list"></i> Activity Logs</a></li>
            <li><a href="financial_overview.php"><i class="fas fa-dollar-sign"></i> Financial Overview</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <!-- Page Content -->
    <div id="content">
        <nav class="navbar navbar-expand-lg">
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
        
        <div class="container mt-5">
            <h1><i class="fas fa-utensils"></i> Manage Meals</h1>

            <?php if ($delete_error): ?>
                <div class="alert alert-danger"><?php echo $delete_error; ?></div>
            <?php endif; ?>

            <!-- Add Category Button -->
            <a href="add_category.php" class="btn btn-secondary mb-3"><i class="fas fa-plus"></i> Add New Category</a>

            <!-- Search Form -->
            <form action="meals.php" method="POST" class="form-inline mb-4">
                <input type="text" class="form-control mr-sm-2" name="search_query" placeholder="Search" value="<?php echo $search_query; ?>">
                <button type="submit" class="btn btn-primary" name="search"><i class="fas fa-search"></i> Search</button>
            </form>

            <!-- Filter by Malady and Category Form -->
            <form action="meals.php" method="POST" class="form-inline mb-4">
                <div class="form-group">
                    <label for="malady_filter">Malady:</label>
                    <select class="form-control ml-2" id="malady_filter" name="malady_filter">
                        <option value="">All</option>
                        <?php
                        // Reset the maladies result set
                        $maladiesResult->data_seek(0);
                        while ($malady = $maladiesResult->fetch_assoc()): ?>
                            <option value="<?php echo $malady['malady_id']; ?>" <?php if ($malady_filter == $malady['malady_id']) echo 'selected'; ?>><?php echo $malady['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group ml-2">
                    <label for="category_filter">Category:</label>
                    <select class="form-control ml-2" id="category_filter" name="category_filter">
                        <option value="">All</option>
                        <?php
                        // Reset the categories result set
                        $categoriesResult->data_seek(0);
                        while ($category = $categoriesResult->fetch_assoc()): ?>
                            <option value="<?php echo $category['category_id']; ?>" <?php if ($category_filter == $category['category_id']) echo 'selected'; ?>><?php echo $category['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary ml-2" name="filter_malady_category"><i class="fas fa-filter"></i> Filter</button>
            </form>

            <!-- Add Meal Form -->
            <h2>Add Meal</h2>
            <form action="meals.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label for="malady_id">Malady:</label>
                    <select class="form-control" id="malady_id" name="malady_id" required>
                        <?php
                        // Reset the maladies result set again for the add form
                        $maladiesResult->data_seek(0);
                        while ($malady = $maladiesResult->fetch_assoc()): ?>
                            <option value="<?php echo $malady['malady_id']; ?>"><?php echo $malady['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="category_id">Category:</label>
                    <select class="form-control" id="category_id" name="category_id" required>
                        <?php
                        // Reset the categories result set again for the add form
                        $categoriesResult->data_seek(0);
                        while ($category = $categoriesResult->fetch_assoc()): ?>
                            <option value="<?php echo $category['category_id']; ?>"><?php echo $category['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="price">Price (MAD):</label>
                    <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                </div>
                <div class="form-group">
                    <label for="stock">Stock:</label> <!-- Add stock field -->
                    <input type="number" class="form-control" id="stock" name="stock" required>
                </div>
                <div class="form-group">
                    <label for="image">Image:</label>
                    <input type="file" class="form-control" id="image" name="image" onchange="previewImage(event)">
                    <img id="imagePreview" src="#" alt="Image Preview" style="display:none; width: 100px; margin-top: 10px;">
                </div>
                <button type="submit" class="btn btn-primary" name="add_meal"><i class="fas fa-plus"></i> Add Meal</button>
            </form>

            <hr>

            <!-- Meals Table -->
            <h2>Meals List</h2>
            <form id="bulkActionForm" action="meals.php" method="POST">
                <a href="meals.php?export=true" class="btn btn-success mb-3"><i class="fas fa-file-excel"></i> Export to Excel</a>
                <table class="table table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <!-- <th><input type="checkbox" id="selectAll"></th> -->
                            <th>Meal ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Malady</th>
                            <th>Category</th>
                            <th>Price (MAD)</th>
                            <th>Stock</th> <!-- Add stock column -->
                            <th>Image</th>
                            <th>Created At</th>
                            <th>Updated At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <!-- <td><input type="checkbox" name="selected_meals[]" value="<?php echo $row['meal_id']; ?>"></td> -->
                                <td><?php echo $row['meal_id']; ?></td>
                                <td><?php echo $row['name']; ?></td>
                                <td><?php echo $row['description']; ?></td>
                                <td><?php echo $row['malady_name']; ?></td>
                                <td><?php echo $row['category_name']; ?></td>
                                <td><?php echo $row['price']; ?></td>
                                <td><?php echo $row['stock']; ?></td> <!-- Display stock -->
                                <td><?php if ($row['image']): ?><img src="<?php echo $row['image']; ?>" alt="<?php echo $row['name']; ?>" style="width: 100px;"><?php endif; ?></td>
                                <td><?php echo $row['created_at']; ?></td>
                                <td><?php echo $row['updated_at']; ?></td>
                                <td>
                                    <!-- View Meal Button -->
                                    <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#viewMealModal<?php echo $row['meal_id']; ?>"><i class="fas fa-eye"></i> View</button>

                                    <!-- Edit Meal Button -->
                                    <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editMealModal<?php echo $row['meal_id']; ?>"><i class="fas fa-edit"></i> Edit</button>

                                    <!-- Delete Meal Link -->
                                    <a href="meals.php?delete_meal=<?php echo $row['meal_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this meal?');"><i class="fas fa-trash"></i> Delete</a>
                                </td>
                            </tr>

                            <!-- View Meal Modal -->
                            <div class="modal fade" id="viewMealModal<?php echo $row['meal_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="viewMealModalLabel<?php echo $row['meal_id']; ?>" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="viewMealModalLabel<?php echo $row['meal_id']; ?>">Meal Details</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>Name:</strong> <?php echo $row['name']; ?></p>
                                            <p><strong>Description:</strong> <?php echo $row['description']; ?></p>
                                            <p><strong>Category:</strong> <?php echo $row['category_name']; ?></p>
                                            <p><strong>Malady:</strong> <?php echo $row['malady_name']; ?></p>
                                            <p><strong>Price (MAD):</strong> <?php echo $row['price']; ?></p>
                                            <p><strong>Stock:</strong> <?php echo $row['stock']; ?></p> <!-- Display stock -->
                                            <?php if ($row['image']): ?>
                                                <p><strong>Image:</strong><br><img src="<?php echo $row['image']; ?>" alt="<?php echo $row['name']; ?>" style="width: 100px;"></p>
                                            <?php endif; ?>
                                            <p><strong>Created At:</strong> <?php echo $row['created_at']; ?></p>
                                            <p><strong>Updated At:</strong> <?php echo $row['updated_at']; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Edit Meal Modal -->
                            <div class="modal fade" id="editMealModal<?php echo $row['meal_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editMealModalLabel<?php echo $row['meal_id']; ?>" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editMealModalLabel<?php echo $row['meal_id']; ?>">Edit Meal</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <form action="meals.php" method="POST" enctype="multipart/form-data">
                                                <input type="hidden" name="meal_id" value="<?php echo $row['meal_id']; ?>">
                                                <input type="hidden" name="existing_image" value="<?php echo $row['image']; ?>">
                                                <div class="form-group">
                                                    <label for="name">Name:</label>
                                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo $row['name']; ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="description">Description:</label>
                                                    <textarea class="form-control" id="description" name="description" rows="3" required><?php echo $row['description']; ?></textarea>
                                                </div>
                                                <div class="form-group">
                                                    <label for="malady_id">Malady:</label>
                                                    <select class="form-control" id="malady_id" name="malady_id" required>
                                                        <?php
                                                        // Fetch maladies again for the edit modal
                                                        $maladiesResultEdit = $conn->query("SELECT malady_id, name FROM maladies");
                                                        while ($malady = $maladiesResultEdit->fetch_assoc()): ?>
                                                            <option value="<?php echo $malady['malady_id']; ?>" <?php if ($row['malady_id'] == $malady['malady_id']) echo 'selected'; ?>><?php echo $malady['name']; ?></option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="category_id">Category:</label>
                                                    <select class="form-control" id="category_id" name="category_id" required>
                                                        <?php
                                                        // Fetch categories again for the edit modal
                                                        $categoriesResultEdit = $conn->query("SELECT category_id, name FROM categories");
                                                        while ($category = $categoriesResultEdit->fetch_assoc()): ?>
                                                            <option value="<?php echo $category['category_id']; ?>" <?php if ($row['category_id'] == $category['category_id']) echo 'selected'; ?>><?php echo $category['name']; ?></option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="price">Price (MAD):</label>
                                                    <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo $row['price']; ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="stock">Stock:</label> <!-- Add stock field -->
                                                    <input type="number" class="form-control" id="stock" name="stock" value="<?php echo $row['stock']; ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="image">Image:</label>
                                                    <input type="file" class="form-control" id="image" name="image" onchange="previewEditImage(event, <?php echo $row['meal_id']; ?>)">
                                                    <img id="imageEditPreview<?php echo $row['meal_id']; ?>" src="<?php echo $row['image']; ?>" alt="Image Preview" style="display:block; width: 100px; margin-top: 10px;">
                                                </div>
                                                <button type="submit" class="btn btn-primary" name="edit_meal"><i class="fas fa-save"></i> Save changes</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <!-- <button type="submit" name="delete_selected" class="btn btn-danger"><i class="fas fa-trash"></i> Delete Selected</button> -->
            </form>

            <!-- Pagination Controls -->
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    <li class="page-item <?php if($page <= 1) echo 'disabled'; ?>">
                        <a class="page-link" href="<?php if($page <= 1) echo '#'; else echo "?page=" . ($page - 1); ?>">Previous</a>
                    </li>
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php if($page == $i) echo 'active'; ?>">
                        <a class="page-link" href="meals.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?php if($page >= $total_pages) echo 'disabled'; ?>">
                        <a class="page-link" href="<?php if($page >= $total_pages) echo '#'; else echo "?page=" . ($page + 1); ?>">Next</a>
                    </li>
                </ul>
            </nav>
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
    });

    // Image preview for add form
    function previewImage(event) {
        var reader = new FileReader();
        reader.onload = function(){
            var output = document.getElementById('imagePreview');
            output.src = reader.result;
            output.style.display = 'block';
        };
        reader.readAsDataURL(event.target.files[0]);
    }

    // Image preview for edit form
    function previewEditImage(event, mealId) {
        var reader = new FileReader();
        reader.onload = function(){
            var output = document.getElementById('imageEditPreview' + mealId);
            output.src = reader.result;
            output.style.display = 'block';
        };
        reader.readAsDataURL(event.target.files[0]);
    }

    // Select all checkboxes
    document.getElementById('selectAll').addEventListener('click', function(event) {
        var checkboxes = document.querySelectorAll('input[name="selected_meals[]"]');
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = event.target.checked;
        });
    });
</script>
</body>
</html>
