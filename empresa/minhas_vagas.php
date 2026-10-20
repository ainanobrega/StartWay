<?php
require_once __DIR__ . '/../includes/csrf.php';
if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") { csrf_validar(); }
require_once __DIR__ . '/../includes/bootstrap.php';


// Verifica se o usuário está logado
if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit;
}

// Verifica se é uma empresa
if ($_SESSION["tipo"] != "empresa") {
    header("Location: ../home.php");
    exit;
}

// Busca o ID da empresa
$id_usuario = $_SESSION["id_usuario"];

$sql_empresa = "SELECT id FROM empresas WHERE usuario_id = ?";
$stmt_empresa = $conexao->prepare($sql_empresa);
$stmt_empresa->bind_param("i", $id_usuario);
$stmt_empresa->execute();

$resultado_empresa = $stmt_empresa->get_result();

if ($resultado_empresa->num_rows == 0) {
    die("Sua conta ainda não possui uma empresa cadastrada.");
}

$empresa = $resultado_empresa->fetch_assoc();
$id_empresa = $empresa["id"];

// Busca as vagas dessa empresa
$sql = "SELECT id, titulo, descricao, tipo, modalidade,
               cidade, salario, status, criada_em
        FROM vagas
        WHERE empresa_id = ?
        ORDER BY criada_em DESC";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $id_empresa);
$stmt->execute();

$vagas = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas vagas - StartWay</title>
    <link rel="stylesheet" href="../css/base.css">

<style>
body{margin:0;background:radial-gradient(circle at 90% 0,#e8ddff,transparent 28rem),#f8f6fc;color:#2b1b45;font-family:Inter,"Segoe UI",Arial,sans-serif}.empresa-shell{width:min(1100px,92%);margin:34px auto}.empresa-nav{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:17px 21px;border:1px solid #eee5fb;border-radius:17px;background:#fff;box-shadow:0 8px 28px #32105c0d}.empresa-brand{font-size:22px;font-weight:900;color:#6d28d9}.empresa-links{display:flex;gap:8px;flex-wrap:wrap}.empresa-links a{padding:9px 11px;border-radius:10px;color:#5b4a73;font-weight:700;text-decoration:none}.empresa-links a:hover{background:#f1eaff;color:#6d28d9}.empresa-title{margin:30px 0 8px;font-size:clamp(27px,4vw,38px);color:#2d174c}.empresa-subtitle{margin-bottom:24px;color:#766b86;line-height:1.7}.empresa-list{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:17px}.empresa-item{padding:22px;border:1px solid #ece3fa;border-radius:18px;background:#fff;box-shadow:0 10px 30px #32105c0b;overflow-wrap:anywhere}.empresa-item h2{margin-bottom:13px;font-size:20px;color:#3b235b}.empresa-item p{margin:9px 0;line-height:1.65;color:#625574}.empresa-btn{display:inline-flex;margin-top:12px;padding:11px 14px;border-radius:10px;background:#6d28d9;color:#fff;text-decoration:none;font-weight:800}.empresa-empty{padding:32px;border:1px dashed #d8c7f3;border-radius:18px;background:#fff;color:#766b86}.empresa-empty a{color:#6d28d9;font-weight:800}@media(max-width:600px){.empresa-shell{margin:18px auto}.empresa-nav{align-items:flex-start}.empresa-links{width:100%}}
</style>
</head>
<body>
<div class="empresa-shell">
<header class="empresa-nav"><a class="empresa-brand" href="painel.php">StartWay.</a><nav class="empresa-links"><a href="painel.php">Painel</a><a href="cadastrar_vagas.php">Cadastrar vaga</a><a href="minhas_vagas.php">Minhas vagas</a><a href="candidatos.php">Candidatos</a><a href="../sair.php">Sair ↗</a></nav></header>
<h1 class="empresa-title">Minhas vagas</h1><p class="empresa-subtitle">Acompanhe as oportunidades cadastradas pela sua empresa.</p>
<?php if ($vagas->num_rows > 0): ?>

        <?php while ($vaga = $vagas->fetch_assoc()): ?>

            <article class="empresa-item">
                <h2>
                    <?= htmlspecialchars($vaga["titulo"]) ?>
                </h2>

                <p>
                    <strong>Descrição:</strong>
                    <?= nl2br(htmlspecialchars($vaga["descricao"])) ?>
                </p>

                <p>
                    <strong>Tipo:</strong>
                    <?= htmlspecialchars($vaga["tipo"]) ?>
                </p>

                <p>
                    <strong>Modalidade:</strong>
                    <?= htmlspecialchars($vaga["modalidade"]) ?>
                </p>

                <p>
                    <strong>Cidade:</strong>
                    <?= htmlspecialchars($vaga["cidade"] ?? "") ?>
                </p>

                <p>
                    <strong>Salário:</strong>
                    <?php
                    if ($vaga["salario"] !== null && $vaga["salario"] !== "") {
                        echo "R$ " . number_format((float) $vaga["salario"], 2,",",".");
                    } else {
                        echo "Não informado";
                    }
                    ?>
                </p>

                <p>
                    <strong>Status:</strong>
                    <?= htmlspecialchars($vaga["status"]) ?>
                </p>

                <p>
                    <strong>Criada em:</strong>
                    <?= htmlspecialchars($vaga["criada_em"] ?? "") ?>
                </p>
            </article>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="empresa-empty">Sua empresa ainda não cadastrou nenhuma vaga.<br><a href="cadastrar_vagas.php">Cadastrar primeira vaga →</a></div>


    <?php endif; ?>

</div>
</body>
</html>