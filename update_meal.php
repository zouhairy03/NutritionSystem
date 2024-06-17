
<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $meal_id = $_POST['meal_id'];
    $name = $_POST['name'];
    $stock = $_POST['stock'];
    $category = $_POST['category'];

    $sql = "UPDATE meals SET name = ?, stock = ?, category = ? WHERE meal_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siii", $name, $stock, $category, $meal_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Meal updated successfully.";
    } else {
        $_SESSION['error'] = "Failed to update meal.";
    }

    header("Location: inventory.php");
    exit();
}
?>
