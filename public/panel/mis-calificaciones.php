<?php
declare(strict_types=1);
require_once __DIR__.'/../../app/Core/bootstrap.php';
require_once __DIR__.'/../../app/Services/ClientRatings.php';
try {
    $account=requireRole('cliente');
    $ratingsService=new ClientRatings(conectarBaseDatos(),(int)$account['id_cuenta']);
    $ratingError=null;
    $ratingNotice=$_SESSION['client_rating_notice']??null;
    unset($_SESSION['client_rating_notice']);
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        try {
            verifyCsrf();
            $ratingsService->submit($_POST);
            $_SESSION['client_rating_notice']='Gracias por calificar tu experiencia. Tu valoración está pendiente de revisión para su publicación.';
            header('Location: mis-calificaciones.php',true,303);
            exit;
        } catch (DomainException $error) {
            $ratingError=$error->getMessage();
        } catch (PDOException $error) {
            error_log('Guardar calificación: '.$error->getMessage());
            $ratingError='No se pudo guardar la valoración. Actualiza la página e intenta nuevamente.';
        }
    }
    $ratedAppointments=$ratingsService->appointments();
    require __DIR__.'/../../app/Views/panel/cliente-calificaciones.php';
} catch (PDOException $error) {
    error_log('Calificaciones del cliente: '.$error->getMessage());
    http_response_code(503);
    require __DIR__.'/../../app/Views/unavailable.php';
}
