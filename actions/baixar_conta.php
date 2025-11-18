<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA O LOGIN E PEGA A CONEXÃO
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: ../pages/login.php?error=not_logged_in');
    exit;
}

// Pega o ID da conta da URL
$id_conta = intval($_GET['id'] ?? 0);
$formas_pagamento = ['boleto', 'deposito', 'credito', 'debito', 'dinheiro', 'pix', 'outros']; 

// 2. VERIFICA SE O MÉTODO É POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getTenantConnection();
    if ($conn === null) {
        $_SESSION['error_message'] = "Falha na conexão com o banco de dados.";
        header('Location: ../pages/contas_pagar.php');
        exit;
    }

    // ✅ CORREÇÃO 1: Pega o ID corretamente da sessão
    $id_usuario = $_SESSION['usuario_id'];
    
    $forma_pagamento = $_POST['forma'];
    // Os campos abaixo existem no formulário, mas não no banco atual.
    // Mantemos a captura caso você atualize o banco depois.
    $juros = floatval(str_replace(',', '.', $_POST['juros'] ?? 0));
    $dataBaixaInput = $_POST['data_baixa'];

    // Lógica de Upload do Comprovante (Mantida, mas não salva no banco por enquanto)
    $comprovantePath = null;
    if (isset($_FILES['comprovante']) && $_FILES['comprovante']['error'] == 0) {
        $target_dir = "../comprovantes/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $fileName = uniqid() . '_' . basename($_FILES["comprovante"]["name"]);
        $target_file = $target_dir . $fileName;
        if (move_uploaded_file($_FILES["comprovante"]["tmp_name"], $target_file)) {
            $comprovantePath = 'comprovantes/' . $fileName;
        }
    }

    // ✅ CORREÇÃO 2: Query simplificada para bater com o schema.sql existente
    // Removemos 'juros', 'data_baixa', 'baixado_por' e 'comprovante' da query pois dão erro.
    $sql = "UPDATE contas_pagar 
            SET status='baixada', forma_pagamento=?
            WHERE id=? AND usuario_id = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $_SESSION['error_message'] = "Erro ao preparar a query: " . $conn->error;
        header('Location: ../pages/contas_pagar.php');
        exit;
    }
    
    // Bind atualizado para: forma_pagamento (s), id (i), usuario_id (i)
    $stmt->bind_param("sii", $forma_pagamento, $id_conta, $id_usuario);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Conta baixada com sucesso!";
    } else {
        $_SESSION['error_message'] = "Erro ao baixar conta: " . $stmt->error;
    }

    $stmt->close();
    header('Location: ../pages/contas_pagar_baixadas.php'); // Redireciona para a lista de baixadas
    exit;
}

// Se não for POST, exibe o formulário HTML
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Baixar Conta - Forma de Pagamento</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
  * { box-sizing: border-box; }

body {
  background-color: #121212;
  color: #eee;
  font-family: 'Segoe UI', sans-serif;
  margin: 0;
  padding: 20px;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
}

form {
  background-color: #1f1f1f;
  padding: 30px;
  border-radius: 10px;
  box-shadow: 0 0 15px rgba(0,191,255,0.3);
  display: flex;
  flex-direction: column;
  gap: 20px;
  width: 100%;
  max-width: 420px;
}

h2 {
  text-align: center;
  color: #00bfff;
  margin-top: 0;
}

.form-group {
  display: flex;
  flex-direction: column;
  width: 100%;
}

input[type="text"],
select,
input[type="file"],
textarea {
  width: 100%;
  min-height: 44px;
  padding: 12px 15px;
  font-size: 16px;
  border-radius: 6px;
  border: 1px solid #444;
  background-color: #2a2a2a;
  color: #eee;
  appearance: none;
  outline: none;
}

input[type="file"] {
  padding: 10px;
  background-color: #2a2a2a;
  border: 1px solid #444;
}
input[type="file"]::-webkit-file-upload-button {
  background-color: #00bfff;
  color: white;
  border: none;
  border-radius: 4px;
  padding: 8px 14px;
  margin-right: 10px;
  cursor: pointer;
  transition: background-color 0.3s ease;
  font-weight: 600;
}
input[type="file"]::-webkit-file-upload-button:hover {
  background-color: #0099cc;
}

button {
  background-color: #00bfff;
  border: none;
  color: white;
  font-weight: 600;
  padding: 12px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 16px;
  width: 100%;
}
button:hover { background-color: #0099cc; }
  </style>
</head>
<body>

  <form method="POST" action="baixar_conta.php?id=<?= $id_conta ?>" enctype="multipart/form-data">
    <h2><i class="fa fa-credit-card"></i> Baixar Conta a Pagar</h2>
    
    <div class="form-group">
        <label for="data_baixa">Data da Baixa</label>
        <input type="text" id="data_baixa" name="data_baixa" class="form-control" value="<?= date('d/m/Y') ?>" required />
    </div>
    
    <div class="form-group">
        <label for="juros">Juros/Acréscimos (R$)</label>
        <input type="text" id="juros" name="juros" class="form-control" value="0,00" />
    </div>
    
    <div class="form-group">
        <label for="forma">Forma de Pagamento</label>
        <select id="forma" name="forma" class="form-control" required>
            <option value="">Selecione...</option>
            <?php foreach ($formas_pagamento as $forma): ?>
              <option value="<?= htmlspecialchars($forma) ?>"><?= ucfirst($forma) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="form-group">
        <label for="comprovante">Anexar Comprovante (Opcional)</label>
        <input type="file" id="comprovante" name="comprovante" class="form-control-file" accept="image/*,.pdf">
    </div>
    
    <button type="submit"><i class="fa fa-check"></i> Confirmar Baixa</button>
  </form>

</body>
</html>