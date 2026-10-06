<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="<?= e($publicPrefix ?? '../') ?>assets/css/app.css">
    <title>Conexión pendiente</title>
</head>

<body class="login">
    <main class="login-card">
        <h1>Conexión pendiente</h1>
        <p>Inicia MySQL, importa <code>database/db_preliminar.sql</code> y verifica las variables de conexión indicadas
            en README.md.</p><a href="<?= e(basename($_SERVER['SCRIPT_NAME'] ?? 'administrador.php')) ?>">Reintentar</a>
    </main>
</body>

</html>
