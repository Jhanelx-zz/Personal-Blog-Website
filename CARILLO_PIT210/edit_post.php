<?php
session_start();
include 'dbconn.php';
include 'functions.php'; // Required for set_flash_message() and display_flash_message()

// Security check: Only Admin can edit posts
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'] ?? null;
// Fetch existing post data
$res = pg_query_params($conn, "SELECT * FROM public.media WHERE id = $1", array($id));
$post = pg_fetch_assoc($res);

if (!$post) {
    die("Post not found.");
}

$msg = '';
if (isset($_POST['update_post'])) {
    $title = $_POST['title'];
    $description = $_POST['description']; // FEATURE ADDED: Get description
    $file = $_FILES['media_file'];
    
    // Default values are the existing data
    $final_path = $post['file_path'];
    $final_type = $post['file_type'];

    // Check if a new file was uploaded
    if (!empty($file['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_path = $target_dir . time() . '_' . uniqid() . '.' . $ext;
        $new_type = (strpos($file['type'], 'video') !== false) ? 'video' : 'image';

        if (move_uploaded_file($file['tmp_name'], $new_path)) {
            // Delete the old file from the server if it exists
            if (file_exists($post['file_path'])) {
                unlink($post['file_path']);
            }
            $final_path = $new_path;
            $final_type = $new_type;
        } else {
            $msg = "Error uploading new file.";
        }
    }

    if (empty($msg)) {
        // FEATURE UPDATED: Added description to the UPDATE query
        $update = pg_query_params($conn, 
            "UPDATE public.media SET title = $1, description = $2, file_path = $3, file_type = $4 WHERE id = $5", 
            array($title, $description, $final_path, $final_type, $id)
        );
        
        if ($update) {
            set_flash_message("Post updated successfully!");
            header("Location: home.php?view=posts");
            exit();
        } else {
            $msg = "Error updating database.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Post | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: var(--bg); margin: 0; flex-direction: column; }
        .edit-card { width: 100%; max-width: 500px; background: var(--panel); border: 1px solid var(--border); padding: 40px; border-radius: 24px; }
        input[type="text"], input[type="file"], textarea { width: 100%; padding: 14px; background: var(--input-bg); border: 1px solid var(--border); color: white; border-radius: 12px; margin-bottom: 20px; box-sizing: border-box; font-family: inherit; }
        textarea { resize: vertical; min-height: 100px; }
        .current-media { margin-bottom: 20px; padding: 10px; background: rgba(255,255,255,0.03); border-radius: 12px; text-align: center; }
    </style>
</head>
<body>

    <?= display_flash_message(); ?>

    <div class="edit-card">
        <h2 style="color: white; margin-bottom: 20px;">Edit Post <span>Details</span></h2>
        
        <?php if($msg): ?><p style="color: var(--danger); margin-bottom: 15px; font-size: 14px;"><?= $msg ?></p><?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <label style="display:block; margin-bottom:10px; font-size:11px; font-weight:700; color:var(--white); text-transform:uppercase;">Post Title</label>
            <input type="text" name="title" value="<?= htmlspecialchars($post['title']) ?>" required>
            
            <label style="display:block; margin-bottom:10px; font-size:11px; font-weight:700; color:var(--white); text-transform:uppercase;">Description</label>
            <textarea name="description" placeholder="Edit description..."><?= htmlspecialchars($post['description'] ?? '') ?></textarea>
            
            <label style="display:block; margin-bottom:10px; font-size:11px; font-weight:700; color:var(--white); text-transform:uppercase;">Current Media</label>
            <div class="current-media">
                <?php if($post['file_type'] === 'video'): ?>
                    <p style="color: var(--accent); font-size: 12px;">Existing Video: <?= basename($post['file_path']) ?></p>
                <?php else: ?>
                    <img src="<?= $post['file_path'] ?>" style="width: 100px; height: 60px; object-fit: cover; border-radius: 6px;">
                <?php endif; ?>
            </div>

            <label style="display:block; margin-bottom:10px; font-size:11px; font-weight:700; color:var(--white); text-transform:uppercase;">Upload New File (Optional)</label>
            <input type="file" name="media_file" accept="image/*,video/*">
            <p style="font-size: 11px; color: var(--muted); margin-top: -15px; margin-bottom: 20px;">Leave empty to keep current file.</p>
            
            <button type="submit" name="update_post" class="btn-primary" style="width:100%; padding:18px; border:none; border-radius:12px; cursor:pointer; font-weight:700;">Save Changes</button>
            <a href="home.php?view=posts" style="display: block; text-align: center; margin-top: 20px; color: var(--muted); text-decoration: none; font-size: 14px;">Cancel and Go Back</a>
        </form>
    </div>
</body>
</html>