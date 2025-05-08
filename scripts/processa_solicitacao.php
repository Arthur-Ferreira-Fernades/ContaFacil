<?php
session_start();
require_once __DIR__ . '/autenticacao.php';
require_once __DIR__ . '/conectaBanco.php';

if (!estaLogado()) {
    header("Location: ../login.php");
    exit;
}

$acao = $_GET['acao'] ?? null;
$solicitacao_id = $_GET['id'] ?? null;

try {
    $conexao->begin_transaction();

    // Validar ação
    if (!in_array($acao, ['aceitar', 'recusar']) || !$solicitacao_id) {
        throw new Exception("Ação inválida!");
    }

    // Buscar solicitação
    $stmt = $conexao->prepare("SELECT s.*, g.responsavel_id 
                              FROM solicitacoes_grupo s
                              JOIN grupos g ON s.grupo_id = g.id
                              WHERE s.id = ? AND g.responsavel_id = ?");
    $stmt->bind_param("ii", $solicitacao_id, $_SESSION['usuario_id']);
    $stmt->execute();
    $solicitacao = $stmt->get_result()->fetch_assoc();

    if (!$solicitacao) {
        throw new Exception("Solicitação não encontrada ou permissão negada!");
    }

    if ($acao === 'aceitar') {
        // Adicionar como membro
        $stmt = $conexao->prepare("INSERT INTO grupo_membros 
            (grupo_id, usuario_id) 
            VALUES (?, ?)");
        $stmt->bind_param("ii", $solicitacao['grupo_id'], $solicitacao['usuario_id']);
        $stmt->execute();
    }

    // Atualizar status
    $status = $acao === 'aceitar' ? 'aceito' : 'recusado';
    $stmt = $conexao->prepare("UPDATE solicitacoes_grupo 
                              SET status = ? 
                              WHERE id = ?");
    $stmt->bind_param("si", $status, $solicitacao_id);
    $stmt->execute();

    $conexao->commit();
    header("Location: ../admin_grupo.php?id={$solicitacao['grupo_id']}&sucesso=Ação+realizada+com+sucesso!");

} catch (Exception $e) {
    $conexao->rollback();
    header("Location: ../admin_grupo.php?id={$solicitacao['grupo_id']}&erro=" . urlencode($e->getMessage()));
} finally {
    $conexao->close();
}
?>