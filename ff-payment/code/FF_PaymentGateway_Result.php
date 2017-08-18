<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/* --------- START define the payment result class --------- */
// -------------------------------------------------------------

/**
 * Class for gateway results
 */
class FF_PaymentGateway_Result {
	
    /* Constants for gateway result status */
    const SUCCESS    = 'Success';
    const FAILURE    = 'Failure';
    const INCOMPLETE = 'Incomplete';

    /**
     * Status of the payment being processed
     *
     * @var String
     */
    protected $status;

    /**
     * Array of errors raised by the gateway
     * array(ErrorCode => ErrorMessage)
     *
     * @var array
     */
    protected $errors = array();

    
    /**
     * The HTTP response object passed back from the gateway
     *
     * @var SS_HTTPResponse
     */
    protected $HTTPResponse;
    
    
    
    /**
     * The response from gateway
     * @var type 
     */
    protected $gateway_response;







    /**
     * @param String $status
     * @param SS_HTTPResponse $response
     * @param Array $errors
     */
    public function __construct($status, $gateway_response = null, $http_response = null, $errors = null) {

        if (!$http_response) {
            $http_response = new SS_HTTPResponse('', 200);
        }

        //To setup gateway response
        $this->gateway_response = $gateway_response;
        
        $this->HTTPResponse = $http_response;
        $this->setStatus($status);

        if ($errors) {
            $this->setErrors($errors);
        }
    }

    
    /**
     * Set the payment result status.
     *
     * @param String $status
     * @throws Exception when status is invalid
     */
    public function setStatus($status) {
        if ($status == self::SUCCESS || $status == self::FAILURE || $status == self::INCOMPLETE) {
            $this->status = $status;
        } else {
            throw new Exception("Result status invalid");
        }
    }

    /**
     * Get status of this result
     * 
     * @return String
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * Get HTTP Response
     * 
     * @return SS_HTTPResponse
     */
    public function getHTTPResponse() {
        return $this->HTTPResponse;
    }
    
    
    /**
     * Get the response from Gateway
     * @return type
     */
    public function getResponse(){
        return $this->response;
    }
    
    
    
    

    /**
     * Set the gateway errors
     *
     * @param array $errors
     */
    public function setErrors($errors) {

        if (is_string($errors)) {
            $errors = array($errors);
        }

        if (is_array($errors)) {
            $this->errors = $errors;
        } else {
            throw new Exception("Gateway errors must be array");
        }
        
    }

    
    /**
     * Add an error to the error list
     *
     * @param String $message: The error message
     * @param String $code: The error code
     */
    public function addError($message, $code = null) {
        
        if ($code) {
            if (array_key_exists($code, $this->errors)) {
                throw new Exception("Error code already exists");
            } else {
                $this->errors[$code] = $message;
            }
        } else {
            array_push($this->errors, $message);
        }
        
    }

    /**
     * Get errors
     * 
     * @return Array
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Returns true if successful
     * 
     * @return Boolean
     */
    public function isSuccess() {
        return $this->status == self::SUCCESS;
    }

    /**
     * Returns true if failure
     * 
     * @return Boolean
     */
    public function isFailure() {
        return $this->status == self::FAILURE;
    }

    /**
     * Returns true if incomplete
     * 
     * @return Boolean
     */
    public function isIncomplete() {
        return $this->status == self::INCOMPLETE;
    }

}

/**
 * Wrapper class for 'success' result
 */
class FF_PaymentGateway_Success extends FF_PaymentGateway_Result {

    public function __construct($gateway_response = null) {
        parent::__construct(FF_PaymentGateway_Result::SUCCESS, $gateway_response);
    }
    
}

/**
 * Wrapper class for 'failure' result
 */
class FF_PaymentGateway_Failure extends FF_PaymentGateway_Result {

    public function __construct($gateway_response = null, $http_response = null, $errors = null) {
        parent::__construct(
            FF_PaymentGateway_Result::FAILURE, 
            $gateway_response, 
            $http_response, 
            $errors
        );
    }
    
}

/**
 * Wrapper class for 'incomplete' result
 */
class FF_PaymentGateway_Incomplete extends FF_PaymentGateway_Result {

    public function __construct($gateway_response = null, $http_response = null, $errors = null) {
        parent::__construct(
            FF_PaymentGateway_Result::INCOMPLETE, 
            $gateway_response, 
            $http_response, 
            $errors
        );
    }
    
}
