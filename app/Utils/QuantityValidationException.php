<?php
namespace App\Utils;

use Exception;

class QuantityValidationException extends Exception {
protected $message;
    protected $errors;

    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->message = $message;
        $this->errors = [
            "sale_details"=> [$this->message],
        ];
        $this->saleDetails = [$message];
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function toArray()
    {
        return [
                'errors' => $this->errors
        ];
    }

}
