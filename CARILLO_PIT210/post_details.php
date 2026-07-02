<?php
session_start();
include 'dbconn.php'; 
include 'functions.php'; // Required for real-time indicator functions

// Security check: Ensure user is logged in
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: user_home.php");
    exit();
}

$post_id = $_GET['id'];
$user_email = $_SESSION['user_id']; 
$full_name = $_SESSION['full_name'] ?? 'User';

// --- NOTIFICATION HANDLER ---
if (isset($_GET['mark_read'])) {
    $comment_to_read = $_GET['mark_read'];
    pg_query_params($conn, "UPDATE public.comments SET is_read = TRUE WHERE id = $1", array($comment_to_read));
}

// Fetch the specific post details
$post_res = pg_query_params($conn, "SELECT * FROM public.media WHERE id = $1", array($post_id));
$post = pg_fetch_assoc($post_res);

if (!$post) {
    header("Location: user_home.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($post['title']) ?> | MyBlog</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css"> 
    <style>
        body { font-family: 'Outfit', sans-serif; background: var(--bg); color: var(--gray); margin: 0; }
        
        header { 
            background: rgba(10,10,11,0.95); 
            backdrop-filter: blur(10px); 
            border-bottom: 1px solid var(--border); 
            position: sticky; 
            top: 0; 
            z-index: 1000; 
            width: 100%;
        }
        nav { 
            width: 100%; 
            margin: 0; 
            padding: 0 40px; 
            height: 70px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            box-sizing: border-box;
        }
        .nav-left { display: flex; align-items: center; gap: 15px; }
        .nav-right { display: flex; align-items: center; gap: 20px; font-size: 14px; }
        .nav-logo { font-size: 22px; font-weight: 700; color: var(--white); text-decoration: none; }
        .nav-logo span { color: var(--accent); }
        
        .user-avatar-circle {
            width: 35px; height: 35px; background: var(--accent); 
            color: #000; border-radius: 50%; display: flex; 
            align-items: center; justify-content: center; font-weight: 700; font-size: 14px;
        }

        .details-wrapper { 
            max-width: 1100px; 
            margin: 15px auto; 
            display: grid; 
            grid-template-columns: 1fr 300px; 
            gap: 15px; 
            padding: 0 20px;
        }

        .post-main-card { 
            background: var(--panel); 
            border: 1px solid var(--border); 
            border-radius: 12px; 
            overflow: hidden; 
            height: fit-content;
        }
        .media-container {
            width: 100%;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            max-height: 450px; 
        }
        .post-media { width: 100%; max-height: 450px; object-fit: contain; }
        
        .post-info { padding: 25px 30px; border-top: 1px solid var(--border); }
        .post-title { font-size: 24px; color: var(--white); margin: 0 0 8px 0; }
        .post-meta { font-size: 12px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); padding-bottom: 15px; margin-bottom: 15px; }
        
        /* New Style for Description */
        .post-description { 
            color: var(--gray); 
            line-height: 1.8; 
            font-size: 15px; 
            white-space: pre-wrap; 
            margin-top: 15px;
        }

        .comments-sidebar { 
            background: var(--panel); 
            border: 1px solid var(--border); 
            border-radius: 12px; 
            display: flex;
            flex-direction: column;
            height: calc(100vh - 100px); 
            position: sticky;
            top: 85px;
        }
        .comments-header { padding: 12px 18px; border-bottom: 1px solid var(--border); }
        .comments-list { 
            flex: 1; 
            overflow-y: auto; 
            padding: 12px 18px; 
            scrollbar-width: thin;
        }
        .comment-input-area { padding: 12px; border-top: 1px solid var(--border); }

        .comment-item { margin-bottom: 12px; font-size: 12.5px; padding-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.03); }
        .comment-author { color: var(--accent); font-weight: 600; }
        .comment-text { color: var(--white); margin: 2px 0; line-height: 1.35; }
        .comment-actions { display: flex; gap: 8px; margin-top: 2px; }
        
        .reply-item { 
            margin-left: 12px; 
            margin-top: 6px; 
            padding-left: 8px; 
            border-left: 1px solid var(--accent); 
        }

        .input-group { display: flex; gap: 6px; }
        .input-group input { 
            flex: 1; background: var(--input-bg); border: 1px solid var(--border); 
            padding: 8px 12px; border-radius: 6px; color: white; outline: none; font-size: 12px;
        }

        .action-btn-small { color: var(--muted); text-decoration: none; font-size: 10.5px; transition: 0.2s; background:none; border:none; cursor:pointer; padding:0; }
        .action-btn-small:hover { color: var(--accent); }

        .reply-form-toggle { display: none; margin-top: 10px; }

        @media (max-width: 900px) {
            .details-wrapper { grid-template-columns: 1fr; }
            .comments-sidebar { height: auto; position: static; }
        }
    </style>
</head>
<body>
    <?= display_flash_message(); ?>

<header>
    <nav>
        <div class="nav-left">
            <a href="user_home.php" class="nav-logo">My<span>Blog</span></a>
        </div>
        
        <div class="nav-right">
            <a href="user_home.php" style="color: var(--accent); text-decoration: none; font-size: 13px; font-weight: 600; letter-spacing: 0.5px;">HOME</a>
            <div class="user-avatar-circle">
                <?= strtoupper(substr($full_name, 0, 1)) ?>
            </div>
            <span style="color: var(--white); font-weight: 500;">Welcome, <?= htmlspecialchars($full_name) ?></span>
            <a href="edit_profile.php" style="color: var(--gray); text-decoration: none;">Settings</a>
            <a href="logout.php" class="btn-outline-pill" style="padding: 6px 20px; font-size: 13px; border-radius: 50px; border: 1px solid var(--border); color: white; text-decoration: none;">Logout</a>
        </div>
    </nav>
</header>

<div class="details-wrapper">
    <main class="post-main-card">
        <div class="media-container">
            <?php if($post['file_type'] === 'video'): ?>
                <video controls src="<?= $post['file_path'] ?>" class="post-media"></video>
            <?php else: ?>
                <img src="<?= $post['file_path'] ?>" class="post-media">
            <?php endif; ?>
        </div>
        <div class="post-info">
            <h1 class="post-title"><?= htmlspecialchars($post['title']) ?></h1>
            <div class="post-meta">
                Posted by Admin • <?= date("M d, Y", strtotime($post['uploaded_at'] ?? 'now')) ?>
            </div>
            
            <div class="post-description">
                <?= nl2br(htmlspecialchars($post['description'] ?? 'No description provided.')) ?>
            </div>
        </div>
    </main>

    <aside class="comments-sidebar">
        <div class="comments-header">
            <h3 style="margin: 0; font-size: 15px; color: white; font-weight: 600;">Comments</h3>
        </div>

        <div class="comments-list">
            <?php
            $comment_res = pg_query_params($conn, "SELECT * FROM public.comments WHERE media_id = $1 AND parent_id IS NULL ORDER BY id DESC", array($post_id));
            while($comment = pg_fetch_assoc($comment_res)): ?>
                <div class="comment-item">
                    <span class="comment-author">@<?= htmlspecialchars($comment['username']) ?></span>
                    <p class="comment-text"><?= htmlspecialchars($comment['comment_text']) ?></p>
                    
                    <div class="comment-actions">
                        <button class="action-btn-small" onclick="toggleReply(<?= $comment['id'] ?>)">Reply</button>
                        <?php if($comment['username'] === $user_email || $_SESSION['role'] === 'Admin'): ?>
                            <a href="edit_comment.php?id=<?= $comment['id'] ?>" class="action-btn-small">Edit</a>
                            <a href="delete_user_comment.php?id=<?= $comment['id'] ?>" class="action-btn-small" style="color:var(--danger);" onclick="return confirm('Delete?')">Delete</a>
                        <?php endif; ?>
                    </div>

                    <form action="add_comment.php" method="POST" id="reply-form-<?= $comment['id'] ?>" class="reply-form-toggle input-group">
                        <input type="hidden" name="media_id" value="<?= $post_id ?>">
                        <input type="hidden" name="parent_id" value="<?= $comment['id'] ?>">
                        <input type="text" name="comment_text" placeholder="Reply..." required>
                        <button type="submit" class="btn-primary" style="padding: 0 8px; font-size: 10px; border-radius: 4px;">Go</button>
                    </form>

                    <?php 
                    $reply_res = pg_query_params($conn, "SELECT * FROM public.comments WHERE parent_id = $1 ORDER BY id ASC", array($comment['id']));
                    while($reply = pg_fetch_assoc($reply_res)): ?>
                        <div class="reply-item">
                            <span class="comment-author" style="font-size: 11px;">@<?= htmlspecialchars($reply['username']) ?></span>
                            <p class="comment-text" style="font-size: 11.5px;"><?= htmlspecialchars($reply['comment_text']) ?></p>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endwhile; ?>
        </div>

        <div class="comment-input-area">
            <form action="add_comment.php" method="POST" class="input-group">
                <input type="hidden" name="media_id" value="<?= $post['id'] ?>">
                <input type="text" name="comment_text" placeholder="Write a comment..." required autocomplete="off">
                <button type="submit" class="btn-primary" style="padding: 0 12px; border-radius: 6px; font-size: 11px; font-weight: 600;">Post</button>
            </form>
        </div>
    </aside>
</div>

<script>
function toggleReply(id) {
    const form = document.getElementById('reply-form-' + id);
    form.style.display = (form.style.display === 'flex') ? 'none' : 'flex';
}
</script>

</body>
</html>