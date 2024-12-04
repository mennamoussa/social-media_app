<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login_form.php");
    exit();
}

// Fetch user details
$user_id = $_SESSION['user_id'];
$sql = "SELECT username, email, bio, profile_pic FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    echo "User not found!";
    exit();
}


// Fetch all posts with user details and like counts
$sql_posts = "SELECT posts.*, 
               (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count,
               (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) AS user_liked,
               (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) AS comment_count
        FROM posts 
        WHERE posts.user_id = ?
        ORDER BY posts.created_at DESC";
$stmt_posts = $conn->prepare($sql_posts);
$stmt_posts->bind_param("ii", $user_id, $user_id);
$stmt_posts->execute();
$posts_result = $stmt_posts->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="profile-style.css">
    <title>Profile Page</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>
<body>
    <div class="navbar">
        <a href="timeline.php">Home</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php" class="logout-button">Logout</a>
    </div>

    <div class="main-container">
        <div class="profile-info">
            <?php
            $profile_pic = ($user['profile_pic'] === 'default.png') ? 'assets/images/default-avatar.png' : $user['profile_pic'];
            ?>
            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture" class="profile-pic">
            <h2>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h2>
            <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
            <p><?php echo htmlspecialchars($user['bio']); ?></p>
            <button class="edit-profile-btn" onclick="openEditModal()">✎ Edit Profile</button>
        </div>

        <div class="user-posts">
            <h3>Your Posts:</h3>
            <?php if ($posts_result->num_rows > 0): ?>
                <?php while ($post = $posts_result->fetch_assoc()): ?>
                    <div class="post">
                        <div class="post-header">
                            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture" class="profile-pic">
                            <h3><?php echo htmlspecialchars($user['username']); ?></h3>
                            <span><?php echo date("F j, Y, g:i a", strtotime($post['created_at'])); ?></span>
                        </div>
                        <div class="post-content">
                            <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                        </div>
                        <div class="post-actions">
                            <div class="likes-comments-count">
                                <span class="like-count"><?php echo $post['like_count'] ?? 0; ?> Likes</span>
                                <span class="comment-count"><?php echo $post['comment_count'] ?? 0; ?> Comments</span>
                            </div>
                            <div class="action-icons">
                                <button 
                                    class="like-button" 
                                    data-post-id="<?php echo $post['id']; ?>" 
                                    data-liked="<?php echo $post['user_liked'] ?? 0; ?>">
                                    <?php echo $post['user_liked'] ? 'Liked' : 'Like'; ?>
                                </button>
                                <button class="view-comments-button" data-post-id="<?php echo $post['id']; ?>">View Comments</button>
                            </div>
                        </div>
                        <div class="comments-section" id="comments-<?php echo $post['id']; ?>" style="display: none;">
                            <div class="comments-list">
                                <?php
                                $comment_sql = "
                                    SELECT comments.*, users.username, users.profile_pic 
                                    FROM comments 
                                    JOIN users ON comments.user_id = users.id 
                                    WHERE comments.post_id = ? 
                                    ORDER BY comments.created_at ASC
                                ";
                                $comment_stmt = $conn->prepare($comment_sql);
                                $comment_stmt->bind_param("i", $post['id']);
                                $comment_stmt->execute();
                                $comment_result = $comment_stmt->get_result();
                                ?>
                                <?php while ($comment = $comment_result->fetch_assoc()): ?>
                                    <div class="comment">
                                        <div class="comment-header">
                                            <?php
                                            $comment_profile_pic = ($comment['profile_pic'] === 'default.png') ? 'assets/images/default-avatar.png' : $comment['profile_pic'];
                                            ?>
                                            <img src="<?php echo htmlspecialchars($comment_profile_pic); ?>" alt="Profile Picture" class="comment-profile-pic">
                                            <strong><?php echo htmlspecialchars($comment['username']); ?>:</strong>
                                            <span><?php echo date("F j, Y, g:i a", strtotime($comment['created_at'])); ?></span>
                                        </div>
                                        <p><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            <div class="comment-input">
                                <textarea class="comment-textarea" placeholder="Write a comment..." id="comment-text-<?php echo $post['id']; ?>"></textarea>
                                <button class="submit-comment" data-post-id="<?php echo $post['id']; ?>">Post Comment</button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No posts to show!</p>
            <?php endif; ?>
        </div>
    </div>
    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Profile</h2>
            <form id="editProfileForm" action="edit_profile.php" method="POST" enctype="multipart/form-data">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                
                <label for="bio">Bio:</label>
                <textarea id="bio" name="bio" rows="3"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                
                 <!-- Display current profile picture -->
                 <div class="current-pic-container">
                    <p>Current Profile Picture:</p>
                    <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Current Profile Picture" class="current-profile-pic">
                </div>
                 
                <label for="profile_pic">Profile Picture:</label>
                <input type="file" id="profile_pic" name="profile_pic" accept="image/*">
                
                <!-- Hidden input to store current profile picture -->
                <input type="hidden" name="current_profile_pic" value="<?php echo htmlspecialchars($profile_pic); ?>">
                
               
                
                <button type="submit" class="save">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal() {
            document.getElementById('editProfileModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editProfileModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('editProfileModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        };

        $(document).on("click", ".like-button", function () {
            const button = $(this);
            const postId = button.data("post-id");
            const liked = button.data("liked");

            $.ajax({
                url: "like_post.php",
                type: "POST",
                data: { post_id: postId, liked: liked },
                success: function (response) {
                    response = JSON.parse(response);
                    button.text(response.status === "liked" ? "Liked" : "Like");
                    button.data("liked", response.status === "liked" ? 1 : 0);
                    button.closest(".post-actions").find(".like-count").text(response.like_count + " Likes");
                },
                error: function () {
                    alert("An error occurred. Please try again.");
                }
            });
        });

        $(document).on("click", ".view-comments-button", function() {
            const postId = $(this).data("post-id");
            const commentsSection = $("#comments-" + postId);

            if (commentsSection.is(":visible")) {
                commentsSection.hide();
                $(this).text("View Comments");
            } else {
                commentsSection.show();
                $(this).text("Hide Comments");
            }
        });

        $(document).on("click", ".submit-comment", function() {
            const postId = $(this).data("post-id");
            const commentText = $("#comment-text-" + postId).val().trim();

            if (commentText === "") return;

            $.ajax({
                url: "add_comment.php",
                type: "POST",
                data: { post_id: postId, comment_text: commentText },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.status === "success") {
                        const newComment = `
                            <div class="comment">
                                <img src="${data.profile_pic}" alt="Profile Picture" class="profile-pic">
                                <strong>${data.username}:</strong>
                                <span>${data.created_at}</span>
                                <p>${data.comment_text}</p>
                            </div>
                        `;
                        $("#comments-" + postId + " .comments-list").prepend(newComment);
                        $("#comment-text-" + postId).val("");
                    } else {
                        alert(data.message);
                    }
                },
                error: function() {
                    alert("Error adding comment.");
                }
            });
        });
    </script>
</body>
</html>
