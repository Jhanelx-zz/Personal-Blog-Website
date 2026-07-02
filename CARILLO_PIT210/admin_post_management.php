<?php
if(!isset($conn)) include 'dbconn.php';

// 1. Handle Category Filtering Logic
$filter = isset($_GET['cat']) ? $_GET['cat'] : 'all';

$sql_filter = "";
if ($filter === 'video') {
    $sql_filter = "WHERE m.file_type = 'video'";
} elseif ($filter === 'image') {
    $sql_filter = "WHERE m.file_type = 'image'";
}

// Fetch media with comment count and category filter
$query = "SELECT m.*, COUNT(c.id) as comment_count 
          FROM public.media m 
          LEFT JOIN public.comments c ON m.id = c.media_id 
          $sql_filter
          GROUP BY m.id ORDER BY m.id DESC";
          
$result = pg_query($conn, $query);
?>

<div class="dashboard-header">
    <h1 style="color: white; margin-bottom: 10px;">Community <span>Posts</span></h1>
    <p style="color: var(--muted); margin-bottom: 30px;">Moderate and manage content uploaded to the feed.</p>
</div>

<div style="display: flex; gap: 10px; margin-bottom: 30px;">
    <a href="home.php?view=posts&cat=all" 
       style="padding: 10px 20px; border-radius: 12px; text-decoration: none; font-size: 13px; font-weight: 600; transition: 0.3s;
       <?= $filter === 'all' ? 'background: var(--accent); color: #000;' : 'background: var(--panel); color: var(--gray); border: 1px solid var(--border);' ?>">
       All Posts
    </a>
    <a href="home.php?view=posts&cat=image" 
       style="padding: 10px 20px; border-radius: 12px; text-decoration: none; font-size: 13px; font-weight: 600; transition: 0.3s;
       <?= $filter === 'image' ? 'background: var(--accent); color: #000;' : 'background: var(--panel); color: var(--gray); border: 1px solid var(--border);' ?>">
       Images Only
    </a>
    <a href="home.php?view=posts&cat=video" 
       style="padding: 10px 20px; border-radius: 12px; text-decoration: none; font-size: 13px; font-weight: 600; transition: 0.3s;
       <?= $filter === 'video' ? 'background: var(--accent); color: #000;' : 'background: var(--panel); color: var(--gray); border: 1px solid var(--border);' ?>">
       Videos Only
    </a>
</div>

<div class="stat-grid" style="margin-bottom: 30px;">
    <div class="stat-card">
        <h3>Showing <?= ucfirst($filter); ?> Posts</h3>
        <p><?= pg_num_rows($result); ?></p>
    </div>
</div>

<div class="card" style="padding: 0; overflow: hidden; border: 1px solid var(--border);">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="background: rgba(255,255,255,0.02); color: var(--muted); font-size: 11px; text-transform: uppercase;">
                <th style="padding: 20px;">Media</th>
                <th style="padding: 20px;">Title</th>
                <th style="padding: 20px;">Type</th>
                <th style="padding: 20px;">Comments</th>
                <th style="padding: 20px; text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if(pg_num_rows($result) > 0): ?>
                <?php while($post = pg_fetch_assoc($result)): ?>
                <tr style="border-bottom: 1px solid var(--border); transition: 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.01)'" onmouseout="this.style.background='transparent'">
                    <td style="padding: 15px;">
                        <?php if($post['file_type'] === 'video'): ?>
                            <div style="width: 60px; height: 40px; background: #000; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 9px; color: var(--accent); font-weight: 700; border: 1px solid rgba(158, 255, 0, 0.3);">MP4</div>
                        <?php else: ?>
                            <img src="<?= $post['file_path'] ?>" style="width: 60px; height: 40px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border);">
                        <?php endif; ?>
                    </td>
                    <td style="padding: 20px; color: white; font-weight: 500;"><?= htmlspecialchars($post['title']) ?></td>
                    <td style="padding: 20px;">
                        <span style="font-size: 10px; padding: 4px 8px; border-radius: 6px; font-weight: 700; 
                            <?= $post['file_type'] === 'video' ? 'background: rgba(158, 255, 0, 0.1); color: var(--accent);' : 'background: rgba(255,255,255,0.05); color: var(--gray);' ?>">
                            <?= strtoupper($post['file_type']) ?>
                        </span>
                    </td>
                    <td style="padding: 20px; color: var(--accent); font-weight: 700;"><?= $post['comment_count'] ?></td>
                    <td style="padding: 20px; text-align: right;">
                        <a href="edit_post.php?id=<?= $post['id'] ?>" 
                           style="color: var(--gray); text-decoration: none; font-size: 13px; margin-right: 15px;">Edit Post</a>
                        
                        <a href="delete_post.php?id=<?= $post['id'] ?>" 
                           style="color: var(--danger); text-decoration: none; font-size: 13px;"
                           onclick="return confirm('Delete this post and all its comments?')">Remove Post</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="padding: 40px; text-align: center; color: var(--muted);">No <?= $filter ?> posts found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>