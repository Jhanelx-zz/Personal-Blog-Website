<?php
session_start();
include 'dbconn.php';
include 'functions.php'; // Added to access the indicator functions

// Security check: Ensure only Admin can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

// --- NOTIFICATION LOGIC (For Sidebar Consistency) ---
$notif_query = "
    SELECT c.id, c.comment_text, c.username, m.title as post_title, m.id as post_id, r.student_fname, r.student_lname
    FROM public.comments c
    JOIN public.media m ON c.media_id = m.id
    JOIN public.registration r ON c.username = r.student_email
    WHERE c.is_read = FALSE AND c.username != $1
    ORDER BY c.id DESC LIMIT 5";

$notif_res = pg_query_params($conn, $notif_query, array($_SESSION['user_id']));
$unread_count = pg_num_rows($notif_res);

$msg = '';
$is_error = false;

if (isset($_POST['upload_media'])) {
    $title = $_POST['title'];
    $description = $_POST['description']; // Get description from form
    $file = $_FILES['media_file'];
    $target_dir = "uploads/";
    
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $is_error = true;
        $msg = "Upload failed (Error Code: " . $file['error'] . "). Check file size limits.";
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $path = $target_dir . time() . '_' . uniqid() . '.' . $ext;
        
        $video_extensions = ['mp4', 'webm', 'ogg', 'mov'];
        $type = (in_array($ext, $video_extensions) || strpos($file['type'], 'video') !== false) ? 'video' : 'image';

        if (move_uploaded_file($file['tmp_name'], $path)) {
            // UPDATED: Added description to the INSERT query
            $result = pg_query_params($conn, "INSERT INTO public.media (title, description, file_path, file_type) VALUES ($1, $2, $3, $4)", array($title, $description, $path, $type));
            if ($result) {
                set_flash_message("New media published successfully!"); 
                header("Location: admin_upload.php");
                exit();
            } else {
                $msg = "Database error: " . pg_last_error($conn);
                $is_error = true;
            }
        } else {
            $msg = "Error moving file to uploads folder.";
            $is_error = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Content | Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        :root { --sidebar-w: 280px; }
        body { display: flex; background: var(--bg); min-height: 100vh; margin: 0; }
        
        .sidebar { width: var(--sidebar-w); background: var(--sidebar); border-right: 1px solid var(--border); position: fixed; height: 100vh; padding: 40px 20px; display: flex; flex-direction: column; z-index: 100; }
        .main-content { margin-left: var(--sidebar-w); width: calc(100% - var(--sidebar-w)); padding: 60px; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; }
        
        .nav-group { flex-grow: 1; margin-top: 40px; }
        .nav-item { display: flex; align-items: center; padding: 16px 20px; color: var(--gray); text-decoration: none; border-radius: 12px; margin-bottom: 8px; transition: 0.3s; font-weight: 500; }
        .nav-item:hover, .nav-item.active { background: rgba(158, 255, 0, 0.1); color: var(--accent); }
        
        .notif-container { position: relative; margin-bottom: 20px; padding: 0 20px; }
        .notif-btn { background: var(--panel); border: 1px solid var(--border); color: white; padding: 12px; border-radius: 12px; width: 100%; cursor: pointer; display: flex; align-items: center; justify-content: space-between; transition: 0.3s; }
        .badge { background: var(--danger); color: white; font-size: 10px; padding: 2px 7px; border-radius: 50px; font-weight: 700; }
        .notif-dropdown { display: none; position: absolute; left: 20px; top: 60px; width: 300px; background: var(--panel); border: 1px solid var(--border); border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.4); z-index: 1000; overflow: hidden; }
        .notif-item { display: block; padding: 15px; text-decoration: none; border-bottom: 1px solid rgba(255,255,255,0.03); transition: 0.3s; }
        .notif-item:hover { background: rgba(158, 255, 0, 0.05); }

        .upload-card { width: 100%; max-width: 600px; background: var(--panel); border: 1px solid var(--border); padding: 40px; border-radius: 24px; }
        input[type="text"], input[type="file"], textarea { width: 100%; padding: 14px; background: var(--input-bg); border: 1px solid var(--border); color: white; border-radius: 12px; margin-bottom: 20px; box-sizing: border-box; font-family: inherit; }
        textarea { resize: vertical; min-height: 100px; }
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
                <div style="padding: 12px 15px; font-size: 11px; font-weight: 700; color: var(--muted); border-bottom: 1px solid var(--border);">RECENT COMMENTS</div>
                <?php if($unread_count > 0): ?>
                    <?php while($n = pg_fetch_assoc($notif_res)): ?>
                        <a href="post_details.php?id=<?= $n['post_id'] ?>&mark_read=<?= $n['id'] ?>" class="notif-item">
                            <p style="margin:0; font-size:12px; color:var(--white);">
                                <strong><?= htmlspecialchars($n['student_fname']) ?></strong> commented on <span style="color:var(--accent);">"<?= htmlspecialchars($n['post_title']) ?>"</span>
                            </p>
                        </a>
                    <?php endwhile; ?>
                    <a href="home.php?view=comments" style="display:block; padding:12px; text-align:center; font-size:11px; color:var(--accent); text-decoration:none; background: rgba(158, 255, 0, 0.02);">View All Comments</a>
                <?php else: ?>
                    <div style="padding:30px; text-align:center; color:var(--muted); font-size:12px;">No new notifications</div>
                <?php endif; ?>
            </div>
        </div>

        <a href="home.php?view=users" class="nav-item">User Management</a>
        <a href="home.php?view=posts" class="nav-item">Manage Posts</a>
        <a href="home.php?view=comments" class="nav-item">Moderate Comments</a>
        <a href="admin_upload.php" class="nav-item active">Upload New Media</a>
    </div>

    <div class="logout-box">
        <p style="font-size: 12px; color: var(--muted); padding: 0 20px; margin-bottom: 10px;">User: <span style="color: var(--white);"><?= htmlspecialchars($_SESSION['username'] ?? $_SESSION['user_id']) ?></span></p>
        <a href="logout.php" class="nav-item" style="color: var(--danger);">Logout</a>
    </div>
</div>

<main class="main-content">
    <div style="margin-bottom: 40px; text-align: center;">
        <h1 style="color: white; margin-bottom: 10px;">Upload <span>Media</span></h1>
        <p style="color: var(--muted);">Publish new photos or videos to the community feed.</p>
    </div>

    <div class="upload-card">
        <?php if($msg): ?>
            <p style="color: <?= $is_error ? 'var(--danger)' : 'var(--accent)' ?>; margin-bottom: 20px; text-align: center; font-weight: 600;"><?= $msg ?></p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <label style="display:block; margin-bottom:10px; font-size:11px; font-weight:700; color:var(--white); text-transform:uppercase;">Post Title</label>
            <input type="text" name="title" required placeholder="Enter a descriptive title...">
            
            <label style="display:block; margin-bottom:10px; font-size:11px; font-weight:700; color:var(--white); text-transform:uppercase;">Description</label>
            <textarea name="description" placeholder="Write a brief description or context for this post..."></textarea>
            
            <label style="display:block; margin-bottom:10px; font-size:11px; font-weight:700; color:var(--white); text-transform:uppercase;">Select File (Image or Video)</label>
            <input type="file" name="media_file" accept="image/*,video/*" required>
            
            <button type="submit" name="upload_media" class="btn-primary" style="width:100%; padding:18px; font-size:16px; border:none; border-radius:12px; cursor:pointer; font-weight: 700;">Publish to Community</button>
        </form>
    </div>
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