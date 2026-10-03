param([string]$BaseUrl='http://127.0.0.1:8018')
$ErrorActionPreference='Stop'
if (!$env:TEST_ROLE_PASSWORD) { throw 'Define TEST_ROLE_PASSWORD para las cuentas role_administrador, role_secretaria, role_artista y role_cliente de una base de prueba.' }
$routes=@{administrador='panel/administrador.php';secretaria='panel/secretaria.php';artista='panel/artista.php';cliente='panel/cliente.php'}
$checks=0
foreach ($role in $routes.Keys) {
    $session=New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $login=Invoke-WebRequest "$BaseUrl/auth/login.php" -WebSession $session
    $token=[regex]::Match($login.Content,'name="csrf" value="([^"]+)"').Groups[1].Value
    $bad=Invoke-WebRequest "$BaseUrl/auth/login.php" -WebSession $session -Method Post -Body @{csrf=$token;usuario="role_$role";clave='incorrecta'}
    if ($bad.Content -notmatch 'Usuario o contraseña incorrectos') { throw "Contraseña incorrecta aceptada: $role" }
    $result=Invoke-WebRequest "$BaseUrl/auth/login.php" -WebSession $session -Method Post -Body @{csrf=$token;usuario="role_$role";clave=$env:TEST_ROLE_PASSWORD;rol='administrador'}
    if ($result.BaseResponse.RequestMessage.RequestUri.AbsolutePath -ne "/$($routes[$role])") { throw "Destino incorrecto: $role" }
    if ($result.Content -match 'Fatal error|Warning:|Conexión pendiente') { throw "Error de página: $role" }
    $checks+=2
    foreach ($other in $routes.Values) {
        $protected=Invoke-WebRequest "$BaseUrl/$other" -WebSession $session
        if ($protected.BaseResponse.RequestMessage.RequestUri.AbsolutePath -ne "/$($routes[$role])") { throw "Acceso cruzado permitido: $role -> $other" }
        $checks++
    }
    $back=Invoke-WebRequest "$BaseUrl/auth/login.php" -WebSession $session
    if ($back.BaseResponse.RequestMessage.RequestUri.AbsolutePath -ne "/$($routes[$role])") { throw "Sesión no recordada: $role" }
    $token=[regex]::Match($result.Content,'name="csrf" value="([^"]+)"').Groups[1].Value
    $null=Invoke-WebRequest "$BaseUrl/auth/logout.php" -WebSession $session -Method Post -Body @{csrf=$token}
    $out=Invoke-WebRequest "$BaseUrl/$($routes[$role])" -WebSession $session
    if ($out.BaseResponse.RequestMessage.RequestUri.AbsolutePath -ne '/auth/login.php') { throw "Sesión no cerrada: $role" }
    $checks+=2
}
"OK: $checks comprobaciones de login, redirección, aislamiento de roles y cierre de sesión."
