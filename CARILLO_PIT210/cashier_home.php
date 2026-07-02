<?php
session_start();
include 'dbconn.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Cashier') {
    header("Location: index.php"); exit();
}
$result = pg_query($conn, "SELECT * FROM registration WHERE status = 'Pending' ORDER BY id ASC");
?>
<div class="sidebar">
    <h2 style="color: white;">Cashier<span>Portal</span></h2>
    <a href="cashier_home.php" class="nav-item active">Pending Approvals</a>
    <a href="logout.php" class="nav-item">Logout</a>
</div>
<div class="main">
    <h2>Verify New Registrations</h2>
    <table border="1" style="width: 100%; color: white; border-collapse: collapse;">
        <tr><th>Name</th><th>Email</th><th>Action</th></tr>
        <?php while($row = pg_fetch_assoc($result)): ?>
        <tr>
            <td><?= $row['student_fname'] ?></td>
            <td><?= $row['student_email'] ?></td>
            <td>
                <a href="approve_user.php?id=<?= $row['id'] ?>&status=Approved" style="color: var(--accent);">Approve</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>