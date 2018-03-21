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
     * Function is to update Access Token
     * @param type $data
     */
    public function updatePayPal($data){
        $paypal = $this->getPayPal();
        
        $paypal->update($data);
        $paypal->write();
        
        return $paypal;
    }
    
    
    
    /**
     * Function is to get access token
     * @return string
     */
    public function getAccessToken(){
        return $this->getPayPal()->Token;
    }
    
    
    /**
     * Function is to check if access token
     * expired
     */
    public function isTokenExpired(){
        $paypal = $this->getPayPal();
        
        //To get expired
        $lastUpdated = strtotime($paypal->LastUpdatedOn);
        $expired = $lastUpdated + $paypal->ExpiresIn;
        $current = time();
        
        if($current > $expired){
            return true;
        }
        
        return false;
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
        
        //To setup return value
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        //To get ca-file path
        $file_path = str_replace('options', 'tools', dirname(__FILE__));
        curl_setopt($ch, CURLOPT_CAINFO, $file_path . '/cacert.pem');
        
        //To make request call
        $response = curl_exec($ch);
        
        //To close curl
        curl_close($ch);
        
        $obj = json_decode($response);
        $data = array(
            'Token' => $obj->access_token,
            'ExpiresIn' => $obj->expires_in,
            'LastUpdatedOn' => date("Y-m-d H:i:s")
        );
        
        //To update PayPal data
        $this->updatePayPal($data);
        
        return $data;
    }

    
    /**
     * OVERRIDE
     * TODO this function is to send request to create
     * payment
     * @param type $data
     */
    public function process($data) {
        
        //If access token expired,
        //we need to request new access token
        if($this->isTokenExpired()){
            $this->requestAccessToken();
        }else{
            $this->onBeforeProcess();
        }
        
        
        //define the json array according to
        //PayPal document
        $json_array = array(
            "intent" => "sale",
            "payer" => array(
                "payment_method" => "paypal"
            ),

            "transactions" => array(

                array(
                    "amount" => array(
                        "total"=> $data['Amount'],
                        "currency" => $data['Currency']
                    )
                ),

            ),

            "redirect_urls" => array(
                "return_url" => $this->returnURL,
                "cancel_url" => $this->cancelURL
            ),

        );
        
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
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json_array));

        //To make request call
        $response = curl_exec($ch);
        //$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        //To close curl
        curl_close($ch);
        
        return json_decode($response);
    }

    
    /**
     * OVERRIDE
     * Function is to check if payment correct
     * @param \SS_HTTPRequest $request
     */
    public function check(\SS_HTTPRequest $request) {
        
        $payment_id = $request->postVar("paymentID");
        $payer_id = $request->postVar("payerID");
        
        
        if(!$payment_id || !$payer_id){
            die("NOT SETUP PAYMENT ID & PAYER ID");
        }
        
        
        //If access token expired,
        //we need to request new access token
        if($this->isTokenExpired()){
            $this->requestAccessToken();
        }else{
            $this->onBeforeProcess();
        }
        
        // Initialize our cURL handle.
        $ch = curl_init($this->payment_url . "/{$payment_id}/execute");

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
        curl_setopt(
            $ch, 
            CURLOPT_POSTFIELDS, 
            json_encode(array('payer_id' => $payer_id))
        );

        //To make request call
        $response = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        //To close curl
        curl_close($ch);
        
        //To convert response into JSON object
        $jsonObj = json_decode($response);
        
        //To check response state
        if($jsonObj->state === "approved"){
            return new FF_PaymentGateway_Success($jsonObj);
        }else{
            return new FF_PaymentGateway_Failure($jsonObj);
        }
        
    }
    
}



/**
 * TODO
 * define the PayPal payment processor
 */
class FF_PaymentProcessor_PayPal extends FF_PaymentProcessor_GatewayHosted{
    
    private static $allowed_actions = array(
        'capture',
        'complete'
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
        //To get passing data
        $data = $this->getData($this->request);
        
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
        
        
        // To call setup gateway
        $this->setupGateway();
        
        
        // Send a request to the gateway
        $result = $this->gateway->process($this->paymentData);

        //To check if result correct
        if(isset($result->error)){
            $payment_result = new FF_PaymentGateway_Failure();
            $this->payment->updateStatus($payment_result);
            die;
        }
        
        //To return json result
        echo json_encode(array(
            'paymentID' => $result->id,
            // we need to return ff payment id
            'ffPaymentID' => $this->payment->ID
        ));
        die;
    }
    
    
    /**
     * Function is to get post data
     * @param SS_HTTPRequest $request
     * @return type
     */
    public function getData(SS_HTTPRequest $request){
        
        //To get data from request
        $amount = $request->postVar("amount");
        $currency = $request->postVar("currency");
        $reference = $request->postVar("reference");
        
        //To init data array
        $data = array(
            "Amount" => $amount,
            "Currency" => $currency,
            "Reference" => $reference
        );
        
        //To extend call back function
        $this->extend("extendGetData", $request, $data);
        
        return $data;
    }
    
    
    
    
    /**
     * OVERRIDE
     * @param \SS_HTTPRequest $request
     */
    public function complete(\SS_HTTPRequest $request) {
        
        // Reconstruct the payment object
        $this->payment = FF_Payment::get()->byID($request->param('PaymentID'));
        
        //To call check function
        $result = $this->gateway->check($request);
        $this->payment->updateStatus($result);
        
        $state = false;
        
        if ($result && $result->isSuccess()) {
            $state = true;
        }
        
        //To get return data
        echo json_encode(array(
            "State" => $state,
            'FFPaymentID' => $this->payment->ID
        ));
        die;
    }
    
}
