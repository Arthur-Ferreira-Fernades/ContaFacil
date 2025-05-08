<?php
require_once 'autenticacao.php';
require_once 'conectaBanco.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php?erro=Método+inválido");
    exit;
}

// Validação dos dados
$required = ['despesa_id', 'grupo_id', 'data_pagamento'];
foreach ($required as $field) {
    if (!isset($_POST[$field])) { 
        header("Location: ../index.php?erro=Dados+incompletos");
        exit;
    }
}

$despesa_id = $_POST['despesa_id'];
$grupo_id = $_POST['grupo_id'];
$data_pagamento = $_POST['data_pagamento'];
$usuario_id = $_SESSION['usuario_id'];

// Obter primeiro dia do mês atual
$mes_referencia = date('Y-m-01');

// Verificar se já existe pagamento para este mês
$conexao = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$sql_verifica = "SELECT id FROM pagamentos 
                WHERE despesa_id = ? 
                AND mes_referencia = ?";
$stmt = $conexao->prepare($sql_verifica);
$stmt->bind_param('is', $despesa_id, $mes_referencia);
$stmt->execute();

if ($stmt->get_result()->num_rows > 0) {
    header("Location: ../index.php?erro=Pagamento+deste+mês+já+registrado");
    exit;
}

$sql = "SELECT tipo FROM despesas WHERE id = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param('i', $despesa_id);
$stmt->execute();
$tipo_despesa = $stmt->get_result()->fetch_assoc()['tipo'];

if ($tipo_despesa === 'fixa') {
    $mes_referencia = date('Y-m-01');
} else {
    $mes_referencia = date('Y-m-d', strtotime($data_pagamento));
}

// Corrigir a query SQL
$sql_insere = "INSERT INTO pagamentos 
              (despesa_id, usuario_id, data_pagamento, mes_referencia, valor_pago)
              SELECT d.id, ?, ?, ?, d.valor_total
              FROM despesas d
              WHERE d.id = ? AND d.grupo_id = ?";

$stmt = $conexao->prepare($sql_insere);

// Adicionar tratamento de erro para prepare
if (!$stmt) {
    die("Erro ao preparar query: " . $conexao->error . " SQL: " . $sql_insere);
}

// Corrigir a ordem e quantidade de parâmetros
$stmt->bind_param('issii', // Tipos: i (usuario_id), s (data_pagamento), s (mes_referencia), i (despesa_id), i (grupo_id)
    $usuario_id,            
    $data_pagamento,        
    $mes_referencia,        
    $despesa_id,            
    $grupo_id               
);

if ($stmt->execute()) {
    header("Location: ../index.php?sucesso=Pagamento+registrado+com+sucesso");
} else {
    header("Location: ../index.php?erro=Erro+ao+registrar+pagamento");
}
exit;
?>