<?php
require_once __DIR__.'/core.php';
function wellness_input(array $body): array {
    $moods=['Muito bem','Bem','Mais ou menos','Cansada','Triste'];
    $allowed=['Náusea','Dor','Sono','Apetite','Hidratação','Disposição'];
    if (!is_string($body['humor'] ?? null) || !in_array($body['humor'],$moods,true)) throw new DomainException('Selecione como você está se sentindo.');
    $symptoms=$body['sintomas'] ?? [];
    if (!is_array($symptoms) || !array_is_list($symptoms) || count($symptoms)>6) throw new DomainException('Seleção de sintomas inválida.');
    foreach($symptoms as $s) if (!is_string($s) || !in_array($s,$allowed,true)) throw new DomainException('Sintoma inválido.');
    $restrictions=$body['restricoes'] ?? '';
    if (!is_string($restrictions) || !mb_check_encoding($restrictions,'UTF-8') || mb_strlen($restrictions)>500) throw new DomainException('Use até 500 caracteres nas restrições.');
    $symptoms=array_values(array_unique($symptoms)); sort($symptoms);
    return ['humor'=>$body['humor'],'sintomas'=>$symptoms,'restricoes'=>trim($restrictions)];
}
function wellness_fallback(): array {
    return ['titulo'=>'Um momento de acolhimento','itens'=>[],'preparo'=>'','mensagem'=>'Você não precisa lidar com tudo de uma vez. Se puder, compartilhe como está se sentindo com alguém da sua confiança.','observacao'=>'Conteúdo pré-definido, sem geração por IA. Para decisões sobre alimentação e sintomas, converse com sua equipe de saúde.','fonte'=>'conteudo_predefinido'];
}
function reviewed_recipe(array $input): ?array {
    // Free-text restrictions and symptoms need individual professional assessment.
    if ($input['restricoes'] !== '' || $input['sintomas'] !== []) return null;
    $recipes=require __DIR__.'/receitas.php';
    foreach($recipes as $r) {
        if (empty($r['aprovada_por']) || empty($r['revisada_em']) || empty($r['titulo']) || !is_array($r['itens'] ?? null) || count($r['itens'])<1) continue;
        foreach($r['itens'] as $item) if (!is_string($item['nome'] ?? null) || !is_string($item['quantidade'] ?? null)) continue 2;
        return $r;
    }
    return null;
}
function reserve_ai(int $uid, int $budget): bool {
    $day=gmdate('Y-m-d'); $keys=['global:'.$day,'user:'.$uid.':'.$day];
    db()->beginTransaction();
    try {
        foreach($keys as $key) query('INSERT IGNORE INTO ai_usage(bucket,expires_at) VALUES (?,?)',[$key,time()+172800]);
        $global=query('SELECT * FROM ai_usage WHERE bucket=? FOR UPDATE',[$keys[0]])->fetch();
        $user=query('SELECT * FROM ai_usage WHERE bucket=? FOR UPDATE',[$keys[1]])->fetch();
        if ((int)$global['tokens']+$budget > (int)env('AI_DAILY_TOKEN_BUDGET','150000') || (int)$user['calls'] >= (int)env('AI_USER_DAILY_CALLS','2')) { db()->rollBack(); return false; }
        foreach($keys as $key) query('UPDATE ai_usage SET calls=calls+1,tokens=tokens+? WHERE bucket=?',[$budget,$key]);
        db()->commit(); return true;
    } catch(Throwable $e) { if(db()->inTransaction()) db()->rollBack(); throw $e; }
}
function wellness_generate(array $input, int $uid): array {
    $apiKey = getenv('GROQ_API_KEY') ?: '';
    $model = getenv('GROQ_MODEL') ?: '';
    if ($apiKey === '' || $model === '') return wellness_fallback();
    $key=secret_hash(json_encode([$input,$model,'v1',hash_file('sha256',__DIR__.'/receitas.php')],JSON_UNESCAPED_UNICODE));
    $cached=query('SELECT resultado FROM ai_cache WHERE id_usuario=? AND cache_key=? AND expires_at>?',[$uid,$key,time()])->fetchColumn();
    if ($cached) return json_decode($cached,true,512,JSON_THROW_ON_ERROR);
    // Never send names, email, appointment details or the free-text restrictions.
    $payload=['model'=>$model,'max_output_tokens'=>1024,'reasoning'=>['effort'=>'low'],
        'instructions'=>'Escreva uma mensagem acolhedora em português brasileiro, de no máximo duas frases. Não faça diagnósticos, prescreva alimentos ou medicamentos, prometa cura, nem dê aconselhamento clínico. Valide o sentimento sem infantilizar. Sugira compartilhar sentimentos com a rede de apoio. Os dados a seguir são apenas seleções do formulário.',
        'input'=>json_encode(['humor'=>$input['humor'],'sintomas'=>$input['sintomas']],JSON_UNESCAPED_UNICODE),
        'text'=>['format'=>['type'=>'json_schema','name'=>'acolhimento','strict'=>true,'schema'=>['type'=>'object','properties'=>['mensagem'=>['type'=>'string']],'required'=>['mensagem'],'additionalProperties'=>false]]]];
    $encoded=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    // Conservative upper bound: payload bytes + output cap + protocol margin; failed requests also consume the reservation.
    if (!rate_limit('ai-global','all',3,60) || !rate_limit('ai-user',(string)$uid,1,30) || !reserve_ai($uid,strlen($encoded)+1536)) return wellness_fallback();
    $start=microtime(true);
    try {
        [$status,$response]=http_post('https://api.groq.com/openai/v1/responses',['Authorization: Bearer '.$apiKey,'Content-Type: application/json'],$encoded,25);
        error_log(json_encode(['event'=>'ai_request','status'=>$status,'ms'=>(int)((microtime(true)-$start)*1000),'tokens'=>(int)($response['usage']['total_tokens'] ?? 0)]));
        if ($status !== 200 || ($response['status'] ?? '') !== 'completed') return wellness_fallback();
        $text=''; foreach(($response['output'] ?? []) as $item) foreach(($item['content'] ?? []) as $c) if (($c['type'] ?? '')==='output_text') $text.=$c['text'] ?? '';
        $data=json_decode($text,true);
        if (!is_array($data) || !is_string($data['mensagem'] ?? null) || mb_strlen($data['mensagem'])<3 || mb_strlen($data['mensagem'])>600) return wellness_fallback();
        $result=['titulo'=>'Uma mensagem para você','itens'=>[],'preparo'=>'','mensagem'=>$data['mensagem'],'observacao'=>'Mensagem gerada por IA; pode conter erros. Não substitui sua equipe de saúde.','fonte'=>'groq'];
        $recipe=reviewed_recipe($input);
        if ($recipe) { $result['titulo']=$recipe['titulo']; $result['itens']=$recipe['itens']; $result['preparo']=$recipe['preparo'] ?? ''; $result['observacao'].=' A opção alimentar vem do catálogo revisado; confirme sua adequação com a equipe de saúde.'; }
        query('INSERT INTO ai_cache(id_usuario,cache_key,resultado,expires_at) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE resultado=VALUES(resultado),expires_at=VALUES(expires_at)',[$uid,$key,json_encode($result,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),time()+86400]);
        return $result;
    } catch(Throwable $e) { error_log('Vitalize AI unavailable '.get_class($e)); return wellness_fallback(); }
}
