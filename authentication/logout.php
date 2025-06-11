<?php
require_once __DIR__ . '/../includes/SessionManager.php';

$session = new SessionManager();
$session->destroy();

header("Location: loginform.php?logged_out=1");

header("Location: loginform.php?timeout=1");

exit();
