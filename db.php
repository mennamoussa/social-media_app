<?php
// Database credentials
$host = 'localhost'; // Your database host (usually localhost)
$db_name = 'social-media_db'; // Your database name
$username = 'root'; // Your database username
$password = ''; // Your database password (empty if not set)

try {
    $conn = new mysqli($host, $username, $password, $db_name);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>
