<?php
session_start();
include 'dbconn.php'; 
include 'functions.php'; // Required for displaying real-time indicators

// SECURITY: Redirect to login if NO session exists
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_email = $_SESSION['user_id'];

// --- NOTIFICATION LOGIC ---
// Fetch unread notifications for the current user
$notif_query = "
    SELECT c.id, c.comment_text, c.username, m.title as post_title, m.id as post_id, r.student_fname, r.student_lname
    FROM public.comments c
    JOIN public.media m ON c.media_id = m.id
    JOIN public.registration r ON c.username = r.student_email
    WHERE c.is_read = FALSE AND c.username != $1
    ORDER BY c.id DESC LIMIT 5";

$notif_res = pg_query_params($conn, $notif_query, array($user_email));
$unread_count = pg_num_rows($notif_res);

// --- SEARCH & FILTER LOGIC ---
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$sql_filter = "";
$params = [];
if (!empty($search)) {
    $sql_filter .= " WHERE title ILIKE $1";
    $params[] = "%$search%";
}

if ($filter !== 'all') {
    $prefix = empty($sql_filter) ? " WHERE " : " AND ";
    $param_index = count($params) + 1;
    $sql_filter .= $prefix . "file_type = $" . $param_index;
    $params[] = $filter;
}

// Fetch Feed Content with Filter
$media_query = "SELECT * FROM public.media $sql_filter ORDER BY id DESC";
$media_res = pg_query_params($conn, $media_query, $params);

// Fetch User Data
$user_res = pg_query_params($conn, "SELECT * FROM registration WHERE student_email = $1", array($user_email));
$user_info = pg_fetch_assoc($user_res);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MyBlog — Community Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css"> 
    <style>
        .feed-container { 
            max-width: 1200px; 
            margin: 40px auto; 
            padding: 0 20px; 
            display: grid; 
            grid-template-columns: 1fr 320px; 
            gap: 30px; 
        }
        .main-feed-column { display: flex; flex-direction: column; gap: 25px; }
        .post-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); 
            gap: 20px; 
        }
        .sidebar-column { display: flex; flex-direction: column; gap: 25px; }
        
        /* Refined Sidebar Styles */
        .sidebar-sticky-wrapper {
            position: sticky;
            top: 20px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .sidebar-section.card {
            padding: 25px;
            border: 1px solid var(--border);
            background: var(--panel);
        }
        .sidebar-title {
            color: var(--accent);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 20px;
            font-weight: 700;
            border-bottom: 1px solid var(--border);
            padding-bottom: 10px;
        }
        .trending-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .trending-item {
            text-decoration: none;
            display: flex;
            flex-direction: column;
            transition: 0.3s ease;
        }
        .trend-tag {
            color: var(--white);
            font-size: 14px;
            font-weight: 500;
            transition: 0.3s;
        }
        .trend-stats {
            color: var(--muted);
            font-size: 11px;
            margin-top: 4px;
        }
        .trending-item:hover .trend-tag {
            color: var(--accent);
            transform: translateX(5px);
        }
        .sidebar-text {
            font-size: 13px;
            color: var(--gray);
            line-height: 1.6;
        }
        .rules-list {
            list-style: none;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .rules-list li {
            font-size: 13px;
            color: var(--gray);
            position: relative;
            padding-left: 15px;
        }
        .rules-list li::before {
            content: "•";
            color: var(--accent);
            position: absolute;
            left: 0;
            font-weight: bold;
        }
        
        /* Avatar Style */
        .user-avatar {
            width: 35px; height: 35px; background: var(--accent); 
            border-radius: 50%; display: flex; align-items: center; 
            justify-content: center; color: black; font-weight: 800; font-size: 14px;
        }

        /* Notification Styles */
        .notif-container { position: relative; }
        .notif-btn { background: none; border: none; color: white; cursor: pointer; position: relative; font-size: 20px; }
        .badge { background: var(--danger); color: white; font-size: 10px; padding: 2px 7px; border-radius: 50px; font-weight: 700; position: absolute; top: -5px; right: -5px; }
        .notif-dropdown { display: none; position: absolute; right: 0; top: 40px; width: 300px; background: var(--panel); border: 1px solid var(--border); border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.4); z-index: 1000; overflow: hidden; }
        .notif-item { display: block; padding: 15px; text-decoration: none; border-bottom: 1px solid rgba(255,255,255,0.03); transition: 0.3s; }
        .notif-item:hover { background: rgba(158, 255, 0, 0.05); }

        @media (max-width: 1000px) {
            .feed-container { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?= display_flash_message(); ?>

<header>
    <nav style="height: 70px; max-width: 100%; padding: 0 40px;">
        <div class="nav-left">
            <a href="user_home.php" class="nav-logo">My<span>Blog</span></a>
        </div>
        
        <div class="nav-right" style="display: flex; align-items: center; gap: 20px;">
            <div class="notif-container">
                <button class="notif-btn" onclick="toggleNotifs()">
                    🔔 <?php if($unread_count > 0): ?><span class="badge"><?= $unread_count ?></span><?php endif; ?>
                </button>
                <div id="notif-dropdown" class="notif-dropdown">
                    <div style="padding: 12px 15px; font-size: 11px; font-weight: 700; color: var(--muted); border-bottom: 1px solid var(--border);">NOTIFICATIONS</div>
                    <?php if($unread_count > 0): ?>
                        <?php while($n = pg_fetch_assoc($notif_res)): ?>
                            <a href="post_details.php?id=<?= $n['post_id'] ?>&mark_read=<?= $n['id'] ?>" class="notif-item">
                                <p style="margin:0; font-size:12px; color:var(--white);">
                                    <strong><?= htmlspecialchars($n['student_fname']) ?></strong> replied to a post you interacted with: <span style="color:var(--accent);">"<?= htmlspecialchars($n['post_title']) ?>"</span>
                                </p>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="padding:30px; text-align:center; color:var(--muted); font-size:12px;">No new notifications</div>
                    <?php endif; ?>
                </div>
            </div>

            <a href="user_home.php" style="color: var(--accent); text-decoration: none; font-size: 13px; font-weight: 600; letter-spacing: 0.5px;">HOME</a>
            
            <div class="user-avatar" style="width: 35px; height: 35px; background: var(--accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: black; font-weight: 800; font-size: 14px;">
                <?= strtoupper(substr($user_info['student_fname'], 0, 1)) ?>
            </div>
            
            <span style="color: var(--white); font-size: 14px; font-weight: 500;">
                Welcome, <?= htmlspecialchars($user_info['student_fname'] . '  ' . $user_info['student_lname']) ?>
            </span>
            
            <a href="edit_profile.php" style="color: var(--gray); text-decoration: none; font-size: 14px;">Settings</a>
            
            <a href="logout.php" class="logout-pill" style="padding: 6px 20px; border: 1px solid var(--border); color: var(--white); border-radius: 50px; text-decoration: none; font-size: 13px; font-weight: 600;">Logout</a>
        </div>
    </nav>
</header>

<main class="feed-container">
    <div class="main-feed-column">
        <div class="card" style="padding: 40px; background: radial-gradient(circle at top right, rgba(158, 255, 0, 0.05), transparent);">
            <h1 style="color: white; margin-bottom: 10px;">
                <?php 
                    $hour = date('H');
                    if($hour < 12) echo "Good Morning";
                    else if($hour < 18) echo "Good Afternoon";
                    else echo "Good Evening";
                ?>, <?= htmlspecialchars($user_info['student_fname']) ?>!
            </h1>
            <p style="color: var(--muted);">There are <?= pg_num_rows($media_res) ?> new posts for you to explore today.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
            <div class="card" style="text-align: center; padding: 20px;">
                <h3 style="font-size: 10px; text-transform: uppercase; color: var(--muted); letter-spacing: 1px;">My Comments</h3>
                <p style="font-size: 24px; color: var(--accent); font-weight: 700;">
                    <?php 
                        $c_res = pg_query_params($conn, "SELECT COUNT(*) FROM comments WHERE username = $1", [$user_email]);
                        echo pg_fetch_result($c_res, 0, 0);
                    ?>
                </p>
            </div>
            <div class="card" style="text-align: center; padding: 20px;">
                <h3 style="font-size: 10px; text-transform: uppercase; color: var(--muted); letter-spacing: 1px;">Feed Posts</h3>
                <p style="font-size: 24px; color: white; font-weight: 700;"><?= pg_num_rows($media_res) ?></p>
            </div>
            <div class="card" style="text-align: center; padding: 20px;">
                <h3 style="font-size: 10px; text-transform: uppercase; color: var(--muted); letter-spacing: 1px;">Status</h3>
                <p style="font-size: 24px; color: #50fa7b; font-weight: 700;">● Online</p>
            </div>
        </div>

        <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
            <form method="GET" style="flex-grow: 1;">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search titles..." 
                       style="width: 100%; padding: 12px 20px; background: var(--input-bg); border: 1px solid var(--border); border-radius: 50px; color: white;">
            </form>
            <div style="display: flex; gap: 8px;">
                <a href="user_home.php?filter=all" class="btn-outline-pill <?= $filter=='all'?'active':'' ?>">All</a>
                <a href="user_home.php?filter=image" class="btn-outline-pill <?= $filter=='image'?'active':'' ?>">Photos</a>
                <a href="user_home.php?filter=video" class="btn-outline-pill <?= $filter=='video'?'active':'' ?>">Videos</a>
            </div>
        </div>

        <div class="post-grid">
            <?php while($post = pg_fetch_assoc($media_res)): ?>
                <?php 
                    // Check if this post is already favorited by the user
                    $fav_check = pg_query_params($conn, "SELECT 1 FROM favorites WHERE student_email = $1 AND media_id = $2", [$user_email, $post['id']]);
                    $is_fav = pg_num_rows($fav_check) > 0;
                ?>
                <article class="blog-post">
                    <div class="media-box" style="position: relative;">
                        <button class="favorite-btn <?= $is_fav ? 'active' : '' ?>" 
                                title="Add to Favorites" 
                                onclick="toggleFavorite(<?= $post['id'] ?>, this)">
                            ❤
                        </button>
                        
                        <?php if($post['file_type'] === 'video'): ?>
                            <video src="<?= $post['file_path'] ?>" muted loop onmouseover="this.play()" onmouseout="this.pause()"></video>
                        <?php else: ?>
                            <img src="<?= $post['file_path'] ?>" alt="Content">
                        <?php endif; ?>
                    </div>
                    
                    <div class="post-header">
                        <span class="post-title"><?= htmlspecialchars($post['title']) ?></span>
                        <p style="color: var(--accent); font-size: 11px; margin-top: 5px; font-weight: 700;">BY ADMIN</p>
                    </div>

                    <div class="post-footer" style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 12px; color: var(--muted); display: flex; align-items: center; gap: 5px;">
                            💬 <?php 
                                $count_res = pg_query_params($conn, "SELECT COUNT(*) FROM comments WHERE media_id = $1", [$post['id']]);
                                echo pg_fetch_result($count_res, 0, 0);
                            ?>
                        </span>
                        <a href="post_details.php?id=<?= $post['id'] ?>" class="view-btn">View Details →</a>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
    </div>

    <aside class="sidebar-column">
        <div class="sidebar-sticky-wrapper">
            <div class="sidebar-section card">
                <h4 class="sidebar-title">Trending Posts</h4>
                <div class="trending-list">
                    <?php 
                        $trend = pg_query($conn, "SELECT m.id, m.title, COUNT(c.id) as cc FROM media m LEFT JOIN comments c ON m.id = c.media_id GROUP BY m.id ORDER BY cc DESC LIMIT 3");
                        while($t = pg_fetch_assoc($trend)): 
                    ?>
                        <a href="post_details.php?id=<?= $t['id'] ?>" class="trending-item">
                            <span class="trend-tag"># <?= htmlspecialchars($t['title']) ?></span>
                            <span class="trend-stats"><?= $t['cc'] ?> interactions</span>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="sidebar-section card">
                <h4 class="sidebar-title">Announcements</h4>
                <p class="sidebar-text">Welcome to your community hub. Check back daily for new uploads and discussions.</p>
            </div>

            <div class="sidebar-section card">
                <h4 class="sidebar-title">Community Rules</h4>
                <ul class="rules-list">
                    <li>Be respectful to others.</li>
                    <li>No inappropriate content.</li>
                    <li>Admins monitor all comments.</li>
                </ul>
            </div>
        </div>
    </aside>
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

function toggleFavorite(mediaId, btn) {
    const formData = new FormData();
    formData.append('media_id', mediaId);

    fetch('toggle_favorite.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        if (data.trim() === 'added') {
            btn.classList.add('active');
        } else if (data.trim() === 'removed') {
            btn.classList.remove('active');
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>

</body>
</html>