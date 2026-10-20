<?php
require_once __DIR__ . '/includes/csrf.php';
if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") { csrf_validar(); }
require_once __DIR__ . '/includes/bootstrap.php';

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

include "conexao.php";

// Usado pelo includes/menu.php para destacar a aba atual.
$paginaAtual = "cursos.php";
$baseMenu = "";

$sql = "SELECT id, titulo, descricao, plataforma, carga_horaria, link
        FROM cursos
        ORDER BY titulo ASC";

$resultado = $conexao->query($sql);

if (!$resultado) {
    die("Erro ao buscar cursos: " . $conexao->error);
}

function escapar($texto) {
    return htmlspecialchars((string) $texto, ENT_QUOTES, "UTF-8");
}

function obterIdYoutube($url) {
    $url = trim((string) $url);

    if ($url === "") {
        return null;
    }

    $partes = parse_url($url);

    if (!$partes) {
        return null;
    }

    $host = strtolower($partes["host"] ?? "");
    $caminho = $partes["path"] ?? "";
    $videoId = null;

    if (in_array($host, [
        "youtube.com",
        "www.youtube.com",
        "m.youtube.com"
    ], true)) {
        parse_str($partes["query"] ?? "", $parametros);

        if (!empty($parametros["v"])) {
            $videoId = $parametros["v"];
        } elseif (
            preg_match(
                '~^/(embed|shorts)/([a-zA-Z0-9_-]+)~',
                $caminho,
                $matches
            )
        ) {
            $videoId = $matches[2];
        }
    } elseif (in_array($host, [
        "youtu.be",
        "www.youtu.be"
    ], true)) {
        $videoId = trim($caminho, "/");
    }

    if (
        $videoId !== null &&
        preg_match('/^[a-zA-Z0-9_-]{11}$/', $videoId)
    ) {
        return $videoId;
    }

    return null;
}

$totalCursos = $resultado->num_rows;
$buscaInicial = trim($_GET["busca"] ?? "");
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cursos | StartWay</title>

    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/menu.css">
    <link rel="stylesheet" href="css/cursos.css">
    <link rel="stylesheet" href="css/cursos-filtros.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body class="pagina-biblioteca">

<div class="layout">

    <!-- MENU LATERAL (botão ☰, fundo escuro e barra lateral) -->
    <?php require __DIR__ . "/includes/menu.php"; ?>

    <main class="conteudo">
        <section class="biblioteca">

            <header class="biblioteca-hero">

                <div class="hero-texto">
                    <span class="hero-etiqueta">
                        ✦ ESPAÇO DE APRENDIZAGEM
                    </span>

                    <h1>
                        Seu próximo nível
                        <span>começa aqui.</span>
                    </h1>

                    <p>
                        Explore novas ideias, descubra habilidades
                        e construa o futuro que você deseja.
                    </p>

                    <a href="#catalogo" class="hero-botao">
                        Explorar aulas <span>↓</span>
                    </a>
                </div>

                <div class="hero-decoracao" aria-hidden="true">
                    <div class="decoracao-orbita orbita-um"></div>
                    <div class="decoracao-orbita orbita-dois"></div>

                    <img
                        src="img/logo.png"
                        alt=""
                        class="hero-logo">

                    <span class="estrela estrela-um">✧</span>
                    <span class="estrela estrela-dois">✦</span>
                    <span class="estrela estrela-tres">✧</span>

                    <span class="decoracao-ponto ponto-um"></span>
                    <span class="decoracao-ponto ponto-dois"></span>
                </div>

            </header>

            <section class="catalogo" id="catalogo">

                <div class="catalogo-cabecalho">
                    <div>
                        <span class="catalogo-etiqueta">
                            SUA BIBLIOTECA
                        </span>

                        <h2>Escolha o que aprender</h2>
                    </div>

                    <span class="catalogo-contagem" id="contagemCursos">
                        <?= $totalCursos; ?>
                        <?= $totalCursos === 1
                            ? "aula disponível"
                            : "aulas disponíveis"; ?>
                    </span>
                </div>

                <div class="filtros-cursos">

                    <label class="campo-busca-cursos">
                        <span aria-hidden="true">⌕</span>
                        <input
                            type="search"
                            id="buscarCurso"
                            placeholder="Buscar curso, tecnologia ou habilidade..."
                            aria-label="Buscar cursos"
                            value="<?= escapar($buscaInicial); ?>">
                    </label>

                    <label class="campo-categoria">
                        <span>Categoria</span>
                        <select id="filtroCategoria" aria-label="Filtrar por categoria">
                            <option value="todos">Todas as categorias</option>
                            <option value="python">Python</option>
                            <option value="java">Java</option>
                            <option value="html">HTML</option>
                            <option value="css">CSS</option>
                            <option value="php">PHP</option>
                            <option value="ingles">Inglês</option>
                            <option value="habilidades">Habilidades profissionais</option>
                        </select>
                    </label>

                    <button
                        type="button"
                        id="limparFiltros"
                        class="limpar-filtros">
                        Limpar filtros ↺
                    </button>

                </div>

                <p
                    id="mensagemFiltro"
                    class="mensagem-filtro"
                    aria-live="polite">
                </p>

                <?php if ($totalCursos > 0): ?>

                    <div class="grade-videos" id="gradeCursos">

                        <?php while ($curso = $resultado->fetch_assoc()): ?>

                            <?php
                            $videoId = obterIdYoutube($curso["link"] ?? "");
                            $titulo = $curso["titulo"] ?? "Curso";
                            $descricao = $curso["descricao"] ?? "";
                            $plataforma = $curso["plataforma"] ?? "";

                            $textoPesquisa = $titulo . " " . $descricao;
                            ?>

                            <article
                                class="curso-item"
                                data-titulo="<?= escapar($titulo); ?>"
                                data-descricao="<?= escapar($descricao); ?>"
                                data-plataforma="<?= escapar($plataforma); ?>"
                                data-pesquisa="<?= escapar($textoPesquisa); ?>">

                                <a
                                    class="capa-video"
                                    href="curso.php?id=<?= (int) $curso["id"]; ?>"
                                    aria-label="Assistir: <?= escapar($titulo); ?>">

                                    <?php if ($videoId !== null): ?>

                                        <img
                                            src="https://i.ytimg.com/vi/<?= escapar($videoId); ?>/hqdefault.jpg"
                                            alt="Capa do curso <?= escapar($titulo); ?>"
                                            loading="lazy">

                                    <?php else: ?>

                                        <div class="capa-sem-video">
                                            <span class="capa-simbolo">✦</span>
                                            <span class="capa-aviso">Aula</span>
                                        </div>

                                    <?php endif; ?>

                                    <span class="capa-sombra"></span>

                                    <span class="capa-play" aria-hidden="true">
                                        <span>▶</span>
                                    </span>

                                    <span class="capa-numero">STARTWAY CLASS</span>
                                </a>

                                <div class="curso-info">
                                    <h3><?= escapar($titulo); ?></h3>

                                    <p><?= escapar($descricao); ?></p>

                                    <?php if ($plataforma !== ""): ?>
                                        <span class="curso-plataforma">
                                            <?= escapar($plataforma); ?>
                                        </span>
                                    <?php endif; ?>

                                    <a
                                        class="curso-assistir"
                                        href="curso.php?id=<?= (int) $curso["id"]; ?>">
                                        Assistir aula <span>↗</span>
                                    </a>
                                </div>

                            </article>

                        <?php endwhile; ?>

                    </div>

                    <div class="biblioteca-vazia resultado-vazio" id="nenhumResultado" hidden>
                        <span>✧</span>
                        <h3>Nenhum curso encontrado</h3>
                        <p>
                            Tente outro termo ou escolha uma categoria diferente.
                        </p>
                        <button type="button" class="limpar-filtros" id="limparFiltrosVazio">
                            Mostrar todos os cursos
                        </button>
                    </div>

                <?php else: ?>

                    <div class="biblioteca-vazia">
                        <span>✧</span>
                        <h3>Novas aulas estão a caminho</h3>
                        <p>
                            Assim que os cursos forem cadastrados,
                            eles aparecerão aqui.
                        </p>
                    </div>

                <?php endif; ?>

            </section>

            <footer class="biblioteca-rodape">
                <span>STARTWAY</span>
                <p>Pequenos passos. Grandes possibilidades. ✦</p>
            </footer>

        </section>
    </main>

</div>

<script src="js/menu.js"></script>
<script>
/* Filtro dos cursos */
const campoBusca = document.getElementById("buscarCurso");
if (campoBusca && new URLSearchParams(window.location.search).has("busca")) { campoBusca.value = new URLSearchParams(window.location.search).get("busca") || ""; }
const filtroCategoria = document.getElementById("filtroCategoria");
const botaoLimpar = document.getElementById("limparFiltros");
const botaoLimparVazio = document.getElementById("limparFiltrosVazio");
const mensagemFiltro = document.getElementById("mensagemFiltro");
const contagemCursos = document.getElementById("contagemCursos");
const nenhumResultado = document.getElementById("nenhumResultado");
const cardsCursos = document.querySelectorAll(".curso-item");

function normalizarTexto(texto) {
    return texto
        .toLocaleLowerCase("pt-BR")
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "");
}

const categorias = {
    python: ["python"],
    java: ["java"],
    html: ["html"],
    css: ["css"],
    php: ["php"],
    ingles: ["ingles", "english"],
    habilidades: [
        "habilidades profissionais",
        "soft skills",
        "hard skills",
        "comunicacao",
        "lideranca",
        "carreira",
        "trabalho em equipe",
        "entrevista",
        "curriculo",
        "produtividade"
    ]
};

function aplicarFiltros() {
    const busca = normalizarTexto(campoBusca.value.trim());
    const categoria = filtroCategoria.value;
    let encontrados = 0;

    cardsCursos.forEach(function(card) {
        const titulo = normalizarTexto(card.dataset.titulo || "");
        const descricao = normalizarTexto(card.dataset.descricao || "");
        const plataforma = normalizarTexto(card.dataset.plataforma || "");
        const conteudo = titulo + " " + descricao + " " + plataforma;

        const correspondeBusca = conteudo.includes(busca);

        const palavras = categorias[categoria] || [];

        const correspondeCategoria =
            categoria === "todos" ||
            palavras.some(function(palavra) {
                return conteudo.includes(palavra);
            });

        const mostrar = correspondeBusca && correspondeCategoria;

        card.classList.toggle("oculto-filtro", !mostrar);

        if (mostrar) {
            encontrados++;
        }
    });

    const plural = encontrados === 1 ? "curso encontrado" : "cursos encontrados";

    mensagemFiltro.textContent = encontrados + " " + plural + ".";

    contagemCursos.textContent = encontrados +
        (encontrados === 1 ? " aula disponível" : " aulas disponíveis");

    if (nenhumResultado) {
        nenhumResultado.hidden = encontrados !== 0;
    }

    const grade = document.getElementById("gradeCursos");
    if (grade) {
        grade.hidden = encontrados === 0;
    }
}

function limparFiltros() {
    campoBusca.value = "";
    filtroCategoria.value = "todos";
    aplicarFiltros();
}

campoBusca.addEventListener("input", aplicarFiltros);
filtroCategoria.addEventListener("change", aplicarFiltros);
botaoLimpar.addEventListener("click", limparFiltros);

if (botaoLimparVazio) {
    botaoLimparVazio.addEventListener("click", limparFiltros);
}

aplicarFiltros();
</script>

</body>
</html>