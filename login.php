<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if user exists
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            echo "Login successful! Welcome, " . $user['username'] . ". <a href='timeline.php'>Go to your profile</a>";
        } else {
            echo "Incorrect password!";
        }
    } else {
        echo "No user found with that email address!";
    }

    $stmt->close();
    $conn->close();
}
?>
