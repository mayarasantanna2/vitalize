<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
require __DIR__.'/../app/core.php';
require __DIR__.'/../app/schema.php';
// Use only after backing up the configured database. No DROP/TRUNCATE or user deletion.
function columns(string $table): array { return array_column(query('SHOW COLUMNS FROM `'.$table.'`')->fetchAll(),'Type','Field'); }
function add_column(string $table,string $name,string $definition): void { if(!array_key_exists($name,columns($table))) db()->exec("ALTER TABLE `$table` ADD COLUMN `$name` $definition"); }
try {
    $lock=query("SELECT GET_LOCK('vitalize_schema_migration',10)")->fetchColumn();
    if(!$lock) throw new RuntimeException('Outra migração está em execução.');
    $tables=query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach(['grupos'=>['contato','telefone_grupo'],'relatos'=>['conteudo','relato']] as $table=>$pair) {
        if(in_array($table,$tables,true)) { $c=columns($table); if(isset($c[$pair[0]],$c[$pair[1]])) throw new RuntimeException("Existem as duas colunas em $table; compare os valores manualmente antes de migrar."); }
    }
    foreach(schema_statements() as $sql) db()->exec($sql);
    $c=columns('grupos');
    if(isset($c['contato'])) db()->exec('ALTER TABLE grupos CHANGE contato telefone_grupo VARCHAR(150) NULL');
    $c=columns('relatos');
    if(isset($c['conteudo'])) db()->exec('ALTER TABLE relatos CHANGE conteudo relato TEXT NOT NULL');
    add_column('relatos','anonimo','TINYINT(1) NOT NULL DEFAULT 0');
    add_column('relatos','status',"VARCHAR(15) NOT NULL DEFAULT 'pendente'");
    add_column('grupos','status',"VARCHAR(15) NOT NULL DEFAULT 'pendente'");
    add_column('grupos','imagem_public_id','VARCHAR(255) NULL');
    add_column('usuarios','foto_public_id','VARCHAR(255) NULL');
    add_column('usuarios','session_version','INT NOT NULL DEFAULT 0');
    add_column('consultas','id_usuario','INT NULL');
    add_column('consultas','tipo',"VARCHAR(20) NOT NULL DEFAULT 'Consulta'");
    add_column('consultas','especialidade','VARCHAR(100) NULL');
    $indexes=query('SHOW INDEX FROM consultas')->fetchAll();
    $indexColumns=[];
    foreach($indexes as $index) $indexColumns[$index['Key_name']][(int)$index['Seq_in_index']]=$index['Column_name'];
    if (!array_filter($indexColumns,fn($cols)=>($cols[1] ?? '')==='id_usuario' && ($cols[2] ?? '')==='data')) db()->exec('ALTER TABLE consultas ADD INDEX consultas_usuario_data (id_usuario,data)');
    if (!query("SELECT 1 FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='consultas' AND COLUMN_NAME='id_usuario' AND REFERENCED_TABLE_NAME='usuarios' AND REFERENCED_COLUMN_NAME='id_usuario'")->fetchColumn()) {
        if(query('SELECT 1 FROM consultas c LEFT JOIN usuarios u ON u.id_usuario=c.id_usuario WHERE c.id_usuario IS NOT NULL AND u.id_usuario IS NULL LIMIT 1')->fetchColumn()) throw new RuntimeException('Consultas possuem usuários inexistentes. Confira os vínculos antes de retomar; nenhum registro foi apagado.');
        db()->exec('ALTER TABLE consultas ADD CONSTRAINT consultas_usuario_fk FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)');
    }
    if (!array_filter(query('SHOW INDEX FROM relatos')->fetchAll(),fn($i)=>$i['Column_name']==='data_publicacao' && (int)$i['Seq_in_index']===1)) db()->exec('ALTER TABLE relatos ADD INDEX relatos_publicacao (data_publicacao)');
    $c=columns('grupos');
    if(str_starts_with(strtolower($c['horario'] ?? ''),'datetime')) {
        if(isset($c['horario_legado'])) throw new RuntimeException('Confira horario_legado antes de continuar.');
        db()->exec('ALTER TABLE grupos CHANGE horario horario_legado DATETIME NULL, ADD horario TIME NULL');
        db()->exec('UPDATE grupos SET horario=TIME(horario_legado)');
    }
    db()->exec('ALTER TABLE grupos MODIFY mais_info TEXT NULL, MODIFY link VARCHAR(2048) NULL');
    foreach(['usuarios','endereco','grupos','relatos','consultas'] as $table) db()->exec("ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $dbname=(string)query('SELECT DATABASE()')->fetchColumn();
    db()->exec('ALTER DATABASE `'.str_replace('`','``',$dbname).'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    echo "Migração concluída. Registros antigos foram preservados. Grupos e relatos precisam de revisão.\n";
    echo "Consultas antigas sem usuário continuam sem dono: atribua somente após conferir a origem.\n";
    echo "Se existia DATETIME, horario_legado foi mantido para auditoria.\n";
} catch(Throwable $e) { fwrite(STDERR,'Migração interrompida: '.$e->getMessage()."\nDDL não é atômico; confira o backup antes de retomar.\n"); exit(1); }
finally { query("SELECT RELEASE_LOCK('vitalize_schema_migration')"); }
