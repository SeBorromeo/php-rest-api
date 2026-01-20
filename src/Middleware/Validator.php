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

    // Recursive function to check if wildcard path exists
    private static function checkPath($array, $keys) {
        if(count($keys) == 0) 
            return $array;

        $currentKey = array_shift($keys);
        if($currentKey == '*') {
            $fieldValues = new ValidatorFieldValues();

            foreach($array as $item) {
                $fieldValues->addFieldValue(self::checkPath($item, $keys));
            }
            return $fieldValues;
        }
        else if(isset($array->{$currentKey})) {
            return self::checkPath($array->{$currentKey}, $keys);
        } else {
            return null;
        }
    }

    private static function getEmptyErrMsg($conditions) {
        return array_filter($conditions, function($condition) {
            return $condition['func'] === 'notEmptyHelper';
        })[0]['errMsg'];
    }
    
    public static function validate(array $rules) {
        return function(Request $req, Response $res, callable $next) use ($rules) {
            $req->extraProperties['validationErrors'] = [];
            foreach($rules as $rule) {
                $paramName = $rule->getParamName();
                $conditions = $rule->getConditions();

                $paramParts = explode('.', $paramName);

                $value = self::checkPath($req->body, $paramParts);

                //TODO: Check different parts of req depending on type (body vs params)

                if($value instanceof ValidatorFieldValues) { // Not empty and is an array from wildcard expression
                    foreach($value->getFieldValues() as $individualValue) {
                        foreach($conditions as $condition) {
                            if($condition['func'] !== 'notEmptyHelper' && ![Validator::class, $condition['func']]($individualValue, ...$condition['args']))  
                                self::addValidationError($req, $individualValue, $condition['errMsg'], $paramName, 'body');  
                            else if($rule->requiresNotEmpty() && $individualValue == null) 
                                self::addValidationError($req, $individualValue, self::getEmptyErrMsg($conditions), $paramName, 'body');
                        }
                    }
                }
                else if($value != null) { // Not Empty
                    foreach($conditions as $condition) {
                        if($condition['func'] !== 'notEmptyHelper' && ![Validator::class, $condition['func']]($value, ...$condition['args'])) 
                            self::addValidationError($req, $value, $condition['errMsg'], $paramName, 'body');  
                    }
                }
                elseif($rule->requiresNotEmpty())  // Empty and requires notEmpty
                    self::addValidationError($req, $value, self::getEmptyErrMsg($conditions), $paramName, 'body');
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
        return (isset($range['min']) ? $stringlength >= $range['min'] : true) && (isset($range['max']) ? $stringlength <= $range['max'] : true);
    }

    public static function isNumericHelper($value, array $range): bool {
        if(is_numeric($value)) 
            return (isset($range['min']) ? $value >= $range['min'] : true) && (isset($range['max']) ? $value <= $range['max'] : true);

        return false;
    }

    public static function isIntHelper($value, array $range): bool {
        if(is_string($value) && filter_var($value, FILTER_VALIDATE_INT))
            $value = (int) $value;

        if(is_int($value)) 
            return (isset($range['min']) ? $value >= $range['min'] : true) && (isset($range['max']) ? $value <= $range['max'] : true);

        return false;
    }

    public static function isInHelper($value, array $array): bool {
        return in_array($value, $array, true);
    }

    public static function isStringArrayHelper($value): bool {
        if (!is_array($value)) 
            return false;
    
        foreach ($value as $element) {
            if (!is_string($element)) 
                return false;
        }
    
        return true;
    }

    public static function isArrayHelper($value): bool {
        return is_array($value);
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