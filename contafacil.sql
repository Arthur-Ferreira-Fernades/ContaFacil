-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 08/05/2025 às 04:06
-- Versão do servidor: 10.4.28-MariaDB
-- Versão do PHP: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `contafacil`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `despesas`
--

CREATE TABLE `despesas` (
  `id` int(11) NOT NULL,
  `grupo_id` int(11) NOT NULL,
  `tipo` enum('fixa','variavel') NOT NULL COMMENT 'Fixa ou Variável',
  `titulo` varchar(100) NOT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `dia_vencimento` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'Dia do mês (1-31) - Apenas para fixas',
  `data_vencimento` date DEFAULT NULL COMMENT 'Data específica - Apenas para variáveis',
  `pagador_id` int(11) NOT NULL,
  `data_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `despesas`
--

INSERT INTO `despesas` (`id`, `grupo_id`, `tipo`, `titulo`, `valor_total`, `dia_vencimento`, `data_vencimento`, `pagador_id`, `data_registro`) VALUES
(2, 2, 'fixa', 'Luz', 150.00, NULL, '2025-05-25', 3, '2025-05-05 01:12:20'),
(4, 2, 'fixa', 'Internet', 100.00, 24, '2025-05-24', 3, '2025-05-05 18:42:42'),
(5, 2, 'fixa', 'Ingles', 250.00, 25, '2025-05-25', 3, '2025-05-05 20:41:59'),
(6, 2, 'fixa', 'Agua', 85.00, NULL, '2025-05-15', 4, '2025-05-05 20:48:01'),
(9, 2, 'fixa', 'Netflix', 44.90, 10, '2025-05-10', 3, '2025-05-05 20:50:56'),
(10, 2, 'fixa', 'Spotify', 11.90, 10, '2025-05-10', 3, '2025-05-05 20:51:10'),
(11, 2, 'fixa', 'Spotify', 11.90, 10, '2025-05-10', 4, '2025-05-05 20:51:22'),
(12, 2, 'fixa', 'Anuidade Cartão', 90.80, 10, '2025-05-10', 3, '2025-05-05 20:51:35'),
(13, 2, 'fixa', 'Telefone', 75.00, NULL, '2025-05-11', 4, '2025-05-05 20:51:51'),
(16, 2, 'fixa', 'CrunchyRoll', 20.00, 10, '2025-05-10', 3, '2025-05-05 21:07:42');

-- --------------------------------------------------------

--
-- Estrutura para tabela `despesa_rateio`
--

CREATE TABLE `despesa_rateio` (
  `despesa_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `valor_parte` decimal(10,2) NOT NULL,
  `status_pagamento` enum('pago','pendente') DEFAULT 'pendente',
  `data_quitacao` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `grupos`
--

CREATE TABLE `grupos` (
  `id` int(11) NOT NULL,
  `nome_grupo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `responsavel_id` int(11) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `grupos`
--

INSERT INTO `grupos` (`id`, `nome_grupo`, `descricao`, `responsavel_id`, `data_criacao`) VALUES
(2, 'Familia', 'Eu e a sarinha', 3, '2025-05-05 00:33:37'),
(3, 'Familia 2', '', 4, '2025-05-05 19:32:38');

-- --------------------------------------------------------

--
-- Estrutura para tabela `grupo_membros`
--

CREATE TABLE `grupo_membros` (
  `grupo_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `data_adesao` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_admin` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `grupo_membros`
--

INSERT INTO `grupo_membros` (`grupo_id`, `usuario_id`, `data_adesao`, `is_admin`) VALUES
(2, 3, '2025-05-05 00:33:37', 1),
(2, 4, '2025-05-05 19:29:52', 0),
(3, 4, '2025-05-05 19:32:38', 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `pagamentos`
--

CREATE TABLE `pagamentos` (
  `id` int(11) NOT NULL,
  `despesa_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `data_pagamento` date NOT NULL,
  `mes_referencia` date NOT NULL,
  `valor_pago` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `solicitacoes_grupo`
--

CREATE TABLE `solicitacoes_grupo` (
  `id` int(11) NOT NULL,
  `grupo_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `status` enum('pendente','aceito','recusado') DEFAULT 'pendente',
  `data_solicitacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `solicitacoes_grupo`
--

INSERT INTO `solicitacoes_grupo` (`id`, `grupo_id`, `usuario_id`, `status`, `data_solicitacao`) VALUES
(1, 2, 4, 'aceito', '2025-05-05 19:29:44');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome_completo` varchar(255) NOT NULL,
  `cpf` char(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `confirmado` tinyint(1) DEFAULT 0,
  `token` varchar(255) DEFAULT NULL,
  `data_expiracao_token` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome_completo`, `cpf`, `email`, `senha_hash`, `data_cadastro`, `confirmado`, `token`, `data_expiracao_token`) VALUES
(3, 'Arthur Ferreira Fernandes', NULL, 'arthurfernandesferreira@hotmail.com', '$2y$10$spV/SqsRSbRkE/H1ejXqpO/5XnqILSot3c8fQwVN9wUuN64ptvnxi', '2025-05-05 00:15:24', 0, NULL, NULL),
(4, 'Sarah Alves Moya', NULL, 'sarah@123.com', '$2y$10$malZUhFSga7uQh.VUWYDH.DHVpXO.5yQ.GYAzOKFLMZ0VuZLPv4d6', '2025-05-05 18:46:47', 0, NULL, NULL);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `despesas`
--
ALTER TABLE `despesas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grupo_id` (`grupo_id`),
  ADD KEY `pagador_id` (`pagador_id`);

--
-- Índices de tabela `despesa_rateio`
--
ALTER TABLE `despesa_rateio`
  ADD PRIMARY KEY (`despesa_id`,`usuario_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `grupos`
--
ALTER TABLE `grupos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `responsavel_id` (`responsavel_id`);

--
-- Índices de tabela `grupo_membros`
--
ALTER TABLE `grupo_membros`
  ADD PRIMARY KEY (`grupo_id`,`usuario_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `despesa_id` (`despesa_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `solicitacoes_grupo`
--
ALTER TABLE `solicitacoes_grupo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grupo_id` (`grupo_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `cpf` (`cpf`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `despesas`
--
ALTER TABLE `despesas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `grupos`
--
ALTER TABLE `grupos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `solicitacoes_grupo`
--
ALTER TABLE `solicitacoes_grupo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `despesas`
--
ALTER TABLE `despesas`
  ADD CONSTRAINT `despesas_ibfk_1` FOREIGN KEY (`grupo_id`) REFERENCES `grupos` (`id`),
  ADD CONSTRAINT `despesas_ibfk_2` FOREIGN KEY (`pagador_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `despesa_rateio`
--
ALTER TABLE `despesa_rateio`
  ADD CONSTRAINT `despesa_rateio_ibfk_1` FOREIGN KEY (`despesa_id`) REFERENCES `despesas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `despesa_rateio_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `grupos`
--
ALTER TABLE `grupos`
  ADD CONSTRAINT `grupos_ibfk_1` FOREIGN KEY (`responsavel_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `grupo_membros`
--
ALTER TABLE `grupo_membros`
  ADD CONSTRAINT `grupo_membros_ibfk_1` FOREIGN KEY (`grupo_id`) REFERENCES `grupos` (`id`),
  ADD CONSTRAINT `grupo_membros_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD CONSTRAINT `pagamentos_ibfk_1` FOREIGN KEY (`despesa_id`) REFERENCES `despesas` (`id`),
  ADD CONSTRAINT `pagamentos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `solicitacoes_grupo`
--
ALTER TABLE `solicitacoes_grupo`
  ADD CONSTRAINT `solicitacoes_grupo_ibfk_1` FOREIGN KEY (`grupo_id`) REFERENCES `grupos` (`id`),
  ADD CONSTRAINT `solicitacoes_grupo_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
