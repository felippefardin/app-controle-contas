<?php
// actions/validar_indicacao_api.php
header('Content-Type: application/json');
session_start();
require_once '../database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['valid' => false, 'msg' => 'Método inválido']);
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$doc   = preg_replace('/[^0-9]/', '', $_POST['documento'] ?? '');
$usuario_atual_id = $_SESSION['user_id'] ?? 0;

if (empty($email) || empty($doc)) {
    echo json_encode(['valid' => false, 'msg' => 'Preencha e-mail e CPF/CNPJ.']);
    exit;
}

$conn = getMasterConnection();

$sql = "SELECT id, nome FROM usuarios WHERE email = ? AND documento_clean = ? AND id != ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $email, $doc, $usuario_atual_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo json_encode([
        'valid' => true, 
        'msg' => 'Indicado por: ' . $user['nome'],
        'nome' => $user['nome']
    ]);
} else {
    echo json_encode([
        'valid' => false, 
        'msg' => 'Conta não encontrada ou dados incorretos.'
    ]);
}

$stmt->close();
$conn->close();
?>