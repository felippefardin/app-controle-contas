<?php
// pages/minha_assinatura.php

// Carrega config (autoload, .env, DB e configuração do Mercado Pago)
require_once __DIR__ . '/../includes/config/config.php';
require_once __DIR__ . '/../includes/session_init.php'; // garante session_start() e sessão
require_once __DIR__ . '/../vendor/autoload.php';

use MercadoPago\MercadoPagoConfig;
// --- CORREÇÃO AQUI ---
use MercadoPago\Client\Subscription\SubscriptionClient; // Cliente correto
// --- FIM DA CORREÇÃO ---

// Pega o access token (config.php já deve ter setado via MercadoPagoConfig::setAccessToken)
$accessToken = $_ENV['MERCADOPAGO_ACCESS_TOKEN'] ?? '';

// Buscar dados do usuário logado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../pages/login.php?msg=sessao_expirada');
    exit;
}

$pdo = getDbConnection();
$stmt = $pdo->prepare("SELECT status_assinatura, mp_subscription_id FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$subscription = null;
$payments = [];
$errorMessage = '';

// --- CORREÇÃO AQUI ---
// Função auxiliar: busca pagamentos pela subscription_id usando a API REST do Mercado Pago
function fetchPaymentsBySubscriptionId(string $subscriptionId, string $accessToken): array
{
    if (empty($subscriptionId) || empty($accessToken)) {
        return [];
    }

    // Endpoint de busca de pagamentos (search) do Mercado Pago
    // Usamos o /v1/payments/search com query param subscription_id
    $url = "https://api.mercadopago.com/v1/payments/search?subscription_id=" . urlencode($subscriptionId) . "&limit=50&sort=date_approved&criteria=desc";

    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "Authorization: Bearer " . $accessToken . "\r\n" .
                        "Content-Type: application/json\r\n",
            "timeout" => 10
        ]
    ];
// --- FIM DA CORREÇÃO ---
    $context = stream_context_create($opts);
    $result = @file_get_contents($url, false, $context);

    if ($result === false) {
        return [];
    }

    $data = json_decode($result, true);
    if (!is_array($data)) return [];

    // A API retorna um array com chave 'results' contendo pagamentos
    return $data['results'] ?? [];
}

// 3. Buscar dados da assinatura via API (SubscriptionClient)
if ($user && !empty($user['mp_subscription_id'])) {
    try {
        // --- CORREÇÃO AQUI ---
        $subscriptionClient = new SubscriptionClient(); // Cliente correto
        $subscription = $subscriptionClient->get($user['mp_subscription_id']);
        // --- FIM DA CORREÇÃO ---

        // 4. Buscar histórico de pagamentos (faturas) via chamada REST
        // --- CORREÇÃO AQUI ---
        $payments = fetchPaymentsBySubscriptionId($user['mp_subscription_id'], $accessToken);
        // --- FIM DA CORREÇÃO ---

    } catch (Exception $e) {
        $errorMessage = 'Não foi possível carregar os dados da sua assinatura. Tente novamente mais tarde.';
        error_log("Erro ao carregar assinatura/minha_assinatura.php: " . $e->getMessage());
    }
} else {
    // Se o usuário está no banco mas sem ID, ou o status local é 'ativo' mas não achou ID.
    if ($user && $user['status_assinatura'] === 'active' && empty($user['mp_subscription_id'])) {
         $errorMessage = 'Não encontramos o ID da sua assinatura. Por favor, entre em contato com o suporte.';
    } else {
         $errorMessage = 'Assinatura não encontrada ou inativa.';
    }
}

// Inclui o header da sua aplicação (HTML)
include '../includes/header.php';
?>

<div class="container mt-4">
    <h2>Gerenciamento da Assinatura</h2>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <?php if ($subscription): ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-header">
                Plano Atual: <?php echo htmlspecialchars($subscription->reason ?? 'Plano indefinido'); ?>
            </div>
            <div class="card-body">
                <h5 class="card-title">
                    Status:
                    <span class="badge 
                        <?php
                            // Mapeamento do status do MP para as classes do Bootstrap
                            $status = $subscription->status ?? 'unknown';
                            if ($status === 'authorized') echo 'bg-success';
                            elseif ($status === 'paused') echo 'bg-warning';
                            elseif ($status === 'pending') echo 'bg-info';
                            else echo 'bg-danger'; // cancelled, expired, etc.
                        ?>">
                        <?php echo ucfirst(htmlspecialchars($status)); ?>
                    </span>
                </h5>

                <p>
                    Valor: R$
                    <?php echo number_format($subscription->auto_recurring->transaction_amount ?? 0, 2, ',', '.'); ?> / mês
                </p>

                <?php if (!empty($subscription->next_payment_date)): // Campo correto na nova API ?>
                    <p>Próxima cobrança:
                        <?php echo date('d/m/Y', strtotime($subscription->next_payment_date)); ?>
                    </p>
                <?php endif; ?>

                <hr>

                <?php if (in_array($subscription->status ?? '', ['authorized', 'paused'])): ?>
                    <form action="../actions/cancelar_assinatura.php" method="POST" style="display:inline-block; margin-left:10px;">
                        <button type="submit" class="btn btn-warning"
                            onclick="return confirm('Tem certeza que deseja cancelar sua assinatura? Esta ação não pode ser desfeita.')">
                            Cancelar Assinatura
                        </button>
                    </form>
                <?php elseif ($subscription->status == 'cancelled'): ?>
                     <p class="text-danger">Sua assinatura está cancelada.</p>
                     <a href="assinar.php" class="btn btn-primary">Assinar Novamente</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-4 shadow-sm">
            <div class="card-header">Histórico de Pagamentos</div>
            <div class="card-body">
                <?php if (count($payments) > 0): ?>
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Status</th>
                                <th>Valor</th>
                                <th>ID Pagamento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td>
                                        <?php echo isset($payment['date_approved']) 
                                            ? date('d/m/Y H:i', strtotime($payment['date_approved'])) 
                                            : (isset($payment['date_created']) ? date('d/m/Y H:i', strtotime($payment['date_created'])) : 'N/A'); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($payment['status'] ?? ''); ?></td>
                                    <td>R$ <?php echo number_format($payment['transaction_amount'] ?? 0, 2, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($payment['id'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Nenhum pagamento processado ainda.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-danger mb-4 shadow-sm">
            <div class="card-header text-white bg-danger">Zona de Perigo</div>
            <div class="card-body">
                <h5 class="card-title">Excluir sua conta</h5>
                <p>Ao excluir sua conta, sua assinatura ativa será cancelada e todos os seus dados serão apagados. Esta ação é irreversível.</p>
                <form action="../actions/enviar_link_exclusao.php" method="POST">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($_SESSION['user_email']); ?>">
                    <button type="submit" class="btn btn-danger">Quero excluir minha conta</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>