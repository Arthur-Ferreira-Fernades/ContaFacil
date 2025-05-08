<?php
require_once 'scripts/autenticacao.php';
require_once 'scripts/conectaBanco.php';

if (!estaLogado()) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || !isset($_GET['grupo_id'])) {
    header("Location: index.php?erro=Requisição+inválida");
    exit;
}

$despesa_id = $_GET['id'];
$grupo_id = $_GET['grupo_id'];
$usuario_id = $_SESSION['usuario_id'];

// Verificar se o usuário pertence ao grupo
$conexao = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$sql = "SELECT 1 FROM grupo_membros WHERE grupo_id = ? AND usuario_id = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param('ii', $grupo_id, $usuario_id);
$stmt->execute();

if (!$stmt->get_result()->num_rows) {
    header("Location: index.php?erro=Acesso+negado");
    exit;
}

// Buscar dados da despesa (para qualquer tipo)
$sql = "SELECT id, titulo, valor_total, tipo 
        FROM despesas 
        WHERE id = ? AND grupo_id = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param('ii', $despesa_id, $grupo_id);
$stmt->execute();
$despesa = $stmt->get_result()->fetch_assoc();

if (!$despesa) {
    header("Location: index.php?erro=Despesa+não+encontrada");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Registrar Pagamento - ContaFácil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .card-pagamento {
            max-width: 500px;
            margin: 2rem auto;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">ContaFácil</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="card card-pagamento">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-money-bill-wave me-2"></i>
                    Registrar Pagamento
                </h4>
            </div>
            <div class="card-body">
                <form action="scripts/processa_pagamento.php" method="POST">
                    <input type="hidden" name="despesa_id" value="<?= $despesa_id ?>">
                    <input type="hidden" name="grupo_id" value="<?= $grupo_id ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Descrição:</label>
                        <input type="text" class="form-control" 
                               value="<?= htmlspecialchars($despesa['titulo']) ?>" 
                               readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Valor Total:</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" class="form-control" 
                                   value="<?= number_format($despesa['valor_total'], 2) ?>" 
                                   readonly>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tipo de Despesa:</label>
                        <input type="text" class="form-control text-capitalize" 
                               value="<?= $despesa['tipo'] ?>" 
                               readonly>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Data do Pagamento:</label>
                        <input type="date" name="data_pagamento" 
                               class="form-control" 
                               value="<?= date('Y-m-d') ?>" 
                               required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-check-circle me-2"></i>
                        Confirmar Pagamento
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>