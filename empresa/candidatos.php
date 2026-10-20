<?php

require_once __DIR__ . '/../includes/csrf.php';

if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") { csrf_validar(); }

require_once __DIR__ . '/../includes/bootstrap.php';

// Verifica se o usuário está logado

if (!isset($_SESSION["id_usuario"])) {

    header("Location: ../login.php");

    exit;

}

// Verifica se o usuário é uma empresa

if (($_SESSION["tipo"] ?? "") !== "empresa") {

    header("Location: ../home.php");

    exit;

}

$id_usuario = (int) $_SESSION["id_usuario"];

// Busca a empresa ligada à conta

$sql_empresa = "SELECT id FROM empresas WHERE usuario_id = ?";

$stmt_empresa = $conexao->prepare($sql_empresa);

$stmt_empresa->bind_param("i", $id_usuario);

$stmt_empresa->execute();

$resultado_empresa = $stmt_empresa->get_result();

$empresa = $resultado_empresa->fetch_assoc();

$stmt_empresa->close();

if (!$empresa) {

    die("Sua conta ainda não possui uma empresa cadastrada.");

}

$id_empresa = (int) $empresa["id"];

// Processa a decisão da empresa
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $idCandidatura = filter_input(
        INPUT_POST,
        "id_candidatura",
        FILTER_VALIDATE_INT
    );

    $acao = $_POST["acao"] ?? "";

    if (
        !$idCandidatura ||
        !in_array($acao, ["aceitar", "recusar"], true)
    ) {
        header("Location: candidatos.php");
        exit;
    }

    // Converte as ações para os valores aceitos pelo ENUM do banco
    $novoStatus = ($acao === "aceitar") ? "aprovada" : "rejeitada";

    /*
     * Busca o usuário jovem e o título da vaga ANTES de atualizar.
     * Esses dados serão usados somente para criar a notificação.
     */
    $dadosNotificacao = null;

    try {
        $sqlDados = "
            SELECT
                j.usuario_id AS usuario_jovem,
                v.titulo AS titulo_vaga
            FROM candidaturas AS c
            INNER JOIN vagas AS v ON v.id = c.vaga_id
            INNER JOIN jovens AS j ON j.id = c.jovem_id
            WHERE c.id = ?
              AND v.empresa_id = ?
            LIMIT 1
        ";

        $stmtDados = $conexao->prepare($sqlDados);
        $stmtDados->bind_param("ii", $idCandidatura, $id_empresa);
        $stmtDados->execute();
        $dadosNotificacao = $stmtDados->get_result()->fetch_assoc();
        $stmtDados->close();
    } catch (Throwable $erro) {
        // Falha ao buscar os dados não deve alterar a lógica da candidatura.
        error_log("StartWay - erro ao buscar dados para notificação: " . $erro->getMessage());
    }

    // Atualiza somente candidaturas de vagas desta empresa (lógica original)
    $sqlAtualizar = "
        UPDATE candidaturas AS c
        INNER JOIN vagas AS v ON v.id = c.vaga_id
        SET c.status = ?
        WHERE c.id = ?
          AND v.empresa_id = ?
    ";

    $stmtAtualizar = $conexao->prepare($sqlAtualizar);

    $stmtAtualizar->bind_param(
        "sii",
        $novoStatus,
        $idCandidatura,
        $id_empresa
    );

    $stmtAtualizar->execute();
    $atualizou = $stmtAtualizar->affected_rows;
    $stmtAtualizar->close();

    /*
     * NOVO: notifica o jovem somente se o status foi atualizado.
     * A página notificacoes.php usa estas colunas e tipos:
     * usuario_id, candidatura_id, mensagem, tipo, lida, criada_em.
     */
    if ($atualizou > 0 && $dadosNotificacao) {
        try {
            $usuarioJovem = (int) $dadosNotificacao["usuario_jovem"];
            $tituloVaga = $dadosNotificacao["titulo_vaga"];

            if ($acao === "aceitar") {
                $mensagem = 'Sua candidatura para a vaga "' .
                    $tituloVaga .
                    '" foi aprovada pela empresa.';
                $tipoNotificacao = "aprovacao";
            } else {
                $mensagem = 'Sua candidatura para a vaga "' .
                    $tituloVaga .
                    '" foi recusada pela empresa.';
                $tipoNotificacao = "recusa";
            }

            $sqlNotificacao = "
                INSERT INTO notificacoes
                    (usuario_id, candidatura_id, mensagem, tipo, lida, criada_em)
                VALUES (?, ?, ?, ?, 0, NOW())
            ";

            $stmtNotificacao = $conexao->prepare($sqlNotificacao);
            $stmtNotificacao->bind_param(
                "iiss",
                $usuarioJovem,
                $idCandidatura,
                $mensagem,
                $tipoNotificacao
            );
            $stmtNotificacao->execute();
            $stmtNotificacao->close();
        } catch (Throwable $erroNotificacao) {
            // Registra o erro sem impedir o redirecionamento dos candidatos.
            error_log("StartWay - erro ao criar notificação: " . $erroNotificacao->getMessage());
        }
    }

    // Evita reenviar o formulário ao atualizar a página (redirecionamento original)
    header("Location: candidatos.php?atualizado=1");
    exit;
}

// Busca as candidaturas às vagas desta empresa

$sql = "

    SELECT

        c.id,

        c.status,

        c.data_candidatura,

        v.titulo,

        u.nome AS nome_jovem,

        u.email AS email_jovem

    FROM candidaturas AS c

    INNER JOIN vagas AS v

        ON c.vaga_id = v.id

    INNER JOIN jovens AS j

        ON c.jovem_id = j.id

    INNER JOIN usuarios AS u

        ON j.usuario_id = u.id

    WHERE v.empresa_id = ?

    ORDER BY c.data_candidatura DESC

";

$stmt = $conexao->prepare($sql);

$stmt->bind_param("i", $id_empresa);

$stmt->execute();

$candidatos = $stmt->get_result();

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Candidatos - StartWay</title>

    <link rel="stylesheet" href="../css/base.css">

    <style>

        body {

            margin: 0;

            background:

                radial-gradient(circle at 90% 0, #e8ddff, transparent 28rem),

                #f8f6fc;

            color: #2b1b45;

            font-family: Inter, "Segoe UI", Arial, sans-serif;

        }

        .empresa-shell {

            width: min(1100px, 92%);

            margin: 34px auto;

        }

        .empresa-nav {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 16px;

            flex-wrap: wrap;

            padding: 17px 21px;

            border: 1px solid #eee5fb;

            border-radius: 17px;

            background: #fff;

            box-shadow: 0 8px 28px #32105c0d;

        }

        .empresa-brand {

            font-size: 22px;

            font-weight: 900;

            color: #6d28d9;

            text-decoration: none;

        }

        .empresa-links {

            display: flex;

            gap: 8px;

            flex-wrap: wrap;

        }

        .empresa-links a {

            padding: 9px 11px;

            border-radius: 10px;

            color: #5b4a73;

            font-weight: 700;

            text-decoration: none;

        }

        .empresa-links a:hover {

            background: #f1eaff;

            color: #6d28d9;

        }

        .empresa-title {

            margin: 30px 0 8px;

            font-size: clamp(27px, 4vw, 38px);

            color: #2d174c;

        }

        .empresa-subtitle {

            margin-bottom: 24px;

            color: #766b86;

            line-height: 1.7;

        }

        .empresa-list {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));

            gap: 17px;

        }

        .empresa-item {

            padding: 22px;

            border: 1px solid #ece3fa;

            border-radius: 18px;

            background: #fff;

            box-shadow: 0 10px 30px #32105c0b;

            overflow-wrap: anywhere;

        }

        .empresa-item h2 {

            margin-bottom: 13px;

            font-size: 20px;

            color: #3b235b;

        }

        .empresa-item p {

            margin: 9px 0;

            line-height: 1.65;

            color: #625574;

        }

        .status-badge {

            display: inline-block;

            padding: 5px 11px;

            border-radius: 20px;

            font-size: 13px;

            font-weight: 800;

        }

        .status-pendente {

            background: #fff3cd;

            color: #8a6100;

        }

        .status-aceito {

            background: #dcfce7;

            color: #166534;

        }

        .status-recusado {

            background: #fee2e2;

            color: #991b1b;

        }

        .status-entrevista {

            background: #e8ddff;

            color: #6428bd;

        }

        .acoes-candidatura {

            display: flex;

            gap: 10px;

            flex-wrap: wrap;

            margin-top: 16px;

        }

        .acoes-candidatura button {

            padding: 10px 16px;

            border: 0;

            border-radius: 10px;

            cursor: pointer;

            font: inherit;

            font-weight: 800;

            transition: filter 0.2s;

        }

        .btn-aceitar {

            background: #dcfce7;

            color: #166534;

        }

        .btn-recusar {

            background: #fee2e2;

            color: #991b1b;

        }

        .acoes-candidatura button:hover {

            filter: brightness(0.96);

        }

        .empresa-msg {

            padding: 14px 16px;

            margin: 20px 0;

            border-radius: 12px;

            font-weight: 700;

            background: #e8f8ef;

            color: #176b43;

        }

        .empresa-empty {

            padding: 32px;

            border: 1px dashed #d8c7f3;

            border-radius: 18px;

            background: #fff;

            color: #766b86;

        }

        @media (max-width: 600px) {

            .empresa-shell {

                margin: 18px auto;

            }

            .empresa-nav {

                align-items: flex-start;

            }

            .empresa-links {

                width: 100%;

            }

        }

    </style>

</head>

<body>

<div class="empresa-shell">

    <header class="empresa-nav">

        <a class="empresa-brand" href="painel.php">StartWay.</a>

        <nav class="empresa-links">

            <a href="painel.php">Painel</a>

            <a href="cadastrar_vagas.php">Cadastrar vaga</a>

            <a href="minhas_vagas.php">Minhas vagas</a>

            <a href="candidatos.php">Candidatos</a>

            <a href="../sair.php">Sair ↗</a>

        </nav>

    </header>

    <h1 class="empresa-title">Candidatos</h1>

    <p class="empresa-subtitle">

        Acompanhe os registros de interesse nas vagas da sua empresa.

    </p>

    <?php if (isset($_GET["atualizado"])): ?>

        <div class="empresa-msg" role="status">

            Status da candidatura atualizado com sucesso!

        </div>

    <?php endif; ?>

    <?php if ($candidatos->num_rows > 0): ?>

        <div class="empresa-list">

            <?php while ($candidato = $candidatos->fetch_assoc()): ?>

                <?php

                // Trata registros que estejam sem status

                $statusAtual = $candidato["status"] ?? "enviada";

                if ($statusAtual === "") {

                    $statusAtual = "enviada";

                }

                $nomesStatus = [

                    "enviada" => "Pendente",

                    "em_analise" => "Pendente",

                    "entrevista" => "Entrevista",

                    "aprovada" => "Aceito",

                    "rejeitada" => "Recusado"

                ];

                $textoStatus = $nomesStatus[$statusAtual] ?? "Pendente";

                $classeStatus = match ($statusAtual) {

                    "aprovada" => "status-aceito",

                    "rejeitada" => "status-recusado",

                    "entrevista" => "status-entrevista",

                    default => "status-pendente"

                };

                ?>

                <article class="empresa-item">

                    <h2>

                        Vaga:

                        <?= htmlspecialchars(

                            $candidato["titulo"],

                            ENT_QUOTES,

                            "UTF-8"

                        ) ?>

                    </h2>

                    <p>

                        <strong>Nome do candidato:</strong>

                        <?= htmlspecialchars(

                            $candidato["nome_jovem"] ?? "Nome não informado",

                            ENT_QUOTES,

                            "UTF-8"

                        ) ?>

                    </p>

                    <p>

                        <strong>E-mail:</strong>

                        <?= htmlspecialchars(

                            $candidato["email_jovem"] ?? "Não informado",

                            ENT_QUOTES,

                            "UTF-8"

                        ) ?>

                    </p>

                    <p>

                        <strong>Data da candidatura:</strong>

                        <?= htmlspecialchars(

                            $candidato["data_candidatura"] ?? "",

                            ENT_QUOTES,

                            "UTF-8"

                        ) ?>

                    </p>

                    <p>

                        <strong>Status:</strong>

                        <span class="status-badge <?= $classeStatus ?>">

                            <?= htmlspecialchars(

                                $textoStatus,

                                ENT_QUOTES,

                                "UTF-8"

                            ) ?>

                        </span>

                    </p>

                    <p>

                        <strong>Código da candidatura:</strong>

                        <?= (int) $candidato["id"] ?>

                    </p>

                    <?php

                    $podeDecidir = in_array(

                        $statusAtual,

                        ["enviada", "em_analise"],

                        true

                    );

                    ?>

                    <?php if ($podeDecidir): ?>

                        <form method="POST" class="acoes-candidatura"><?php csrf_campo(); ?>

                            <input

                                type="hidden"

                                name="id_candidatura"

                                value="<?= (int) $candidato["id"] ?>"

                            >

                            <button

                                type="submit"

                                name="acao"

                                value="aceitar"

                                class="btn-aceitar"

                            >

                                Aceitar

                            </button>

                            <button

                                type="submit"

                                name="acao"

                                value="recusar"

                                class="btn-recusar"

                            >

                                Recusar

                            </button>

                        </form>

                    <?php endif; ?>

                </article>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <div class="empresa-empty">

            Ainda não existem candidaturas para as vagas da sua empresa.

        </div>

    <?php endif; ?>

</div>

</body>

</html>
