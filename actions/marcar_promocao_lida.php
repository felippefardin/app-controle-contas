<?php
require_once '../includes/session_init.php';
require_once '../database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $connMaster = getMasterConnection();
    $id = intval($_POST['id']);
    $connMaster->query("UPDATE tenant_promocoes SET visualizado = 1 WHERE id = $id");
}
?>