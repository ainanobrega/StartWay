<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
require_once dirname(__DIR__) . '/conexao.php';
if (!function_exists('e')) {
    function e($valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
if (!defined('STARTWAY_URL_BASE')) {
    define('STARTWAY_URL_BASE', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/');
}
function urlBase(string $caminho = ''): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
    $prefix = (str_contains($script, '/admin/') || str_contains($script, '/empresa/')) ? '../' : '';
    return $prefix . ltrim($caminho, '/');
}
function exigirLogin(): void
{
    if (empty($_SESSION['id_usuario'])) {
        $dest = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') ? 'login.php' : (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/empresa/') ? '../login.php' : 'login.php');
        header('Location: ' . $dest);
        exit;
    }
}
function exigirTipo(string $tipo): void
{
    exigirLogin();
    $atual = strtolower((string) ($_SESSION['tipo'] ?? ''));
    if ($atual !== $tipo) {
        if ($atual === 'admin') {
            header('Location: ' . (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') ? 'painel.php' : 'admin/painel.php'));
        } elseif ($atual === 'empresa') {
            header('Location: ' . (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/empresa/') ? 'painel.php' : 'empresa/painel.php'));
        } else {
            header('Location: ' . ((str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/empresa/') || str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/')) ? '../home.php' : 'home.php'));
        }
        exit;
    }
}
function exigirAdmin(): void
{
    exigirTipo('admin');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    if (isset($_SESSION['admin_ultima_atividade']) && time() - (int) $_SESSION['admin_ultima_atividade'] > 1800) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', (bool) $p['secure'], (bool) $p['httponly']);
        }
        session_destroy();
        header('Location: login.php');
        exit;
    }
    $_SESSION['admin_ultima_atividade'] = time();
}
