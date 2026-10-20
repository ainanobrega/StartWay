<?php
function obterIdYoutube($url): ?string
{
    $url = trim((string) $url);
    if ($url === '')
        return null;
    $p = parse_url($url);
    if (!$p)
        return null;
    $host = strtolower($p['host'] ?? '');
    $path = $p['path'] ?? '';
    $id = null;
    if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], true)) {
        parse_str($p['query'] ?? '', $q);
        $id = $q['v'] ?? null;
        if (!$id && preg_match('~^/(embed|shorts)/([a-zA-Z0-9_-]+)~', $path, $m))
            $id = $m[2];
    } elseif (in_array($host, ['youtu.be', 'www.youtu.be'], true))
        $id = trim($path, '/');
    return ($id && preg_match('/^[a-zA-Z0-9_-]{11}$/', $id)) ? $id : null;
}
