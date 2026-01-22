<?php require __DIR__ . '/vendor/autoload.php';

header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type");
header('Content-Type: application/json');

use App\Lib\App;
use App\Lib\Request;
use App\Lib\Response;
use App\Middleware\BodyParser;
use App\Lib\Logger;

if (file_exists(__DIR__.'/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

set_error_handler(function ($severity, $message, $file, $line) {
    if ($severity === E_DEPRECATED || $severity === E_USER_DEPRECATED) {
        return false;
    }

    if (!(error_reporting() & $severity)) {
        return false;
    }

    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function (Throwable $e) {
    Logger::getInstance('php')->error(
        $e->getMessage(),
        ['file' => $e->getFile(), 'line' => $e->getLine()]
    );

    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
});


App::useMiddleware([BodyParser::class, 'json']);
App::useMiddleware([BodyParser::class, 'urlencoded']);

// API Documentation at base route /api/
App::get('/', function (Request $req, Response $res) { 
    $res->toJSON(['name' => "API by Sebastian Borromeo",
        "version" => "1.0.0",
        'endpoints' => [
            'merch' => [
                'description' => 'Merchandise resources',
                'routes' => [
                    [
                        'method' => 'GET',
                        'path' => '/merch',
                        'description' => 'Get full list of merch'
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/merch/{id}',
                        'description' => 'Get merch by ID'
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/merch',
                        'description' => 'Create new merch item'
                    ]
                ]
                ],
                'auth' => [
                'description' => 'Authentication and authorization',
                'routes' => [
                    [
                        'method' => 'GET',
                        'path' => '/auth/validateToken',
                        'description' => 'Validate authorization token'
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/auth/extendToken',
                        'description' => 'Issue new authorization token given a valid token'
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/auth/login',
                        'description' => 'Get authorization token from credentials'
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/auth/logout',
                        'description' => 'Invalidate authorization token'
                    ]
                ]
                ]
        ]
    ]);
    exit;
});

$authRouter = require_once './src/Routes/AuthRouter.php';
App::useRouter('/auth', $authRouter);

$merchRouter = require_once './src/Routes/MerchRouter.php';
App::useRouter('/merch', $merchRouter);

App::run();