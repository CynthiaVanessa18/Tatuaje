<?php
function membershipClientIcon(string $code): string {
    $paths=[
        'brillo'=>'<path d="m12 2 2.5 7.5L22 12l-7.5 2.5L12 22l-2.5-7.5L2 12l7.5-2.5Z"/><path d="m4 2 .5 1.5L6 4l-1.5.5L4 6l-.5-1.5L2 4l1.5-.5Z"/>',
        'color'=>'<path d="M12 2S5 11 5 15a7 7 0 0 0 14 0c0-4-7-13-7-13Z"/><path d="M9 16a3 3 0 0 0 3 3"/>',
        'hidratacion'=>'<path d="M12 21C3 20 2 14 2 10c5 0 8 3 10 11ZM12 21c9-1 10-7 10-11-5 0-8 3-10 11Z"/><path d="M12 21C5 13 8 7 12 2c4 5 7 11 0 19Z"/>',
        'kit_regalo'=>'<rect x="3" y="9" width="18" height="4" rx="1"/><path d="M5 13v9h14v-9M12 9v13"/><path d="M12 9C4 9 5 1 8 3c3 1 4 6 4 6s1-5 4-6c3-2 4 6-4 6Z"/>',
        'kit_descuento'=>'<path d="M3 3h8l10 10-8 8L3 11Z"/><circle cx="7.5" cy="7.5" r="1"/>',
        'mercancia_porcentaje'=>'<path d="M2 3h3l3 13h11l3-10H6M9 19a1 1 0 1 0 0 2 1 1 0 0 0 0-2ZM18 19a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z"/>',
        'mercancia_monto'=>'<ellipse cx="15" cy="5" rx="6" ry="3"/><path d="M9 5v4c0 4 12 4 12 0V5M9 9v4c0 4 12 4 12 0V9"/><ellipse cx="7" cy="15" rx="6" ry="3"/><path d="M1 15v4c0 4 12 4 12 0v-4"/>',
        'prioridad'=>'<rect x="3" y="5" width="18" height="17" rx="2"/><path d="M7 2v6M17 2v6M3 11h18M7 15h2M15 15h2M7 19h2M15 19h2"/>',
        'retoques'=>'<path d="M21 10a9 9 0 0 0-16-5L2 8M2 8h6M2 8V2M3 14a9 9 0 0 0 16 5l3-3M22 16h-6M22 16v6"/>',
        'pendiente'=>'<path d="M6 2h12M6 22h12M7 2v5l10 10v5M17 2v5L7 17v5"/>',
        'pago'=>'<rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 9h20M6 15h4"/>',
        'activa'=>'<circle cx="12" cy="12" r="9"/><path d="m7 12 3 3 7-7"/>',
        'info'=>'<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/>',
    ];
    return '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round">'.($paths[$code]??$paths['brillo']).'</svg>';
}
