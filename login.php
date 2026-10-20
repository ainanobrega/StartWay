<?php
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/admin_helpers.php';
$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $email = trim((string) ($_POST['email'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');
    $chave = tentativasChave($email);
    if (!loginBloqueado($conexao, $chave)) {
        $s = $conexao->prepare('SELECT id,nome,email,senha,tipo,ativo FROM usuarios WHERE email=? LIMIT 1');
        $s->bind_param('s', $email);
        $s->execute();
        $u = $s->get_result()->fetch_assoc();
        $s->close();
        if ($u && (int) $u['ativo'] === 1 && password_verify($senha, $u['senha'])) {
            limparFalhasLogin($conexao, $chave);
            if (password_needs_rehash($u['senha'], PASSWORD_DEFAULT)) {
                $ph = password_hash($senha, PASSWORD_DEFAULT);
                $up = $conexao->prepare('UPDATE usuarios SET senha=? WHERE id=?');
                $up->bind_param('si', $ph, $u['id']);
                $up->execute();
                $up->close();
            }
            session_regenerate_id(true);
            $_SESSION['id_usuario'] = (int) $u['id'];
            $_SESSION['nome'] = $u['nome'];
            $_SESSION['tipo'] = strtolower(trim($u['tipo']));
            if ($_SESSION['tipo'] === 'admin') {
                $_SESSION['admin_ultima_atividade'] = time();
                header('Location: admin/painel.php');
            } elseif ($_SESSION['tipo'] === 'empresa')
                header('Location: empresa/painel.php');
            else
                header('Location: home.php');
            exit;
        }
    }
    registrarFalhaLogin($conexao, $chave);
    $mensagem = 'E-mail ou senha incorretos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar | StartWay</title>
    <link rel="stylesheet" href="css/login.css">
</head>

<body>
    <div class="transicao-login" id="transicaoLogin" aria-hidden="true"><img src="img/logo.png"
            alt=""><span>StartWay</span></div>
    <main class="auth-wrapper">
        <section class="auth-lado-esquerdo">
            <header class="brand-container"><img src="img/logo.png" alt="Logo StartWay" class="logo-img"><span
                    class="logo-texto">StartWay</span></header>
            <div class="mensagem-banner"><span class="etiqueta-banner">✦ QUE BOM TER VOCÊ DE VOLTA</span>
                <h1>Seu próximo passo <span>continua aqui.</span></h1>
                <p>Acompanhe seus aprendizados e fique de olho nas oportunidades que podem transformar seu futuro.</p>
            </div><?php include __DIR__ . '/painel_oportunidades.php'; ?>
            <footer class="rodape-banner">© 2026 StartWay · Oportunidade, crescimento e conexão</footer>
        </section>
        <section class="auth-lado-direito" aria-label="Entrar na conta">
            <div class="form-box">
                <div class="form-topo"><span class="form-etiqueta">BEM-VINDO DE VOLTA</span>
                    <h2>Entre na sua conta</h2>
                    <p>Continue desenvolvendo suas habilidades e acompanhando oportunidades.</p>
                </div><?php if ($mensagem !== ''): ?>
                    <div class="mensagem erro" role="alert" aria-live="polite"><?= e($mensagem) ?></div><?php endif; ?>
                <form method="post" action="login.php"><?php csrf_campo(); ?>
                    <div class="grupo-form"><label for="email">E-mail</label><input type="email" name="email" id="email"
                            autocomplete="email" placeholder="voce@email.com" required></div>
                    <div class="grupo-form"><label for="senha">Senha</label><input type="password" name="senha"
                            id="senha" autocomplete="current-password" placeholder="Sua senha" required></div><button
                        type="submit" class="botao-principal">Entrar na conta <span>→</span></button>
                </form>
                <p><a href="esqueci_senha.php">Esqueci minha senha</a></p>
                <p class="link-login">Ainda não tem uma conta? <a href="cadastro.php">Criar conta</a></p>
            </div>
        </section>
    </main>
    <script src="js/transicao.js"></script>
    <script src="js/oportunidades.js"></script>
</body>

</html>