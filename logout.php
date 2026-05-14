<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth_system.php';

$auth = new AuthSystem($pdo);
$auth->logout();

header('Location: index.php');
exit;
