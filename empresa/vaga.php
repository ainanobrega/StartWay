<?php
require_once __DIR__ . '/../includes/csrf.php';
if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") { csrf_validar(); }
require_once __DIR__ . '/../includes/bootstrap.php';


if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit;
}
if (($_SESSION["tipo"] ?? "") === "empresa") {
    header("Location: painel.php");
    exit;
}

$mensagemCandidatura = "";
$tipoMensagemCandidatura = "";

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Vaga não encontrada.");
}

$id_vaga = (int) $_GET["id"];

$sql = "SELECT
            vagas.id,
            vagas.titulo,
            vagas.descricao,
            vagas.tipo,
            vagas.modalidade,
            vagas.cidade,
            vagas.salario,
            vagas.status,
            empresas.nome_empresa
        FROM vagas
        INNER JOIN empresas
            ON vagas.empresa_id = empresas.id
        WHERE vagas.id = ?";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $id_vaga);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows == 0) {
    die("Essa vaga não existe.");
}

$vaga = $resultado->fetch_assoc();

// Processa a candidatura apenas de contas de jovens e para vagas abertas.
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["candidatar"])) {
    if (($_SESSION["tipo"] ?? "") !== "jovem") {
        $mensagemCandidatura = "Entre com uma conta de jovem para se candidatar a esta vaga.";
        $tipoMensagemCandidatura = "erro";
    } elseif ($vaga["status"] !== "aberta") {
        $mensagemCandidatura = "Esta vaga não está mais aberta para candidaturas.";
        $tipoMensagemCandidatura = "erro";
    } else {
        $idUsuario = (int) $_SESSION["id_usuario"];
        $stmtJovem = $conexao->prepare("SELECT id FROM jovens WHERE usuario_id = ? LIMIT 1");
        $stmtJovem->bind_param("i", $idUsuario);
        $stmtJovem->execute();
        $resJovem = $stmtJovem->get_result();
        $jovem = $resJovem->fetch_assoc();
        $stmtJovem->close();

        if (!$jovem) {
            $mensagemCandidatura = "Não foi possível localizar seu perfil de jovem. Confira seu cadastro.";
            $tipoMensagemCandidatura = "erro";
        } else {
            $idJovem = (int) $jovem["id"];
            $stmtExiste = $conexao->prepare("SELECT id FROM candidaturas WHERE vaga_id = ? AND jovem_id = ? LIMIT 1");
            $stmtExiste->bind_param("ii", $id_vaga, $idJovem);
            $stmtExiste->execute();
            $jaCandidatou = $stmtExiste->get_result()->num_rows > 0;
            $stmtExiste->close();

            if ($jaCandidatou) {
                $mensagemCandidatura = "Você já se candidatou a esta vaga.";
                $tipoMensagemCandidatura = "aviso";
            } else {
                $statusCandidatura = "Em análise";
                $stmtInsere = $conexao->prepare("INSERT INTO candidaturas (vaga_id, jovem_id, status, data_candidatura) VALUES (?, ?, ?, NOW())");
                $stmtInsere->bind_param("iis", $id_vaga, $idJovem, $statusCandidatura);
                if ($stmtInsere->execute()) {
                    $mensagemCandidatura = "Candidatura enviada com sucesso! A empresa poderá ver seu perfil na área de candidatos.";
                    $tipoMensagemCandidatura = "sucesso";
                } else {
                    error_log("Erro ao registrar candidatura: " . $stmtInsere->error);
                    $mensagemCandidatura = "Não foi possível enviar sua candidatura agora. Tente novamente.";
                    $tipoMensagemCandidatura = "erro";
                }
                $stmtInsere->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da vaga - StartWay</title>
    <link rel="stylesheet" href="../css/base.css">
    <link rel="stylesheet" href="../css/vagas.css">
    <style>
      .detalhe-vaga-wrap{width:min(900px,92%);margin:38px auto 60px}.detalhe-voltar{display:inline-flex;margin-bottom:20px;color:#6d28d9;font-weight:800}.detalhe-vaga{padding:clamp(24px,5vw,48px);border:1px solid #e9defa;border-radius:26px;background:#fff;box-shadow:0 18px 55px rgba(46,19,89,.10)}.detalhe-selo{display:inline-block;padding:7px 12px;border-radius:999px;background:#f2eaff;color:#6d28d9;font-size:12px;font-weight:800}.detalhe-vaga h1{margin:18px 0 8px;font-size:clamp(28px,4vw,42px);color:#2d174c}.detalhe-empresa{margin-bottom:26px;color:#756587;font-size:16px}.detalhe-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:13px;margin:24px 0}.detalhe-info{padding:16px;border:1px solid #eee6f8;border-radius:14px;background:#fbf9ff}.detalhe-info strong{display:block;margin-bottom:5px;color:#766b86;font-size:12px}.detalhe-info span{font-weight:750;color:#34204f}.detalhe-descricao{margin-top:28px;line-height:1.9;color:#514660;overflow-wrap:anywhere}.detalhe-descricao h2{margin-bottom:12px;color:#2d174c}.detalhe-nota{margin-top:26px;padding:16px;border-radius:14px;background:#f4efff;color:#5a3b83;line-height:1.7}.mensagem-candidatura{margin:20px 0 0;padding:14px 16px;border-radius:12px;line-height:1.6;font-weight:700}.mensagem-candidatura.sucesso{background:#e8f8ef;color:#176b43}.mensagem-candidatura.erro{background:#fff0f0;color:#a12626}.mensagem-candidatura.aviso{background:#fff8e6;color:#805b13}.detalhe-form-candidatura{display:inline-block;margin:24px 10px 0 0}.detalhe-candidatar{border:0;cursor:pointer;display:inline-flex;padding:13px 18px;border-radius:12px;background:linear-gradient(135deg,#8b5cf6,#6d28d9);color:white;font:inherit;font-weight:800;box-shadow:0 8px 18px #6d28d92b}.detalhe-candidatar:hover{filter:brightness(1.05);transform:translateY(-1px)}.detalhe-acao{display:inline-flex;margin-top:24px;padding:13px 18px;border-radius:12px;background:linear-gradient(135deg,#8b5cf6,#6d28d9);color:white;font-weight:800}
      </style>
</head>
<body>
<main class="detalhe-vaga-wrap">
    <a class="detalhe-voltar" href="../vagas.php">← Voltar para as vagas</a>
    <article class="detalhe-vaga">
        <span class="detalhe-selo">OPORTUNIDADE PROFISSIONAL</span>
        <h1><?= htmlspecialchars($vaga["titulo"], ENT_QUOTES, "UTF-8") ?></h1>
        <p class="detalhe-empresa"><?= htmlspecialchars($vaga["nome_empresa"], ENT_QUOTES, "UTF-8") ?></p>
        <div class="detalhe-grid">
            <div class="detalhe-info"><strong>Tipo de oportunidade</strong><span><?= htmlspecialchars(ucwords(str_replace("_", " ", $vaga["tipo"])), ENT_QUOTES, "UTF-8") ?></span></div>
            <div class="detalhe-info"><strong>Modalidade</strong><span><?= htmlspecialchars(ucfirst($vaga["modalidade"]), ENT_QUOTES, "UTF-8") ?></span></div>
            <div class="detalhe-info"><strong>Localização</strong><span><?= htmlspecialchars($vaga["cidade"] ?: "Não informada", ENT_QUOTES, "UTF-8") ?></span></div>
            <div class="detalhe-info"><strong>Remuneração</strong><span><?php if ($vaga["salario"] !== null && $vaga["salario"] !== ""): ?>R$ <?= number_format((float)$vaga["salario"], 2, ",", ".") ?><?php else: ?>A combinar<?php endif; ?></span></div>
        </div>
        <section class="detalhe-descricao"><h2>Sobre a oportunidade</h2><p><?= nl2br(htmlspecialchars($vaga["descricao"], ENT_QUOTES, "UTF-8")) ?></p></section>
        <?php if ($mensagemCandidatura !== ""): ?>
            <div class="mensagem-candidatura <?= htmlspecialchars($tipoMensagemCandidatura, ENT_QUOTES, "UTF-8") ?>" role="status"><?= htmlspecialchars($mensagemCandidatura, ENT_QUOTES, "UTF-8") ?></div>
        <?php endif; ?>
        <?php if ($vaga["status"] === "aberta"): ?>
            <div class="detalhe-nota">Esta vaga está aberta. Envie sua candidatura para que a empresa possa visualizar seu perfil na área de candidatos.</div>
            <?php if (($_SESSION["tipo"] ?? "") === "jovem"): ?>
                <form method="post" class="detalhe-form-candidatura"><?php csrf_campo(); ?>
                    <button class="detalhe-candidatar" type="submit" name="candidatar" value="1">Candidatar-se à vaga <span aria-hidden="true"> →</span></button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <div class="detalhe-nota">Esta oportunidade não está mais aberta para candidaturas.</div>
        <?php endif; ?>
        <a class="detalhe-acao" href="../vagas.php">Explorar outras vagas <span aria-hidden="true">→</span></a>
    </article>
</main>
</body>
</html>
