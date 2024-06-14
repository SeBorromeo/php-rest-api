<?php namespace App\Lib;

class MiddlewareHandler {
    public static function configureMiddlewareStack(array $middlewares, callable $controller) {
        $middlewares = array_reverse($middlewares);
        $stack = array_reduce($middlewares, function($nextMiddleware, $middleware) {
            return function($req, $res) use ($nextMiddleware, $middleware) {
                $next = function(\Exception $err = null) use ($nextMiddleware, $req, $res) {
                    //TODO: add ability to add custom Error Handlers
                    if($err) 
                        return MiddlewareHandler::defaultErrorHandler($err, $req, $res);
                    
                    return $nextMiddleware($req, $res);
                };

                return $middleware($req, $res, $next);
            };
        }, function($req, $res) use ($controller) {
            return $controller($req, $res, function($err) use ($req, $res) {
                return MiddlewareHandler::defaultErrorHandler($err, $req, $res);
            });
        });
        return $stack;
    }

    private static function defaultErrorHandler(\Exception $err, $req, $res) {
        $res->status($err->getCode())->toJSON(['error' => [
            'code' => $err->getCode(),
            'message' => $err->getMessage()
        ]]);
    }

    private static function checkCallableArgs($callable): int {
        if (!is_callable($callable))
            throw new InvalidArgumentException('The provided argument is not callable.');
    
        // Handle functions
        if (is_string($callable) && function_exists($callable)) {
            $reflection = new ReflectionFunction($callable);
        }
        // Handle static methods
        elseif (is_array($callable) && is_callable($callable)) {
            $reflection = new ReflectionMethod($callable[0], $callable[1]);
        }
        // Handle closures or other callables
        elseif ($callable instanceof Closure) {
            $reflection = new ReflectionFunction($callable);
        } else {
            throw new InvalidArgumentException('Unsupported callable type.');
        }
    
        // Get number of parameters
        $numParams = $reflection->getNumberOfParameters();
        return $numParams;
    }
    
}