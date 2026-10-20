<?php
function sw_data($valor, bool $hora = false): string
{
    if (!$valor)
        return 'Não informado';
    $ts = strtotime((string) $valor);
    return $ts ? date($hora ? 'd/m/Y H:i' : 'd/m/Y', $ts) : 'Não informado';
}
function sw_rotulo(string $valor): string
{
    $map = ['jovem_aprendiz' => 'Jovem aprendiz', 'estagio' => 'Estágio', 'hibrido' => 'Híbrido', 'remoto' => 'Remoto', 'presencial' => 'Presencial', 'aberta' => 'Aberta', 'fechada' => 'Fechada', 'em_analise' => 'Em análise', 'em análise' => 'Em análise', 'em_processo' => 'Em processo', 'aprovado' => 'Aprovado', 'recusado' => 'Recusado'];
    return $map[strtolower(trim($valor))] ?? ucfirst(str_replace('_', ' ', $valor));
}
