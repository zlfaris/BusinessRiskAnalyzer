<?php

declare(strict_types=1);

session_start();

// Handle basic CORS and Preflight checks
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Global header
header('Content-Type: application/json; charset=utf-8');

// Load environment variables manually
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) < 2) continue;
        list($name, $value) = $parts;
        $name = trim($name);
        $value = trim($value);

        putenv(sprintf('%s=%s', $name, $value));
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

loadEnv(__DIR__ . '/../.env');

// Require Composer Autoloader
$autoloaderPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloaderPath)) {
    require_once $autoloaderPath;
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Composer autoloader not found. Please run "composer install".']);
    exit;
}

use App\Controllers\AuthController;
use App\Controllers\AnalysisController;

// Extract Request URI and Method
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Dynamically handle subdirectories and index.php in the URI
$scriptName = $_SERVER['SCRIPT_NAME'];
$baseDir = dirname($scriptName);

// If the URI starts with the full script name (e.g. /BusinessRiskAnalyzer/public/index.php)
if (strpos($requestUri, $scriptName) === 0) {
    $requestUri = substr($requestUri, strlen($scriptName));
} 
// Else if it starts with just the base directory (e.g. /BusinessRiskAnalyzer/public)
elseif (strlen($baseDir) > 1 && strpos($requestUri, $baseDir) === 0) {
    $requestUri = substr($requestUri, strlen($baseDir));
}

$requestUri = rtrim($requestUri, '/') ?: '/';
$requestMethod = $_SERVER['REQUEST_METHOD'];

try {
    $authController = new AuthController();
    $analysisController = new AnalysisController();

    // Map endpoints
    if ($requestUri === '/' && $requestMethod === 'GET') {
        header('Content-Type: text/html; charset=utf-8');
        readfile(__DIR__ . '/index.html');
        exit;
    } elseif ($requestUri === '/api/register' && $requestMethod === 'POST') {
        $authController->register();
        exit;
    } elseif ($requestUri === '/api/login' && $requestMethod === 'POST') {
        $authController->login();
        exit;
    } elseif ($requestUri === '/api/user/me' && $requestMethod === 'GET') {
        $authController->me();
        exit;
    } elseif ($requestUri === '/api/logout' && $requestMethod === 'POST') {
        $authController->logout();
        exit;
    } elseif ($requestUri === '/api/analyze' && $requestMethod === 'POST') {
        $analysisController->runAnalysis();
        exit;
    } elseif ($requestUri === '/api/history' && $requestMethod === 'GET') {
        $analysisController->index();
        exit;
    } elseif (preg_match('#^/api/analyses/(\d+)$#', $requestUri, $matches)) {
        $id = (int)$matches[1];
        if ($requestMethod === 'GET') {
            $analysisController->show($id);
            exit;
        } elseif ($requestMethod === 'PUT') {
            $analysisController->update($id);
            exit;
        } elseif ($requestMethod === 'DELETE') {
            $analysisController->delete($id);
            exit;
        }
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found.']);
        exit;
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error.', 'message' => $e->getMessage()]);
    exit;
}
