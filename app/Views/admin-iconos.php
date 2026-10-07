<?php
function adminIcon(string $name): string
{
    $paths = [
        'cuentas'=>'<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M17 5a3 3 0 0 1 0 6M18 15a5 5 0 0 1 3 5"/>',
        'activo'=>'<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
        'bloqueado'=>'<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"/>',
        'categorias'=>'<path d="M3 3h8l10 10-8 8L3 11Z"/><circle cx="7" cy="7" r="1"/>',
        'calificaciones'=>'<path d="m12 3 3 6 6 1-4 5 1 6-6-3-6 3 1-6-4-5 6-1Z"/>',
        'planes'=>'<path d="m3 6 5 5 4-7 4 7 5-5-2 13H5Z"/>',
        'tarjetas'=>'<rect x="2" y="5" width="20" height="15" rx="2"/><path d="M2 10h20M7 15h4"/>',
        'productos'=>'<path d="m12 3 9 5v8l-9 5-9-5V8ZM3 8l9 5 9-5M12 13v8"/>',
        'editar'=>'<path d="m15 4 5 5M4 20l4-1L21 6l-4-4L4 15Z"/>',
        'buscar'=>'<circle cx="10" cy="10" r="7"/><path d="m15 15 6 6"/>',
        'crear'=>'<path d="M12 4v16M4 12h16"/>',
        'registros'=>'<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h4"/>',
    ];
    $aliases=['especialidades'=>'categorias','promociones'=>'categorias','beneficios'=>'calificaciones','planes_beneficios'=>'planes','membresias'=>'planes','movimientos'=>'tarjetas','saldos_tarjetas'=>'tarjetas'];
    return '<svg class="admin-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">'.($paths[$aliases[$name]??$name]??$paths['registros']).'</svg>';
}
