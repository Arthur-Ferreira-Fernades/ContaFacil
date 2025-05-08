<?php
$conexao = new mysqli('localhost', 'root', '', 'ContaFacil');
if ($conexao->connect_error) {
    die("Erro de conexão: " . $conexao->connect_error);
}
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ContaFacil');
?>