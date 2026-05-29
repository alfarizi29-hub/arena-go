<?php
require_once 'includes/config.php';
requireLogin();
$user_id = $_SESSION['user_id'];
$conn->query("DELETE FROM notifikasi WHERE user_id=$user_id");
header('Location: notifikasi.php');
exit;
