param([string]$BaseUrl='http://127.0.0.1:8018')
$ErrorActionPreference='Stop'
if (!$env:TEST_ADMIN_USER -or !$env:TEST_ADMIN_PASSWORD) { throw 'Define TEST_ADMIN_USER y TEST_ADMIN_PASSWORD de la base de prueba.' }
$loginPage=Invoke-WebRequest "$BaseUrl/login.php" -SessionVariable tattooSession
$csrfValue=[regex]::Match($loginPage.Content,'name="csrf" value="([^"]+)"').Groups[1].Value
$panel=Invoke-WebRequest "$BaseUrl/login.php" -WebSession $tattooSession -Method Post -Body @{csrf=$csrfValue;usuario=$env:TEST_ADMIN_USER;clave=$env:TEST_ADMIN_PASSWORD}
if ($panel.Content -notmatch 'PANEL ADMINISTRATIVO') { throw 'Login fallido' }
$moduleNames=@('categorias','especialidades','calificaciones','resumen_calificaciones','promociones','planes','membresias','beneficios','planes_beneficios','tarjetas','movimientos','saldos_tarjetas','categorias_productos','productos','imagenes','ventas','detalle')
foreach ($moduleName in $moduleNames) {
    $response=Invoke-WebRequest "$BaseUrl/index.php?module=$moduleName" -WebSession $tattooSession
    if ($response.Content -match 'Fatal error|Warning:|Conexión pendiente') { throw "Error en $moduleName" }
    if ($moduleName -notin @('movimientos','resumen_calificaciones','saldos_tarjetas')) {
        $formPage=Invoke-WebRequest "$BaseUrl/index.php?module=$moduleName&mode=create" -WebSession $tattooSession
        if ($formPage.Content -notmatch 'Guardar registro') { throw "Formulario faltante $moduleName" }
    }
}
$csrfValue=[regex]::Match($panel.Content,'name="csrf" value="([^"]+)"').Groups[1].Value
$invalid=Invoke-WebRequest "$BaseUrl/index.php?module=categorias" -WebSession $tattooSession -Method Post -Body @{csrf='incorrecto';action='create';nombre='No insertar';activo='1'}
if ($invalid.Content -notmatch 'sesión del formulario venció') { throw 'No rechaza CSRF' }
$created=Invoke-WebRequest "$BaseUrl/index.php?module=categorias" -WebSession $tattooSession -Method Post -Body @{csrf=$csrfValue;action='create';nombre='HTTP smoke';descripcion='<script>alert(1)</script>';activo='1'}
if ($created.Content -notmatch 'Registro guardado correctamente' -or $created.Content -notmatch '&lt;script&gt;') { throw 'Alta o escape fallido' }
$editKey=[regex]::Match($created.Content,'id_categoria=(\d+)&amp;mode=edit').Groups[1].Value
$updated=Invoke-WebRequest "$BaseUrl/index.php?module=categorias" -WebSession $tattooSession -Method Post -Body @{csrf=$csrfValue;action='update';'key[id_categoria]'=$editKey;nombre='HTTP editado';descripcion='Verificado';activo='1'}
if ($updated.Content -notmatch 'HTTP editado' -or $updated.Content -notmatch 'Registro guardado correctamente') { throw 'Edición fallida' }
$deleted=Invoke-WebRequest "$BaseUrl/index.php?module=categorias" -WebSession $tattooSession -Method Post -Body @{csrf=$csrfValue;action='delete';'key[id_categoria]'=$editKey}
if ($deleted.Content -notmatch 'Registro eliminado correctamente') { throw 'Eliminar fallido' }
$null=Invoke-WebRequest "$BaseUrl/logout.php" -WebSession $tattooSession -Method Post -Body @{csrf=$csrfValue}
$protected=Invoke-WebRequest "$BaseUrl/index.php" -WebSession $tattooSession
if ($protected.Content -notmatch 'Ingresa con tu cuenta') { throw 'Acceso sin autenticar' }
'HTTP OK: login, 17 listados, 14 formularios, CSRF, alta/edición/baja, escape y cierre de sesión.'
