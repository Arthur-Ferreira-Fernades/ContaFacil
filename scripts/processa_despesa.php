<?php
require_once __DIR__ . '/autenticacao.php';
require_once __DIR__ . '/conectaBanco.php';

if (!estaLogado()) {
    header("Location: login.php");
    exit;
}

try {
    $conexao->begin_transaction();
    $camposObrigatorios = ['titulo', 'valor_total', 'tipo', 'pagador_id'];
    foreach ($camposObrigatorios as $campo) {
        if (!isset($_POST[$campo])) {
            throw new Exception("Campo '$campo' não recebido!");
        }
    }

    // Coletar dados básicos
    $tipo = $_POST['tipo'];
    $pagador_id = (int)$_POST['pagador_id'];
    $valor_total = (float)$_POST['valor_total'];
    require __DIR__ . '/autenticacao.php';
    $usuario_id = $_SESSION['usuario_id'];

    $stmt_grupo = $conexao->prepare("SELECT grupo_id FROM grupo_membros WHERE usuario_id = ? LIMIT 1");
    $stmt_grupo->bind_param("i", $usuario_id);
    $stmt_grupo->execute();
    $resultado = $stmt_grupo->get_result();

    if ($resultado->num_rows === 0) {
        throw new Exception("Você não está em nenhum grupo!");
    }

    $grupo = $resultado->fetch_assoc();
    $grupo_id = $grupo['grupo_id'];

    // Processar datas
    $dia_vencimento = null;
    $data_vencimento = null;

    if ($tipo === 'fixa') {
        if (!isset($_POST['dia_vencimento']) || $_POST['dia_vencimento'] < 1 || $_POST['dia_vencimento'] > 31) {
            throw new Exception("Dia de vencimento inválido!");
        }
        $dia_vencimento = (int)$_POST['dia_vencimento'];
        $data_vencimento = date('Y-m') . '-' . str_pad($dia_vencimento, 2, '0', STR_PAD_LEFT);
    } else {
        if (!isset($_POST['data_vencimento']) || !strtotime($_POST['data_vencimento'])) {
            throw new Exception("Data de vencimento inválida!");
        }
        $data_vencimento = $_POST['data_vencimento'];
    }

    if ($tipo === 'variavel') {
        $data_vencimento = $_POST['data_vencimento'];
    }

    // Inserir despesa principal
    $stmt = $conexao->prepare("INSERT INTO despesas 
        (grupo_id, tipo, titulo, valor_total, dia_vencimento, data_vencimento, pagador_id)
        VALUES (?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("issddsi",
        $grupo_id,
        $tipo,
        $_POST['titulo'],
        $valor_total,
        $dia_vencimento,
        $data_vencimento,
        $pagador_id
    );

    if (!$stmt->execute()) {
        throw new Exception("Erro ao salvar despesa: " . $stmt->error);
    }

    $conexao->commit();
    header("Location: ../index.php?sucesso=Despesa+registrada+com+sucesso!");

} catch (mysqli_sql_exception $e) {
    $conexao->rollback();
    $erro = "Erro no banco: " . $e->getMessage();
    header("Location: ../nova_despesa.php?erro=" . urlencode($erro));

} catch (Exception $e) {
    $conexao->rollback();
    header("Location: ../index.php?erro=" . urlencode($e->getMessage()));
} finally {
    if (isset($stmt)) $stmt->close();
    $conexao->close();
}
?>
