<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PayPalGateway
 *
 * @author Fang
 */
class PayPalGateway extends FF_PaymentGateway_GatewayHosted {
    
    
    /**
     * @config
     * @var string
     */
    protected $client_id;
    protected $secret;
    protected $access_token_url;
    protected $payment_url;


    /**
     * @config
     * @var type 
     */
    protected $supportedCurrencies = array(
        'NZD' => 'New Zealand Dollar',
        'USD' => 'United States Dollar',
        'GBP' => 'Great British Pound'
    );
    
    
    /**
     * Function is to get PayPal object from Database
     */
    public function getPayPal(){
        $paypal = DataObject::get("FF_PayPal")->first();
        
        if(!$paypal){
            $paypal = new FF_PayPal();
            $paypal->write();   
        }
        
        return $paypal;
    }
    
    
    /**
     * Function is to get access token
     * @return string
     */
    public function getAccessToken(){
        return "A21AAG6Muc1gzLaScCYC7-ZQRCYrk2T1DY2YAQ8Y1KpHxDpdcCW810tzpzZ_f05AIgtI3u2tnKAIhxu_waEz5NOAxb84glJSQ";
    }
    
    
    
    
    /**
     * Function is to setup default setting
     */
    private function onBeforeProcess(){
        $config = $this->getConfig();
        
        //To setup client id
        if(!$this->client_id){
            $this->client_id = $config['authentication']['client_id'];
        }
        
        //To setup secret key
        if(!$this->secret){
            $this->secret = $config['authentication']['secret'];
        }
        
        //TODO
        //setup taken url
        if(!$this->access_token_url){
            $this->access_token_url = $config['access_token_url'];
        }
        
        if(!$this->payment_url){
            $this->payment_url = $config['payment_url'];
        }
    }
    
    
    /**
     * Function is to request payment
     */
    public function requestPayment($data, SS_HTTPRequest $request){
        $this->onBeforeProcess();
        
        // Initialize our cURL handle.
        $ch = curl_init($this->payment_url);

        //To setup header
        $header = array();
        $header[] = "Content-Type: application/json";
        $header[] = 'Authorization: Bearer ' . $this->getAccessToken();

        
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        
        //To setup return value
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        //To get ca-file path
        $file_path = str_replace('options', 'tools', dirname(__FILE__));
        curl_setopt($ch, CURLOPT_CAINFO, $file_path . '/cacert.pem');

        
        //To setup JSON array
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        //To make request call
        $response = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        //To close curl
        curl_close($ch);
        
        return json_decode($response);
    }
    
    
    /**
     * 
     * A21AAG6Muc1gzLaScCYC7-ZQRCYrk2T1DY2YAQ8Y1KpHxDpdcCW810tzpzZ_f05AIgtI3u2tnKAIhxu_waEz5NOAxb84glJSQ
     * 32400
     * 
     * Function is to get access token
     */
    public function requestAccessToken(){
        $this->onBeforeProcess();
        
        // Initialize our cURL handle.
        $ch = curl_init($this->access_token_url);

        //To setup header
        $header = array();
        $header[] = "Accept: application/json";
        $header[] = "Accept-Language: en_US";

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->client_id}:{$this->secret}");
        
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        
        //To get ca-file path
        $file_path = str_replace('options', 'tools', dirname(__FILE__));
        curl_setopt($ch, CURLOPT_CAINFO, $file_path . '/cacert.pem');
        
        //To make request call
        $response = curl_exec($ch);
        
        //To close curl
        curl_close($ch);
        
        Debug::show($response);die;
    }

    
    /**
     * OVERRIDE
     * @param type $data
     */
    public function process($data) {
        die("NOTHING HERE");
    }

}



/**
 * TODO
 * define the PayPal payment processor
 */
class FF_PaymentProcessor_PayPal extends FF_PaymentProcessor_GatewayHosted{
    
    private static $allowed_actions = array(
        'createPayment',
        'execute',
        'capture'
    );
    
    
    /**
     * OVERRIDE
     */
    public function init() {
        parent::init();
        
        //To setup vars for processor
        $this->setMethodName("PayPal");
        $this->setPaymentGateway(FF_PaymentFactory::get_gateway("PayPal"));  
        $this->setPayment(FF_PaymentFactory::get_payment_model("PayPal"));
    }
    
    
    /**
     * OVERRIDE
     */
    public function capture() {
        
        //To get data from request
        //$amount = $this->request->postVar("amount");
        //$currency = $this->request->postVar("currency");
        //$reference = $this->request->postVar("reference");
        
        $amount = 18.99;
        $currency = "NZD";
        $reference = "Test for PayPal";
        
        //To init data array
        $data = array(
            "Amount" => $amount,
            "Currency" => $currency,
            "Reference" => $reference
        );
        
        
        
        //To setup data
        $this->setup($data);
        
        // Validate the payment data
        $validation = $this->gateway->validate($this->paymentData);

        if (! $validation->valid()) {
            
            // Use the exception message to identify this is a validation exception
            // Payment pages can call gateway->getValidationResult() to get all the
            // validation error messages
            Debug::show($validation->messageList());
            
            throw new Exception("Validation Exception");
        }
        
        
        die("I'm here");
    }
    
    
    


    /**
     * Function is to handle create payment request
     * @param SS_HTTPRequest $request
     * @return type
     */
    public function createPayment(SS_HTTPRequest $request){
        
        //define the json array
        $json_array = array(
            "intent" => "sale",
            "payer" => array(
                "payment_method" => "paypal"
            ),

            "transactions" => array(

                array(
                    "amount" => array(
                        "total"=> "25",
                        "currency" => "NZD"
                    )
                ),

            ),

            "redirect_urls" => array(
                "return_url" => "http://localhost/www.mensproformance.co.nz/return",
                "cancel_url" => "http://localhost/www.mensproformance.co.nz/errortext"
            ),

        );
        
        //To request create payment
        $payment_result = $this->gateway->requestPayment($json_array, $request);
        
        //To reset return array
        $return = array(
            'paymentID' => $payment_result->id
        );
        
        echo json_encode($return);
        die;
    }
    
}
