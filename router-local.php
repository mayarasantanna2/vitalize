<?php
// Only for PHP's development server; equivalent to Apache's private-path restrictions.
$path=rawurldecode(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH) ?: '/');
if(preg_match('~(?:^|/)(?:app|bin|tests|storage|database|\.[^/]*)(?:/|$)|(?:\.sql|\.md|\.txt|\.ini|\.json|\.ya?ml)$|(?:^|/)(?:config\.local\.php|conexao\.php|Dockerfile)$~i',$path) || str_contains($path,'..')) { http_response_code(403); exit('Acesso negado.'); }
return false;
