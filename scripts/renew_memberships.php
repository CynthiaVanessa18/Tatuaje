<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../config/database.php';
require __DIR__.'/../app/Services/MembershipRenewal.php';
MembershipRenewal::process(conectarBaseDatos());
echo "Renovaciones de demostración procesadas.\n";
