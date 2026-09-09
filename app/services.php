<?php
require_once __DIR__.'/core.php';
function upload_image(string $field): ?array {
    $f = $_FILES[$field] ?? null;
    if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) return null;
    if (env('UPLOADS_ENABLED','0') !== '1') throw new DomainException('O envio de imagens ainda não está disponível.');
    if (!function_exists('imagecreatefromstring') || !function_exists('imagewebp')) throw new DomainException('O servidor ainda precisa habilitar o processamento de imagens.');
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name']) || $f['size'] > 2*1024*1024) throw new DomainException('Envie uma imagem de até 2 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $size = getimagesize($f['tmp_name']);
    if (!in_array($mime,['image/jpeg','image/png','image/webp'], true) || !$size || $size[0] < 1 || $size[1] < 1 || $size[0]*$size[1] > 12000000 || max($size[0],$size[1]) > 6000) throw new DomainException('Imagem inválida ou com dimensões muito grandes.');
    $cloud = env('CLOUDINARY_CLOUD_NAME');
    if (!preg_match('/^[a-z0-9_-]+$/iD',$cloud) || !env('CLOUDINARY_API_SECRET') || !env('CLOUDINARY_API_KEY')) throw new DomainException('O envio de imagens ainda não foi configurado.');
    $image = imagecreatefromstring(file_get_contents($f['tmp_name']));
    if (!$image) throw new DomainException('Não foi possível ler a imagem.');
    $tmp = tempnam(sys_get_temp_dir(), 'vitalize-');
    try {
        imagewebp($image,$tmp,82); imagedestroy($image);
        $id = 'vitalize/'.bin2hex(random_bytes(16)); $timestamp = time();
        $signature = sha1('public_id='.$id.'&timestamp='.$timestamp.env('CLOUDINARY_API_SECRET'));
        [$status,$r] = http_post('https://api.cloudinary.com/v1_1/'.$cloud.'/image/upload', [], ['file'=>new CURLFile($tmp,'image/webp','image.webp'),'public_id'=>$id,'timestamp'=>$timestamp,'api_key'=>env('CLOUDINARY_API_KEY'),'signature'=>$signature]);
        if ($status !== 200 || !is_string($r['secure_url'] ?? null) || !str_starts_with($r['secure_url'],'https://res.cloudinary.com/')) throw new DomainException('Não foi possível enviar a imagem. Tente novamente.');
        return ['url'=>$r['secure_url'],'id'=>$id];
    } finally { if (is_file($tmp)) unlink($tmp); }
}
function queue_media(?string $id): void { if ($id) query('INSERT IGNORE INTO media_cleanup(public_id) VALUES (?)',[$id]); }
function send_account_email(array $u, string $purpose): void {
    $token = bin2hex(random_bytes(32)); $expires = time()+($purpose === 'reset' ? 1800 : 86400);
    query('DELETE FROM tokens_conta WHERE id_usuario=? AND finalidade=?',[$u['id_usuario'],$purpose]);
    query('INSERT INTO tokens_conta VALUES (?,?,?,?)',[hash('sha256',$token),$u['id_usuario'],$purpose,$expires]);
    $link = absolute_url(($purpose === 'reset' ? 'recuperar.php' : 'verificar.php').'?token='.$token);
    $subject = $purpose === 'reset' ? 'Redefinir sua senha — Vitalize' : 'Confirme seu e-mail — Vitalize';
    $body = $subject."\n\nAbra este link e confirme a ação: ".$link."\n\nSe não foi você, ignore esta mensagem. O link expira e só pode ser usado uma vez.";
    if (env('MAIL_TRANSPORT') === 'log' && !production()) {
        $dir = env('MAIL_LOG_DIR',dirname(__DIR__).'/storage');
        if (!is_dir($dir)) mkdir($dir,0700,true);
        file_put_contents($dir.'/mail-'.bin2hex(random_bytes(8)).'.json',json_encode(['to'=>$u['email'],'subject'=>$subject,'text'=>$body],JSON_UNESCAPED_UNICODE));
        return;
    }
    if (!env('RESEND_API_KEY') || !env('MAIL_FROM')) throw new RuntimeException('Configure o serviço de e-mail.');
    [$status] = http_post('https://api.resend.com/emails',['Authorization: Bearer '.env('RESEND_API_KEY'),'Content-Type: application/json'],json_encode(['from'=>env('MAIL_FROM'),'to'=>[$u['email']],'subject'=>$subject,'text'=>$body],JSON_THROW_ON_ERROR));
    if ($status < 200 || $status >= 300) throw new RuntimeException('Serviço de e-mail indisponível.');
}
function account_token(string $token, string $purpose): ?array {
    if (!preg_match('/^[a-f0-9]{64}$/D',$token)) return null;
    return query('SELECT t.*,u.email FROM tokens_conta t JOIN usuarios u ON u.id_usuario=t.id_usuario WHERE token_hash=? AND finalidade=? AND expires_at>? FOR UPDATE',[hash('sha256',$token),$purpose,time()])->fetch() ?: null;
}
