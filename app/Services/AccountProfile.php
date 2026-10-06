<?php
declare(strict_types=1);

final class AccountProfile
{
    public function __construct(private PDO $db, private int $accountId) {}

    public function load(): array
    {
        $query = $this->db->prepare('SELECT c.usuario, c.correo, c.fecha_creacion, c.ultimo_acceso,
            p.nombre, p.telefono, p.biografia, p.foto_url FROM cuentas c
            LEFT JOIN perfiles_cuentas p ON p.id_cuenta=c.id_cuenta WHERE c.id_cuenta=?');
        $query->execute([$this->accountId]);
        return $query->fetch() ?: [];
    }

    private static function field(array $data, string $key, int $max): string
    {
        $value = $data[$key] ?? '';
        if (!is_string($value)) throw new DomainException('Datos del perfil inválidos.');
        $value = trim($value);
        if (mb_strlen($value) > $max) throw new DomainException('El campo '.label($key).' es demasiado largo.');
        return $value;
    }

    public function save(array $data, ?array $file): void
    {
        $username = self::field($data, 'usuario', 80);
        $email = self::field($data, 'correo', 254);
        $name = self::field($data, 'nombre', 120);
        $phone = self::field($data, 'telefono', 30);
        $bio = self::field($data, 'biografia', 1000);
        if ($username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Indica un usuario y un correo electrónico válido.');
        }
        $password = $data['nueva_clave'] ?? '';
        $confirmation = $data['confirmar_clave'] ?? '';
        $current = $data['clave_actual'] ?? '';
        if (!is_string($password) || !is_string($confirmation) || !is_string($current)) {
            throw new DomainException('Contraseña inválida.');
        }
        if ($password !== '' && (strlen($password) < 12 || strlen($password) > 72 || $password !== $confirmation)) {
            throw new DomainException('La nueva contraseña debe tener entre 12 y 72 bytes y coincidir con la confirmación.');
        }
        $photo = null;
        try {
            $this->db->beginTransaction();
            $query = $this->db->prepare('SELECT correo, contrasena_hash FROM cuentas WHERE id_cuenta=? FOR UPDATE');
            $query->execute([$this->accountId]);
            $account = $query->fetch();
            if (!$account) throw new DomainException('Cuenta no disponible.');
            if (($email !== $account['correo'] || $password !== '') && !password_verify($current, $account['contrasena_hash'])) {
                throw new DomainException('Ingresa tu contraseña actual para cambiar el correo o la contraseña.');
            }
            require_once __DIR__.'/ProductImageUpload.php';
            $photo = ProductImageUpload::save($file, 'perfiles');
            $this->db->prepare('UPDATE cuentas SET usuario=?, correo_verificado=IF(correo=?,correo_verificado,0), correo=?, contrasena_hash=? WHERE id_cuenta=?')
                ->execute([$username, $email, $email, $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : $account['contrasena_hash'], $this->accountId]);
            $this->db->prepare('INSERT INTO perfiles_cuentas (id_cuenta,nombre,telefono,biografia,foto_url) VALUES (?,?,?,?,?)
                ON DUPLICATE KEY UPDATE nombre=VALUES(nombre),telefono=VALUES(telefono),biografia=VALUES(biografia),foto_url=IF(?,NULL,COALESCE(VALUES(foto_url),foto_url))')
                ->execute([$this->accountId,$name,$phone,$bio,$photo,($data['quitar_foto']??'')==='1' && $photo===null ? 1 : 0]);
            $this->db->commit();
            if ($password !== '' && PHP_SAPI !== 'cli') session_regenerate_id(true);
        } catch (Throwable $ex) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if ($photo !== null) ProductImageUpload::discard($photo);
            throw $ex;
        }
    }
}
