<?php
require 'config/db.php';

if (isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    
    // Get user's malady
    $userMaladyQuery = $conn->query("SELECT malady_id FROM users WHERE user_id = '$user_id'");
    $userMalady = $userMaladyQuery->fetch_assoc()['malady_id'];
    
    // Fetch meals for the user's malady
    $mealsQuery = $conn->query("SELECT meal_id, name, price FROM meals WHERE malady_id = '$userMalady'");
    
    while ($meal = $mealsQuery->fetch_assoc()) {
        echo '<option value="' . $meal['meal_id'] . '" data-price="' . $meal['price'] . '">' . $meal['name'] . '</option>';
    }
}
?>
