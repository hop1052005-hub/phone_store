<?php
require_once __DIR__ . '/config/db.php';
unset($_SESSION['user']);
$_SESSION['flash_message'] = "Bạn đã đăng xuất tài khoản.";
$_SESSION['flash_type'] = "info";
header("Location: index.php");
exit;
?>