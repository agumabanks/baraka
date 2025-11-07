<?php

namespace App\Services\ETL\Validators;

class DataValidator
{
    protected array $validationRules = [];
    protected array $errors = [];
    protected float $score = 1.0;

    public function validate(array $data, array $validationRules): ValidationResult
    {
        $this->validationRules = $validationRules;
        $this->errors = [];
        $this->score = 1.0;

        // Check required fields
        if (isset($validationRules['required_fields'])) {
            $this->validateRequiredFields($data, $validationRules['required_fields']);
        }

        // Check data types
        if (isset($validationRules['data_types'])) {
            $this->validateDataTypes($data, $validationRules['data_types']);
        }

        // Check business constraints
        if (isset($validationRules['business_constraints'])) {
            $this->validateBusinessConstraints($data, $validationRules['business_constraints']);
        }

        // Check referential integrity
        if (isset($validationRules['referential_integrity'])) {
            $this->validateReferentialIntegrity($data, $validationRules['referential_integrity']);
        }

        // Run custom validation rules
        if (isset($validationRules['custom_rules'])) {
            $this->runCustomRules($data, $validationRules['custom_rules']);
        }

        return new ValidationResult(
            empty($this->errors),
            $this->errors,
            $this->score
        );
    }

    protected function validateRequiredFields(array $data, array $requiredFields): void
    {
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === null || $data[$field] === '') {
                $this->errors[] = [
                    'type' => 'required_field',
                    'field' => $field,
                    'message' => "Required field '{$field}' is missing or empty"
                ];
                $this->score -= 0.1;
            }
        }
    }

    protected function validateDataTypes(array $data, array $dataTypes): void
    {
        foreach ($dataTypes as $field => $expectedType) {
            if (isset($data[$field]) && $data[$field] !== null) {
                if (!$this->isValidDataType($data[$field], $expectedType)) {
                    $this->errors[] = [
                        'type' => 'data_type',
                        'field' => $field,
                        'expected_type' => $expectedType,
                        'actual_value' => $data[$field],
                        'message' => "Field '{$field}' should be of type {$expectedType}"
                    ];
                    $this->score -= 0.05;
                }
            }
        }
    }

    protected function validateBusinessConstraints(array $data, array $constraints): void
    {
        foreach ($constraints as $constraintName => $rule) {
            try {
                // Replace field placeholders with actual values
                $evaluatedRule = $this->replacePlaceholders($rule, $data);
                
                if (!$this->evaluateExpression($evaluatedRule)) {
                    $this->errors[] = [
                        'type' => 'business_constraint',
                        'constraint' => $constraintName,
                        'rule' => $rule,
                        'message' => "Business constraint '{$constraintName}' failed"
                    ];
                    $this->score -= 0.1;
                }
            } catch (\Exception $e) {
                $this->errors[] = [
                    'type' => 'business_constraint_error',
                    'constraint' => $constraintName,
                    'error' => $e->getMessage(),
                    'message' => "Error evaluating constraint '{$constraintName}': {$e->getMessage()}"
                ];
                $this->score -= 0.15;
            }
        }
    }

    protected function validateReferentialIntegrity(array $data, array $rules): void
    {
        foreach ($rules as $ruleName => $checkSql) {
            try {
                // Extract table and field from the rule
                if (preg_match('/^(\w+)\.(\w+)\s+IN\s+\(SELECT\s+(\w+)\s+FROM\s+(\w+)\)/', $checkSql, $matches)) {
                    $field = $matches[1] . '.' . $matches[2];
                    $targetField = $matches[3];
                    $table = $matches[4];
                    
                    if (isset($data[str_replace('.', '_', $field)])) {
                        $value = $data[str_replace('.', '_', $field)];
                        
                        $exists = \DB::table($table)
                            ->where($targetField, $value)
                            ->exists();
                        
                        if (!$exists) {
                            $this->errors[] = [
                                'type' => 'referential_integrity',
                                'rule' => $ruleName,
                                'field' => $field,
                                'value' => $value,
                                'table' => $table,
                                'message' => "Referential integrity check failed: {$field} = {$value} not found in {$table}.{$targetField}"
                            ];
                            $this->score -= 0.2;
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->errors[] = [
                    'type' => 'referential_integrity_error',
                    'rule' => $ruleName,
                    'error' => $e->getMessage(),
                    'message' => "Error checking referential integrity '{$ruleName}': {$e->getMessage()}"
                ];
                $this->score -= 0.15;
            }
        }
    }

    protected function runCustomRules(array $data, array $customRules): void
    {
        foreach ($customRules as $ruleName => $ruleConfig) {
            try {
                $className = $ruleConfig['class'] ?? null;
                $method = $ruleConfig['method'] ?? 'validate';
                $parameters = $ruleConfig['parameters'] ?? [];
                
                if ($className && class_exists($className)) {
                    $validator = new $className();
                    if (method_exists($validator, $method)) {
                        $result = $validator->$method($data, $parameters);
                        
                        if (!$result['valid']) {
                            $this->errors[] = [
                                'type' => 'custom_rule',
                                'rule' => $ruleName,
                                'message' => $result['message'] ?? "Custom rule '{$ruleName}' failed",
                                'details' => $result['details'] ?? null
                            ];
                            $this->score -= $result['score_impact'] ?? 0.1;
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->errors[] = [
                    'type' => 'custom_rule_error',
                    'rule' => $ruleName,
                    'error' => $e->getMessage(),
                    'message' => "Error running custom rule '{$ruleName}': {$e->getMessage()}"
                ];
                $this->score -= 0.1;
            }
        }
    }

    protected function isValidDataType($value, string $expectedType): bool
    {
        switch ($expectedType) {
            case 'integer':
                return is_int($value) || (is_string($value) && ctype_digit($value));
            case 'decimal':
                return is_numeric($value) && strpos($value, '.') !== false;
            case 'string':
                return is_string($value);
            case 'boolean':
                return is_bool($value) || in_array(strtolower($value), ['true', 'false', '1', '0']);
            case 'date':
                return strtotime($value) !== false;
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'phone':
                return preg_match('/^\+?[\d\s\-\(\)]+$/', $value);
            default:
                return true;
        }
    }

    protected function replacePlaceholders(string $rule, array $data): string
    {
        // Replace field names with actual values in the rule
        foreach ($data as $field => $value) {
            if (is_scalar($value)) {
                $rule = str_replace($field, var_export($value, true), $rule);
            }
        }
        
        return $rule;
    }

    protected function evaluateExpression(string $expression): bool
    {
        // Create a safe evaluation environment
        $allowedFunctions = [
            'abs' => true, 'min' => true, 'max' => true,
            'sqrt' => true, 'pow' => true, 'log' => true
        ];
        
        // Use a very restricted eval environment
        // In production, consider using a proper expression parser
        try {
            // Simple expression evaluation (this is a simplified version)
            // For production, use a library likeSymfony ExpressionLanguage
            $safeExpression = preg_replace('/[^0-9+\-*/().\s<>=!&|]/', '', $expression);
            return eval("return ({$safeExpression});");
        } catch (\Exception $e) {
            return false;
        }
    }
}

class ValidationResult
{
    protected bool $isValid;
    protected array $errors;
    protected float $score;

    public function __construct(bool $isValid, array $errors, float $score = 1.0)
    {
        $this->isValid = $isValid;
        $this->errors = $errors;
        $this->score = max(0.0, min(1.0, $score));
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function getErrorCount(): int
    {
        return count($this->errors);
    }

    public function getErrorsByType(string $type): array
    {
        return array_filter($this->errors, function($error) use ($type) {
            return $error['type'] === $type;
        });
    }
}