<?php
require_once '../includes/session_init.php';
include('../database.php');

// 🔹 Conexão com o banco (mesma de contas_pagar.php)
$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "app_controle_contas";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Captura os dados do POST
$nome     = trim($_POST['nome'] ?? '');
$cpf      = trim($_POST['cpf'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$email    = trim($_POST['email'] ?? '');
$senha    = $_POST['senha'] ?? '';

// Validação básica
if (!$nome || !$cpf || !$telefone || !$email || !$senha) {
    die(estilizarMensagem("Preencha todos os campos.", "erro"));
}

// Remove caracteres especiais do CPF
$cpf_clean = preg_replace('/[.-]/', '', $cpf);

// Verifica duplicidade de email ou CPF
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? OR REPLACE(REPLACE(cpf,'.',''),'-','') = ?");
$stmt->bind_param("ss", $email, $cpf_clean);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    die(estilizarMensagem("Email ou CPF já cadastrado!", "erro"));
}
$stmt->close();

// Cria hash da senha
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);

// Insere o usuário
$stmt = $conn->prepare("INSERT INTO usuarios (nome, cpf, telefone, email, senha) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $nome, $cpf, $telefone, $email, $senha_hash);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    echo estilizarMensagem("Cadastro realizado com sucesso! <br><a href='usuarios.php'>Voltar para o login</a>", "sucesso");
    exit;
} else {
    $stmt->close();
    $conn->close();
    die(estilizarMensagem("Erro ao cadastrar usuário: " . $conn->error, "erro"));
}

// Função para estilizar mensagens
function estilizarMensagem($mensagem, $tipo = "info") {
    $cor = $tipo === "sucesso" ? "#27ae60" : ($tipo === "erro" ? "#e74c3c" : "#3498db");
    return "
    <!DOCTYPE html>
    <html lang='pt-BR'>
    <head>
        <meta charset='UTF-8'>
        <title>Cadastro</title>
        <style>
            body {
                background: #121212;
                color: #eee;
                font-family: Arial, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .caixa {
                background: #1e1e1e;
                padding: 30px;
                border-radius: 10px;
                text-align: center;
                box-shadow: 0 0 12px rgba(0,0,0,0.5);
                max-width: 400px;
            }
            h1 {
                margin-bottom: 20px;
                color: {$cor};
            }
            a {
                display: inline-block;
                margin-top: 15px;
                padding: 10px 20px;
                background: #007bff;
                color: #fff;
                text-decoration: none;
                border-radius: 5px;
                transition: 0.3s;
            }
            a:hover {
                background: #0056b3;
            }
        </style>
    </head>
    <body>
        <div class='caixa'>
            <h1>{$mensagem}</h1>
        </div>
    </body>
    </html>
    ";
}
?>
