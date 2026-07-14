<?php

declare(strict_types=1);

use Portfolio\Support\Config;
use Portfolio\Support\CurlTransport;
use Portfolio\Support\FileRateLimiter;
use Portfolio\Support\KadiClient;
use Portfolio\Support\SupportController;
use Portfolio\Support\SupportService;
require __DIR__ . '/src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

function respond(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function requestOrigin(): ?string
{
    $origin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
    return $origin !== '' ? rtrim($origin, '/') : null;
}

function sameOrigin(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
}

try {
    $config = Config::fromEnvironment();
} catch (Throwable) {
    respond(503, ['message' => 'The payment service has not been configured.']);
}

$origin = requestOrigin();
if ($origin !== null) {
    $allowedOrigins = array_filter([sameOrigin(), $config->frontendUrl]);
    $originAllowed = false;
    foreach ($allowedOrigins as $allowedOrigin) {
        if (hash_equals(rtrim((string) $allowedOrigin, '/'), $origin)) {
            $originAllowed = true;
            break;
        }
    }

    if (!$originAllowed) {
        respond(403, ['message' => 'This origin is not allowed.']);
    }

    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Max-Age: 600');
    http_response_code(204);
    exit;
}

$requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
$scriptDirectory = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/api/index.php'))), '/');
if ($scriptDirectory !== '' && str_starts_with($requestPath, $scriptDirectory)) {
    $requestPath = substr($requestPath, strlen($scriptDirectory)) ?: '/';
}

$controller = new SupportController(new SupportService(
    $config,
    new KadiClient($config, new CurlTransport()),
    new FileRateLimiter($config->rateLimitDirectory),
));
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$ipAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

if ($method === 'POST' && $requestPath === '/support/coffee') {
    $contentType = strtolower(trim(explode(';', (string) ($_SERVER['CONTENT_TYPE'] ?? ''))[0]));
    if ($contentType !== 'application/json') {
        respond(415, ['message' => 'A JSON request body is required.']);
    }

    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($contentLength > 4096) {
        respond(413, ['message' => 'The request is too large.']);
    }

    $input = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($input)) {
        respond(400, ['message' => 'A valid JSON request body is required.']);
    }

    [$status, $body] = $controller->initiate($input, $ipAddress);
    respond($status, $body);
}

if ($method === 'GET' && preg_match('#^/support/coffee/([^/]+)$#', $requestPath, $matches) === 1) {
    [$status, $body] = $controller->status(rawurldecode($matches[1]), $ipAddress);
    respond($status, $body);
}

respond(404, ['message' => 'API endpoint not found.']);
