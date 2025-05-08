<?php
require 'autenticacao.php';
logout();
header("Location:../login.php");
exit;
?>