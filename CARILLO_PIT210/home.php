<?php
session_start();
include 'dbconn.php';
include 'functions.php'; // Required for displaying and setting real-time indicators

// Security check: Ensure only Admin can access this dashboard
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php"); 
    exit();
}

// --- NOTIFICATION LOGIC ---
$notif_query = "
    SELECT c.id, c.comment_text, c.username, m.title as post_title, m.id as post_id, r.student_fname, r.student_lname
    FROM public.comments c
    JOIN public.media m ON c.media_id = m.id
    JOIN public.registration r ON c.username = r.student_email
    WHERE c.is_read = FALSE AND c.username != $1
    ORDER BY c.id DESC LIMIT 5";

$notif_res = pg_query_params($conn, $notif_query, array($_SESSION['user_id']));
$unread_count = pg_num_rows($notif_res);

// Handler for deleting users
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $user_res = pg_query_params($conn, "SELECT student_email FROM public.registration WHERE id = $1", array($delete_id));
    $user_data = pg_fetch_assoc($user_res);
    
    if ($user_data) {
        $email = $user_data['student_email'];
        pg_query_params($conn, "DELETE FROM public.registration WHERE id = $1", array($delete_id));
        pg_query_params($conn, "DELETE FROM public.login WHERE username = $1", array($email));
        
        // FEATURE ADDED: Set indicator for user deletion
        set_flash_message("User account and login credentials removed.", "danger");
    }
    header("Location: home.php?view=users");
    exit();
}

$view = $_GET['view'] ?? 'users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | MyBlog</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        :root { --sidebar-w: 280px; }
        body { display: flex; background: var(--bg); min-height: 100vh; margin: 0; }
        .sidebar { width: var(--sidebar-w); background: var(--sidebar); border-right: 1px solid var(--border); position: fixed; height: 100vh; padding: 40px 20px; display: flex; flex-direction: column; z-index: 100; }
        .main-content { margin-left: var(--sidebar-w); width: calc(100% - var(--sidebar-w)); padding: 60px; }
        .nav-group { flex-grow: 1; margin-top: 40px; }
        .nav-item { display: flex; align-items: center; padding: 16px 20px; color: var(--gray); text-decoration: none; border-radius: 12px; margin-bottom: 8px; transition: 0.3s; font-weight: 500; }
        .nav-item:hover, .nav-item.active { background: rgba(158, 255, 0, 0.1); color: var(--accent); }
        .notif-container { position: relative; margin-bottom: 20px; padding: 0 20px; }
        .notif-btn { background: var(--panel); border: 1px solid var(--border); color: white; padding: 12px; border-radius: 12px; width: 100%; cursor: pointer; display: flex; align-items: center; justify-content: space-between; transition: 0.3s; }
        .notif-btn:hover { border-color: var(--accent); }
        .badge { background: var(--danger); color: white; font-size: 10px; padding: 2px 7px; border-radius: 50px; font-weight: 700; }
        .notif-dropdown { display: none; position: absolute; left: 20px; top: 60px; width: 300px; background: var(--panel); border: 1px solid var(--border); border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.4); z-index: 1000; overflow: hidden; }
        .notif-item { display: block; padding: 15px; text-decoration: none; border-bottom: 1px solid rgba(255,255,255,0.03); transition: 0.3s; }
        .logout-box { padding-top: 20px; border-top: 1px solid var(--border); }
    </style>
</head>
<body>
    <?= display_flash_message(); ?>

<div class="sidebar">
    <a href="index.php" class="nav-logo" style="text-decoration:none;">My<span>Blog</span></a>
    
    <p style="font-size: 11px; color: var(--muted); margin-top: 25px; padding: 0 20px; text-transform: uppercase; letter-spacing: 1px;">Admin Control</p>

    <div class="nav-group">
        <div class="notif-container">
            <button class="notif-btn" onclick="toggleNotifs()">
                <span style="display:flex; align-items:center; gap:10px;">🔔 Alerts</span>
                <?php if($unread_count > 0): ?>
                    <span class="badge"><?= $unread_count ?></span>
                <?php endif; ?>
            </button>
            
            <div id="notif-dropdown" class="notif-dropdown">
                <div style="padding: 12px 15px; font-size: 11px; font-weight: 700; color: var(--muted); border-bottom: 1px solid var(--border); background: rgba(255,255,255,0.01);">RECENT COMMENTS</div>
                
                <?php if($unread_count > 0): ?>
                    <?php while($n = pg_fetch_assoc($notif_res)): ?>
                        <a href="post_details.php?id=<?= $n['post_id'] ?>&mark_read=<?= $n['id'] ?>" class="notif-item">
                            <p style="margin:0; font-size:12px; color:var(--white);">
                                <strong><?= htmlspecialchars($n['student_fname']) ?></strong> commented on <span style="color:var(--accent);">"<?= htmlspecialchars($n['post_title']) ?>"</span>
                            </p>
                            <p style="margin:5px 0 0; font-size:11px; color:var(--muted); line-height:1.4;">
                                <?= substr(htmlspecialchars($n['comment_text']), 0, 35) ?>...
                            </p>
                        </a>
                    <?php endwhile; ?>
                    <a href="home.php?view=comments" style="display:block; padding:12px; text-align:center; font-size:11px; color:var(--accent); text-decoration:none; background: rgba(158, 255, 0, 0.02);">View All Comments</a>
                <?php else: ?>
                    <div style="padding:30px; text-align:center; color:var(--muted); font-size:12px;">No new notifications</div>
                <?php endif; ?>
            </div>
        </div>

        <a href="home.php?view=users" class="nav-item <?= $view == 'users' ? 'active' : '' ?>">User Management</a>
        <a href="home.php?view=posts" class="nav-item <?= $view == 'posts' ? 'active' : '' ?>">Manage Posts</a>
        <a href="home.php?view=comments" class="nav-item <?= $view == 'comments' ? 'active' : '' ?>">Moderate Comments</a>
        <a href="admin_upload.php" class="nav-item">Upload New Media</a>
    </div>
    
    <div class="logout-box">
        <p style="font-size: 12px; color: var(--muted); padding: 0 20px; margin-bottom: 10px;">User: <span style="color: var(--white);"><?= htmlspecialchars($_SESSION['username']) ?></span></p>
        <a href="logout.php" class="nav-item" style="color: var(--danger);">Logout</a>
    </div>
</div>

<main class="main-content">
    <?php
    if ($view == 'posts') {
        include 'admin_post_management.php';
    } elseif ($view == 'comments') {
        include 'admin_comment_management.php';
    } else {
        include 'user_management.php';
    }
    ?>
</main>

<script>
function toggleNotifs() {
    const dropdown = document.getElementById('notif-dropdown');
    dropdown.style.display = (dropdown.style.display === 'block') ? 'none' : 'block';
}

window.onclick = function(event) {
    if (!event.target.closest('.notif-container')) {
        document.getElementById('notif-dropdown').style.display = 'none';
    }
}
</script>

</body>
</html>