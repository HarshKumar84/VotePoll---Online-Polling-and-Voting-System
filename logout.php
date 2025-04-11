<?php
session_start();
session_destroy();
// Start a new session to set the message
session_start();
$_SESSION['message'] = "You have been successfully logged out.";
$_SESSION['message_type'] = "success";
header("Location: index.php");
exit;
?>