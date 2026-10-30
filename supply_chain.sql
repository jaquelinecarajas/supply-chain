-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 28/10/2026 às 19:49
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `supply_chain`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `anexos`
--

CREATE TABLE `anexos` (
  `id` int(11) NOT NULL,
  `ordem_id` int(11) DEFAULT NULL,
  `pedido_id` int(11) DEFAULT NULL,
  `pagamento_id` int(11) DEFAULT NULL,
  `nome_original` varchar(255) NOT NULL,
  `caminho_arquivo` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `tamanho_bytes` int(11) NOT NULL,
  `enviado_por` int(11) NOT NULL,
  `setor_id` int(11) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `bom_itens`
--

CREATE TABLE `bom_itens` (
  `id` int(11) NOT NULL,
  `ordem_id` int(11) NOT NULL,
  `codigo_material` varchar(50) NOT NULL,
  `descricao` varchar(200) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `unidade` varchar(10) DEFAULT 'un'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `bom_itens`
--

INSERT INTO `bom_itens` (`id`, `ordem_id`, `codigo_material`, `descricao`, `quantidade`, `unidade`) VALUES
(4, 4, 'p-1028', 'parafuso de aço 10mm', 12, 'un'),
(5, 5, 'p-1028', 'parafuso de aço 10mm', 1, 'un'),
(6, 6, 'p-1028', 'parafuso de aço 10mm', 11, 'un'),
(7, 7, '10101', 'Matheus lindão', 1, 'un');

-- --------------------------------------------------------

--
-- Estrutura para tabela `estoque_movimentacoes`
--

CREATE TABLE `estoque_movimentacoes` (
  `id` int(11) NOT NULL,
  `ordem_id` int(11) NOT NULL,
  `codigo_material` varchar(50) NOT NULL,
  `tipo` enum('entrada','saida') NOT NULL,
  `quantidade` int(11) NOT NULL,
  `registrado_por` int(11) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `estoque_movimentacoes`
--

INSERT INTO `estoque_movimentacoes` (`id`, `ordem_id`, `codigo_material`, `tipo`, `quantidade`, `registrado_por`, `criado_em`) VALUES
(1, 4, 'p-1028', 'entrada', 12, 1, '2026-10-27 15:37:13');

-- --------------------------------------------------------

--
-- Estrutura para tabela `fornecedores`
--

CREATE TABLE `fornecedores` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `cnpj` varchar(20) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `prazo_medio_dias` int(11) DEFAULT NULL,
  `contato` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `fornecedores`
--

INSERT INTO `fornecedores` (`id`, `nome`, `cnpj`, `cidade`, `estado`, `prazo_medio_dias`, `contato`) VALUES
(1, 'jaqueline oliveira da silva', '0111581', NULL, NULL, 6, NULL),
(2, 'jaqueline oliveira da silva', '0111581', 'rio de janeiro', 'RJ', 6, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_ordens`
--

CREATE TABLE `historico_ordens` (
  `id` int(11) NOT NULL,
  `ordem_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `acao` varchar(255) NOT NULL,
  `detalhes` text DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `historico_ordens`
--

INSERT INTO `historico_ordens` (`id`, `ordem_id`, `usuario_id`, `acao`, `detalhes`, `criado_em`) VALUES
(4, 4, 1, 'Desmontagem Concluída', 'O trem de pouso foi desmontado e a lista BOM foi enviada para o Planejamento.', '2026-10-27 15:35:33'),
(5, 4, 1, 'Remontagem Finalizada', 'O trem de pouso foi remontado com sucesso e a ordem foi finalizada.', '2026-10-27 15:37:23'),
(6, 5, 1, 'Desmontagem Concluída', 'O trem de pouso foi desmontado e a lista BOM foi enviada para o Planejamento.', '2026-10-27 15:43:05'),
(7, 3, 1, 'Movimentação Kanban', 'Moveu a tarefa \'Acompanhar rota de entrega da OP-2026-0003\' para em andamento', '2026-10-27 15:43:51'),
(8, 6, 1, 'Desmontagem Concluída', 'O trem de pouso foi desmontado e a lista BOM foi enviada para o Planejamento.', '2026-10-27 15:54:06'),
(9, 6, 1, 'Planejamento Aprovado', 'A lista BOM foi revisada e aprovada. A ordem foi enviada para o setor de Compras.', '2026-10-27 16:01:07'),
(10, 6, 1, 'Movimentação Kanban', 'Moveu a tarefa \'Analisar BOM da OP-2026-0006\' para a fazer', '2026-10-28 14:02:00'),
(11, 3, 1, 'Taxa de Chegada Paga', 'A taxa no valor de R$ 200,00 foi paga (NF: 005).', '2026-10-28 14:10:51'),
(12, 3, 1, 'Material Recebido', 'Os materiais deram entrada no estoque e a ordem retornou para a Produção.', '2026-10-28 14:10:58'),
(13, 3, 1, 'Remontagem Finalizada', 'O trem de pouso foi remontado com sucesso e a ordem foi finalizada.', '2026-10-28 14:11:08'),
(14, 7, 1, 'Desmontagem Concluída', 'O trem de pouso foi desmontado e a lista BOM foi enviada para o Planejamento.', '2026-10-28 14:33:22'),
(15, 7, 1, 'Planejamento Aprovado', 'A lista BOM foi revisada e aprovada. A ordem foi enviada para o setor de Compras.', '2026-10-28 14:34:25'),
(16, 7, 1, 'Pedido de Compra Gerado', 'Foi criado um pedido no valor de R$ 1.000,00 com prazo de entrega para 03/11/2026.', '2026-10-28 14:35:13'),
(17, 7, 1, 'Pagamento Efetuado', 'O pedido no valor de R$ 1.000,00 foi pago. Nota Fiscal: 101010101010.', '2026-10-28 14:35:31'),
(18, 7, 1, 'Rota Atualizada', 'A rota foi salva. Origem: - | Destino: Fábrica Matriz | Status: aguardando', '2026-10-28 14:35:56'),
(19, 7, 1, 'Rota Atualizada', 'A rota foi salva. Origem: - | Destino: Fábrica Matriz | Status: em_transito', '2026-10-28 14:36:03'),
(20, 7, 1, 'Rota Atualizada', 'A rota foi salva. Origem: - | Destino: Fábrica Matriz | Status: chegou', '2026-10-28 14:36:14');

-- --------------------------------------------------------

--
-- Estrutura para tabela `kanban_tarefas`
--

CREATE TABLE `kanban_tarefas` (
  `id` int(11) NOT NULL,
  `ordem_id` int(11) NOT NULL,
  `setor_id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `coluna` enum('a_fazer','em_andamento','concluido') DEFAULT 'a_fazer',
  `sla_horas` int(11) DEFAULT NULL,
  `iniciado_em` datetime DEFAULT NULL,
  `concluido_em` datetime DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `kanban_tarefas`
--

INSERT INTO `kanban_tarefas` (`id`, `ordem_id`, `setor_id`, `titulo`, `coluna`, `sla_horas`, `iniciado_em`, `concluido_em`, `criado_em`) VALUES
(29, 6, 2, 'Analisar BOM da OP-2026-0006', 'a_fazer', 24, NULL, NULL, '2026-10-27 15:54:06'),
(30, 6, 3, 'Cotar preços e fornecedores para a OP-2026-0006', 'a_fazer', 48, NULL, NULL, '2026-10-27 16:01:07'),
(31, 3, 1, 'Iniciar remontagem da OP-2026-0003', 'concluido', 24, NULL, '2026-10-28 14:11:08', '2026-10-28 14:10:58'),
(32, 7, 2, 'Analisar BOM da OP-2026-0007', 'concluido', 24, NULL, '2026-10-28 14:34:25', '2026-10-28 14:33:22'),
(33, 7, 3, 'Cotar preços e fornecedores para a OP-2026-0007', 'concluido', 48, NULL, '2026-10-28 14:35:13', '2026-10-28 14:34:25'),
(34, 7, 4, 'Efetuar pagamento do pedido da OP-2026-0007', 'concluido', 24, NULL, '2026-10-28 14:35:31', '2026-10-28 14:35:13'),
(35, 7, 5, 'Acompanhar rota de entrega da OP-2026-0007', 'a_fazer', 72, NULL, NULL, '2026-10-28 14:35:31');

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes`
--

CREATE TABLE `notificacoes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `setor_id` int(11) DEFAULT NULL,
  `titulo` varchar(100) NOT NULL,
  `mensagem` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `lida` tinyint(1) DEFAULT 0,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `notificacoes`
--

INSERT INTO `notificacoes` (`id`, `usuario_id`, `setor_id`, `titulo`, `mensagem`, `link`, `lida`, `criado_em`) VALUES
(2, NULL, 2, 'Nova Ordem Recebida', 'A ordem OP-2026-0004 foi enviada pela Produção para análise do BOM.', '/setores/planejamento/ordem_detalhe.php?id=4', 1, '2026-10-27 15:35:33'),
(3, NULL, 2, 'Nova Ordem Recebida', 'A ordem OP-2026-0005 foi enviada pela Produção para análise do BOM.', '/setores/planejamento/ordem_detalhe.php?id=5', 1, '2026-10-27 15:43:05'),
(4, NULL, 2, 'Nova Ordem Recebida', 'A ordem OP-2026-0006 foi enviada pela Produção para análise do BOM.', '/setores/planejamento/ordem_detalhe.php?id=6', 1, '2026-10-27 15:54:06'),
(5, NULL, 3, 'BOM Aprovado', 'A ordem OP-2026-0006 foi aprovada e precisa de cotação.', '/setores/compras/pedido_form.php?id=6', 1, '2026-10-27 16:01:07'),
(6, NULL, 6, 'Material Chegou', 'A taxa da ordem OP-2026-0003 foi paga. Receba o material.', '/setores/estoque/receber.php?id=3', 1, '2026-10-28 14:10:51'),
(7, NULL, 1, 'Material no Estoque', 'O material da ordem OP-2026-0003 chegou ao estoque. Inicie a remontagem.', '/setores/producao/ordem_detalhe.php?id=3', 1, '2026-10-28 14:10:58'),
(8, NULL, 2, 'Nova Ordem Recebida', 'A ordem OP-2026-0007 foi enviada pela Produção para análise do BOM.', '/setores/planejamento/ordem_detalhe.php?id=7', 0, '2026-10-28 14:33:22'),
(9, NULL, 3, 'BOM Aprovado', 'A ordem OP-2026-0007 foi aprovada e precisa de cotação.', '/setores/compras/pedido_form.php?id=7', 0, '2026-10-28 14:34:25'),
(10, NULL, 4, 'Pedido para Pagamento', 'O pedido da ordem OP-2026-0007 foi gerado e precisa ser pago.', '/setores/financeiro/pagar.php?id=7', 0, '2026-10-28 14:35:13'),
(11, NULL, 5, 'Pedido Pago', 'O pedido da ordem OP-2026-0007 foi pago. Acompanhe a entrega.', '/setores/logistica/rota_form.php?id=7', 0, '2026-10-28 14:35:31');

-- --------------------------------------------------------

--
-- Estrutura para tabela `ordens`
--

CREATE TABLE `ordens` (
  `id` int(11) NOT NULL,
  `codigo` varchar(30) NOT NULL,
  `descricao` varchar(200) DEFAULT NULL,
  `status` enum('aberta','desmontagem','planejamento','compras','financeiro','logistica','estoque','remontagem','concluida','cancelada') DEFAULT 'aberta',
  `criado_por` int(11) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `ordens`
--

INSERT INTO `ordens` (`id`, `codigo`, `descricao`, `status`, `criado_por`, `criado_em`) VALUES
(1, 'OP-2026-0001', 'trem de pouso 9000', 'logistica', 3, '2026-10-23 15:09:49'),
(2, 'OP-2026-0002', '011', 'financeiro', 1, '2026-10-26 14:10:29'),
(3, 'OP-2026-0003', 'trem de pouso roxo', 'concluida', 1, '2026-10-27 14:49:07'),
(4, 'OP-2026-0004', 'trem de pouso azul', 'concluida', 1, '2026-10-27 15:34:59'),
(5, 'OP-2026-0005', '011', 'compras', 1, '2026-10-27 15:42:52'),
(6, 'OP-2026-0006', 'trem de pouso azul', 'compras', 1, '2026-10-27 15:53:42'),
(7, 'OP-2026-0007', 'Matheus fazendo teste', 'logistica', 1, '2026-10-28 14:32:21');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pagamentos`
--

CREATE TABLE `pagamentos` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `tipo` enum('pedido','taxa_chegada') DEFAULT 'pedido',
  `valor` decimal(12,2) DEFAULT NULL,
  `nota_fiscal` varchar(50) DEFAULT NULL,
  `pago_em` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `pagamentos`
--

INSERT INTO `pagamentos` (`id`, `pedido_id`, `tipo`, `valor`, `nota_fiscal`, `pago_em`) VALUES
(3, 4, 'pedido', 100.00, '001', '2026-10-27 15:36:17'),
(4, 3, 'pedido', 100.00, '002', '2026-10-27 15:36:22'),
(5, 1, 'pedido', 100.00, '003', '2026-10-27 15:36:28'),
(6, 4, 'taxa_chegada', 200.00, '005', '2026-10-27 15:37:05'),
(7, 3, 'taxa_chegada', 200.00, '005', '2026-10-28 14:10:51'),
(8, 5, 'pedido', 1000.00, '101010101010', '2026-10-28 14:35:31');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedidos_compra`
--

CREATE TABLE `pedidos_compra` (
  `id` int(11) NOT NULL,
  `ordem_id` int(11) NOT NULL,
  `fornecedor_id` int(11) NOT NULL,
  `valor_total` decimal(12,2) DEFAULT NULL,
  `prazo_entrega` date DEFAULT NULL,
  `status` enum('aberto','pago','em_transito','entregue','cancelado') DEFAULT 'aberto',
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `pedidos_compra`
--

INSERT INTO `pedidos_compra` (`id`, `ordem_id`, `fornecedor_id`, `valor_total`, `prazo_entrega`, `status`, `criado_em`) VALUES
(1, 1, 1, 100.00, '2026-10-24', 'pago', '2026-10-23 15:44:52'),
(3, 3, 1, 100.00, '2026-10-24', 'pago', '2026-10-27 15:36:04'),
(4, 4, 1, 100.00, '2026-10-24', 'pago', '2026-10-27 15:36:10'),
(5, 7, 1, 1000.00, '2026-11-03', 'pago', '2026-10-28 14:35:13');

-- --------------------------------------------------------

--
-- Estrutura para tabela `rotas`
--

CREATE TABLE `rotas` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `origem` varchar(200) DEFAULT NULL,
  `destino` varchar(200) DEFAULT NULL,
  `status` enum('aguardando','em_transito','chegou') DEFAULT 'aguardando',
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `rotas`
--

INSERT INTO `rotas` (`id`, `pedido_id`, `origem`, `destino`, `status`, `atualizado_em`) VALUES
(2, 4, '-', 'Fábrica Matriz', 'chegou', '2026-10-27 15:36:51'),
(3, 5, '-', 'Fábrica Matriz', 'chegou', '2026-10-28 14:36:14');

-- --------------------------------------------------------

--
-- Estrutura para tabela `setores`
--

CREATE TABLE `setores` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `descricao` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `setores`
--

INSERT INTO `setores` (`id`, `nome`, `descricao`) VALUES
(1, 'producao', 'Desmontagem, BOM e remontagem do trem de pouso'),
(2, 'planejamento', 'Organização da lista de materiais'),
(3, 'compras', 'Fornecedores, pedidos e conferência de pagamento'),
(4, 'financeiro', 'Pagamentos, notas fiscais e taxas'),
(5, 'logistica', 'Acompanhamento de rotas de entrega'),
(6, 'estoque', 'Recebimento e baixa de materiais');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `setor_id` int(11) DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `deve_trocar_senha` tinyint(1) DEFAULT 0,
  `senha_temporaria_expira` datetime DEFAULT NULL,
  `tentativas_login` int(11) DEFAULT 0,
  `bloqueado_ate` datetime DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha_hash`, `setor_id`, `is_admin`, `ativo`, `foto_perfil`, `deve_trocar_senha`, `senha_temporaria_expira`, `tentativas_login`, `bloqueado_ate`, `criado_em`) VALUES
(1, 'Administrador', 'admin@supply.local', '$2y$10$Hwwk7FHSzwwZzwwdXCJJzuTO5DeezTWIHLKU8fk.WSwh3MhUTOUBS', NULL, 1, 1, 'avatars/3e2bdb3b124638cd6cdc5422e43fa627.webp', 0, NULL, 0, NULL, '2026-10-23 14:22:17'),
(3, 'Maria Teste', 'maria@supply.local', '$2y$10$arwUwY9XpOS4C4zhSVwi5eB5BC1E1FpgUxN1jWQdfJ.qoAdfqHt3u', 1, 0, 1, NULL, 0, '2026-10-24 20:00:56', 0, NULL, '2026-10-23 15:00:56'),
(4, 'Teste Produção', 'prod@teste.com', '$2y$10$EV24ZwubSM0vDtmR1mHt7.Bfnuy.Y5W1ko6bC/zCw3fWr/aFMlEEy', 1, 0, 1, NULL, 0, '2026-10-29 18:12:31', 0, NULL, '2026-10-28 14:12:31'),
(5, 'Teste Planejamento', 'plan@teste.com', '$2y$10$BxO0Q5xBC7xDCsWt11fmMOOol6kfN.WfUPcjX4U1xKCFt6h3JKtMi', 2, 0, 1, NULL, 1, '2026-10-29 18:13:25', 0, NULL, '2026-10-28 14:13:25'),
(6, 'Teste Compras', 'comp@teste.com', '$2y$10$VsG3bunPifHGRPYwIoVBaOs4aRCJgCeqPKwGd8ZDiBOQQjPkx3sdW', 3, 0, 1, NULL, 1, '2026-10-29 18:14:04', 0, NULL, '2026-10-28 14:14:04'),
(7, 'teste financeiro', 'fin@teste.com', '$2y$10$XJDr.B5gwfRKFjvnjn/YzOj4eKqb5UbZxQ2KBd5EVeIsriRTC9BxK', 4, 0, 1, NULL, 1, '2026-10-29 18:15:11', 0, NULL, '2026-10-28 14:15:11'),
(8, 'Teste Logística', 'log@teste.com', '$2y$10$ZHFckTaigEXvytT1qZjtLu5LT.FKXnzAbXMWngIGTa/nhGyKor7IK', 5, 0, 1, NULL, 1, '2026-10-29 18:15:54', 0, NULL, '2026-10-28 14:15:54'),
(9, 'Teste Estoque', 'est@teste.com', '$2y$10$8u74ri8otttJxOx1amx.pePjsPmy5tfks0fryj3pJn9jcsp2u91DS', 6, 0, 1, NULL, 1, '2026-10-29 18:19:14', 0, NULL, '2026-10-28 14:19:14');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `anexos`
--
ALTER TABLE `anexos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ordem_id` (`ordem_id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `pagamento_id` (`pagamento_id`),
  ADD KEY `enviado_por` (`enviado_por`),
  ADD KEY `setor_id` (`setor_id`);

--
-- Índices de tabela `bom_itens`
--
ALTER TABLE `bom_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ordem_id` (`ordem_id`);

--
-- Índices de tabela `estoque_movimentacoes`
--
ALTER TABLE `estoque_movimentacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ordem_id` (`ordem_id`),
  ADD KEY `registrado_por` (`registrado_por`);

--
-- Índices de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `historico_ordens`
--
ALTER TABLE `historico_ordens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ordem_id` (`ordem_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `kanban_tarefas`
--
ALTER TABLE `kanban_tarefas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ordem_id` (`ordem_id`),
  ADD KEY `setor_id` (`setor_id`);

--
-- Índices de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `setor_id` (`setor_id`);

--
-- Índices de tabela `ordens`
--
ALTER TABLE `ordens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `criado_por` (`criado_por`);

--
-- Índices de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`);

--
-- Índices de tabela `pedidos_compra`
--
ALTER TABLE `pedidos_compra`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ordem_id` (`ordem_id`),
  ADD KEY `fornecedor_id` (`fornecedor_id`);

--
-- Índices de tabela `rotas`
--
ALTER TABLE `rotas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`);

--
-- Índices de tabela `setores`
--
ALTER TABLE `setores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `setor_id` (`setor_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `anexos`
--
ALTER TABLE `anexos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `bom_itens`
--
ALTER TABLE `bom_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `estoque_movimentacoes`
--
ALTER TABLE `estoque_movimentacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `historico_ordens`
--
ALTER TABLE `historico_ordens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `kanban_tarefas`
--
ALTER TABLE `kanban_tarefas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `ordens`
--
ALTER TABLE `ordens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `pedidos_compra`
--
ALTER TABLE `pedidos_compra`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `rotas`
--
ALTER TABLE `rotas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `setores`
--
ALTER TABLE `setores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `anexos`
--
ALTER TABLE `anexos`
  ADD CONSTRAINT `anexos_ibfk_1` FOREIGN KEY (`ordem_id`) REFERENCES `ordens` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `anexos_ibfk_2` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos_compra` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `anexos_ibfk_3` FOREIGN KEY (`pagamento_id`) REFERENCES `pagamentos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `anexos_ibfk_4` FOREIGN KEY (`enviado_por`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `anexos_ibfk_5` FOREIGN KEY (`setor_id`) REFERENCES `setores` (`id`);

--
-- Restrições para tabelas `bom_itens`
--
ALTER TABLE `bom_itens`
  ADD CONSTRAINT `bom_itens_ibfk_1` FOREIGN KEY (`ordem_id`) REFERENCES `ordens` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `estoque_movimentacoes`
--
ALTER TABLE `estoque_movimentacoes`
  ADD CONSTRAINT `estoque_movimentacoes_ibfk_1` FOREIGN KEY (`ordem_id`) REFERENCES `ordens` (`id`),
  ADD CONSTRAINT `estoque_movimentacoes_ibfk_2` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `historico_ordens`
--
ALTER TABLE `historico_ordens`
  ADD CONSTRAINT `historico_ordens_ibfk_1` FOREIGN KEY (`ordem_id`) REFERENCES `ordens` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `historico_ordens_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `kanban_tarefas`
--
ALTER TABLE `kanban_tarefas`
  ADD CONSTRAINT `kanban_tarefas_ibfk_1` FOREIGN KEY (`ordem_id`) REFERENCES `ordens` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kanban_tarefas_ibfk_2` FOREIGN KEY (`setor_id`) REFERENCES `setores` (`id`);

--
-- Restrições para tabelas `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD CONSTRAINT `notificacoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notificacoes_ibfk_2` FOREIGN KEY (`setor_id`) REFERENCES `setores` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `ordens`
--
ALTER TABLE `ordens`
  ADD CONSTRAINT `ordens_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD CONSTRAINT `pagamentos_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos_compra` (`id`);

--
-- Restrições para tabelas `pedidos_compra`
--
ALTER TABLE `pedidos_compra`
  ADD CONSTRAINT `pedidos_compra_ibfk_1` FOREIGN KEY (`ordem_id`) REFERENCES `ordens` (`id`),
  ADD CONSTRAINT `pedidos_compra_ibfk_2` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores` (`id`);

--
-- Restrições para tabelas `rotas`
--
ALTER TABLE `rotas`
  ADD CONSTRAINT `rotas_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos_compra` (`id`);

--
-- Restrições para tabelas `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`setor_id`) REFERENCES `setores` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
