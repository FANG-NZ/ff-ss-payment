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
abstract class FF_PaymentGateway {
    
    /**
     * The gateway url
     * TODO: Can this just be moved to PaymentGateway_GatewayHosted?
     *
     * @var String
     */
    protected $gatewayURL;
    
    
    /**
     * Object holding the result from gateway
     *
     * @var FF_PaymentGateway_Result
     */
    protected $gatewayResult;



    /**
     * Object holding the gateway validation result
     *
     * @var ValidationResult
     */
    private $validationResult;


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
     * Get the response from gateway
     * @return type
     */
    public function getResponse(){
        return $this->response;
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
     * ABSTRACT
     * Send a request to the gateway to process the payment.
     * To be implemented by individual gateways
     *
     * @param Array $data
     * @return PaymentGateway_Result
     */
    abstract public function process($data); 
    
}



/**
 * Parent class for all merchant-hosted gateways
 */
abstract class FF_PaymentGateway_MerchantHosted extends FF_PaymentGateway { 
    /* THIS IS FOR MERCHANT HOSTED */
}






/**
 * Parent class for all gateway-hosted gateways
 */
abstract class FF_PaymentGateway_GatewayHosted extends FF_PaymentGateway {
    
    /**
     * The link to return to after processing payment (for gateway-hosted payments only)
     *
     * @var String
     */
    protected $returnURL;

    /**
     * The link to return to after cancelling payment (for gateway-hosted payments only)
     *
     * @var String
     */
    protected $cancelURL;
            
    
    /**
     * Function is to set return url
     * @param type $url
     */
    public function setReturnURL($url){
        $this->returnURL = $url;
    }
    
    /**
     * Function is to set cancel url
     * @param type $url
     */
    public function setCancelURL($url){
        $this->cancelURL = $url;
    }
     
    /**
     * Check the payment using gateway lookup API or request
     * 
     * TODO: Should this return PaymentGateway_Failure by default instead?
     *
     * @param SS_HTTPRequest $request
     * @return PaymentGateway_Result
     */
    public function check(SS_HTTPRequest $request) {
        return new FF_PaymentGateway_Success();
    }
    
}

