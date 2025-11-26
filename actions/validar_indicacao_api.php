<?php
require_once '../includes/session_init.php';
require_once '../database.php';

header('Content-Type: application/json; charset=utf-8');

$conn = getMasterConnection();

// Verifica se veio o código
$codigo = trim($_POST['codigo_indicacao'] ?? '');

if (empty($codigo)) {
    echo json_encode([
        'valid' => false,
        'message' => 'Código não informado.'
    ]);
    exit;
}

// Valida existencia no banco
$stmt = $conn->prepare("SELECT id, nome, codigo_indicacao FROM usuarios WHERE codigo_indicacao = ?");
$stmt->bind_param("s", $codigo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'valid' => false,
        'message' => 'Código inválido ou não encontrado.'
    ]);
    exit;
}

$usuario = $result->fetch_assoc();

// Sucesso
echo json_encode([
    'valid' => true,
    'message' => "Indicação válida! Indicador: " . $usuario['nome'],
    'id_indicador' => $usuario['id']
]);
