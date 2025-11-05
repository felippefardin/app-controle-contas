<?php
// Arquivo: includes/config/config.php

// 1. Carrega o autoloader do Composer
require_once __DIR__ . '/../../vendor/autoload.php';

// 2. Carrega variáveis de ambiente (.env)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// 3. Configura o SDK do Mercado Pago (nova versão)
use MercadoPago\MercadoPagoConfig;

// ✅ Usa o Access Token salvo no .env
MercadoPagoConfig::setAccessToken($_ENV['MERCADOPAGO_ACCESS_TOKEN'] ?? '');

// 4. Carrega conexão com o banco de dados
require_once __DIR__ . '/../../database.php';
