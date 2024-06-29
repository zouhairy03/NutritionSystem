<?php
session_start();

// Check if the user is logged in, otherwise redirect to the login page
if (!isset($_SESSION['user'])) {
    header('Location: user_login.php');
    exit();
}

// Retrieve user information from the session
$user = $_SESSION['user'];
$malady_id = $user['malady_id'] ?? null;

// Fetch malady details based on malady_id
include 'config/db.php';

$malady = null;
if ($malady_id !== null) {
    $malady_query = "SELECT * FROM maladies WHERE malady_id = ?";
    $stmt = $conn->prepare($malady_query);
    $stmt->bind_param("i", $malady_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $malady = $result->fetch_assoc();
    $stmt->close();
}

// Fetch list of all maladies
$all_maladies_query = "SELECT malady_id, name FROM maladies";
$all_maladies_result = $conn->query($all_maladies_query);

// Handle malady selection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['select_malady'])) {
    $selected_malady_id = $_POST['malady_id'];

    // Update the user's malady_id
    $update_user_malady_query = "UPDATE users SET malady_id = ? WHERE user_id = ?";
    $stmt = $conn->prepare($update_user_malady_query);
    $stmt->bind_param("ii", $selected_malady_id, $user['user_id']);
    $stmt->execute();
    $stmt->close();

    // Update the session with the new malady_id
    $_SESSION['user']['malady_id'] = $selected_malady_id;

    // Refresh the page to show the selected malady
    header("Location: user_malady.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Malady</title>
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
        <div class="container mt-5">
            <h1 class="text-center">Your Malady</h1>
            <?php if ($malady): ?>
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($malady['name']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($malady['description']); ?></p>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-center">No malady information found.</p>
            <?php endif; ?>

            <button type="button" class="btn btn-primary mt-4" data-toggle="modal" data-target="#selectMaladyModal">
                Select Malady
            </button>
        </div>

        <!-- Select Malady Modal -->
        <div class="modal fade" id="selectMaladyModal" tabindex="-1" aria-labelledby="selectMaladyModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="selectMaladyModalLabel">Select Malady</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form method="POST" action="user_malady.php">
                        <div class="modal-body">
                            <input type="hidden" name="select_malady" value="1">
                            <div class="form-group">
                                <label for="malady_id">Malady</label>
                                <select name="malady_id" id="malady_id" class="form-control" required>
                                    <?php while ($row = $all_maladies_result->fetch_assoc()): ?>
                                        <option value="<?php echo $row['malady_id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Select Malady</button>
                        </div>
                    </form>
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
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
