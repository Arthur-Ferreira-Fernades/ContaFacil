<?php
require_once 'scripts/autenticacao.php';
require_once 'scripts/conectaBanco.php';

if (!estaLogado()) {
    header("Location: login.php");
    exit;
}

$erro_criar = $_GET['erro_criar'] ?? null;
$sucesso_criar = $_GET['sucesso_criar'] ?? null;
$erro_entrar = $_GET['erro_entrar'] ?? null;
$sucesso_entrar = $_GET['sucesso_entrar'] ?? null;

// Verificar grupos existentes
try {
    $conexao = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Verificar se já está em grupos
    $stmt = $conexao->prepare("SELECT g.id, g.nome_grupo 
                              FROM grupo_membros gm
                              JOIN grupos g ON gm.grupo_id = g.id
                              WHERE gm.usuario_id = ?");
    if (!$stmt) {
        die("Erro na query: " . $conexao->error);
    }                          
    $stmt->bind_param("i", $_SESSION['usuario_id']);
    $stmt->execute();
    $grupos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    die("Erro ao verificar grupos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="img/contafacilLogo.jpeg">
    <title>Gerenciar Grupos - ContaFácil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/novo_grupo.css">
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
        <!-- Seção de Grupos Existentes -->
        <?php if(count($grupos) > 0): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-users me-2"></i>Seus Grupos</h5>
            </div>
            <div class="card-body">
                <div class="row row-cols-1 row-cols-md-2 g-4">
                    <?php foreach ($grupos as $grupo): ?>
                    <div class="col">
                        <div class="card grupo-card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($grupo['nome_grupo']) ?></h5>
                                <p class="text-muted small">Código: <?= $grupo['id'] ?></p>
                                <a href="index.php?grupo=<?= $grupo['id'] ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-door-open me-1"></i>Acessar
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Seção de Entrar em Grupo -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-sign-in-alt me-2"></i>Entrar em Grupo Existente</h5>
            </div>
            <div class="card-body">
                <?php if ($erro_entrar): ?>
                <div class="alert alert-danger mb-3">
                    <?= htmlspecialchars($erro_entrar) ?>
                </div>
                <?php endif; ?>

                <?php if ($sucesso_entrar): ?>
                <div class="alert alert-success mb-3">
                    <?= htmlspecialchars($sucesso_entrar) ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="scripts/processa_entrar_grupo.php">
                    <div class="mb-3">
                        <label class="form-label">Código do Grupo</label>
                        <input type="text" name="codigo_grupo" class="form-control" 
                               required pattern="[A-Za-z0-9-]{6,20}"
                               placeholder="Ex: GRUPO-123ABC">
                        <small class="form-text text-muted">Peça o código para o administrador do grupo</small>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check-circle me-2"></i>Entrar no Grupo
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Seção de Criar Novo Grupo -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-plus-circle me-2"></i>Criar Novo Grupo</h5>
            </div>
            <div class="card-body">
                <?php if ($erro_criar): ?>
                <div class="alert alert-danger mb-3">
                    <?= htmlspecialchars($erro_criar) ?>
                </div>
                <?php endif; ?>

                <?php if ($sucesso_criar): ?>
                <div class="alert alert-success mb-3">
                    <?= htmlspecialchars($sucesso_criar) ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="scripts/processa_grupo.php">
                    <div class="mb-3">
                        <label class="form-label">Nome do Grupo</label>
                        <input type="text" name="nome_grupo" class="form-control" 
                               required maxlength="50"
                               placeholder="Ex: Família Silva">
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Descrição</label>
                        <textarea name="descricao" class="form-control" rows="2"
                                  placeholder="Finalidade do grupo (opcional)"></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Criar Grupo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>