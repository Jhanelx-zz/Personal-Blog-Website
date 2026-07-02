<?php
session_start();
include 'dbconn.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: user_home.php");
    exit();
}

$comment_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'User';

// Fetch the existing comment
// If Admin, they can fetch any comment; if User, only their own
if ($user_role === 'Admin') {
    $res = pg_query_params($conn, "SELECT * FROM public.comments WHERE id = $1", array($comment_id));
} else {
    $res = pg_query_params($conn, "SELECT * FROM public.comments WHERE id = $1 AND username = $2", array($comment_id, $user_id));
}

$comment = pg_fetch_assoc($res);

if (!$comment) {
    die("Unauthorized or comment not found.");
}

// Handle the update request
if (isset($_POST['update_comment'])) {
    $new_text = $_POST['comment_text'];
    $update = pg_query_params($conn, "UPDATE public.comments SET comment_text = $1 WHERE id = $2", array($new_text, $comment_id));
    
    if ($update) {
        // IMPROVEMENT: Redirect based on role to maintain workflow
        if ($user_role === 'Admin') {
            // Redirects Admin back to the Moderate Comments page
            header("Location: home.php?view=comments");
        } else {
            // Redirects regular users back to the post details
            header("Location: post_details.php?id=" . $comment['media_id']);
        }
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Comment | MyBlog</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; height: 100vh; background: var(--bg); margin: 0; }
        .edit-card { background: var(--panel); padding: 40px; border-radius: 24px; border: 1px solid var(--border); width: 100%; max-width: 500px; }
        textarea { 
            width: 100%; background: var(--input-bg); border: 1px solid var(--border); 
            color: white; padding: 15px; border-radius: 12px; height: 150px; 
            margin-bottom: 20px; font-family: inherit; resize: none;
        }
    </style>
</head>
<body>
    <div class="edit-card">
        <h2 style="color: white; margin-bottom: 10px;">Edit Comment</h2>
        <p style="color: var(--muted); margin-bottom: 25px; font-size: 14px;">Modify the text below and save your changes.</p>
        
        <form method="POST">
            <textarea name="comment_text" required><?= htmlspecialchars($comment['comment_text']) ?></textarea>
            
            <button type="submit" name="update_comment" class="btn-primary" style="width: 100%; padding: 16px; border: none; border-radius: 12px; cursor: pointer; font-weight: 700;">Save Changes</button>
            
            <?php 
            // Dynamic Cancel Link
            $cancel_url = ($user_role === 'Admin') ? "home.php?view=comments" : "post_details.php?id=" . $comment['media_id'];
            ?>
            <a href="<?= $cancel_url ?>" style="display: block; text-align: center; margin-top: 20px; color: var(--muted); text-decoration: none; font-size: 14px;">Cancel and Go Back</a>
        </form>
    </div>
</body>
</html>