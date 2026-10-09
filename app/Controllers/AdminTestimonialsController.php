<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/TestimonialRepository.php';
require_once __DIR__ . '/../Services/TestimonialService.php';

$testimonialRepository = new TestimonialRepository(conectarBaseDatos());
$testimonialService = new TestimonialService($testimonialRepository);
$testimonialError = null;
$testimonialNotice = $_SESSION['admin_testimonial_notice'] ?? null;
unset($_SESSION['admin_testimonial_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrf();
        $_SESSION['admin_testimonial_notice'] = $testimonialService->moderate($_POST);
        header('Location: testimonios.php', true, 303);
        exit;
    } catch (DomainException $exception) {
        $testimonialError = $exception->getMessage();
    } catch (PDOException $exception) {
        error_log('Moderación de testimonios: ' . $exception->getMessage());
        $testimonialError = 'No fue posible guardar la moderación. Intenta nuevamente.';
    }
}

$statusValue = $_GET['estado'] ?? '';
$testimonialStatus = is_string($statusValue)
    && in_array($statusValue, ['pendiente', 'publicado', 'rechazado'], true)
        ? $statusValue
        : '';
$testimonials = $testimonialRepository->adminListing($testimonialStatus);
$testimonialSummary = $testimonialRepository->adminSummary();
