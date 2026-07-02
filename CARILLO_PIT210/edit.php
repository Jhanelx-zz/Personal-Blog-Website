<?php
session_start();
include 'dbconn.php';
include 'functions.php'; // Required for real-time indicator functions

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'] ?? null;
// Fetching user data from both registration and login tables
$res = pg_query_params($conn, "SELECT r.*, l.password FROM public.registration r JOIN public.login l ON r.student_email = l.username WHERE r.id = $1", array($id));
$user = pg_fetch_assoc($res);

if (isset($_POST['update_user'])) {
    $new_status = $_POST['status'];
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];

    // Update registration table
    $update_res = pg_query_params($conn, "UPDATE public.registration SET student_fname=$1, student_lname=$2, student_email=$3, status=$4 WHERE id=$5", 
        array($fname, $lname, $email, $new_status, $id));
    
    // Sync email with login table
    pg_query_params($conn, "UPDATE public.login SET username=$1 WHERE username=$2", array($email, $user['student_email']));

    if ($update_res) {
        // FEATURE ADDED: Set indicator before redirect
        // Determine type based on status (red for Disapproved, green for Approved)
        $type = ($new_status === 'Disapproved') ? 'danger' : 'success';
        set_flash_message("User account details and status updated successfully!", $type);

        header("Location: home.php?view=users");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User | Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        :root { --sidebar-w: 280px; }
        body { display: flex; background: var(--bg); margin: 0; }
        .sidebar { width: var(--sidebar-w); background: var(--sidebar); border-right: 1px solid var(--border); position: fixed; height: 100vh; padding: 40px 20px; display: flex; flex-direction: column; }
        .main-content { margin-left: var(--sidebar-w); width: calc(100% - var(--sidebar-w)); padding: 60px; display: flex; flex-direction: column; align-items: center; }
        .edit-card { background: var(--panel); border: 1px solid var(--border); padding: 40px; border-radius: 24px; width: 100%; max-width: 600px; height: fit-content; }
        input, select { width: 100%; padding: 14px; background: var(--input-bg); border: 1px solid var(--border); border-radius: 12px; color: white; margin-bottom: 20px; box-sizing: border-box; }
        .nav-item { display: flex; align-items: center; padding: 16px 20px; color: var(--gray); text-decoration: none; border-radius: 12px; margin-bottom: 8px; font-weight: 500; }
    </style>
</head>
<body>

<?= display_flash_message(); ?>

<div class="sidebar">
    <a href="index.php" class="nav-logo" style="text-decoration:none;">My<span>Blog</span></a>
    <p style="font-size: 12px; color: var(--muted); margin-top: 20px; padding: 0 20px;">Logged in as: <span style="color: var(--white);"><?= htmlspecialchars($_SESSION['username']) ?></span></p>
    
    <div style="margin-top: 40px; flex-grow: 1;">
        <a href="home.php" class="nav-item">← Back to Dashboard</a>
    </div>

    <div style="padding-top: 20px; border-top: 1px solid var(--border);">
        <a href="logout.php" class="nav-item" style="color: var(--danger);">Logout</a>
    </div>
</div>

<main class="main-content">
    <div class="edit-card">
        <h2 style="color: white; margin-bottom: 30px;">Update Account Access</h2>
        <form method="POST">
            <label style="color: var(--muted); font-size: 11px; text-transform: uppercase;">Full Name</label>
            <div style="display: flex; gap: 10px;">
                <input type="text" name="fname" value="<?= htmlspecialchars($user['student_fname']) ?>" required>
                <input type="text" name="lname" value="<?= htmlspecialchars($user['student_lname']) ?>" required>
            </div>

            <label style="color: var(--muted); font-size: 11px; text-transform: uppercase;">Email Address</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['student_email']) ?>" required>

            <label style="color: var(--muted); font-size: 11px; text-transform: uppercase;">Account Status</label>
            <select name="status">
                <option value="Approved" <?= $user['status'] == 'Approved' ? 'selected' : '' ?>>Approved</option>
                <option value="Disapproved" <?= $user['status'] == 'Disapproved' ? 'selected' : '' ?>>Disapproved</option>
                <option value="Pending" <?= $user['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
            </select>

            <button type="submit" name="update_user" class="btn-primary" style="width: 100%; padding: 16px; border: none; border-radius: 12px; font-weight: 700; cursor: pointer;">Save Changes</button>
        </form>
    </div>
</main>
</body>
</html>