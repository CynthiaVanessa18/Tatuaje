<?php
function clientStoreIcon(string $name): string {
    $paths=[
        'carrito'=>'<path d="M2 3h3l3 13h11l3-10H6M9 19a1 1 0 1 0 0 2 1 1 0 0 0 0-2ZM18 19a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z"/>',
        'regalo'=>'<rect x="3" y="9" width="18" height="4" rx="1"/><path d="M5 13v9h14v-9M12 9v13M12 9C4 9 5 1 8 3c3 1 4 6 4 6s1-5 4-6c3-2 4 6-4 6Z"/>',
        'buscar'=>'<circle cx="10" cy="10" r="7"/><path d="m15 15 6 6"/>',
        'etiqueta'=>'<path d="M3 3h8l10 10-8 8L3 11Z"/><circle cx="7.5" cy="7.5" r="1"/>',
        'fecha'=>'<rect x="3" y="5" width="18" height="17" rx="2"/><path d="M7 2v6M17 2v6M3 11h18M7 15h2M15 15h2M7 19h2"/>',
    ];
    return '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">'.($paths[$name]??$paths['etiqueta']).'</svg>';
}
