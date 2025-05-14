<?php
require_once 'scripts/autenticacao.php';
require_once 'scripts/conectaBanco.php';

if (!estaLogado()) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

try {
    $conexao = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conexao->connect_error) {
        throw new Exception("Conexão falhou: " . $conexao->connect_error);
    }

    // 1. Obtém os grupos aos quais o usuário pertence
    $sql_grupos = "SELECT grupo_id FROM grupo_membros WHERE usuario_id = ?";
    $stmt_grupos = $conexao->prepare($sql_grupos);
    $stmt_grupos->bind_param('i', $usuario_id);
    $stmt_grupos->execute();
    $result_grupos = $stmt_grupos->get_result();
    $grupo_ids = $result_grupos->fetch_all(MYSQLI_ASSOC);

    if (empty($grupo_ids)) {
        throw new Exception("Usuário não pertence a nenhum grupo.");
    }

    // Transforma o array em uma lista de IDs para usar em IN (...)
    $grupo_ids_str = implode(',', array_map(fn($g) => $g['grupo_id'], $grupo_ids));

    // 2. Consulta para pagamentos (despesas fixas) do grupo

    $sql_pagamentos = "
    SELECT 
        p.data_pagamento AS data,
        p.mes_referencia,
        p.valor_pago AS valor_total,
        d.titulo,
        g.nome_grupo,
        u.nome_completo AS pagador_nome
    FROM pagamentos p
    JOIN despesas d ON p.despesa_id = d.id
    JOIN grupos g ON d.grupo_id = g.id
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE d.grupo_id IN ($grupo_ids_str)
";
    $result_pagamentos = $conexao->query($sql_pagamentos);
    $pagamentos = $result_pagamentos->fetch_all(MYSQLI_ASSOC);

    // Ordena por data decrescente
    usort($pagamentos, fn($a, $b) => strtotime($b['data']) - strtotime($a['data']));
} catch (Exception $e) {
    die("Erro: " . $e->getMessage());
}



?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="img/contafacilLogo.jpeg">
    <title>Histórico de Pagamentos - ContaFácil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/historico.css">
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">ContaFácil</a>
            <div class="d-flex">
                <?php if (estaLogado()): ?>
                    <a href="index.php" class="btn btn-outline-light me-2">
                        <i class="fas fa-home"></i>
                    </a>
                    <a href="scripts/logout.php" class="btn btn-outline-light">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <?php if ($_GET['sucesso'] ?? null): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($_GET['sucesso']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($_GET['erro'] ?? null): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($_GET['erro']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card card-historico">
            <div class="card-header bg-primary text-white">
                <h4><i class="fas fa-history me-2"></i>Histórico de Pagamentos</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-light">
                            <tr>
                                <th><i class="fas fa-calendar-day me-2"></i>Data</th>
                                <th><i class="fas fa-calendar-alt me-2"></i>Mês Referência</th>
                                <th><i class="fas fa-coins me-2"></i>Valor</th>
                                <th><i class="fas fa-file-invoice me-2"></i>Descrição</th>
                                <th><i class="fas fa-users me-2"></i>Grupo</th>
                                <th><i class="fas fa-user me-2"></i>Registrador</th> <!-- Nova coluna -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagamentos as $registro): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($registro['data'])) ?></td>
                                    <td><?= date('m/Y', strtotime($registro['mes_referencia'])) ?></td>
                                    <td class="fw-bold">R$ <?= number_format($registro['valor_total'], 2, ',', '.') ?></td>
                                    <td><?= htmlspecialchars($registro['titulo']) ?></td>
                                    <td>
                                        <span class="badge bg-primary badge-status">
                                            <?= htmlspecialchars($registro['nome_grupo']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($registro['pagador_nome']) ?></td> <!-- Exibe o nome do registrador -->
                                </tr>
                            <?php endforeach; ?>


                            <?php if (empty($pagamentos)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">Nenhum registro encontrado.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>