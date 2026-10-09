<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/TestimonialRepository.php';
require_once __DIR__ . '/../Services/TestimonialService.php';

$testimonialRepository = new TestimonialRepository(conectarBaseDatos());
$testimonialService = new TestimonialService($testimonialRepository);
$clientProfile = $testimonialRepository->clientForAccount((int) $account['id_cuenta']);
$profileMissing = $clientProfile === null;
$testimonialError = null;
$testimonialNotice = $_SESSION['client_testimonial_notice'] ?? null;
unset($_SESSION['client_testimonial_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrf();

        if ($clientProfile === null) {
            throw new DomainException('Tu cuenta todavía no tiene un perfil de cliente vinculado.');
        }

        $testimonialService->submit((int) $clientProfile['id_cliente'], $_POST);
        $_SESSION['client_testimonial_notice'] = 'Tu testimonio fue recibido. El estudio lo revisará antes de publicarlo.';
        header('Location: mis-testimonios.php', true, 303);
        exit;
    } catch (DomainException $exception) {
        $testimonialError = $exception->getMessage();
    } catch (PDOException $exception) {
        error_log('Testimonio del cliente: ' . $exception->getMessage());
        $testimonialError = (int) ($exception->errorInfo[1] ?? 0) === 1062
            ? 'Esta cita ya tiene un testimonio registrado.'
            : 'No fue posible guardar el testimonio. Intenta nuevamente.';
    }
}

$eligibleAppointments = $clientProfile === null
    ? []
    : $testimonialRepository->eligibleAppointments((int) $clientProfile['id_cliente']);
$clientTestimonials = $clientProfile === null
    ? []
    : $testimonialRepository->forClient((int) $clientProfile['id_cliente']);
$clientLastName = trim((string) ($clientProfile['apellidos'] ?? ''));
$clientLastInitial = function_exists('mb_substr')
    ? mb_substr($clientLastName, 0, 1)
    : substr($clientLastName, 0, 1);
$defaultPublicName = $clientProfile === null
    ? (string) $account['usuario']
    : trim((string) $clientProfile['nombre']) . ' ' . $clientLastInitial . '.';
