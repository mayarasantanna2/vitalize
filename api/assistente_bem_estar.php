<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/ai.php';
require_post(true);
$u=require_user(true);
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0)>8192) json_response(['erro'=>'Requisição muito grande.'],413);
$raw=file_get_contents('php://input',false,null,0,8193);
if (strlen($raw)>8192) json_response(['erro'=>'Requisição muito grande.'],413);
if (!str_starts_with(strtolower($_SERVER['CONTENT_TYPE'] ?? ''),'application/json')) json_response(['erro'=>'Envie JSON.'],415);
try {
    $body=json_decode($raw,true,16,JSON_THROW_ON_ERROR);
    if (!is_array($body) || ($body['consentimento'] ?? false)!==true) json_response(['erro'=>'Confirme o envio das seleções à IA.'],422);
    $input=wellness_input($body);
} catch(JsonException|DomainException $e) { json_response(['erro'=>$e instanceof DomainException ? $e->getMessage() : 'JSON inválido.'],422); }
session_write_close();
json_response(wellness_generate($input,(int)$u['id_usuario']));
