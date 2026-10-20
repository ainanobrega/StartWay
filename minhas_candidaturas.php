<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/formatadores.php';

exigirTipo('jovem');

$paginaAtual = 'minhas_candidaturas.php';
$baseMenu = '';

// Busca o perfil do jovem.
$st = $conexao->prepare('SELECT id FROM jovens WHERE usuario_id = ?');
$st->bind_param('i', $_SESSION['id_usuario']);
$st->execute();
$j = $st->get_result()->fetch_assoc();
$st->close();

$rows = [];

// Busca as candidaturas do jovem.
if ($j) {
    $jid = (int) $j['id'];

    $sql = '
        SELECT
            c.status,
            c.data_candidatura,
            v.id AS vaga_id,
            v.titulo,
            e.nome_empresa
        FROM candidaturas c
        INNER JOIN vagas v ON v.id = c.vaga_id
        INNER JOIN empresas e ON e.id = v.empresa_id
        WHERE c.jovem_id = ?
        ORDER BY c.data_candidatura DESC
    ';

    $st = $conexao->prepare($sql);
    $st->bind_param('i', $jid);
    $st->execute();

    $resultado = $st->get_result();
    while ($linha = $resultado->fetch_assoc()) {
        $rows[] = $linha;
    }

    $st->close();
}

/**
 * Converte o status do banco para uma classe CSS.
 * Os valores correspondem aos status usados na tabela candidaturas.
 */
function classeStatus(string $status): string
{
    switch ($status) {
        case 'aprovada':
        case 'aceito':
            return 'status-aceito';

        case 'rejeitada':
        case 'recusado':
            return 'status-recusado';

        case 'enviada':
        case 'em_analise':
        case 'pendente':
        default:
            return 'status-pendente';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#7138cf">

    <title>Minhas candidaturas | StartWay</title>

    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/menu.css">
    <link rel="stylesheet" href="css/minhas_candidaturas.css">

    <style>
        /* ========================================
           STARTWAY — MINHAS CANDIDATURAS
        ======================================== */

        .conteudo {
            min-width: 0;
            padding-bottom: 40px;
        }

        .cabecalho-candidaturas {
            margin-bottom: 28px;
        }

        .cabecalho-candidaturas h1 {
            margin: 0 0 10px;
            color: #2d174c;
            font-size: clamp(28px, 4vw, 36px);
            font-weight: 850;
            letter-spacing: -0.8px;
        }

        .cabecalho-candidaturas h1::after {
            content: "";
            display: block;
            width: 65px;
            height: 4px;
            margin-top: 12px;
            border-radius: 10px;
            background: linear-gradient(90deg, #7138cf, #c084fc);
        }

        .cabecalho-candidaturas > p {
            color: #766b86;
            line-height: 1.7;
        }

        .resumo-candidaturas {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
        }

        .resumo-item {
            padding: 11px 16px;
            border: 1px solid #e9ddfb;
            border-radius: 12px;
            background: #fff;
            color: #60467d;
            font-size: 13px;
            font-weight: 750;
            box-shadow: 0 4px 14px rgba(50, 16, 92, 0.04);
        }

        .lista-candidaturas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr));
            gap: 18px;
        }

        .conteudo > .lista-candidaturas .vaga-card {
            position: relative;
            min-width: 0;
            padding: 27px 26px 25px;
            overflow: hidden;
            background: #fff;
            border: 1px solid #e9ddfb;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(50, 16, 92, 0.07);
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }

        .conteudo > .lista-candidaturas .vaga-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #8b50e8, #c4a5ff);
        }

        .conteudo > .lista-candidaturas .vaga-card:hover {
            transform: translateY(-3px);
            border-color: #d5bdfb;
            box-shadow: 0 15px 35px rgba(50, 16, 92, 0.11);
        }

        .vaga-card h2 {
            margin: 0 0 12px;
            color: #351b57;
            font-size: clamp(18px, 2vw, 22px);
            font-weight: 800;
            line-height: 1.45;
        }

        .vaga-card h2 a {
            color: inherit;
            text-decoration: none;
            transition: color .2s ease;
        }

        .vaga-card h2 a:hover {
            color: #7138cf;
        }

        .vaga-card p {
            margin: 10px 0 0;
            color: #71647f;
            font-size: 14px;
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .vaga-card .empresa-candidatura {
            color: #7138cf;
            font-size: 14px;
            font-weight: 750;
        }

        .vaga-card .data-candidatura {
            color: #81758f;
        }

        .status-candidatura {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-top: 8px;
            padding: 8px 13px;
            border: 1px solid transparent;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 800;
        }

        .status-candidatura::before {
            content: "";
            width: 7px;
            height: 7px;
            flex: 0 0 7px;
            border-radius: 50%;
            background: currentColor;
        }

        .status-pendente {
            color: #986300;
            background: #fff6d9;
            border-color: #f4e2a6;
        }

        .status-aceito {
            color: #187548;
            background: #e6f8ee;
            border-color: #bce9ce;
        }

        .status-recusado {
            color: #b42332;
            background: #fff0f0;
            border-color: #f4caca;
        }

        .candidaturas-vazias {
            padding: 40px 24px;
            text-align: center;
            background: #fff;
            border: 1px dashed #d8c7f3;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(50, 16, 92, 0.05);
        }

        .candidaturas-vazias .vazio-icone {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            margin-bottom: 14px;
            border-radius: 17px;
            background: #f1eaff;
            color: #7138cf;
            font-size: 27px;
        }

        .candidaturas-vazias h2 {
            margin: 0 0 9px;
            color: #351b57;
            font-size: 20px;
        }

        .candidaturas-vazias p {
            margin: 0 0 18px;
            color: #766b86;
            line-height: 1.7;
        }

        .botao-explorar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 13px 21px;
            border-radius: 12px;
            background: linear-gradient(135deg, #8b50e8, #6428bd);
            color: #fff;
            font-weight: 800;
            text-decoration: none;
            box-shadow: 0 6px 16px rgba(100, 40, 189, 0.2);
            transition: filter .2s ease, transform .2s ease;
        }

        .botao-explorar:hover {
            filter: brightness(1.08);
            transform: translateY(-2px);
        }

        @media (max-width: 700px) {
            .conteudo > .lista-candidaturas .vaga-card {
                padding: 23px 18px;
                border-radius: 16px;
            }

            .resumo-candidaturas {
                gap: 8px;
            }

            .resumo-item {
                padding: 9px 12px;
            }

            .vaga-card h2 {
                font-size: 18px;
            }

            .vaga-card p {
                font-size: 13px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .vaga-card,
            .vaga-card h2 a,
            .botao-explorar {
                transition: none;
            }

            .conteudo > .lista-candidaturas .vaga-card:hover,
            .botao-explorar:hover {
                transform: none;
            }
        }
    </style>
</head>

<body>
<div class="layout">
    <?php require __DIR__ . '/includes/menu.php'; ?>

    <main class="conteudo">
        <header class="cabecalho-candidaturas">
            <h1>Minhas candidaturas</h1>
            <p>
                Acompanhe suas oportunidades e veja as atualizações
                das empresas sobre suas candidaturas.
            </p>

            <?php if ($rows): ?>
                <?php
                $total = count($rows);
                $pendentes = 0;
                $aceitas = 0;
                $recusadas = 0;

                foreach ($rows as $item) {
                    $statusAtual = classeStatus((string) ($item['status'] ?? ''));

                    if ($statusAtual === 'status-aceito') {
                        $aceitas++;
                    } elseif ($statusAtual === 'status-recusado') {
                        $recusadas++;
                    } else {
                        $pendentes++;
                    }
                }
                ?>

                <div class="resumo-candidaturas">
                    <span class="resumo-item">Total: <?= $total ?></span>
                    <span class="resumo-item">Pendentes: <?= $pendentes ?></span>
                    <span class="resumo-item">Aceitas: <?= $aceitas ?></span>
                    <span class="resumo-item">Recusadas: <?= $recusadas ?></span>
                </div>
            <?php endif; ?>
        </header>

        <?php if (!$rows): ?>
            <section class="candidaturas-vazias">
                <span class="vazio-icone" aria-hidden="true">✧</span>
                <h2>Suas oportunidades começam aqui!</h2>
                <p>
                    Você ainda não se candidatou a nenhuma vaga.
                    Explore as oportunidades disponíveis e dê o próximo passo.
                </p>
                <a href="vagas.php" class="botao-explorar">
                    Explorar vagas <span aria-hidden="true">→</span>
                </a>
            </section>
        <?php else: ?>
            <section class="lista-candidaturas" aria-label="Lista de candidaturas">
                <?php foreach ($rows as $r): ?>
                    <?php
                    $status = (string) ($r['status'] ?? '');
                    $classe = classeStatus($status);
                    ?>

                    <article class="vaga-card">
                        <h2>
                            <a href="vaga.php?id=<?= (int) $r['vaga_id'] ?>">
                                <?= e($r['titulo']) ?>
                            </a>
                        </h2>

                        <p class="empresa-candidatura">
                            <span aria-hidden="true">✦</span>
                            <?= e($r['nome_empresa']) ?>
                        </p>

                        <p class="data-candidatura">
                            <strong>Enviada em:</strong>
                            <?= e(sw_data($r['data_candidatura'], true)) ?>
                        </p>

                        <p>
                            <strong>Status:</strong>
                            <span class="status-candidatura <?= e($classe) ?>">
                                <?= e(sw_rotulo($status)) ?>
                            </span>
                        </p>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
</div>

<script src="js/menu.js"></script>
</body>
</html>
