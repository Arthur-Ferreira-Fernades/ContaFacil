<?php
require_once 'autenticacao.php';
require_once 'conectaBanco.php';

if (!estaLogado()) {
    header("Location: ../login.php");
    exit;
}

// Verifica se os parâmetros estão corretos
if (!isset($_GET['id']) || !isset($_GET['grupo_id'])) {
    header("Location: ../index.php?erro=Requisição+inválida");
    exit;
}

$despesa_id = intval($_GET['id']);
$grupo_id = intval($_GET['grupo_id']);
$usuario_id = $_SESSION['usuario_id'];

try {
    $conexao = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conexao->connect_error) {
        throw new Exception("Erro de conexão: " . $conexao->connect_error);
    }

    // Verifica se o usuário tem permissão para excluir
    $sql_verifica = "SELECT d.id 
                    FROM despesas d
                    JOIN grupo_membros gm ON d.grupo_id = gm.grupo_id
                    WHERE d.id = ? 
                    AND d.grupo_id = ? 
                    AND gm.usuario_id = ?"; 
    
    $stmt = $conexao->prepare($sql_verifica);
    if (!$stmt) {
        throw new Exception("Erro ao preparar verificação: " . $conexao->error);
    }
    
    $stmt->bind_param('iii', $despesa_id, $grupo_id, $usuario_id);
    $stmt->execute();
    
    if (!$stmt->get_result()->num_rows) {
        header("Location: ../index.php?erro=Acesso+negado+ou+despesa+não+encontrada");
        exit;
    }

    $sql_delete_pagamentos = "DELETE FROM pagamentos WHERE despesa_id = ?";
    $stmt = $conexao->prepare($sql_delete_pagamentos);
    if (!$stmt) {
        throw new Exception("Erro ao preparar exclusão de pagamentos: " . $conexao->error);
    }
    $stmt->bind_param('i', $despesa_id);
    $stmt->execute();

    // Depois remove a despesa
    $sql_delete_despesa = "DELETE FROM despesas WHERE id = ? AND grupo_id = ?";
    $stmt = $conexao->prepare($sql_delete_despesa);
    if (!$stmt) {
        throw new Exception("Erro ao preparar exclusão de despesa: " . $conexao->error);
    }
    $stmt->bind_param('ii', $despesa_id, $grupo_id);
    
    if ($stmt->execute()) {
        if ($conexao->affected_rows > 0) {
            header("Location: ../index.php?sucesso=Despesa+excluída+com+sucesso");
        } else {
            header("Location: ../index.php?erro=Nenhuma+despesa+foi+excluída");
        }
    } else {
        throw new Exception("Erro ao executar exclusão: " . $stmt->error);
    }

} catch (Exception $e) {
    error_log("Erro ao excluir despesa: " . $e->getMessage());
    header("Location: ../index.php?erro=Erro+ao+excluir+despesa");
} finally {
    if (isset($conexao)) {
        $conexao->close();
    }
    exit;
}
?>