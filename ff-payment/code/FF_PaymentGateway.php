<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FF_PaymentGateway
 *
 * @author Fang
 */
class FF_PaymentGateway {
    
    /**
     * The gateway url
     * TODO: Can this just be moved to PaymentGateway_GatewayHosted?
     *
     * @var String
     */
    protected $gatewayURL;

    /**
     * Object holding the gateway validation result
     *
     * @var ValidationResult
     */
    private $validationResult;

    /**
     * Object holding the result from gateway
     *
     * @var PaymentGateway_Result
     */
    private $gatewayResult;

    /**
     * Supported credit card types for this gateway
     * 
     * @see PaymentGateway::getSupportedCardTypes()
     */
    protected $supportedCardTypes = array();

    /**
     * Supported currencies for this gateway
     * 
     * @see PaymentGateway::getSupportedCurrencies()
     */
    protected $supportedCurrencies = array();

    /**
     * Array of config for this gateway
     *
     * @var Array
     */
    protected $config;

    /**
     * Get the payment environment.
     * The environment is retrieved from the config yaml file.
     * If no environment is specified, assume SilverStripe's environment.
     */
    public static function get_environment() {
        if (Config::inst()->get('FF_PaymentGateway', 'environment')) {
            return Config::inst()->get('FF_PaymentGateway', 'environment');
        } else {
            return Director::get_environment_type();
        }
    }
    
    
    
    /**
     * Get validation result for this gateway
     * 
     * @see PaymentGateway::validate()
     * @return ValidationResult
     */
    public function getValidationResult() {
        if (!$this->validationResult) {
            $this->validationResult = new ValidationResult();
        }
        return $this->validationResult;
    }

    
    /**
     * Get the YAML config for current environment
     * 
     * @return Array
     */
    public function getConfig() {
        if (!$this->config) {
            $this->config = Config::inst()->get(get_class($this), self::get_environment());
        }
        return $this->config;
    }

    
    /**
     * Get the list of credit card types supported by this gateway
     *
     * @return Array Credit card types
     */
    public function getSupportedCardTypes() {
        return $this->supportedCardTypes;
    }

    /**
     * Get the list of currencies supported by this gateway
     *
     * @return Array Supported currencies
     */
    public function getSupportedCurrencies() {
        return $this->supportedCurrencies;
    }

    
    
    /**
     * Validate the payment data against the gateway-specific requirements
     *
     * @param Array $data
     * @return ValidationResult
     */
    public function validate($data) {
        $validationResult = $this->getValidationResult();

        if (! isset($data['Amount'])) {
            $validationResult->error('Payment amount not set');
        }
        else if (empty($data['Amount'])) {
            $validationResult->error('Payment amount cannot be null');
        }

        if (! isset($data['Currency'])) {
            $validationResult->error('Payment currency not set');
        }
        else if (empty($data['Currency'])) {
            $validationResult->error('Payment currency cannot be null');
        }
        else if (! array_key_exists($data['Currency'], $this->getSupportedCurrencies())) {
            $validationResult->error('Currency ' . $data['Currency'] . ' not supported by this gateway');
        }

        $this->validationResult = $validationResult;

        return $validationResult;
    }
    
    
    
    /**
     * Send a request to the gateway to process the payment.
     * To be implemented by individual gateways
     *
     * @param Array $data
     * @return PaymentGateway_Result
     */
    public function process($data, $payment) {
        return new FF_PaymentGateway_Success();
    }
    
}



/**
 * Parent class for all merchant-hosted gateways
 */
class FF_PaymentGateway_MerchantHosted extends FF_PaymentGateway { 

    
}







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
     * @param String $status
     * @param SS_HTTPResponse $response
     * @param Array $errors
     */
    public function __construct($status, $response = null, $errors = null) {

        if (!$response) {
            $response = new SS_HTTPResponse('', 200);
        }

        $this->HTTPResponse = $response;
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

    public function __construct() {
        parent::__construct(FF_PaymentGateway_Result::SUCCESS);
    }
    
}

/**
 * Wrapper class for 'failure' result
 */
class FF_PaymentGateway_Failure extends FF_PaymentGateway_Result {

    public function __construct($response = null, $errors = null) {
        parent::__construct(FF_PaymentGateway_Result::FAILURE, $response, $errors);
    }
    
}

/**
 * Wrapper class for 'incomplete' result
 */
class FF_PaymentGateway_Incomplete extends FF_PaymentGateway_Result {

    public function __construct($response = null, $errors = null) {
        parent::__construct(FF_PaymentGateway_Result::INCOMPLETE, $response, $errors);
    }
    
}
