<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: delivery_login.php");
    exit();
}
require 'config/db.php';

// Function to fetch data securely
function fetchData($conn, $query, $params = []) {
    $stmt = $conn->prepare($query);
    if ($params) {
        $stmt->bind_param(...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$delivery_id = $_SESSION['id'];

// Fetch deliveries
$deliveriesQuery = "SELECT d.*, o.created_at AS order_date, a.street, a.city, a.state, a.zip_code, a.country 
                    FROM deliveries d 
                    JOIN orders o ON d.order_id = o.order_id 
                    JOIN addresses a ON o.address_id = a.address_id 
                    WHERE d.delivery_person_id = ?
                    ORDER BY d.created_at DESC";
$deliveries = fetchData($conn, $deliveriesQuery, ['i', $delivery_id]);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deliveries</title>
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
        .table-responsive {
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-user-shield"></i> Delivery Dashboard</h3>
        </div>
        <ul class="list-unstyled components">
            <li><a href="delivery_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="delivery_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li class="active"><a href="delivery_deliveries.php"><i class="fas fa-truck"></i> Deliveries</a></li>
            <li><a href="delivery_profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="delivery_notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
            <!-- <li><a href="delivery_settings.php"><i class="fas fa-cogs"></i> Settings</a></li> -->
            <li><a href="delivery_support.php"><i class="fas fa-headset"></i> Support</a></li>
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
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Delivery ID</th>
                            <th>Order Date</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($deliveries) > 0): ?>
                            <?php foreach ($deliveries as $delivery): ?>
                                <tr>
                                    <td><?php echo $delivery['delivery_id']; ?></td>
                                    <td><?php echo $delivery['order_date']; ?></td>
                                    <td><?php echo $delivery['street'] . ', ' . $delivery['city'] . ', ' . $delivery['state'] . ', ' . $delivery['zip_code'] . ', ' . $delivery['country']; ?></td>
                                    <td><?php echo $delivery['status']; ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#updateStatusModal" data-id="<?php echo $delivery['delivery_id']; ?>"><i class="fas fa-edit"></i> Update Status</button>
                                        <button class="btn btn-secondary btn-sm" data-toggle="modal" data-target="#viewDetailsModal" data-id="<?php echo $delivery['delivery_id']; ?>"><i class="fas fa-eye"></i> View Details</button>
                                        <a href="download_invoice.php?delivery_id=<?php echo $delivery['delivery_id']; ?>" class="btn btn-success btn-sm"><i class="fas fa-download"></i> Download Invoice</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No deliveries found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" role="dialog" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="updateStatusForm" method="POST" action="update_status.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">Update Delivery Status</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="delivery_id" id="updateStatusDeliveryId">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="Pending">Pending</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="viewDetailsModal" tabindex="-1" role="dialog" aria-labelledby="viewDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewDetailsModalLabel">Delivery Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="deliveryDetails">
                <!-- Delivery details will be loaded here via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
$(document).ready(function() {
    $('#sidebarCollapse').on('click', function () {
        $('#sidebar').toggleClass('active');
    });

    $('#updateStatusModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var deliveryId = button.data('id');
        var modal = $(this);
        modal.find('#updateStatusDeliveryId').val(deliveryId);
    });

    $('#viewDetailsModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var deliveryId = button.data('id');
        var modal = $(this);
        $.ajax({
            url: 'fetch_delivery_details.php',
            method: 'POST',
            data: { delivery_id: deliveryId },
            success: function(response) {
                modal.find('#deliveryDetails').html(response);
            }
        });
    });
});
</script>
</body>
</html>
