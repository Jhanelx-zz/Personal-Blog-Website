<?php
session_start();
include 'dbconn.php';

// Security: Check if user is logged in and ID is provided
if (isset($_SESSION['user_id']) && isset($_GET['id'])) {
    $comment_id = $_GET['id'];
    $user_id = $_SESSION['user_id']; // This is the email/username from login session

    // Ensure the user can only delete their OWN comment
    $query = "DELETE FROM public.comments WHERE id = $1 AND username = $2";
    $result = pg_query_params($conn, $query, array($comment_id, $user_id));

    if ($result) {
        // Redirect back to the previous page (post_details.php)
        if (isset($_SERVER['HTTP_REFERER'])) {
            header("Location: " . $_SERVER['HTTP_REFERER']);
        } else {
            header("Location: user_home.php");
        }
        exit();
    } else {
        die("Error deleting comment: " . pg_last_error($conn));
    }
} else {
    // If not logged in or no ID, send to home
    header("Location: user_home.php");
    exit();
}
?>