<?php
require_once __DIR__ . '/includes/csrf.php';
if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") { csrf_validar(); }
require_once __DIR__ . '/includes/bootstrap.php';

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

include "conexao.php";

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: cursos.php");
    exit;
}

$id = (int) $_GET["id"];

$sql = "SELECT id, titulo, descricao, plataforma, carga_horaria, link
        FROM cursos
        WHERE id = ?";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();
$curso = $resultado->fetch_assoc();

$stmt->close();

if (!$curso) {
    header("Location: cursos.php");
    exit;
}


/* Identifica o jovem conectado */
$sqlJovem = "SELECT id FROM jovens WHERE usuario_id = ?";
$stmtJovem = $conexao->prepare($sqlJovem);
$stmtJovem->bind_param("i", $_SESSION["id_usuario"]);
$stmtJovem->execute();

$resultadoJovem = $stmtJovem->get_result();
$jovem = $resultadoJovem->fetch_assoc();
$stmtJovem->close();

if (!$jovem) {
    exit("Perfil de jovem não encontrado.");
}

$jovemId = (int) $jovem["id"];
$cursoId = (int) $curso["id"];

/* Registra o curso caso ainda não exista */
$sqlRegistro = "
    INSERT INTO jovem_cursos (jovem_id, curso_id, progresso, concluido)
    VALUES (?, ?, 0, 0)
    ON DUPLICATE KEY UPDATE jovem_id = VALUES(jovem_id)
";

$stmtRegistro = $conexao->prepare($sqlRegistro);
$stmtRegistro->bind_param("ii", $jovemId, $cursoId);
$stmtRegistro->execute();
$stmtRegistro->close();


function escapar($texto) {
    return htmlspecialchars((string) $texto, ENT_QUOTES, "UTF-8");
}

$videoId = null;
$link = trim($curso["link"] ?? "");

if ($link !== "") {
    $partes = parse_url($link);
    $host = strtolower($partes["host"] ?? "");
    $caminho = $partes["path"] ?? "";

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
        !preg_match('/^[a-zA-Z0-9_-]{11}$/', $videoId)
    ) {
        $videoId = null;
    }
}

$buscaYoutube = "https://www.youtube.com/results?search_query="
    . urlencode($curso["titulo"]);


if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["concluir_curso"])
) {
    $sqlConcluir = "
        UPDATE jovem_cursos
        SET progresso = 100,
            concluido = 1,
            data_conclusao = CURDATE()
        WHERE jovem_id = ? AND curso_id = ?
    ";

    $stmtConcluir = $conexao->prepare($sqlConcluir);
    $stmtConcluir->bind_param("ii", $jovemId, $cursoId);
    $stmtConcluir->execute();
    $stmtConcluir->close();

    header("Location: curso.php?id=" . $cursoId);
    exit;
}
?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo escapar($curso["titulo"]); ?> | StartWay</title>

    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/menu.css">
    <link rel="stylesheet" href="css/cursos.css">
    <link rel="stylesheet" href="css/layout.css">
</head>

<body>

<?php
$paginaAtual = "cursos.php";
$baseMenu = "";
?>
<div class="layout">
    <?php require __DIR__ . "/includes/menu.php"; ?>

    <main class="conteudo">

        <section class="pagina-curso">

            <a href="cursos.php" class="voltar-cursos">
                ← Voltar para os cursos
            </a>

            <header class="curso-cabecalho">
                <span class="cursos-etiqueta">SUA JORNADA DE APRENDIZADO</span>

                <h1><?php echo escapar($curso["titulo"]); ?></h1>

                <p><?php echo escapar($curso["descricao"]); ?></p>
            </header>

            <?php if ($videoId !== null): ?>

                <div class="video-container">
                    <iframe
                        src="https://www.youtube.com/embed/<?php echo escapar($videoId); ?>"
                        title="<?php echo escapar($curso["titulo"]); ?>"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        referrerpolicy="strict-origin-when-cross-origin"
                        allowfullscreen>
                    </iframe>
                </div>

                <form method="POST"><?php csrf_campo(); ?>
                    <button type="submit" name="concluir_curso">
                        Marcar curso como concluído
                    </button>
                </form>

            <?php else: ?>

                <div class="aviso-video">
                    <h2>Vídeo ainda não configurado</h2>

                    <p>
                        Este curso ainda não possui um endereço direto
                        de vídeo. Você pode procurar aulas relacionadas
                        no YouTube.
                    </p>

                    <a
                        class="botao-curso"
                        href="<?php echo escapar($buscaYoutube); ?>"
                        target="_blank"
                        rel="noopener noreferrer">
                        Procurar aulas no YouTube →
                    </a>
                </div>

            <?php endif; ?>

            <div class="curso-detalhes">

                <?php if (!empty($curso["plataforma"])): ?>
                    <div class="curso-detalhe">
                        <span>Plataforma</span>
                        <strong><?php echo escapar($curso["plataforma"]); ?></strong>
                    </div>
                <?php endif; ?>

                <?php
                if (
                    $curso["carga_horaria"] !== null &&
                    $curso["carga_horaria"] !== ""
                ):
                ?>
                    <div class="curso-detalhe">
                        <span>Carga horária</span>
                        <strong>
                            <?php echo escapar($curso["carga_horaria"]); ?> horas
                        </strong>
                    </div>
                <?php endif; ?>

            </div>

        </section>

    </main>

</div>

<script>
const menu = document.getElementById("menuLateral");
const fundo = document.getElementById("fundoMenu");
const abrir = document.getElementById("abrirMenu");
const fechar = document.getElementById("fecharMenu");

function abrirMenu() {
    menu.classList.add("aberto");
    fundo.classList.add("aberto");
    abrir.setAttribute("aria-expanded", "true");
}

function fecharMenu() {
    menu.classList.remove("aberto");
    fundo.classList.remove("aberto");
    abrir.setAttribute("aria-expanded", "false");
}

abrir.addEventListener("click", abrirMenu);
fechar.addEventListener("click", fecharMenu);
fundo.addEventListener("click", fecharMenu);

document.addEventListener("keydown", function(evento) {
    if (evento.key === "Escape") {
        fecharMenu();
    }
});
</script>

<script src="js/menu.js"></script>
</body>
</html>
