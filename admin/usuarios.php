<?php
require_once dirname(__DIR__).'/includes/admin_helpers.php'; exigirAdmin();
if($_SERVER['REQUEST_METHOD']==='POST'){csrf_validar();$id=(int)($_POST['id']??0);$acao=(string)($_POST['acao']??'');
 if($id>0 && in_array($acao,['ativar','desativar','excluir'],true)){
  $q=$conexao->prepare('SELECT id,tipo,ativo FROM usuarios WHERE id=?');$q->bind_param('i',$id);$q->execute();$alvo=$q->get_result()->fetch_assoc();$q->close();
  if($alvo){$proprio=$id===(int)$_SESSION['id_usuario'];$ultimo=false;if($alvo['tipo']==='admin'){$r=$conexao->query("SELECT COUNT(*) n FROM usuarios WHERE tipo='admin' AND ativo=1");$ultimo=(int)$r->fetch_assoc()['n']<=1;}
   if(!(($acao==='excluir'||$acao==='desativar')&&($proprio||($alvo['tipo']==='admin'&&$ultimo)))){
    if($acao==='ativar'||$acao==='desativar'){$ativo=$acao==='ativar'?1:0;$s=$conexao->prepare('UPDATE usuarios SET ativo=? WHERE id=?');$s->bind_param('ii',$ativo,$id);$s->execute();$s->close();}
    elseif($acao==='excluir'){$s=$conexao->prepare('DELETE FROM usuarios WHERE id=?');$s->bind_param('i',$id);$s->execute();$s->close();}
    adminLog($conexao,$acao,'usuario',$id);
   }
  }
 }
 header('Location: usuarios.php');exit;
}
$busca=trim((string)($_GET['q']??''));$pagina=max(1,(int)($_GET['pagina']??1));$por=20;$offset=($pagina-1)*$por;$like='%'.$busca.'%';
$s=$conexao->prepare('SELECT id,nome,email,tipo,ativo,criado_em FROM usuarios WHERE nome LIKE ? OR email LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?');$s->bind_param('ssii',$like,$like,$por,$offset);$s->execute();$lista=$s->get_result();
adminHeader('Gerenciar usuários');echo '<form method="get"><label for="q">Buscar por nome ou e-mail</label> <input id="q" name="q" value="'.e($busca).'"><button>Buscar</button></form><div style="overflow:auto"><table><thead><tr><th>Nome</th><th>E-mail</th><th>Tipo</th><th>Status</th><th>Ações</th></tr></thead><tbody>';
while($u=$lista->fetch_assoc()){
 echo '<tr><td>'.e($u['nome']).'</td><td>'.e($u['email']).'</td><td>'.e($u['tipo']).'</td><td>'.((int)$u['ativo']===1?'Ativo':'Bloqueado').'</td><td>';
 foreach(['ativar'=>'Ativar','desativar'=>'Desativar','excluir'=>'Excluir'] as $a=>$rot){echo '<form method="post" style="display:inline"'.($a==='excluir'?' onsubmit="return confirm(\'Confirma excluir este usuário?\')"':'').'><input type="hidden" name="id" value="'.(int)$u['id'].'"><input type="hidden" name="acao" value="'.$a.'">';csrf_campo();echo '<button type="submit">'.$rot.'</button></form> ';}
 echo '</td></tr>';
}
echo '</tbody></table></div><nav aria-label="Paginação"><a href="?q='.rawurlencode($busca).'&pagina='.max(1,$pagina-1).'">Anterior</a> | <a href="?q='.rawurlencode($busca).'&pagina='.($pagina+1).'">Próxima</a></nav>';
adminFooter();
