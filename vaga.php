<?php

require_once __DIR__ . '/includes/bootstrap.php';

require_once __DIR__ . '/includes/csrf.php';

require_once __DIR__ . '/includes/formatadores.php';



exigirTipo('jovem');



$id = (int) ($_GET['id'] ?? 0);



if ($id < 1) {

    header('Location: vagas.php');

    exit;

}



// Busca os dados da vaga e da empresa

$st = $conexao->prepare("

    SELECT

        v.id,

        v.titulo,

        v.descricao,

        v.tipo,

        v.modalidade,

        v.cidade,

        v.salario,

        v.status,

        v.criada_em,

        e.nome_empresa

    FROM vagas v

    INNER JOIN empresas e ON e.id = v.empresa_id

    WHERE v.id = ?

    LIMIT 1

");



$st->bind_param('i', $id);

$st->execute();



$vaga = $st->get_result()->fetch_assoc();



$st->close();



if (!$vaga) {

    header('Location: erro.php?codigo=404');

    exit;

}



// Busca o cadastro do jovem

$st = $conexao->prepare(

    'SELECT id FROM jovens WHERE usuario_id = ?'

);



$st->bind_param('i', $_SESSION['id_usuario']);

$st->execute();



$j = $st->get_result()->fetch_assoc();



$st->close();



$jovemId = (int) ($j['id'] ?? 0);

$mensagem = '';

$tipoMensagem = 'info';



// Processa a candidatura

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_validar();



    if ($vaga['status'] !== 'aberta') {

        $mensagem = 'Esta vaga não está mais aberta.';

        $tipoMensagem = 'erro';



    } elseif (!$jovemId) {

        $mensagem = 'Não foi possível localizar seu perfil de jovem.';

        $tipoMensagem = 'erro';



    } else {

        try {

            $st = $conexao->prepare("

                INSERT INTO candidaturas

                    (vaga_id, jovem_id, status, data_candidatura)

                VALUES (?, ?, 'em_analise', NOW())

            ");



            $st->bind_param('ii', $id, $jovemId);

            $st->execute();

            $st->close();



            header('Location: vaga.php?id=' . $id . '&enviada=1');

            exit;



        } catch (mysqli_sql_exception $ex) {

            if ($ex->getCode() === 1062) {

                $mensagem = 'Você já se candidatou a esta vaga.';

                $tipoMensagem = 'info';

            } else {

                error_log('Candidatura StartWay: ' . $ex->getMessage());



                $mensagem = 'Não foi possível enviar sua candidatura agora.';

                $tipoMensagem = 'erro';

            }

        }

    }

}



// Verifica se o jovem já se candidatou

$st = $conexao->prepare("

    SELECT status

    FROM candidaturas

    WHERE vaga_id = ? AND jovem_id = ?

    LIMIT 1

");



$st->bind_param('ii', $id, $jovemId);

$st->execute();



$candidatura = $st->get_result()->fetch_assoc();



$st->close();



// Mensagem de sucesso após o redirecionamento

if (isset($_GET['enviada'])) {

    $mensagem = 'Sua candidatura foi enviada com sucesso!';

    $tipoMensagem = 'sucesso';

}

?>



<!DOCTYPE html>

<html lang="pt-BR">



<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">



    <title><?= e($vaga['titulo']) ?> | StartWay</title>



    <link rel="stylesheet" href="css/base.css">

    <link rel="stylesheet" href="css/layout.css">

    <link rel="stylesheet" href="css/menu.css">

    <link rel="stylesheet" href="css/vagas.css">



    <style>

        /* =====================================

           STARTWAY - DETALHES DA VAGA

        ===================================== */



        .conteudo {

            min-width: 0;

            padding-bottom: 45px;

            color: #2d174c;

        }



        .vaga-voltar {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            margin: 0 0 22px;

            color: #7138cf;

            font-size: 14px;

            font-weight: 800;

            text-decoration: none;

            transition: color .2s ease, transform .2s ease;

        }



        .vaga-voltar:hover {

            color: #5123a3;

            transform: translateX(-3px);

        }



        .vaga-detalhes {

            position: relative;

            overflow: hidden;

            padding: clamp(24px, 4vw, 48px);

            border: 1px solid #eadcfb;

            border-radius: 26px;

            background: #fff;

            box-shadow: 0 18px 50px rgba(50, 16, 92, .08);

        }



        .vaga-detalhes::before {

            content: "";

            position: absolute;

            inset: 0 0 auto;

            height: 5px;

            background: linear-gradient(90deg, #7138cf, #b58aff, #e5d5ff);

        }



        .vaga-topo {

            margin-bottom: 28px;

        }



        .vaga-etiqueta {

            display: inline-flex;

            align-items: center;

            margin-bottom: 17px;

            padding: 8px 14px;

            border: 1px solid #eadcff;

            border-radius: 30px;

            background: #f4ecff;

            color: #6d28d9;

            font-size: 11px;

            font-weight: 900;

            letter-spacing: .5px;

            text-transform: uppercase;

        }



        .vaga-titulo {

            margin: 0 0 12px;

            color: #2d174c;

            font-size: clamp(28px, 4vw, 43px);

            font-weight: 900;

            line-height: 1.2;

            letter-spacing: -1px;

            overflow-wrap: anywhere;

        }



        .vaga-empresa {

            display: flex;

            align-items: center;

            gap: 10px;

            margin: 0;

            color: #756486;

            font-size: 16px;

        }



        /\ Informações resumidas \/



        /* Informações da vaga sem caixas ou cartões */
        .vaga-detalhes .vaga-informacoes {
            display: grid !important;
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            gap: 24px !important;
            margin: 28px 0 34px !important;
            padding: 22px 0 !important;
            border-top: 1px solid #eee6f8 !important;
            border-bottom: 1px solid #eee6f8 !important;
            background: transparent !important;
        }

        .vaga-detalhes .vaga-info {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            min-width: 0;
            padding: 0 !important;
            border: 0 !important;
            border-radius: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
        }



        .vaga-detalhes .vaga-info-label {

            display: block;

            margin-bottom: 9px;

            color: #857594;

            font-size: 12px;

            font-weight: 600;

        }



        .vaga-detalhes .vaga-info-valor {

            display: block;

            color: #351b57;

            font-size: 15px;

            font-weight: 850;

            line-height: 1.5;

            overflow-wrap: anywhere;

        }



        /\ Descrição \/



        .vaga-descricao {

            margin-bottom: 28px;

        }



        .vaga-secao-titulo {

            margin: 0 0 15px;

            color: #351b57;

            font-size: 21px;

            font-weight: 850;

        }



        .vaga-descricao-texto {

            color: #685878;

            font-size: 15px;

            line-height: 1.9;

            overflow-wrap: anywhere;

        }



        /\ Status da vaga \/



        .vaga-status-linha {

            display: flex;

            align-items: center;

            flex-wrap: wrap;

            gap: 10px;

            margin: 25px 0;

            padding: 15px 17px;

            border: 1px solid #e9defa;

            border-radius: 14px;

            background: #faf7ff;

        }



        .vaga-status-linha strong {

            color: #51366d;

            font-size: 14px;

        }



        .vaga-status {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            padding: 7px 12px;

            border-radius: 30px;

            background: #e8f8ee;

            color: #197344;

            font-size: 12px;

            font-weight: 850;

        }



        .vaga-status::before {

            content: "";

            width: 7px;

            height: 7px;

            border-radius: 50%;

            background: currentColor;

        }



        .vaga-status.fechada {

            background: #fff0f0;

            color: #bd3333;

        }



        /\ Mensagens \/



        .vaga-alerta {

            margin: 20px 0;

            padding: 16px 18px;

            border: 1px solid #e5d7fa;

            border-radius: 14px;

            background: #f5efff;

            color: #58348a;

            font-size: 14px;

            line-height: 1.7;

        }



        .vaga-alerta.sucesso {

            border-color: #c7ecd5;

            background: #effbf3;

            color: #216b40;

        }



        .vaga-alerta.erro {

            border-color: #f4cccc;

            background: #fff3f3;

            color: #a72c36;

        }



        /\ Situação da candidatura \/



        .vaga-candidatura {

            margin-top: 24px;

            padding: 20px;

            border: 1px solid #e7dcf8;

            border-radius: 16px;

            background: #fcfaff;

        }



        .vaga-candidatura h2 {

            margin: 0 0 10px;

            color: #351b57;

            font-size: 18px;

        }



        .vaga-candidatura p {

            margin: 8px 0 0;

            color: #756486;

            font-size: 14px;

            line-height: 1.7;

        }



        .vaga-status-candidatura {

            display: inline-flex;

            margin-top: 7px;

            padding: 8px 13px;

            border: 1px solid #e4d5fb;

            border-radius: 30px;

            background: #f1eaff;

            color: #6428bd;

            font-size: 13px;

            font-weight: 850;

        }



        /\ Botões \/



        .vaga-acoes {

            display: flex;

            flex-wrap: wrap;

            gap: 12px;

            margin-top: 25px;

        }



        .vaga-botao {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            min-height: 48px;

            padding: 13px 20px;

            border: 0;

            border-radius: 12px;

            background: linear-gradient(135deg, #8b50e8, #6428bd);

            box-shadow: 0 7px 17px rgba(100, 40, 189, .18);

            color: #fff;

            font-family: inherit;

            font-size: 14px;

            font-weight: 850;

            text-align: center;

            text-decoration: none;

            cursor: pointer;

            transition: transform .2s ease, box-shadow .2s ease;

        }



        .vaga-botao:hover {

            transform: translateY(-2px);

            box-shadow: 0 11px 23px rgba(100, 40, 189, .25);

        }



        .vaga-botao.secundario {

            border: 1px solid #e4d5fb;

            background: #fff;

            box-shadow: none;

            color: #7138cf;

        }



        .vaga-botao:focus-visible,

        .vaga-voltar:focus-visible {

            outline: 3px solid #c4a5ff;

            outline-offset: 3px;

        }



        .vaga-botao:disabled {

            border: 1px solid #e4d5fb;

            background: #f1eaff;

            box-shadow: none;

            color: #67449b;

            cursor: not-allowed;

            transform: none;

        }



        /\ Responsividade \/



        .vaga-detalhes .vaga-topo {
            padding-bottom: 25px;
            border-bottom: 1px solid #f0e8fb;
        }

        .vaga-detalhes .vaga-descricao {
            padding: 4px 0 8px;
        }

        .vaga-detalhes .vaga-status-linha {
            justify-content: space-between;
        }

        @media (max-width: 1000px) {
            .vaga-detalhes .vaga-informacoes {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 22px 18px !important;
            }
        }



        @media (max-width: 600px) {

            .conteudo {

                padding-bottom: 25px;

            }



            .vaga-detalhes {

                padding: 25px 19px;

                border-radius: 19px;

            }



            .vaga-titulo {

                font-size: 29px;

                letter-spacing: -.5px;

            }



            .vaga-empresa {

                font-size: 14px;

            }



            .vaga-detalhes .vaga-informacoes {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 20px 14px !important;
                margin: 22px 0 27px !important;
                padding: 20px 0 !important;
            }



            .vaga-detalhes .vaga-info-valor {

                font-size: 13px;

            }



            .vaga-secao-titulo {

                font-size: 19px;

            }



            .vaga-descricao-texto {

                font-size: 14px;

            }



            .vaga-acoes {

                flex-direction: column;

            }



            .vaga-botao {

                width: 100%;

                box-sizing: border-box;

            }

        }



        @media (prefers-reduced-motion: reduce) {

            .vaga-botao,

            .vaga-voltar {

                transition: none;

            }



            .vaga-botao:hover,

            .vaga-voltar:hover {

                transform: none;

            }

        }

    </style>

</head>



<body>



<div class="layout">



    <?php

    $paginaAtual = 'vagas.php';

    $baseMenu = '';



    require __DIR__ . '/includes/menu.php';

    ?>



    <main class="conteudo">



        <a href="vagas.php" class="vaga-voltar">

            &larr; Voltar para as vagas

        </a>



        <section class="vaga-detalhes">



            <header class="vaga-topo">



                <span class="vaga-etiqueta">

                    <?= e(sw_rotulo($vaga['tipo'])) ?>

                </span>



                <h1 class="vaga-titulo">

                    <?= e($vaga['titulo']) ?>

                </h1>



                <p class="vaga-empresa">
                    <?= e($vaga['nome_empresa']) ?>

                </p>



            </header>



            <section class="vaga-informacoes" aria-label="Informações da vaga">



                <div class="vaga-info">

                    <span class="vaga-info-label">

                        Tipo de oportunidade

                    </span>



                    <span class="vaga-info-valor">

                        <?= e(sw_rotulo($vaga['tipo'])) ?>

                    </span>

                </div>



                <div class="vaga-info">

                    <span class="vaga-info-label">

                        Modalidade

                    </span>



                    <span class="vaga-info-valor">

                        <?= e(sw_rotulo($vaga['modalidade'])) ?>

                    </span>

                </div>



                <div class="vaga-info">

                    <span class="vaga-info-label">

                        Localização

                    </span>



                    <span class="vaga-info-valor">

                        <?= e($vaga['cidade'] ?: 'Não informada') ?>

                    </span>

                </div>



                <div class="vaga-info">

                    <span class="vaga-info-label">

                        Remuneração

                    </span>



                    <span class="vaga-info-valor">

                        <?php if ($vaga['salario'] !== null): ?>

                            R$ <?= number_format(

                                (float) $vaga['salario'],

                                2,

                                ',',

                                '.'

                            ) ?>

                        <?php else: ?>

                            A combinar

                        <?php endif; ?>

                    </span>

                </div>



            </section>



            <section class="vaga-descricao">



                <h2 class="vaga-secao-titulo">

                    Sobre a oportunidade

                </h2>



                <div class="vaga-descricao-texto">

                    <?= nl2br(e($vaga['descricao'])) ?>

                </div>



            </section>



            <div class="vaga-status-linha">



                <strong>Status da vaga:</strong>



                <span class="vaga-status <?= $vaga['status'] !== 'aberta' ? 'fechada' : '' ?>">

                    <?= e(sw_rotulo($vaga['status'])) ?>

                </span>



            </div>



            <?php if ($mensagem): ?>



                <div

                    class="vaga-alerta <?= e($tipoMensagem) ?>"

                    role="status"

                    aria-live="polite"

                >

                    <?= e($mensagem) ?>

                </div>



            <?php endif; ?>



            <?php if ($candidatura): ?>



                <section class="vaga-candidatura">



                    <h2>

                        Sua candidatura

                    </h2>



                    <p>

                        Você já enviou seu interesse nesta oportunidade.

                        Acompanhe abaixo a situação da sua candidatura.

                    </p>



                    <p>

                        <strong>Status atual:</strong>

                    </p>



                    <span class="vaga-status-candidatura">

                        <?= e(sw_rotulo($candidatura['status'])) ?>

                    </span>



                    <div class="vaga-acoes">



                        <button type="button" class="vaga-botao" disabled>

                            &#10003; Candidatura enviada

                        </button>



                        <a href="minhas_candidaturas.php" class="vaga-botao secundario">

                            Minhas candidaturas &rarr;

                        </a>



                    </div>



                </section>



            <?php elseif ($vaga['status'] === 'aberta'): ?>



                <div class="vaga-alerta">

                    Esta vaga está aberta. Envie sua candidatura para que

                    a empresa possa visualizar seu perfil na área de candidatos.

                </div>



                <div class="vaga-acoes">



                    <form method="POST">

                        <?php csrf_campo(); ?>



                        <button type="submit" class="vaga-botao">

                            Candidatar-se à vaga &rarr;

                        </button>

                    </form>



                    <a href="vagas.php" class="vaga-botao secundario">

                        Explorar outras vagas

                    </a>



                </div>



            <?php else: ?>



                <div class="vaga-alerta">

                    Esta oportunidade não está recebendo novas candidaturas

                    no momento. Você pode explorar outras vagas disponíveis.

                </div>



                <div class="vaga-acoes">



                    <a href="vagas.php" class="vaga-botao">

                        Explorar outras vagas &rarr;

                    </a>



                </div>



            <?php endif; ?>



        </section>



    </main>



</div>



<script src="js/menu.js"></script>



</body>

</html>
