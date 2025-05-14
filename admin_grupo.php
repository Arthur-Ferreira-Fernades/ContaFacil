<?php
require_once 'scripts/autenticacao.php';
require_once 'scripts/conectaBanco.php';

if (!estaLogado()) {
    header("Location: login.php");
    exit;
}

$sucesso = $_GET['sucesso'] ?? null;
$erro = $_GET['erro'] ?? null;

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

    // Buscar todos os grupos do usuário
    $sql_grupos = "SELECT g.id, g.nome_grupo, g.responsavel_id 
                  FROM grupos g
                  JOIN grupo_membros gm ON g.id = gm.grupo_id
                  WHERE gm.usuario_id = ?";
    $stmt_grupos = executarQuery($conexao, $sql_grupos, 'i', [$usuario_id]);
    $grupos = $stmt_grupos->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($grupos)) {
        header("Location: novo_grupo.php?erro=Você+ainda+não+participa+de+nenhum+grupo");
        exit;
    }

    // Processar cada grupo
    $grupos_completos = [];
    foreach ($grupos as $grupo) {
        $grupo_id = $grupo['id'];

        // Buscar membros do grupo
        $membros = $conexao->query("
            SELECT u.id, u.nome_completo, gm.is_admin 
            FROM grupo_membros gm
            JOIN usuarios u ON gm.usuario_id = u.id
            WHERE gm.grupo_id = $grupo_id
        ")->fetch_all(MYSQLI_ASSOC);

        // Buscar solicitações pendentes
        $solicitacoes = $conexao->query("
            SELECT s.id, u.nome_completo, s.data_solicitacao 
            FROM solicitacoes_grupo s
            JOIN usuarios u ON s.usuario_id = u.id
            WHERE s.grupo_id = $grupo_id AND s.status = 'pendente'
        ")->fetch_all(MYSQLI_ASSOC);

        $grupos_completos[] = [
            'info' => $grupo,
            'membros' => $membros,
            'solicitacoes' => $solicitacoes
        ];
    }
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
    <title>Administrar Grupos - ContaFácil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/admin_grupo.css">
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

    <div class="container mt-5">
        <?php if ($erro): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($erro) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($sucesso) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <h2 class="mb-4"><i class="fas fa-users me-2"></i>Meus Grupos</h2>

        <?php foreach ($grupos_completos as $grupo): ?>
            <div class="card grupo-card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-users-cog me-2"></i>
                            <?= htmlspecialchars($grupo['info']['nome_grupo']) ?>
                            <?php if ($grupo['info']['responsavel_id'] == $usuario_id): ?>
                                <span class="responsavel-badge badge bg-warning">Responsável</span>
                            <?php endif; ?>
                        </h4>

                        <?php if ($grupo['info']['responsavel_id'] == $usuario_id): ?>
                            <a href="scripts/excluir_grupo.php?grupo_id=<?= $grupo['info']['id'] ?>"
                                class="btn btn-sm btn-danger"
                                onclick="return confirm('Tem certeza que deseja excluir este grupo? Essa ação não poderá ser desfeita.')">
                                <i class="fas fa-trash-alt me-1"></i>Excluir Grupo
                            </a>
                            <a href="editar_grupo.php?grupo_id=<?= $grupo['info']['id'] ?>"
                                class="btn btn-sm btn-warning">
                                <i class="fas fa-edit me-1"></i>Editar Grupo
                            </a>

                        <?php endif; ?>
                    </div>
                </div>

                <!-- Seção de Solicitações Pendentes -->
                <div class="card-body">
                    <h5 class="mb-4"><i class="fas fa-user-clock me-2"></i>Solicitações Pendentes</h5>

                    <?php if (count($grupo['solicitacoes']) > 0): ?>
                        <div class="list-group">
                            <?php foreach ($grupo['solicitacoes'] as $s): ?>
                                <div class="list-group-item solicitacao-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?= htmlspecialchars($s['nome_completo']) ?></strong><br>
                                            <small class="text-muted">Solicitado em: <?= date('d/m/Y H:i', strtotime($s['data_solicitacao'])) ?></small>
                                        </div>
                                        <div class="admin-actions">
                                            <a href="scripts/processa_solicitacao.php?acao=aceitar&id=<?= $s['id'] ?>&grupo_id=<?= $grupo['info']['id'] ?>"
                                                class="btn btn-sm btn-success me-2">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="scripts/processa_solicitacao.php?acao=recusar&id=<?= $s['id'] ?>&grupo_id=<?= $grupo['info']['id'] ?>"
                                                class="btn btn-sm btn-danger">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">Nenhuma solicitação pendente</div>
                    <?php endif; ?>
                </div>

                <!-- Seção de Membros -->
                <div class="card-footer">
                    <h5 class="mb-3"><i class="fas fa-users me-2"></i>Membros</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Tipo</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grupo['membros'] as $m): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($m['nome_completo']) ?></td>
                                        <td>
                                            <?php if ($m['is_admin']): ?>
                                                <span class="badge bg-primary">Administrador</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Membro</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="admin-actions">
                                            <?php if ($m['id'] != $grupo['info']['responsavel_id']): ?>
                                                <?php if (!$m['is_admin']): ?>
                                                    <a href="scripts/processa_admin.php?acao=promover&usuario_id=<?= $m['id'] ?>&grupo_id=<?= $grupo['info']['id'] ?>"
                                                        class="btn btn-sm btn-warning me-2"
                                                        title="Tornar Administrador">
                                                        <i class="fas fa-user-shield"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="scripts/processa_admin.php?acao=remover&usuario_id=<?= $m['id'] ?>&grupo_id=<?= $grupo['info']['id'] ?>"
                                                    class="btn btn-sm btn-danger"
                                                    title="Remover do Grupo"
                                                    onclick="return confirm('Tem certeza que deseja remover este membro?')">
                                                    <i class="fas fa-user-minus"></i>
                                                </a>
                                            <?php endif; ?>
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
    <script>
        // Adiciona efeito hover nas solicitações
        document.querySelectorAll('.solicitacao-item').forEach(item => {
            item.addEventListener('mouseover', () => {
                item.style.backgroundColor = '#f8f9fa';
            });
            item.addEventListener('mouseout', () => {
                item.style.backgroundColor = '';
            });
        });
    </script>
</body>

</html>