<?php
session_start();
require_once __DIR__ . '/autenticacao.php';
require_once __DIR__ . '/conectaBanco.php';

if (!estaLogado()) {
    header("Location: ../login.php");
    exit;
}

try {
    $conexao->begin_transaction();

    if (!isset($_POST['codigo_grupo']) || empty($_POST['codigo_grupo'])) {
        throw new Exception("Código do grupo é obrigatório!");
    }

    // Buscar grupo
    $stmt = $conexao->prepare("SELECT id, responsavel_id FROM grupos WHERE id = ?");
    $stmt->bind_param("i", $_POST['codigo_grupo']);
    $stmt->execute();
    $grupo = $stmt->get_result()->fetch_assoc();

    if (!$grupo) {
        throw new Exception("Grupo não encontrado!");
    }

    // Verificar se já existe solicitação
    $stmt = $conexao->prepare("SELECT id FROM solicitacoes_grupo 
                              WHERE grupo_id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $grupo['id'], $_SESSION['usuario_id']);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception("Você já tem uma solicitação pendente para este grupo!");
    }

    // Criar solicitação
    $stmt = $conexao->prepare("INSERT INTO solicitacoes_grupo 
        (grupo_id, usuario_id) 
        VALUES (?, ?)");
    
    $stmt->bind_param("ii", $grupo['id'], $_SESSION['usuario_id']);
    
    if (!$stmt->execute()) {
        throw new Exception("Erro ao enviar solicitação: " . $stmt->error);
    }

    $conexao->commit();
    header("Location: ../novo_grupo.php?sucesso_entrar=Solicitação+enviada+com+sucesso!");

} catch (Exception $e) {
    $conexao->rollback();
    header("Location: ../novo_grupo.php?erro_entrar=" . urlencode($e->getMessage()));
} finally {
    $conexao->close();
}
?>