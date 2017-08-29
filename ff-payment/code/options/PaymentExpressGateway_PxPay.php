<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PaymentExpressGateway_PxPay
 *
 * @author Fang
 */
class PaymentExpressGateway_PxPay extends FF_PaymentGateway_GatewayHosted {
    
    protected $pxpay_userid;
    protected $pxpay_key;
    
    
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
     * Function is to setup default setting
     */
    private function onBeforeProcess(){
        $config = $this->getConfig();
        
        //TODO
        //setup user id
        if(!$this->pxpay_userid){
            $this->pxpay_userid = $config['authentication']['user_id'];
        }
        
        //TODO
        //setup user key
        if(!$this->pxpay_key){
            $this->pxpay_key = $config['authentication']['key'];
        }
        
        //TODO
        //setup url
        if(!$this->gatewayURL){
            $this->gatewayURL = $config['url'];
        }
        
    }
    
    
    //data structure
    /**
     * Amount
     * Currency
     * Reference
     * Email
     * 
     * EnableRebilling
     * BillingID
     * DpsBillingID
     */
   
    
    /**
     * OVERRIDE
     * @param type $data
     */
    public function process($data) {
        //Construct the request
        $request = new PxPayRequest();
        
        //START setup request data
        $request->setAmountInput($data['Amount']);
        $request->setCurrencyInput($data['Currency']);
        
        //Set PxPay properties
        if (isset($data['Reference'])){ 
            $request->setMerchantReference($data['Reference']);
        }
	if (isset($data['Email'])){ 
            $request->setEmailAddress($data['Email']);
        }
        
        //To setup DPS billing ID & Billing ID
        if(
            isset($data['BillingID']) || 
            isset($data['DpsBillingID'])
        ){
            
            if(isset($data['BillingID'])){
                $request->setBillingId($data['BillingID']);
            }
        
            if(isset($data['DpsBillingID'])){
                $request->setDpsBillingId($data['DpsBillingID']);
            }
            
        }
        else{
            
            //To setup Enable rebilling function
            if(isset($data['EnableRebilling'])){
                $request->setEnableAddBillCard(TRUE);
            }
            
        }//END setup DPSBillingID & BillingID
        
        
        $request->setUrlFail($this->cancelURL);
        $request->setUrlSuccess($this->returnURL);

        //Generate a unique identifier for the transaction
        $request->setTxnId(uniqid('ID')); 
        $request->setTxnType('Purchase');
                
        //Get encrypted URL from DPS to redirect the user to
        $request_string = $this->makeProcessRequest($request);

        //Obtain output XML
        $response = new MifMessage($request_string);
        
        //Parse output XML
        $url = $response->get_element_text('URI');
        $valid = $response->get_attribute('valid');
    
        
    
        //If this is a fail or incomplete (cannot reach gateway) then mark payment accordingly and redirect to payment 
        if ($valid && is_numeric($valid) && $valid == 1) {
            //Redirect to payment page
            Controller::curr()->redirect($url);
        }
        else if (is_numeric($valid) && $valid == 0) {
            return new FF_PaymentGateway_Failure($request_string);
        }
        else{
            return new FF_PaymentGateway_Incomplete($request_string);
        }
        
    }//End process
    
    
    /**
     * Function is to make process request
     * @param type $request
     * @return type
     */
    public function makeProcessRequest($request) {
        $this->onBeforeProcess();
        
        $pxpay = new PxPay_Curl($this->gatewayURL, $this->pxpay_userid, $this->pxpay_key);
        return $pxpay->makeRequest($request);
    }
    
    
    /**
     * Check that the payment was successful using "Process Response" API 
     * (http://www.paymentexpress.com/Technical_Resources/Ecommerce_Hosted/PxPay.aspx).
     * 
     * @param SS_HTTPRequest $request Request from the gateway - transaction response
     * @return PaymentGateway_Result
     */ 
    public function check($request) {
        $result = $request->getVar('result');

        //Construct the request to check the payment status
        $pxpay_request = new PxPayLookupRequest();
        $pxpay_request->setResponse($result);

        //Get encrypted URL from DPS to redirect the user to
        $request_string = $this->makeProcessRequest($pxpay_request);

        //Obtain output XML
        $response = new MifMessage($request_string);
        
        //Parse output XML
        $success = $response->get_element_text('Success');

        //To check out response result
        if ($success && is_numeric($success) && $success > 0) {
            return new FF_PaymentGateway_Success();
        }
        else if (is_numeric($success) && $success == 0) {
            return new FF_PaymentGateway_Failure();
        }
        else {
            return new FF_PaymentGateway_Incomplete();
        }
    }
    

}
