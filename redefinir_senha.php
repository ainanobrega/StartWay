<?php
require_once __DIR__ . '/includes/csrf.php';
$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $senha = (string) ($_POST['senha'] ?? '');
    if (strlen($senha) < 10)
        $mensagem = 'A senha deve ter pelo menos 10 caracteres.';
    else {
        $hash = hash('sha256', $token);
        $st = $conexao->prepare('SELECT id,usuario_id FROM recuperacao_senha WHERE token_hash=? AND usado_em IS NULL AND expira_em>NOW() LIMIT 1');
        $st->bind_param('s', $hash);
        $st->execute();
        $r = $st->get_result()->fetch_assoc();
        $st->close();
        if ($r) {
            $ph = password_hash($senha, PASSWORD_DEFAULT);
            $uid = (int) $r['usuario_id'];
            $st = $conexao->prepare('UPDATE usuarios SET senha=? WHERE id=?');
            $st->bind_param('si', $ph, $uid);
            $st->execute();
            $st->close();
            $rid = (int) $r['id'];
            $st = $conexao->prepare('UPDATE recuperacao_senha SET usado_em=NOW() WHERE id=? AND usado_em IS NULL');
            $st->bind_param('i', $rid);
            $st->execute();
            $st->close();
            header('Location: login.php?senha=alterada');
            exit;
        } else
            $mensagem = 'Este link é inválido ou expirou.';
    }
}
?><!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Redefinir senha | StartWay</title>
    <link rel="stylesheet" href="css/login.css">
</head>

<body>
    <main class="form-box">
        <h1>Redefinir senha</h1>
        <p class="lead">Escolha uma nova senha para acessar sua conta.</p>

        <?php if ($mensagem): ?>
            <p class="alerta" role="alert" aria-live="polite"><?= e($mensagem) ?></p>
        <?php endif; ?>

        <form method="post">
            <?php csrf_campo(); ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">

            <label for="senha">Nova senha</label>
            <input id="senha" name="senha" type="password" minlength="10"
                autocomplete="new-password" placeholder="Mínimo de 10 caracteres" required>

            <button type="submit">Salvar nova senha</button>
        </form>

        <a class="voltar" href="login.php">Voltar ao login</a>
    </main>
</body>

</html>