<?php
require_once '../includes/session_init.php';
require_once '../database.php';
require_once __DIR__ . '/../vendor/autoload.php';

// ------------------------
// Configurações iniciais
// ------------------------
$access_token = "TEST-434665267442294-110610-a6c0df937492f2c030236826d3634d8c-456404185";

// Permite acesso se estiver logado ou se veio do fluxo de assinatura pendente
if (!isset($_SESSION['usuario_logado']) && !isset($_SESSION['assinatura_pendente'])) {
    header('Location: login.php');
    exit;
}

// Email do usuário logado
$email_usuario = $_SESSION['usuario_logado']['email'] ?? 'test_user_123456@testuser.com';

// ------------------------
// Planos disponíveis
// ------------------------
$planos = [
    ["id" => "plano_basico", "nome" => "Básico", "preco" => 29.90, "descricao" => "Acesso mensal básico ao sistema"],
    ["id" => "plano_pro", "nome" => "Pro", "preco" => 59.90, "descricao" => "Recursos avançados e relatórios"],
    ["id" => "plano_premium", "nome" => "Premium", "preco" => 99.90, "descricao" => "Todos os recursos + suporte prioritário"]
];

// ------------------------
// Fluxo de assinatura
// ------------------------
if (isset($_POST['plano_id'])) {
    $plano_id = $_POST['plano_id'];
    $plano = array_filter($planos, fn($p) => $p['id'] === $plano_id);
    $plano = reset($plano);

    if ($plano) {
        $start_date = gmdate("Y-m-d\TH:i:s.000\Z", strtotime("+1 minute"));
        $end_date   = gmdate("Y-m-d\TH:i:s.000\Z", strtotime("+1 year"));

        // URL de retorno do Mercado Pago após assinatura
        $back_url = "https://hydrometallurgical-unsententiously-deirdre.ngrok-free.dev/app-controle-contas/pages/home.php";

        $dados = [
            "payer_email" => $email_usuario,
            "auto_recurring" => [
                "frequency" => 1,
                "frequency_type" => "months",
                "transaction_amount" => $plano['preco'],
                "currency_id" => "BRL",
                "start_date" => $start_date,
                "end_date" => $end_date
            ],
            "back_url" => $back_url,
            "reason" => "Assinatura do plano {$plano['nome']}"
        ];

        $curl = curl_init("https://api.mercadopago.com/preapproval");
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $access_token",
                "Content-Type: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode($dados)
        ]);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $resposta = json_decode($response, true);

        if ($httpcode == 201 && isset($resposta['init_point'])) {
            // Redireciona o usuário para a página de pagamento Mercado Pago
            header("Location: " . $resposta['init_point']);
            exit;
        } else {
            echo "<div style='color:red; font-family:monospace; padding:20px;'>
                    <h3>Erro ao criar assinatura:</h3>
                    <pre>" . htmlspecialchars(print_r($resposta, true)) . "</pre>
                  </div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Escolha seu Plano</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f7f9fc; margin:0; padding:0; }
        .container { max-width: 900px; margin: 60px auto; background:#fff; border-radius:16px; padding:30px; box-shadow:0 4px 15px rgba(0,0,0,0.1);}
        h1 { text-align:center; color:#333; }
        .usuario { text-align:center; color:#555; margin-bottom:30px; }
        .planos { display:flex; justify-content:space-around; flex-wrap:wrap; gap:20px; }
        .plano { background:#f4f6f8; border-radius:12px; padding:20px; width:250px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.08); transition:transform .2s ease; }
        .plano:hover { transform: translateY(-5px); }
        .preco { font-size:24px; color:#2d89ef; font-weight:bold; }
        button { background:#2d89ef; color:white; border:none; border-radius:8px; padding:10px 20px; cursor:pointer; font-size:15px; transition: background 0.2s ease; }
        button:hover { background:#1b5cb8; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Escolha seu Plano</h1>
        <p class="usuario">Olá, <strong><?= htmlspecialchars($email_usuario) ?></strong>! Escolha um plano para ativar seu acesso.</p>

        <div class="planos">
            <?php foreach ($planos as $plano): ?>
                <div class="plano">
                    <h3><?= htmlspecialchars($plano['nome']) ?></h3>
                    <p><?= htmlspecialchars($plano['descricao']) ?></p>
                    <p class="preco">R$ <?= number_format($plano['preco'], 2, ',', '.') ?>/mês</p>
                    <form method="POST">
                        <input type="hidden" name="plano_id" value="<?= htmlspecialchars($plano['id']) ?>">
                        <button type="submit">Assinar</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
