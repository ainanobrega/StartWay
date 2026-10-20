
<?php

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/formatadores.php';
require_once __DIR__ . '/includes/email.php';

$mensagem = 'Se o e-mail estiver cadastrado, você receberá instruções para redefinir a senha.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    $email = trim((string) ($_POST['email'] ?? ''));

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $st = $conexao->prepare(
            'SELECT id, email FROM usuarios WHERE email = ? LIMIT 1'
        );

        $st->bind_param('s', $email);
        $st->execute();

        $usuario = $st->get_result()->fetch_assoc();

        $st->close();

        if ($usuario) {
            $token = bin2hex(random_bytes(32));
            $hash = hash('sha256', $token);
            $expira = date('Y-m-d H:i:s', time() + 3600);
            $usuarioId = (int) $usuario['id'];

            $st = $conexao->prepare(
                'INSERT INTO recuperacao_senha
                (usuario_id, token_hash, expira_em, criado_em)
                VALUES (?, ?, ?, NOW())'
            );

            $st->bind_param('iss', $usuarioId, $hash, $expira);

            if ($st->execute()) {
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

                // Evita usar um host arbitrário no link de recuperação.
                $hostPermitido = in_array(
                    $host,
                    ['localhost', '127.0.0.1'],
                    true
                );

                if ($hostPermitido) {
                    $https = !empty($_SERVER['HTTPS'])
                        && $_SERVER['HTTPS'] !== 'off';

                    $base = rtrim(
                        dirname($_SERVER['SCRIPT_NAME'] ?? '/'),
                        '/'
                    );

                    $url = ($https ? 'https' : 'http')
                        . '://' . $host . $base
                        . '/redefinir_senha.php?token='
                        . rawurlencode($token);

                    if (!enviarEmailRecuperacao($usuario['email'], $url)) {
                        error_log(
                            'StartWay: não foi possível enviar o e-mail de recuperação.'
                        );
                    }
                } else {
                    error_log(
                        'StartWay: configure um host confiável para os links de recuperação.'
                    );
                }
            } else {
                error_log(
                    'StartWay: não foi possível registrar o token de recuperação.'
                );
            }

            $st->close();
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar senha | StartWay</title>
    <link rel="stylesheet" href="css/login.css">
</head>

<body>
    <main class="form-box">
        <h1>Recuperar senha</h1>

        <p class="lead">
            <?= e($mensagem) ?>
        </p>

        <form method="post">
            <?php csrf_campo(); ?>

            <label for="email">E-mail</label>
            <input
                id="email"
                name="email"
                type="email"
                autocomplete="email"
                required
            >

            <button type="submit">Enviar instruções</button>
        </form>

        <a class="voltar" href="login.php">Voltar ao login</a>
    </main>
</body>
</html>
