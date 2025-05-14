<?php
require __DIR__ . '/scripts/autenticacao.php';

if (estaLogado()) {
    header("Location: index.php");
    exit;
}

$erro = $_GET['erro'] ?? null;
$sucesso = $_GET['sucesso'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/scripts/conectaBanco.php';
    
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    try {
        if ($senha !== $confirmar_senha) {
            throw new Exception("As senhas não coincidem!");
        }
    
        // Query corrigida com o nome da coluna certo
        $stmt = $conexao->prepare("INSERT INTO usuarios (nome_completo, email, senha_hash) VALUES (?, ?, ?)");
        
        if (!$stmt) {
            throw new Exception("Erro na query: " . $conexao->error);
        }
    
        $stmt->bind_param("sss", $nome, $email, password_hash($senha, PASSWORD_DEFAULT));
        
        if ($stmt->execute()) {
            header("Location: login.php?sucesso=Registro realizado com sucesso!");
            exit;
        } else {
            throw new Exception("Erro ao executar a query: " . $stmt->error);
        }
        
    } catch (mysqli_sql_exception $e) {
        $erro = (strpos($e->getMessage(), 'Duplicate entry') !== false) 
                ? "E-mail já cadastrado!" 
                : "Erro no cadastro: " . $e->getMessage();
    } catch (Exception $e) {
        $erro = $e->getMessage();
    } finally {
        if (isset($stmt)) $stmt->close();
        $conexao->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="img/contafacilLogo.jpeg">
    <title>ContaFácil - Registrar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/registra.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">ContaFácil</a>
            <div class="d-flex">
                <a href="login.php" class="btn btn-outline-light">Entrar</a>
            </div>
        </div>
    </nav>

    <!-- Conteúdo Principal -->
    <div class="container">
        <div class="register-card card">
            <div class="card-body">
                <h2 class="card-title mb-4 text-center">Criar Nova Conta</h2>

                <?php if ($erro): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($erro) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Nome Completo</label>
                        <input type="text" name="nome" class="form-control" 
                               placeholder="Ex: João da Silva" required
                               minlength="3" maxlength="100">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">E-mail</label>
                        <input type="email" name="email" class="form-control"
                               placeholder="seu@email.com" required
                               pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$">
                    </div>

                    <div class="mb-3 position-relative">
                        <label class="form-label">Senha</label>
                        <input type="password" name="senha" id="senha" 
                               class="form-control" placeholder="••••••••" 
                               required minlength="6">
                        <i class="password-toggle fas fa-eye-slash" 
                           onclick="togglePassword('senha')"></i>
                    </div>

                    <div class="mb-4 position-relative">
                        <label class="form-label">Confirmar Senha</label>
                        <input type="password" name="confirmar_senha" id="confirmar_senha" 
                               class="form-control" placeholder="••••••••" 
                               required minlength="6">
                        <i class="password-toggle fas fa-eye-slash" 
                           onclick="togglePassword('confirmar_senha')"></i>
                    </div>

                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Criar Conta
                        </button>
                    </div>

                    <div class="text-center">
                        <a href="login.php" class="text-decoration-none">
                            Já tem conta? Faça login
                        </a>
                    </div>
                </form>
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