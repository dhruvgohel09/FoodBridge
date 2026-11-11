<?php
require_once __DIR__ . '/config/db.php';

unset($_SESSION['user_id']);
unset($_SESSION['donation_draft']);
session_destroy();

header('Location: index.php');
exit;

