<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PoliPaymentGateway
 *
 * @author techonsite
 */
class PoliPaymentGateway extends FF_PaymentGateway_GatewayHosted {
    
    /**
     * @config
     * @var string
     */
    protected $merchant_code;
    protected $authentication_code;
    protected $check_url;


    /**
     * @config
     * @var type 
     */
    protected $supportedCurrencies = array(
        'NZD' => 'New Zealand Dollar'
    );
    
    



    /**
     * Function is to setup default setting
     */
    private function onBeforeProcess(){
        $config = $this->getConfig();
        
        //To setup merchant code
        if(!$this->merchant_code){
            $this->merchant_code = $config['authentication']['merchant_code'];
        }
        
        //To setup authentication code
        if(!$this->authentication_code){
            $this->authentication_code = $config['authentication']['authentication_code'];
        }
        
        //TODO
        //setup base url
        if(!$this->gatewayURL){
            $this->gatewayURL = $config['base_url'];
        }
        
        if(!$this->check_url){
            $this->check_url = $config['check_url'];
        }
    }
    
    
    /**
     * OVERRIDE
     * @param type $data
     */
    public function validate($data) {
        $validationResult = parent::validate($data);
        
        //To check if setup HomeURL 
        if (!isset($data['HomeURL'])) {
            $validationResult->error('The Home page URL [HomeURL] cannot be EMPTY');
        }
        
        return $validationResult;
    }
    
    
    
    /**
     * OVERRIDE
     * @param type $data
     */
    public function process($data) {
        $this->onBeforeProcess();
        
        //To setup json array
        $json_array = array(
            'Amount' => $data["Amount"],
            'CurrencyCode' => $data["Currency"],
            'MerchantHomepageURL' => $data['HomeURL'],
            
            'MerchantReference' => $data['Reference'],
            
            'SuccessURL' => $this->returnURL,
            'CancellationURL' => $this->cancelURL,
            'FailureURL' => $this->returnURL,
            'NotificationURL' => $this->returnURL
        );
        
        
        
        //To get json string
        $json_string = json_encode($json_array);

        //To setup auth
        $auth = base64_encode($this->merchant_code . ":" . $this->authentication_code);
        $header = array();
        $header[] = 'Content-Type: application/json';
        $header[] = 'Authorization: Basic '.$auth;

        //To get ca-file path
        $file_path = str_replace('options', 'tools', dirname(__FILE__));
        
        //TODO
        //call curl_init
        $ch = curl_init($this->gatewayURL);
        //See the cURL documentation for more information: http://curl.haxx.se/docs/sslcerts.html
        //We recommend using this bundle: https://raw.githubusercontent.com/bagder/ca-bundle/master/ca-bundle.crt
        curl_setopt( $ch, CURLOPT_CAINFO, $file_path . "/ca-bundle.crt");
        //CURL_SSLVERSION_TLSv1_2 == 6
        curl_setopt( $ch, CURLOPT_SSLVERSION, 6);
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt( $ch, CURLOPT_HEADER, 0);
        curl_setopt( $ch, CURLOPT_POST, 1);
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $json_string);
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec( $ch );
        curl_close ($ch);

        //TODO
        //get result array
        $result = json_decode($response, true);
        
        
        if($result && $result["Success"]){
            //Redirect to payment page
            Controller::curr()->redirect($result["NavigateURL"]);
        }else{
            return new FF_PaymentGateway_Failure($result);
        }
        
    }
    
    
    
    /**
     * OVERRIDE
     * @param type $request
     */
    public function check(SS_HTTPRequest $request) {
        $token = $request->postVar("Token");
        if(is_null($token)){
            $token = $request->getVar("token");
        }
        
        if(is_null($token)){
            throw new Exception("[TOKEN] is EMPTY");
        }
        
        //call on before process setup
        $this->onBeforeProcess();
        
        //To setup auth
        $auth = base64_encode($this->merchant_code . ":" . $this->authentication_code);
        $header = array();
        $header[] = 'Authorization: Basic '.$auth;

        //To get ca-file path
        $file_path = str_replace('options', 'tools', dirname(__FILE__));
        
        //TODO
        //call curl_init
        $ch = curl_init($this->check_url . "?token=" . urldecode($token));
        //See the cURL documentation for more information: http://curl.haxx.se/docs/sslcerts.html
        //We recommend using this bundle: https://raw.githubusercontent.com/bagder/ca-bundle/master/ca-bundle.crt
        curl_setopt( $ch, CURLOPT_CAINFO, $file_path . "/ca-bundle.crt");
        //CURL_SSLVERSION_TLSv1_2 == 6
        curl_setopt( $ch, CURLOPT_SSLVERSION, 6);
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt( $ch, CURLOPT_HEADER, 0);
        curl_setopt( $ch, CURLOPT_POST, 0);
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
        
        $response = curl_exec( $ch );
        curl_close ($ch);

        //TODO
        //get result array
        $result = json_decode($response, true);
        
        
        
        //To check out response result
        if ($result['TransactionStatusCode'] == "Completed") {
            return new FF_PaymentGateway_Success($result);
        }
        else {
            return new FF_PaymentGateway_Incomplete($result);
        }
    }
    
    
}




