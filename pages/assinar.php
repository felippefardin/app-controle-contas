<?php
// pages/assinar.php

// --- Inicia sessão e configurações ---
require_once __DIR__ . '/../includes/session_init.php'; 

// --- Bloco para capturar a mensagem de erro ---
$mensagem_erro_assinatura = '';
if (isset($_SESSION['erro_assinatura'])) {
    $mensagem_erro_assinatura = $_SESSION['erro_assinatura'];
    unset($_SESSION['erro_assinatura']); // Limpa a mensagem
}
// --- Fim do bloco ---

require_once __DIR__ . '/../includes/config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use MercadoPago\MercadoPagoConfig;

// 🔹 Pega modo de operação
$mp_mode = $_ENV['MERCADOPAGO_MODE'] ?? 'sandbox';

// 🔹 Token e back_url conforme sandbox
if ($mp_mode === 'sandbox') {
    $access_token = $_ENV['MP_ACCESS_TOKEN_SANDBOX'] ?? null;
    $back_url = $_ENV['MP_BACK_URL_SANDBOX'] ?? ($_ENV['APP_URL'] . "/pages/home.php");
} else {
    $access_token = $_ENV['MP_ACCESS_TOKEN_PRODUCAO'] ?? null;
    $back_url = $_ENV['MP_BACK_URL_PRODUCAO'] ?? ($_ENV['APP_URL'] . "/pages/home.php");
}

// 🔹 Verifica token
if (!$access_token) {
    die("⚠️ Access token {$mp_mode} não encontrado no .env");
}

// 🔹 Configura Mercado Pago
MercadoPagoConfig::setAccessToken($access_token);

// 🔹 Planos disponíveis
$planos = [
    'basico' => [
        'nome' => 'Básico',
        'valor' => 29.90,
        'descricao' => 'Acesso mensal básico ao sistema'
    ],
    'pro' => [
        'nome' => 'Pro',
        'valor' => 59.90,
        'descricao' => 'Recursos avançados e relatórios'
    ],
    'premium' => [
        'nome' => 'Premium',
        'valor' => 99.90,
        'descricao' => 'Todos os recursos + suporte prioritário'
    ]
];

// 🔹 Processa o formulário de assinatura
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plano'], $_POST['email'])) {

    // ⬇️ CORREÇÃO PRINCIPAL (Foreign Key) ⬇️
    // Tenta pegar o ID Master (se logado no painel). Se não, pega o ID normal (se bloqueado no login).
    $idUsuario = $_SESSION['usuario_id_master'] ?? $_SESSION['usuario_id'] ?? null;

    if (!$idUsuario) {
        $_SESSION['erro_assinatura'] = 'Erro de sessão: ID do usuário não identificado. Faça login novamente.';
        header("Location: assinar.php");
        exit;
    }
    // ⬆️ FIM DA CORREÇÃO ⬆️

    $planoSelecionado = $_POST['plano'];
    $emailComprador = trim($_POST['email']);

    if (!isset($planos[$planoSelecionado])) {
        die("Plano inválido");
    }

    $plano = $planos[$planoSelecionado];

    // 🔹 Dados do comprador e vendedor sandbox
    // Idealmente, use o e-mail do comprador real se estiver em produção, ou um fixo em teste
    $payer_email = "test_user_2368268688435555249@testuser.com"; 
    $collector_id = "2411601376"; 

    // 🔹 Monta dados da assinatura
    $dados = [
        "payer_email" => $payer_email,
        "collector_id" => $collector_id,
        "back_url" => $back_url,
        "reason" => "Assinatura do plano {$plano['nome']}",
        "auto_recurring" => [
            "frequency" => 1,
            "frequency_type" => "months",
            "transaction_amount" => $plano['valor'],
            "currency_id" => "BRL",
            "start_date" => gmdate("Y-m-d\TH:i:s.000\Z", strtotime("+1 minute")),
            "end_date" => gmdate("Y-m-d\TH:i:s.000\Z", strtotime("+1 year"))
        ],
        "metadata" => [
            "plano" => $plano['nome'],
            "email_usuario_real" => $emailComprador,
            "id_usuario_sistema" => $idUsuario
        ]
    ];

    // 🔹 Envia requisição para criar assinatura sandbox
    $ch = curl_init("https://api.mercadopago.com/preapproval");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $access_token"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
    $resposta = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $resposta = json_decode($resposta, true);

    if ($httpcode == 201 && isset($resposta['id'], $resposta['init_point'])) {
        // 🔹 Salva assinatura no banco
        $conn = getMasterConnection();

        $stmt = $conn->prepare("
            INSERT INTO assinaturas (id_usuario, email, plano, valor, status, mp_preapproval_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $status = 'pendente';

        $stmt->bind_param(
            "isdsss",
            $idUsuario,
            $emailComprador,
            $plano['nome'],
            $plano['valor'],
            $status,
            $resposta['id']
        );
        
        if ($stmt->execute()) {
            // 🔹 Redireciona para checkout sandbox
            header("Location: " . $resposta['init_point']);
            exit;
        } else {
             // Erro ao salvar no banco (ex: tabela não existe ou dados inválidos)
             echo "Erro de Banco de Dados: " . $stmt->error;
             exit;
        }

    } else {
        echo "<pre>❌ Erro ao criar assinatura (HTTP $httpcode)\n";
        print_r($resposta);
        echo "\n\nJSON Enviado:\n";
        echo json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo "</pre>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assinar Plano - App Controle de Contas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        body {
            background-color: #121212;
            color: #eee;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 30px auto;
            background-color: #222;
            padding: 25px 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 191, 255, 0.1);
        }
        h2 {
            text-align: center;
            color: #00bfff;
            margin-bottom: 25px;
            border-bottom: 2px solid #0af;
            padding-bottom: 10px;
        }
        .planos-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        .plano-card {
            background-color: #1f1f1f;
            border: 1px solid #444;
            border-radius: 8px;
            padding: 25px;
            width: 100%;
            max-width: 320px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 12px rgba(0,0,0,0.4);
        }
        .plano-card h3 {
            color: #0af;
            text-align: left;
            margin-bottom: 10px;
            font-size: 1.5rem;
            border-bottom: none;
        }
        .plano-card p {
            color: #ccc;
            text-align: left;
            font-size: 0.95rem;
            flex-grow: 1;
            margin-top: 0;
        }
        .plano-card form {
            margin-top: 20px;
        }
        .plano-card label {
            font-size: 0.9rem;
            color: #aaa;
            margin-bottom: 5px;
            display: block;
        }
        .plano-card input[type="email"] {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border-radius: 6px;
            border: 1px solid #444;
            background-color: #333;
            color: #eee;
            margin-bottom: 15px;
        }
        .plano-card button {
            width: 100%;
            background-color: #00bfff;
            color: #121212;
            border: none;
            font-weight: bold;
            padding: 12px;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .plano-card button:hover {
            background-color: #0099cc;
            color: white;
        }
        .aviso-sandbox {
            text-align: center;
            color: #aaa;
            font-size: 0.9rem;
        }
        .mensagem-erro {
            background-color: #cc4444;
            color: white;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            font-family: Arial, sans-serif;
            border: 1px solid #dc3545;
        }
        .btn-voltar {
            display: block;
            width: fit-content;
            margin: 20px auto 0;
            color: #aaa;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .btn-voltar:hover { color: #fff; }
    </style>
</head>
<body>

<div class="container">

    <?php if (!empty($mensagem_erro_assinatura)): ?>
        <div class="mensagem-erro">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <?php echo htmlspecialchars($mensagem_erro_assinatura); ?>
        </div>
    <?php endif; ?>

    <h2>Escolha seu Plano (SANDBOX)</h2>

    <div class="planos-container">
        <?php foreach ($planos as $chave => $plano): ?>
            <div class="plano-card">
                <h3><?= $plano['nome'] ?> — R$ <?= number_format($plano['valor'], 2, ',', '.') ?>/mês</h3>
                <p><?= $plano['descricao'] ?></p>
                <form method="post">
                    <input type="hidden" name="plano" value="<?= $chave ?>">
                    <label for="email_<?= $chave ?>">Seu e-mail (para registro interno):</label>
                    
                    <input type="email" name="email" id="email_<?= $chave ?>" 
                           value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" 
                           required placeholder="ex: cliente@teste.com">
                    
                    <button type="submit">Assinar (SANDBOX)</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <p class="aviso-sandbox">
        <small>💡 Use comprador sandbox:
            <b>test_user_2368268688435555249@testuser.com</b>
        </small>
    </p>
    
    <a href="minha_assinatura.php" class="btn-voltar">Voltar</a>
</div>

</body>
</html>