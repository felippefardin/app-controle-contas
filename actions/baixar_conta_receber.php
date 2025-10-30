<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA O LOGIN E PEGA A CONEXÃO
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: ../pages/login.php?error=not_logged_in');
    exit;
}

$id_conta = intval($_GET['id'] ?? 0);
$formas_pagamento = ['boleto', 'deposito', 'credito', 'debito', 'dinheiro', 'pix', 'outros'];
$comprovantePath = null;

// 2. LÓGICA DE ATUALIZAÇÃO (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getTenantConnection();
    if ($conn === null) {
        $_SESSION['error_message'] = "Falha na conexão com o banco de dados.";
        header('Location: ../pages/contas_receber.php');
        exit;
    }

    $id_usuario = $_SESSION['usuario_logado']['id'];
    $forma = $_POST['forma'];
    $juros = floatval(str_replace(',', '.', $_POST['juros'] ?? 0));
    $dataBaixaInput = $_POST['data_baixa'];

    // Lógica de Upload
    if (isset($_FILES['comprovante']) && $_FILES['comprovante']['error'] == 0) {
        $target_dir = "../comprovantes/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }
        $fileName = uniqid() . '_' . basename($_FILES["comprovante"]["name"]);
        $target_file = $target_dir . $fileName;
        if (move_uploaded_file($_FILES["comprovante"]["tmp_name"], $target_file)) {
            $comprovantePath = 'comprovantes/' . $fileName;
        }
    }

    $dataBaixa = DateTime::createFromFormat('d/m/Y', $dataBaixaInput);
    $dataBaixaFormatada = $dataBaixa ? $dataBaixa->format('Y-m-d') : date('Y-m-d');

    // 3. ATUALIZA A CONTA COM SEGURANÇA (SQL CORRIGIDO)
    // A coluna `baixado_por_usuario_id` foi trocada por `baixado_por`
    $sql = "UPDATE contas_receber SET status='baixada', forma_pagamento=?, juros=?, data_baixa=?, baixado_por=?, comprovante=? WHERE id=? AND usuario_id=?";
    $stmt = $conn->prepare($sql);
    
    // O bind_param foi ajustado para a nova consulta
    $stmt->bind_param("sdsisii", $forma, $juros, $dataBaixaFormatada, $id_usuario, $comprovantePath, $id_conta, $id_usuario);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Conta baixada com sucesso!";
    } else {
        $_SESSION['error_message'] = "Erro ao baixar a conta: " . $stmt->error;
    }
    
    $stmt->close();
    header('Location: ../pages/contas_receber_baixadas.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Baixar Conta a Receber</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
      body { background-color: #121212; color: #eee; font-family: Arial, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
      form { background-color: #1f1f1f; padding: 25px 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.6); width: 100%; max-width: 400px; display: flex; flex-direction: column; gap: 20px; }
      h2 { color: #27ae60; margin-bottom: 20px; text-align: center; }
      label { font-weight: bold; margin-bottom: 5px; }
      select, input { padding: 12px 14px; font-size: 16px; border-radius: 6px; border: 1px solid #444; background-color: #333; color: #eee; width: 100%; }
      button { background-color: #27ae60; color: white; border: none; font-weight: bold; padding: 12px; font-size: 16px; border-radius: 6px; cursor: pointer; transition: background-color 0.3s ease; }
      button:hover { background-color: #1e874b; }
  </style>
</head>
<body>
  <form method="POST" action="baixar_conta_receber.php?id=<?= $id_conta ?>" enctype="multipart/form-data">
    <h2><i class="fas fa-check-circle"></i> Baixar Conta a Receber</h2>
    <div>
        <label for="data_baixa">Data da Baixa</label>
        <input type="text" id="data_baixa" name="data_baixa" value="<?= date('d/m/Y') ?>" required />
    </div>
    <div>
        <label for="juros">Juros / Acréscimos (R$)</label>
        <input type="text" id="juros" name="juros" value="0,00" />
    </div>
    <div>
        <label for="forma">Forma de Recebimento</label>
        <select id="forma" name="forma" required>
            <option value="" disabled selected>Selecione...</option>
            <?php foreach ($formas_pagamento as $forma): ?>
              <option value="<?= htmlspecialchars($forma) ?>"><?= ucfirst($forma) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="comprovante">Anexar Comprovante (Opcional)</label>
        <input type="file" id="comprovante" name="comprovante" accept="image/*,.pdf">
    </div>
    <button type="submit"><i class="fa-solid fa-check"></i> Confirmar</button>
  </form>
</body>
</html>