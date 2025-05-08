<?php
session_start();

if (!function_exists('estaLogado')) {
    function estaLogado() {
        return isset($_SESSION['usuario_id']);
    }
}

if (!function_exists('login')) { 

    function login($conexao, $email, $senha) {
        $stmt = $conexao->prepare("SELECT id, nome_completo, senha_hash FROM usuarios WHERE email = ?");
        if (!$stmt) {
            error_log("Erro SQL: " . $conexao->error);
            return false;
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $usuario = $resultado->fetch_assoc();
            if (password_verify($senha, $usuario['senha_hash'])) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome_completo'];
                return true;
            }
        }
        return false;
    }
}
if (!function_exists('logout')) {
    function logout() {
        session_unset();
        session_destroy();
    }
 }

?>