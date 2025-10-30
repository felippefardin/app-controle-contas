<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA O LOGIN E PEGA A CONEXÃO CORRETA
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: login.php?error=not_logged_in');
    exit;
}
$conn = getTenantConnection();
if ($conn === null) {
    die("Falha na conexão com o banco de dados.");
}

// Pega os IDs da sessão e da URL
$id_usuario = $_SESSION['usuario_logado']['id'];
$id_conta = intval($_GET['id'] ?? 0);

include('../includes/header.php');

if ($id_conta === 0) {
    echo "<div class='container'><h1>ID da conta não fornecido.</h1></div>";
    include('../includes/footer.php');
    exit;
}

// 2. BUSCA A CONTA COM SEGURANÇA, VERIFICANDO SE ELA PERTENCE AO USUÁRIO
$stmt = $conn->prepare("SELECT * FROM contas_receber WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $id_conta, $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$conta = $result->fetch_assoc();
$stmt->close();

if (!$conta) {
    echo "<div class='container'><h1>Conta não encontrada ou acesso não permitido.</h1></div>";
    include('../includes/footer.php');
    exit;
}

// Busca as categorias de 'receita' do usuário para o dropdown
$categorias_result = $conn->query("SELECT id, nome FROM categorias WHERE id_usuario = {$id_usuario} AND tipo = 'receita'");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Conta a Receber</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        body { background-color: #121212; color: #eee; }
        .container { background-color: #222; padding: 25px; border-radius: 8px; margin-top: 30px; }
        h2 { border-bottom: 2px solid #0af; padding-bottom: 10px; margin-bottom: 20px; }
        .form-control { background-color: #333; color: #eee; border-color: #444; }
        .form-control:focus { background-color: #333; color: #eee; border-color: #0af; box-shadow: none; }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-edit"></i> Editar Conta a Receber</h2>
        
        <form action="../actions/editar_conta_receber.php" method="POST">
            <input type="hidden" name="id" value="<?= $conta['id'] ?>">
            
            <div class="form-group">
                <label for="responsavel">Cliente / Responsável</label>
                <input type="text" class="form-control" id="responsavel" name="responsavel" value="<?= htmlspecialchars($conta['responsavel'] ?? '') ?>" required>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="numero">Número/Documento</label>
                    <input type="text" class="form-control" id="numero" name="numero" value="<?= htmlspecialchars($conta['numero'] ?? '') ?>" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="valor">Valor (R$)</label>
                    <input type="text" class="form-control" id="valor" name="valor" value="<?= number_format($conta['valor'], 2, ',', '.') ?>" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="data_vencimento">Data de Vencimento</label>
                    <input type="date" class="form-control" id="data_vencimento" name="data_vencimento" value="<?= $conta['data_vencimento'] ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="id_categoria">Categoria</label>
                <select class="form-control" id="id_categoria" name="id_categoria" required>
                    <option value="">Selecione uma categoria</option>
                    <?php while($cat = $categorias_result->fetch_assoc()): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($cat['id'] == $conta['id_categoria']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nome']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            <a href="contas_receber.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</body>
</html>
<?php include('../includes/footer.php'); ?>