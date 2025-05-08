<?php
require_once 'autenticacao.php';
require_once 'conectaBanco.php';

if (!isset($_GET['pagamento_id']) || !isset($_GET['grupo_id'])) {
    header("Location: ../index.php?erro=Requisição+inválida");
    exit;
}

$pagamento_id = $_GET['pagamento_id'];
$grupo_id = $_GET['grupo_id'];
$usuario_id = $_SESSION['usuario_id'];

$conexao = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Verificar se o usuário tem permissão
$sql = "SELECT p.id 
        FROM pagamentos p
        JOIN despesas d ON p.despesa_id = d.id
        JOIN grupo_membros gm ON d.grupo_id = gm.grupo_id
        WHERE p.id = ?
        AND gm.usuario_id = ?
        AND d.grupo_id = ?";

$stmt = $conexao->prepare($sql);
$stmt->bind_param('iii', $pagamento_id, $usuario_id, $grupo_id);
$stmt->execute();

if (!$stmt->get_result()->num_rows) {
    header("Location: ../index.php?erro=Acesso+negado");
    exit;
}

// Excluir o pagamento
$sql_delete = "DELETE FROM pagamentos WHERE id = ?";
$stmt = $conexao->prepare($sql_delete);
$stmt->bind_param('i', $pagamento_id);

if ($stmt->execute()) {
    header("Location: ../index.php?sucesso=Pagamento+desfeito+com+sucesso");
} else {
    header("Location: ../index.php?erro=Erro+ao+desfazer+pagamento");
}
exit;
?>