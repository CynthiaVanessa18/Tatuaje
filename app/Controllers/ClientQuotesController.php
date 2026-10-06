<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/QuoteRepository.php';
require_once __DIR__ . '/../Services/ClientQuoteResponseService.php';

function clientQuoteId(mixed $value): ?int
{
    return is_scalar($value) && ctype_digit((string) $value) && (int) $value > 0
        ? (int) $value
        : null;
}

function clientQuoteImageUrl(mixed $value): ?string
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

$quoteRepository = new QuoteRepository(conectarBaseDatos());
$quoteResponseService = new ClientQuoteResponseService($quoteRepository);
$clientProfile = $quoteRepository->clientForAccount((int) $account['id_cuenta']);
$profileMissing = $clientProfile === null;
$quotes = [];
$selectedQuote = null;
$error = null;
$notice = $_SESSION['client_quote_notice'] ?? null;
$responseMessage = is_scalar($_POST['mensaje'] ?? null) ? trim((string) $_POST['mensaje']) : '';
unset($_SESSION['client_quote_notice']);

if (!$profileMissing) {
    $clientId = (int) $clientProfile['id_cliente'];

    try {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            verifyCsrf();
            $postedQuoteId = clientQuoteId($_POST['id_cotizacion'] ?? null);

            if ($postedQuoteId === null) {
                throw new DomainException('La cotización indicada no es válida.');
            }

            $notice = $quoteResponseService->respond(
                $postedQuoteId,
                $clientId,
                (int) $account['id_cuenta'],
                $_POST['accion'] ?? null,
                $_POST['mensaje'] ?? null
            );
            $_SESSION['client_quote_notice'] = $notice;
            header('Location: mis-cotizaciones.php?id=' . $postedQuoteId, true, 303);
            exit;
        }
    } catch (DomainException $exception) {
        $error = $exception->getMessage();
    }

    $quotes = $quoteRepository->quotesForClient($clientId);
    $selectedQuoteId = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
        ? clientQuoteId($_POST['id_cotizacion'] ?? null)
        : clientQuoteId($_GET['id'] ?? null);

    if ($selectedQuoteId !== null) {
        $selectedQuote = $quoteRepository->findForClient($selectedQuoteId, $clientId);

        if ($selectedQuote === null) {
            $error = 'La solicitud indicada no existe o no pertenece a tu cuenta.';
        }
    }
}
