<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require 'config/db.php';

// Handle Search
$search_query = "";
if (isset($_POST['search'])) {
    $search_query = $_POST['search_query'];
}

// Handle Add Malady
if (isset($_POST['add_malady'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];

    $sql = "INSERT INTO maladies (name, description, created_at, updated_at) VALUES ('$name', '$description', NOW(), NOW())";
    $conn->query($sql);
    header("Location: maladies.php");
}

// Handle Edit Malady
if (isset($_POST['edit_malady'])) {
    $malady_id = $_POST['malady_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];

    $sql = "UPDATE maladies SET name='$name', description='$description', updated_at=NOW() WHERE malady_id='$malady_id'";
    $conn->query($sql);
    header("Location: maladies.php");
}

// Handle Delete Malady
$delete_error = "";
if (isset($_GET['delete_malady'])) {
    $malady_id = $_GET['delete_malady'];

    // Check for related meals
    $related_meals_query = "SELECT COUNT(*) AS count FROM meals WHERE malady_id='$malady_id'";
    $related_meals_result = $conn->query($related_meals_query);
    $related_meals_count = $related_meals_result->fetch_assoc()['count'];

    if ($related_meals_count > 0) {
        $delete_error = "Cannot delete malady. There are meals associated with this malady.";
    } else {
        $sql = "DELETE FROM maladies WHERE malady_id='$malady_id'";
        $conn->query($sql);
        header("Location: maladies.php");
    }
}

// Fetch maladies data
$search_sql = $search_query ? "WHERE name LIKE '%$search_query%' OR description LIKE '%$search_query%'" : "";
$sql = "SELECT * FROM maladies $search_sql";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Maladies</title>
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
        .modal .modal-dialog {
            max-width: 800px;
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
            <li><a href="delivers.php"><i class="fas fa-people-carry"></i> Deliver Personnel</a></li>
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
            <h1><i class="fas fa-notes-medical"></i> Manage Maladies</h1>

            <?php if ($delete_error): ?>
                <div class="alert alert-danger"><?php echo $delete_error; ?></div>
            <?php endif; ?>

            <!-- Search Form -->
            <form action="maladies.php" method="POST" class="form-inline mb-4">
                <input type="text" class="form-control mr-sm-2" name="search_query" placeholder="Search" value="<?php echo $search_query; ?>">
                <button type="submit" class="btn btn-primary" name="search"><i class="fas fa-search"></i> Search</button>
            </form>

            <!-- Add Malady Button -->
            <button class="btn btn-success mb-4" data-toggle="modal" data-target="#addMaladyModal"><i class="fas fa-plus"></i> Add Malady</button>

            <!-- Maladies Table -->
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Malady ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Created At</th>
                        <th>Updated At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['malady_id']; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['description']; ?></td>
                            <td><?php echo $row['created_at']; ?></td>
                            <td><?php echo $row['updated_at']; ?></td>
                            <td>
                                <!-- Edit Malady Button -->
                                <button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editMaladyModal<?php echo $row['malady_id']; ?>"><i class="fas fa-edit"></i> Edit</button>

                                <!-- Delete Malady Link -->
                                <a href="maladies.php?delete_malady=<?php echo $row['malady_id']; ?>" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</a>
                            </td>
                        </tr>

                        <!-- Edit Malady Modal -->
                        <div class="modal fade" id="editMaladyModal<?php echo $row['malady_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editMaladyModalLabel<?php echo $row['malady_id']; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editMaladyModalLabel<?php echo $row['malady_id']; ?>">Edit Malady</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <form action="maladies.php" method="POST">
                                            <input type="hidden" name="malady_id" value="<?php echo $row['malady_id']; ?>">
                                            <div class="form-group">
                                                <label for="name">Name:</label>
                                                <input type="text" class="form-control" id="name" name="name" value="<?php echo $row['name']; ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="description">Description:</label>
                                                <textarea class="form-control" id="description" name="description" rows="3" required><?php echo $row['description']; ?></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary" name="edit_malady"><i class="fas fa-save"></i> Save changes</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Malady Modal -->
<div class="modal fade" id="addMaladyModal" tabindex="-1" role="dialog" aria-labelledby="addMaladyModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMaladyModalLabel">Add Malady</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="maladies.php" method="POST">
                    <div class="form-group">
                        <label for="name">Name:</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" name="add_malady"><i class="fas fa-plus"></i> Add Malady</button>
                </form>
            </div>
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
</script>
</body>
</html>
