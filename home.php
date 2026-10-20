<?php
require_once __DIR__ . '/includes/csrf.php';
if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") { csrf_validar(); }
require_once __DIR__ . '/includes/bootstrap.php';

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

$nome = htmlspecialchars($_SESSION["nome"] ?? "Jovem", ENT_QUOTES, "UTF-8");

$idUsuario = (int) $_SESSION["id_usuario"];

// Conta as notificações ainda não lidas do usuário conectado.
$stmtNotificacoes = $conexao->prepare(
    "SELECT COUNT(*) AS total
     FROM notificacoes
     WHERE usuario_id = ? AND lida = 0"
);

$stmtNotificacoes->bind_param("i", $idUsuario);
$stmtNotificacoes->execute();

$resultadoNotificacoes = $stmtNotificacoes->get_result();
$totalNotificacoes = (int) $resultadoNotificacoes->fetch_assoc()["total"];

$stmtNotificacoes->close();

/* Localiza o perfil do jovem */
$sqlJovem = "SELECT id FROM jovens WHERE usuario_id = ? LIMIT 1";
$stmtJovem = $conexao->prepare($sqlJovem);
$stmtJovem->bind_param("i", $idUsuario);
$stmtJovem->execute();
$resultadoJovem = $stmtJovem->get_result();
$jovem = $resultadoJovem->fetch_assoc();
$stmtJovem->close();

$cursoAtual = null;
$nomeCurso = "Explore novos cursos";
$progresso = 0;

/* Cursos reais cadastrados na plataforma para exibir no início */
$cursosDestaque = [];
$resultadoDestaque = $conexao->query("SELECT id, titulo, descricao, plataforma FROM cursos ORDER BY titulo ASC LIMIT 2");
if ($resultadoDestaque) {
    while ($linhaCurso = $resultadoDestaque->fetch_assoc()) {
        $cursosDestaque[] = $linhaCurso;
    }
}


if ($jovem) {
    $jovemId = (int) $jovem["id"];
    $sqlCurso = "
        SELECT c.id, c.titulo, jc.progresso
        FROM jovem_cursos jc
        INNER JOIN cursos c ON c.id = jc.curso_id
        WHERE jc.jovem_id = ? AND jc.concluido = 0
        ORDER BY jc.progresso DESC
        LIMIT 1
    ";
    $stmtCurso = $conexao->prepare($sqlCurso);
    $stmtCurso->bind_param("i", $jovemId);
    $stmtCurso->execute();
    $resultadoCurso = $stmtCurso->get_result();
    $cursoAtual = $resultadoCurso->fetch_assoc();
    $stmtCurso->close();

    if ($cursoAtual) {
        $nomeCurso = $cursoAtual["titulo"];
        $progresso = max(0, min(100, (int) $cursoAtual["progresso"]));
    }
}
?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="theme-color" content="#5527a5">
        <title>StartWay | Início</title>
        <link rel="stylesheet" href="css/base.css">
        <link rel="stylesheet" href="css/layout.css">
        <link rel="stylesheet" href="css/componentes.css">
        <link rel="stylesheet" href="css/menu.css">
        <link rel="stylesheet" href="css/home.css">
    </head>
    <body class="home-page">
    <div class="transicao-logo" id="transicaoLogo" aria-hidden="true"><img src="img/logo.png" alt=""><span>StartWay</span></div>
    <div class="layout">    
        <?php
        $paginaAtual = "home.php";
        $baseMenu = "";
        require __DIR__ . "/includes/menu.php";
        ?>

    <div class="conteudo">
       
<header class="cabecalho">

    <a href="home.php" class="marca-home">
        <img src="img/logo.png" alt="Logo StartWay">
        <span>StartWay</span>
    </a>

    <div class="acoes-cabecalho">

       
        <!-- Notificações -->
        <a
            href="notificacoes.php"
            class="botao-icone-cabecalho botao-notificacoes"
            aria-label="Notificações<?= $totalNotificacoes > 0 ? ', ' . $totalNotificacoes . ' não lidas' : '' ?>"
            title="Notificações"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>
                <path d="M10 21h4"/>
            </svg>

            <?php if ($totalNotificacoes > 0): ?>
                <span class="contador-notificacoes">
                    <?= $totalNotificacoes > 99 ? '99+' : $totalNotificacoes ?>
                </span>
            <?php endif; ?>
        </a>


        <!-- Meu perfil -->
        <a
            href="perfil.php"
            class="botao-icone-cabecalho"
            aria-label="Meu perfil"
            title="Meu perfil"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="8" r="4"/>
                <path d="M4 21a8 8 0 0 1 16 0"/>
            </svg>
        </a>

    </div>

    <!-- Busca -->
    <form action="cursos.php" method="GET" class="form-busca">
        <input
            type="search"
            name="busca"
            placeholder="Buscar cursos..."
            aria-label="Buscar cursos"
        >
        <button type="submit" aria-label="Pesquisar">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/>
                <path d="m16 16 5 5"/>
            </svg>
        </button>
    </form>

    <!-- Sair -->
    <a href="sair.php" class="link-sair">
        Sair ↗
    </a>

</header>


        <main>
            <section class="boas-vindas">
                <p class="texto-suave"><span class="ponto-boas-vindas"></span> SEU FUTURO COMEÇA AQUI</p>
                <h1>Olá, <?= $nome ?>! <span class="emoji-boas-vindas" aria-hidden="true">✦</span></h1>
                <p>Seu próximo passo começa com uma oportunidade. Continue desenvolvendo suas habilidades e construa o futuro que você quer.</p>
            </section>

            <div class="grade-home">
                <div class="coluna-principal">
                    <section class="destaque">
                        <span class="destaque-orbita orbita-um" aria-hidden="true"></span>
                        <span class="destaque-orbita orbita-dois" aria-hidden="true"></span>
                        <div class="conteudo-destaque">
                            <span class="etiqueta-destaque"><span aria-hidden="true">✦</span> SUA PRÓXIMA OPORTUNIDADE</span>
                            <h2 id="tituloDestaque">Seu talento merece espaço.</h2>
                            <p id="descricaoDestaque">Descubra estágios, explore novas áreas e transforme o que você sabe em novas possibilidades.</p>
                            <a href="vagas.php" class="botao-destaque">Explorar vagas <span aria-hidden="true">→</span></a>
                        </div>
                        <div class="ilustracao-destaque" aria-hidden="true">
                            <div class="anel anel-externo"></div><div class="anel anel-interno"></div>
                            <div class="cartao-flutuante cartao-flutuante-um">✦ <span>Novas possibilidades</span></div>
                            <div class="cartao-flutuante cartao-flutuante-dois"><span class="mini-ponto"></span> Seu próximo passo</div>
                            <div class="estrela-grande">✧</div>
                        </div>
                    </section>

                    <section class="secao-vagas">
                        <div class="cabecalho-secao">
                            <div><p class="texto-suave">OPORTUNIDADES PARA VOCÊ</p><h2>Vagas em destaque</h2></div>
                            <a href="vagas.php" class="link-ver-mais">Ver todas <span aria-hidden="true">→</span></a>
                        </div>
                        <div class="lista-vagas" id="listaVagas">
                            <div class="estado-carregamento"><span class="spinner-carregamento" aria-hidden="true"></span><p>Buscando oportunidades para você...</p></div>
                        </div>
                    </section>
                </div>

                <aside class="painel-direito">
                    <section class="bloco-painel painel-progresso">
                        <div class="cabecalho-painel">
                            <div><p class="texto-suave">SEU DESENVOLVIMENTO</p><h2>Meu progresso</h2></div>
                            <span class="icone-painel icone-progresso" aria-hidden="true">↗</span>
                        </div>
                        <?php if ($cursoAtual): ?>
                            <div class="rotulo-progresso"><span class="status-ativo"></span> CURSO EM ANDAMENTO</div>
                            <h3><?= htmlspecialchars($nomeCurso, ENT_QUOTES, "UTF-8") ?></h3>
                            <a href="curso.php?id=<?= (int) $cursoAtual["id"] ?>" class="link-painel">Continuar aprendendo <span aria-hidden="true">→</span></a>
                        <?php else: ?>
                            <div class="estado-sem-curso"><span class="icone-sem-curso" aria-hidden="true">✧</span><p>Todo caminho começa pelo primeiro passo. Escolha um curso e comece a evoluir.</p></div>
                            <a href="cursos.php" class="link-painel">Explorar cursos <span aria-hidden="true">→</span></a>
                        <?php endif; ?>
                    </section>

                    <section class="bloco-painel">
                        <div class="cabecalho-painel">
                            <div><p class="texto-suave">SEU PERFIL</p><h2>Minhas habilidades</h2></div>
                            <span class="icone-painel" aria-hidden="true">✦</span>
                        </div>
                        <p class="texto-painel">Pequenas habilidades constroem grandes oportunidades.</p>
                        <div class="lista-habilidades">
                            <span class="habilidade"><span aria-hidden="true">✧</span> Comunicação</span>
                            <span class="habilidade"><span aria-hidden="true">✧</span> Trabalho em equipe</span>
                            <span class="habilidade"><span aria-hidden="true">✧</span> Lógica</span>
                            <span class="habilidade"><span aria-hidden="true">✧</span> Criatividade</span>
                        </div>
                        <a href="habilidades.php" class="link-painel">Gerenciar habilidades <span aria-hidden="true">→</span></a>
                    </section>

                    <section class="bloco-painel painel-cursos">
                        <div class="cabecalho-painel">
                            <div><p class="texto-suave">APRENDA ALGO NOVO</p><h2>Cursos disponíveis</h2></div>
                            <span class="icone-painel icone-livro" aria-hidden="true">▤</span>
                        </div>
                        <?php if (!empty($cursosDestaque)): ?>
                            <?php foreach ($cursosDestaque as $indice => $cursoDestaque): ?>
                                <a href="curso.php?id=<?= (int) $cursoDestaque['id'] ?>" class="curso-recomendado">
                                    <span class="icone-curso <?= $indice % 2 ? 'icone-curso-dois' : '' ?>"><?= str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT) ?></span>
                                    <span class="informacao-recomendacao"><strong><?= htmlspecialchars($cursoDestaque['titulo'], ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars($cursoDestaque['plataforma'] ?: ($cursoDestaque['descricao'] ?: 'Comece a aprender agora'), ENT_QUOTES, 'UTF-8') ?></small></span>
                                    <span class="seta-curso" aria-hidden="true">↗</span>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="texto-painel">Os cursos aparecerão aqui assim que forem cadastrados.</p>
                        <?php endif; ?>
                        <a href="cursos.php" class="link-painel">Explorar todos os cursos <span aria-hidden="true">→</span></a>
                    </section>
                </aside>
            </div>
        </main>

        <footer class="rodape"><span>StartWay<span class="logo-ponto">.</span></span><span>Conectando talentos a oportunidades.</span><span>© <?= date("Y") ?> StartWay</span></footer>
    </div>
</div>
<script src="js/transicao.js"></script>
<script src="js/menu.js"></script>
<script src="js/home.js"></script>
</body>
</html>
