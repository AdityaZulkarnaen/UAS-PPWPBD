<?php
require_once '../includes/session_manager.php';

destroy_session();
header("Location: ../../index.php");
exit();
?>