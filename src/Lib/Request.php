<?php namespace App\Lib;

class Request {
    public $params;
    public $reqMethod;
    public $contentType;
    public $extraProperties;
    public $body = null;

    public function __construct($params = []) {
        $this->params = $params;
        $this->reqMethod = trim($_SERVER['REQUEST_METHOD']);
        $this->contentType = !empty($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
        $this->extraProperties = [];
    }
}