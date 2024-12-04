<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login_form.php");
    exit();
}

// Fetch all posts with user details and like counts
$sql = "SELECT posts.*, users.username, users.profile_pic, 
               (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count,
               (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) AS user_liked,
               (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) AS comment_count
        FROM posts 
        JOIN users ON posts.user_id = users.id
        ORDER BY posts.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Social Media App</title>
    <link rel="stylesheet" href="timeline-style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <nav>
        <div class="navbar">
            <a href="timeline.php">Home</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="main-container">
        <!-- Post Creation Form -->
        <div class="post-form-container">
            <form action="create_post.php" method="POST" class="post-form">
                <textarea name="content" placeholder="What's on your mind?" required></textarea>
                <button type="submit">Post</button>
            </form>
        </div>

        <!-- Timeline Posts -->
        <div class="timeline">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($post = $result->fetch_assoc()): ?>
                    <div class="post">
                        <div class="post-header">
                            <?php
                            $profile_pic = ($post['profile_pic'] === 'default.png') ? 'assets/images/default-avatar.png' : $post['profile_pic'];
                            ?>
                            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture" class="profile-pic">
                            <h3><?php echo htmlspecialchars($post['username']); ?></h3>
                            <span><?php echo date("F j, Y, g:i a", strtotime($post['created_at'])); ?></span>
                        </div>
                        <div class="post-content">
                            <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                        </div>
                        <div class="post-actions">
                            <div class="likes-comments-count">
                                <span class="like-count"><?php echo $post['like_count']; ?> Likes</span>
                                <span class="comment-count"><?php echo $post['comment_count']; ?> Comments</span>
                            </div>
                            <div class="action-icons">
                                <!-- Like Button -->
                                <button 
                                    class="like-button" 
                                    data-post-id="<?php echo $post['id']; ?>" 
                                    data-liked="<?php echo $post['user_liked']; ?>">
                                    <?php echo $post['user_liked'] ? 'Liked' : 'Like'; ?>
                                </button>
                                <!-- View Comments Button -->
                                <button class="view-comments-button" data-post-id="<?php echo $post['id']; ?>">View Comments</button>
                            </div>
                        </div>
                        <!-- Comment Section (Initially Hidden) -->
                        <div class="comments-section" id="comments-<?php echo $post['id']; ?>" style="display: none;">
                            <div class="comments-list">
                                <?php
                                // Fetch comments for the current post
                                $comment_sql = "SELECT comments.*, users.username, users.profile_pic FROM comments 
                                                JOIN users ON comments.user_id = users.id 
                                                WHERE comments.post_id = ?
                                                ORDER BY comments.created_at ASC";
                                $comment_stmt = $conn->prepare($comment_sql);
                                $comment_stmt->bind_param("i", $post['id']);
                                $comment_stmt->execute();
                                $comment_result = $comment_stmt->get_result();
                                ?>
                                <?php while ($comment = $comment_result->fetch_assoc()): ?>
                                    <div class="comment">
                                        <?php
                                        $profile_pic = ($comment['profile_pic'] === 'default.png') ? 'assets/images/default-avatar.png' : $comment['profile_pic'];
                                        ?>
                                        <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture" class="profile-pic">
                                        <strong><?php echo htmlspecialchars($comment['username']); ?>:</strong>
                                        <span><?php echo date("F j, Y, g:i a", strtotime($comment['created_at'])); ?></span>
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
                <p>No posts yet. Be the first to post!</p>
            <?php endif; ?>
        </div>

    </div>

    <button class="back-to-top" onclick="scrollToTop()">↑</button>

    <script>

        const backToTopButton = document.querySelector(".back-to-top");

        window.addEventListener("scroll", () => {
            if (window.scrollY > 300) {
                backToTopButton.style.display = "block";
            } else {
                backToTopButton.style.display = "none";
            }
        });

        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: "smooth" });
        }

        // like button
        $(document).on("click", ".like-button", function () {
            const button = $(this);
            const postId = button.data("post-id");
            const liked = button.data("liked"); 

            $.ajax({
                url: "like_post.php",
                type: "POST",
                data: {
                    post_id: postId,
                    liked: liked,
                },
                success: function (response) {
                    response = JSON.parse(response);

                    if (response.status === "liked") {
                        button.text("Liked"); // Change button text
                        button.data("liked", 1); // Update liked status
                    } else if (response.status === "unliked") {
                        button.text("Like"); // Change button text
                        button.data("liked", 0); // Update liked status
                    }

                    // Update the like count
                    button.closest(".post-actions")
                        .find(".like-count")
                        .text(response.like_count + " Likes");
                },
                error: function () {
                    alert("An error occurred. Please try again.");
                },
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

        // Handle comment submission
        $(document).on("click", ".submit-comment", function() {
            const postId = $(this).data("post-id");
            const commentText = $("#comment-text-" + postId).val();

            if (commentText.trim() === "") return;

            $.ajax({
                url: "add_comment.php",
                type: "POST",
                data: { post_id: postId, comment_text: commentText },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.status === "success") {
                        // Add the new comment
                        const newComment = `
                            <div class="comment">
                                <img src="${data.profile_pic}" alt="Profile Picture" class="profile-pic">
                                <strong>${data.username}:</strong>
                                <span>${data.created_at}</span> <!-- Display the comment date -->
                                <p>${data.comment_text}</p>
                            </div>
                        `;
                        $("#comments-" + postId + " .comments-list").prepend(newComment);
                        $("#comment-text-" + postId).val(""); // Clear the comment box
                        // Update the like count
                        button.closest(".post-actions")
                                .find(".comment-count")
                                .text(response.comment_count + " Comments");
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
