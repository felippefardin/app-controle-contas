-- =========================================
-- SCHEMA COMPLETO MULTI-TENANT SAAS (VERSÃO FINAL)
-- =========================================

-- Usuários
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_criador` INT DEFAULT NULL,
  `nome` VARCHAR(100) NOT NULL,
  `tipo_pessoa` VARCHAR(10) NOT NULL,
  `documento` VARCHAR(20) DEFAULT NULL,
  `telefone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'usuario',
  `senha` VARCHAR(255) NOT NULL,
  `perfil` ENUM('padrao','admin') DEFAULT 'padrao',
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tipo` ENUM('admin','padrao') DEFAULT 'padrao',
  `owner_id` INT DEFAULT NULL,
  `foto` VARCHAR(255) DEFAULT 'default-profile.png',
  `banco_usuario` VARCHAR(100) NOT NULL DEFAULT 'app_controle_contas',
  `criado_por_usuario_id` INT DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'ativo',
  `nivel_acesso` VARCHAR(20) DEFAULT 'padrao',
  `tenant_id` VARCHAR(32) DEFAULT NULL,
  `documento_clean` VARCHAR(14) GENERATED ALWAYS AS (REGEXP_REPLACE(`documento`,'[^0-9]','')) STORED,
  `cpf` VARCHAR(14) DEFAULT NULL,
  `token_reset` VARCHAR(255) DEFAULT NULL,
  `token_expira_em` DATETIME DEFAULT NULL,
  `is_master` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_usuarios_email_tenant` (`email`,`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categorias
CREATE TABLE IF NOT EXISTS `categorias` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_usuario` INT NOT NULL,
  `id_pai` INT DEFAULT NULL,
  `nome` VARCHAR(100) NOT NULL,
  `tipo` ENUM('receita','despesa') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `fk_categorias_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pessoas / Fornecedores
CREATE TABLE IF NOT EXISTS `pessoas_fornecedores` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_usuario` INT NOT NULL,
  `nome` VARCHAR(255) NOT NULL,
  `cpf_cnpj` VARCHAR(20) DEFAULT NULL,
  `endereco` VARCHAR(255) DEFAULT NULL,
  `contato` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `tipo` ENUM('pessoa','fornecedor') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `fk_pessoas_fornecedores_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contas a Receber
CREATE TABLE IF NOT EXISTS `contas_receber` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `usuario_id` INT DEFAULT NULL,
  `id_categoria` INT DEFAULT NULL,
  `id_pessoa_fornecedor` INT DEFAULT NULL,
  `valor` DECIMAL(10,2) DEFAULT NULL,
  `status` ENUM('pendente','baixada') DEFAULT 'pendente',
  `forma_pagamento` VARCHAR(50) DEFAULT NULL,
  `data_vencimento` DATE DEFAULT NULL,
  `descricao` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_contas_receber_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_contas_receber_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_contas_receber_fornecedor` FOREIGN KEY (`id_pessoa_fornecedor`) REFERENCES `pessoas_fornecedores`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contas a Pagar
CREATE TABLE IF NOT EXISTS `contas_pagar` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `usuario_id` INT DEFAULT NULL,
  `id_categoria` INT DEFAULT NULL,
  `id_pessoa_fornecedor` INT DEFAULT NULL,
  `valor` DECIMAL(10,2) DEFAULT NULL,
  `status` ENUM('pendente','baixada') DEFAULT 'pendente',
  `forma_pagamento` VARCHAR(50) DEFAULT NULL,
  `data_vencimento` DATE DEFAULT NULL,
  `descricao` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_contas_pagar_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_contas_pagar_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_contas_pagar_fornecedor` FOREIGN KEY (`id_pessoa_fornecedor`) REFERENCES `pessoas_fornecedores`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contas Bancárias
CREATE TABLE IF NOT EXISTS `contas_bancarias` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_usuario` INT NOT NULL,
  `nome_banco` VARCHAR(100) NOT NULL,
  `agencia` VARCHAR(20) DEFAULT NULL,
  `conta` VARCHAR(20) NOT NULL,
  `tipo_conta` VARCHAR(50) DEFAULT NULL,
  `chave_pix` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `fk_contas_bancarias_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Produtos
CREATE TABLE IF NOT EXISTS `produtos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_usuario` INT NOT NULL,
  `nome` VARCHAR(255) NOT NULL,
  `descricao` TEXT,
  `preco_compra` DECIMAL(10,2) DEFAULT NULL,
  `preco_venda` DECIMAL(10,2) DEFAULT NULL,
  `quantidade_estoque` INT NOT NULL,
  `unidade_medida` VARCHAR(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `fk_produtos_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vendas
CREATE TABLE IF NOT EXISTS `vendas` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_usuario` INT NOT NULL,
  `id_cliente` INT NOT NULL,
  `data_venda` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `valor_total` DECIMAL(10,2) NOT NULL,
  `desconto` DECIMAL(10,2) DEFAULT '0.00',
  `observacao` TEXT,
  `forma_pagamento` VARCHAR(50) NOT NULL,
  `numero_parcelas` INT DEFAULT '1',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_vendas_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vendas_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `pessoas_fornecedores`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Itens da Venda
CREATE TABLE IF NOT EXISTS `venda_items` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_venda` INT NOT NULL,
  `id_produto` INT NOT NULL,
  `quantidade` INT NOT NULL,
  `preco_unitario` DECIMAL(10,2) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_venda_items_venda` FOREIGN KEY (`id_venda`) REFERENCES `vendas`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_venda_items_produto` FOREIGN KEY (`id_produto`) REFERENCES `produtos`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Movimento Estoque
CREATE TABLE IF NOT EXISTS `movimento_estoque` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_produto` INT NOT NULL,
  `id_usuario` INT NOT NULL,
  `id_pessoa_fornecedor` INT DEFAULT NULL,
  `tipo` ENUM('entrada','saida') NOT NULL,
  `quantidade` INT NOT NULL,
  `data_movimento` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `observacao` TEXT,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_mov_estoque_produto` FOREIGN KEY (`id_produto`) REFERENCES `produtos`(`id`),
  CONSTRAINT `fk_mov_estoque_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id`),
  CONSTRAINT `fk_mov_estoque_fornecedor` FOREIGN KEY (`id_pessoa_fornecedor`) REFERENCES `pessoas_fornecedores`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compras
CREATE TABLE IF NOT EXISTS `compras` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_usuario` INT NOT NULL,
  `id_fornecedor` INT NOT NULL,
  `data_compra` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `valor_total` DECIMAL(10,2) NOT NULL,
  `observacao` TEXT,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_compras_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id`),
  CONSTRAINT `fk_compras_fornecedor` FOREIGN KEY (`id_fornecedor`) REFERENCES `pessoas_fornecedores`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Itens da Compra
CREATE TABLE IF NOT EXISTS `compra_items` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_compra` INT NOT NULL,
  `id_produto` INT NOT NULL,
  `quantidade` INT NOT NULL,
  `preco_unitario` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_compra_items_compra` FOREIGN KEY (`id_compra`) REFERENCES `compras`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_compra_items_produto` FOREIGN KEY (`id_produto`) REFERENCES `produtos`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Caixa Diário
CREATE TABLE IF NOT EXISTS `caixa_diario` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `data` DATE NOT NULL,
  `valor` DECIMAL(10,2) NOT NULL,
  `tipo` ENUM('entrada','saida') NOT NULL DEFAULT 'entrada',
  `descricao` VARCHAR(255) DEFAULT NULL,
  `id_venda` INT DEFAULT NULL,
  `usuario_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_caixa_diario` (`data`,`usuario_id`),
  CONSTRAINT `fk_caixa_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notas Fiscais
CREATE TABLE IF NOT EXISTS `notas_fiscais` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_venda` INT NOT NULL,
  `ambiente` INT NOT NULL COMMENT '1=Produção, 2=Homologação',
  `status` VARCHAR(50) NOT NULL COMMENT 'autorizada, cancelada, erro',
  `chave_acesso` VARCHAR(44) DEFAULT NULL,
  `protocolo` VARCHAR(100) DEFAULT NULL,
  `xml_path` VARCHAR(255) DEFAULT NULL,
  `mensagem_erro` TEXT,
  `data_emissao` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_nf_venda` FOREIGN KEY (`id_venda`) REFERENCES `vendas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Logs Webhook
CREATE TABLE IF NOT EXISTS `logs_webhook` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tipo` VARCHAR(100),
  `acao` VARCHAR(100),
  `data_id` VARCHAR(100),
  `payload` TEXT,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Solicitações de Exclusão
CREATE TABLE IF NOT EXISTS `solicitacoes_exclusao` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_usuario` INT NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `expira_em` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_solicitacoes_exclusao_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configurações do Tenant
CREATE TABLE IF NOT EXISTS `configuracoes_tenant` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `chave` VARCHAR(100) NOT NULL,
  `valor` TEXT,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_chave` (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tenants (master)
CREATE TABLE IF NOT EXISTS `tenants` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(255) DEFAULT NULL,
  `nome_empresa` VARCHAR(255) NOT NULL,
  `admin_email` VARCHAR(255) NOT NULL,
  `subdominio` VARCHAR(191) DEFAULT NULL,
  `db_host` VARCHAR(255) NOT NULL,
  `db_database` VARCHAR(255) NOT NULL,
  `db_user` VARCHAR(255) NOT NULL,
  `db_password` VARCHAR(255) NOT NULL,
  `status_assinatura` VARCHAR(50) DEFAULT 'ativo',
  `role` VARCHAR(50) NOT NULL DEFAULT 'usuario',
  `data_criacao` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_email` (`admin_email`),
  UNIQUE KEY `subdominio` (`subdominio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
