<?php
include "dbconn.php";
$reg_message = '';

if(isset($_POST['register'])) {
    $fname = $_POST['fname'];
    $mname = $_POST['mname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];
    $password = $_POST['user_password'];
    $confirm_password = $_POST['confirm_password'];

    $check_email = pg_query_params($conn, "SELECT student_email FROM registration WHERE student_email = $1", array($email));

    if (pg_num_rows($check_email) > 0) {
        $reg_message = "This email is already registered.";
    } elseif ($password !== $confirm_password) {
        $reg_message = "Passwords do not match!";
    } else {
        $query = "INSERT INTO registration (student_fname, student_mname, student_lname, student_email, status) 
                  VALUES($1, $2, $3, $4, 'Pending')";
        $result = pg_query_params($conn, $query, array($fname, $mname, $lname, $email));
        
        if($result) {
            // New users are explicitly inserted with 'User' privilege
            $login_query = "INSERT INTO login (username, password, status, privileged) 
                            VALUES($1, $2, 0, 'User')";
            pg_query_params($conn, $login_query, array($email, $password));

            header("Location: login.php");
            exit();
        } else {
            $reg_message = "Registration failed.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>MyBlog — Register</title>
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
        body { font-family: 'Outfit', sans-serif; background: var(--bg); color: var(--gray); display: flex; align-items: center; justify-content: center; padding: 40px 20px; }
        .reg-card { background: var(--panel); width: 100%; max-width: 600px; padding: 50px; border-radius: 24px; border: 1px solid var(--border); box-shadow: 0 30px 60px rgba(0,0,0,0.5); }
        .logo { text-align: center; font-size: 28px; font-weight: 700; color: var(--white); margin-bottom: 30px; text-decoration: none; display: block; }
        .logo span { color: var(--accent); }
        h2 { font-size: 24px; color: var(--white); margin-bottom: 25px; text-align: center; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full { grid-column: span 2; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 8px; color: var(--white); text-transform: uppercase; }
        input { width: 100%; background: rgba(255,255,255,0.03); border: 1px solid var(--border); padding: 12px; border-radius: 10px; color: var(--white); font-family: inherit; }
        input:focus { outline: none; border-color: var(--accent); }
        .btn-reg { width: 100%; padding: 15px; background: var(--accent); border: none; border-radius: 12px; color: #000; font-weight: 700; font-size: 16px; cursor: pointer; transition: all 0.3s; margin-top: 20px; }
        .btn-reg:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(158, 255, 0, 0.3); }
        .login-link { text-align: center; margin-top: 20px; font-size: 14px; }
        .login-link a { color: var(--accent); text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>

<div class="reg-card">
    <a href="index.php" class="logo">My<span>Blog</span></a>
    <h2>Create your account</h2>

    <?php if ($reg_message != ''): ?>
        <div style="color: #ff3d7f; text-align: center; margin-bottom: 20px;"><?= $reg_message ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="fname" required>
            </div>
            <div class="form-group">
                <label>Middle Name</label>
                <input type="text" name="mname" required>
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="lname" required>
            </div>
            <div class="form-group full">
                <label>Username (Email)</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group full">
                <label>Password</label>
                <input type="password" name="user_password" required>
            </div>
            <div class="form-group full">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required> 
            </div>
        </div>
        <button type="submit" name="register" class="btn-reg">Register Now</button>
    </form>
    <div class="login-link">Already have an account? <a href="login.php">Sign In</a></div>
</div>

</body>
</html>