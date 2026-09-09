<?php
declare(strict_types=1);
require_once __DIR__.'/core.php';
ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set(env('APP_TIMEZONE', 'America/Sao_Paulo'));
set_exception_handler(function(Throwable $e): void {
    $id = bin2hex(random_bytes(5));
    error_log('Vitalize '.$id.' '.get_class($e));
    if (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/api/')) json_response(['erro'=>'Não foi possível concluir. Tente novamente.', 'referencia'=>$id], 503);
    http_response_code(503); echo 'Serviço temporariamente indisponível. Referência: '.e($id);
});
if (production() && (strlen(env('APP_KEY')) < 32 || !str_starts_with(env('APP_URL'), 'https://'))) throw new RuntimeException('Configure APP_KEY e APP_URL.');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: DENY');
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' https://res.cloudinary.com blob:; connect-src 'self'; form-action 'self'; base-uri 'self'; frame-ancestors 'none'");
header('Cache-Control: no-store');
if (production()) header('Strict-Transport-Security: max-age=31536000');
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 3*1024*1024) { http_response_code(413); exit('Arquivo ou formulário muito grande.'); }

final class DatabaseSession implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface {
    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }
    public function read(string $id): string { $r = query('SELECT data FROM app_sessions WHERE id=? AND expires_at>?', [hash('sha256',$id), time()])->fetchColumn(); return $r === false ? '' : (string)$r; }
    public function write(string $id, string $data): bool { query('INSERT INTO app_sessions (id,data,expires_at) VALUES (?,?,?) ON DUPLICATE KEY UPDATE data=VALUES(data),expires_at=VALUES(expires_at)', [hash('sha256',$id),$data,time()+7200]); return true; }
    public function destroy(string $id): bool { query('DELETE FROM app_sessions WHERE id=?',[hash('sha256',$id)]); return true; }
    public function gc(int $max_lifetime): int|false { return query('DELETE FROM app_sessions WHERE expires_at<?',[time()])->rowCount(); }
    public function validateId(string $id): bool { return (bool)query('SELECT 1 FROM app_sessions WHERE id=? AND expires_at>?',[hash('sha256',$id),time()])->fetchColumn(); }
    public function updateTimestamp(string $id,string $data): bool { return $this->write($id,$data); }
}
session_name('vitalize_session');
ini_set('session.use_strict_mode','1');
ini_set('session.use_only_cookies','1');
session_set_cookie_params(['lifetime'=>0,'path'=>url(),'secure'=>production() || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),'httponly'=>true,'samesite'=>'Lax']);
session_set_save_handler(new DatabaseSession(), true);
session_start();
