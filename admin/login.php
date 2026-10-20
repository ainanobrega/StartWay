<?php
require_once dirname(__DIR__).'/includes/csrf.php'; require_once dirname(__DIR__).'/includes/admin_helpers.php';
$mensagem='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 csrf_validar(); $email=trim((string)($_POST['email']??''));$senha=(string)($_POST['senha']??'');$hashChave=tentativasChave($email);
 if(!loginBloqueado($conexao,$hashChave)){
  $s=$conexao->prepare("SELECT id,nome,email,senha,tipo,ativo FROM usuarios WHERE email=? AND tipo='admin' LIMIT 1");$s->bind_param('s',$email);$s->execute();$u=$s->get_result()->fetch_assoc();$s->close();
  if($u && (int)$u['ativo']===1 && password_verify($senha,$u['senha'])){
   limparFalhasLogin($conexao,$hashChave);if(password_needs_rehash($u['senha'],PASSWORD_DEFAULT)){$novo=password_hash($senha,PASSWORD_DEFAULT);$up=$conexao->prepare('UPDATE usuarios SET senha=? WHERE id=?');$up->bind_param('si',$novo,$u['id']);$up->execute();$up->close();}
   session_regenerate_id(true);$_SESSION['id_usuario']=(int)$u['id'];$_SESSION['nome']=$u['nome'];$_SESSION['tipo']='admin';$_SESSION['admin_ultima_atividade']=time();header('Location: painel.php');exit;
  }
 }
 registrarFalhaLogin($conexao,$hashChave);$mensagem='E-mail ou senha incorretos';
}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Área administrativa | StartWay</title><link rel="stylesheet" href="../css/login.css"></head><body><main class="auth-wrapper"><section class="auth-lado-direito" style="margin:auto"><div class="form-box"><div class="form-topo"><span class="form-etiqueta">STARTWAY</span><h1>Área administrativa</h1><p>Acesso restrito à equipe administrativa.</p></div><?php if($mensagem):?><p class="mensagem erro" role="alert" aria-live="polite"><?=e($mensagem)?></p><?php endif;?><form method="post" action="login.php"><?php csrf_campo();?><div class="grupo-form"><label for="email">E-mail</label><input id="email" name="email" type="email" autocomplete="username" required></div><div class="grupo-form"><label for="senha">Senha</label><input id="senha" name="senha" type="password" autocomplete="current-password" required></div><button class="botao-principal" type="submit">Entrar</button></form></div></section></main></body></html>
