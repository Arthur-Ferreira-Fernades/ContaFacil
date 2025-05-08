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

// Verificar permissão
$conexao = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$sql = "SELECT d.* FROM despesas d
        JOIN grupo_membros gm ON d.grupo_id = gm.grupo_id
        WHERE d.id = ? AND d.grupo_id = ? AND gm.usuario_id = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param('iii', $despesa_id, $grupo_id, $usuario_id);
$stmt->execute();
$despesa = $stmt->get_result()->fetch_assoc();

if (!$despesa) {
    header("Location: index.php?erro=Despesa+não+encontrada+ou+acesso+negado");
    exit;
}

// Processar formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titulo = $_POST['titulo'];
    $valor_total = (float) str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_total']);
    $data_vencimento = $_POST['data_vencimento'];
    $pagador_id = $_POST['pagador_id'];

// Garantir que a data é válida
if (!strtotime($data_vencimento)) {
    $erro = "Data de vencimento inválida!";
} else {
    $dia_vencimento = (int) date('d', strtotime($data_vencimento));
    
    $sql_update = "UPDATE despesas SET 
                  titulo = ?,
                  valor_total = ?,
                  data_vencimento = ?,
                  dia_vencimento = ?,
                  pagador_id = ?
                  WHERE id = ? AND grupo_id = ?";
    
    $stmt = $conexao->prepare($sql_update);
    $stmt->bind_param('sdsiiii', $titulo, $valor_total, $data_vencimento, $dia_vencimento, $pagador_id, $despesa_id, $grupo_id);

    if ($stmt->execute()) {
        header("Location: index.php?sucesso=Despesa+atualizada+com+sucesso");
        exit;
    } else {
        $erro = "Erro ao atualizar despesa: " . $conexao->error;
    }
}

    
    if ($stmt->execute()) {
        header("Location: index.php?sucesso=Despesa+atualizada+com+sucesso");
        exit;
    } else {
        $erro = "Erro ao atualizar despesa: " . $conexao->error;
    }
}

// Buscar membros do grupo para o select de pagador
$sql_membros = "SELECT u.id, u.nome_completo 
FROM grupo_membros gm
JOIN usuarios u ON gm.usuario_id = u.id
WHERE gm.grupo_id = ?";

$stmt = $conexao->prepare($sql_membros);
$stmt->bind_param('i', $grupo_id);
$stmt->execute();
$membros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Despesa - ContaFácil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/editar_despesa.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">ContaFácil</a>
        </div>
    </nav>

    <div class="container">
        <div class="card card-edicao">
            <div class="card-header bg-primary text-white">
                <h4><i class="fas fa-edit me-2"></i>Editar Despesa</h4>
            </div>
            <div class="card-body">
                <?php if (isset($erro)): ?>
                <div class="alert alert-danger"><?= $erro ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <input type="text" name="titulo" class="form-control" 
                               value="<?= htmlspecialchars($despesa['titulo']) ?>" required>
                    </div>

                    <div class="mb-3 money-input">
                        <label class="form-label">Valor</label>
                        <span>R$</span>
                        <input type="text" name="valor_total" class="form-control" 
                               value="<?= number_format($despesa['valor_total'], 2, ',', '.') ?>" 
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Data de Vencimento</label>
                        <input type="date" name="data_vencimento" class="form-control" 
                               value="<?= date('Y-m-d', strtotime($despesa['data_vencimento'])) ?>" 
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Responsável pelo Pagamento</label>
                        <select name="pagador_id" class="form-select" required>
                            <?php foreach ($membros as $membro): ?>
                            <option value="<?= $membro['id'] ?>" 
                                <?= $membro['id'] == $despesa['pagador_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($membro['nome_completo']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Salvar Alterações
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Máscara para o campo de valor
        document.querySelector('input[name="valor_total"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = (value/100).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            e.target.value = value;
        });
    </script>
</body>
</html>