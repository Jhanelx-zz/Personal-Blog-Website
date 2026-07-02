<?php
session_start();
include 'dbconn.php';
include 'functions.php'; // Required for set_flash_message()

if ($_SESSION['role'] !== 'Admin') {
    exit("Unauthorized Access");
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // 1. Get file path to delete from storage
    $res = pg_query_params($conn, "SELECT file_path FROM media WHERE id = $1", array($id));
    if ($row = pg_fetch_assoc($res)) {
        if (file_exists($row['file_path'])) { 
            unlink($row['file_path']);
        }
    }
    
    // 2. Delete from DB
    $result = pg_query_params($conn, "DELETE FROM media WHERE id = $1", array($id));
    
    if ($result) {
        // Set red indicator for deletion
        set_flash_message("Post and associated media permanently deleted.", "danger");
        header("Location: home.php?view=posts");
        exit();
    } else {
        die("Delete failed: " . pg_last_error($conn));
    }
}
?>