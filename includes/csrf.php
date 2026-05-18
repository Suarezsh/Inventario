<?php

function generarTokenCSRF(): string
{
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    $_SESSION['csrf_token_time'] = time();
    return $token;
}

function obtenerTokenCSRF(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        return generarTokenCSRF();
    }
    return $_SESSION['csrf_token'];
}

function validarTokenCSRF(?string $token): bool
{
    if (empty($token) || empty($_SESSION['csrf_token'])) return false;
    if (!hash_equals($_SESSION['csrf_token'], $token)) return false;
    $maxTime = 3600;
    if (isset($_SESSION['csrf_token_time']) && (time() - $_SESSION['csrf_token_time']) > $maxTime) return false;
    return true;
}

function campoCSRF(): string
{
    return '<input type="hidden" name="csrf_token" value="' . obtenerTokenCSRF() . '">';
}
