<?php

$host = getenv('mysql.railway.internal');
$porta = getenv('3306') ?: '3306';
$banco = getenv('railway');
$usuario = getenv('root');
$senha = getenv('DwLacVNWyorzHJVGwxaniDOZGOcbSiiI');

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$porta;dbname=$banco;charset=utf8mb4",
        $usuario,
        $senha,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("Erro de conexão com banco: " . $e->getMessage());
    http_response_code(500);
    exit("Erro interno do servidor.");
}