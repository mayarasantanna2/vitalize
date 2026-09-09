<?php
require __DIR__.'/../app/bootstrap.php';
require_post(true); $u=require_user(true,false);
if (!password_verify((string)($_POST['senha_atual'] ?? ''),$u['senha'])) json_response(['erro'=>'Confirme sua senha atual.'],403);
$id=$u['id_usuario'];
unset($u['senha'],$u['token_verificacao'],$u['session_version']);
header('Content-Disposition: attachment; filename="meus-dados-vitalize.json"');
json_response(['perfil'=>$u,'relatos'=>query('SELECT * FROM relatos WHERE id_usuario=?',[$id])->fetchAll(),'grupos_criados'=>query('SELECT * FROM grupos WHERE id_criador=?',[$id])->fetchAll(),'participacoes'=>query('SELECT * FROM participantes WHERE id_usuario=?',[$id])->fetchAll(),'consultas'=>query('SELECT * FROM consultas WHERE id_usuario=?',[$id])->fetchAll(),'denuncias'=>query('SELECT * FROM denuncias WHERE id_usuario=?',[$id])->fetchAll(),'sugestoes_temporarias'=>query('SELECT resultado,expires_at FROM ai_cache WHERE id_usuario=?',[$id])->fetchAll()]);
