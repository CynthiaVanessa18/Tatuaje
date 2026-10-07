<?php
require_once __DIR__.'/../../app/Core/bootstrap.php';
require_once __DIR__.'/../../app/Services/ClientRegistration.php';
$error=null;
try {
    if ($account=currentAccount()) redirectToRole($account);
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        verifyCsrf();
        ClientRegistration::register(conectarBaseDatos(),$_POST);
        header('Location: login.php?registro=ok',true,303); exit;
    }
} catch (DomainException $ex) { $error=$ex->getMessage(); }
catch (PDOException $ex) { error_log($ex->getMessage()); $error='No se pudo crear la cuenta. Inténtalo nuevamente.'; }
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Crear cuenta · Tinta Viva</title><link rel="stylesheet" href="../assets/css/app.css"><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700&family=Montserrat:wght@400;500;600;700&display=swap"><link rel="stylesheet" href="../assets/css/auth.css?v=<?= e(filemtime(__DIR__.'/../assets/css/auth.css')) ?>"><?php responsiveAssets(); ?>
</head>
<body class="login registro"><div class="auth-atmosphere" aria-hidden="true"><span class="auth-fog auth-fog-far"></span><span class="auth-fog auth-fog-near"></span><span class="auth-golden-light"></span></div><main class="login-card">
<a class="auth-brand" href="../index.php">TINTA VIVA</a><p class="eyebrow">FORMA PARTE DEL ESTUDIO</p><h1>Crear cuenta</h1><p>Regístrate como cliente para comprar y consultar tus citas.</p>
<?php if ($error): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif ?>
<form method="post">
<input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
<?php foreach (['nombre'=>['Nombre','given-name',100],'apellidos'=>['Apellidos','family-name',150],'usuario'=>['Usuario','username',80],'correo'=>['Correo electrónico','email',254],'telefono'=>['Teléfono (opcional)','tel',25]] as $key=>[$caption,$autocomplete,$max]): ?>
<label class="<?= in_array($key,['nombre','apellidos'],true)?'registro-medio':'registro-completo' ?>"><?= e($caption) ?><input type="<?= $key==='correo'?'email':($key==='telefono'?'tel':'text') ?>" name="<?= e($key) ?>" autocomplete="<?= e($autocomplete) ?>" maxlength="<?= e($max) ?>" <?= $key!=='telefono'?'required':'' ?> value="<?= e(is_string($_POST[$key]??null)?$_POST[$key]:'') ?>"></label>
<?php endforeach ?>
<label>Contraseña<input type="password" name="clave" autocomplete="new-password" minlength="12" maxlength="72" required></label><p>Usa una contraseña de al menos 12 caracteres.</p>
<label>Confirmar contraseña<input type="password" name="confirmar_clave" autocomplete="new-password" minlength="12" maxlength="72" required></label>
<button type="submit">Crear mi cuenta</button>
</form><p>¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a></p>
</main></body></html>
