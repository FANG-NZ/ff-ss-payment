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
    
    
    //TODO
    //define the notification url for polipay
    private $notificationURL;
    
    public function setNotificationURL($url){
        $this->notificationURL = $url;
    }
    
    public function getNotificationURL(){
        return $this->notificationURL;
    }
    
    



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
            'NotificationURL' => $this->notificationURL
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
     * Function is to do check nudge page
     * @param SS_HTTPRequest $request
     * @return \FF_PaymentGateway_Incomplete|\FF_PaymentGateway_Success
     * @throws Exception
     */
    public function check_nudge(SS_HTTPRequest $request) {
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



/**
 * TODO
 * define the payment processor fpr PoliPay
 * 
 * http://www.ffpayment.local/payment/complete/PoliPayment/16?token=HeZp0uYuRDZWK7JojBU08Elc2TzdcMGB
 */
class PoliPay_PaymentProcessor_GatewayHosted extends FF_PaymentProcessor_GatewayHosted{
    
    private static $allowed_actions = array(
        'notify'
    );
    
    
    
    /**
     * OVERRIDE
     */
    protected function setupGateway() {
        parent::setupGateway();
        
        //To set notification url
        $notify_url = Director::absoluteURL(Controller::join_links(
                        $this->Link(),
                        'notify',
                        $this->methodName,
                        $this->payment->ID
        ));
        $this->gateway->setNotificationURL($notify_url);
    }

    
    /**
     * OVERRIDE
     * @param \SS_HTTPRequest $request
     * @return string
     */
    protected function getMethodName(\SS_HTTPRequest $request) {
        return "PoliPayment";
    }




    /**
     * http://www.ffpayment.local/ff-payment/notify/PoliPayment/2?token=8lS%2fDlT10K2jbqS1tOv%2fgW0%2bNf4ZLTJG
     * Function is to handle notify callback
     * @param SS_HTTPRequest $request
     */
    public function notify(SS_HTTPRequest $request){
        
        // Reconstruct the payment object
        $this->payment = FF_Payment::get()->byID($request->param('PaymentID'));
        
        //To check payment status
//        if(!$this->payment->isPending()){
//            //If payment is NOT pending, we do NOTHING here
//            die;
//        }
        

        // Reconstruct the gateway object
        // get the method name
        $methodName = $this->getMethodName($request);
        // call factory to reconstruct payment gateway
        $this->gateway = FF_PaymentFactory::get_gateway($methodName);

        // Query the gateway for the payment result
        //$result = $this->gateway->check_nudge($request);
        $result = new FF_PaymentGateway_Success();
        //To update payment
        $this->payment->updateStatus($result);
        
    }
    
}
