<?php

declare(strict_types=1);

// Ensure autoloader is loaded
require_once __DIR__ . '/../vendor/autoload.php';

use PayrollApi\Response;
use PayrollApi\Router;

// Handle CORS Preflight
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    http_response_code(204);
    exit;
}

// Add standard CORS header for API Gateway clients
header('Access-Control-Allow-Origin: *');

// Dispatch Router
$router = new Router();
$router->dispatch();
