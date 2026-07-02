<?php
session_start();
include 'dbconn.php';
include 'functions.php'; // Required for set_flash_message()

// Access Control: Only Cashier or Admin can approve accounts
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'Cashier' && $_SESSION['role'] !== 'Admin')) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id']) && isset($_GET['status'])) {
    $user_id = $_GET['id'];
    $new_status = $_GET['status'];

    $query = "UPDATE registration SET status = $1 WHERE id = $2";
    $result = pg_query_params($conn, $query, array($new_status, $user_id));

    if ($result) {
        // Set success (green) for Approved, and danger (red) for Disapproved
        $type = ($new_status === 'Approved') ? 'success' : 'danger';
        set_flash_message("User account has been " . strtoupper($new_status) . " successfully!", $type);

        header("Location: " . ($_SESSION['role'] === 'Cashier' ? "cashier_home.php" : "home.php"));
        exit();
    } else {
        die("Update failed: " . pg_last_error($conn));
    }
}
?>