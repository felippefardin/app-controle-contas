<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

include('../database.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../vendor/autoload.php'; // ajuste o caminho conforme sua estrutura

$erro = '';
$sucesso = '';
$email = $_SESSION['recuperacao_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $codigo = trim($_POST['codigo'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $senha_confirmar = $_POST['senha_confirmar'] ?? '';

    if (!$email || !$codigo || !$senha || !$senha_confirmar) {
        $erro = "Preencha todos os campos.";
    } elseif ($senha !== $senha_confirmar) {
        $erro = "As senhas não coincidem.";
    } elseif (!preg_match('/^[a-zA-Z0-9]{1,10}$/', $codigo)) {
        $erro = "Código inválido.";
    } else {
        // Buscar usuário pelo email
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id_usuario);
            $stmt->fetch();

            // Verificar código válido e não usado nos últimos 30 minutos
            $stmt_code = $conn->prepare("
                SELECT id, data_geracao, usado FROM recuperacao_senha 
                WHERE usuario_id = ? AND codigo = ? ORDER BY data_geracao DESC LIMIT 1
            ");
            $stmt_code->bind_param("is", $id_usuario, $codigo);
            $stmt_code->execute();
            $stmt_code->store_result();

            if ($stmt_code->error) {
                $erro = "Erro na consulta do código: " . $stmt_code->error;
            } elseif ($stmt_code->num_rows === 1) {
                $stmt_code->bind_result($id_code, $data_geracao, $usado);
                $stmt_code->fetch();

                $agora = new DateTime();
                $data_geracao_dt = new DateTime($data_geracao);
                $intervalo = $agora->getTimestamp() - $data_geracao_dt->getTimestamp();
                // DEBUG: mostrar datas para entender problema "Código expirado"
                echo "<pre>";
                echo "Agora (PHP): " . $agora->format('Y-m-d H:i:s') . "\n";
                echo "Data geracao (BD): " . $data_geracao_dt->format('Y-m-d H:i:s') . "\n";
                echo "Intervalo em segundos: " . $intervalo . "\n";
                echo "</pre>";


                if ($usado) {
                    $erro = "Código já utilizado.";
                } elseif ($intervalo > 1800) {
                    $erro = "Código expirado.";
                } else {
                    // Atualizar senha
                    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                    $stmt_up = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                    $stmt_up->bind_param("si", $senha_hash, $id_usuario);

                    if ($stmt_up->execute()) {
                        // Marcar código como usado
                        $stmt_used = $conn->prepare("UPDATE recuperacao_senha SET usado = 1 WHERE id = ?");
                        $stmt_used->bind_param("i", $id_code);
                        $stmt_used->execute();

                        // Enviar e-mail de confirmação
                        try {
                            $mail = new PHPMailer(true);
                            $mail->isSMTP();
                            $mail->Host = 'smtp.seudominio.com';         // Seu servidor SMTP
                            $mail->SMTPAuth = true;
                            $mail->Username = 'seu-email@seudominio.com'; // Seu e-mail SMTP
                            $mail->Password = 'sua-senha';                 // Senha SMTP
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port = 587;

                            $mail->setFrom('nao-responda@seudominio.com', 'Seu Site');
                            $mail->addAddress($email);

                            $mail->isHTML(true);
                            $mail->Subject = 'Confirmação de alteração de senha';
                            $mail->Body    = "
                                <p>Olá,</p>
                                <p>Sua senha foi alterada com sucesso no nosso sistema.</p>
                                <p>Se você não solicitou essa alteração, entre em contato imediatamente.</p>
                                <p>Atenciosamente,<br>Equipe Seu Site</p>
                            ";

                            $mail->send();
                        } catch (Exception $e) {
                            error_log("Erro ao enviar e-mail de confirmação: {$mail->ErrorInfo}");
                        }

                        $sucesso = "Senha alterada com sucesso! Você já pode fazer login.";
                        unset($_SESSION['recuperacao_email']);
                    } else {
                        $erro = "Erro ao atualizar senha.";
                    }
                }
            } else {
                // Debug: listar últimos códigos para esse usuário
                $stmt_check = $conn->prepare("SELECT codigo, usado, data_geracao FROM recuperacao_senha WHERE usuario_id = ? ORDER BY data_geracao DESC LIMIT 5");
                $stmt_check->bind_param("i", $id_usuario);
                $stmt_check->execute();
                $stmt_check->bind_result($codigo_bd, $usado_bd, $data_geracao_bd);
                $resultados = [];
                while ($stmt_check->fetch()) {
                    $resultados[] = [
                        'codigo' => $codigo_bd,
                        'usado' => $usado_bd,
                        'data_geracao' => $data_geracao_bd
                    ];
                }
                $stmt_check->close();

                if (count($resultados) === 0) {
                    $erro = "Nenhum código foi gerado para este usuário.";
                } else {
                    $lista_codigos = implode(", ", array_map(function($r) {
                        return $r['codigo'] . ($r['usado'] ? " (usado)" : "");
                    }, $resultados));
                    $erro = "Código inválido. Códigos recentes para este usuário: " . $lista_codigos;
                }
            }
            $stmt_code->close();
        } else {
            $erro = "E-mail não encontrado.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<title>Nova Senha</title>
<style>
  .senha-forca {
    height: 8px;
    background: #ddd;
    border-radius: 4px;
    margin-top: 6px;
    margin-bottom: 12px;
    overflow: hidden;
  }
  .senha-forca > div {
    height: 100%;
    transition: width 0.3s ease;
  }
  .forca-fraca { background-color: #dc3545; width: 25%; }
  .forca-media { background-color: #ffc107; width: 50%; }
  .forca-bom { background-color: #28a745; width: 100%; }
  .toggle-password {
    cursor: pointer;
    color: #00bfff;
    user-select: none;
  }
</style>
</head>
<body>
<form method="POST" novalidate>
  <h2>Redefinir Senha</h2>

  <?php if ($erro): ?>
    <div style="color:red;"><?= htmlspecialchars($erro) ?></div>
  <?php elseif ($sucesso): ?>
    <div style="color:green;"><?= htmlspecialchars($sucesso) ?></div>
    <p><a href="login.php">Voltar para Login</a></p>
  <?php else: ?>
    <label for="email">E-mail:</label>
    <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

    <label for="codigo">Código enviado por e-mail:</label>
    <input type="text" id="codigo" name="codigo" required>

    <label for="senha">Nova senha:</label>
    <input type="password" id="senha" name="senha" required>
    <span class="toggle-password" onclick="toggleSenha('senha')">👁️</span>
    <div class="senha-forca"><div id="barra-forca"></div></div>

    <label for="senha_confirmar">Confirmar nova senha:</label>
    <input type="password" id="senha_confirmar" name="senha_confirmar" required>
    <span class="toggle-password" onclick="toggleSenha('senha_confirmar')">👁️</span>

    <button type="submit">Alterar senha</button>
  <?php endif; ?>
</form>

<script>
    
  function toggleSenha(id) {
    const input = document.getElementById(id);
    if (input.type === "password") {
      input.type = "text";
    } else {
      input.type = "password";
    }
  }

  const senhaInput = document.getElementById('senha');
  const barraForca = document.getElementById('barra-forca');

  senhaInput.addEventListener('input', function() {
    const valor = senhaInput.value;
    let forca = 0;

    if (valor.length >= 6) forca++;
    if (/[A-Z]/.test(valor)) forca++;
    if (/[0-9]/.test(valor)) forca++;
    if (/[\W]/.test(valor)) forca++;

    if (forca <= 1) {
      barraForca.style.width = '25%';
      barraForca.style.backgroundColor = '#dc3545';
    } else if (forca === 2 || forca === 3) {
      barraForca.style.width = '50%';
      barraForca.style.backgroundColor = '#ffc107';
    } else if (forca === 4) {
      barraForca.style.width = '100%';
      barraForca.style.backgroundColor = '#28a745';
    } else {
      barraForca.style.width = '0';
    }
  });
</script>

</body>
</html>
