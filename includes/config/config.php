<?php

// Carrega o autoloader do Composer para ter acesso às bibliotecas
require_once __DIR__ . '/../../vendor/autoload.php';

// Define o caminho para o diretório raiz do projeto
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// (Pronto! Sem o colchete final)