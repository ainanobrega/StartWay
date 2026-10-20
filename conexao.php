<?php
mysqli_report(MYSQLI_REPORT_OFF);
$servidor = getenv('STARTWAY_DB_HOST') ?: 'localhost';
$usuario = getenv('STARTWAY_DB_USER') ?: 'root';
$senha = getenv('STARTWAY_DB_PASSWORD') ?: '';
$banco = getenv('STARTWAY_DB_NAME') ?: 'startway';
$porta = (int) (getenv('STARTWAY_DB_PORT') ?: 3306);
$conexao = @new mysqli($servidor, $usuario, $senha, $banco, $porta);
if ($conexao->connect_errno) {
    error_log('StartWay: falha na conexão com banco (' . $conexao->connect_errno . '): ' . $conexao->connect_error);
    http_response_code(500);
    exit('Não foi possível conectar ao sistema no momento. Tente novamente mais tarde.');
}
$conexao->set_charset('utf8mb4');
