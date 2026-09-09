<?php
require __DIR__.'/app/actions.php';
$action=$_POST['acao'] ?? '';
if(!is_string($action)) { http_response_code(400); exit('Ação inválida.'); }
dispatch_action($action);
