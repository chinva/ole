<?php
session_start();
require_once 'config/config.php';
require_once 'includes/Auth.php';

$database = new Database();
$db = $database->connect();
$auth = new Auth($db);

$auth->logout();

header('Location: login.php');
exit;
?>