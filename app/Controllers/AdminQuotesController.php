<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/AdminQuoteRepository.php';
require_once __DIR__ . '/../Models/AppointmentRepository.php';
require_once __DIR__ . '/../Services/AdminQuoteService.php';
require_once __DIR__ . '/../Services/ArtistAvailabilityService.php';
require_once __DIR__ . '/../Services/QuoteValidityPolicy.php';
require_once __DIR__ . '/../Services/AppointmentEmailService.php';
require_once __DIR__ . '/../Services/AdminAppointmentService.php';

function adminQuoteId(mixed $value): ?int
{
    return is_scalar($value) && ctype_digit((string) $value) && (int) $value > 0
        ? (int) $value
        : null;
}

function adminAppointmentId(mixed $value): ?int
{
    return is_scalar($value) && ctype_digit((string) $value) && (int) $value > 0
        ? (int) $value
        : null;
}

function adminQuoteImageUrl(mixed $value): ?string
{
    if (!is_string($value)) {
        return null;
    }

    $path = trim(str_replace('\\', '/', $value));

    if ($path === '' || str_contains($path, '../')) {
        return null;
    }

    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    return '../' . ltrim($path, '/');
}

$database = conectarBaseDatos();
$quoteRepository = new AdminQuoteRepository($database);
$availabilityService = new ArtistAvailabilityService($database);
$validityPolicy = new QuoteValidityPolicy();
$quoteService = new AdminQuoteService($quoteRepository, $availabilityService, $validityPolicy);
$appointmentRepository = new AppointmentRepository($database);
$appointmentEmailService = new AppointmentEmailService($appointmentRepository);
$appointmentService = new AdminAppointmentService($appointmentRepository, $appointmentEmailService, $availabilityService);
$quoteRepository->expireSentQuotes();
$quoteId = adminQuoteId($_GET['id'] ?? null);
$mode = $quoteId === null ? 'list' : 'view';
$quote = null;
$appointments = [];
$artistSchedule = [];
$artistUnavailablePeriods = [];
$availableAppointmentSlots = [];
$error = null;
$notice = $_SESSION['admin_quote_notice'] ?? null;
$warning = $_SESSION['admin_quote_warning'] ?? null;
$appointmentForm = [];
$automaticExpiration = null;
$validityIssue = null;
unset($_SESSION['admin_quote_notice'], $_SESSION['admin_quote_warning']);

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();
        $postedQuoteId = adminQuoteId($_POST['id_cotizacion'] ?? null);

        $action = is_scalar($_POST['action'] ?? null) ? (string) $_POST['action'] : '';

        if ($postedQuoteId === null) {
            throw new DomainException('La acción solicitada no es válida.');
        }

        if ($action === 'save') {
            $quoteService->update($postedQuoteId, (int) $account['id_cuenta'], $_POST);
            $_SESSION['admin_quote_notice'] = 'La propuesta fue enviada al cliente y quedó bloqueada hasta que responda.';
        } elseif ($action === 'schedule_appointment') {
            $createdAppointments = $appointmentService->schedule($postedQuoteId, (int) $account['id_cuenta'], $_POST);
            $_SESSION['admin_quote_notice'] = count($createdAppointments) . ' cita(s) fueron creadas como pendientes. Todavía no se envió ningún correo.';
        } elseif ($action === 'confirm_appointment') {
            $appointmentId = adminAppointmentId($_POST['id_cita'] ?? null);

            if ($appointmentId === null) {
                throw new DomainException('La cita indicada no es válida.');
            }

            $result = $appointmentService->confirm($postedQuoteId, $appointmentId, (int) $account['id_cuenta']);

            if ($result['sent']) {
                $_SESSION['admin_quote_notice'] = $result['message'];
            } else {
                $_SESSION['admin_quote_warning'] = $result['message'];
            }
        } elseif ($action === 'retry_appointment_email') {
            $appointmentId = adminAppointmentId($_POST['id_cita'] ?? null);

            if ($appointmentId === null) {
                throw new DomainException('La cita indicada no es válida.');
            }

            $result = $appointmentService->retryEmail($postedQuoteId, $appointmentId);

            if ($result['sent']) {
                $_SESSION['admin_quote_notice'] = $result['message'];
            } else {
                $_SESSION['admin_quote_warning'] = $result['message'];
            }
        } elseif ($action === 'cancel_appointment') {
            $appointmentId = adminAppointmentId($_POST['id_cita'] ?? null);

            if ($appointmentId === null) {
                throw new DomainException('La cita indicada no es válida.');
            }

            $appointmentService->cancelPending($postedQuoteId, $appointmentId, (int) $account['id_cuenta']);
            $_SESSION['admin_quote_notice'] = 'La cita pendiente fue cancelada. No se envió ningún correo.';
        } else {
            throw new DomainException('La acción solicitada no es válida.');
        }

        header('Location: cotizaciones.php?id=' . $postedQuoteId, true, 303);
        exit;
    }
} catch (DomainException $exception) {
    $error = $exception->getMessage();
    $quoteId = adminQuoteId($_POST['id_cotizacion'] ?? null) ?? $quoteId;
    $mode = $quoteId === null ? 'list' : 'view';
} catch (PDOException $exception) {
    error_log('Administración de cotizaciones: ' . $exception->getMessage());
    $databaseCode = (int) ($exception->errorInfo[1] ?? 0);
    $driverMessage = (string) ($exception->errorInfo[2] ?? '');
    $error = match ($databaseCode) {
        1452 => 'El cliente o artista seleccionado ya no existe.',
        1644 => $driverMessage !== '' ? $driverMessage : 'La cita no cumple las reglas de la agenda.',
        1062 => 'Ya existe un registro para esa sesión o confirmación.',
        default => 'No fue posible actualizar la cotización o la cita.',
    };
}

if ($mode === 'view' && $quoteId !== null) {
    $quote = $quoteRepository->find($quoteId);

    if ($quote !== null && $_SERVER['REQUEST_METHOD'] !== 'POST' && $quote['estado'] === 'solicitada') {
        $quoteRepository->markUnderReview($quoteId, (int) $account['id_cuenta']);
        $quote = $quoteRepository->find($quoteId);
    }

    if ($quote === null) {
        $mode = 'list';
        $error = 'La cotización solicitada ya no existe.';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $error !== null && ($_POST['action'] ?? '') === 'save') {
        $quote = array_merge($quote, $_POST);
    }

    if ($quote !== null) {
        if (!empty($quote['fecha_vencimiento'])) {
            $automaticExpiration = new DateTimeImmutable((string) $quote['fecha_vencimiento'], new DateTimeZone('UTC'));
        } elseif (!empty($quote['fecha_preferida'])) {
            try {
                $automaticExpiration = $validityPolicy->expirationFor((string) $quote['fecha_preferida']);
            } catch (DomainException $exception) {
                $validityIssue = $exception->getMessage();
            }
        }

        $appointments = $appointmentRepository->forQuote($quoteId);

        if ($quote['id_artista'] !== null) {
            $artistSchedule = $appointmentRepository->weeklyScheduleForArtist((int) $quote['id_artista']);
            $availabilityStart = new DateTimeImmutable('today', new DateTimeZone('America/Costa_Rica'));
            $availabilityEnd = $availabilityStart->modify('+180 days');
            $artistUnavailablePeriods = $appointmentRepository->unavailablePeriodsForArtist(
                (int) $quote['id_artista'],
                $availabilityStart->format('Y-m-d H:i:s'),
                $availabilityEnd->format('Y-m-d H:i:s')
            );
            $availableAppointmentSlots = $availabilityService->availableSlotsForArtist(
                (int) $quote['id_artista'],
                max(15, (int) ($quote['duracion_estimada_minutos'] ?? 120)),
                180
            );
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error !== null && ($_POST['action'] ?? '') === 'schedule_appointment') {
            $appointmentForm = $_POST;
        }
    }
}

$searchValue = $_GET['buscar'] ?? '';
$search = is_string($searchValue) ? trim($searchValue) : '';
$search = function_exists('mb_substr') ? mb_substr($search, 0, 100) : substr($search, 0, 100);
$allowedStatuses = ['solicitada', 'en_revision', 'enviada', 'aceptada', 'rechazada', 'vencida', 'cancelada'];
$statusValue = $_GET['estado'] ?? '';
$status = is_string($statusValue) && in_array($statusValue, $allowedStatuses, true) ? $statusValue : '';
$viewValue = $_GET['vista'] ?? 'active';
$archiveView = is_string($viewValue) && in_array($viewValue, ['active', 'scheduled', 'closed'], true) ? $viewValue : 'active';
$quotes = $quoteRepository->listing($search, $status, $archiveView);
$summary = $quoteRepository->summary();
$artists = $quoteRepository->artists();

if ($quote !== null && !empty($quote['fecha_preferida'])) {
    $availableRows = $availabilityService->availableArtistsOnDate(
        (string) $quote['fecha_preferida'],
        max(60, (int) ($quote['duracion_estimada_minutos'] ?? 60))
    );
    $availableArtistIds = array_map(static fn(array $artist): int => (int) $artist['id_artista'], $availableRows);
    $artists = array_values(array_filter(
        $artists,
        static fn(array $artist): bool => in_array((int) $artist['id_artista'], $availableArtistIds, true)
            || (int) $artist['id_artista'] === (int) ($quote['id_artista'] ?? 0)
    ));
}
