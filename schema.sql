
-- Schema para a criação de um novo banco de dados de cliente (tenant)


-- Tabela de Usuários do Cliente
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_criador` int DEFAULT NULL,
  `nome` varchar(100) NOT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `perfil` enum('padrao','admin') DEFAULT 'padrao',
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `foto` varchar(255) DEFAULT 'default-profile.png', 
  `status` varchar(20) NOT NULL DEFAULT 'ativo',
  `nivel_acesso` varchar(20) DEFAULT 'padrao',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  PRIMARY KEY (`id`),
  KEY `idx_contas_receber_usuario` (`usuario_id`),
  KEY `id_categoria` (`id_categoria`),
  KEY `id_pessoa_fornecedor` (`id_pessoa_fornecedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `usuario_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `data` (`data`,`usuario_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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