<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
date_default_timezone_set('UTC');
if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    session_start(['cookie_httponly'=>true, 'cookie_samesite'=>'Lax', 'cookie_secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
}
function e($value): string { return htmlspecialchars(is_scalar($value) ? (string)$value : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function label(string $name): string { return ucfirst(str_replace('_', ' ', $name)); }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function verifyCsrf(): void {
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals(csrf(), $_POST['csrf'])) {
        throw new DomainException('La sesión del formulario venció. Recarga e intenta de nuevo.');
    }
}
require_once __DIR__ . '/auth.php';
