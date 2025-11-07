<?php
// --- Inicia sessão e configurações ---
require_once __DIR__ . '/../includes/session_init.php';
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
    'basico' => ['nome' => 'Básico', 'valor' => 29.90, 'descricao' => 'Acesso mensal básico ao sistema'],
    'pro' => ['nome' => 'Pro', 'valor' => 59.90, 'descricao' => 'Recursos avançados e relatórios'],
    'premium' => ['nome' => 'Premium', 'valor' => 99.90, 'descricao' => 'Todos os recursos + suporte prioritário']
];

// 🔹 Processa o formulário de assinatura
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plano'], $_POST['email'])) {

    $planoSelecionado = $_POST['plano'];
    $emailComprador = trim($_POST['email']);

    if (!isset($planos[$planoSelecionado])) {
        die("Plano inválido");
    }

    $plano = $planos[$planoSelecionado];

    // 🔹 Dados do comprador e vendedor sandbox
    $payer_email = "test_user_2368268688435555249@testuser.com";
    $collector_id = "2411601376"; // vendedor sandbox

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
            "email_usuario_real" => $emailComprador
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
        $stmt = $conn->prepare("INSERT INTO assinaturas (email, plano, valor, status, mp_preapproval_id) VALUES (?, ?, ?, ?, ?)");
        $status = 'pendente';
        $stmt->bind_param(
            "ssdss",
            $emailComprador,
            $plano['nome'],
            $plano['valor'],
            $status,
            $resposta['id']
        );
        $stmt->execute();

        // 🔹 Redireciona para checkout sandbox
        header("Location: " . $resposta['init_point']);
        exit;
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

<h2>Escolha seu Plano (SANDBOX)</h2>

<?php foreach ($planos as $chave => $plano): ?>
<div style="border:1px solid #ccc; padding:15px; margin:10px; width:300px;">
    <h3><?= $plano['nome'] ?> — R$ <?= number_format($plano['valor'], 2, ',', '.') ?>/mês</h3>
    <p><?= $plano['descricao'] ?></p>
    <form method="post">
        <input type="hidden" name="plano" value="<?= $chave ?>">
        <label>Seu e-mail (para registro interno):</label><br>
        <input type="email" name="email" required placeholder="ex: cliente@teste.com"><br><br>
        <button type="submit">Assinar (SANDBOX)</button>
    </form>
</div>
<?php endforeach; ?>

<p><small>💡 Use comprador sandbox: <b>test_user_2368268688435555249@testuser.com</b></small></p>
