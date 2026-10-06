<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
date_default_timezone_set('UTC');
if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    session_start(['cookie_httponly'=>true, 'cookie_samesite'=>'Lax', 'cookie_secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
}
function e($value): string { return htmlspecialchars(is_scalar($value) ? (string)$value : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function imageUrl(string $path): string {
    if (str_starts_with($path,'/') || preg_match('~^https?://~i',$path)) return $path;
    return ($GLOBALS['publicPrefix'] ?? '../').$path;
}
function label(string $name): string {
    if ($name==='id_grupo') return 'Grupo de clientes';
    $labels=['id_categoria'=>'Categoría de tatuaje','id_categoria_producto'=>'Categoría de artículo','id_cliente'=>'Cliente','id_cliente_comprador'=>'Cliente comprador','id_artista'=>'Artista','id_producto'=>'Artículo','id_plan'=>'Plan de membresía','id_beneficio'=>'Beneficio','id_venta'=>'Venta','id_detalle_venta'=>'Artículo vendido','id_cita'=>'Cita','id_pago'=>'Pago','id_cotizacion'=>'Cotización','id_promocion'=>'Promoción','id_tarjeta'=>'Tarjeta de regalo','imagen_url'=>'Imagen','stock_actual'=>'Existencias','stock_minimo'=>'Stock mínimo'];
    return $labels[$name] ?? ucfirst(str_replace('_', ' ', preg_replace('/^id_/', '', $name)));
}
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function verifyCsrf(): void {
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals(csrf(), $_POST['csrf'])) {
        throw new DomainException('La sesión del formulario venció. Recarga e intenta de nuevo.');
    }
}
require_once __DIR__ . '/auth.php';
