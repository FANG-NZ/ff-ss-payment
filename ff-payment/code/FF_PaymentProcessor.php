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
     * @var FF_Payment
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
     * Get the payment object from this processor
     * @return type
     */
    public function getPayment(){
        return $this->payment;
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
        Session::set('FF_Payment.RedirectionURL', $url);
    }

    public function getRedirectURL() {
        return Session::get('FF_Payment.RedirectionURL');
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
     * @param Array data The input array of data
     * @return Payment object for payment
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
        
        //To return Payment dataobject
        return $this->payment;
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
     */
    public function capture() {
        
        if(!$this->paymentData || !is_array($this->paymentData)){
            throw new Exception("Not setup payment data");
        }

        // Validate the payment data
        $validation = $this->gateway->validate($this->paymentData);

        if (! $validation->valid()) {
            
            // Use the exception message to identify this is a validation exception
            // Payment pages can call gateway->getValidationResult() to get all the
            // validation error messages
            Debug::show($validation->messageList());
            
            throw new Exception("Validation Exception");
        }
    }
        
        
    /**
     * OVERRIDE
     * @return string
     */
    public function Link() {
        return "ff-payment";
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
    public function capture() {
        parent::capture();

        //call gateway process
        $result = $this->gateway->process($this->paymentData);
        //To update payment
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
    
    private static $allowed_actions = array(
        'complete',
        'cancel'
    );
    
    
    /**
     * Process a gateway-hosted payment. Users will be redirected to
     * the external gateway to enter payment info. Redirect back to
     * our site when the payment is completed.
     *
     * @see PaymentProcessor::capture()
     */
    public function capture() {
        parent::capture();
        
        // To call setup gateway
        $this->setupGateway();
        
        // Send a request to the gateway
        $result = $this->gateway->process($this->paymentData);

        // Processing may not get to here if all goes smoothly, customer will be at the 3rd party gateway
        if ($result && !$result->isSuccess()) {

            // Gateway did not respond or responded with error
            // Need to save the gateway response and save HTTP Status, errors etc. to Payment
            $this->payment->updateStatus($result);

            // Payment has failed - redirect to confirmation page
            // Developers can get the failure data from the database to show
            // the proper errors to users
            $this->doRedirect();
        }
        
    }
    
    
    /**
     * Function is to setup gateway 
     */
    protected function setupGateway(){
        // Set the return link
        $complete_url = Director::absoluteURL(Controller::join_links(
                        $this->Link(),
                        'complete',
                        $this->methodName,
                        $this->payment->ID
        ));
        $this->gateway->setReturnURL($complete_url);

        // Set the cancel link
        $cancel_url = Director::absoluteURL(Controller::join_links(
                        $this->Link(),
                        'cancel',
                        $this->methodName,
                        $this->payment->ID
        ));
        $this->gateway->setCancelURL($cancel_url);
    }


    /**
     * Function is to get method name from request
     * @param SS_HTTPRequest $request
     */
    protected function getMethodName(SS_HTTPRequest $request){
        return $request->param("MethodName");
    }




    /**
     * Process request from the external gateway, this action is usually triggered if the payment was completed on the gateway 
     * and the user was redirected to the returnURL.
     * 
     * The request is passed to the gateway so that it can process the request and use a mechanism to check the status of the payment.
     *
     * @param SS_HTTPResponse $request
     */
    public function complete(SS_HTTPRequest $request) {
        
        // Reconstruct the payment object
        $this->payment = FF_Payment::get()->byID($request->param('PaymentID'));

        // Reconstruct the gateway object
        // get the method name
        $methodName = $this->getMethodName($request);
        // call factory to reconstruct payment gateway
        $this->gateway = FF_PaymentFactory::get_gateway($methodName);

        // Query the gateway for the payment result
        $result = $this->gateway->check($request);
        $this->payment->updateStatus($result);

        // Do redirection
        $this->doRedirect();
    }
    
    
    /**
     * Process request from the external gateway, this action is usually triggered if the payment was cancelled
     * and the user was redirected to the cancelURL.
     * 
     * @param SS_HTTPResponse $request
     */
    public function cancel($request) {
        
        // Reconstruct the payment object
        $this->payment = FF_Payment::get()->byID($request->param('PaymentID'));

        
        // The payment result was a incomplete
        $this->payment->updateStatus(new FF_PaymentGateway_Incomplete());

        // Do redirection
        $this->doRedirect();
    }
    
    
}
