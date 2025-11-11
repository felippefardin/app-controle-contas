<?php
$host = 'localhost';
$user = 'root';
$pass = 'Fa525658*';
$db   = 'app_controle_contas';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Erro: " . $conn->connect_error);
}
echo "Conexão bem-sucedida!";
