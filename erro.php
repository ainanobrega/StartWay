<?php
$codigo = (int) ($_GET['codigo'] ?? http_response_code());
if (!in_array($codigo, [403, 404, 500], true))
    $codigo = 500;
http_response_code($codigo);
$titulos = [403 => 'Acesso negado', 404 => 'Página não encontrada', 500 => 'Algo deu errado'];
$mensagens = [403 => 'Você não tem permissão para acessar este conteúdo.', 404 => 'A página que você procura não existe ou mudou de endereço.', 500 => 'O sistema encontrou um problema. Tente novamente mais tarde.']; ?><!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= htmlspecialchars($titulos[$codigo], ENT_QUOTES, 'UTF-8') ?> | StartWay</title>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/login.css">
</head>

<body>
    <main class="form-box">
        <h1><?= htmlspecialchars($titulos[$codigo], ENT_QUOTES, 'UTF-8') ?></h1>
        <p role="alert"><?= htmlspecialchars($mensagens[$codigo], ENT_QUOTES, 'UTF-8') ?></p><a href="index.html">Voltar ao
            início</a>
    </main>
</body>

</html>