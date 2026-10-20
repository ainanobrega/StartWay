<?php
require_once __DIR__ . '/bootstrap.php';
function exigirJovem(): void
{
    exigirTipo('jovem');
}
function exigirEmpresa(): void
{
    exigirTipo('empresa');
}
