<?php
session_start();

// Function to verify if the user is logged in
function verify_session() {
    if (!isset($_SESSION['user_id'])) {
        // User is not logged in, redirect to login page
        header("Location: /HireWay/src/auth/login.php");
        exit();
    }
}

// Call the function to verify the session
verify_session();
?>