<?php namespace App\Routes;

use App\Lib\Router;
use App\Lib\Request;
use App\Lib\Response;
use App\Middleware\AuthMiddleware;
use App\Controllers\AuthController;

$authRouter = new Router();

$authRouter->get('/validateToken', function (Request $req, Response $res) {
    $res->toJSON(['message' => 'Valid token']);
    exit;
}, [[AuthMiddleware::class, 'strictAuthMiddleware']]);

$authRouter->post('/login', [AuthController::class, 'login']);

$authRouter->post('/logout', [AuthController::class, 'logout'], [[AuthMiddleware::class, 'authMiddleware']]);

return $authRouter;