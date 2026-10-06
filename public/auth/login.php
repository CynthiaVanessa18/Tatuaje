<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';

$error = null;
$returnAfterLogin = $_SESSION['return_after_login'] ?? null;

if ($returnAfterLogin !== '../cotizaciones/') {
    $returnAfterLogin = null;
    unset($_SESSION['return_after_login']);
}

try {
    if ($account = currentAccount()) {
        if ($account['nombre_rol'] === 'cliente' && $returnAfterLogin !== null) {
            unset($_SESSION['return_after_login']);
            header('Location: ' . $returnAfterLogin, true, 303);
            exit;
        }

        redirectToRole($account);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();

        $username = is_string($_POST['usuario'] ?? null)
            ? trim($_POST['usuario'])
            : '';

        $password = is_string($_POST['clave'] ?? null)
            ? $_POST['clave']
            : '';

        $account = authenticate($username, $password);

        if ($account['nombre_rol'] === 'cliente' && $returnAfterLogin !== null) {
            header('Location: ' . $returnAfterLogin, true, 303);
            exit;
        }

        redirectToRole($account);
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
    <title>Iniciar sesión · Tinta Viva</title>
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700&family=Montserrat:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="../assets/css/auth.css?v=<?= e(filemtime(__DIR__.'/../assets/css/auth.css')) ?>">
</head>
<body class="login login-artistic">
<div class="auth-atmosphere" aria-hidden="true"><span class="auth-fog auth-fog-far"></span><span class="auth-fog auth-fog-near"></span><span class="auth-golden-light"></span></div>
<main class="login-card">
    <a class="auth-brand" href="../index.php">TINTA VIVA</a>
    <p class="eyebrow">TU ESPACIO EN EL ESTUDIO</p>
    <h1>Iniciar sesión</h1>
    <p>
        Ingresa para acceder a tu cuenta y seguir explorando.
    </p>

    <?php if ($returnAfterLogin !== null): ?>
        <p class="notice" role="status">
            Inicia sesión como cliente para continuar con tu cotización.
        </p>
    <?php endif ?>

    <?php if ($error): ?>
        <p class="notice error" role="alert"><?= e($error) ?></p>
    <?php endif ?>
    <?php if (($_GET['registro']??'')==='ok'): ?><p class="notice" role="status">Cuenta creada correctamente. Ya puedes iniciar sesión.</p><?php endif ?>

    <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">

        <label>
            Usuario
            <input
                name="usuario"
                autocomplete="username"
                required
                value="<?= e(
                    is_string($_POST['usuario'] ?? null)
                        ? $_POST['usuario']
                        : ''
                ) ?>"
            >
        </label>

        <label>
            Contraseña
            <input
                type="password"
                name="clave"
                autocomplete="current-password"
                required
            >
        </label>

        <button type="submit">Ingresar</button>
    </form>

    <p class="password-recovery">
        <a href="recuperar_contrasena.php">
            ¿Olvidaste tu contraseña?
        </a>
    </p>
    <p class="auth-footer">¿No tienes cuenta? <a href="registro.php">Crear una cuenta</a></p>
    <a class="auth-back" href="../index.php">← Volver al inicio</a>
</main>
</body>
</html>
