<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/database.php';

$conn = getMasterConnection();

$sql = "SELECT id, admin_email, db_host, db_database, db_user, db_password FROM tenants ORDER BY id DESC LIMIT 5";
$result = $conn->query($sql);

echo "<pre>";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";
