<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__).'/conexao.php';
function perguntar(string $rotulo): string { echo $rotulo; return trim((string)fgets(STDIN)); }
$nome=perguntar('Nome do administrador: '); $email=strtolower(perguntar('E-mail: '));
echo 'Senha (mínimo 10 caracteres): '; if(function_exists('readline')){$senha=(string)readline();}else{$senha=trim((string)fgets(STDIN));}
if($nome===''||mb_strlen($nome)>100||!filter_var($email,FILTER_VALIDATE_EMAIL)||mb_strlen($email)>100||strlen($senha)<10){fwrite(STDERR,"Dados inválidos. Nome/e-mail inválidos ou senha menor que 10 caracteres.\n");exit(1);}
$hash=password_hash($senha,PASSWORD_DEFAULT);$tipo='admin';$s=$conexao->prepare('INSERT INTO usuarios (nome,email,senha,tipo,ativo) VALUES (?,?,?,?,1)');$s->bind_param('ssss',$nome,$email,$hash,$tipo);
if(!$s->execute()){fwrite(STDERR,"Não foi possível criar o administrador. Verifique se o e-mail já existe e se a coluna ativo está disponível.\n");error_log('StartWay criar admin: '.$s->error);exit(1);}echo "Administrador criado com sucesso.\n";
