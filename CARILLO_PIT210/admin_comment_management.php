<?php
if(!isset($conn)) include 'dbconn.php';

// Handle Admin Replies to specific comments
if (isset($_POST['admin_reply'])) {
    $media_id = $_POST['media_id'];
    $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
    $reply_text = "ADMIN: " . $_POST['reply_text'];
    $admin_user = $_SESSION['user_id'];
    
    pg_query_params($conn, "INSERT INTO public.comments (media_id, username, comment_text, parent_id) VALUES ($1, $2, $3, $4)", 
        array($media_id, $admin_user, $reply_text, $parent_id));
}

// Fetch all media posts to organize comments by post
$query = "SELECT * FROM public.media ORDER BY id DESC";
$result = pg_query($conn, $query);
?>

<div class="dashboard-header">
    <h1 style="color: white; margin-bottom: 10px;">Moderate <span>Comments</span></h1>
    <p style="color: var(--muted); margin-bottom: 40px;">Manage interactions. You can only edit your own ADMIN replies.</p>
</div>

<?php while($post = pg_fetch_assoc($result)): ?>
<div class="card" style="margin-bottom: 30px; padding: 25px;">
    <div style="display: flex; gap: 20px; align-items: start;">
        <div style="width: 120px; text-align: center;">
            <span style="font-size: 10px; color: var(--accent); text-transform: uppercase; font-weight: 700;"><?= $post['file_type'] ?></span>
            <img src="<?= $post['file_path'] ?>" style="width: 100%; height: 80px; object-fit: cover; border-radius: 8px; margin-top: 5px; border: 1px solid var(--border);">
        </div>
        
        <div style="flex-grow: 1;">
            <h3 style="color: white; margin-bottom: 15px;"><?= htmlspecialchars($post['title']) ?></h3>
            
            <div style="background: rgba(255,255,255,0.02); border-radius: 12px; padding: 15px;">
                <?php 
                // 1. Fetch only TOP-LEVEL comments
                $c_res = pg_query_params($conn, "SELECT * FROM public.comments WHERE media_id = $1 AND parent_id IS NULL ORDER BY id ASC", array($post['id']));
                
                if(pg_num_rows($c_res) == 0) echo "<p style='color:var(--muted); font-size:12px;'>No comments yet.</p>";
                
                while($comment = pg_fetch_assoc($c_res)): ?>
                    <div style="margin-bottom: 12px; font-size: 13px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 8px;">
                        <div style="display: flex; justify-content: space-between;">
                            <div>
                                <span style="color: var(--accent); font-weight: 600;">@<?= htmlspecialchars($comment['username']) ?>:</span> 
                                <span style="color: var(--gray);"><?= htmlspecialchars($comment['comment_text']) ?></span>
                            </div>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <button onclick="toggleReplyForm(<?= $comment['id'] ?>)" style="background:none; border:none; color:var(--accent); cursor:pointer; font-size:11px; font-family:inherit;">Reply</button>
                                
                                <?php if (strpos($comment['comment_text'], 'ADMIN:') === 0): ?>
                                    <a href="edit_comment.php?id=<?= $comment['id'] ?>" style="color: var(--gray); text-decoration: none; font-size: 11px;">Edit</a>
                                <?php endif; ?>

                                <a href="delete_comment.php?id=<?= $comment['id'] ?>" style="color: var(--danger); text-decoration: none; font-size: 11px;" onclick="return confirm('Delete?')">Delete</a>
                            </div>
                        </div>

                        <?php 
                        $r_res = pg_query_params($conn, "SELECT * FROM public.comments WHERE parent_id = $1 ORDER BY id ASC", array($comment['id']));
                        while($reply = pg_fetch_assoc($r_res)): ?>
                            <div style="margin-left: 30px; margin-top: 10px; padding-left: 10px; border-left: 1px solid var(--accent); display: flex; justify-content: space-between;">
                                <div>
                                    <span style="color: var(--accent); font-size: 12px;">@<?= htmlspecialchars($reply['username']) ?>:</span>
                                    <span style="color: white; font-size: 12px;"><?= htmlspecialchars($reply['comment_text']) ?></span>
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <?php if (strpos($reply['comment_text'], 'ADMIN:') === 0): ?>
                                        <a href="edit_comment.php?id=<?= $reply['id'] ?>" style="color: var(--gray); text-decoration: none; font-size: 10px;">Edit</a>
                                    <?php endif; ?>

                                    <a href="delete_comment.php?id=<?= $reply['id'] ?>" style="color: var(--danger); text-decoration: none; font-size: 10px;" onclick="return confirm('Delete?')">Delete</a>
                                </div>
                            </div>
                        <?php endwhile; ?>

                        <form method="POST" id="reply-form-<?= $comment['id'] ?>" style="display: none; margin-top: 10px; gap: 10px;">
                            <input type="hidden" name="media_id" value="<?= $post['id'] ?>">
                            <input type="hidden" name="parent_id" value="<?= $comment['id'] ?>">
                            <input type="text" name="reply_text" placeholder="Reply to @<?= htmlspecialchars($comment['username']) ?>..." required style="flex-grow: 1; padding: 8px; background: var(--input-bg); border: 1px solid var(--border); border-radius: 8px; color: white; font-size: 12px;">
                            <button type="submit" name="admin_reply" class="btn-primary" style="padding: 5px 15px; border-radius: 8px; font-size: 11px; border: none; cursor: pointer;">Post</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>

            <form method="POST" style="margin-top: 15px; display: flex; gap: 10px;">
                <input type="hidden" name="media_id" value="<?= $post['id'] ?>">
                <input type="text" name="reply_text" placeholder="Post an admin response..." required style="flex-grow: 1; padding: 10px; background: var(--input-bg); border: 1px solid var(--border); border-radius: 8px; color: white; font-size: 13px;">
                <button type="submit" name="admin_reply" class="btn-primary" style="padding: 10px 20px; border-radius: 8px; font-size: 12px; border: none; cursor: pointer;">Reply</button>
            </form>
        </div>
    </div>
</div>
<?php endwhile; ?>

<script>
function toggleReplyForm(commentId) {
    const form = document.getElementById('reply-form-' + commentId);
    form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'flex' : 'none';
}
</script>