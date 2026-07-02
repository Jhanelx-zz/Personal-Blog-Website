<?php
if(!isset($conn)) include 'dbconn.php';

// Fetch user data for the table
$query = "SELECT * FROM public.registration ORDER BY id ASC";
$result = pg_query($conn, $query);

// Database logic to count statistics
$total_q = pg_query($conn, "SELECT COUNT(*) FROM public.registration");
$total_users = pg_fetch_result($total_q, 0, 0);

$approved_q = pg_query($conn, "SELECT COUNT(*) FROM public.registration WHERE status = 'Approved'");
$approved_users = pg_fetch_result($approved_q, 0, 0);

$pending_q = pg_query($conn, "SELECT COUNT(*) FROM public.registration WHERE status = 'Pending'");
$pending_users = pg_fetch_result($pending_q, 0, 0);

$disapproved_q = pg_query($conn, "SELECT COUNT(*) FROM public.registration WHERE status = 'Disapproved'");
$disapproved_users = pg_fetch_result($disapproved_q, 0, 0);
?>

<div class="dashboard-header">
    <h1 style="color: white; margin-bottom: 10px;">User <span>Management</span></h1>
    <p style="color: var(--muted); margin-bottom: 40px;">Review and manage student account access and status.</p>
</div>

<div class="stat-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px;">
    <div class="glass-card">
        <h3>Total Users</h3>
        <p><?= $total_users ?></p>
    </div>

    <div class="glass-card">
        <h3>Approved</h3>
        <p style="color: var(--accent);"><?= $approved_users ?></p>
    </div>

    <div class="glass-card">
        <h3>Pending</h3>
        <p style="color: #ffcc00;"><?= $pending_users ?></p>
    </div>

    <div class="glass-card">
        <h3>Disapproved</h3>
        <p style="color: var(--danger);"><?= $disapproved_users ?></p>
    </div>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="background: rgba(255,255,255,0.02); color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">
                <th style="padding: 20px;">Users Name</th>
                <th style="padding: 20px;">Email Address</th>
                <th style="padding: 20px;">Account Status</th>
                <th style="padding: 20px; text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = pg_fetch_assoc($result)): ?>
            <tr style="border-bottom: 1px solid var(--border); transition: 0.3s;">
                <td style="padding: 20px; color: var(--white); font-weight: 500;">
                    <?= htmlspecialchars($row['student_fname'] . ' ' . $row['student_lname']); ?>
                </td>
                <td style="padding: 20px; color: var(--gray);"><?= htmlspecialchars($row['student_email']); ?></td>
                <td style="padding: 20px;">
                    <span style="padding: 6px 14px; border-radius: 50px; font-size: 11px; font-weight: 700; 
                        background: <?= $row['status'] === 'Approved' ? 'rgba(158, 255, 0, 0.1)' : 'rgba(255, 61, 127, 0.1)' ?>; 
                        color: <?= $row['status'] === 'Approved' ? 'var(--accent)' : 'var(--danger)' ?>;">
                        <?= strtoupper($row['status']); ?>
                    </span>
                </td>
                <td style="padding: 20px; text-align: right;">
                    <a href="edit.php?id=<?= $row['id'] ?>" class="btn-primary" style="padding: 8px 16px; font-size: 12px; text-decoration: none; margin-right: 10px; border-radius: 8px;">Edit</a>
                    <a href="home.php?view=users&delete_id=<?= $row['id'] ?>" 
                       style="color: var(--danger); font-size: 12px; text-decoration: none; font-weight: 600;" 
                       onclick="return confirm('Permanently delete this user account?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<style>
.glass-card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1); 
    border-radius: 20px;
    padding: 25px;
    text-align: center;
    transition: 0.3s;
}
.glass-card h3 {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--muted);
    margin-bottom: 10px;
}
.glass-card p {
    font-size: 32px;
    font-weight: 700;
    color: white;
    margin: 0;
}
</style>