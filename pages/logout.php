<?php
require_once '../includes/session_init.php';
session_destroy();
header('Location: login.php');
exit;
