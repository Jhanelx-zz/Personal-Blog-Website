<?php
session_start();
include 'dbconn.php'; 

$login_message = '';

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Join login and registration to check status and role 
    $query = "SELECT l.*, r.status, r.student_fname, r.student_lname 
              FROM public.login l 
              JOIN public.registration r ON l.username = r.student_email 
              WHERE l.username = $1 AND l.password = $2";
    
    $result = pg_query_params($conn, $query, array($email, $password)); 

    if ($row = pg_fetch_assoc($result)) {
        // Only "Approved" status allows login
        if ($row['status'] === 'Approved') {
            
            // 1. Force a fresh session ID for security and to ensure a value exists
            session_regenerate_id(true);
            $current_session_id = session_id();

            // 2. Update status to 1 AND store the user_session_id in the DB
            // We use 'public.login' to be explicit with the schema
            $update_sql = "UPDATE public.login SET status = 1, user_session_id = $1 WHERE username = $2";
            $update_result = pg_query_params($conn, $update_sql, array($current_session_id, $email));

            // Optional: Error check to see why it might fail
            if (!$update_result) {
                die("Database Update Failed: " . pg_last_error($conn));
            }

            // 3. Set session variables
            $_SESSION['user_id'] = $row['username'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['privileged']; 
            $_SESSION['full_name'] = $row['student_fname'] . " " . $row['student_lname'];

            // Redirect based on role
            if ($_SESSION['role'] === 'Admin') {
                header("Location: home.php");
            } else {
                header("Location: user_home.php");
            }
            exit();
        } else {
            $login_message = "Your account is currently " . htmlspecialchars($row['status']) . ". Access is restricted.";
        }
    } else {
        $login_message = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>MyBlog — Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css"> 
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: var(--bg); }
        .login-card { background: var(--panel); padding: 50px; border-radius: 24px; border: 1px solid var(--border); width: 100%; max-width: 450px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 11px; font-weight: 700; margin-bottom: 8px; color: var(--white); text-transform: uppercase; }
        input { width: 100%; padding: 14px; background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 12px; color: white; outline: none; transition: 0.3s; }
        input:focus { border-color: var(--accent); background: rgba(255,255,255,0.05); }
        .btn-login { width: 100%; padding: 16px; background: var(--accent); color: #000; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; margin-top: 10px; transition: 0.3s; }
        .btn-login:hover { opacity: 0.9; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="login-card">
        <a href="index.php" class="nav-logo" style="display:block; text-align:center; margin-bottom:30px; text-decoration:none; font-size:28px; font-weight:700; color:white;">My<span>Blog</span></a>
        
        <?php if ($login_message): ?>
            <div style="color: var(--danger); text-align: center; margin-bottom: 20px; font-size: 14px; background: rgba(255, 61, 127, 0.1); padding: 10px; border-radius: 8px;"><?= $login_message ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="name@email.com">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" name="login" class="btn-login">Sign In</button>
        </form>
        <p style="text-align: center; margin-top: 25px; font-size: 14px; color: var(--muted);">
            New here? <a href="registration.php" style="color: var(--accent); text-decoration: none;">Create an account</a>
        </p>
    </div>
</body>
</html>