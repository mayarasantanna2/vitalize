<?php
declare(strict_types=1);
require_once __DIR__.'/app/core.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    if (production() && (strlen(env('APP_KEY')) < 32 || !str_starts_with(env('APP_URL'), 'https://'))) {
        throw new RuntimeException('Configuração incompleta.');
    }
    // Check the schema used by login and the main pages, without returning user data.
    query('SELECT session_version, foto_public_id FROM usuarios LIMIT 0');
    query('SELECT data, expires_at FROM app_sessions LIMIT 0');
    query('SELECT bucket, hits FROM rate_limits LIMIT 0');
    query('SELECT status, telefone_grupo, imagem_public_id FROM grupos LIMIT 0');
    query('SELECT status, relato, anonimo FROM relatos LIMIT 0');
    query('SELECT id_usuario, tipo, especialidade FROM consultas LIMIT 0');
    foreach (['participantes','tokens_conta','ai_usage','ai_cache','denuncias','media_cleanup'] as $table) query('SELECT 1 FROM '.$table.' LIMIT 0');
    echo '{"status":"ready"}';
} catch (Throwable $e) {
    http_response_code(503);
    echo '{"status":"unavailable"}';
}
