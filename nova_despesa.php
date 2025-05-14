<?php
require_once 'scripts/autenticacao.php';
require_once 'scripts/conectaBanco.php';

if (!estaLogado()) {
    header("Location: login.php");
    exit;
}

$conexao = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conexao->connect_error) die("Erro de conexão: " . $conexao->connect_error);

try {
    // Obter grupo do usuário
    $usuario_id = $_SESSION['usuario_id'];
    $stmt_grupo = $conexao->prepare("SELECT grupo_id FROM grupo_membros WHERE usuario_id = ? LIMIT 1");
    $stmt_grupo->bind_param("i", $usuario_id);
    $stmt_grupo->execute();
    $grupo = $stmt_grupo->get_result()->fetch_assoc();

    if (!$grupo) die("Você não está em nenhum grupo!");
    $grupo_id = $grupo['grupo_id'];

    // Buscar membros
    $stmt = $conexao->prepare("SELECT u.id, u.nome_completo 
                              FROM grupo_membros gm
                              JOIN usuarios u ON gm.usuario_id = u.id
                              WHERE gm.grupo_id = ?");
    $stmt->bind_param("i", $grupo_id);
    $stmt->execute();
    $membros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    die("Erro: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <!-- Mesmo cabeçalho do index.php -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="img/contafacilLogo.jpeg">
    <title>Nova Despesa - ContaFácil</title>
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
        <h1>Nova Despesa</h1><br>
        <div class="card-body">
            <form method="POST" action="scripts/processa_despesa.php">
                <div class="mb-3">
                    <label class="form-label">Título</label>
                    <input type="text" name="titulo" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Valor Total</label>
                    <input type="number" step="0.01" name="valor_total" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tipo de Despesa</label>
                    <select class="form-select" name="tipo" id="tipoDespesa" required>
                        <option value="fixa">Fixa</option>
                        <option value="variavel">Variável</option>
                    </select>
                </div>

                <div class="mb-3" id="campoDia">
                    <label class="form-label">Dia do Vencimento (1-31)</label>
                    <input type="number" name="dia_vencimento"
                        class="form-control" min="1" max="31" required>
                </div>

                <div class="mb-3" id="campoData" style="display: none;">
                    <label class="form-label">Data de Vencimento</label>
                    <input type="date" name="data_vencimento"
                        class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Pagador</label>
                    <select name="pagador_id" class="form-select" required>
                        <?php foreach ($membros as $membro): ?>
                            <option value="<?= $membro['id'] ?>">
                                <?= htmlspecialchars($membro['nome_completo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <script>
                    document.getElementById('tipoDespesa').addEventListener('change', function() {
                        const tipo = this.value;
                        document.getElementById('campoDia').style.display =
                            tipo === 'fixa' ? 'block' : 'none';
                        document.getElementById('campoData').style.display =
                            tipo === 'variavel' ? 'block' : 'none';
                        document.querySelector('[name="dia_vencimento"]').required = (tipo === 'fixa');
                        document.querySelector('[name="data_vencimento"]').required = (tipo === 'variavel');
                    });
                </script>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Salvar
                </button>
            </form>
        </div>
    </div>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script do datepicker (copiado do index original)
        document.addEventListener('DOMContentLoaded', function() {
            const tipoSelect = document.getElementById('tipoDespesa');
            const campoDia = document.getElementById('campoDia');
            const campoData = document.getElementById('campoData');

            function atualizarCampos() {
                if (tipoSelect.value === 'fixa') {
                    campoDia.style.display = 'block';
                    campoData.style.display = 'none';
                    campoData.required = false;
                } else {
                    campoDia.style.display = 'none';
                    campoData.style.display = 'block';
                    campoData.min = new Date().toISOString().split('T')[0];
                    campoData.required = true;
                }
            }

            tipoSelect.addEventListener('change', atualizarCampos);
            atualizarCampos(); // Inicializar
        });
    </script>
</body>

</html>