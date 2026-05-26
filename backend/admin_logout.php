<?php
session_start();
session_destroy();
// session_destroy wipes the session, so start a fresh one for the flash message
session_start();
$_SESSION['status'] = "You've logged out successfully.";
header("Location: ../index");
exit;
