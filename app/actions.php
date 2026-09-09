<?php
require_once __DIR__.'/bootstrap.php';
require_once __DIR__.'/services.php';

function dispatch_action(string $action): never {
    require_post();
    $destinations = ['signup'=>'cadastro.php','login'=>'login.php','profile'=>'pagperfil.php','delete'=>'pagperfil.php','group'=>'cadastrar_grupo.php','story'=>'relatos.php','join'=>'grupos.php','appointment'=>'saude.php','delete_appointment'=>'saude.php','reset_request'=>'recuperar.php','reset'=>'recuperar.php','verify'=>'verificar.php','verify_request'=>'verificar.php','moderate'=>'admin.php','report'=>'grupos.php','delete_story'=>'relatos.php','delete_group'=>'grupos.php'];
    $return = $destinations[$action] ?? 'index.php';
    if ($action === 'reset' && is_string($_POST['token'] ?? null) && preg_match('/^[a-f0-9]{64}$/D', $_POST['token'])) $return .= '?token='.$_POST['token'];
    $newImage = null;
    try {
        if (!rate_limit('post',client_ip(),120,60)) throw new DomainException('Muitas tentativas. Aguarde um minuto.');
        switch ($action) {
        case 'signup':
            if (!rate_limit('signup',client_ip(),5,3600)) throw new DomainException('Limite de cadastros atingido. Tente mais tarde.');
            $name = text_input('nome',100,true); $email = email_input(); $password = password_input();
            if ($password !== ($_POST['confirmar_senha'] ?? null)) throw new DomainException('As senhas não coincidem.');
            if (($_POST['privacidade'] ?? '') !== '1') throw new DomainException('Leia e confirme as informações de privacidade.');
            query('INSERT INTO usuarios(nome,sobrenome,email,senha,telefone) VALUES (?,?,?,?,?)',[$name,text_input('sobrenome',100),$email,password_hash($password,PASSWORD_DEFAULT),text_input('telefone',20)]);
            $u = query('SELECT * FROM usuarios WHERE id_usuario=?',[db()->lastInsertId()])->fetch(); sign_in($u);
            try { send_account_email($u,'verify'); flash('Conta criada. Verifique seu e-mail para confirmar o cadastro.'); }
            catch (Throwable $e) { error_log('Vitalize email signup unavailable'); flash('Conta criada. O envio de confirmação está indisponível; tente reenviar pelo seu perfil.'); }
            redirect('pagperfil.php');
        case 'login':
            $email = email_input();
            if (!rate_limit('login-ip',client_ip(),15,900) || !rate_limit('login-email',$email,8,900)) throw new DomainException('Muitas tentativas de acesso. Aguarde 15 minutos.');
            $p = $_POST['senha'] ?? ''; if (!is_string($p) || strlen($p)>72) $p = '';
            $u = query('SELECT * FROM usuarios WHERE email=?',[$email])->fetch();
            $dummy = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
            $valid = password_verify($p, $u['senha'] ?? $dummy);
            if (!$u || !$valid || $p === '') throw new DomainException('E-mail ou senha inválidos.');
            if (password_needs_rehash($u['senha'],PASSWORD_DEFAULT)) query('UPDATE usuarios SET senha=? WHERE id_usuario=?',[password_hash($p,PASSWORD_DEFAULT),$u['id_usuario']]);
            sign_in($u); redirect('pagperfil.php');
        case 'logout':
            $_SESSION = []; session_destroy(); setcookie(session_name(),'', ['expires'=>time()-3600,'path'=>url(),'secure'=>production(),'httponly'=>true,'samesite'=>'Lax']); redirect('login.php');
        case 'profile':
            $u = require_user(false,false); $email = email_input(); $name = text_input('nome',100,true);
            $p = $_POST['senha'] ?? ''; if (!is_string($p)) throw new DomainException('Senha inválida.');
            $changed = $email !== $u['email'] || $p !== '';
            if ($changed && !password_verify((string)($_POST['senha_atual'] ?? ''),$u['senha'])) throw new DomainException('Informe a senha atual para mudar o e-mail ou a senha.');
            if ($p !== '') { $p = password_input(); if ($p !== ($_POST['confirmar_senha'] ?? null)) throw new DomainException('As senhas não coincidem.'); }
            $newImage = upload_image('foto_perfil');
            db()->beginTransaction();
            query('UPDATE usuarios SET nome=?,sobrenome=?,email=?,telefone=?,senha=?,email_verificado=?,foto_perfil=?,foto_public_id=?,session_version=session_version+? WHERE id_usuario=?',[$name,text_input('sobrenome',100),$email,text_input('telefone',20),$p !== '' ? password_hash($p,PASSWORD_DEFAULT) : $u['senha'],$email === $u['email'] ? $u['email_verificado'] : 0,$newImage['url'] ?? $u['foto_perfil'],$newImage['id'] ?? $u['foto_public_id'],$changed?1:0,$u['id_usuario']]);
            if ($newImage) queue_media($u['foto_public_id']);
            db()->commit(); $newImage = null;
            if ($changed) { query('DELETE FROM tokens_conta WHERE id_usuario=?',[$u['id_usuario']]); sign_in(query('SELECT * FROM usuarios WHERE id_usuario=?',[$u['id_usuario']])->fetch()); }
            flash('Perfil atualizado.'); redirect('pagperfil.php');
        case 'delete':
            $u = require_user(false,false);
            if (($_POST['confirmar_exclusao'] ?? '') !== '1') throw new DomainException('Confirme que deseja excluir permanentemente a conta.');
            if (!password_verify((string)($_POST['senha_atual'] ?? ''),$u['senha'])) throw new DomainException('Confirme sua senha atual para excluir a conta.');
            db()->beginTransaction();
            $images = query('SELECT imagem_public_id FROM grupos WHERE id_criador=?',[$u['id_usuario']])->fetchAll();
            foreach ($images as $img) queue_media($img['imagem_public_id']); queue_media($u['foto_public_id']);
            query('DELETE FROM denuncias WHERE (tipo=\'grupo\' AND alvo IN (SELECT id_grupo FROM grupos WHERE id_criador=?)) OR (tipo=\'relato\' AND alvo IN (SELECT id_relato FROM relatos WHERE id_usuario=?))',[$u['id_usuario'],$u['id_usuario']]);
            query('DELETE FROM consultas WHERE id_usuario=?',[$u['id_usuario']]);
            query('DELETE FROM relatos WHERE id_usuario=?',[$u['id_usuario']]);
            query('DELETE FROM grupos WHERE id_criador=?',[$u['id_usuario']]);
            query('DELETE FROM usuarios WHERE id_usuario=?',[$u['id_usuario']]); db()->commit();
            $_SESSION=[]; session_destroy(); redirect('login.php');
        case 'group':
            $u = require_user(); $name = text_input('nome',150,true); $info = text_input('mais_info',5000,true); $link = safe_link(text_input('link',2048));
            $date = date_value(text_input('data_encontro',10)); $time = time_value(text_input('horario',5));
            if (!rate_limit('group',(string)$u['id_usuario'],5,86400)) throw new DomainException('Limite diário de grupos atingido.');
            $newImage = upload_image('imagem');
            query('INSERT INTO grupos (id_criador,nome_grupo,mais_info,foco,data_encontro,horario,link,telefone_grupo,imagem,imagem_public_id) VALUES (?,?,?,?,?,?,?,?,?,?)',[$u['id_usuario'],$name,$info,text_input('foco',100),$date,$time,$link,text_input('telefone_grupo',150),$newImage['url'] ?? null,$newImage['id'] ?? null]); $newImage=null;
            flash('Grupo enviado para moderação. O responsável é a sua conta.'); redirect('grupos.php');
        case 'story':
            $u = require_user(); $title=text_input('titulo',150,true); $body=text_input('conteudo',10000,true);
            if (($_POST['publicacao'] ?? '') !== '1') throw new DomainException('Confirme que deseja publicar este texto.');
            if (!rate_limit('story',(string)$u['id_usuario'],10,86400)) throw new DomainException('Limite diário de relatos atingido.');
            query('INSERT INTO relatos (id_usuario,titulo,relato,anonimo) VALUES (?,?,?,?)',[$u['id_usuario'],$title,$body,($_POST['anonimo'] ?? '')==='1'?1:0]); flash('Relato enviado para moderação.'); redirect('relatos.php');
        case 'join':
            $u = require_user(); $id=(int)text_input('id',12,true);
            if (!query("SELECT id_grupo FROM grupos WHERE id_grupo=? AND status='aprovado'",[$id])->fetch()) throw new DomainException('Grupo indisponível.');
            if (($_POST['sair'] ?? '')==='1') query('DELETE FROM participantes WHERE id_grupo=? AND id_usuario=?',[$id,$u['id_usuario']]);
            else query('INSERT IGNORE INTO participantes (id_grupo,id_usuario) VALUES (?,?)',[$id,$u['id_usuario']]);
            flash('Sua participação foi atualizada.'); redirect('grupos.php');
        case 'appointment':
            $u = require_user(); $date=date_value(text_input('data',10,true)); $time=time_value(text_input('horario',5,true)); $type=text_input('tipo',20,true);
            if (!in_array($type,['Consulta','Exame'],true)) throw new DomainException('Tipo inválido.');
            $values=[$date,$time,text_input('medico',150),text_input('nome_local',150,true),$type,text_input('especialidade',100)]; $id=(int)text_input('id',12);
            if ($id) query('UPDATE consultas SET data=?,horario=?,medico=?,nome_local=?,tipo=?,especialidade=? WHERE id_consulta=? AND id_usuario=?',[...$values,$id,$u['id_usuario']]);
            else query('INSERT INTO consultas (data,horario,medico,nome_local,tipo,especialidade,id_usuario) VALUES (?,?,?,?,?,?,?)',[...$values,$u['id_usuario']]);
            flash('Compromisso salvo na sua agenda.'); redirect('saude.php');
        case 'delete_appointment':
            $u=require_user(); query('DELETE FROM consultas WHERE id_consulta=? AND id_usuario=?',[(int)text_input('id',12,true),$u['id_usuario']]); flash('Compromisso removido.'); redirect('saude.php');
        case 'reset_request':
            $email=email_input();
            if (rate_limit('reset-ip',client_ip(),5,3600) && rate_limit('reset-email',$email,3,3600)) {
                $u=query('SELECT * FROM usuarios WHERE email=?',[$email])->fetch();
                if ($u) { try { send_account_email($u,'reset'); } catch(Throwable $e) { error_log('Vitalize reset mail unavailable'); } }
            }
            flash('Se houver uma conta com esse e-mail, você receberá as instruções. Caso não cheguem, tente mais tarde ou contate o responsável pelo site.'); redirect('recuperar.php');
        case 'reset':
            $p=password_input(); if ($p!==($_POST['confirmar_senha'] ?? null)) throw new DomainException('As senhas não coincidem.');
            db()->beginTransaction(); $t=account_token(text_input('token',64,true),'reset'); if (!$t) throw new DomainException('Link inválido ou expirado. Solicite outro.');
            query('UPDATE usuarios SET senha=?,session_version=session_version+1 WHERE id_usuario=?',[password_hash($p,PASSWORD_DEFAULT),$t['id_usuario']]);
            query('DELETE FROM tokens_conta WHERE id_usuario=?',[$t['id_usuario']]); db()->commit(); $_SESSION=[]; flash('Senha alterada. Entre novamente.'); redirect('login.php');
        case 'verify_request':
            $u=require_user(false,false); if (!rate_limit('verify',(string)$u['id_usuario'],3,3600)) throw new DomainException('Aguarde antes de reenviar.');
            if (!$u['email_verificado']) send_account_email($u,'verify'); flash('Confirmação enviada. Verifique seu e-mail.'); redirect('verificar.php');
        case 'verify':
            db()->beginTransaction(); $t=account_token(text_input('token',64,true),'verify'); if (!$t) throw new DomainException('Link inválido ou expirado.');
            query('UPDATE usuarios SET email_verificado=1 WHERE id_usuario=?',[$t['id_usuario']]); query('DELETE FROM tokens_conta WHERE token_hash=?',[$t['token_hash']]); db()->commit(); flash('E-mail confirmado.'); redirect('pagperfil.php');
        case 'moderate':
            require_admin(); $type=text_input('tipo',10,true); $id=(int)text_input('id',12,true); $status=text_input('status',15,true);
            if ($type==='denuncia') query('UPDATE denuncias SET resolvida=1 WHERE id=?',[$id]);
            else {
                if (!in_array($type,['grupo','relato'],true) || !in_array($status,['aprovado','rejeitado','pendente'],true)) throw new DomainException('Ação inválida.');
                $table=$type==='grupo'?'grupos':'relatos'; $column=$type==='grupo'?'id_grupo':'id_relato'; query("UPDATE $table SET status=? WHERE $column=?",[$status,$id]);
            } flash('Moderação atualizada.'); redirect('admin.php');
        case 'report':
            $u=require_user(); $type=text_input('tipo',10,true); $id=(int)text_input('id',12,true);
            if (!in_array($type,['grupo','relato'],true)) throw new DomainException('Conteúdo inválido.');
            $table=$type==='grupo'?'grupos':'relatos'; $col=$type==='grupo'?'id_grupo':'id_relato';
            if (!query("SELECT $col FROM $table WHERE $col=? AND status='aprovado'",[$id])->fetch()) throw new DomainException('Conteúdo indisponível.');
            if (!rate_limit('report',(string)$u['id_usuario'],10,86400)) throw new DomainException('Limite de denúncias atingido.');
            query('INSERT INTO denuncias(id_usuario,tipo,alvo,motivo) VALUES (?,?,?,?)',[$u['id_usuario'],$type,$id,text_input('motivo',1000,true)]); flash('Denúncia enviada para análise.'); redirect($type==='grupo'?'grupos.php':'relatos.php');
        case 'delete_story':
            $u=require_user(); $id=(int)text_input('id',12,true);
            db()->beginTransaction();
            if (query('SELECT id_relato FROM relatos WHERE id_relato=? AND id_usuario=? FOR UPDATE',[$id,$u['id_usuario']])->fetch()) {
                query("DELETE FROM denuncias WHERE tipo='relato' AND alvo=?",[$id]);
                query('DELETE FROM relatos WHERE id_relato=? AND id_usuario=?',[$id,$u['id_usuario']]);
            }
            db()->commit(); flash('Relato removido.'); redirect('relatos.php');
        case 'delete_group':
            $u=require_user(); $id=(int)text_input('id',12,true); db()->beginTransaction();
            $g=query('SELECT * FROM grupos WHERE id_grupo=? AND id_criador=? FOR UPDATE',[$id,$u['id_usuario']])->fetch();
            if ($g) {
                query("DELETE FROM denuncias WHERE tipo='grupo' AND alvo=?",[$id]);
                queue_media($g['imagem_public_id']);
                query('DELETE FROM grupos WHERE id_grupo=? AND id_criador=?',[$id,$u['id_usuario']]);
            }
            db()->commit(); flash('Grupo removido.'); redirect('grupos.php');
        default: http_response_code(404); exit('Ação inexistente.');
        }
    } catch(Throwable $ex) {
        if (db()->inTransaction()) db()->rollBack();
        if ($newImage) queue_media($newImage['id']);
        if ($ex instanceof DomainException) flash($ex->getMessage());
        elseif ($ex instanceof PDOException && $ex->getCode()==='23000') flash('Não foi possível salvar. Confira os dados; o e-mail pode já estar cadastrado.');
        else { error_log('Vitalize action '.$action.' '.get_class($ex)); flash('Não foi possível concluir agora. Tente novamente.'); }
        redirect($return);
    }
}
