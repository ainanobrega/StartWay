<nav aria-label="Menu administrativo" style="display:flex;gap:1rem;flex-wrap:wrap;margin:1rem 0">
    <a href="painel.php">Painel</a><a href="usuarios.php">Usuários</a><a href="vagas.php">Vagas</a><a
        href="cursos.php">Cursos</a>
    <form method="post" action="sair.php" style="display:inline"><?php csrf_campo(); ?><button
            type="submit">Sair</button></form>
</nav>