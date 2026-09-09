<?php
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
require __DIR__.'/../app/core.php';
foreach(['app_sessions','rate_limits','tokens_conta','ai_cache','ai_usage'] as $table) query("DELETE FROM $table WHERE expires_at<?",[time()]);
if(env('CLOUDINARY_API_SECRET') && preg_match('/^[a-z0-9_-]+$/iD',env('CLOUDINARY_CLOUD_NAME'))) {
    foreach(query('SELECT public_id FROM media_cleanup ORDER BY created_at LIMIT 100')->fetchAll() as $row) {
        $id=$row['public_id']; $timestamp=time();
        $signature=sha1('invalidate=true&public_id='.$id.'&timestamp='.$timestamp.env('CLOUDINARY_API_SECRET'));
        try {
            [$status,$r]=http_post('https://api.cloudinary.com/v1_1/'.env('CLOUDINARY_CLOUD_NAME').'/image/destroy',[],['public_id'=>$id,'timestamp'=>$timestamp,'invalidate'=>'true','api_key'=>env('CLOUDINARY_API_KEY'),'signature'=>$signature]);
            if($status===200 && in_array($r['result'] ?? '',['ok','not found'],true)) query('DELETE FROM media_cleanup WHERE public_id=?',[$id]);
        }catch(Throwable $e){ error_log('Vitalize media cleanup pending'); }
    }
}
echo "Manutenção concluída; mídias com falha permanecem na fila.\n";
