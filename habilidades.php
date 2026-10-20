<?php
require_once __DIR__ . '/includes/csrf.php';
if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") { csrf_validar(); }
require_once __DIR__ . '/includes/bootstrap.php';


if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

if (isset($_SESSION["tipo"]) && $_SESSION["tipo"] !== "jovem") {
    header("Location: home.php");
    exit;
}

// Usado pelo includes/menu.php para destacar a aba atual.
$paginaAtual = "habilidades.php";
$baseMenu = "";

$idUsuario = (int) $_SESSION["id_usuario"];
$mensagem = "";
$tipoMensagem = "sucesso";
$jovem = null;
$habilidadesCadastradas = null;

// Localiza o perfil de jovem ligado à conta que entrou no sistema.
$sqlJovem = "SELECT id FROM jovens WHERE usuario_id = ?";
$stmtJovem = $conexao->prepare($sqlJovem);
$stmtJovem->bind_param("i", $idUsuario);
$stmtJovem->execute();
$resultadoJovem = $stmtJovem->get_result();
$jovem = $resultadoJovem->fetch_assoc();
$stmtJovem->close();

if (!$jovem) {
    $mensagem = "Seu perfil de jovem ainda não foi criado. Não foi possível salvar as habilidades.";
    $tipoMensagem = "erro";
} else {
    $idJovem = (int) $jovem["id"];

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $acao = $_POST["acao"] ?? "";

        if ($acao === "adicionar") {
            $nomeHabilidade = trim($_POST["nome_habilidade"] ?? "");
            $nivel = $_POST["nivel"] ?? "basico";
            $niveisValidos = ["basico", "intermediario", "avancado"];

            if ($nomeHabilidade === "" || mb_strlen($nomeHabilidade) > 100) {
                $mensagem = "Digite uma habilidade com até 100 caracteres.";
                $tipoMensagem = "erro";
            } elseif (!in_array($nivel, $niveisValidos, true)) {
                $mensagem = "Selecione um nível válido.";
                $tipoMensagem = "erro";
            } else {
                // Reutiliza a habilidade do catálogo se ela já existir.
                $sql = "SELECT id FROM habilidades WHERE nome = ?";
                $stmt = $conexao->prepare($sql);
                $stmt->bind_param("s", $nomeHabilidade);
                $stmt->execute();
                $habilidade = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($habilidade) {
                    $idHabilidade = (int) $habilidade["id"];
                } else {
                    $sql = "INSERT INTO habilidades (nome) VALUES (?)";
                    $stmt = $conexao->prepare($sql);
                    $stmt->bind_param("s", $nomeHabilidade);
                    $inseriu = $stmt->execute();
                    $idHabilidade = $inseriu ? (int) $conexao->insert_id : 0;
                    $stmt->close();

                    // Em caso de cadastro simultâneo com o mesmo nome, busca novamente.
                    if ($idHabilidade === 0) {
                        $sql = "SELECT id FROM habilidades WHERE nome = ?";
                        $stmt = $conexao->prepare($sql);
                        $stmt->bind_param("s", $nomeHabilidade);
                        $stmt->execute();
                        $habilidade = $stmt->get_result()->fetch_assoc();
                        $stmt->close();
                        if ($habilidade) {
                            $idHabilidade = (int) $habilidade["id"];
                        } else {
                            $mensagem = "Não foi possível cadastrar a habilidade.";
                            $tipoMensagem = "erro";
                        }
                    }
                }

                if ($mensagem === "") {
                    $sql = "SELECT habilidade_id FROM jovem_habilidades WHERE jovem_id = ? AND habilidade_id = ?";
                    $stmt = $conexao->prepare($sql);
                    $stmt->bind_param("ii", $idJovem, $idHabilidade);
                    $stmt->execute();
                    $jaExiste = $stmt->get_result()->num_rows > 0;
                    $stmt->close();

                    if ($jaExiste) {
                        $mensagem = "Você já cadastrou essa habilidade. Você pode alterar o nível na lista abaixo.";
                        $tipoMensagem = "erro";
                    } else {
                        $sql = "INSERT INTO jovem_habilidades (jovem_id, habilidade_id, nivel) VALUES (?, ?, ?)";
                        $stmt = $conexao->prepare($sql);
                        $stmt->bind_param("iis", $idJovem, $idHabilidade, $nivel);
                        if ($stmt->execute()) {
                            $mensagem = "Habilidade cadastrada com sucesso!";
                        } else {
                            $mensagem = "Não foi possível salvar a habilidade.";
                            $tipoMensagem = "erro";
                        }
                        $stmt->close();
                    }
                }
            }
        } elseif ($acao === "atualizar") {
            $idHabilidade = (int) ($_POST["habilidade_id"] ?? 0);
            $nivel = $_POST["nivel"] ?? "";
            $niveisValidos = ["basico", "intermediario", "avancado"];

            if ($idHabilidade <= 0 || !in_array($nivel, $niveisValidos, true)) {
                $mensagem = "Dados inválidos para atualizar a habilidade.";
                $tipoMensagem = "erro";
            } else {
                $sql = "UPDATE jovem_habilidades SET nivel = ? WHERE jovem_id = ? AND habilidade_id = ?";
                $stmt = $conexao->prepare($sql);
                $stmt->bind_param("sii", $nivel, $idJovem, $idHabilidade);
                if ($stmt->execute()) {
                    $mensagem = "Nível atualizado com sucesso!";
                } else {
                    $mensagem = "Não foi possível atualizar o nível.";
                    $tipoMensagem = "erro";
                }
                $stmt->close();
            }
        } elseif ($acao === "remover") {
            $idHabilidade = (int) ($_POST["habilidade_id"] ?? 0);
            if ($idHabilidade <= 0) {
                $mensagem = "Habilidade inválida.";
                $tipoMensagem = "erro";
            } else {
                $sql = "DELETE FROM jovem_habilidades WHERE jovem_id = ? AND habilidade_id = ?";
                $stmt = $conexao->prepare($sql);
                $stmt->bind_param("ii", $idJovem, $idHabilidade);
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    $mensagem = "Habilidade removida do seu perfil.";
                } else {
                    $mensagem = "Não foi possível remover a habilidade.";
                    $tipoMensagem = "erro";
                }
                $stmt->close();
            }
        }
    }

    // Atualiza a lista depois de qualquer operação.
    $sql = "SELECT h.id, h.nome, jh.nivel
            FROM jovem_habilidades jh
            INNER JOIN habilidades h ON h.id = jh.habilidade_id
            WHERE jh.jovem_id = ?
            ORDER BY h.nome ASC";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $idJovem);
    $stmt->execute();
    $habilidadesCadastradas = $stmt->get_result();
}

function e($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StartWay - Habilidades</title>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/menu.css">
    <link rel="stylesheet" href="css/habilidades.css">
</head>
<body>

<div class="layout">

    <!-- MENU LATERAL (botão ☰, fundo escuro e barra lateral) -->
    <?php require __DIR__ . "/includes/menu.php"; ?>

    <main class="conteudo">
        <div class="habilidades-pagina">

            <header class="habilidades-topo">
                <span class="hero-etiqueta">SEU DESENVOLVIMENTO PROFISSIONAL</span>
                <h1>Habilidades <span>&amp; competências</span></h1>
                <p>Mostre o que você sabe fazer, acompanhe seu desenvolvimento e fortaleça seu perfil profissional.</p>
            </header>

            <?php if ($mensagem !== ""): ?>
                <div class="mensagem-habilidade <?= e($tipoMensagem) ?>" role="status">
                    <?= e($mensagem) ?>
                </div>
            <?php endif; ?>

            <?php if (!$jovem): ?>
                <section class="habilidades-card">
                    <h2>Perfil não encontrado</h2>
                    <p>Não encontramos um perfil de jovem associado à sua conta. Verifique se o cadastro do perfil foi criado corretamente.</p>
                </section>
            <?php else: ?>
                <section class="habilidades-card">
                    <div class="card-titulo-linha">
                        <div>
                            <span class="secao-etiqueta">VAMOS COMEÇAR</span>
                            <h2>Adicionar habilidade</h2>
                        </div>
                        <span class="icone-card" aria-hidden="true">✦</span>
                    </div>

                    <form method="POST" class="habilidades-formulario"><?php csrf_campo(); ?>
                        <input type="hidden" name="acao" value="adicionar">

                        <div class="habilidades-campo">
                            <label for="nome_habilidade">Nome da habilidade</label>
                            <input type="text" id="nome_habilidade" name="nome_habilidade" maxlength="100" placeholder="Ex.: Python, comunicação..." required>
                        </div>

                        <div class="habilidades-campo">
                            <label for="nivel">Seu nível</label>
                            <select id="nivel" name="nivel" required>
                                <option value="basico">Básico</option>
                                <option value="intermediario">Intermediário</option>
                                <option value="avancado">Avançado</option>
                            </select>
                        </div>

                        <button type="submit" class="botao-habilidade">+ Adicionar</button>
                    </form>
                </section>

                <section class="habilidades-card">
                    <div class="card-titulo-linha">
                        <div>
                            <span class="secao-etiqueta">SEU PROGRESSO</span>
                            <h2>Minhas habilidades</h2>
                        </div>
                        <span class="contador-habilidades">
                            <?= $habilidadesCadastradas ? (int) $habilidadesCadastradas->num_rows : 0 ?> cadastrada(s)
                        </span>
                    </div>

                    <?php if ($habilidadesCadastradas && $habilidadesCadastradas->num_rows > 0): ?>
                        <div class="lista-habilidades">
                            <?php while ($habilidade = $habilidadesCadastradas->fetch_assoc()): ?>
                                <div class="habilidade-item">
                                    <div class="habilidade-nome">
                                        <span class="habilidade-simbolo" aria-hidden="true">✧</span>
                                        <strong><?= e($habilidade["nome"]) ?></strong>
                                    </div>

                                    <form method="POST" class="form-nivel"><?php csrf_campo(); ?>
                                        <input type="hidden" name="acao" value="atualizar">
                                        <input type="hidden" name="habilidade_id" value="<?= (int) $habilidade["id"] ?>">
                                        <label for="nivel_<?= (int) $habilidade["id"] ?>">Nível</label>
                                        <select id="nivel_<?= (int) $habilidade["id"] ?>" name="nivel" onchange="this.form.submit()">
                                            <option value="basico" <?= $habilidade["nivel"] === "basico" ? "selected" : "" ?>>Básico</option>
                                            <option value="intermediario" <?= $habilidade["nivel"] === "intermediario" ? "selected" : "" ?>>Intermediário</option>
                                            <option value="avancado" <?= $habilidade["nivel"] === "avancado" ? "selected" : "" ?>>Avançado</option>
                                        </select>
                                    </form>

                                    <form method="POST" class="acoes-habilidade" onsubmit="return confirm('Deseja remover esta habilidade do seu perfil?');"><?php csrf_campo(); ?>
                                        <input type="hidden" name="acao" value="remover">
                                        <input type="hidden" name="habilidade_id" value="<?= (int) $habilidade["id"] ?>">
                                        <button type="submit" class="botao-remover">Remover</button>
                                    </form>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="habilidades-vazio">
                            <span class="vazio-icone" aria-hidden="true">✧</span>
                            <h3>Sua jornada começa aqui</h3>
                            <p>Você ainda não cadastrou nenhuma habilidade. Use o formulário acima para adicionar a primeira!</p>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <footer class="habilidades-rodape">
                Cada habilidade é mais um passo em direção à sua próxima oportunidade. <span>✦</span>
            </footer>

        </div>
    </main>
</div>

<script src="js/menu.js"></script>
</body>
</html>