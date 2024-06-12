<?php require __DIR__ . '/vendor/autoload.php';

header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Headers: Authorization, Content-Type");
header('Content-Type: application/json');

use App\Lib\App;
use App\Lib\Request;
use App\Lib\Response;
use App\Middleware\BodyParser;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

App::useMiddleware([BodyParser::class, 'json']);
App::useMiddleware([BodyParser::class, 'urlencoded']);

// API Documentation at base route /api/
App::get('/', function (Request $req, Response $res) { 
    $res->toJSON(['message' => "API by Sebastian Borromeo",
        'endpoints' => [
            ['path' => '/api/auth/validateToken', 'description' => 'Validate Bearer Token'],
            ['path' => '/api/auth/login', 'description' => 'POST with credentials to get token'],
        ]
    ]);
    exit;
});

$authRouter = require_once './src/Routes/AuthRouter.php';
App::useRouter('/auth', $authRouter);

App::run();