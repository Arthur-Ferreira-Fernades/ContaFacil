<?php
require_once __DIR__ . '/autenticacao.php';
require_once __DIR__ . '/conectaBanco.php';

if (!estaLogado()) {
    header("Location: ../login.php");
    exit;
}

try {
    $conexao->begin_transaction();

    // Validar dados
    if (!isset($_POST['nome_grupo']) || empty($_POST['nome_grupo'])) {
        throw new Exception("Nome do grupo é obrigatório!");
    }

    // Criar grupo
    $stmt = $conexao->prepare("INSERT INTO grupos 
        (nome_grupo, descricao, responsavel_id) 
        VALUES (?, ?, ?)");
    
    $stmt->bind_param("ssi",
        $_POST['nome_grupo'],
        $_POST['descricao'],
        $_SESSION['usuario_id']
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Erro ao criar grupo: " . $stmt->error);
    }
    $grupo_id = $conexao->insert_id;

    // Adicionar criador como membro
    $stmt = $conexao->prepare("INSERT INTO grupo_membros 
        (grupo_id, usuario_id) 
        VALUES (?, ?)");
    
    $stmt->bind_param("ii", $grupo_id, $_SESSION['usuario_id']);
    if (!$stmt->execute()) {
        throw new Exception("Erro ao adicionar membro: " . $stmt->error);
    }

    $conexao->commit();
    header("Location: ../index.php?sucesso_grupo=Grupo+criado+com+sucesso!");

} catch (mysqli_sql_exception $e) {
    $conexao->rollback();
    $erro = (strpos($e->getMessage(), 'Duplicate entry') !== false)
            ? "Já existe um grupo com este nome!"
            : "Erro no banco: " . $e->getMessage();
    header("Location: ../novo_grupo.php?erro=" . urlencode($erro));

} catch (Exception $e) {
    $conexao->rollback();
    header("Location: ../novo_grupo.php?erro=" . urlencode($e->getMessage()));
} finally {
    if (isset($stmt)) $stmt->close();
    $conexao->close();
}