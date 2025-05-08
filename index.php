<?php
require_once 'scripts/autenticacao.php';
if (!estaLogado()) {
    header("Location: login.php");
    exit;
}
require_once 'scripts/conectaBanco.php';

$meses = [
    'January' => 'Janeiro',
    'February' => 'Fevereiro',
    'March' => 'Março',
    'April' => 'Abril',
    'May' => 'Maio',
    'June' => 'Junho',
    'July' => 'Julho',
    'August' => 'Agosto',
    'September' => 'Setembro',
    'October' => 'Outubro',
    'November' => 'Novembro',
    'December' => 'Dezembro'
];

$mes_en = (new DateTime())->format('F');
$ano = (new DateTime())->format('Y');



// Verifica se há mensagens de sucesso/erro
$sucesso = $_GET['sucesso'] ?? null;
$erro = $_GET['erro'] ?? null;

// Conexão com tratamento de erro
$conexao = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conexao->connect_error) {
    die("Erro de conexão: " . $conexao->connect_error);
}

function executarQuery($conexao, $sql, $tipos = null, $params = null)
{
    $stmt = $conexao->prepare($sql);
    if (!$stmt) {
        die("Erro ao preparar query: " . $conexao->error);
    }

    if ($tipos && $params) {
        $stmt->bind_param($tipos, ...$params);
    }

    if (!$stmt->execute()) {
        die("Erro ao executar query: " . $stmt->error);
    }

    return $stmt;
}

try {
    $usuario_id = $_SESSION['usuario_id'];
    $total_gastos = 0;
    $todos_grupos = [];

    // Buscar todos os grupos do usuário
    $sql_grupos = "SELECT g.id AS grupo_id, g.nome_grupo, g.responsavel_id 
                  FROM grupos g
                  JOIN grupo_membros gm ON g.id = gm.grupo_id
                  WHERE gm.usuario_id = ?";
    $stmt_grupos = executarQuery($conexao, $sql_grupos, 'i', [$usuario_id]);
    $grupos = $stmt_grupos->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($grupos)) {
        header("Location: novo_grupo.php?erro=Você+ainda+não+participa+de+nenhum+grupo");
        exit;
    }

    foreach ($grupos as $grupo) {
        $grupo_id = $grupo['grupo_id'];

        // Membros do grupo
        $sql_membros = "SELECT u.id, u.nome_completo 
                        FROM grupo_membros gm
                        JOIN usuarios u ON gm.usuario_id = u.id
                        WHERE gm.grupo_id = ?";
        $stmt_membros = executarQuery($conexao, $sql_membros, 'i', [$grupo_id]);
        $membros = $stmt_membros->get_result()->fetch_all(MYSQLI_ASSOC);

        // Despesas fixas
        $sql_fixas = "SELECT d.id, d.titulo, d.valor_total, d.dia_vencimento,
              p.id AS pagamento_id,
              EXISTS(
                  SELECT 1 FROM pagamentos p
                  WHERE p.despesa_id = d.id
                  AND p.mes_referencia = DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')
              ) AS status_pago
              FROM despesas d
              LEFT JOIN pagamentos p ON p.despesa_id = d.id
                  AND p.mes_referencia = DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')
              WHERE d.grupo_id = ? AND d.tipo = 'fixa'
              ORDER BY d.dia_vencimento";
        $stmt_fixas = executarQuery($conexao, $sql_fixas, 'i', [$grupo_id]);
        $despesas_fixas = $stmt_fixas->get_result()->fetch_all(MYSQLI_ASSOC);

        // Despesas variáveis
        $sql_variaveis = "SELECT d.id, d.titulo, d.valor_total, d.data_vencimento, 
                    u.nome_completo AS pagador, p.id AS pagamento_id,
                    (p.id IS NOT NULL) AS status_pago
                  FROM despesas d
                  JOIN usuarios u ON d.pagador_id = u.id
                  LEFT JOIN pagamentos p ON p.despesa_id = d.id
                  WHERE d.grupo_id = ? AND d.tipo = 'variavel'
                  AND MONTH(d.data_vencimento) = MONTH(CURRENT_DATE())
                  ORDER BY d.data_vencimento DESC";
        $stmt_variaveis = executarQuery($conexao, $sql_variaveis, 'i', [$grupo_id]);
        $despesas_variaveis = $stmt_variaveis->get_result()->fetch_all(MYSQLI_ASSOC);

        // Calcular total por grupo
        $total_grupo = array_sum(array_column($despesas_fixas, 'valor_total'))
            + array_sum(array_column($despesas_variaveis, 'valor_total'));
        $total_gastos += $total_grupo;
        $total_variaveis = array_sum(array_column($despesas_variaveis, 'valor_total'));


        // Armazenar dados do grupo
        $todos_grupos[] = [
            'info' => $grupo,
            'membros' => $membros,
            'fixas' => $despesas_fixas,
            'variaveis' => $despesas_variaveis,
            'total_grupo' => $total_grupo,
            'total_variaveis' => $total_variaveis
        ];
    }
} catch (Exception $e) {
    die("Erro fatal: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ContaFácil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/index.css">
</head>

<body class="bg-light">
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">ContaFácil</a>
                <div class="collapse navbar-collapse">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="nova_despesa.php">Nova Despesa</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="novo_grupo.php">Criar Grupo</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_grupo.php">Meus Grupos</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="historico_pagamentos.php">Histórico</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="edita_perfil.php">Editar Perfil</a>
                        </li>
                    </ul>
                    <div class="d-flex">
                        <?php if (estaLogado()): ?>
                            <span class="navbar-text me-3">
                                Olá, <?= htmlspecialchars($_SESSION['usuario_nome'] ?? 'Usuário') ?>
                            </span>
                            <a href="scripts/logout.php" class="btn btn-outline-light">Sair</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <div class="container mt-4">
        <?php if ($_GET['sucesso_grupo'] ?? null): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($_GET['sucesso_grupo']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($_GET['sucesso'] ?? null): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($_GET['sucesso']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-body text-center">
                <h4 class="card-title">Total Gastos em Todos os Grupos</h4>
                <h2 class="text-primary">R$ <?= number_format($total_gastos, 2) ?></h2>
            </div>
        </div>

        <?php foreach ($todos_grupos as $grupo): ?>
            <div class="card grupo-card">
                <div class="card-header grupo-header">
                    <h4>
                        <?= htmlspecialchars($grupo['info']['nome_grupo']) ?>
                        <?php if ($grupo['info']['responsavel_id'] == $usuario_id): ?>
                            <span class="badge bg-warning badge-responsavel">Responsável</span>
                        <?php endif; ?>
                        <span class="float-end">Total do Grupo: R$ <?= number_format($grupo['total_grupo'], 2) ?></span>
                    </h4>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5><i class="fas fa-users me-2"></i>Membros e Gastos</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Membro</th>
                                                <th>Fixos (R$)</th>
                                                <th>Variados (R$)</th>
                                                <th>Falta Pagar (R$)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Calcular gastos por membro
                                            $gastos_membros = [];
                                            $mes_atual = date('Y-m-01');

                                            foreach ($membros as $membro) {
                                                // Gastos variados
                                                $sql_variaveis = "SELECT COALESCE(SUM(valor_total), 0) as total
                                         FROM despesas 
                                         WHERE grupo_id = ? 
                                         AND pagador_id = ?
                                         AND tipo = 'variavel'
                                         AND MONTH(data_vencimento) = MONTH(CURRENT_DATE())";
                                                $stmt = $conexao->prepare($sql_variaveis);
                                                $stmt->bind_param('ii', $grupo_id, $membro['id']);
                                                $stmt->execute();
                                                $total_variaveis = $stmt->get_result()->fetch_assoc()['total'];

                                                // Gastos fixos
                                                $sql_fixas = "SELECT COALESCE(SUM(valor_total), 0) as total
                                     FROM despesas 
                                     WHERE grupo_id = ? 
                                     AND pagador_id = ?
                                     AND tipo = 'fixa'";
                                                $stmt = $conexao->prepare($sql_fixas);
                                                $stmt->bind_param('ii', $grupo_id, $membro['id']);
                                                $stmt->execute();
                                                $total_fixas = $stmt->get_result()->fetch_assoc()['total'];

                                                // Falta pagar (fixas não pagas deste mês)
                                                $sql_pendente = "SELECT COALESCE(SUM(d.valor_total), 0) as total
                                        FROM despesas d
                                        LEFT JOIN pagamentos p ON p.despesa_id = d.id
                                            AND p.mes_referencia = ?
                                        WHERE d.grupo_id = ?
                                        AND d.pagador_id = ?
                                        AND d.tipo = 'fixa'
                                        AND p.id IS NULL";
                                                $stmt = $conexao->prepare($sql_pendente);
                                                $stmt->bind_param('sii', $mes_atual, $grupo_id, $membro['id']);
                                                $stmt->execute();
                                                $falta_pagar = $stmt->get_result()->fetch_assoc()['total'];

                                                $gastos_membros[] = [
                                                    'nome' => $membro['nome_completo'],
                                                    'fixos' => $total_fixas,
                                                    'variados' => $total_variaveis,
                                                    'pendente' => $falta_pagar
                                                ];
                                            }

                                            // Exibir na tabela
                                            foreach ($gastos_membros as $membro): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($membro['nome']) ?></td>
                                                    <td class="<?= $membro['fixos'] > 0 ? 'text-danger fw-bold' : '' ?>">
                                                        <?= number_format($membro['fixos'], 2) ?>
                                                    </td>
                                                    <td class="<?= $membro['variados'] > 0 ? 'text-warning fw-bold' : '' ?>">
                                                        <?= number_format($membro['variados'], 2) ?>
                                                    </td>
                                                    <td class="<?= $membro['pendente'] > 0 ? 'text-danger fw-bold' : '' ?>">
                                                        <?= number_format($membro['pendente'], 2) ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Despesas Fixas - <?php echo "{$meses[$mes_en]} {$ano}"; ?></h5>
                                    <div class="d-flex justify-content-between small mt-2">
                                        <?php
                                        $total_fixas = array_sum(array_column($grupo['fixas'], 'valor_total'));
                                        $pago_fixas = array_sum(array_column(
                                            array_filter($grupo['fixas'], fn($f) => $f['status_pago']),
                                            'valor_total'
                                        ));
                                        ?>
                                        <span>Total: R$ <?= number_format($total_fixas, 2) ?></span>
                                        <span class="text-success">Pago: R$ <?= number_format($pago_fixas, 2) ?></span>
                                        <span class="text-danger">Pendente: R$ <?= number_format($total_fixas - $pago_fixas, 2) ?></span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Descrição</th>
                                                <th>Valor</th>
                                                <th>Vencimento</th>
                                                <th>Status</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($grupo['fixas'] as $despesa): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($despesa['titulo']) ?></td>
                                                    <td>R$ <?= number_format($despesa['valor_total'], 2) ?></td>
                                                    <td>Dia <?= $despesa['dia_vencimento'] ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= $despesa['status_pago'] ? 'success' : 'danger' ?>">
                                                            <?= $despesa['status_pago'] ? 'Pago' : 'Pendente' ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($despesa['status_pago']): ?>
                                                            <a href="scripts/desfazer_pagamento.php?pagamento_id=<?= $despesa['pagamento_id'] ?>&grupo_id=<?= $grupo['info']['grupo_id'] ?>"
                                                                class="btn btn-sm btn-danger"
                                                                title="Desfazer Pagamento"
                                                                onclick="return confirm('Tem certeza que deseja desfazer este pagamento?')">
                                                                <i class="fas fa-undo"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="registra_pagamento.php?id=<?= $despesa['id'] ?>&grupo_id=<?= $grupo['info']['grupo_id'] ?>"
                                                                class="btn btn-sm btn-warning"
                                                                title="Registrar Pagamento">
                                                                <i class="fas fa-money-bill-wave"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <a href="editar_despesa.php?id=<?= $despesa['id'] ?>&grupo_id=<?= $grupo['info']['grupo_id'] ?>"
                                                            class="btn btn-sm btn-primary me-2"
                                                            title="Editar Despesa">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="scripts/excluir_despesa.php?id=<?= $despesa['id'] ?>&grupo_id=<?= $grupo['info']['grupo_id'] ?>"
                                                            class="btn btn-sm btn-danger"
                                                            title="Excluir Despesa"
                                                            onclick="return confirm('Tem certeza que deseja excluir esta despesa?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5><i class="fas fa-receipt me-2"></i>Despesas Variáveis deste Mês</h5>
                            <div class="total-box bg-light p-2 rounded">
                                <strong>Total: R$ <?= number_format($grupo['total_variaveis'], 2) ?></strong>
                            </div>
                        </div>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Descrição</th>
                                    <th>Valor</th>
                                    <th>Pagador</th>
                                    <th>Vencimento</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grupo['variaveis'] as $variavel): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($variavel['titulo']) ?></td>
                                        <td>R$ <?= number_format($variavel['valor_total'], 2) ?></td>
                                        <td><?= htmlspecialchars($variavel['pagador']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($variavel['data_vencimento'])) ?></td>
                                        <td>
                                            <a href="editar_despesa.php?id=<?= $variavel['id'] ?>&grupo_id=<?= $grupo['info']['grupo_id'] ?>"
                                                class="btn btn-sm btn-primary me-2"
                                                title="Editar Despesa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="scripts/excluir_despesa.php?id=<?= $variavel['id'] ?>&grupo_id=<?= $grupo['info']['grupo_id'] ?>"
                                                class="btn btn-sm btn-danger"
                                                title="Excluir Despesa"
                                                onclick="return confirm('Tem certeza que deseja excluir esta despesa?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>