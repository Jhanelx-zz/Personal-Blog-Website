<?php
session_start(); // Start the session to check if user_id exists
include 'dbconn.php'; // Include database connection

// Fetch Admin Information (assuming 'Admin' role exists in the login table joined with registration)
$admin_query = "SELECT r.* FROM public.registration r 
                JOIN public.login l ON r.student_email = l.username 
                WHERE l.privileged = 'Admin' LIMIT 1";
$admin_res = pg_query($conn, $admin_query);
$admin_info = pg_fetch_assoc($admin_res);

// Fetch Featured Posts from Admin
$featured_query = "SELECT * FROM public.media ORDER BY id DESC LIMIT 3";
$featured_res = pg_query($conn, $featured_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>MyBlog — Home</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --accent:   #9eff00;
            --bg:       #0a0a0b;
            --panel:    #121214;
            --white:    #ffffff;
            --gray:     #dcdcdc;
            --muted:    #666666;
            --border:   rgba(255,255,255,0.1);
        }
        body { font-family: 'Outfit', sans-serif; background: var(--bg); color: var(--gray); }
        header { background: rgba(10,10,11,0.8); backdrop-filter: blur(10px); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; }
        nav { max-width: 1200px; margin: 0 auto; padding: 0 40px; height: 80px; display: flex; align-items: center; justify-content: space-between; }
        .nav-logo { font-size: 24px; font-weight: 700; color: var(--white); text-decoration: none; }
        .nav-logo span { color: var(--accent); }
        .nav-links { display: flex; gap: 30px; list-style: none; }
        .nav-links a { color: var(--gray); text-decoration: none; font-size: 14px; transition: 0.3s; }
        .nav-links a:hover { color: var(--accent); }
        .hero { padding: 120px 40px; text-align: center; background: radial-gradient(circle at center, rgba(158, 255, 0, 0.05) 0%, transparent 70%); }
        .hero h1 { font-size: clamp(40px, 8vw, 72px); color: var(--white); margin-bottom: 20px; line-height: 1.1; }
        .hero h1 span { color: var(--accent); }
        .hero p { max-width: 600px; margin: 0 auto 40px; font-size: 18px; color: var(--muted); }
        .hero-btns { display: flex; justify-content: center; gap: 15px; }
        .btn-primary { padding: 16px 35px; background: var(--accent); color: #000; border-radius: 50px; text-decoration: none; font-weight: 700; transition: 0.3s; }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(158, 255, 0, 0.4); }
        .btn-outline { padding: 16px 35px; border: 1px solid var(--border); color: var(--white); border-radius: 50px; text-decoration: none; transition: 0.3s; }
        .btn-outline:hover { background: rgba(255,255,255,0.05); }
        .posts-section { max-width: 1200px; margin: 0 auto; padding: 80px 40px; }
        .posts-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px; }
        .post-card { background: var(--panel); border: 1px solid var(--border); border-radius: 20px; padding: 30px; transition: 0.3s; text-decoration: none; color: inherit; }
        .post-card:hover { transform: translateY(-10px); border-color: var(--accent); }
        .post-card h3 { font-size: 22px; color: var(--white); margin-bottom: 15px; }
        .post-card p { color: var(--muted); font-size: 14px; line-height: 1.6; }
        
        /* New Admin and Featured Media Styles */
        .admin-section { max-width: 1200px; margin: 0 auto; padding: 40px; background: var(--panel); border: 1px solid var(--border); border-radius: 24px; display: flex; align-items: center; gap: 30px; }
        .admin-avatar { width: 100px; height: 100px; background: var(--accent); color: #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; font-weight: 700; }
        .admin-text h2 { color: var(--white); margin-bottom: 5px; }
        .admin-text p { color: var(--muted); font-size: 14px; }
        .media-preview { width: 100%; height: 200px; object-fit: cover; border-radius: 12px; margin-bottom: 15px; border: 1px solid var(--border); }
    </style>
</head>
<body>

<header>
    <nav>
        <a href="index.php" class="nav-logo">My<span>Blog</span></a>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="#">Explore</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="#">Write</a></li>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
                <li><a href="registration.php">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<section class="hero">
    <h1>Share your <span>stories</span><br>with the world.</h1>
    <p>A modern platform for students and writers to connect, inspire, and grow together.</p>
    <div class="hero-btns">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="#" class="btn-primary">Start Writing</a>
        <?php else: ?>
            <a href="login.php" class="btn-primary">Get Started</a>
        <?php endif; ?>
        <a href="#" class="btn-outline">Explore Posts</a>
    </div>
</section>

<?php if ($admin_info): ?>
<section class="admin-section" style="margin-top: -60px; margin-bottom: 60px; position: relative; z-index: 10;">
    <div class="admin-avatar">
        <?= strtoupper(substr($admin_info['student_fname'], 0, 1)) ?>
    </div>
    <div class="admin-text">
        <h2 style="font-size: 24px;">Meet the Author: <?= htmlspecialchars($admin_info['student_fname'] . " " . $admin_info['student_lname']) ?></h2>
        <p>Curating the best content for our community. Log in to join the conversation!</p>
    </div>
</section>
<?php endif; ?>

<section class="posts-section" style="padding-top: 0;">
    <h2 style="color: var(--white); margin-bottom: 30px; text-align: center;">Featured <span>Media</span></h2>
    <div class="posts-grid">
        <?php while($media = pg_fetch_assoc($featured_res)): ?>
            <div class="post-card">
                <?php if($media['file_type'] === 'video'): ?>
                    <div style="background: #000; border-radius: 12px; height: 200px; display: flex; align-items: center; justify-content: center; color: var(--accent); font-weight: 700; margin-bottom: 15px;">VIDEO CONTENT</div>
                <?php else: ?>
                    <img src="<?= $media['file_path'] ?>" class="media-preview">
                <?php endif; ?>
                <h3><?= htmlspecialchars($media['title']) ?></h3>
                <p><?= htmlspecialchars(substr($media['description'] ?? 'Explore this admin highlight on MyBlog.', 0, 80)) ?>...</p>
                
                <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid var(--border); font-size: 12px; color: var(--muted);">
                    View only mode: <a href="login.php" style="color: var(--accent); text-decoration: none;">Login to comment</a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</section>

<section class="posts-section">
    <div class="posts-grid">
        <div class="post-card">
            <h3>The Future of Web Design</h3>
            <p>Exploring how minimalist aesthetics and dark modes are taking over the modern web...</p>
        </div>
        <div class="post-card">
            <h3>Getting Started with PHP</h3>
            <p>A beginner's guide to building dynamic web applications using PostgreSQL and PHP...</p>
        </div>
        <div class="post-card">
            <h3>Mindfulness for Students</h3>
            <p>Simple techniques to stay focused and productive during the busiest semesters...</p>
        </div>
    </div>
</section>

</body>
</html>