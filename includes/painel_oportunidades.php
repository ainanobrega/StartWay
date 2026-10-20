<?php
// Este componente usa $conexao (mysqli), como nos arquivos atuais do StartWay.
$cursosDestaque = [];
$vagasDestaque = [];

try {
    $r = $conexao->query("SELECT id, titulo, descricao, plataforma, carga_horaria, link FROM cursos ORDER BY id DESC LIMIT 8");
    if ($r) {
        while ($linha = $r->fetch_assoc()) $cursosDestaque[] = $linha;
        $r->free();
    }
} catch (Throwable $e) { $cursosDestaque = []; }

// A tabela vagas existe, mas ainda está vazia. O SELECT não presume colunas específicas.
try {
    $r = $conexao->query("SELECT v.id,v.titulo,v.descricao,v.modalidade,v.tipo,v.link,e.nome_empresa FROM vagas v LEFT JOIN empresas e ON e.id=v.empresa_id WHERE v.status='aberta' ORDER BY v.criada_em DESC LIMIT 8");
    if ($r) {
        while ($linha = $r->fetch_assoc()) $vagasDestaque[] = $linha;
        $r->free();
    }
} catch (Throwable $e) { $vagasDestaque = []; }

function sw_campo(array $linha, array $nomes, string $padrao = ""): string {
    foreach ($nomes as $nome) {
        if (isset($linha[$nome]) && trim((string)$linha[$nome]) !== "") return trim((string)$linha[$nome]);
    }
    return $padrao;
}
function sw_curto(string $texto, int $limite = 110): string {
    $texto = trim(strip_tags($texto));
    if (function_exists("mb_strlen") && mb_strlen($texto, "UTF-8") > $limite) return mb_substr($texto, 0, $limite, "UTF-8") . "...";
    return strlen($texto) > $limite ? substr($texto, 0, $limite) . "..." : $texto;
}
function sw_link(string $url): string {
    return filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ["http", "https"], true) ? $url : "";
}
?>
<aside class="painel-oportunidades" aria-label="Cursos e vagas disponíveis">
    <div class="painel-op-topo">
        <div>
            <span class="op-etiqueta"><i></i> STARTWAY EXPLORA</span>
            <h2>Seu próximo passo começa aqui.</h2>
            <p>Aprenda algo novo e prepare-se para novas oportunidades.</p>
        </div>
        <span class="op-icone" aria-hidden="true">✦</span>
    </div>

    <div class="op-abas" role="tablist">
        <button type="button" class="op-aba ativa" data-alvo="lista-cursos" role="tab" aria-selected="true">Cursos</button>
        <button type="button" class="op-aba" data-alvo="lista-vagas" role="tab" aria-selected="false">Vagas</button>
    </div>

    <div class="op-lista ativa" id="lista-cursos" role="tabpanel">
        <?php if ($cursosDestaque): ?>
            <div class="op-carrossel">
                <?php foreach ($cursosDestaque as $i => $curso):
                    $titulo = (string)($curso["titulo"] ?? "Curso disponível");
                    $descricao = (string)($curso["descricao"] ?? "Desenvolva suas habilidades com este conteúdo.");
                    $plataforma = (string)($curso["plataforma"] ?? "Curso online");
                    $carga = $curso["carga_horaria"] ?? null;
                    $link = sw_link((string)($curso["link"] ?? ""));
                ?>
                <article class="op-card <?= $i === 0 ? "visivel" : "" ?>">
                    <div class="op-card-icone">&lt;/&gt;</div>
                    <div class="op-card-corpo">
                        <span class="op-categoria"><?= htmlspecialchars($plataforma, ENT_QUOTES, "UTF-8") ?></span>
                        <h3><?= htmlspecialchars($titulo, ENT_QUOTES, "UTF-8") ?></h3>
                        <p><?= htmlspecialchars(sw_curto($descricao), ENT_QUOTES, "UTF-8") ?></p>
                        <div class="op-card-rodape">
                            <span><?= is_numeric($carga) && (int)$carga > 0 ? htmlspecialchars((string)$carga) . " h" : "Aprenda no seu ritmo" ?></span>
                            <?php if ($link): ?><a href="<?= htmlspecialchars($link, ENT_QUOTES, "UTF-8") ?>" target="_blank" rel="noopener noreferrer">Explorar ↗</a><?php else: ?><span>Curso disponível</span><?php endif; ?>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php if (count($cursosDestaque) > 1): ?>
            <div class="op-controles">
                <div class="op-paginacao">
                    <?php foreach ($cursosDestaque as $i => $curso): ?><button class="op-ponto <?= $i === 0 ? "ativo" : "" ?>" type="button" data-ir="<?= $i ?>" aria-label="Mostrar curso <?= $i + 1 ?>"></button><?php endforeach; ?>
                </div>
                <div class="op-setas"><button type="button" data-anterior aria-label="Curso anterior">‹</button><button type="button" data-proximo aria-label="Próximo curso">›</button></div>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="op-vazio"><span>⌘</span><h3>Novos cursos em breve</h3><p>Estamos preparando conteúdos para ajudar você a desenvolver suas habilidades.</p></div>
        <?php endif; ?>
    </div>

    <div class="op-lista" id="lista-vagas" role="tabpanel" hidden>
        <?php if ($vagasDestaque): ?>
            <div class="op-carrossel">
            <?php foreach ($vagasDestaque as $i => $vaga):
                $titulo = sw_campo($vaga, ["titulo", "titulo_vaga", "nome_vaga", "cargo", "nome"], "Oportunidade de estágio");
                $descricao = sw_campo($vaga, ["descricao", "descricao_vaga", "detalhes", "sobre"], "Confira os detalhes desta oportunidade.");
                $empresa = sw_campo($vaga, ["nome_empresa", "empresa", "empresa_nome"], "Empresa cadastrada");
                $modalidade = sw_campo($vaga, ["modalidade", "modelo_trabalho"], "Oportunidade disponível");
                $link = sw_link(sw_campo($vaga, ["link", "url", "link_candidatura"]));
            ?>
                <article class="op-card <?= $i === 0 ? "visivel" : "" ?>">
                    <div class="op-card-icone vaga-icone">↗</div>
                    <div class="op-card-corpo">
                        <span class="op-categoria"><?= htmlspecialchars($empresa, ENT_QUOTES, "UTF-8") ?></span>
                        <h3><?= htmlspecialchars($titulo, ENT_QUOTES, "UTF-8") ?></h3>
                        <p><?= htmlspecialchars(sw_curto($descricao), ENT_QUOTES, "UTF-8") ?></p>
                        <div class="op-card-rodape"><span><?= htmlspecialchars($modalidade, ENT_QUOTES, "UTF-8") ?></span>
                        <?php if ($link): ?><a href="<?= htmlspecialchars($link, ENT_QUOTES, "UTF-8") ?>" target="_blank" rel="noopener noreferrer">Ver vaga ↗</a><?php endif; ?></div>
                    </div>
                </article>
            <?php endforeach; ?>
            </div>
            <?php if (count($vagasDestaque) > 1): ?>
            <div class="op-controles"><div class="op-paginacao"><?php foreach ($vagasDestaque as $i => $vaga): ?><button class="op-ponto <?= $i === 0 ? "ativo" : "" ?>" type="button" data-ir="<?= $i ?>" aria-label="Mostrar vaga <?= $i + 1 ?>"></button><?php endforeach; ?></div><div class="op-setas"><button type="button" data-anterior aria-label="Vaga anterior">‹</button><button type="button" data-proximo aria-label="Próxima vaga">›</button></div></div>
            <?php endif; ?>
        <?php else: ?>
            <div class="op-vazio"><span>✧</span><h3>Novas oportunidades em breve</h3><p>As vagas de estágio aparecerão aqui quando as empresas começarem a cadastrá-las. Crie sua conta e prepare-se para as próximas oportunidades.</p></div>
        <?php endif; ?>
    </div>
    <div class="op-rodape"><span>✦</span> Seu futuro não precisa esperar.</div>
</aside>
