<?php
if (!isset($conn)) {
    session_start();
    include 'dbconn.php';
    include 'functions.php'; // Required for set_flash_message()

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

$user_email = $_SESSION['user_id'];
$message = '';

// Fetch current user data
$res = pg_query_params($conn, "SELECT r.*, l.password FROM public.registration r JOIN public.login l ON r.student_email = l.username WHERE r.student_email = $1", array($user_email));
$user = pg_fetch_assoc($res);

if (isset($_POST['update_user'])) {
    $fname = $_POST['fname'];
    $mname = $_POST['mname'];
    $lname = $_POST['lname'];
    $new_email = $_POST['email'];
    $new_pass = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($new_pass) && $new_pass !== $confirm_password) {
        $message = "New passwords do not match!";
    } else {
        pg_query($conn, "BEGIN");
        try {
            if (!empty($new_pass)) {
                pg_query_params($conn, "UPDATE public.login SET password = $1, username = $2 WHERE username = $3", array($new_pass, $new_email, $user_email));
            } else {
                pg_query_params($conn, "UPDATE public.login SET username = $1 WHERE username = $2", array($new_email, $user_email));
            }
            pg_query_params($conn, "UPDATE public.registration SET student_fname=$1, student_mname=$2, student_lname=$3, student_email=$4 WHERE student_email=$5", array($fname, $mname, $lname, $new_email, $user_email));
            pg_query($conn, "COMMIT");

            $_SESSION['user_id'] = $new_email;
            $_SESSION['username'] = $new_email;
            $_SESSION['full_name'] = $fname . " " . ($mname ? $mname . " " : "") . $lname;

            // FEATURE ADDED: Set the indicator message
            set_flash_message("Your personal details have been updated!");

            header("Location: " . ($_SESSION['role'] === 'Admin' ? "home.php" : "user_home.php"));
            exit();
        } catch (Exception $e) {
            pg_query($conn, "ROLLBACK");
            $message = "Error updating profile.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile | MyBlog</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css"> 
    <style>
        /* CSS VARIABLE DEFAULTS */
        :root {
            --bg: #0a0a0b;
            --panel: #111113;
            --border: rgba(255,255,255,0.1);
            --accent: #9eff00;
            --white: #ffffff;
            --gray: #a0a0a5;
            --muted: #666666;
            --input-bg: rgba(255,255,255,0.03);
            --danger: #ff3d7f;
        }

        body { 
            font-family: 'Outfit', sans-serif; 
            background: var(--bg); 
            color: var(--white); 
            margin: 0; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh;
            padding: 20px;
            flex-direction: column;
        }

        .content-card { 
            width: 100%; 
            max-width: 500px; 
            background: var(--panel); 
            padding: 40px; 
            border-radius: 24px; 
            border: 1px solid var(--border);
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }

        h2 { text-align: center; margin-bottom: 30px; font-weight: 700; }
        .form-group { margin-bottom: 20px; }
        label { 
            display: block; 
            font-size: 11px; 
            font-weight: 700; 
            text-transform: uppercase; 
            color: var(--gray); 
            margin-bottom: 8px; 
            letter-spacing: 1px;
        }

        input { 
            width: 100%; 
            padding: 14px; 
            background: var(--input-bg); 
            border: 1px solid var(--border); 
            border-radius: 12px; 
            color: white; 
            font-family: inherit;
            box-sizing: border-box; 
            outline: none;
            transition: 0.3s;
        }

        input:focus { border-color: var(--accent); background: rgba(255,255,255,0.06); }
        input[readonly] { opacity: 0.5; cursor: not-allowed; }

        .btn-action { 
            width: 100%; 
            padding: 16px; 
            background: var(--accent); 
            color: #000; 
            font-weight: 700; 
            border: none; 
            border-radius: 12px; 
            cursor: pointer; 
            font-size: 16px; 
            margin-top: 10px;
            transition: 0.3s;
        }

        .btn-action:hover { transform: translateY(-2px); opacity: 0.9; }

        .alert { 
            background: rgba(255, 61, 127, 0.1); 
            color: var(--danger); 
            padding: 12px; 
            border-radius: 10px; 
            font-size: 13px; 
            text-align: center; 
            margin-bottom: 20px; 
            border: 1px solid rgba(255, 61, 127, 0.2);
        }

        .cancel-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--muted);
            text-decoration: none;
            font-size: 13px;
        }
    </style>
</head>
<body>

<?= display_flash_message(); ?>

<div class="content-card">
    <h2>Edit Profile Settings</h2>

    <?php if ($message): ?>
        <div class="alert"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>First Name</label>
            <input type="text" name="fname" value="<?= htmlspecialchars($user['student_fname'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label>Middle Name</label>
            <input type="text" name="mname" value="<?= htmlspecialchars($user['student_mname'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Last Name</label>
            <input type="text" name="lname" value="<?= htmlspecialchars($user['student_lname'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['student_email'] ?? '') ?>" required>
        </div>
        
        <div style="margin: 30px 0; border-top: 1px solid var(--border); padding-top: 20px;">
            <div class="form-group">
                <label>Current Password (Stored)</label>
                <input type="text" value="<?= htmlspecialchars($user['password'] ?? '') ?>" readonly>
            </div>

            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" placeholder="Leave blank to keep current">
            </div>

            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" placeholder="Confirm new password">
            </div>
        </div>

        <button type="submit" name="update_user" class="btn-action">Save Profile Changes</button>
        <a href="javascript:history.back()" class="cancel-link">Cancel Changes</a>
    </form>
</div>

</body>
</html>