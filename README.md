# StartWay

Plataforma em PHP puro + mysqli, MySQL, HTML/CSS e JavaScript vanilla.

## Requisitos e configuração
- PHP 8.1+ com `mysqli`, `mysqlnd` e `fileinfo`; MySQL 8+ ou MariaDB compatível; Apache com PHP.
- Configure `STARTWAY_DB_HOST`, `STARTWAY_DB_PORT`, `STARTWAY_DB_USER`, `STARTWAY_DB_PASSWORD` e `STARTWAY_DB_NAME` no ambiente do servidor. Fallback apenas para desenvolvimento: `localhost:3306`, usuário `root`, senha vazia e banco `startway`.
- O banco precisa conter `usuarios.ativo` (`TINYINT(1) NOT NULL DEFAULT 1`), `admin_logs` (`id`, `admin_id`, `acao`, `alvo_tipo`, `alvo_id`, `ip`, `criado_em`), além das tabelas de tentativas e recuperação descritas no requisito de implantação. Este projeto não cria nem altera SQL automaticamente.

## Instalação
1. Faça backup do banco e configure as variáveis de ambiente.
2. Importe/revise o esquema por sua conta. Não execute arquivos SQL sem revisar sua compatibilidade com o banco atual.
3. Configure o document root para a pasta do projeto e habilite HTTPS em produção.
4. Crie o primeiro administrador pelo terminal, dentro da pasta do projeto: `php admin/criar_admin.php`. O script só funciona em CLI e solicita nome, e-mail e senha; use senha com pelo menos 10 caracteres. Não há senha administrativa fixa.
5. Acesse `admin/login.php` para entrar na área administrativa.

## Estrutura
- `admin/`: login, painel, usuários, vagas, cursos, logout e script CLI de criação do primeiro admin.
- `includes/`: bootstrap de sessão, CSRF, autenticação, auditoria e componentes compartilhados.
- `empresa/`: recursos para contas de empresa.
- `database/`: arquivos SQL preexistentes, não alterados por esta revisão.
- `css/`, `js/`, `img/`, `uploads/`: front-end e arquivos enviados.

## Checklist de segurança para produção
- [ ] HTTPS obrigatório e cookies de sessão `Secure`, `HttpOnly` e `SameSite`.
- [ ] `display_errors=Off`; erros técnicos apenas em logs protegidos.
- [ ] Credenciais do banco via ambiente e usuário SQL com privilégios mínimos.
- [ ] Criar `usuarios.ativo` e `admin_logs` conforme a estrutura definida pelo responsável pelo banco.
- [ ] Revisar permissões de `uploads/`; impedir execução de scripts na pasta.
- [ ] Configurar envio de e-mail via SMTP confiável. O `mail()` é isolado para facilitar substituição; em ambiente local, o link é gravado no log do PHP.
- [ ] Backups regulares, testados e armazenados fora do document root.
- [ ] Revisão de dependências, logs, política de retenção e testes de segurança antes da publicação.
- [ ] Revisão jurídica dos termos, privacidade e tratamento de dados de menores.

## Testes manuais essenciais
1. Execute `php admin/criar_admin.php` no terminal e informe credenciais válidas.
2. Entre em `admin/login.php`; usuário admin ativo deve chegar a `admin/painel.php`.
3. Tente entrar como jovem/empresa no login administrativo: deve falhar com mensagem genérica.
4. Faça cinco tentativas incorretas para o mesmo IP/e-mail; o acesso deve ser bloqueado temporariamente.
5. Envie um POST sem `csrf_token` nas telas protegidas; deve retornar HTTP 403.
6. Entre como jovem/empresa e tente abrir páginas `admin/`; deve ser redirecionado/negado.
7. Verifique se alterações administrativas aparecem em `admin_logs`.

## Observações
O código assume as estruturas informadas pelo responsável pelo banco. Confirme especialmente as colunas existentes no banco real antes de publicar. Os textos de termos e privacidade são provisórios e precisam de revisão jurídica.
