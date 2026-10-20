
<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/csrf.php';

exigirTipo('jovem');

$uid = (int) $_SESSION['id_usuario'];
$msg = '';

$pastaFotos = __DIR__ . '/uploads/perfis/';

if (!is_dir($pastaFotos)) {
    mkdir($pastaFotos, 0755, true);
}

function urlValida(string $url): bool
{
    if ($url === '') {
        return true;
    }

    $esquema = strtolower(
        (string) parse_url($url, PHP_URL_SCHEME)
    );

    return filter_var($url, FILTER_VALIDATE_URL)
        && in_array($esquema, ['http', 'https'], true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    // Busca a foto atual do jovem.
    $consultaFoto = $conexao->prepare(
        'SELECT foto_perfil FROM jovens WHERE usuario_id = ?'
    );

    $consultaFoto->bind_param('i', $uid);
    $consultaFoto->execute();

    $dadosFoto = $consultaFoto->get_result()->fetch_assoc();
    $consultaFoto->close();

    $novaFoto = $dadosFoto['foto_perfil'] ?? null;

    // Recebe os campos do formulário.
    $bio = trim($_POST['bio'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $formacao = trim($_POST['formacao'] ?? '');
    $linkedin = trim($_POST['linkedin'] ?? '');
    $github = trim($_POST['github'] ?? '');
    $portfolio = trim($_POST['portfolio'] ?? '');
    $nascTexto = trim($_POST['data_nascimento'] ?? '');

    // Valida a data de nascimento.
    $nasc = null;

    if ($nascTexto !== '') {
        $d = DateTime::createFromFormat('!Y-m-d', $nascTexto);
        $errosData = DateTime::getLastErrors();

        $dataValida = $d !== false
            && $d->format('Y-m-d') === $nascTexto
            && (
                $errosData === false
                || (
                    $errosData['warning_count'] === 0
                    && $errosData['error_count'] === 0
                )
            );

        if (
            $dataValida
            && $d <= new DateTime('today')
        ) {
            $nasc = $nascTexto;
        } else {
            $msg = 'Data de nascimento inválida.';
        }
    }

    // Valida o tamanho dos campos.
    if (
        $msg === ''
        && (
            mb_strlen($bio) > 500
            || mb_strlen($cidade) > 120
            || mb_strlen($formacao) > 180
        )
    ) {
        $msg = 'Algum campo ultrapassou o tamanho permitido.';
    }

    // Valida os links.
    if (
        $msg === ''
        && !(
            urlValida($linkedin)
            && urlValida($github)
            && urlValida($portfolio)
        )
    ) {
        $msg = 'Use links completos começando com http:// ou https://.';
    }

    // Valida a foto selecionada.
    $arquivoFoto = null;

    if (
        isset($_FILES['foto_perfil'])
        && $_FILES['foto_perfil']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        $arquivo = $_FILES['foto_perfil'];

        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            $msg = 'Não foi possível enviar a foto.';
        } elseif ($arquivo['size'] > 3 * 1024 * 1024) {
            $msg = 'A foto deve ter no máximo 3 MB.';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeReal = $finfo->file($arquivo['tmp_name']);
            $info = @getimagesize($arquivo['tmp_name']);

            $tiposPermitidos = [
                IMAGETYPE_JPEG => 'jpg',
                IMAGETYPE_PNG  => 'png',
                IMAGETYPE_WEBP => 'webp'
            ];

            if (!$info || !isset($tiposPermitidos[$info[2]]) || !in_array($mimeReal, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                $msg = 'Envie uma imagem JPG, PNG ou WEBP.';
            } else {
                $arquivoFoto = [
                    'temporario' => $arquivo['tmp_name'],
                    'extensao' => $tiposPermitidos[$info[2]]
                ];
            }
        }
    }

    // Salva a foto e atualiza os dados do perfil.
    if ($msg === '') {
        if ($arquivoFoto !== null) {
            try {
                $nomeFoto = bin2hex(random_bytes(16))
                    . '.' . $arquivoFoto['extensao'];

                if (
                    move_uploaded_file(
                        $arquivoFoto['temporario'],
                        $pastaFotos . $nomeFoto
                    )
                ) {
                    $novaFoto = 'uploads/perfis/' . $nomeFoto;
                } else {
                    $msg = 'Erro ao salvar a foto no servidor.';
                }
            } catch (Throwable $erro) {
                $msg = 'Não foi possível processar a foto.';
            }
        }

        if ($msg === '') {
            $st = $conexao->prepare(
                'UPDATE jovens
                 SET bio=?, cidade=?, formacao=?, linkedin=?,
                     github=?, portfolio=?, data_nascimento=?, foto_perfil=?
                 WHERE usuario_id=?'
            );

            $st->bind_param(
                'ssssssssi',
                $bio,
                $cidade,
                $formacao,
                $linkedin,
                $github,
                $portfolio,
                $nasc,
                $novaFoto,
                $uid
            );

            $st->execute();
            $st->close();

            header('Location: perfil.php?salvo=1');
            exit;
        }
    }
}

// Busca os dados salvos do perfil.
$st = $conexao->prepare(
    'SELECT bio, cidade, formacao, linkedin, github,
            portfolio, data_nascimento, foto_perfil
     FROM jovens
     WHERE usuario_id=?'
);

$st->bind_param('i', $uid);
$st->execute();

$perfil = $st->get_result()->fetch_assoc() ?: [];

$st->close();

// Mantém os dados digitados caso haja algum erro.
if ($msg !== '') {
    $camposFormulario = [
        'bio',
        'cidade',
        'formacao',
        'linkedin',
        'github',
        'portfolio',
        'data_nascimento'
    ];

    $perfil = array_merge(
        $perfil,
        array_intersect_key(
            $_POST,
            array_flip($camposFormulario)
        )
    );
}

$paginaAtual = 'perfil.php';
$baseMenu = '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Meu perfil | StartWay</title>

    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/menu.css">
    <link rel="stylesheet" href="css/perfil.css">
</head>

<body>
<div class="layout">

    <?php require __DIR__ . '/includes/menu.php'; ?>

    <main class="conteudo">
        <div class="perfil-pagina">

            <section class="perfil-hero">
                <div class="perfil-avatar">

                    <?php if (!empty($perfil['foto_perfil'])): ?>
                        <img
                            src="<?= e($perfil['foto_perfil']) ?>"
                            alt="Sua foto de perfil"
                        >
                    <?php else: ?>
                        <span>
                            <?= e(
                                mb_strtoupper(
                                    mb_substr(
                                        $_SESSION['nome'] ?? 'J',
                                        0,
                                        1
                                    )
                                )
                            ) ?>
                        </span>
                    <?php endif; ?>

                </div>

                <div class="perfil-hero-info">
                    <span class="perfil-etiqueta">
                        SEU ESPAÇO PROFISSIONAL
                    </span>

                    <h1>
                        <?= e($_SESSION['nome'] ?? 'Meu perfil') ?>
                    </h1>

                    <p>
                        Mostre seus talentos e prepare-se para novas oportunidades.
                    </p>
                </div>
            </section>

            <h2>Complete seu perfil</h2>

            <p class="perfil-sub">
                Conte um pouco sobre você. Essas informações ajudam
                as empresas a conhecer o seu perfil.
            </p>

            <?php if (isset($_GET['salvo'])): ?>
                <div class="perfil-msg sucesso" role="status">
                    Perfil atualizado com sucesso.
                </div>
            <?php endif; ?>

            <?php if ($msg !== ''): ?>
                <div class="perfil-msg erro" role="alert">
                    <?= e($msg) ?>
                </div>
            <?php endif; ?>

            <form
                method="post"
                class="perfil-form"
                enctype="multipart/form-data"
            >
                <?php csrf_campo(); ?>

                <div class="perfil-foto-campo">
                    <label for="foto_perfil">
                        Sua foto de perfil
                    </label>

                    <input
                        id="foto_perfil"
                        type="file"
                        name="foto_perfil"
                        accept="image/jpeg,image/png,image/webp"
                    >

                    <small>
                        JPG, PNG ou WEBP. Tamanho máximo de 3 MB.
                    </small>
                </div>

                <label for="bio">
                    Sobre mim
                    <textarea
                        id="bio"
                        name="bio"
                        maxlength="500"
                    ><?= e($perfil['bio'] ?? '') ?></textarea>
                </label>

                <div class="linha-dupla">
                    <label for="cidade">
                        Cidade
                        <input
                            id="cidade"
                            name="cidade"
                            maxlength="120"
                            value="<?= e($perfil['cidade'] ?? '') ?>"
                        >
                    </label>

                    <label for="data_nascimento">
                        Data de nascimento
                        <input
                            id="data_nascimento"
                            type="date"
                            name="data_nascimento"
                            value="<?= e($perfil['data_nascimento'] ?? '') ?>"
                        >
                    </label>
                </div>

                <label for="formacao">
                    Formação
                    <input
                        id="formacao"
                        name="formacao"
                        maxlength="180"
                        value="<?= e($perfil['formacao'] ?? '') ?>"
                    >
                </label>

                <label for="linkedin">
                    LinkedIn
                    <input
                        id="linkedin"
                        type="url"
                        name="linkedin"
                        placeholder="https://linkedin.com/in/seu-perfil"
                        value="<?= e($perfil['linkedin'] ?? '') ?>"
                    >
                </label>

                <label for="github">
                    GitHub
                    <input
                        id="github"
                        type="url"
                        name="github"
                        placeholder="https://github.com/seu-usuario"
                        value="<?= e($perfil['github'] ?? '') ?>"
                    >
                </label>

                <label for="portfolio">
                    Portfólio
                    <input
                        id="portfolio"
                        type="url"
                        name="portfolio"
                        placeholder="https://seusite.com"
                        value="<?= e($perfil['portfolio'] ?? '') ?>"
                    >
                </label>

                <button type="submit">
                    Salvar perfil
                </button>
            </form>

        </div>
    </main>
</div>

<script src="js/menu.js"></script>
</body>
</html>
