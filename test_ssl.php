<?php
require __DIR__ . '/../vendor/autoload.php';

use Gerencianet\Gerencianet;
use Gerencianet\Exception\GerencianetException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Caminho absoluto do cacert.pem
$cacertPath = "C:/wamp64/bin/php/php8.2.0/extras/ssl/cacert.pem"; 
// ⚠️ Troque php8.2.0 pela versão exata do seu PHP

$options = [
    'client_id' => 'SEU_CLIENT_ID',
    'client_secret' => 'SEU_CLIENT_SECRET',
    'sandbox' => true,
    'timeout' => 30,
    'debug' => false,
];

// 🔧 Forçar cURL a usar o cacert.pem
$options['pix_cert'] = $cacertPath; 

try {
    $api = new Gerencianet($options);

    $body = [
        'items' => [
            [
                'name' => 'Serviço de Cobrança',
                'amount' => 1,
                'value' => 120000 // R$1200,00 em centavos
            ]
        ],
        'payment' => [
            'banking_billet' => [
                'expire_at' => date('Y-m-d', strtotime('+5 days')),
                'customer' => [
                    'name' => 'Cliente Teste',
                    'cpf' => '12345678909',
                    'phone_number' => '31999999999',
                ]
            ]
        ]
    ];

    $charge = $api->createOneStepCharge([], $body);

    echo "<pre>";
    print_r($charge);
    echo "</pre>";

    // =============== ENVIO DE EMAIL ===============
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'felippefardin@gmail.com';
        $mail->Password   = 'nxpt njcd flgu noib'; // senha de app do Gmail
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('felippefardin@gmail.com', 'Financeiro - Minha Empresa');
        $mail->addAddress("email_cliente@teste.com", "Cliente Teste");

        $mail->isHTML(true);
        $mail->Subject = "Cobrança #123";
        $mail->Body    = "
            <h2>Cobrança Gerada</h2>
            <p>Olá Cliente Teste,</p>
            <p>Segue o link para pagamento:</p>
            <p><a href='{$charge['data']['link']}'>Clique aqui para pagar</a></p>
        ";

        $mail->send();
        echo "✅ Cobrança enviada por e-mail!";
    } catch (Exception $e) {
        echo "❌ Erro ao enviar e-mail: {$mail->ErrorInfo}";
    }

} catch (GerencianetException $e) {
    echo "Erro GN: " . $e->getMessage();
} catch (Exception $e) {
    echo "Erro geral: " . $e->getMessage();
}
