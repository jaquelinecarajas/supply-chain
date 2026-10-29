<?php
define('SUPPLY_CHAIN', true);
require_once __DIR__ . '/../config/constants.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name(SESSION_NAME);
    session_start();
}
$_SESSION = [];
session_destroy();

header('Location: ' . BASE_URL . '/auth/login.php');
exit;