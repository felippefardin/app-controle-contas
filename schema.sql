-- Schema para a criação de um novo banco de dados de cliente (tenant)


-- Tabela de Usuários do Cliente
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_criador` int DEFAULT NULL,
  `nome` varchar(100) NOT NULL,
  `tipo_pessoa` varchar(10) NOT NULL,
  `documento` varchar(20) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `perfil` enum('padrao','admin') DEFAULT 'padrao',
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tipo` enum('admin','padrao') DEFAULT 'padrao',
  `owner_id` int DEFAULT NULL,
  `foto` varchar(255) DEFAULT 'default-profile.png',
  `banco_usuario` varchar(100) NOT NULL DEFAULT 'app_controle_contas',
  `criado_por_usuario_id` int DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'ativo',
  `nivel_acesso` varchar(20) DEFAULT 'padrao',
  `tenant_id` int DEFAULT NULL,
  `documento_clean` varchar(14) GENERATED ALWAYS AS (regexp_replace(`documento`,_utf8mb4'[^0-9]',_utf8mb4'')) STORED,
  `cpf` varchar(14) DEFAULT NULL,
  `token_reset` varchar(255) DEFAULT NULL,
  `token_expira_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `ux_usuarios_email` (`email`),
  UNIQUE KEY `email_2` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;



-- Tabela de Clientes e Fornecedores
CREATE TABLE `pessoas_fornecedores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `nome` varchar(255) NOT NULL,
  `cpf_cnpj` varchar(20) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `contato` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `tipo` enum('pessoa','fornecedor') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabela de Categorias
CREATE TABLE `categorias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `id_pai` int DEFAULT NULL,
  `nome` varchar(100) NOT NULL,
  `tipo` enum('receita','despesa') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_pai` (`id_pai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabela de Contas Bancárias
CREATE TABLE `contas_bancarias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `nome_banco` varchar(100) NOT NULL,
  `agencia` varchar(20) DEFAULT NULL,
  `conta` varchar(20) NOT NULL,
  `tipo_conta` varchar(50) DEFAULT NULL,
  `chave_pix` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabela de Contas a Pagar
CREATE TABLE `contas_pagar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int DEFAULT NULL,
  `enviar_email` char(1) NOT NULL DEFAULT 'N',
  `fornecedor` varchar(100) DEFAULT NULL,
  `data_vencimento` date DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  `id_categoria` int DEFAULT NULL,
  `status` enum('pendente','baixada') DEFAULT 'pendente',
  `forma_pagamento` varchar(50) DEFAULT NULL,
  `data_baixa` date DEFAULT NULL,
  `baixado_por` int DEFAULT NULL,
  `juros` decimal(10,2) DEFAULT '0.00',
  `comprovante` varchar(255) DEFAULT NULL,
  `data_pagamento` datetime DEFAULT NULL,
  `id_pessoa_fornecedor` int DEFAULT NULL,
  `descricao` TEXT DEFAULT NULL, -- <-- CAMPO ADICIONADO
  PRIMARY KEY (`id`),
  KEY `idx_contas_pagar_usuario` (`usuario_id`),
  KEY `id_categoria` (`id_categoria`),
  KEY `id_pessoa_fornecedor` (`id_pessoa_fornecedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabela de Contas a Receber
CREATE TABLE `contas_receber` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int DEFAULT NULL,
  `responsavel` varchar(100) DEFAULT NULL,
  `data_vencimento` date DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  `id_categoria` int DEFAULT NULL,
  `status` enum('pendente','baixada') DEFAULT 'pendente',
  `baixado_por_usuario_id` int DEFAULT NULL,
  `pagamento_token` varchar(255) DEFAULT NULL,
  `forma_pagamento` varchar(50) DEFAULT NULL,
  `data_baixa` date DEFAULT NULL,
  `baixado_por` int DEFAULT NULL,
  `fornecedor` varchar(255) DEFAULT NULL,
  `pix_payload` text,
  `boleto_link` varchar(255) DEFAULT NULL,
  `juros` decimal(10,2) DEFAULT '0.00',
  `comprovante` varchar(255) DEFAULT NULL,
  `data_pagamento` datetime DEFAULT NULL,
  `id_pessoa_fornecedor` int DEFAULT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `id_venda` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_contas_receber_usuario` (`usuario_id`),
  KEY `fk_contas_receber_baixado_por` (`baixado_por`),
  KEY `id_categoria` (`id_categoria`),
  KEY `id_pessoa_fornecedor` (`id_pessoa_fornecedor`),
  CONSTRAINT `contas_receber_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contas_receber_ibfk_2` FOREIGN KEY (`id_pessoa_fornecedor`) REFERENCES `pessoas_fornecedores` (`id`),
  CONSTRAINT `fk_contas_receber_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- Tabela de Produtos
CREATE TABLE `produtos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `nome` varchar(255) NOT NULL,
  `descricao` text,
  `preco_compra` decimal(10,2) DEFAULT NULL,
  `preco_venda` decimal(10,2) DEFAULT NULL,
  `quantidade_estoque` int NOT NULL,
  `unidade_medida` varchar(50) DEFAULT NULL,
  `data_cadastro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `quantidade_minima` int DEFAULT '0',
  `ncm` varchar(8) DEFAULT NULL COMMENT 'Nomenclatura Comum do Mercosul',
  `cfop` varchar(4) DEFAULT NULL COMMENT 'Código Fiscal de Operações e Prestações',
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabela de Vendas
CREATE TABLE `vendas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `id_cliente` int NOT NULL,
  `data_venda` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `valor_total` decimal(10,2) NOT NULL,
  `desconto` decimal(10,2) DEFAULT '0.00',
  `observacao` text,
  `forma_pagamento` varchar(50) NOT NULL,
  `numero_parcelas` int DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_cliente` (`id_cliente`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabela de Itens da Venda
CREATE TABLE `venda_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_venda` int NOT NULL,
  `id_produto` int NOT NULL,
  `quantidade` int NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `forma_pagamento` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_venda` (`id_venda`),
  KEY `id_produto` (`id_produto`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Outras tabelas essenciais
CREATE TABLE `caixa_diario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `data` date NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `tipo` varchar(50) NOT NULL DEFAULT 'entrada',
  `descricao` varchar(255) DEFAULT NULL,
  `id_venda` int DEFAULT NULL,
  `usuario_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `data` (`data`,`usuario_id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


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

CREATE TABLE `tenants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) DEFAULT NULL,
  `nome_empresa` varchar(255) NOT NULL,
  `admin_email` varchar(255) NOT NULL,
  `subdominio` varchar(191) DEFAULT NULL,
  `db_host` varchar(255) NOT NULL,
  `db_database` varchar(255) NOT NULL,
  `db_user` varchar(255) NOT NULL,
  `db_password` varchar(255) NOT NULL,
  `stripe_customer_id` varchar(255) DEFAULT NULL,
  `status_assinatura` varchar(50) DEFAULT 'ativo',
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `senha` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_email` (`admin_email`),
  UNIQUE KEY `subdominio` (`subdominio`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE logs_webhook (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(100),
    acao VARCHAR(100),
    data_id VARCHAR(100),
    payload TEXT,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
);

