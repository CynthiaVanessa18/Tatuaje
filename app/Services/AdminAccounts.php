<?php
declare(strict_types=1);

final class AdminAccounts
{
    public function __construct(private PDO $db, private int $actor) {}
    private function authorize(): void
    {
        $q=$this->db->prepare("SELECT c.id_cuenta FROM cuentas c JOIN roles r ON r.id_rol=c.id_rol WHERE c.id_cuenta=? AND c.estado='activo' AND r.nombre_rol='administrador' AND r.activo=1");
        $q->execute([$this->actor]);
        if (!$q->fetchColumn()) throw new DomainException('Solo un administrador puede gestionar cuentas.');
    }
    public function find(int $id): array
    {
        $this->authorize();
        $q=$this->db->prepare("SELECT c.id_cuenta,c.usuario,c.correo,c.estado,r.nombre_rol AS rol,cl.nombre,cl.apellidos,cl.telefono FROM cuentas c JOIN roles r ON r.id_rol=c.id_rol LEFT JOIN clientes cl ON cl.id_cuenta=c.id_cuenta WHERE c.id_cuenta=? AND r.nombre_rol IN ('administrador','cliente')");
        $q->execute([$id]);return $q->fetch()?:throw new DomainException('La cuenta no está disponible para esta gestión.');
    }
    public function listing(string $search,string $role,string $state,int $page): array
    {
        $this->authorize();
        $where="r.nombre_rol IN ('administrador','cliente')";$args=[];
        if ($search!=='') { $where.=' AND (c.usuario LIKE ? OR c.correo LIKE ?)';$args[]='%'.$search.'%';$args[]='%'.$search.'%'; }
        if (in_array($role,['administrador','cliente'],true)) { $where.=' AND r.nombre_rol=?';$args[]=$role; }
        if (in_array($state,['activo','inactivo','bloqueado'],true)) { $where.=' AND c.estado=?';$args[]=$state; }
        $from=' FROM cuentas c JOIN roles r ON r.id_rol=c.id_rol WHERE '.$where;
        $q=$this->db->prepare('SELECT COUNT(*)'.$from);$q->execute($args);$total=(int)$q->fetchColumn();
        $page=max(1,min($page,max(1,(int)ceil($total/20))));
        $q=$this->db->prepare('SELECT c.id_cuenta,c.usuario,c.correo,c.estado,r.nombre_rol AS rol'.$from.' ORDER BY c.id_cuenta DESC LIMIT 20 OFFSET '.(($page-1)*20));$q->execute($args);
        return ['rows'=>$q->fetchAll(),'total'=>$total,'page'=>$page];
    }
    public function save(array $data,?int $id=null): int
    {
        $this->authorize();$fields=[];
        foreach (['usuario'=>80,'correo'=>254,'rol'=>40,'estado'=>20,'nombre'=>100,'apellidos'=>150,'telefono'=>25] as $key=>$max) {
            $v=$data[$key]??'';
            if (!is_string($v) || mb_strlen(trim($v))>$max) throw new DomainException('Revisa los datos de la cuenta.');
            $fields[$key]=trim($v);
        }
        if ($fields['usuario']==='' || !filter_var($fields['correo'],FILTER_VALIDATE_EMAIL)) throw new DomainException('Indica un usuario y un correo válido.');
        if (!in_array($fields['rol'],['administrador','cliente'],true) || !in_array($fields['estado'],['activo','inactivo','bloqueado'],true)) throw new DomainException('Selecciona administrador o cliente y un estado válido.');
        if ($fields['rol']==='cliente' && ($fields['nombre']==='' || $fields['apellidos']==='')) throw new DomainException('Completa el nombre y los apellidos del cliente.');
        $password=$data['clave']??'';
        if (!is_string($password) || (!$id && $password==='') || ($password!=='' && (strlen($password)<12 || strlen($password)>72 || str_contains($password,"\0")))) throw new DomainException('La contraseña debe tener entre 12 y 72 bytes.');
        if ($password!=='' && $password!==($data['confirmar_clave']??null)) throw new DomainException('Las contraseñas no coinciden.');
        $this->db->beginTransaction();
        try {
            // Serializa los cambios de administradores para conservar el acceso.
            $roles=$this->db->query("SELECT id_rol,nombre_rol,activo FROM roles WHERE nombre_rol IN ('administrador','cliente') ORDER BY id_rol FOR UPDATE")->fetchAll();
            $role=null;foreach ($roles as $r) if ($r['nombre_rol']===$fields['rol'] && $r['activo']) $role=(int)$r['id_rol'];
            if (!$role) throw new DomainException('El rol seleccionado no está habilitado.');
            $this->authorize();
            $old=null;
            if ($id) {
                $old=$this->find($id);
                if ($id===$this->actor && ($fields['rol']!=='administrador' || $fields['estado']!=='activo')) throw new DomainException('No puedes quitarte el acceso de administrador.');
                if ($old['rol']==='administrador' && $old['estado']==='activo' && ($fields['rol']!=='administrador' || $fields['estado']!=='activo')) {
                    $count=(int)$this->db->query("SELECT COUNT(*) FROM cuentas c JOIN roles r ON r.id_rol=c.id_rol WHERE c.estado='activo' AND r.nombre_rol='administrador' AND r.activo=1")->fetchColumn();
                    if ($count<=1) throw new DomainException('Debe quedar al menos un administrador activo.');
                }
                $sql='UPDATE cuentas SET usuario=?,correo_verificado=IF(correo=?,correo_verificado,0),correo=?,id_rol=?,estado=?';
                $args=[$fields['usuario'],$fields['correo'],$fields['correo'],$role,$fields['estado']];
                if ($password!=='') { $sql.=',contrasena_hash=?';$args[]=password_hash($password,PASSWORD_DEFAULT); }
                $sql.=' WHERE id_cuenta=?';$args[]=$id;$this->db->prepare($sql)->execute($args);
            } else {
                $this->db->prepare('INSERT INTO cuentas (usuario,correo,id_rol,estado,contrasena_hash) VALUES (?,?,?,?,?)')->execute([$fields['usuario'],$fields['correo'],$role,$fields['estado'],password_hash($password,PASSWORD_DEFAULT)]);
                $id=(int)$this->db->lastInsertId();
            }
            if ($fields['rol']==='cliente') $this->db->prepare('INSERT INTO clientes (id_cuenta,nombre,apellidos,telefono) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE nombre=VALUES(nombre),apellidos=VALUES(apellidos),telefono=VALUES(telefono)')->execute([$id,$fields['nombre'],$fields['apellidos'],$fields['telefono']?:null]);
            $this->db->commit();return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if ($e instanceof PDOException && ($e->errorInfo[1]??null)===1062) throw new DomainException('El usuario o correo ya está registrado.');
            throw $e;
        }
    }
}
