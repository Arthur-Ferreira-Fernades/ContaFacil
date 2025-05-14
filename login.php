<?php
require __DIR__ . '/scripts/autenticacao.php';
if (estaLogado()) {
    header("Location: index.php");
    exit;
}

$erro = $_GET['erro'] ?? null;
$sucesso = $_GET['sucesso'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="img/contafacilLogo.jpeg">
    <title>ContaFácil - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/login.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">ContaFácil</a>
            <div class="d-flex">
                <a href="registra.php" class="btn btn-outline-light">Criar Conta</a>
            </div>
        </div>
    </nav>
    <div class="container">
        <div class="login-card card">
            <div class="card-body">
                <h4 class="card-title mb-4">Acesse sua conta</h4>

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

                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label class="form-label">E-mail</label>
                        <input type="email" name="email" class="form-control" 
                               placeholder="seu@email.com" required>
                    </div>

                    <div class="mb-3 position-relative">
                        <label class="form-label">Senha</label>
                        <input type="password" name="senha" id="senha" 
                               class="form-control" placeholder="••••••••" 
                               required minlength="6">
                        <i class="password-toggle fas fa-eye-slash" 
                           onclick="togglePassword('senha')"></i>
                    </div>

                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Entrar
                        </button>
                    </div>

                    <div class="text-center">
                        <a href="recuperar-senha.php" class="text-decoration-none">
                            Esqueceu a senha?
                        </a>
                    </div>
                </form>
            </div>

            <div class="card-footer text-center">
                Não tem conta? <a href="registra.php" class="text-decoration-none">Cadastre-se</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar/esconder senha
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.querySelector(`[onclick="togglePassword('${fieldId}')"]`);
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            } else {
                field.type = 'password';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            }
        }
    </script>
</body>
</html>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/scripts/conectaBanco.php';
    if (login($conexao, $_POST['email'], $_POST['senha'])) {
        header("Location: index.php");
        exit;
    } else {
        header("Location: login.php?erro=Credenciais inválidas!");
        exit;
    }
}
?>