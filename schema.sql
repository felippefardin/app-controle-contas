-- Schema para a criação de um novo banco de dados de cliente (tenant)


-- Tabela de Usuários do Cliente
CREATE TABLE IF NOT EXISTS usuarios (
  id INT NOT NULL AUTO_INCREMENT,
  id_criador INT DEFAULT NULL,
  nome VARCHAR(100) NOT NULL,
  tipo_pessoa VARCHAR(10) NOT NULL,
  documento VARCHAR(20) DEFAULT NULL,
  telefone VARCHAR(20) DEFAULT NULL,
  email VARCHAR(100) NOT NULL,
  role VARCHAR(50) NOT NULL DEFAULT 'usuario',
  senha VARCHAR(255) NOT NULL,
  perfil ENUM('padrao','admin') DEFAULT 'padrao',
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  tipo ENUM('admin','padrao') DEFAULT 'padrao',
  owner_id INT DEFAULT NULL,
  foto VARCHAR(255) DEFAULT 'default-profile.png',
  banco_usuario VARCHAR(100) NOT NULL DEFAULT 'app_controle_contas',
  criado_por_usuario_id INT DEFAULT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ativo',
  nivel_acesso VARCHAR(20) DEFAULT 'padrao',
  tenant_id VARCHAR(32) DEFAULT NULL,
  documento_clean VARCHAR(14) GENERATED ALWAYS AS (REGEXP_REPLACE(documento,'[^0-9]','')) STORED,
  cpf VARCHAR(14) DEFAULT NULL,
  token_reset VARCHAR(255) DEFAULT NULL,
  token_expira_em DATETIME DEFAULT NULL,
  is_master TINYINT(1) DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY ux_usuarios_email_tenant (email, tenant_id),
  FOREIGN KEY (tenant_id) REFERENCES tenants(tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `movimento_estoque` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_produto` int NOT NULL,
  `id_usuario` int NOT NULL,
  `id_pessoa_fornecedor` int DEFAULT NULL,
  `tipo` enum('entrada','saida') NOT NULL,
  `quantidade` int NOT NULL,
  `data_movimento` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `observacao` text,
  PRIMARY KEY (`id`),
  KEY `id_produto` (`id_produto`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_pessoa_fornecedor` (`id_pessoa_fornecedor`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `compras` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `id_fornecedor` int NOT NULL,
  `data_compra` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `valor_total` decimal(10,2) NOT NULL,
  `observacao` text,
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_fornecedor` (`id_fornecedor`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `compra_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_compra` int NOT NULL,
  `id_produto` int NOT NULL,
  `quantidade` int NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_compra` (`id_compra`),
  KEY `id_produto` (`id_produto`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS solicitacoes_exclusao (
  id INT NOT NULL AUTO_INCREMENT,
  id_usuario INT NOT NULL,
  token VARCHAR(64) NOT NULL,
  expira_em DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY fk_solicitacoes_exclusao_usuario (id_usuario),
  CONSTRAINT fk_solicitacoes_exclusao_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuarios (id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;



CREATE TABLE `empresa_config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `razao_social` varchar(255) DEFAULT NULL,
  `cnpj` varchar(14) DEFAULT NULL,
  `fantasia` varchar(255) DEFAULT NULL,
  `ie` varchar(20) DEFAULT NULL,
  `logradouro` varchar(255) DEFAULT NULL,
  `numero` varchar(10) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `municipio` varchar(100) DEFAULT NULL,
  `uf` char(2) DEFAULT NULL,
  `cep` varchar(8) DEFAULT NULL,
  `cod_municipio` varchar(7) DEFAULT NULL,
  `regime_tributario` int DEFAULT NULL,
  `csc` varchar(100) DEFAULT NULL,
  `csc_id` varchar(10) DEFAULT NULL,
  `certificado_a1_path` varchar(255) DEFAULT NULL,
  `certificado_senha` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cnpj` (`cnpj`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `notas_fiscais` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_venda` int NOT NULL,
  `ambiente` int NOT NULL COMMENT '1=Produção, 2=Homologação',
  `status` varchar(50) NOT NULL COMMENT 'autorizada, cancelada, erro',
  `chave_acesso` varchar(44) DEFAULT NULL,
  `protocolo` varchar(100) DEFAULT NULL,
  `xml_path` varchar(255) DEFAULT NULL,
  `mensagem_erro` text,
  `data_emissao` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabela para os Planos de Assinatura
CREATE TABLE `planos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `ciclo` enum('mensal','trimestral','anual') NOT NULL,
  `descricao` text,
  `mercadopago_plan_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `assinaturas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `email` varchar(255) NOT NULL,
  `plano` varchar(50) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT 'pendente',
  `mp_preapproval_id` varchar(255) DEFAULT NULL,
  `data_criacao` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_assinaturas_usuario` (`id_usuario`),
  CONSTRAINT `fk_assinaturas_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;



CREATE TABLE `pagamentos_historico` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `id_assinatura` int NOT NULL,
  `mercadopago_payment_id` varchar(255) DEFAULT NULL,
  `data_pagamento` datetime NOT NULL,
  `valor_pago` decimal(10,2) NOT NULL,
  `status` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_assinatura` (`id_assinatura`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `configuracoes_tenant` (
  `id` int NOT NULL AUTO_INCREMENT,
  `chave` varchar(100) NOT NULL,
  `valor` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_chave` (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS tenants (
  tenant_id VARCHAR(32) NOT NULL PRIMARY KEY,        -- ID do tenant (md5 ou uniqid)
  usuario_id INT NOT NULL,                            -- ID do usuário admin no master
  nome VARCHAR(255) NOT NULL,                         -- Nome do usuário ou empresa
  nome_empresa VARCHAR(255) DEFAULT NULL,             -- Nome da empresa (opcional)
  admin_email VARCHAR(255) NOT NULL,                  -- Email do admin
  db_host VARCHAR(100) NOT NULL DEFAULT 'localhost',  -- Host do banco do tenant
  db_database VARCHAR(255) NOT NULL,                 -- Nome do banco do tenant
  db_user VARCHAR(100) NOT NULL,                     -- Usuário MySQL do tenant
  db_password VARCHAR(255) NOT NULL,                 -- Senha do usuário MySQL do tenant
  status_assinatura ENUM('trial', 'ativo', 'cancelado') DEFAULT 'trial',
  role ENUM('usuario','admin') DEFAULT 'usuario',    -- Papel do usuário no tenant
  data_inicio_teste TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  plano_atual ENUM('mensal', 'trimestral') DEFAULT 'mensal',
  senha VARCHAR(255) NOT NULL,                        -- Hash do admin
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_usuario_master FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE logs_webhook (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(100),
    acao VARCHAR(100),
    data_id VARCHAR(100),
    payload TEXT,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
);

