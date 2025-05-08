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
    
    $sql = "SELECT p.*, d.titulo, g.nome_grupo 
           FROM pagamentos p
           JOIN despesas d ON p.despesa_id = d.id
           JOIN grupos g ON d.grupo_id = g.id
           WHERE p.usuario_id = ?
           ORDER BY p.mes_referencia DESC";
           
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $pagamentos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    die("Erro: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagamentos as $p): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($p['data_pagamento'])) ?></td>
                                <td><?= date('m/Y', strtotime($p['mes_referencia'])) ?></td>
                                <td class="fw-bold">R$ <?= number_format($p['valor_pago'], 2) ?></td>
                                <td><?= htmlspecialchars($p['titulo']) ?></td>
                                <td>
                                    <span class="badge bg-primary badge-status">
                                        <?= htmlspecialchars($p['nome_grupo']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>