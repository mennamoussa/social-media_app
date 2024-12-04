<?php
require 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $bio = $_POST['bio'];
    $current_profile_pic = $_POST['current_profile_pic']; // The current profile picture path
    $new_profile_pic = $_FILES['profile_pic'];

    // Check if a new file was uploaded
    if ($new_profile_pic['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        $new_pic_name = uniqid() . basename($new_profile_pic['name']);
        $target_file = $upload_dir . $new_pic_name;

        // Move the uploaded file to the server
        if (move_uploaded_file($new_profile_pic['tmp_name'], $target_file)) {
            $profile_pic = $target_file;
        } else {
            echo "Error uploading file.";
            exit();
        }
    } else {
        // No new file uploaded; retain the current picture
        $profile_pic = $current_profile_pic;
    }

    // Update the database
    $sql = "UPDATE users SET username = ?, email = ?, bio = ?, profile_pic = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ssssi", $username, $email, $bio, $profile_pic, $_SESSION['user_id']);

        if ($stmt->execute()) {
            echo "Profile updated successfully!";
            header("Location: profile.php");
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }

    $conn->close();
}
?>
