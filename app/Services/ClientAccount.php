<?php
declare(strict_types=1);
final class ClientAccount
{
    public function __construct(private PDO $db,private int $id) {}
    public function load(): array
    {
        $q=$this->db->prepare("SELECT c.usuario,c.correo,cl.nombre,cl.apellidos,cl.telefono FROM cuentas c JOIN roles r ON r.id_rol=c.id_rol LEFT JOIN clientes cl ON cl.id_cuenta=c.id_cuenta WHERE c.id_cuenta=? AND c.estado='activo' AND r.nombre_rol='cliente' AND r.activo=1");
        $q->execute([$this->id]);return $q->fetch()?:throw new DomainException('La cuenta de cliente no está disponible.');
    }
    public function save(array $data): void
    {
        $fields=[];
        foreach (['usuario'=>80,'correo'=>254,'nombre'=>100,'apellidos'=>150,'telefono'=>25] as $key=>$max) {
            $v=$data[$key]??'';
            if (!is_string($v) || mb_strlen(trim($v))>$max) throw new DomainException('Revisa los datos de tu cuenta.');
            $fields[$key]=trim($v);
        }
        if ($fields['usuario']==='' || $fields['nombre']==='' || $fields['apellidos']==='' || !filter_var($fields['correo'],FILTER_VALIDATE_EMAIL)) throw new DomainException('Completa tu nombre, apellidos, usuario y un correo válido.');
        $password=$data['nueva_clave']??'';$current=$data['clave_actual']??'';$confirmation=$data['confirmar_clave']??'';
        if (!is_string($password) || !is_string($current) || !is_string($confirmation)) throw new DomainException('Contraseña inválida.');
        if ($password!=='' && (strlen($password)<12 || strlen($password)>72 || str_contains($password,"\0") || $password!==$confirmation)) throw new DomainException('La nueva contraseña debe tener entre 12 y 72 bytes y coincidir con su confirmación.');
        if ($password==='' && $confirmation!=='') throw new DomainException('Ingresa la nueva contraseña y su confirmación.');
        $this->db->beginTransaction();
        try {
            $q=$this->db->prepare('SELECT usuario,correo,contrasena_hash FROM cuentas WHERE id_cuenta=? FOR UPDATE');$q->execute([$this->id]);$old=$q->fetch();
            $this->load();
            if (($fields['usuario']!==$old['usuario'] || $fields['correo']!==$old['correo'] || $password!=='') && !password_verify($current,$old['contrasena_hash'])) throw new DomainException('Ingresa tu contraseña actual para cambiar el usuario, correo o contraseña.');
            $this->db->prepare('UPDATE cuentas SET usuario=?,correo_verificado=IF(correo=?,correo_verificado,0),correo=?,contrasena_hash=? WHERE id_cuenta=?')->execute([$fields['usuario'],$fields['correo'],$fields['correo'],$password!==''?password_hash($password,PASSWORD_DEFAULT):$old['contrasena_hash'],$this->id]);
            $this->db->prepare('INSERT INTO clientes (id_cuenta,nombre,apellidos,telefono) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE nombre=VALUES(nombre),apellidos=VALUES(apellidos),telefono=VALUES(telefono)')->execute([$this->id,$fields['nombre'],$fields['apellidos'],$fields['telefono']?:null]);
            $this->db->commit();
            if ($password!=='' && PHP_SAPI!=='cli') session_regenerate_id(true);
        } catch (Throwable $ex) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if ($ex instanceof PDOException && ($ex->errorInfo[1]??null)===1062) throw new DomainException('Ese usuario o correo ya está registrado.');
            throw $ex;
        }
    }
}
