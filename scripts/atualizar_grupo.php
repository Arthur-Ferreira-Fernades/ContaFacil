<?php
require_once 'autenticacao.php';
require_once 'conectaBanco.php';

if (!estaLogado()) {
    header("Location: ../login.php");
    exit;
}

$grupo_id = $_POST['grupo_id'] ?? null;
$novo_nome = trim($_POST['nome_grupo'] ?? '');
$usuario_id = $_SESSION['usuario_id'];

if (!$grupo_id || !$novo_nome) {
    header("Location: ../admin_grupo.php?erro=Dados+inválidos");
    exit;
}

$conexao = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Verifica se o usuário é responsável pelo grupo
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
    header("Location: ../admin_grupo.php?erro=Você+não+pode+editar+este+grupo");
    exit;
}

// Atualiza o nome do grupo
$stmt = $conexao->prepare("UPDATE grupos SET nome_grupo = ? WHERE id = ?");
$stmt->bind_param("si", $novo_nome, $grupo_id);
if ($stmt->execute()) {
    header("Location: ../admin_grupo.php?sucesso=Grupo+atualizado+com+sucesso");
} else {
    header("Location: ../admin_grupo.php?erro=Erro+ao+atualizar+grupo");
}
exit;
