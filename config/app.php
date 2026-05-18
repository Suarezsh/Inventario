<?php

define('APP_NAME', 'Sistema de Inventario');
define('APP_VERSION', '1.0.0');

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$dir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
define('BASE_URL', $protocol . '://' . $host . $dir);

session_name('INVENTARIO_SESSION');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Lima');

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function money(float $amount, string $symbol = '$'): string
{
    return $symbol . ' ' . number_format($amount, 2, '.', ',');
}

function fdate(?string $date, string $format = 'Y-m-d'): string
{
    if (!$date || $date === '0000-00-00') return '';
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dt) {
        $dt = new DateTime($date);
    }
    return $dt ? $dt->format($format) : '';
}

function fdatetime(?string $datetime, string $format = 'Y-m-d H:i:s'): string
{
    if (!$datetime) return '';
    $dt = new DateTime($datetime);
    return $dt ? $dt->format($format) : '';
}

function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
