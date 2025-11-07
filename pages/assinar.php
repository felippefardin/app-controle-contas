<?php
require_once '../vendor/autoload.php'; // apenas autoload do Composer, sem sessão obrigatória

// Planos disponíveis
$planos = [
    ["id" => "plano_basico", "nome" => "Básico", "preco" => 29.90, "descricao" => "Acesso mensal básico ao sistema"],
    ["id" => "plano_pro", "nome" => "Pro", "preco" => 59.90, "descricao" => "Recursos avançados e relatórios"],
    ["id" => "plano_premium", "nome" => "Premium", "preco" => 99.90, "descricao" => "Todos os recursos + suporte prioritário"]
];

// Fluxo de assinatura
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plano_id'], $_POST['email_usuario'])) {
    $email_usuario_formulario = filter_var(trim($_POST['email_usuario']), FILTER_VALIDATE_EMAIL); // Renomeado para clareza
    $plano_id = $_POST['plano_id'];

    if (!$email_usuario_formulario) {
        $erro = "Informe um e-mail válido para continuar.";
    } else {
        $plano = array_filter($planos, fn($p) => $p['id'] === $plano_id);
        $plano = reset($plano);

        if ($plano) {
            $start_date = gmdate("Y-m-d\TH:i:s.000\Z", strtotime("+1 minute"));
            $end_date   = gmdate("Y-m-d\TH:i:s.000\Z", strtotime("+1 year"));

            // URL de retorno após assinatura
            $back_url = "https://hydrometallurgical-unsententiously-deirdre.ngrok-free.dev/app-controle-contas/pages/home.php";
            
            // --- AJUSTE CRÍTICO PARA TESTE ---
            // 1. O e-mail DEVE ser um "Comprador de Teste" do seu painel MP.
            // 2. O e-mail DEVE terminar com @testuser.com
            
            // ⚠️ VÁ NO SEU PAINEL DO MP > CONTAS DE TESTE E PEGUE UM E-MAIL DE COMPRADOR VÁLIDO
            $email_comprador_teste = "TEST_USER_XXXXXX@testuser.com"; // ⚠️ TROQUE AQUI PELO SEU E-MAIL DE TESTE

            $dados = [
                // ▶️ CORRIGIDO: A API de Assinatura (/preapproval) espera 'payer_email' no nível raiz.
                "payer_email" => $email_comprador_teste, 
                
                "auto_recurring" => [
                    "frequency" => 1,
                    "frequency_type" => "months",
                    "transaction_amount" => $plano['preco'],
                    "currency_id" => "BRL",
                    "start_date" => $start_date,
                    "end_date" => $end_date
                ],
                "back_url" => $back_url,
                "reason" => "Assinatura do plano {$plano['nome']}",
                // Adiciona o e-mail do usuário real (do formulário) como metadados
                // para que você possa identificá-lo no webhook
                "metadata" => [
                    "email_usuario_real" => $email_usuario_formulario
                ]
            ];
            // --- FIM DO AJUSTE ---


            $access_token = "TEST-434665267442294-110610-a6c0df937492f2c030236826d3634d8c-456404lippefardin";

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
                header("Location: " . $resposta['init_point']);
                exit;
            } else {
                $erro = "Erro ao criar assinatura: " . htmlspecialchars(print_r($resposta, true));
            }
        } else {
            $erro = "Plano inválido.";
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
body { font-family: Arial; background:#f7f9fc; margin:0; }
.container { max-width:900px; margin:60px auto; background:#fff; border-radius:16px; padding:30px; }
h1 { text-align:center; color:#333; }
.planos { display:flex; gap:20px; flex-wrap:wrap; justify-content:center; }
.plano { background:#f4f6f8; padding:20px; width:250px; border-radius:12px; text-align:center; }
.preco { font-size:24px; color:#2d89ef; font-weight:bold; }
button { background:#2d89ef; color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; }
button:hover { background:#1b5cb8; }
.erro { color:red; font-weight:bold; margin-bottom:15px; text-align:center; }
label { display:block; margin-top:10px; font-weight:bold; }
input[type=email] { width:100%; padding:8px; margin-top:5px; border-radius:4px; border:1px solid #ccc; }
</style>
</head>
<body>
<div class="container">
<h1>Escolha seu Plano</h1>

<?php if ($erro): ?>
<div class="erro"><?= $erro ?></div>
<?php endif; ?>

<div class="planos">
<?php foreach ($planos as $plano): ?>
    <div class="plano">
        <h3><?= htmlspecialchars($plano['nome']) ?></h3>
        <p><?= htmlspecialchars($plano['descricao']) ?></p>
        <p class="preco">R$ <?= number_format($plano['preco'],2,',','.') ?>/mês</p>
        <form method="POST">
            <input type="hidden" name="plano_id" value="<?= htmlspecialchars($plano['id']) ?>">
            <label for="email_<?= $plano['id'] ?>">Seu e-mail</label>
            <input type="email" id="email_<?= $plano['id'] ?>" name="email_usuario" placeholder="exemplo@dominio.com" required>
            <button type="submit">Assinar</button>
        </form>
    </div>
<?php endforeach; ?>
</div>
</div>
</body>
</html>