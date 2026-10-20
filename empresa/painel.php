<?php
require_once __DIR__ . '/../includes/csrf.php';
if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") { csrf_validar(); }
require_once __DIR__ . '/../includes/bootstrap.php';

// verififca se o usuario esta logado
if (!isset($_SESSION["id_usuario"])){
    header("Location: ../login.php");
    exit;
}

// somente empresas podem acessar este painel
if ($_SESSION["tipo"] !== "empresa") {
    header("Location: ../home.php");
    exit;
}

$nome = htmlspecialchars($_SESSION["nome"], ENT_QUOTES, "UTF-8");

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Painel da Empresa - StartWay</title>

    <link rel="stylesheet" href="../css/base.css">
    <style>
    body{background:radial-gradient(circle at 90% 0,#e7dbff,transparent 28rem),#f7f4ff;color:#2b1b45;font-family:Inter,"Segoe UI",Arial,sans-serif}
    .empresa-layout{width:min(1180px,92%);margin:36px auto}.empresa-nav{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:18px 22px;border:1px solid #eee5fb;border-radius:18px;background:#fff;box-shadow:0 8px 28px #32105c0d}.empresa-brand{font-size:23px;font-weight:900;color:#6d28d9}.empresa-links{display:flex;gap:10px;flex-wrap:wrap}.empresa-links a{padding:10px 13px;border-radius:10px;color:#5b4a73;font-weight:700}.empresa-links a:hover,.empresa-links a.ativo{background:#f1eaff;color:#6d28d9}.empresa-hero{position:relative;overflow:hidden;margin:24px 0;padding:clamp(28px,5vw,52px);border-radius:26px;color:#fff;background:linear-gradient(120deg,#2d174c,#6d28d9 68%,#8b5cf6);box-shadow:0 20px 48px #5b21b633}.empresa-hero:after{content:"";position:absolute;width:230px;height:230px;border:1px solid #ffffff55;border-radius:50%;right:-45px;top:-65px;box-shadow:0 0 0 25px #ffffff0d,0 0 0 52px #ffffff0a}.empresa-hero h2{font-size:clamp(28px,4vw,42px);margin-bottom:12px}.empresa-hero p{max-width:620px;color:#eee5ff;line-height:1.8}.empresa-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:18px}.empresa-card{padding:25px;border:1px solid #ece3fa;border-radius:20px;background:#fff;box-shadow:0 10px 30px #32105c0b;transition:.2s}.empresa-card:hover{transform:translateY(-3px);box-shadow:0 16px 35px #32105c16}.empresa-card .emoji{display:grid;place-items:center;width:48px;height:48px;margin-bottom:18px;border-radius:15px;background:#f2eaff;color:#6d28d9;font-size:24px}.empresa-card h3{margin-bottom:10px;font-size:19px}.empresa-card p{min-height:48px;margin-bottom:20px;color:#766b86;line-height:1.7}.empresa-card a{display:inline-flex;padding:11px 14px;border-radius:11px;background:#6d28d9;color:#fff;font-weight:800}.empresa-footer{padding:24px 0;color:#817391;font-size:12px}@media(max-width:600px){.empresa-layout{margin:18px auto}.empresa-nav{align-items:flex-start}.empresa-links{width:100%}.empresa-links a{padding:8px}}
    </style>
</head>

<body>
<div class="empresa-layout">
    <header class="empresa-nav">
        <a class="empresa-brand" href="painel.php">StartWay<span style="color:#a78bfa">.</span></a>
        <nav class="empresa-links" aria-label="Navegação da empresa">
            <a class="ativo" href="painel.php">Painel inicial</a>
            <a href="cadastrar_vagas.php">Cadastrar vaga</a>
            <a href="minhas_vagas.php">Minhas vagas</a>
            <a href="candidatos.php">Candidatos</a>
            <a href="../sair.php">Sair ↗</a>
        </nav>
    </header>
    <section class="empresa-hero">
        <h2>Olá, <?= $nome ?>! 💜</h2>
        <p>Bem-vinda à área da empresa. Gerencie suas oportunidades, acompanhe candidaturas e encontre novos talentos para crescer junto com sua equipe.</p>
    </section>
    <section class="empresa-grid" aria-label="Ações da empresa">
        <article class="empresa-card"><div class="emoji">＋</div><h3>Publicar oportunidade</h3><p>Crie uma vaga clara e encontre pessoas alinhadas ao que sua equipe precisa.</p><a href="cadastrar_vagas.php">Cadastrar vaga →</a></article>
        <article class="empresa-card"><div class="emoji">▤</div><h3>Minhas vagas</h3><p>Consulte as oportunidades publicadas e acompanhe suas informações.</p><a href="minhas_vagas.php">Ver minhas vagas →</a></article>
        <article class="empresa-card"><div class="emoji">♙</div><h3>Candidatos</h3><p>Acompanhe os registros de interesse nas oportunidades da sua empresa.</p><a href="candidatos.php">Ver candidatos →</a></article>
    </section>
    <footer class="empresa-footer">StartWay · Conectando talentos a oportunidades.</footer>
</div>
</body>
</html>
