<?php
declare(strict_types=1);

// Las credenciales deben configurarse en el entorno local, nunca en Git.
return [
    'host' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
    'port' => (int) (getenv('SMTP_PORT') ?: 587),
    'encryption' => getenv('SMTP_ENCRYPTION') ?: 'tls',
    'usuario' => getenv('SMTP_USER') ?: '',
    'password' => getenv('SMTP_PASSWORD') ?: '',
    'remitente' => getenv('SMTP_FROM') ?: (getenv('SMTP_USER') ?: ''),
    'nombre_remitente' => getenv('SMTP_FROM_NAME') ?: 'Tinta Viva',
    'direccion_estudio' => getenv('STUDIO_ADDRESS') ?: '',
    'telefono_estudio' => getenv('STUDIO_PHONE') ?: '',
    'app_url' => rtrim(getenv('APP_URL') ?: 'http://127.0.0.1:8000', '/'),
];
