<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/Core/bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');

function campoRecuperacion(string $nombre): string
{
    return is_string($_POST[$nombre] ?? null)
        ? trim($_POST[$nombre])
        : '';
}

$usuario = campoRecuperacion('usuario');
$correo = campoRecuperacion('correo');

$error = null;
$mensaje = null;
$completado = false;

$paso = 1;
$codigo = '';

$pdo = null;
$hashCreado = null;

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        verifyCsrf();

        $accion = campoRecuperacion('accion');

        if (
            !in_array(
                $accion,
                ['enviar', 'validar', 'restablecer'],
                true
            )
        ) {
            throw new DomainException('Acción inválida.');
        }

        $paso = match ($accion) {
            'validar' => 2,
            'restablecer' => 3,
            default => 1,
        };

        $codigo = strtoupper(
            preg_replace(
                '/\s+/',
                '',
                campoRecuperacion('codigo')
            )
        );

        if ($usuario === '' || strlen($usuario) > 80) {
            throw new DomainException(
                'Ingresa un usuario válido de máximo 80 bytes.'
            );
        }

        if (
            strlen($correo) > 254 ||
            !filter_var($correo, FILTER_VALIDATE_EMAIL)
        ) {
            throw new DomainException(
                'Ingresa un correo electrónico válido.'
            );
        }

        // Límite de solicitudes por sesión.
        $ahora = time();

        $limite = $_SESSION['recuperacion_limite'] ?? [
            'inicio' => $ahora,
            'cantidad' => 0,
        ];

        if ($ahora - $limite['inicio'] >= 900) {
            $limite = [
                'inicio' => $ahora,
                'cantidad' => 0,
            ];
        }

        if ($limite['cantidad'] >= 20) {
            throw new DomainException(
                'Demasiadas solicitudes. Intenta más tarde.'
            );
        }

        $limite['cantidad']++;
        $_SESSION['recuperacion_limite'] = $limite;

        if ($accion === 'enviar') {
            require_once __DIR__ . '/../../app/Services/correo.php';
        }

        $pdo = conectarBaseDatos();
        $pdo->beginTransaction();

        // Usuario y correo deben pertenecer a la misma cuenta.
        $consulta = $pdo->prepare(
            'SELECT id_cuenta, correo, estado
             FROM cuentas
             WHERE usuario = ?
               AND correo = ?
             FOR UPDATE'
        );

        $consulta->execute([$usuario, $correo]);

        $cuenta = $consulta->fetch(PDO::FETCH_ASSOC);

        if (!$cuenta || $cuenta['estado'] !== 'activo') {
            throw new DomainException(
                'Comprueba el usuario y el correo. '
                . 'La cuenta debe estar activa.'
            );
        }

        $idCuenta = $cuenta['id_cuenta'];

        if ($accion === 'enviar') {
            /*
             * PASO 1: CREAR Y ENVIAR EL CÓDIGO.
             */

            // Máximo cinco códigos por cuenta en 15 minutos.
            $consulta = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM tokens_recuperacion_contrasena
                 WHERE id_cuenta = ?
                   AND fecha_creacion >
                       DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
            );

            $consulta->execute([$idCuenta]);

            if ((int) $consulta->fetchColumn() >= 5) {
                throw new DomainException(
                    'Ya solicitaste varios códigos. '
                    . 'Revisa tu correo o intenta más tarde.'
                );
            }

           // Código numérico de seis dígitos, conservando ceros iniciales.
$codigoEnviar = str_pad(
    (string) random_int(0, 999999),
    6,
    '0',
    STR_PAD_LEFT
);
            // Guardar únicamente el hash binario.
            $hashCreado = hash(
                'sha256',
                $codigoEnviar,
                true
            );

            $consulta = $pdo->prepare(
                'INSERT INTO tokens_recuperacion_contrasena
                    (id_cuenta, token_hash, fecha_expiracion)
                 VALUES
                    (:id_cuenta, :token_hash,
                     DATE_ADD(NOW(), INTERVAL 30 MINUTE))'
            );

            $consulta->bindValue(
                ':id_cuenta',
                $idCuenta
            );

            $consulta->bindValue(
                ':token_hash',
                $hashCreado,
                PDO::PARAM_LOB
            );

            $consulta->execute();

            // Liberar el bloqueo antes del envío SMTP.
            $pdo->commit();

            $enviado = enviarCorreoRecuperacion(
                $cuenta['correo'],
                $codigoEnviar
            );

            if (!$enviado) {
                throw new RuntimeException(
                    'El servidor SMTP no aceptó el correo.'
                );
            }

            // Conservar el código en la base de datos.
            $hashCreado = null;

            // Mostrar el formulario para ingresar el código.
            $paso = 2;
            $codigo = '';

            $mensaje =
                'El servidor SMTP aceptó el correo. '
                . 'Revisa tu bandeja de entrada y spam '
                . 'e ingresa el código recibido.';
        } else {
            /*
             * PASO 2: VALIDAR CÓDIGO.
             * PASO 3: VALIDAR NUEVAMENTE Y CAMBIAR CONTRASEÑA.
             */

            if (!preg_match('/^[0-9]{6}$/D', $codigo)) {
    $paso = 2;

    throw new DomainException(
        'El código debe tener exactamente 6 dígitos. '
        . 'Cópialo completo desde el correo.'
    );
}
            $tokenHash = hash('sha256', $codigo, true);

            $consulta = $pdo->prepare(
                'SELECT id_token
                 FROM tokens_recuperacion_contrasena
                 WHERE id_cuenta = :id_cuenta
                   AND token_hash = :token_hash
                   AND fecha_uso IS NULL
                   AND fecha_expiracion > NOW()
                 FOR UPDATE'
            );

            $consulta->bindValue(
                ':id_cuenta',
                $idCuenta
            );

            $consulta->bindValue(
                ':token_hash',
                $tokenHash,
                PDO::PARAM_LOB
            );

            $consulta->execute();

            if ($consulta->fetchColumn() === false) {
                $paso = 2;

                throw new DomainException(
                    'El código es incorrecto, venció '
                    . 'o ya fue utilizado. '
                    . 'Solicita otro si es necesario.'
                );
            }

            if ($accion === 'validar') {
                // No consumir el código todavía.
                $pdo->commit();

                $paso = 3;

                $mensaje =
                    'Código válido. '
                    . 'Ahora escribe tu nueva contraseña.';
            } else {
                // No quitar espacios de las contraseñas.
                $clave = is_string($_POST['clave'] ?? null)
                    ? $_POST['clave']
                    : '';

                $confirmacion = is_string(
                    $_POST['confirmacion'] ?? null
                )
                    ? $_POST['confirmacion']
                    : '';

                if (
                    strlen($clave) < 8 ||
                    strlen($clave) > 72 ||
                    strpos($clave, "\0") !== false
                ) {
                    throw new DomainException(
                        'La contraseña debe tener entre 8 y 72 bytes '
                        . 'y no incluir caracteres nulos.'
                    );
                }

                if ($clave !== $confirmacion) {
                    throw new DomainException(
                        'Las contraseñas no coinciden.'
                    );
                }

                $nuevoHash = password_hash(
                    $clave,
                    PASSWORD_BCRYPT
                );

                // Actualizar contraseña.
                $consulta = $pdo->prepare(
                    'UPDATE cuentas
                     SET contrasena_hash = ?
                     WHERE id_cuenta = ?'
                );

                $consulta->execute([
                    $nuevoHash,
                    $idCuenta,
                ]);

                // Invalidar todos los códigos pendientes.
                $consulta = $pdo->prepare(
                    'UPDATE tokens_recuperacion_contrasena
                     SET fecha_uso = NOW()
                     WHERE id_cuenta = ?
                       AND fecha_uso IS NULL'
                );

                $consulta->execute([$idCuenta]);

                $pdo->commit();

                $completado = true;
                $codigo = '';

                $mensaje =
                    'Contraseña actualizada correctamente. '
                    . 'Ya puedes iniciar sesión.';
            }
        }
    }
} catch (Throwable $ex) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Invalidar el código recién creado si el envío falló.
    if ($pdo instanceof PDO && $hashCreado !== null) {
        try {
            $consulta = $pdo->prepare(
                'UPDATE tokens_recuperacion_contrasena
                 SET fecha_uso = NOW()
                 WHERE token_hash = :token_hash
                   AND fecha_uso IS NULL'
            );

            $consulta->bindValue(
                ':token_hash',
                $hashCreado,
                PDO::PARAM_LOB
            );

            $consulta->execute();
        } catch (Throwable $limpieza) {
            error_log(
                'Error al invalidar código: '
                . $limpieza->getMessage()
            );
        }
    }

    error_log(
        'Recuperación: ' . $ex->getMessage()
    );

    // Mensajes detallados para pruebas locales en XAMPP.
    $error = $ex->getMessage();
    $mensaje = null;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="referrer"
        content="no-referrer"
    >

    <title>Recuperar contraseña · EclipseTATTO</title>

    <link
        rel="stylesheet"
        href="../assets/css/app.css"
    >
</head>

<body class="login">
<main class="login-card">
    <p class="eyebrow">ECLIPSETATTO</p>

    <h1>Recuperar contraseña</h1>

    <?php if ($error !== null): ?>
        <p class="notice error" role="alert">
            <?= e($error) ?>
        </p>
    <?php endif ?>

    <?php if ($mensaje !== null): ?>
        <p class="notice" role="status">
            <?= e($mensaje) ?>
        </p>
    <?php endif ?>

    <?php if (!$completado): ?>

        <?php if ($paso === 1): ?>

            <h2>1. Solicitar código</h2>

            <p>
                Ingresa el usuario y el correo
                registrados en tu cuenta.
            </p>

            <form
                method="post"
                action="recuperar_contrasena.php"
            >
                <input
                    type="hidden"
                    name="csrf"
                    value="<?= e(csrf()) ?>"
                >

                <input
                    type="hidden"
                    name="accion"
                    value="enviar"
                >

                <label>
                    Usuario

                    <input
                        type="text"
                        name="usuario"
                        autocomplete="username"
                        maxlength="80"
                        value="<?= e($usuario) ?>"
                        required
                    >
                </label>

                <label>
                    Correo electrónico

                    <input
                        type="email"
                        name="correo"
                        autocomplete="email"
                        maxlength="254"
                        value="<?= e($correo) ?>"
                        required
                    >
                </label>

                <button type="submit">
                    Enviar código
                </button>
            </form>

        <?php elseif ($paso === 2): ?>

            <h2>2. Validar código</h2>

            <p>
                Ingresa el código enviado a
                <strong><?= e($correo) ?></strong>.
            </p>

            <form
                method="post"
                action="recuperar_contrasena.php"
            >
                <input
                    type="hidden"
                    name="csrf"
                    value="<?= e(csrf()) ?>"
                >

                <input
                    type="hidden"
                    name="accion"
                    value="validar"
                >

                <input
                    type="hidden"
                    name="usuario"
                    value="<?= e($usuario) ?>"
                >

                <input
                    type="hidden"
                    name="correo"
                    value="<?= e($correo) ?>"
                >

                <label>
                    Código recibido por correo

                   <input
    type="text"
    name="codigo"
    inputmode="numeric"
    autocomplete="one-time-code"
    pattern="[0-9]{6}"
    minlength="6"
    maxlength="6"
    spellcheck="false"
    placeholder="Código de 6 dígitos"
    value="<?= e($codigo) ?>"
    autofocus
    required
>
                </label>

                <button type="submit">
                    Validar y continuar
                </button>
            </form>

            <p>
                <a href="recuperar_contrasena.php">
                    Solicitar otro código
                </a>
            </p>

        <?php elseif ($paso === 3): ?>

            <h2>3. Nueva contraseña</h2>

            <p>
                Escribe tu nueva contraseña y confírmala.
            </p>

            <form
                method="post"
                action="recuperar_contrasena.php"
            >
                <input
                    type="hidden"
                    name="csrf"
                    value="<?= e(csrf()) ?>"
                >

                <input
                    type="hidden"
                    name="accion"
                    value="restablecer"
                >

                <input
                    type="hidden"
                    name="usuario"
                    value="<?= e($usuario) ?>"
                >

                <input
                    type="hidden"
                    name="correo"
                    value="<?= e($correo) ?>"
                >

                <input
                    type="hidden"
                    name="codigo"
                    value="<?= e($codigo) ?>"
                >

                <label>
                    Nueva contraseña

                    <input
                        type="password"
                        name="clave"
                        autocomplete="new-password"
                        minlength="8"
                        autofocus
                        required
                    >
                </label>

                <label>
                    Confirmar contraseña

                    <input
                        type="password"
                        name="confirmacion"
                        autocomplete="new-password"
                        minlength="8"
                        required
                    >
                </label>

                <button type="submit">
                    Guardar contraseña
                </button>
            </form>

        <?php endif ?>

    <?php endif ?>

    <p>
        <a href="login.php">
            Volver a iniciar sesión
        </a>
    </p>
</main>
</body>
</html>
