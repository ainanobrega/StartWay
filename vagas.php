<?php
require_once __DIR__ . '/includes/csrf.php';
if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") { csrf_validar(); }
require_once __DIR__ . '/includes/bootstrap.php';

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/conexao.php";

// Usado pelo includes/menu.php para destacar a aba atual.
$paginaAtual = "vagas.php";
$baseMenu = "";

// Busca as vagas abertas e os nomes das empresas.
$sql = "SELECT
            v.id,
            v.titulo,
            v.descricao,
            v.tipo,
            v.modalidade,
            v.cidade,
            v.salario,
            e.nome_empresa
        FROM vagas v
        INNER JOIN empresas e ON v.empresa_id = e.id
        WHERE v.status = 'aberta'
        ORDER BY v.criada_em DESC";

$resultado = $conexao->query($sql);

if (!$resultado) {
    error_log("Erro ao buscar vagas: " . $conexao->error);
    die("Não foi possível carregar as vagas no momento.");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>StartWay - Vagas disponíveis</title>

    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/menu.css">
    <link rel="stylesheet" href="css/vagas.css">
</head>

<body>

<div class="layout">

    <!-- MENU LATERAL (botão ☰, fundo escuro e barra lateral) -->
    <?php require __DIR__ . "/includes/menu.php"; ?>

    <!-- CONTEÚDO PRINCIPAL -->
    <main class="conteudo">

        <div class="vagas-pagina">

            <header class="vagas-topo">
                <span class="hero-etiqueta">
                    SUA PRÓXIMA OPORTUNIDADE
                </span>

                <h1>
                    Vagas <span>disponíveis</span>
                </h1>

                <p>
                    Descubra oportunidades, desenvolva seu potencial
                    e dê o próximo passo na sua carreira profissional.
                </p>
            </header>

            <section class="vagas-secao">

                <div class="vagas-secao-titulo">

                    <div>
                        <span class="secao-etiqueta">
                            OPORTUNIDADES
                        </span>

                        <h2>Encontre sua oportunidade</h2>

                        <p>
                            Confira as vagas abertas pelas empresas cadastradas.
                        </p>
                    </div>

                    <span class="vagas-contador">
                        <?= (int) $resultado->num_rows ?>

                        <?= $resultado->num_rows === 1
                            ? "vaga encontrada"
                            : "vagas encontradas" ?>
                    </span>

                </div>

                <?php if ($resultado->num_rows > 0): ?>

                    <div class="lista-vagas">

                        <?php while ($vaga = $resultado->fetch_assoc()): ?>

                            <article class="vaga-card">

                                <div class="vaga-card-topo">
                                    <span class="vaga-icone">✦</span>

                                    <span class="etiqueta">
                                        <?= htmlspecialchars(
                                            $vaga["tipo"] ?? "Oportunidade",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </span>
                                </div>

                                <h3>
                                    <?= htmlspecialchars(
                                        $vaga["titulo"] ?? "",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </h3>

                                <p class="empresa">
                                    <?= htmlspecialchars(
                                        $vaga["nome_empresa"] ?? "Empresa",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </p>

                                <p class="descricao">
                                    <?= nl2br(htmlspecialchars(
                                        $vaga["descricao"] ?? "Confira os detalhes desta oportunidade.",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    )) ?>
                                </p>

                                <div class="informacoes">

                                    <p>
                                        <strong>Modalidade</strong>

                                        <span>
                                            <?= htmlspecialchars(
                                                $vaga["modalidade"] ?? "Não informada",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </span>
                                    </p>

                                    <p>
                                        <strong>Localização</strong>

                                        <span>
                                            <?= htmlspecialchars(
                                                $vaga["cidade"] ?: "Não informada",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </span>
                                    </p>

                                    <p>
                                        <strong>Remuneração</strong>

                                        <span>
                                            <?php
                                            if (
                                                $vaga["salario"] !== null &&
                                                $vaga["salario"] !== ""
                                            ):
                                            ?>
                                                R$
                                                <?= number_format(
                                                    (float) $vaga["salario"],
                                                    2,
                                                    ",",
                                                    "."
                                                ) ?>
                                            <?php else: ?>
                                                A combinar
                                            <?php endif; ?>
                                        </span>
                                    </p>

                                </div>

                                <a
                                    href="empresa/vaga.php?id=<?= (int) $vaga["id"] ?>"
                                    class="botao-vaga"
                                >
                                    Ver detalhes
                                    <span aria-hidden="true">→</span>
                                </a>

                            </article>

                        <?php endwhile; ?>

                    </div>

                <?php else: ?>

                    <div class="vagas-vazio">
                        <span class="vazio-icone">✧</span>

                        <h3>
                            Nenhuma vaga disponível por enquanto
                        </h3>

                        <p>
                            Assim que as empresas publicarem novas
                            oportunidades, elas aparecerão aqui.
                            Continue acompanhando!
                        </p>
                    </div>

                <?php endif; ?>

            </section>

            <footer class="vagas-rodape">
                StartWay — Conectando talentos a oportunidades.
            </footer>

        </div>

    </main>

</div>

<script src="js/menu.js"></script>
</body>
</html>