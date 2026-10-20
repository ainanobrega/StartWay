<?php
/*
 * Menu lateral compartilhado do StartWay.
 *
 * Antes de incluir, defina em cada página:
 *   $paginaAtual = "vagas.php";   // nome do arquivo da aba atual
 *   $baseMenu    = "";            // "" na raiz, "../" se a página estiver numa subpasta
 */
$paginaAtual = $paginaAtual ?? "";
$baseMenu    = $baseMenu ?? "";

$paginaSair  = "sair.php";

$itensMenu = [
    "home.php"                => "Início",
    "vagas.php"               => "Vagas disponíveis",
    "minhas_candidaturas.php" => "Minhas candidaturas",
    "notificacoes.php"        => "Notificações",
    "cursos.php"              => "Cursos",
    "habilidades.php"         => "Habilidades",
    "perfil.php"              => "Meu perfil",
];
?>
<button type="button" class="botao-menu" aria-label="Abrir menu" aria-controls="menuLateral" aria-expanded="false">☰</button>

<div class="fundo-menu"></div>

<aside class="menu-lateral" id="menuLateral" aria-hidden="true">
    <button type="button" class="botao-fechar-menu" aria-label="Fechar menu">×</button>

    <a href="<?= $baseMenu ?>home.php" class="marca-menu">
        <img src="<?= $baseMenu ?>img/logo.png" alt="Logo StartWay">
    </a>

    <p class="saudacao">
        Olá, <?= htmlspecialchars($_SESSION["nome"] ?? "usuário", ENT_QUOTES, "UTF-8") ?>!
    </p>

    <nav class="navegacao">
        <?php foreach ($itensMenu as $arquivo => $rotulo): ?>
            <a
                href="<?= $baseMenu . $arquivo ?>"
                class="link-menu<?= $arquivo === $paginaAtual ? " ativo" : "" ?>"
                <?= $arquivo === $paginaAtual ? 'aria-current="page"' : "" ?>
            >
                <?= $rotulo ?>
            </a>
        <?php endforeach; ?>

        <a href="<?= $baseMenu . $paginaSair ?>" class="link-menu link-sair">Sair</a>
    </nav>
</aside>