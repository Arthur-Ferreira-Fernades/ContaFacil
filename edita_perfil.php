<?php
require __DIR__ . '/scripts/autenticacao.php';

if (!estaLogado()) {
    header("Location: login.php");
    exit;
}

require __DIR__ . '/scripts/conectaBanco.php';

session_start();

$erro = null;
$sucesso = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    try {
        if (!empty($senha) && $senha !== $confirmar_senha) {
            throw new Exception("As senhas não coincidem!");
        }

        $query = "UPDATE usuarios SET nome_completo = ?, email = ?";
        $params = [$nome, $email];

        if (!empty($senha)) {
            $query .= ", senha_hash = ?";
            $params[] = password_hash($senha, PASSWORD_DEFAULT);
        }

        $query .= " WHERE id = ?";
        $params[] =  $_SESSION['usuario_id'];

        $stmt = $conexao->prepare($query);
        $stmt->bind_param(str_repeat('s', count($params) - 1) . 'i', ...$params);

        if ($stmt->execute()) {
            $sucesso = "Dados atualizados com sucesso!";
        } else {
            throw new Exception("Erro ao atualizar: " . $stmt->error);
        }

    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

// Buscar dados atuais
$stmt = $conexao->prepare("SELECT nome_completo, email FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$nomeAtual = $result['nome_completo'] ?? '';
$emailAtual = $result['email'] ?? '';

$stmt->close();
$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Perfil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">ContaFácil</a>
        <div class="d-flex">
            <a href="index.php" class="btn btn-outline-light me-2"><i class="fas fa-home"></i></a>
            <a href="scripts/logout.php" class="btn btn-outline-light"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <h2 class="mb-4 text-center">Editar Meus Dados</h2>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php elseif ($sucesso): ?>
        <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label for="nome">Nome Completo</label>
            <input type="text" name="nome" id="nome" class="form-control"
                   value="<?= htmlspecialchars($nomeAtual) ?>" required>
        </div>

        <div class="mb-3">
            <label for="email">E-mail</label>
            <input type="email" name="email" id="email" class="form-control"
                   value="<?= htmlspecialchars($emailAtual) ?>" required>
        </div>

        <div class="mb-3">
            <label for="senha">Nova Senha</label>
            <input type="password" name="senha" id="senha" class="form-control" minlength="6"
                   placeholder="Deixe em branco para manter a senha atual">
        </div>

        <div class="mb-4">
            <label for="confirmar_senha">Confirmar Nova Senha</label>
            <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control" minlength="6"
                   placeholder="Confirme a nova senha">
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        </div>
    </form>
</div>
</body>
</html>
