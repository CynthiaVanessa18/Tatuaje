<?php
declare(strict_types=1);

final class ClientRegistration
{
    public static function register(PDO $db, array $data): int
    {
        $fields=[];
        foreach (['usuario'=>80,'correo'=>254,'nombre'=>100,'apellidos'=>150,'telefono'=>25] as $key=>$max) {
            $value=$data[$key]??'';
            if (!is_string($value) || mb_strlen(trim($value))>$max) throw new DomainException('Revisa los datos del registro.');
            $fields[$key]=trim($value);
        }
        if ($fields['usuario']==='' || $fields['nombre']==='' || $fields['apellidos']==='' || !filter_var($fields['correo'],FILTER_VALIDATE_EMAIL)) throw new DomainException('Completa tu nombre, apellidos, usuario y un correo válido.');
        $password=$data['clave']??null;
        if (!is_string($password) || strlen($password)<12 || strlen($password)>72 || str_contains($password,"\0")) throw new DomainException('La contraseña debe tener entre 12 y 72 caracteres; las tildes y símbolos pueden ocupar más de un byte.');
        if ($password!==($data['confirmar_clave']??null)) throw new DomainException('Las contraseñas no coinciden.');
        $db->beginTransaction();
        try {
            $role=$db->query("SELECT id_rol FROM roles WHERE nombre_rol='cliente' AND activo=1")->fetchColumn();
            if (!$role) throw new DomainException('El registro de clientes no está disponible en este momento.');
            $db->prepare("INSERT INTO cuentas (id_rol,usuario,correo,contrasena_hash,estado) VALUES (?,?,?,?,'activo')")
                ->execute([$role,$fields['usuario'],$fields['correo'],password_hash($password,PASSWORD_DEFAULT)]);
            $id=(int)$db->lastInsertId();
            $db->prepare('INSERT INTO clientes (id_cuenta,nombre,apellidos,telefono) VALUES (?,?,?,?)')->execute([$id,$fields['nombre'],$fields['apellidos'],$fields['telefono']?:null]);
            $db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            if ($e instanceof PDOException && ($e->errorInfo[1]??null)===1062) throw new DomainException('El usuario o correo ya está registrado. Usa otro o inicia sesión.');
            throw $e;
        }
    }
}
