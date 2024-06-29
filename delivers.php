<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require 'config/db.php';

// Handle form submission for adding or editing delivery personnel
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_person'])) {
    $person_id = $_POST['person_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];

    if ($person_id) {
        // Edit existing person
        $stmt = $conn->prepare("UPDATE delivery_personnel SET name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $email, $phone, $password, $person_id);
    } else {
        // Add new person
        $stmt = $conn->prepare("INSERT INTO delivery_personnel (name, email, phone, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $phone, $password);
    }
    $stmt->execute();
    $stmt->close();

    header("Location: delivers.php");
    exit();
}

// Handle delete request
if (isset($_GET['delete'])) {
    $person_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM delivery_personnel WHERE id = ?");
    $stmt->bind_param("i", $person_id);
    $stmt->execute();
    $stmt->close();

    header("Location: delivers.php");
    exit();
}

// Handle search and filter
$search_query = isset($_POST['search_query']) ? $_POST['search_query'] : '';

// Fetch delivery personnel
$personnel = $conn->query("SELECT * FROM delivery_personnel WHERE name LIKE '%$search_query%' OR email LIKE '%$search_query%' ORDER BY id DESC");

// Handle export to Excel
if (isset($_POST['export_excel'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename=delivery_personnel.xls');
    
    $output = fopen('php://output', 'w');
    fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));
    fputcsv($output, array('ID', 'Name', 'Email', 'Phone', 'Password'));

    $result = $conn->query("SELECT * FROM delivery_personnel");
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
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
    <title>Manage Delivery Personnel</title>
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
            <h2 class="mt-4">Manage Delivery Personnel</h2>
            <button type="button" class="btn btn-success mb-4" data-toggle="modal" data-target="#addEditModal"><i class="fas fa-plus"></i> Add Delivery Personnel</button>
            
            <!-- Search and Filter Form -->
            <form action="delivers.php" method="POST" class="form-inline mb-4">
                <input type="text" class="form-control mr-sm-2" name="search_query" placeholder="Search" value="<?php echo $search_query; ?>">
                <button type="submit" class="btn btn-primary" name="search"><i class="fas fa-search"></i> Search</button>
            </form>
            
            <!-- Export to Excel -->
            <form method="POST" action="delivers.php">
                <button type="submit" name="export_excel" class="btn btn-success mb-4"><i class="fas fa-file-excel"></i> Export to Excel</button>
            </form>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Password</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($person = $personnel->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $person['id']; ?></td>
                                <td><?php echo $person['name']; ?></td>
                                <td><?php echo $person['email']; ?></td>
                                <td><?php echo $person['phone']; ?></td>
                                <td><?php echo $person['password']; ?></td>
                                <td>
                                    <button type="button" class="btn btn-primary btn-sm edit-btn" data-toggle="modal" data-target="#addEditModal"
                                            data-id="<?php echo $person['id']; ?>"
                                            data-name="<?php echo $person['name']; ?>"
                                            data-email="<?php echo $person['email']; ?>"
                                            data-phone="<?php echo $person['phone']; ?>"
                                            data-password="<?php echo $person['password']; ?>"><i class="fas fa-edit"></i> Edit</button>
                                    <a href="delivers.php?delete=<?php echo $person['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this person?');"><i class="fas fa-trash-alt"></i> Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Delivery Personnel Modal -->
<div class="modal fade" id="addEditModal" tabindex="-1" role="dialog" aria-labelledby="addEditModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEditModalLabel">Add Delivery Personnel</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" id="addEditForm">
                    <input type="hidden" id="person_id" name="person_id">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="text" id="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary" name="save_person">Save</button>
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
        var personId = $(this).data('id');
        var name = $(this).data('name');
        var email = $(this).data('email');
        var phone = $(this).data('phone');
        var password = $(this).data('password');

        $("#person_id").val(personId);
        $("#name").val(name);
        $("#email").val(email);
        $("#phone").val(phone);
        $("#password").val(password);
        $("#addEditModalLabel").text('Edit Delivery Personnel');
    });

    $('#addEditModal').on('hidden.bs.modal', function () {
        $("#person_id").val('');
        $("#name").val('');
        $("#email").val('');
        $("#phone").val('');
        $("#password").val('');
        $("#addEditModalLabel").text('Add Delivery Personnel');
    });

    $(document).ready(function () {
        $('#sidebarCollapse').on('click', function () {
            $('#sidebar').toggleClass('active');
        });
    });
</script>
</body>
</html>
