<?php
declare(strict_types=1);
// Run against a disposable *_test_* database and a local PHP development server.
require __DIR__.'/../app/core.php';
require __DIR__.'/../app/ai.php';
if(PHP_SAPI!=='cli' || !str_contains(env('DB_NAME'),'_test_')) exit("Use apenas um banco descartável com _test_ no nome.\n");
if(env('GROQ_API_KEY')!=='' || env('RESEND_API_KEY')!=='') exit("Desative chaves externas para os testes.\n");
$base=rtrim(env('TEST_URL','http://127.0.0.1:18089'),'/');
if(!preg_match('~^http://(?:127\.0\.0\.1|localhost):\d+$~D',$base)) exit("Use um servidor local de teste.\n");
$count=0;
function check(bool $condition,string $message): void { global $count; if(!$condition) throw new RuntimeException('FALHOU: '.$message); $count++; echo "OK $count: $message\n"; }
final class Browser {
    private $ch;
    public string $csrf='';
    public function __construct() { $this->ch=curl_init(); curl_setopt($this->ch,CURLOPT_COOKIEFILE,''); }
    public function request(string $path,array|string|null $body=null,bool $json=false,array $headers=[]): array {
        global $base;
        $headerList=$headers;
        if($json) $headerList=[...$headerList,'Content-Type: application/json','X-CSRF-Token: '.$this->csrf];
        curl_setopt_array($this->ch,[CURLOPT_URL=>$base.$path,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>15,CURLOPT_HTTPHEADER=>$headerList]);
        if($body!==null) { curl_setopt($this->ch,CURLOPT_POST,true); curl_setopt($this->ch,CURLOPT_POSTFIELDS,is_array($body)?http_build_query($body):$body); }
        else { curl_setopt($this->ch,CURLOPT_POST,false); curl_setopt($this->ch,CURLOPT_HTTPGET,true); }
        $raw=curl_exec($this->ch); if($raw===false) throw new RuntimeException(curl_error($this->ch));
        $h=(int)curl_getinfo($this->ch,CURLINFO_HEADER_SIZE); $status=(int)curl_getinfo($this->ch,CURLINFO_HTTP_CODE); $text=substr($raw,$h);
        if(preg_match('/name="csrf-token" content="([a-f0-9]+)"/',$text,$m)) $this->csrf=$m[1];
        return [$status,$text,substr($raw,0,$h)];
    }
    public function action(string $action,array $body=[]): array { return $this->request('/acao.php',['acao'=>$action,'csrf'=>$this->csrf,...$body]); }
}
$suffix=bin2hex(random_bytes(4)); $email='a'.$suffix.'@example.invalid'; $emailB='b'.$suffix.'@example.invalid'; $password='Senha-teste-12345';
$a=new Browser; $b=new Browser; $anonymous=new Browser;
[$status,$html,$headers]=$a->request('/cadastro.php'); check($status===200 && $a->csrf!=='','Página e sessão disponíveis');
check(str_contains($headers,'HttpOnly') && str_contains($headers,'SameSite=Lax'),'Cookie de sessão protegido');
check(str_contains($headers,"frame-ancestors 'none'"),'CSP aplicada');
check($a->request('/processos/deletarperfil.php')[0]===405,'GET não exclui conta');
check($a->request('/acao.php',['acao'=>'signup','csrf'=>'errado'])[0]===403,'CSRF inválido bloqueado');
$a->action('signup',['nome'=>'','email'=>$email,'senha'=>'','confirmar_senha'=>'','privacidade'=>'1']);
check(!query('SELECT 1 FROM usuarios WHERE email=?',[$email])->fetchColumn(),'Cadastro vazio não insere usuário');
$oldToken=$a->csrf;
$a->action('signup',['nome'=>'Teste A','email'=>$email,'senha'=>$password,'confirmar_senha'=>$password,'privacidade'=>'1']);
[$status,$html]=$a->request('/pagperfil.php'); check($status===200 && str_contains($html,$email),'Cadastro válido autentica usuário');
check($a->csrf!==$oldToken,'Cadastro renova proteção da sessão');
$uid=(int)query('SELECT id_usuario FROM usuarios WHERE email=?',[$email])->fetchColumn();
check($a->request('/processos/agenda.php')[0]===200,'API legada usa a sessão atual');
check($a->request('/processos/agenda.php',['acao'=>'salvar'])[0]===403,'API da agenda bloqueia escrita sem CSRF');
$legacy=['acao'=>'salvar','csrf'=>$a->csrf,'tipo'=>'Consulta','especialidade'=>'Teste','nome_local'=>'Local','data'=>'2026-10-01','horario'=>'10:00'];
check($a->request('/processos/agenda.php',$legacy)[0]===200,'API da agenda salva com sessão e CSRF atuais');
$legacyId=(int)query('SELECT id_consulta FROM consultas WHERE id_usuario=?',[$uid])->fetchColumn();
check($a->request('/processos/agenda.php',[...$legacy,'especialidade'=>['invalida']])[0]===422,'API da agenda rejeita campos estruturados');
check($a->request('/processos/agenda.php',['acao'=>'excluir','csrf'=>$a->csrf,'id_consulta'=>$legacyId])[0]===200,'API da agenda exclui compromisso próprio');
check(password_verify($password,query('SELECT senha FROM usuarios WHERE id_usuario=?',[$uid])->fetchColumn()),'Senha armazenada com hash válido');
$b->request('/cadastro.php'); $b->action('signup',['nome'=>'Teste B','email'=>$emailB,'senha'=>$password,'confirmar_senha'=>$password,'privacidade'=>'1']); $b->request('/pagperfil.php');
$uidB=(int)query('SELECT id_usuario FROM usuarios WHERE email=?',[$emailB])->fetchColumn();
check($b->request('/admin.php')[0]===403,'Usuário comum não acessa moderação');
$a->action('appointment',['data'=>'2026-02-30','horario'=>'10:00','tipo'=>'Consulta','nome_local'=>'Local']);
check((int)query('SELECT COUNT(*) FROM consultas WHERE id_usuario=?',[$uid])->fetchColumn()===0,'Agenda rejeita data inexistente');
$a->action('appointment',['data'=>'2026-10-10','horario'=>'10:30','tipo'=>'Consulta','nome_local'=>'Local privado','especialidade'=>'Especialidade']);
$appointment=(int)query('SELECT id_consulta FROM consultas WHERE id_usuario=?',[$uid])->fetchColumn(); check($appointment>0,'Agenda grava compromisso');
check(str_contains($a->request('/saude.php')[1],'Local privado'),'Agenda lê compromisso após nova requisição');
check(!str_contains($b->request('/saude.php')[1],'Local privado'),'Agenda de outro usuário permanece privada');
$b->action('delete_appointment',['id'=>$appointment]); check((bool)query('SELECT 1 FROM consultas WHERE id_consulta=?',[$appointment])->fetchColumn(),'Outro usuário não exclui compromisso');
$b->action('appointment',['id'=>$appointment,'data'=>'2026-10-11','horario'=>'10:30','tipo'=>'Exame','nome_local'=>'INVASAO']); check(query('SELECT nome_local FROM consultas WHERE id_consulta=?',[$appointment])->fetchColumn()==='Local privado','Outro usuário não altera compromisso');
$a->action('appointment',['id'=>$appointment,'data'=>'2026-10-11','horario'=>'11:30','tipo'=>'Exame','nome_local'=>'Local editado']); check(query('SELECT nome_local FROM consultas WHERE id_consulta=?',[$appointment])->fetchColumn()==='Local editado','Proprietário edita compromisso');
$a->action('group',['nome'=>'Malicioso','mais_info'=>'Texto','link'=>'javascript:alert(1)']); check(!query('SELECT 1 FROM grupos WHERE id_criador=?',[$uid])->fetchColumn(),'Link javascript rejeitado');
$a->action('group',['nome'=>'Grupo '.$suffix,'mais_info'=>'<script>alert(1)</script>','link'=>'https://example.invalid','data_encontro'=>'2026-10-10','horario'=>'08:30']);
$group=query('SELECT * FROM grupos WHERE id_criador=?',[$uid])->fetch(); check($group && $group['status']==='pendente' && $group['horario']==='08:30:00','Grupo grava criador, horário e moderação');
check(!str_contains($anonymous->request('/grupos.php')[1],'Grupo '.$suffix),'Grupo pendente não aparece publicamente');
$a->action('story',['titulo'=>'Relato '.$suffix,'conteudo'=>'<script>alert(2)</script>','anonimo'=>'1','publicacao'=>'1']);
$story=query('SELECT * FROM relatos WHERE id_usuario=?',[$uid])->fetch(); check($story && $story['anonimo']==1 && $story['status']==='pendente','Relato salva conteúdo, autoria oculta e moderação');
query("UPDATE usuarios SET tipo='admin' WHERE id_usuario=?",[$uidB]);
$b->action('moderate',['tipo'=>'grupo','id'=>$group['id_grupo'],'status'=>'aprovado']);
$b->action('moderate',['tipo'=>'relato','id'=>$story['id_relato'],'status'=>'aprovado']);
$html=$anonymous->request('/relatos.php')[1]; check(str_contains($html,'Autoria oculta') && !str_contains($html,'Teste A'),'Relato publicado não revela nome');
check(str_contains($html,'&lt;script&gt;alert(2)&lt;/script&gt;') && !str_contains($html,'<script>alert(2)'),'Conteúdo HTML escapado');
$b->action('join',['id'=>$group['id_grupo']]); check((bool)query('SELECT 1 FROM participantes WHERE id_grupo=? AND id_usuario=?',[$group['id_grupo'],$uidB])->fetchColumn(),'Participação no grupo persistida');
$b->action('report',['tipo'=>'relato','id'=>$story['id_relato'],'motivo'=>'Revisar conteúdo']); check((bool)query('SELECT 1 FROM denuncias WHERE alvo=?',[$story['id_relato']])->fetchColumn(),'Denúncia persistida');
$anonymous->request('/login.php');
$payload=json_encode(['humor'=>'Bem','sintomas'=>['Náusea','Hidratação','Disposição'],'restricoes'=>'','consentimento'=>true],JSON_UNESCAPED_UNICODE);
check($anonymous->request('/api/assistente_bem_estar.php',$payload,true)[0]===401,'IA bloqueia acesso anônimo');
$a->request('/saude.php'); check($a->request('/api/assistente_bem_estar.php','{',true)[0]===422,'IA rejeita JSON inválido');
check($a->request('/api/assistente_bem_estar.php',json_encode(['humor'=>'Bem','consentimento'=>false]),true)[0]===422,'IA exige consentimento');
[$status,$body]=$a->request('/api/assistente_bem_estar.php',$payload,true); $data=json_decode($body,true); check($status===200 && ($data['fonte'] ?? '')==='conteudo_predefinido','Sem chave: contingência identificada');
check(count(wellness_input(json_decode($payload,true))['sintomas'])===3,'Sintomas acentuados preservados');
check(reviewed_recipe(['restricoes'=>'amendoim','sintomas'=>[]])===null,'Restrições impedem cardápio automático');
check(reserve_ai($uid,1000) && reserve_ai($uid,1000) && !reserve_ai($uid,1000),'Cota diária por usuário funciona no banco');
check(!reserve_ai($uidB,200000),'Teto global de tokens bloqueia excesso');
$a->action('profile',['nome'=>'Teste A','email'=>'changed'.$email,'senha_atual'=>'incorreta']); check(query('SELECT email FROM usuarios WHERE id_usuario=?',[$uid])->fetchColumn()===$email,'Troca de e-mail exige senha atual');
check($a->request('/api/exportar.php',['csrf'=>$a->csrf,'senha_atual'=>$password])[0]===200,'Exportação autenticada disponível');
foreach(['/vitalize.sql','/config.local.php','/app/core.php','/storage/test.json','/bin/admin.php','/.env'] as $path) check($anonymous->request($path)[0]===403,'Arquivo privado bloqueado: '.$path);
$a->action('verify_request');
$logs=glob(env('MAIL_LOG_DIR').'/*.json') ?: []; $verifyToken='';
foreach($logs as $file){ $mail=json_decode(file_get_contents($file),true); if($mail['to']===$email && preg_match('/verificar\.php\?token=([a-f0-9]{64})/',$mail['text'],$m) && query('SELECT 1 FROM tokens_conta WHERE token_hash=?',[hash('sha256',$m[1])])->fetchColumn()) $verifyToken=$m[1]; }
check($verifyToken!=='','Link de confirmação criado sem enviar e-mail real');
$a->action('verify',['token'=>$verifyToken]); check((bool)query('SELECT email_verificado FROM usuarios WHERE id_usuario=?',[$uid])->fetchColumn(),'Confirmação de e-mail funciona');
$a->action('reset_request',['email'=>$email]); $resetToken='';
foreach(glob(env('MAIL_LOG_DIR').'/*.json') ?: [] as $file){ $mail=json_decode(file_get_contents($file),true); if($mail['to']===$email && preg_match('/recuperar\.php\?token=([a-f0-9]{64})/',$mail['text'],$m)) $resetToken=$m[1]; }
check($resetToken!=='','Link de redefinição criado');
[$invalidStatus,,$invalidHeaders]=$a->action('reset',['token'=>$resetToken,'senha'=>'curta','confirmar_senha'=>'curta']);
check($invalidStatus===303 && str_contains($invalidHeaders,'recuperar.php?token='.$resetToken),'Senha inválida preserva link para nova tentativa');
check((bool)query('SELECT 1 FROM tokens_conta WHERE token_hash=?',[hash('sha256',$resetToken)])->fetchColumn(),'Erro de preenchimento não consome token');
$newPassword='Nova-senha-123456'; $a->action('reset',['token'=>$resetToken,'senha'=>$newPassword,'confirmar_senha'=>$newPassword]);
check(password_verify($newPassword,query('SELECT senha FROM usuarios WHERE id_usuario=?',[$uid])->fetchColumn()),'Redefinição altera senha');
check(!query('SELECT 1 FROM tokens_conta WHERE token_hash=?',[hash('sha256',$resetToken)])->fetchColumn(),'Token utilizado removido');
check($a->request('/pagperfil.php')[0]===303,'Redefinição encerra sessão');
$a->request('/login.php'); $a->action('login',['email'=>$email,'senha'=>$newPassword]); $a->request('/pagperfil.php');
query("INSERT INTO relatos (id_usuario,titulo,relato) VALUES (?,'Excluir','Teste')",[$uid]); $deleteStory=(int)db()->lastInsertId();
query("INSERT INTO denuncias (id_usuario,tipo,alvo,motivo) VALUES (?,'relato',?,'Teste')",[$uidB,$deleteStory]);
$b->action('delete_story',['id'=>$deleteStory]);
check((bool)query("SELECT 1 FROM denuncias WHERE tipo='relato' AND alvo=?",[$deleteStory])->fetchColumn(),'Outro usuário não remove relato ou denúncia');
$a->action('delete_story',['id'=>$deleteStory]);
check(!query('SELECT 1 FROM relatos WHERE id_relato=?',[$deleteStory])->fetchColumn() && !query("SELECT 1 FROM denuncias WHERE tipo='relato' AND alvo=?",[$deleteStory])->fetchColumn(),'Exclusão individual remove relato e denúncia');
query("INSERT INTO grupos (id_criador,nome_grupo,imagem_public_id) VALUES (?,'Excluir','test-media')",[$uid]); $deleteGroup=(int)db()->lastInsertId();
query("INSERT INTO denuncias (id_usuario,tipo,alvo,motivo) VALUES (?,'grupo',?,'Teste')",[$uidB,$deleteGroup]);
$b->action('delete_group',['id'=>$deleteGroup]);
check((bool)query('SELECT 1 FROM grupos WHERE id_grupo=?',[$deleteGroup])->fetchColumn(),'Outro usuário não exclui grupo');
$a->action('delete_group',['id'=>$deleteGroup]);
check(!query('SELECT 1 FROM grupos WHERE id_grupo=?',[$deleteGroup])->fetchColumn() && !query("SELECT 1 FROM denuncias WHERE tipo='grupo' AND alvo=?",[$deleteGroup])->fetchColumn(),'Exclusão individual remove grupo e denúncia');
check((bool)query("SELECT 1 FROM media_cleanup WHERE public_id='test-media'")->fetchColumn(),'Imagem do grupo excluído entra na fila de limpeza');
$a->action('delete',['senha_atual'=>'errada','confirmar_exclusao'=>'1']); check((bool)query('SELECT 1 FROM usuarios WHERE id_usuario=?',[$uid])->fetchColumn(),'Exclusão exige senha correta');
$a->action('delete',['senha_atual'=>$newPassword,'confirmar_exclusao'=>'1']); check(!query('SELECT 1 FROM usuarios WHERE id_usuario=?',[$uid])->fetchColumn(),'Conta excluída com vínculos');
check(!query('SELECT 1 FROM grupos WHERE id_criador=?',[$uid])->fetchColumn() && !query('SELECT 1 FROM consultas WHERE id_usuario=?',[$uid])->fetchColumn(),'Exclusão remove grupos e agenda');
check(!query('SELECT 1 FROM participantes WHERE id_grupo=?',[$group['id_grupo']])->fetchColumn(),'Exclusão remove participações vinculadas');
echo "PASSOU: $count verificações. Nenhuma chamada real de e-mail/IA foi feita.\n";
