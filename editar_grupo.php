<?php
require_once 'scripts/autenticacao.php';
require_once 'scripts/conectaBanco.php';

if (!estaLogado()) {
    header("Location: login.php");
    exit;
}

$grupo_id = $_GET['grupo_id'] ?? null;
$usuario_id = $_SESSION['usuario_id'];

if (!$grupo_id) {
    header("Location: admin_grupo.php?erro=Grupo+não+especificado");
    exit;
}

$conexao = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$stmt = $conexao->prepare("SELECT id, nome_grupo, responsavel_id FROM grupos WHERE id = ?");
$stmt->bind_param("i", $grupo_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: admin_grupo.php?erro=Grupo+não+encontrado");
    exit;
}

$grupo = $result->fetch_assoc();

if ($grupo['responsavel_id'] != $usuario_id) {
    header("Location: admin_grupo.php?erro=Você+não+tem+permissão+para+editar+este+grupo");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Grupo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
    <h2 class="mb-4">Editar Grupo: <?= htmlspecialchars($grupo['nome_grupo']) ?></h2>
    <form action="scripts/atualizar_grupo.php" method="POST">
        <input type="hidden" name="grupo_id" value="<?= $grupo['id'] ?>">
        <div class="mb-3">
            <label for="nome_grupo" class="form-label">Novo Nome do Grupo</label>
            <input type="text" class="form-control" id="nome_grupo" name="nome_grupo" required value="<?= htmlspecialchars($grupo['nome_grupo']) ?>">
        </div>
        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        <a href="admin_grupo.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
</body>
</html>
