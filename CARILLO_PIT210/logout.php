<?php
session_start();
include 'dbconn.php';

// Check if a username is set in the session before attempting cleanup
if (isset($_SESSION['username'])) {
    $user_uname = $_SESSION['username'];
    
    // Activity #1: Set status to 0 (offline) and user_session_id to NULL
    // This ensures the database reflects that the session is no longer active
    $logout_query = "UPDATE public.login SET status = 0, user_session_id = NULL WHERE username = $1";
    pg_query_params($conn, $logout_query, array($user_uname));
}

// Clear all session variables
session_unset();

// Destroy the session on the server
session_destroy();

// Redirect back to the landing page
header("Location: index.php");
exit();
?>