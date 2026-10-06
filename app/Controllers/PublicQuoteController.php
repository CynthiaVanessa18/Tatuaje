<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/QuoteRepository.php';
require_once __DIR__ . '/../Services/PublicQuoteService.php';

$database = conectarBaseDatos();
$quoteRepository = new QuoteRepository($database);
$quoteService = new PublicQuoteService($quoteRepository, dirname(__DIR__, 2));
$categories = $quoteRepository->categories();
$artists = $quoteRepository->artists();
$account = currentAccount();
$clientProfile = null;

if (($account['nombre_rol'] ?? null) === 'cliente') {
    $clientProfile = $quoteRepository->clientForAccount((int) $account['id_cuenta']);
}

$artistReference = is_scalar($_GET['artista'] ?? null) ? trim((string) $_GET['artista']) : '';
$selectedArtistId = $artistReference === '' ? null : $quoteRepository->resolveArtistReference($artistReference);
$error = null;
$notice = $_SESSION['public_quote_notice'] ?? null;
$submittedQuoteId = $_SESSION['public_quote_id'] ?? null;
unset($_SESSION['public_quote_notice'], $_SESSION['public_quote_id']);

$form = [
    'nombre_contacto' => $clientProfile === null
        ? ''
        : trim($clientProfile['nombre'] . ' ' . $clientProfile['apellidos']),
    'correo_contacto' => $clientProfile['correo'] ?? '',
    'telefono_contacto' => $clientProfile['telefono'] ?? '',
    'id_artista' => $selectedArtistId ?? '',
    'id_categoria' => '',
    'descripcion_idea' => '',
    'zona_cuerpo' => '',
    'ancho_cm' => '',
    'alto_cm' => '',
    'tamano_descripcion' => '',
    'a_color' => '',
    'fecha_preferida' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = array_merge($form, $_POST);

    try {
        verifyCsrf();

        if ($clientProfile === null) {
            throw new DomainException('Tu cuenta necesita un perfil de cliente antes de solicitar una cotización.');
        }

        $lastSubmission = (int) ($_SESSION['last_public_quote_at'] ?? 0);

        if ($lastSubmission > 0 && time() - $lastSubmission < 20) {
            throw new DomainException('Espera unos segundos antes de enviar otra solicitud.');
        }

        $submission = $_POST;
        $submission['correo_contacto'] = $clientProfile['correo'];

        $quoteId = $quoteService->submit(
            $submission,
            $_FILES,
            (int) $clientProfile['id_cliente']
        );

        $_SESSION['last_public_quote_at'] = time();
        $_SESSION['public_quote_notice'] = 'Tu solicitud fue recibida. Podrás seguir la respuesta desde tu cuenta; el correo se enviará únicamente cuando una cita quede confirmada.';
        $_SESSION['public_quote_id'] = $quoteId;
        header('Location: index.php?enviada=1', true, 303);
        exit;
    } catch (DomainException $exception) {
        $error = $exception->getMessage();
    } catch (RuntimeException $exception) {
        error_log('Referencias de cotización: ' . $exception->getMessage());
        $error = 'No fue posible guardar las imágenes de referencia. Intenta nuevamente.';
    } catch (PDOException $exception) {
        error_log('Cotización pública: ' . $exception->getMessage());
        $databaseCode = (int) ($exception->errorInfo[1] ?? 0);
        $error = $databaseCode === 1452
            ? 'El artista o estilo seleccionado ya no está disponible. Recarga la página.'
            : 'No fue posible enviar la solicitud. Intenta nuevamente.';
    }
}
