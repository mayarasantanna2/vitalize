<?php
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
require __DIR__.'/../app/core.php';
$email=$argv[1] ?? '';
if(!filter_var($email,FILTER_VALIDATE_EMAIL)) { fwrite(STDERR,"Uso: php bin/admin.php email-da-conta\n"); exit(1); }
$s=query("UPDATE usuarios SET tipo='admin' WHERE email=?",[$email]);
echo $s->rowCount() ? "Conta promovida a moderador.\n" : "Conta não encontrada ou já administradora.\n";
