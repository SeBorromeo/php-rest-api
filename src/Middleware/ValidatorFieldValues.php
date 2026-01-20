<?php namespace App\Middleware;

class ValidatorFieldValues {
    private $fieldValues;

    public function __construct(array $fieldValues = []) {
        $this->fieldValues = $fieldValues;
    }

    public function getFieldValues(): array {
        return $this->fieldValues;
    }

    public function addFieldValue($fieldValue): void {
        $this->fieldValues[] = $fieldValue;
    }
}