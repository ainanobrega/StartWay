<?php
require_once __DIR__ . '/csrf.php';
function adminLog(mysqli $db, string $acao, string $alvoTipo = '', ?int $alvoId = null): void
{
    $admin = (int) ($_SESSION['id_usuario'] ?? 0);
    $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    $s = $db->prepare('INSERT INTO admin_logs (admin_id,acao,alvo_tipo,alvo_id,ip,criado_em) VALUES (?,?,?,?,?,NOW())');
    if ($s) {
        $s->bind_param('issis', $admin, $acao, $alvoTipo, $alvoId, $ip);
        if (!$s->execute())
            error_log('StartWay admin log: ' . $s->error);
        $s->close();
    }
}
function adminPost(mysqli $db): void
{
    exigirAdmin();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_validar();
        adminLog($db, (string) ($_POST['acao'] ?? 'acao_admin'), (string) ($_POST['alvo_tipo'] ?? ''), isset($_POST['id']) ? (int) $_POST['id'] : null);
    }
}
function tentativasChave(string $email): string
{
    return hash('sha256', strtolower(trim($email)) . '|' . (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
}
function loginBloqueado(mysqli $db, string $hash): bool
{
    $s = $db->prepare('SELECT bloqueado_ate FROM tentativas_login WHERE chave_hash=?');
    $s->bind_param('s', $hash);
    $s->execute();
    $r = $s->get_result()->fetch_assoc();
    $s->close();
    return $r && !empty($r['bloqueado_ate']) && strtotime($r['bloqueado_ate']) > time();
}
function registrarFalhaLogin(mysqli $db, string $hash): void
{
    $s = $db->prepare("INSERT INTO tentativas_login(chave_hash,tentativas,bloqueado_ate,atualizado_em) VALUES (?,1,NULL,NOW()) ON DUPLICATE KEY UPDATE tentativas=IF(bloqueado_ate IS NOT NULL AND bloqueado_ate<=NOW(),1,tentativas+1), bloqueado_ate=IF(tentativas>=4,DATE_ADD(NOW(),INTERVAL 15 MINUTE),IF(bloqueado_ate<=NOW(),NULL,bloqueado_ate)), atualizado_em=NOW()");
    $s->bind_param('s', $hash);
    $s->execute();
    $s->close();
}
function limparFalhasLogin(mysqli $db, string $hash): void
{
    $s = $db->prepare('DELETE FROM tentativas_login WHERE chave_hash=?');
    $s->bind_param('s', $hash);
    $s->execute();
    $s->close();
}
function adminHeader(string $titulo): void
{
    echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . e($titulo) . ' | StartWay</title><link rel="stylesheet" href="../css/base.css"><link rel="stylesheet" href="../css/empresa.css"><meta http-equiv="Cache-Control" content="no-store"></head><body><main class="container" style="max-width:1100px;margin:2rem auto;padding:1rem"><h1>' . e($titulo) . '</h1>';
    include __DIR__ . '/menu_admin.php';
}
function adminFooter(): void
{
    echo '</main></body></html>';
}
