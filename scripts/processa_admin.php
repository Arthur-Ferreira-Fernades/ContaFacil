<?php
session_start();
require_once __DIR__ . '/autenticacao.php';
require_once __DIR__ . '/conectaBanco.php';

if (!estaLogado()) {
    header("Location: ../login.php");
    exit;
}

$acao = $_GET['acao'] ?? null;
$usuario_id = $_GET['usuario_id'] ?? null;
$grupo_id = $_GET['grupo_id'] ?? null;

try {
    $conexao->begin_transaction();

    // Verificar permissões
    $stmt = $conexao->prepare("SELECT responsavel_id FROM grupos WHERE id = ?");
    $stmt->bind_param("i", $grupo_id);
    $stmt->execute();
    $grupo = $stmt->get_result()->fetch_assoc();

    if (!$grupo || $grupo['responsavel_id'] != $_SESSION['usuario_id']) {
        throw new Exception("Acesso não autorizado!");
    }

    switch ($acao) {
        case 'promover':
            $stmt = $conexao->prepare("UPDATE grupo_membros 
                                      SET is_admin = TRUE 
                                      WHERE grupo_id = ? AND usuario_id = ?");
            break;
            
        case 'remover':
            $stmt = $conexao->prepare("DELETE FROM grupo_membros 
                                      WHERE grupo_id = ? AND usuario_id = ?");
            break;
            
        default:
            throw new Exception("Ação inválida!");
    }

    $stmt->bind_param("ii", $grupo_id, $usuario_id);
    $stmt->execute();

    $conexao->commit();
    header("Location: ../admin_grupo.php?id=$grupo_id&sucesso=Ação+realizada+com+sucesso!");

} catch (Exception $e) {
    $conexao->rollback();
    header("Location: ../admin_grupo.php?id=$grupo_id&erro=" . urlencode($e->getMessage()));
} finally {
    $conexao->close();
}
?>