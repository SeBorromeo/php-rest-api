<?php namespace App\Controllers;

use App\Lib\Request;
use App\Lib\Response;
use App\Lib\DBConnect;
use App\Services\AuthService;

class AuthController {
    public static function login(Request $req, Response $res) {
        $db = DBConnect::getDB();
        $authService = new AuthService($db, $_ENV['SECRET_KEY']);
        
        $username = $req->body->user;
        $pass = $req->body->pass;

        try {
            $user = $authService->validateCredentials($username, $pass);
            $token = $authService->generateToken($user['id'], $user['role']);
        } catch (\Exception $e) { $next($e); }
        
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