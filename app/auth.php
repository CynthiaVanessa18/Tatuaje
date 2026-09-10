<?php
declare(strict_types=1);

/** Las rutas y los permisos se deciden en el servidor, nunca en el formulario. */
function roleRoutes(): array
{
    return [
        'administrador' => 'index.php',
        'secretaria' => 'secretaria.php',
        'artista' => 'artista.php',
        'cliente' => 'cliente.php',
    ];
}

function currentAccount(): ?array
{
    if (empty($_SESSION['account'])) return null;
    $query = conectarBaseDatos()->prepare(
        "SELECT c.id_cuenta, c.usuario, r.nombre_rol
         FROM cuentas c JOIN roles r ON r.id_rol = c.id_rol
         WHERE c.id_cuenta = ? AND c.estado = 'activo' AND r.activo = 1"
    );
    $query->execute([$_SESSION['account']]);
    $account = $query->fetch();
    if (!$account || !isset(roleRoutes()[$account['nombre_rol']])) {
        unset($_SESSION['account'], $_SESSION['role']);
        return null;
    }
    $_SESSION['role'] = $account['nombre_rol'];
    return $account;
}

function redirectToRole(array $account): never
{
    header('Location: ' . roleRoutes()[$account['nombre_rol']], true, 303);
    exit;
}

function requireRole(string $role): array
{
    $account = currentAccount();
    if (!$account) {
        header('Location: login.php', true, 303);
        exit;
    }
    if ($account['nombre_rol'] !== $role) redirectToRole($account);
    return $account;
}

function requireAdmin(): void
{
    requireRole('administrador');
}

function authenticate(string $username, string $password): array
{
    $query = conectarBaseDatos()->prepare(
        "SELECT c.id_cuenta, c.usuario, c.contrasena_hash, r.nombre_rol
         FROM cuentas c JOIN roles r ON r.id_rol = c.id_rol
         WHERE c.usuario = ? AND c.estado = 'activo' AND r.activo = 1"
    );
    $query->execute([$username]);
    $account = $query->fetch();
    if (!$account || !password_verify($password, $account['contrasena_hash'])
        || !isset(roleRoutes()[$account['nombre_rol']])) {
        throw new DomainException('Usuario o contraseña incorrectos, o cuenta sin acceso habilitado.');
    }
    conectarBaseDatos()->prepare('UPDATE cuentas SET ultimo_acceso = UTC_TIMESTAMP() WHERE id_cuenta = ?')
        ->execute([$account['id_cuenta']]);
    session_regenerate_id(true);
    $_SESSION = ['account' => $account['id_cuenta'], 'role' => $account['nombre_rol']];
    unset($account['contrasena_hash']);
    return $account;
}
