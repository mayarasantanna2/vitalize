<?php
declare(strict_types=1);

function env(string $key, string $default = ''): string {
    static $local;
    if ($local === null) $local = is_file(__DIR__ . '/../config.local.php') ? require __DIR__ . '/../config.local.php' : [];
    $value = getenv($key);
    return $value !== false ? $value : (string)($local[$key] ?? $default);
}
function production(): bool { return env('APP_ENV', 'local') === 'production'; }
function db(): PDO {
    static $pdo;
    if ($pdo) return $pdo;
    $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false];
    if (env('DB_SSL_CA') !== '') {
        $options[PDO::MYSQL_ATTR_SSL_CA] = env('DB_SSL_CA');
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    } elseif (production()) throw new RuntimeException('Configure DB_SSL_CA.');
    $pdo = new PDO('mysql:host='.env('DB_HOST', '127.0.0.1').';port='.env('DB_PORT', '3306').';dbname='.env('DB_NAME', 'vitalize').';charset=utf8mb4', env('DB_USER', 'root'), env('DB_PASSWORD'), $options);
    $pdo->exec("SET time_zone = '+00:00'");
    return $pdo;
}
function query(string $sql, array $params = []): PDOStatement { $s = db()->prepare($sql); $s->execute($params); return $s; }
function e(mixed $text): string { return htmlspecialchars((string)($text ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path = ''): string { return rtrim(env('APP_BASE_PATH'), '/') . '/' . ltrim($path, '/'); }
function absolute_url(string $path): string { return rtrim(env('APP_URL', 'http://localhost/vitalize'), '/') . '/' . ltrim($path, '/'); }
function redirect(string $path): never { header('Location: '.url($path), true, 303); exit; }
function json_response(array $data, int $status = 200): never { http_response_code($status); header('Content-Type: application/json; charset=utf-8'); echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR); exit; }
function flash(string $message): void { $_SESSION['flash'] = $message; }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="'.e(csrf()).'">'; }
function require_post(bool $json = false): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Allow: POST');
        if ($json) json_response(['erro'=>'Método não permitido.'], 405);
        http_response_code(405); exit('Método não permitido.');
    }
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals(csrf(), $token)) {
        if ($json) json_response(['erro'=>'Sua sessão expirou. Recarregue a página.'], 403);
        http_response_code(403); exit('Sessão inválida. Volte à página e tente novamente.');
    }
}
function current_user(): ?array {
    if (empty($_SESSION['id_usuario'])) return null;
    $u = query('SELECT * FROM usuarios WHERE id_usuario = ?', [$_SESSION['id_usuario']])->fetch();
    if (!$u || (int)($u['session_version'] ?? 0) !== (int)($_SESSION['version'] ?? -1)) { $_SESSION = []; return null; }
    return $u;
}
function require_user(bool $json = false, bool $verified = true): array {
    $u = current_user();
    if (!$u) { if ($json) json_response(['erro'=>'Entre na sua conta.'], 401); redirect('login.php'); }
    if ($verified && env('REQUIRE_EMAIL_VERIFICATION', production() ? '1' : '0') === '1' && !$u['email_verificado']) {
        if ($json) json_response(['erro'=>'Confirme seu e-mail antes de continuar.'], 403);
        flash('Confirme seu e-mail antes de continuar.'); redirect('verificar.php');
    }
    return $u;
}
function require_admin(): array { $u = require_user(); if ($u['tipo'] !== 'admin') { http_response_code(403); exit('Acesso restrito.'); } return $u; }
function sign_in(array $u): void { session_regenerate_id(true); $_SESSION = ['id_usuario'=>(int)$u['id_usuario'], 'version'=>(int)$u['session_version'], 'csrf'=>bin2hex(random_bytes(32))]; }
function text_input(string $key, int $max, bool $required = false): string {
    $v = $_POST[$key] ?? '';
    if (!is_string($v) || !mb_check_encoding($v, 'UTF-8')) throw new DomainException('Campo inválido: '.$key.'.');
    $v = trim($v);
    if (($required && $v === '') || mb_strlen($v) > $max) throw new DomainException('Preencha corretamente o campo '.$key.' (máximo '.$max.' caracteres).');
    return $v;
}
function email_input(): string { $v = mb_strtolower(text_input('email', 150, true)); if (!filter_var($v, FILTER_VALIDATE_EMAIL)) throw new DomainException('Informe um e-mail válido.'); return $v; }
function password_input(string $key = 'senha'): string { $v = $_POST[$key] ?? ''; if (!is_string($v) || strlen($v) < 12 || strlen($v) > 72) throw new DomainException('Use uma senha de 12 a 72 bytes, sem cortar espaços.'); return $v; }
function safe_link(string $v): string {
    if ($v === '') return '';
    if (!filter_var($v, FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($v, PHP_URL_SCHEME)), ['http','https'], true) || parse_url($v, PHP_URL_USER) !== null) throw new DomainException('Use um endereço completo iniciado por https:// ou http://.');
    return $v;
}
function date_value(string $v): ?string {
    if ($v === '') return null;
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $v);
    if (!$d || $d->format('Y-m-d') !== $v) throw new DomainException('Informe uma data válida.');
    return $v;
}
function time_value(string $v): ?string { if ($v === '') return null; if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $v)) throw new DomainException('Informe um horário válido.'); return $v; }
function secret_hash(string $v): string { return hash_hmac('sha256', $v, env('APP_KEY', 'local-development-only')); }
function rate_limit(string $scope, string $identity, int $max, int $seconds): bool {
    $key = secret_hash($scope.':'.$identity.':'.intdiv(time(), $seconds));
    query('INSERT INTO rate_limits (bucket, hits, expires_at) VALUES (?, 1, ?) ON DUPLICATE KEY UPDATE hits=hits+1', [$key, time()+$seconds*2]);
    return (int)query('SELECT hits FROM rate_limits WHERE bucket=?', [$key])->fetchColumn() <= $max;
}
function client_ip(): string { return $_SERVER['REMOTE_ADDR'] ?? 'cli'; } // Do not trust arbitrary forwarded headers.
function image_url(?string $v): string {
    if (!$v) return url('img/logov.png');
    if (preg_match('~^https://res\.cloudinary\.com/~', $v)) return $v;
    if (preg_match('~^img/[a-zA-Z0-9_./-]+$~D', $v) && !str_contains($v, '..')) return url($v);
    return url('img/logov.png');
}
function http_post(string $endpoint, array $headers, string|array $body, int $timeout = 20): array {
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_RETURNTRANSFER=>true, CURLOPT_CONNECTTIMEOUT=>5, CURLOPT_TIMEOUT=>$timeout, CURLOPT_HTTPHEADER=>$headers, CURLOPT_POSTFIELDS=>$body, CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS]);
    $raw = curl_exec($ch); $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    if ($raw === false) throw new RuntimeException('Falha de comunicação externa.');
    return [$status, json_decode($raw, true)];
}
