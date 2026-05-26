<?php
session_start();
session_destroy();
session_start();
$_SESSION['status'] = "You've logged out successfully.";
header("Location: ../index");
exit;
