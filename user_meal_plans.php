<?php
session_start();
include 'config/db.php'; // Database connection file

// Check if the user is logged in, otherwise redirect to the login page
if (!isset($_SESSION['user'])) {
    header('Location: user_login.php');
    exit();
}

// Fetch all meals with category names and malady types
$meals_query = "SELECT meals.meal_id, meals.name, meals.description, categories.name AS category_name, maladies.name AS malady_name, meals.price, meals.image 
                FROM meals 
                JOIN categories ON meals.category = categories.category_id
                JOIN maladies ON meals.malady_id = maladies.malady_id";
$meals_result = $conn->query($meals_query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Meal Plans</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            display: flex;
        }
        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background-color: #343a40;
            padding-top: 20px;
            transition: transform 0.3s ease;
        }
        .sidebar.hidden {
            transform: translateX(-250px);
        }
        .sidebar a {
            color: white;
            padding: 15px;
            text-decoration: none;
            display: block;
        }
        .sidebar a:hover {
            background-color: #575d63;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
            width: 100%;
            transition: margin-left 0.3s ease;
        }
        .content.expanded {
            margin-left: 0;
        }
        .toggle-btn {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1000;
        }
        .img-thumbnail {
            width: 100px; /* Adjusted for better visibility */
            height: 100px;
        }
    </style>
</head>
<body>
    <button class="btn btn-primary toggle-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
    <div class="sidebar" id="sidebar">
        <a href="user_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="user_profile.php"><i class="fas fa-user"></i> Profile</a>
        <a href="user_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
        <a href="user_meal_plans.php"><i class="fas fa-clipboard-list"></i> Meal Plans</a>
        <a href="user_malady.php"><i class="fas fa-notes-medical"></i> Malady</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
    <div class="content" id="content">
        <div class="container">
            <div class="row justify-content-center mt-5">
                <div class="col-md-12">
                    <h1 class="text-center">Available Meals</h1>

                    <?php if ($meals_result->num_rows == 0): ?>
                        <p class="text-center">There are no meals available.</p>
                    <?php else: ?>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Meal ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Category</th>
                                    <th>Malady</th>
                                    <th>Price (MAD)</th>
                                    <th>Image</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($meal = $meals_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($meal['meal_id']); ?></td>
                                        <td><?php echo htmlspecialchars($meal['name']); ?></td>
                                        <td><?php echo htmlspecialchars($meal['description']); ?></td>
                                        <td><?php echo htmlspecialchars($meal['category_name']); ?></td>
                                        <td><?php echo htmlspecialchars($meal['malady_name']); ?></td>
                                        <td><?php echo htmlspecialchars($meal['price']); ?></td>
                                        <td>
                                            <?php if ($meal['image']): ?>
                                                <img src="uploads/<?php echo htmlspecialchars($meal['image']); ?>" alt="<?php echo htmlspecialchars($meal['name']); ?>" class="img-thumbnail">
                                            <?php else: ?>
                                                No image
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-success btn-sm" onclick="orderMeal(<?php echo $meal['meal_id']; ?>)"><i class="fas fa-shopping-cart"></i> Order</button>
                                            <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#viewMealModal<?php echo $meal['meal_id']; ?>"><i class="fas fa-eye"></i> View</button>
                                        </td>
                                    </tr>

                                    <!-- View Meal Modal -->
                                    <div class="modal fade" id="viewMealModal<?php echo $meal['meal_id']; ?>" tabindex="-1" aria-labelledby="viewMealModalLabel<?php echo $meal['meal_id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="viewMealModalLabel<?php echo $meal['meal_id']; ?>">View Meal Details</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="form-group">
                                                        <label for="mealName<?php echo $meal['meal_id']; ?>">Name</label>
                                                        <input type="text" class="form-control" id="mealName<?php echo $meal['meal_id']; ?>" value="<?php echo htmlspecialchars($meal['name']); ?>" readonly>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="mealDescription<?php echo $meal['meal_id']; ?>">Description</label>
                                                        <textarea class="form-control" id="mealDescription<?php echo $meal['meal_id']; ?>" readonly><?php echo htmlspecialchars($meal['description']); ?></textarea>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="mealCategory<?php echo $meal['meal_id']; ?>">Category</label>
                                                        <input type="text" class="form-control" id="mealCategory<?php echo $meal['meal_id']; ?>" value="<?php echo htmlspecialchars($meal['category_name']); ?>" readonly>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="mealMalady<?php echo $meal['meal_id']; ?>">Malady</label>
                                                        <input type="text" class="form-control" id="mealMalady<?php echo $meal['meal_id']; ?>" value="<?php echo htmlspecialchars($meal['malady_name']); ?>" readonly>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="mealPrice<?php echo $meal['meal_id']; ?>">Price (MAD)</label>
                                                        <input type="text" class="form-control" id="mealPrice<?php echo $meal['meal_id']; ?>" value="<?php echo htmlspecialchars($meal['price']); ?>" readonly>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="mealImage<?php echo $meal['meal_id']; ?>">Image</label>
                                                        <?php if ($meal['image']): ?>
                                                            <img src="uploads/<?php echo htmlspecialchars($meal['image']); ?>" alt="<?php echo htmlspecialchars($meal['name']); ?>" class="img-fluid">
                                                        <?php else: ?>
                                                            No image
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            var sidebar = document.getElementById('sidebar');
            var content = document.getElementById('content');
            sidebar.classList.toggle('hidden');
            content.classList.toggle('expanded');
        }

        function orderMeal(mealId) {
            window.location.href = 'user_orders.php?add_order=true&meal_id=' + mealId;
        }
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
