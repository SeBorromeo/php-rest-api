<?php namespace App\Middleware;

use App\Lib\Request;
use App\Lib\Response;
use App\Lib\DBConnect;
use App\Services\AuthService;

class AuthMiddleware {
    public static function authMiddleware(Request $req, Response $res, callable $next) {
        $db = DBConnect::getDB();
        $authService = new AuthService($db, $_ENV['SECRET_KEY']);

        try {
            $payload = $authService->validateToken();

            $req->extraProperties['userId'] = $payload->userId;
            $req->extraProperties['userRole'] = $payload->userRole;
        } catch (\Exception $e) { /* Ignored */ }

        return $next();
    }

    public static function strictAuthMiddleware(Request $req, Response $res, callable $next) {
        $db = DBConnect::getDB();
        $authService = new AuthService($db, $_ENV['SECRET_KEY']);

        try {
            $payload = $authService->validateToken();

            $req->extraProperties['userId'] = $payload->userId;
            $req->extraProperties['userRole'] = $payload->userRole;
        } catch (\Exception $e) { return $next($e); }
          
        return $next();
    }

    public static function checkRole(array $roles) {
        return function (Request $req, Response $res, callable $next) use ($roles) {
            if(!$req->extraProperties['userRole'])
                $next(new \Exception("Unauthorized", 401));

            $role = $req->extraProperties['userRole'];
            if(in_array($role, $roles))
                return $next();

            $next(new \Exception("Forbidden for role " . $role, 403));
        };
    }
}