<?php namespace App\Middleware;

use App\Lib\Request;
use App\Lib\Response;

enum ValidatorType {
    case Body;
    case Query;
}

class Validator {
    private ValidatorType $type;
    private string $paramName;
    private $conditions = []; 
    private $notEmpty = false;

    public function __construct(ValidatorType $type, string $paramName) {
        $this->type = $type;
        $this->paramName = $paramName;
    }

    /**
     * Magic method '__call' to reuse same logic for validation methods.
     * Adds condition to conditions array.
     *
     * @return void
     */
    public function __call(string $functionName, $arguments) {
        if($functionName === 'notEmpty')
            $this->notEmpty = true;

        $this->conditions[] = ['func' => $functionName . 'Helper', 'args' => $arguments, 'errMsg' => 'Invalid Value'];
        return $this;
    }
    
    public static function validate(array $rules) {
        return function(Request $req, Response $res, callable $next) use ($rules) {
            $req->extraProperties['validationErrors'] = [];
            foreach($rules as $rule) {
                $paramName = $rule->getParamName();
                $conditions = $rule->getConditions();

                $value = $req->body->{$paramName};

                //TODO: Check different parts of req depending on type
                if($value) { // Not Empty
                    foreach($conditions as $condition) {
                        if($condition['func'] !== 'notEmptyHelper' && ![Validator::class, $condition['func']]($value, ...$condition['args'])) 
                            self::addValidationError($req, $value, $condition['errMsg'], $paramName, 'body');  
                    }
                }
                elseif($rule->requiresNotEmpty()) { //Empty and requires notEmpty
                    $errMsg = array_filter($conditions, function($condition) {
                        return $condition['func'] === 'notEmptyHelper';
                    })[0]['errMsg'];
                    self::addValidationError($req, $value, $errMsg, $paramName, 'body');
                }
            }
            return $next();
        };
    }

    public static function validationResult(Request $req) {
        return $req->extraProperties['validationErrors'];
    }

    public static function body(string $paramName) {
        return new Validator(ValidatorType::Body, $paramName);
    }

    public function withMessage(string $message) {
        if(!empty($this->conditions)) {
            $this->conditions[count($this->conditions) - 1]['errMsg'] = $message;
        }
        return $this;
    }

    public static function addValidationError(Request $req, $value, string $msg, string $param, string $location) {
        $error = [
            "value" => $value,
            "msg" => $msg,
            "param" => $param,
            "location" => $location
        ];  
        $req->extraProperties['validationErrors'][] = $error;
    }

    /* Helper Validation Methods */

    public static function isLengthHelper($value, array $range): bool {
        $stringlength = strlen((string)$value);
        return ($range['min'] ? $stringlength >= $range['min'] : true) && ($range['max'] ? $stringlength <= $range['max'] : true);
    }

    public static function isNumericHelper($value, array $range): bool {
        if(is_numeric($value)) 
            return ($range['min'] ? $value >= $range['min'] : true) && ($range['max'] ? $value <= $range['max'] : true);

        return false;
    }

    public static function isIntHelper($value, array $range): bool {
        if(is_int($value)) 
            return ($range['min'] ? $value >= $range['min'] : true) && ($range['max'] ? $value <= $range['max'] : true);

        return false;
    }

    public static function isInHelper($value, array $array): bool {
        return in_array($value, $array, true);
    }

    /* Getters */

    public function getParamName() {
        return $this->paramName;
    }

    public function getErrMsg() {
        return $this->errMsg;
    }

    public function getConditions() {
        return $this->conditions;
    }

    public function requiresNotEmpty() {
        return $this->notEmpty;
    }
}