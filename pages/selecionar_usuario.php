<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. Verifica Login
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: login.php');
    exit;
}

// 2. Verifica Permissão
$nivel = $_SESSION['nivel_acesso'] ?? 'padrao';
$ja_impersonando = isset($_SESSION['usuario_original_id']);

// Permite acesso se for admin/master/proprietario OU se já estiver impersonando (para trocar entre usuários)
if (($nivel !== 'admin' && $nivel !== 'master' && $nivel !== 'proprietario') && !$ja_impersonando) {
    header('Location: home.php?erro=sem_permissao');
    exit;
}

$conn = getTenantConnection();
if (!$conn) die("Erro de conexão.");

$id_atual = $_SESSION['usuario_id'];

// Busca todos os usuários exceto o atual
$sql = "SELECT id, nome, email, foto, nivel_acesso, status FROM usuarios WHERE id != ? AND status = 'ativo' ORDER BY nome ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_atual);
$stmt->execute();
$result = $stmt->get_result();

include('../includes/header.php');

// Tratamento de mensagens de erro
$erro_msg = '';
if (isset($_GET['erro'])) {
    switch($_GET['erro']) {
        case 'id_invalido': $erro_msg = 'Selecione um usuário válido para acessar.'; break;
        case 'db_error': $erro_msg = 'Erro de conexão com o banco de dados.'; break;
        case 'usuario_nao_encontrado': $erro_msg = 'Usuário não encontrado ou inativo.'; break;
        case 'sem_permissao_troca': $erro_msg = 'Você não tem permissão para trocar de usuário.'; break;
        default: $erro_msg = 'Ocorreu um erro inesperado.'; break;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Selecionar Usuário</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
<style>
    /* ===== ESTILO GERAL ===== */
body {
    background: linear-gradient(135deg, #0f0f0f, #1b1b1b);
    color: #e8e8e8;
    font-family: 'Segoe UI', Roboto, sans-serif;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center; /* centraliza toda a página */
}

.container {
    max-width: 950px;
    width: 100%;
    margin-top: 40px;
}

/* ===== TÍTULO ===== */
h2 {
    color: #00bfff;
    font-weight: 700;
    border-bottom: 2px solid #00bfff;
    padding-bottom: 12px;
    text-shadow: 0 0 10px rgba(0, 191, 255, 0.6);
}

/* ===== CARTÕES DE USUÁRIOS ===== */
.user-card {
    background: rgba(31, 31, 31, 0.8);
    border: 1px solid #2c2c2c;
    border-radius: 12px;
    padding: 20px;
    transition: 0.2s ease-in-out;
    cursor: pointer;
    height: 100%;
    width: 100%;
    max-width: 230px; /* 🔥 CARTÃO MENOR */
    margin: 0 auto;   /* 🔥 CENTRALIZA CADA CARTÃO */
    text-align: center;
    position: relative;
    overflow: hidden;
}

/* Glow ao passar o mouse */
.user-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 10px 25px rgba(0, 191, 255, 0.25);
    border-color: #00bfff;
}

/* Avatar menor */
.user-avatar {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    border: 2px solid #00bfff;
    object-fit: cover;
    margin-bottom: 10px;
}

/* Nome */
.user-name {
    font-size: 1rem;
    font-weight: 600;
    color: #fff;
}

/* Email */
.user-email {
    font-size: 0.8rem;
    color: #bdbdbd;
    margin-bottom: 10px;
}

/* Badge */
.user-role {
    background: rgba(0, 191, 255, 0.1);
    border: 1px solid #00bfff;
    padding: 4px 10px;
    border-radius: 15px;
    color: #00bfff;
    font-size: 0.75rem;
    display: inline-block;
    font-weight: 600;
}

/* Botão */
.btn-acessar-fake {
    margin-top: 15px;
    width: 100%;
    background-color: #00bfff;
    color: #fff;
    border: none;
    padding: 8px;
    border-radius: 6px;
    font-weight: 700;
    transition: 0.3s ease;
    font-size: 0.85rem;
}

.user-card:hover .btn-acessar-fake {
    background-color: #0099cc;
}

/* ===== CENTRALIZAÇÃO DO GRID ===== */
.row {
    display: flex;
    justify-content: center; /* 🔥 centraliza todos os cartões */
    gap: 20px;               /* espaçamento melhor */
    flex-wrap: wrap;
}

/* ===== ALERTAS ===== */
.alert-danger {
    background-color: rgba(255, 0, 0, 0.15);
    border: 1px solid rgba(255, 80, 80, 0.5);
    color: #ff8080;
    font-weight: 600;
    border-radius: 8px;
    backdrop-filter: blur(4px);
}

/* ===== RESPONSIVIDADE ===== */
@media (max-width: 768px) {
    .user-card {
        padding: 18px;
    }

    .user-avatar {
        width: 70px;
        height: 70px;
    }

    h2 {
        font-size: 1.4rem;
    }

    .container {
        padding: 15px;
    }
}

</style>
</head>
<body>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-users-cog"></i> Acessar como outro Usuário</h2>
        <a href="home.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
    </div>

    <?php if (!empty($erro_msg)): ?>
        <div class="alert alert-danger text-center mb-4">
            <?= htmlspecialchars($erro_msg) ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="col-md-4 mb-4">
                    <form action="../actions/trocar_usuario.php" method="POST" style="height: 100%;">
                        <input type="hidden" name="id_usuario" value="<?= $row['id'] ?>">
                        
                        <div class="user-card" onclick="this.parentNode.submit()">
                            <img src="../img/usuarios/<?= htmlspecialchars($row['foto'] ?? 'default-profile.png') ?>" class="user-avatar" alt="Foto">
                            <div class="user-name"><?= htmlspecialchars($row['nome']) ?></div>
                            <div class="user-email"><?= htmlspecialchars($row['email']) ?></div>
                            <div class="user-role">
                                <?= ($row['nivel_acesso'] === 'admin' || $row['nivel_acesso'] === 'master') ? 'Administrador' : 'Padrão' ?>
                            </div>
                            <div class="mt-3 w-100">
                                <div class="btn-acessar-fake">
                                    <i class="fas fa-sign-in-alt"></i> Acessar Conta
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center text-muted">
                <h4>Nenhum outro usuário ativo encontrado.</h4>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include('../includes/footer.php'); ?>
</body>
</html>