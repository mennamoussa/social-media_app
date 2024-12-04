<?php
session_start();
require 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

// Check if post_id and comment_text are set
if (isset($_POST['post_id']) && isset($_POST['comment_text'])) {
    $post_id = $_POST['post_id'];
    $comment_text = trim($_POST['comment_text']);
    $user_id = $_SESSION['user_id'];

    if (empty($comment_text)) {
        echo json_encode(['status' => 'error', 'message' => 'Comment text cannot be empty']);
        exit();
    }

    $comment_text = htmlspecialchars($comment_text, ENT_QUOTES, 'UTF-8');

    // Insert the comment into the database
    $sql = "INSERT INTO comments (post_id, user_id, comment_text, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $post_id, $user_id, $comment_text);

    if ($stmt->execute()) {
        $created_at = date("F j, Y, g:i a");
        
        $sql_user = "SELECT username, profile_pic FROM users WHERE id = ?";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param("i", $_SESSION['user_id']);
        $stmt_user->execute();
        $stmt_user->bind_result($username, $profile_pic);
        $stmt_user->fetch();
        $stmt_user->close();

        
        echo json_encode([
            'status' => 'success',
            'message' => 'Comment added successfully',
            'username' => $username,
            'profile_pic' => $profile_pic,
            'comment_text' => $comment_text,
            'created_at' => $created_at

        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add comment']);
    }

    
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
}

$conn->close();
?>
