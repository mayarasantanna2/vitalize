<?php
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
require __DIR__.'/../app/schema.php';
echo "-- Vitalize atualizado: instalação NOVA em banco vazio.\n-- Não apaga dados e não substitui a migração de bancos existentes.\nSET NAMES utf8mb4;\nCREATE DATABASE IF NOT EXISTS vitalize CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\nUSE vitalize;\n\n";
foreach(schema_statements() as $sql) echo str_replace('CREATE TABLE IF NOT EXISTS','CREATE TABLE',$sql).";\n\n";
