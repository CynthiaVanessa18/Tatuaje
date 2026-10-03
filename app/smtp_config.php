<?php
declare(strict_types=1);

// Las credenciales deben configurarse en el entorno local, nunca en Git.
return [
    'usuario' => getenv('SMTP_USER') ?: '',
    'password' => getenv('SMTP_PASSWORD') ?: '',
];
