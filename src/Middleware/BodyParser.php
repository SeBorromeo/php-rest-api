<?php namespace App\Middleware;

use App\Lib\Request;
use App\Lib\Response;

class BodyParser {
    public static function json(Request $req, Response $res, callable $next) {
        if (($req->reqMethod === 'POST' || $req->reqMethod === 'PUT') && strcasecmp($req->contentType, 'application/json') === 0) {
            // Receive the RAW post data.
            $content = trim(file_get_contents("php://input"));
            $decoded = json_decode($content);

            $req->body = $decoded;
        }

        return $next($req, $res);
    }

    public static function urlencoded(Request $req, Response $res, callable $next) {
        if (($req->reqMethod === 'POST' || $req->reqMethod === 'PUT') && strcasecmp($req->contentType, 'application/x-www-form-urlencoded') === 0) {
            $body = [];
            foreach ($_POST as $key => $value) {
                $body[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
            $req->body = $body;
        }

        return $next($req, $res);
    }
}