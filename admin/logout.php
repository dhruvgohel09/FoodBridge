<?php
require_once __DIR__ . '/../config/db.php';

unset($_SESSION['admin_id']);

header('Location: admin-login.php');
exit;

