<?php
// includes/config/config.php

// 1. Carrega o autoloader do Composer
require_once __DIR__ . '/../../vendor/autoload.php';

// 2. Carrega variáveis de ambiente (.env)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// 3. Configura o SDK do Mercado Pago
use MercadoPago\MercadoPagoConfig;

// ✅ Define o Access Token — usa o valor do .env ou, se vazio, o de teste abaixo
MercadoPagoConfig::setAccessToken($_ENV['MERCADOPAGO_ACCESS_TOKEN'] ?? 'TEST-434665267442294-110610-a6c0df937492f2c030236826d3634d8c-456404185');

// 4. Conexão com o banco de dados
require_once __DIR__ . '/../../database.php';
