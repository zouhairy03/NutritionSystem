<?php
// config/db.php
$servername = "localhost";
$username = "root";  // Adjust as necessary
$password = "root";  // Adjust as necessary
$dbname = "NutritionSystemDB";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
