<?php
require_once __DIR__ . '/../app/bootstrap.php';
$error = null;
try {
    if ($account = currentAccount()) redirectToRole($account);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();
        $username = is_string($_POST['usuario'] ?? null) ? trim($_POST['usuario']) : '';
        $password = is_string($_POST['clave'] ?? null) ? $_POST['clave'] : '';
        redirectToRole(authenticate($username, $password));
    }
} catch (DomainException $ex) {
    $error = $ex->getMessage();
} catch (PDOException $ex) {
    error_log($ex->getMessage());
    $error = 'No se pudo acceder a la base de datos. Revisa la conexión de MySQL.';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingreso · Estudio Tattoo</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="login">
<main class="login-card">
    <p class="eyebrow">ESTUDIO TATTOO</p>
    <h1>Iniciar sesión</h1>
    <p>Ingresa con tu cuenta. Te dirigiremos al espacio correspondiente a tu rol.</p>
    <?php if ($error): ?>
        <p class="notice error" role="alert"><?= e($error) ?></p>
    <?php endif ?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <label>Usuario
            <input name="usuario" autocomplete="username" required
                   value="<?= e(is_string($_POST['usuario'] ?? null) ? $_POST['usuario'] : '') ?>">
        </label>
        <label>Contraseña
            <input type="password" name="clave" autocomplete="current-password" required>
        </label>
        <button>Ingresar</button>
    </form>
</main>
</body>
</html>
