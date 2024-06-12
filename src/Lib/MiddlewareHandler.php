<?php namespace App\Lib;

class MiddlewareHandler {
    public static function configureMiddlewareStack(array $middlewares, callable $controller) {
        $stack = array_reduce(array_reverse($middlewares), function($next, $middleware) {
            return function($req, $res) use ($next, $middleware) {
                return $middleware($req, $res, $next);
            };
        }, function($req, $res) use ($controller) {
            return $controller($req, $res);
        });

        return $stack;
    }
}