<?php
require_once 'autenticacao.php';
require_once 'conectaBanco.php';

if (!estaLogado()) {
    header("Location: ../login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$grupo_id = $_GET['grupo_id'] ?? null;

if (!$grupo_id) {
    header("Location: ../admin_grupo.php?erro=ID+do+grupo+inválido");
    exit;
}

$conexao = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conexao->connect_error) {
    die("Erro de conexão: " . $conexao->connect_error);
}

// Verifica se o usuário é o responsável pelo grupo
$stmt = $conexao->prepare("SELECT responsavel_id FROM grupos WHERE id = ?");
$stmt->bind_param("i", $grupo_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../admin_grupo.php?erro=Grupo+não+encontrado");
    exit;
}

$grupo = $result->fetch_assoc();
if ($grupo['responsavel_id'] != $usuario_id) {
    header("Location: ../admin_grupo.php?erro=Você+não+tem+permissão+para+excluir+este+grupo");
    exit;
}

// Começa a exclusão em ordem segura (dependências primeiro)
try {
    // Desativa o autocommit para garantir transação
    $conexao->begin_transaction();

    // Remove membros
    $stmt = $conexao->prepare("DELETE FROM grupo_membros WHERE grupo_id = ?");
    $stmt->bind_param("i", $grupo_id);
    $stmt->execute();

    // Remove solicitações
    $stmt = $conexao->prepare("DELETE FROM solicitacoes_grupo WHERE grupo_id = ?");
    $stmt->bind_param("i", $grupo_id);
    $stmt->execute();

    // Por fim, remove o grupo
    $stmt = $conexao->prepare("DELETE FROM grupos WHERE id = ?");
    $stmt->bind_param("i", $grupo_id);
    $stmt->execute();

    // Confirma as alterações
    $conexao->commit();

    header("Location: ../admin_grupo.php?sucesso=Grupo+excluído+com+sucesso");
    exit;

} catch (Exception $e) {
    $conexao->rollback();
    header("Location: ../admin_grupo.php?erro=Erro+ao+excluir+grupo:+".$e->getMessage());
    exit;
}
