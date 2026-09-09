<?php
require_once __DIR__.'/bootstrap.php';
require_once __DIR__.'/view.php';
if(in_array($page,['login','cadastro','perfil','pagperfil','recuperar','verificar'],true)) require __DIR__.'/auth-pages.php';
elseif(in_array($page,['grupos','sobregrupos','cadastrar_grupo','relatos','admin'],true)) require __DIR__.'/community-pages.php';
elseif($page==='saude') require __DIR__.'/wellness-page.php';
else require __DIR__.'/public-pages.php';
page_footer();
