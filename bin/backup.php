<?php
// Produces a portable SQL snapshot, using the configured TLS PDO connection.
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
require __DIR__.'/../app/core.php';
$target=$argv[1] ?? '';
if(!$target || is_file($target)) { fwrite(STDERR,"Informe um caminho NOVO fora da pasta pública: php bin/backup.php /caminho/backup.sql\n"); exit(1); }
$parent=realpath(dirname($target)); $root=realpath(dirname(__DIR__));
if(!$parent || str_starts_with(strtolower(str_replace('\\','/',$parent).'/'),strtolower(str_replace('\\','/',$root).'/'))) { fwrite(STDERR,"Escolha um diretório existente fora do projeto.\n"); exit(1); }
$f=fopen($target,'x'); if(!$f) exit(1); chmod($target,0600);
try {
    db()->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ'); db()->beginTransaction();
    fwrite($f,"-- Backup Vitalize: restaurar em banco vazio, mantendo a ordem abaixo.\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n");
    foreach(query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
        $quoted='`'.str_replace('`','``',$table).'`';
        $create=query('SHOW CREATE TABLE '.$quoted)->fetch(PDO::FETCH_NUM)[1]; fwrite($f,$create.";\n");
        // Session IDs, tokens and transient caches are intentionally not backed up.
        if(in_array($table,['app_sessions','tokens_conta','ai_cache','rate_limits','ai_usage'],true)) continue;
        $s=query('SELECT * FROM '.$quoted);
        while($row=$s->fetch()) {
            $values=array_map(fn($v)=>$v===null?'NULL':db()->quote((string)$v),array_values($row));
            fwrite($f,'INSERT INTO '.$quoted.' VALUES ('.implode(',',$values).");\n");
        }
    }
    fwrite($f,"SET FOREIGN_KEY_CHECKS=1;\n"); db()->commit(); fclose($f); echo "Backup criado. Proteja o arquivo: ele contém dados pessoais.\n";
}catch(Throwable $e){ if(db()->inTransaction()) db()->rollBack(); fclose($f); fwrite(STDERR,"Backup incompleto; não use para restauração.\n"); exit(1); }
