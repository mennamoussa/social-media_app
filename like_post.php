<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
$liked = isset($_POST['liked']) ? intval($_POST['liked']) : 0;

if ($post_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid post ID']);
    exit();
}

if ($liked) {
    // Unlike the post
    $sql = "DELETE FROM likes WHERE user_id = ? AND post_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $post_id);
    $stmt->execute();
    $stmt->close();

    // Return updated like count
    $like_count_query = "SELECT COUNT(*) AS like_count FROM likes WHERE post_id = ?";
    $stmt = $conn->prepare($like_count_query);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $like_count = $result->fetch_assoc()['like_count'];
    $stmt->close();

    echo json_encode(['status' => 'unliked', 'like_count' => $like_count]);
} else {
    // Like the post
    $sql = "INSERT INTO likes (post_id, user_id, created_at) VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $stmt->close();

    // Return updated like count
    $like_count_query = "SELECT COUNT(*) AS like_count FROM likes WHERE post_id = ?";
    $stmt = $conn->prepare($like_count_query);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $like_count = $result->fetch_assoc()['like_count'];
    $stmt->close();

    echo json_encode(['status' => 'liked', 'like_count' => $like_count]);
}
