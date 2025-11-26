<?php
// Arquivo: pages/fix_permissoes_db.php
require_once '../includes/session_init.php';
require_once '../database.php';

// Verifica login apenas para garantir que a sessão carregou os dados do tenant
if (empty($_SESSION['usuario_logado'])) {
    die("Por favor, faça login no sistema antes de executar este script.");
}

// Conecta no banco do Tenant atual (ex: tenant_db_27)
$conn = getTenantConnection();

if (!$conn) {
    die("Erro: Não foi possível conectar ao banco de dados do tenant.");
}

echo "<h2><i class='fas fa-tools'></i> Atualizando Banco de Dados (Permissões)</h2>";

$comandos = [
    // 1. Criar tabela de Permissões Disponíveis
    "CREATE TABLE IF NOT EXISTS `permissoes` (
      `id` int NOT NULL AUTO_INCREMENT,
      `nome` varchar(100) NOT NULL,
      `slug` varchar(50) NOT NULL,
      `descricao` text,
      PRIMARY KEY (`id`),
      UNIQUE KEY `slug` (`slug`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 2. Criar tabela de Vínculo (Usuário <-> Permissão)
    "CREATE TABLE IF NOT EXISTS `usuario_permissoes` (
      `id` int NOT NULL AUTO_INCREMENT,
      `usuario_id` int NOT NULL,
      `permissao_id` int NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uk_user_perm` (`usuario_id`, `permissao_id`),
      CONSTRAINT `fk_up_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
      CONSTRAINT `fk_up_permissao` FOREIGN KEY (`permissao_id`) REFERENCES `permissoes` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 3. Inserir permissões padrão
    "INSERT INTO `permissoes` (`nome`, `slug`) VALUES 
    ('Admin', 'admin'), 
    ('Financeiro', 'financeiro'), 
    ('Suporte', 'suporte'), 
    ('Vendas', 'vendas'),
    ('Master', 'master')
    ON DUPLICATE KEY UPDATE nome = VALUES(nome);"
];

foreach ($comandos as $index => $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "<div style='color: green; margin-bottom: 10px;'>✔️ Passo " . ($index + 1) . " executado com sucesso.</div>";
    } else {
        echo "<div style='color: red; margin-bottom: 10px;'>❌ Erro no Passo " . ($index + 1) . ": " . $conn->error . "</div>";
    }
}

echo "<hr>";
echo "<a href='usuarios.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Voltar para Usuários</a>";

$conn->close();
?>