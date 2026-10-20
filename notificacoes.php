<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/formatadores.php';

if (empty($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

$usuarioId = (int) $_SESSION['id_usuario'];

// Marca uma notificação como lida, sempre conferindo se ela pertence ao usuário.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'marcar_lida') {
        $notificacaoId = (int) ($_POST['notificacao_id'] ?? 0);

        if ($notificacaoId > 0) {
            $st = $conexao->prepare(
                'UPDATE notificacoes SET lida = 1 WHERE id = ? AND usuario_id = ?'
            );
            $st->bind_param('ii', $notificacaoId, $usuarioId);
            $st->execute();
            $st->close();
        }

        header('Location: notificacoes.php');
        exit;
    }

    if ($acao === 'marcar_todas') {
        $st = $conexao->prepare(
            'UPDATE notificacoes SET lida = 1 WHERE usuario_id = ? AND lida = 0'
        );
        $st->bind_param('i', $usuarioId);
        $st->execute();
        $st->close();

        header('Location: notificacoes.php');
        exit;
    }
}

$st = $conexao->prepare(
    'SELECT COUNT(*) AS total
     FROM notificacoes
     WHERE usuario_id = ? AND lida = 0'
);
$st->bind_param('i', $usuarioId);
$st->execute();
$totalNaoLidas = (int) ($st->get_result()->fetch_assoc()['total'] ?? 0);
$st->close();

$st = $conexao->prepare(
    'SELECT id, candidatura_id, mensagem, tipo, lida, criada_em
     FROM notificacoes
     WHERE usuario_id = ?
     ORDER BY criada_em DESC, id DESC'
);
$st->bind_param('i', $usuarioId);
$st->execute();
$resultado = $st->get_result();
$notificacoes = [];

while ($linha = $resultado->fetch_assoc()) {
    $notificacoes[] = $linha;
}
$st->close();

function notificacaoTipoClasse(string $tipo): string
{
    return match ($tipo) {
        'aprovacao' => 'aprovacao',
        'recusa' => 'recusa',
        'candidatura' => 'candidatura',
        default => 'informacao',
    };
}

function notificacaoTipoRotulo(string $tipo): string
{
    return match ($tipo) {
        'aprovacao' => 'Aprovação',
        'recusa' => 'Atualização de candidatura',
        'candidatura' => 'Candidatura',
        default => 'Informação',
    };
}

function notificacaoData(?string $data): string
{
    if (!$data) {
        return '';
    }

    try {
        return (new DateTime($data))->format('d/m/Y \\à\\s H:i');
    } catch (Exception $e) {
        return $data;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notificações | StartWay</title>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/menu.css">

    <style>
        /*
         * Mantém o painel do site, mas centraliza o conteúdo da página
         * em uma largura confortável em vez de espalhá-lo pela tela.
         */
        .conteudo-notificacoes {
            box-sizing: border-box;
            width: 100%;
            min-width: 0;
            padding: 38px clamp(20px, 5vw, 64px) 52px;
            color: #2d174c;
        }

        .notificacoes-inner {
            width: 100%;
            max-width: 1000px;
            margin-inline: auto;
        }

        .notificacoes-cabecalho {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 30px;
        }

        .notificacoes-sobretitulo {
            margin: 0 0 8px;
            color: #8154c7;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.4px;
            text-transform: uppercase;
        }

        .notificacoes-titulo {
            margin: 0;
            color: #2d174c;
            font-size: clamp(27px, 4vw, 36px);
            font-weight: 850;
            letter-spacing: -.8px;
        }

        .notificacoes-descricao {
            margin: 9px 0 0;
            color: #756486;
            font-size: 15px;
            line-height: 1.6;
        }

        .notificacoes-contador {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 30px;
            height: 30px;
            margin-left: 8px;
            padding: 0 9px;
            border-radius: 999px;
            background: #eee4ff;
            color: #6428bd;
            font-size: 13px;
            font-weight: 850;
            vertical-align: middle;
        }

        .notificacoes-marcar-todas {
            flex-shrink: 0;
            padding: 10px 14px;
            border: 1px solid #e4d5fb;
            border-radius: 10px;
            background: #fff;
            color: #7138cf;
            font: inherit;
            font-size: 13px;
            font-weight: 750;
            cursor: pointer;
        }

        .notificacoes-marcar-todas:hover {
            background: #f8f3ff;
        }

        .notificacoes-lista {
            border-top: 1px solid #eadff7;
        }

        .notificacao-item {
            display: grid;
            grid-template-columns: 42px minmax(0, 1fr) auto;
            align-items: start;
            gap: 16px;
            padding: 24px 8px;
            border-bottom: 1px solid #eadff7;
            transition: background .2s ease;
        }

        .notificacao-item.nao-lida {
            background: linear-gradient(90deg, #faf6ff 0%, #fff 72%);
        }

        .notificacao-icone {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 13px;
            background: #f0e7ff;
            color: #7138cf;
            font-size: 19px;
            font-weight: 800;
        }

        .notificacao-icone.aprovacao {
            background: #e8f8ee;
            color: #197344;
        }

        .notificacao-icone.recusa {
            background: #fff0f0;
            color: #b8323c;
        }

        .notificacao-conteudo {
            min-width: 0;
        }

        .notificacao-tipo {
            margin: 0 0 6px;
            color: #8154c7;
            font-size: 11px;
            font-weight: 850;
            letter-spacing: .7px;
            text-transform: uppercase;
        }

        .notificacao-mensagem {
            margin: 0;
            color: #38224f;
            font-size: 15px;
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .notificacao-data {
            display: block;
            margin-top: 8px;
            color: #8b7c98;
            font-size: 12px;
        }

        .notificacao-lado {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-top: 3px;
        }

        .notificacao-ponto {
            width: 8px;
            height: 8px;
            flex-shrink: 0;
            border-radius: 50%;
            background: #8b50e8;
        }

        .notificacao-marcar {
            padding: 7px 10px;
            border: 1px solid #e7dcf8;
            border-radius: 9px;
            background: #fff;
            color: #7138cf;
            font: inherit;
            font-size: 12px;
            font-weight: 750;
            cursor: pointer;
        }

        .notificacao-marcar:hover {
            background: #f7f1ff;
        }

        .notificacoes-vazio {
            padding: 64px 20px;
            text-align: center;
        }

        .notificacoes-vazio-icone {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 58px;
            height: 58px;
            margin-bottom: 15px;
            border-radius: 18px;
            background: #f2eaff;
            color: #7138cf;
            font-size: 25px;
        }

        .notificacoes-vazio h2 {
            margin: 0 0 8px;
            color: #351b57;
            font-size: 20px;
        }

        .notificacoes-vazio p {
            margin: 0;
            color: #756486;
            font-size: 14px;
            line-height: 1.7;
        }

        @media (max-width: 700px) {
            .conteudo-notificacoes {
                padding: 28px 18px 38px;
            }

            .notificacoes-cabecalho {
                align-items: flex-start;
                flex-direction: column;
                gap: 18px;
            }

            .notificacao-item {
                grid-template-columns: 38px minmax(0, 1fr);
                gap: 12px;
                padding: 19px 2px;
            }

            .notificacao-icone {
                width: 38px;
                height: 38px;
                border-radius: 11px;
            }

            .notificacao-lado {
                grid-column: 2;
                padding-top: 0;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .notificacao-item {
                transition: none;
            }
        }
    </style>
</head>

<body>
    <div class="layout">
        <?php
        $paginaAtual = 'notificacoes.php';
        $baseMenu = '';
        require __DIR__ . '/includes/menu.php';
        ?>

        <main class="conteudo conteudo-notificacoes">
            <div class="notificacoes-inner">
                <header class="notificacoes-cabecalho">
                    <div>
                        <p class="notificacoes-sobretitulo">StartWay</p>
                        <h1 class="notificacoes-titulo">
                            Notificações
                            <?php if ($totalNaoLidas > 0): ?>
                                <span class="notificacoes-contador"><?= $totalNaoLidas ?></span>
                            <?php endif; ?>
                        </h1>
                        <p class="notificacoes-descricao">
                            Acompanhe as atualizações da sua conta e das suas candidaturas.
                        </p>
                    </div>

                    <?php if ($totalNaoLidas > 0): ?>
                        <form method="POST">
                            <?php csrf_campo(); ?>
                            <input type="hidden" name="acao" value="marcar_todas">
                            <button class="notificacoes-marcar-todas" type="submit">
                                Marcar todas como lidas
                            </button>
                        </form>
                    <?php endif; ?>
                </header>

                <?php if (!$notificacoes): ?>
                    <section class="notificacoes-vazio">
                        <div class="notificacoes-vazio-icone" aria-hidden="true">&#128276;</div>
                        <h2>Você está em dia!</h2>
                        <p>Quando houver novidades, elas aparecerão aqui.</p>
                    </section>
                <?php else: ?>
                    <section class="notificacoes-lista" aria-label="Lista de notificações">
                        <?php foreach ($notificacoes as $notificacao): ?>
                            <?php
                            $tipo = (string) $notificacao['tipo'];
                            $lida = (int) $notificacao['lida'] === 1;
                            $classeTipo = notificacaoTipoClasse($tipo);
                            $simbolo = $tipo === 'aprovacao'
                                ? '&#10003;'
                                : ($tipo === 'recusa' ? '&#10005;' : '&#8226;');
                            ?>
                            <article class="notificacao-item <?= $lida ? '' : 'nao-lida' ?>">
                                <div class="notificacao-icone <?= e($classeTipo) ?>" aria-hidden="true">
                                    <?= $simbolo ?>
                                </div>

                                <div class="notificacao-conteudo">
                                    <p class="notificacao-tipo"><?= e(notificacaoTipoRotulo($tipo)) ?></p>
                                    <p class="notificacao-mensagem"><?= nl2br(e($notificacao['mensagem'])) ?></p>
                                    <time class="notificacao-data" datetime="<?= e($notificacao['criada_em']) ?>">
                                        <?= e(notificacaoData($notificacao['criada_em'])) ?>
                                    </time>
                                </div>

                                <div class="notificacao-lado">
                                    <?php if (!$lida): ?>
                                        <span class="notificacao-ponto" title="Não lida" aria-label="Não lida"></span>
                                        <form method="POST">
                                            <?php csrf_campo(); ?>
                                            <input type="hidden" name="acao" value="marcar_lida">
                                            <input type="hidden" name="notificacao_id" value="<?= (int) $notificacao['id'] ?>">
                                            <button type="submit" class="notificacao-marcar">Marcar como lida</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="notificacao-data">Lida</span>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="js/menu.js"></script>
</body>
</html>
                                        