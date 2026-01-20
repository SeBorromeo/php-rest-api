<?php namespace App\Controllers;

use App\Lib\Request;
use App\Lib\Response;
use App\Lib\DBConnect;
use App\Lib\Logger;
use App\Services\AuthService;

class AuthController {
    public static function extendToken(Request $req, Response $res, callable $next) {
        $db = DBConnect::getDB();
        $authService = new AuthService($db, $_ENV['SECRET_KEY']);

        try {
            $payload = $authService->validateToken();      
            $newToken = $authService->generateToken($payload->userId, $payload->userRole);

            return $res->toJSON([
                'message' => 'Successfully issued new token',
                'data' => [ 'token' => $newToken ]
            ]);   
        } catch (\Exception $e) { return $next(new \Exception("Can't extend token: {$e->getMessage()}", $e->getCode())); }
    }

    public static function login(Request $req, Response $res) {
        $db = DBConnect::getDB();
        $authService = new AuthService($db, $_ENV['SECRET_KEY']);
        
        $username = $req->body->user;
        $pass = $req->body->pass;

        try {
            $user = $authService->validateCredentials($username, $pass);
            $token = $authService->generateToken($user['id'], $user['role']);
        } catch (\Exception $e) { $next($e); }
        
        Logger::getInstance()->info("$username successfully logged in");

        return $res->toJSON([
            'message' => 'Successful credentials',
            'data' => [ 'token' => $token ]
        ]);   
    }

    //TODO: use AuthService's invalidateToken() and return response with meaningful message
    public static function logout() {
        exit();
    }
}