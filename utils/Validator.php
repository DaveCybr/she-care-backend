<?php

/**
 * Input Validator Class
 */
class Validator
{
    private $errors = [];
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Validate required field
     */
    public function required($field, $message = null)
    {
        if (!isset($this->data[$field])) {
            $this->errors[$field] = $message ?? ucfirst($field) . " is required";
            return $this;
        }

        $value = $this->data[$field];

        // Check for empty arrays
        if (is_array($value) && empty($value)) {
            $this->errors[$field] = $message ?? ucfirst($field) . " is required";
            return $this;
        }

        // Check for empty strings
        if (is_string($value) && trim($value) === '') {
            $this->errors[$field] = $message ?? ucfirst($field) . " is required";
            return $this;
        }

        // Check for null or empty non-array values
        if (!is_array($value) && empty($value) && $value !== '0' && $value !== 0) {
            $this->errors[$field] = $message ?? ucfirst($field) . " is required";
        }

        return $this;
    }

    /**
     * Validate email format
     */
    public function email($field, $message = null)
    {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message ?? "Valid email is required";
        }
        return $this;
    }

    /**
     * Validate minimum length
     */
    public function minLength($field, $length, $message = null)
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field] = $message ?? ucfirst($field) . " must be at least {$length} characters";
        }
        return $this;
    }

    /**
     * Validate maximum length
     */
    public function maxLength($field, $length, $message = null)
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->errors[$field] = $message ?? ucfirst($field) . " must not exceed {$length} characters";
        }
        return $this;
    }

    /**
     * Validate numeric value
     */
    public function numeric($field, $message = null)
    {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = $message ?? ucfirst($field) . " must be numeric";
        }
        return $this;
    }

    /**
     * Validate integer value
     */
    public function integer($field, $message = null)
    {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_INT)) {
            $this->errors[$field] = $message ?? ucfirst($field) . " must be an integer";
        }
        return $this;
    }

    /**
     * Validate value is in array
     */
    public function in($field, array $values, $message = null)
    {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $values)) {
            $allowed = implode(', ', $values);
            $this->errors[$field] = $message ?? ucfirst($field) . " must be one of: {$allowed}";
        }
        return $this;
    }

    /**
     * Validate array field
     */
    public function isArray($field, $message = null)
    {
        if (isset($this->data[$field]) && !is_array($this->data[$field])) {
            $this->errors[$field] = $message ?? ucfirst($field) . " must be an array";
        }
        return $this;
    }

    /**
     * Validate non-empty array
     */
    public function notEmptyArray($field, $message = null)
    {
        if (!isset($this->data[$field]) || !is_array($this->data[$field]) || empty($this->data[$field])) {
            $this->errors[$field] = $message ?? ucfirst($field) . " must be a non-empty array";
        }
        return $this;
    }

    /**
     * Validate date format
     */
    public function date($field, $format = 'Y-m-d', $message = null)
    {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            $d = DateTime::createFromFormat($format, $this->data[$field]);
            if (!$d || $d->format($format) !== $this->data[$field]) {
                $this->errors[$field] = $message ?? ucfirst($field) . " must be a valid date in format {$format}";
            }
        }
        return $this;
    }

    /**
     * Validate phone number (Indonesian format)
     */
    public function phone($field, $message = null)
    {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            $pattern = '/^(\+62|62|0)[0-9]{9,12}$/';
            if (!preg_match($pattern, $this->data[$field])) {
                $this->errors[$field] = $message ?? "Invalid phone number format";
            }
        }
        return $this;
    }

    /**
     * Check if validation passed
     */
    public function passes()
    {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     */
    public function fails()
    {
        return !$this->passes();
    }

    /**
     * Get validation errors
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Get formatted errors for response
     */
    public function getFormattedErrors()
    {
        $formatted = [];
        foreach ($this->errors as $field => $message) {
            $formatted[] = [
                'field' => $field,
                'message' => $message
            ];
        }
        return $formatted;
    }
}
