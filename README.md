# Vitalize — versão corrigida

## Revisão de hospedagem de 09/09/2026

Base remota incorporada: `997e40c`. A revisão local anterior foi preservada
e integrada aos ajustes remotos de conexão, agenda e Railway. Os uploads
existentes no GitHub foram mantidos. Nenhuma chave Groq literal foi encontrada
nos commits acessíveis ou arquivos desta cópia.

Antes de ativar esta versão em um banco existente, faça backup e execute
`php bin/migrate.php` com as variáveis do banco correto. Não importe o SQL de
instalação por cima dos dados existentes. A migração não roda automaticamente.

No Railway, configure DB_HOST, DB_PORT, DB_NAME, DB_USER e DB_PASSWORD com as
referências do serviço MySQL. Use DB_SSL_CA em conexões públicas; somente se
ambos os serviços usarem a rede privada, configure DB_PRIVATE_NETWORK=1.
Configure APP_ENV=production, APP_URL com a URL HTTPS real, APP_BASE_PATH vazio
na raiz e APP_KEY com pelo menos 32 caracteres aleatórios. O container respeita
PORT e usa 10000 se a variável não existir.

GROQ_API_KEY e GROQ_MODEL são lidas exclusivamente com getenv. Configure ambas
no ambiente, inclusive no desenvolvimento; config.local.php não fornece essas
duas variáveis. Revogue a chave anteriormente vazada no painel da Groq.
Sem as variáveis, a resposta de contingência é identificada na interface.

Railway e Render consultam /ready.php, que verifica configuração essencial e
estrutura do banco e retorna 503 quando indisponível. /health.php continua
indicando apenas que o processo PHP respondeu. A prontidão não testa entrega
de e-mail, chamadas à IA ou uploads externos.

Validação desta revisão: 63 verificações HTTP/banco aprovadas, 51 arquivos PHP
sem erros de sintaxe e migração repetida sobre banco vazio e sobre a estrutura
remota atual. Banco de testes isolado, sem dados reais nem chaves externas.
O build Docker e os serviços externos ainda precisam de validação no provedor.

Referências: [Docker no Railway](https://docs.railway.com/builds/dockerfiles)
e [Responses API da Groq](https://console.groq.com/docs/responses-api).

Aplicação PHP/MySQL de apoio, com cadastro, perfil, grupos moderados, relatos,
agenda privada, recuperação de senha, confirmação de e-mail e mensagens por IA.
Base de origem: mayarasantanna2/vitalize, commit 69defc1 (25/08/2026).

Esta entrega é uma versão completa, refatorada, para substituir o código da
aplicação. Mantém os caminhos principais e a identidade de cores/borboleta,
mas reorganiza as telas e centraliza os componentes. Não é apenas o SQL enviado
na etapa anterior: use o `vitalize.sql` DESTA pasta para uma instalação nova.

## Início rápido no XAMPP

1. Extraia esta pasta como `vitalize` dentro de `C:\xampp\htdocs`.
2. Inicie Apache e MySQL. Use PHP 8.2+ atualizado; a imagem Docker usa PHP 8.3.
3. No phpMyAdmin, importe `vitalize.sql` em uma instalação vazia. Ele cria o
   banco `vitalize` e suas tabelas. Não importe por cima de tabelas existentes.
4. Copie `config.example.php` para `config.local.php` e ajuste o acesso ao banco.
5. Gere uma chave com `php -r "echo bin2hex(random_bytes(32));"` e coloque o
   resultado em `APP_KEY`. O arquivo local não deve ser publicado no Git.
6. Abra `http://localhost/vitalize/` e cadastre uma conta de teste.
7. Para torná-la moderadora: `php bin/admin.php email-da-conta`.

No terminal do Windows, caso `php` não seja reconhecido, use
`C:\xampp\php\php.exe` no lugar de `php`.

Sem chave de IA, a tela apresenta uma mensagem pré-definida identificada.
Sem serviço de e-mail, a conta pode ser testada localmente, mas a confirmação
e a recuperação por e-mail só funcionarão depois de configurar o envio.
Uploads estão desativados inicialmente e usam uma imagem padrão.

Extensões: PDO MySQL, cURL, mbstring, fileinfo, GD com WebP e sessões.
Não há dependências npm ou Composer. Bibliotecas de fontes e ícones externas
foram removidas; o frontend utiliza CSS e JavaScript locais.

## Se já houver banco e arquivos em uso

Não apague o banco nem importe o SQL completo por cima dele.

1. Faça um backup pelo phpMyAdmin ou use `php bin/backup.php CAMINHO_FORA_DO_SITE.sql`.
2. Faça também uma cópia das imagens e da configuração atual.
3. Instale a nova versão em uma pasta separada e configure uma cópia do banco.
4. Execute `php bin/migrate.php` nessa cópia e teste os fluxos.
5. Em uma janela de manutenção, repita o backup e a migração no banco escolhido,
   usando uma credencial de migração com permissões DDL. Volte a usar uma
   credencial limitada a leitura/escrita para o site.
6. Publique o novo diretório completo; não misture arquivos PHP antigos com os novos.
7. Se seu banco referencia `img/uploads/...`, preserve/copie essas imagens da
   instalação anterior. Elas não foram distribuídas dentro deste pacote.

A migração aceita o SQL original e o corrigido da etapa anterior. Renomeia
`contato` para `telefone_grupo`, `conteudo` para `relato`, cria `anonimo` e
acrescenta as novas estruturas. Não elimina registros. Se houver as duas
versões de uma coluna, ela para para evitar escolher dados arbitrariamente.

Quando o horário original é DATETIME, o valor inteiro fica preservado em
`horario_legado`, e `horario` passa a TIME. A conversão para utf8mb4 preserva
caracteres existentes; não tenta adivinhar/reparar textos já corrompidos.
DDL não é transacional: uma falha pode deixar migração parcial, por isso o backup
e o teste em cópia são necessários.

Grupos e relatos antigos passam a pendentes de revisão. Grupos sem criador e
consultas antigas sem usuário não recebem donos inventados. O administrador
deve conferir a origem antes de associá-los manualmente.

## O que foi corrigido ou concluído

- Banco compatível com o código, utf8mb4 e scripts separados de instalação/migração.
- Cadastro com validação no servidor e senhas com hash; erros exibidos ao usuário.
- Sessões persistentes no banco, cookies HttpOnly/SameSite, renovação de ID e
  invalidação após redefinir senha. HTTPS obrigatório na configuração de produção.
- POST e CSRF em todos os processos de escrita, inclusive logout e exclusão.
- Alteração de e-mail/senha e exclusão exigem a senha atual.
- Login e formulários com limites de frequência persistentes e mensagens genéricas.
- Agenda real: criar, consultar, editar e excluir somente os próprios compromissos.
- Grupos vinculados ao criador, links HTTP/HTTPS validados e participação persistente.
- Relatos com autoria oculta na interface, aviso de publicação e moderação.
- Painel de aprovação/rejeição, denúncias e exclusão de conteúdo pelo proprietário.
- Exclusão de conta com tratamento explícito de seus grupos, relatos e vínculos.
- Exportação autenticada de dados em JSON.
- Tokens de confirmação e redefinição armazenados somente como hash, com expiração
  e uso único; envio por Resend, sem dependência de SMTP.
- Uploads opcionais para Cloudinary, MIME real, dimensões e tamanho limitados,
  reprocessamento em WebP, IDs aleatórios e fila de remoção de imagens antigas.
- API de IA protegida, sintomas acentuados corrigidos, limite de entrada/saída,
  cotas por usuário/global, cache privado e contingência explícita.
- Layout responsivo, navegação comum, foco visível, formulários rotulados,
  saída escapada e política CSP sem scripts inline.
- Remoção dos diagnósticos públicos, contatos fictícios, promessas de botões
  sem ação, página salva do Instagram e banner com estatísticas não verificadas.
- Docker, configuração Render, testes CI, manutenção e backup/restauração.

## Configurar IA

Configure `GROQ_API_KEY` e `GROQ_MODEL=openai/gpt-oss-20b` no ambiente do servidor.
O endpoint continua sendo `/api/assistente_bem_estar.php` e usa Groq Responses API.
Nenhuma chave vai ao JavaScript.

Diferença deliberada em relação ao protótipo: a IA gera uma mensagem de
acolhimento, não inventa ingredientes ou dietas para pacientes. Para habilitar
opções alimentares, o responsável de saúde deve preencher e revisar
`app/receitas.php`, com autoria e data da revisão. O catálogo inicial está vazio;
esta entrega não alega revisão clínica que não aconteceu. Mesmo com catálogo,
restrições alimentares livres ou sintomas impedem recomendação automática.

O formulário pede autorização antes de enviar humor/sintomas à Groq. Não envia
nomes, e-mails, dados da agenda ou o texto livre das restrições. Não cria histórico
clínico. Respostas ficam em cache privado por 24 horas; restrições não vão para
localStorage. O valor antigo de localStorage é removido ao visitar a nova versão.

Padrões configuráveis:
- `AI_USER_DAILY_CALLS=2` por conta.
- `AI_DAILY_TOKEN_BUDGET=150000` reservado globalmente por dia UTC.
- No máximo 3 tentativas externas por minuto globalmente e 1 a cada 30 segundos
  por usuário. Falhas contam para o orçamento; não há repetição automática.
- Saída limitada a 1.024 tokens; orçamento reservado conservadoramente a partir
  dos bytes da requisição + margem/saída. Registra tokens reais e tempo nos logs,
  sem registrar prompt ou resposta. A cota real do provedor pode ser menor.

Essa reserva é conservadora e não promete o número máximo teórico de gerações.
Quando faltam chave, cota, resposta válida ou disponibilidade, o site usa conteúdo
pré-definido e o informa. Ative Zero Data Retention no painel da Groq e reveja os
termos do fornecedor para a finalidade do projeto. Testes locais não usaram chave
real, portanto a conta/modelo ainda precisa de um teste de integração no deploy.

## Configurar e-mail e imagens

E-mail: configure `RESEND_API_KEY` e `MAIL_FROM` com remetente/domínio verificado.
`APP_URL` deve ser a URL HTTPS oficial; links de recuperação nunca usam o Host
enviado pelo navegador. Use `REQUIRE_EMAIL_VERIFICATION=1` em produção.
Não recebo essas credenciais automaticamente e nenhuma mensagem real foi enviada.

Somente para desenvolvimento, `MAIL_TRANSPORT=log` grava mensagens em arquivos
locais no diretório privado `storage`, ou `MAIL_LOG_DIR` fora do site. Em produção
esse modo é desabilitado. Os logs locais incluem tokens de teste e não devem
ser publicados ou usados para contas reais.

Imagens: configure `CLOUDINARY_CLOUD_NAME`, `CLOUDINARY_API_KEY`,
`CLOUDINARY_API_SECRET` e `UPLOADS_ENABLED=1`. O servidor aceita imagens até 2 MB,
12 megapixels e 6.000 pixels por dimensão, reprocessando em WebP. Necessita GD.
O segredo assina o upload no servidor. Não há upload unsigned público.
Use somente fotos destinadas à exibição pública; não envie exames ou documentos.
Armazene snapshots/exports das imagens separadamente do backup SQL se necessário.

## Hospedar no Render + Aiven

1. Crie um MySQL Free no Aiven e um banco com o nome autorizado pelo painel.
2. Para banco vazio, importe `vitalize.sql`, retirando CREATE DATABASE/USE se o
   painel impuser outro nome. Para banco existente, siga a migração acima.
3. Crie usuário do site com SELECT/INSERT/UPDATE/DELETE; use usuário separado para DDL.
4. Baixe o certificado CA do Aiven e configure as regras de rede para a aplicação
   e a máquina administrativa, evitando acesso amplo sem necessidade.
5. Envie ESTA pasta a um repositório seu, sem config.local.php nem dados privados.
6. No Render crie Web Service, runtime Docker, plano Free. O Dockerfile usa
   Apache na porta 10000. Também há `render.yaml` para importar como Blueprint.
7. Cadastre o certificado como Secret File `ca.pem`, montado em
   `/etc/secrets/ca.pem`. Configure `DB_SSL_CA` com esse caminho.
8. Configure DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD, APP_ENV=production,
   APP_URL=https://SEU-ENDERECO, APP_KEY aleatória e APP_BASE_PATH vazio na raiz.
9. Configure e-mail, IA e PRIVACY_CONTACT; habilite uploads somente após configurar
   Cloudinary. REQUIRE_EMAIL_VERIFICATION deve permanecer 1 em produção.
10. Publique, crie/confirme uma conta e promova o moderador pelo CLI a partir
    da sua máquina, conectando-se ao banco com TLS.
11. Valide login, publicação/moderação, agenda, recuperação, upload, erro da IA
    e persistência após novo deploy. /health.php é liveness e não atesta o banco.

O Render Free pode pausar e reiniciar. Sessões/banco não dependem do disco
efêmero; imagens novas ficam no Cloudinary. Não hospede MySQL dentro desse
container. A versão gratuita não inclui terminal/cron persistente: execute
manutenção e backups por uma máquina administrativa ou serviço agendado de sua
escolha. Não foi criada nenhuma assinatura ou automação paga.

O contador de IP usa REMOTE_ADDR, sem confiar em cabeçalhos arbitrários. Atrás
de proxy, múltiplos usuários podem compartilhar esse limite. Ajuste IP real
somente na camada de proxy confiável, mantendo os limites por conta e globais.

## Operação e dados

Execute `php bin/maintenance.php` periodicamente (recomendado: a cada hora).
Remove registros temporários expirados e tenta excluir até 100 imagens da fila.
Falhas permanecem na fila para a próxima execução. A indisponibilidade dessa
rotina adia a remoção física; a expiração lógica de sessões/tokens já é verificada
nas requisições. Não há lembretes automáticos de consultas nesta versão.

Backup: `php bin/backup.php /diretorio-privado/backup-novo.sql`.
Use diretório fora do site; o script recusa sobrescrever arquivo existente.
Não execute durante migrações. Proteja e criptografe o arquivo no armazenamento
escolhido, pois contém dados pessoais e hashes de senhas. Sessões, tokens e caches
temporários não entram no backup. Teste restauração em banco vazio e isolado.
Defina prazo de retenção e eliminação de backups com o responsável pelo projeto.

Exclusão de conta remove seus relatos (inclusive os de autoria oculta), agenda,
participações e grupos criados. Participantes desses grupos perdem a associação.
Imagens entram em fila. Backups obedecem a retenção configurada pelo operador.

## Testes

Verificação local realizada com PHP 8.2.12 e MariaDB 10.4.32:
- Sintaxe dos arquivos PHP.
- 58 verificações HTTP/banco: cadastro, sessões, CSRF, isolamento da agenda,
  XSS escapado, links proibidos, moderação, participação, denúncias, IA sem chave,
  cotas, exportação, confirmação, recuperação e exclusão com vínculos.
- Migração do SQL original com dados fictícios e segunda execução da migração.
- Backup restaurado em banco separado, com conferência de registros.
- Página inicial e menu conferidos no navegador em desktop e 390 px.

Para repetir: use banco descartável com `_test_` no nome, configure o mesmo banco
nos processos CLI/servidor e `MAIL_TRANSPORT=log`, `MAIL_LOG_DIR` privado,
`APP_URL=http://127.0.0.1:18089`, `APP_BASE_PATH` vazio. Sem chaves externas.
Execute migração, inicie `php -S 127.0.0.1:18089 -t . router-local.php` e rode
`php tests/integration.php`. Use um ambiente novo para cada execução para não
acumular os limites de requisições. Os testes inserem exclusivamente dados fictícios.

O workflow `.github/workflows/tests.yml` prepara MariaDB e PHP, testa a aplicação
e constrói o container. Não foi executado no GitHub nesta entrega. O build Docker
e chamadas reais a Groq/Resend/Cloudinary dependem do ambiente/credenciais e não
foram validados localmente. Não confunda lint com certificação de segurança.

## Decisões externas que ainda cabem ao responsável

- Chaves/contas, domínio, remetente de e-mail e publicação efetiva.
- Base legal, contato, prazos, contratos de fornecedores e revisão de privacidade.
- Revisão clínica de receitas; não há cardápio aprovado incluído.
- Direitos das imagens/logotipo e licença de distribuição (ver LICENSE-NOTICE.md).
- Parceiros e estabelecimentos: só cadastrar/publicar após confirmar os contatos.

Fontes técnicas: [Groq](https://console.groq.com/docs/responses-api),
[Resend](https://resend.com/docs/api-reference/emails/send-email),
[Cloudinary](https://cloudinary.com/documentation/image_upload_api_reference),
[Render](https://render.com/docs/docker).
