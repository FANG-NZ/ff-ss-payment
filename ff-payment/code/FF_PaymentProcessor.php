<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FF_PaymentProcessor
 *
 * @author Fang
 */
class FF_PaymentProcessor extends Controller {
    
    /**
     * The method name of this controller
     *
     * @var String
     */
    protected $methodName;

    /**
     * The payment object to be injected to this controller
     *
     * @var Payment
     */
    protected $payment;

    /**
     * The gateway object to be injected to this controller
     *
     * @var PaymentGateway
     */
    protected $gateway;

    /**
     * The payment data array
     *
     * @var array
     */
    protected $paymentData;

    /**
     * Get the supported methods array set by the yaml configuraion
     *
     * @return array
     */
    public static function get_supported_methods() {
        $methodConfig = Config::inst()->get('FF_PaymentProcessor', 'supported_methods');
        $environment = FF_PaymentGateway::get_environment();
        
        // Check if all methods are defined in factory
        foreach ($methodConfig[$environment] as $method) {
            if (! FF_PaymentFactory::get_factory_config($method)) {
                user_error("Method $method not defined in factory", E_USER_ERROR);
            }
        }
        
        return $methodConfig[$environment];
    }

    /**
     * Set the method name of this controller.
     * This must be called after initializing a controller instance.
     * If not a generic method name 'Payment' will be used.
     *
     * @param String $method
     */
    public function setMethodName($method) {
        $this->methodName = $method;
    }
    
    
    /**
     * Set the payment object into this controller
     * @param type $payment
     */
    public function setPayment($payment){
        $this->payment = $payment;
    }
    
    
    /**
     * Set the payment gateway object into this controller
     * @param type $gateway
     */
    public function setPaymentGateway($gateway){
        $this->gateway = $gateway;
    }
    
    

    /**
     * Set the url to be redirected to after the payment is completed.
     *
     * @param String $url
     */
    public function setRedirectURL($url) {
            Session::set('FF_Payment.PostRedirectionURL', $url);
    }

    public function getRedirectURL() {
            return Session::get('FF_Payment.PostRedirectionURL');
    }

    /**
     * Redirection after payment processing
     */
    public function doRedirect() {
        $this->extend('onBeforeRedirect');
        Controller::curr()->redirect($this->getRedirectURL());
    }

    
    /**
     * Save preliminary data to database before processing payment
     */
    public function setup($data) {
        
        if(!$data || !is_array($data)){
            throw new Exception("Not correct data format! Required ARRAY of data");
        }
        
        
        //To setup payment data
        $this->paymentData = $data;

        //To setup payment dataobject
        $this->payment->Amount->Amount = $this->paymentData['Amount'];
        $this->payment->Amount->Currency = $this->paymentData['Currency'];
        $this->payment->Reference = isset($this->paymentData['Reference']) ? $this->paymentData['Reference'] : null;
        $this->payment->Status = FF_Payment::PENDING;
        $this->payment->Method = $this->methodName;
        
        $this->payment->write();
    }
    
    

    /**
     * Process a payment request. To be extended by individual processor type
     * If there's no break point (i.e exceptions and errors), this should
     * redirect to the postRedirectURL (merchant-hosted) or the external gateway (gateway-hosted)
     * 
     * Data passed in the format (Reference is optional)
     * array('Amount' => 1.00, 'Currency' => 'USD', 'Reference' => 'Ref')
     *
     * @see paymentGateway::validate()
     * @param Array $data Payment data
     */
    public function capture($data) {
        //To setup data
        $this->setup($data);

        // Validate the payment data
        $validation = $this->gateway->validate($this->paymentData);

        if (! $validation->valid()) {
            // Use the exception message to identify this is a validation exception
            // Payment pages can call gateway->getValidationResult() to get all the
            // validation error messages
            throw new Exception("Validation Exception");
        }
    }

	/**
	 * Get the processor's form fields. Custom controllers use this function
	 * to add the form fields specifically to gateways.
	 *
	 * @return FieldList
	 */
//	public function getFormFields() {
//		$fieldList = new FieldList();
//
//		$fieldList->push(new NumericField('Amount', 'Amount', ''));
//		$fieldList->push(new DropDownField('Currency', 'Select currency :', $this->gateway->getSupportedCurrencies()));
//
//		return $fieldList;
//	}

	/**
	 * Get the form requirements
	 *
	 * @return RequiredFields
	 */
//	public function getFormRequirements() {
//		return new RequiredFields('Amount', 'Currency');
//	}
        
        
    /**
     * OVERRIDE
     * @return string
     */
    public function Link() {
        return "payment";
    }
    
}



/**
 * SUB CLASS of FF_PaymentProcessor
 */
class FF_PaymentProcessor_MerchantHosted extends FF_PaymentProcessor {
    
    /**
     * Process a merchant-hosted payment. Users will remain on the site
     * until the payment is completed. Redirect to the postRedirectURL afterwards
     *
     * @see PaymentProcessor::capture()
     */
    public function capture($data) {
        parent::capture($data);

        Debug::show("I'm here");
        
        $result = $this->gateway->process($this->paymentData, $this->payment);
        $this->payment->updateStatus($result);

        // Do redirection
        $this->doRedirect();
    }
    
}



/**
 * SUB CLASS of FF_PaymentProcessor
 * The Gateway Hosted extends from FF PaymentProcessor
 * 
 */
class FF_PaymentProcessor_GatewayHosted extends FF_PaymentProcessor {
    
    
    
}
