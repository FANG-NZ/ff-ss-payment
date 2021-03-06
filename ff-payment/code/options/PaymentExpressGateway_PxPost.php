<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PaymentExpressGateway_PxPost
 *
 * @author techonsite
 */
class PaymentExpressGateway_PxPost extends FF_PaymentGateway_MerchantHosted {
    
    
    /**
     * @config
     * @var string
     */
    protected static $pxpost_username;
    protected static $pxpost_password;
    
    
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
        //setup username
        if(!self::$pxpost_username){
            self::$pxpost_username = $config['authentication']['user_name'];
        }
        
        //TODO
        //setup password
        if(!self::$pxpost_password){
            self::$pxpost_password = $config['authentication']['password'];
        }
        
        //TODO
        //setup url
        if(!$this->gatewayURL){
            $this->gatewayURL = $config['url'];
        }
        
    }
    
    
    //data structure
    /**
     * 
     * PostUsername
     * PostPassword
     * 
     * Amount
     * Currency
     * Reference
     * Email
     * 
     * MonthExpiry
     * YearExpiry
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
        $this->onBeforeProcess();
        
        
        // Main Settings
        $data['PostUsername'] = self::$pxpost_username;
        $data['PostPassword'] = self::$pxpost_password;

      
        //To setup Month & Year
        if(isset($data["MonthExpiry"]) && isset($data["YearExpiry"])){
            $data["DateExpiry"] = $data["MonthExpiry"] . $data["YearExpiry"];
        }
        unset($data["MonthExpiry"]);
        unset($data["YearExpiry"]);
        
        //Add TxnType
        $data["TxnType"] = "Purchase";
        
        //To setup EnableAddBillCard
        if(isset($data["EnableRebilling"])){
            
            if($data["EnableRebilling"] === TRUE){
                $data["EnableAddBillCard"] = TRUE;
            }
            
            unset($data["EnableRebilling"]);
        }
        
        
        // Transaction Creation
        $transaction = "<Txn>";
        foreach ($data as $name => $value) {
            
            if ($name == "Amount") {
                $value = number_format($value, 2, '.', '');
            }
            
            if($name == "CardName"){
                $name = "CardHolderName";
            }
            
            if($name == "SecurityCode"){
                $name = "Cvc2";
            }
            
            if($name == "Reference"){
                $name = "MerchantReference";
            }
            
            if($name == "Currency"){
                $name = "InputCurrency";
            }
            
            if($name == "BillingID"){
                $name = "BillingId";
            }
            
            if($name == "DpsBillingID"){
                $name = "DpsBillingId";
            }
            
            
            $XML_name = Convert::raw2xml($name);
            $XML_value = Convert::raw2xml($value);
            $transaction .= "<$XML_name>$XML_value</$XML_name>";
        }
        $transaction .= "</Txn>";
        
        //To send request
        $resultXml = $this->post($transaction);
        //To get convert response
        $result = $this->parserXML($resultXml);
        
        
        if ($result['SUCCESS']) {
            return new FF_PaymentGateway_Success($result);
        }else{
            return new FF_PaymentGateway_Failure($result);
        }
    }
    
    
    /**
     * Function is to parse xml response
     * @param type $xml
     */
    private function parserXML($xml){
        
        $xmlParser = xml_parser_create();
        $values = null;
        $indexes = null;
        xml_parse_into_struct($xmlParser, $xml, $values, $indexes);
        xml_parser_free($xmlParser);
        
        // XML Result Parsed In A PHP Array
        $resultPhp = array();
        $level = array();
        foreach ($values as $xmlElement) {
            if ($xmlElement['type'] == 'open') {
                if (array_key_exists('attributes', $xmlElement)) {
                    list($level[$xmlElement['level']], $extra) = array_values($xmlElement['attributes']);
                } else {
                    $level[$xmlElement['level']] = $xmlElement['tag'];
                }
            } elseif ($xmlElement['type'] == 'complete') {
                $startLevel = 1;
                $phpArray = '$resultPhp';
                while ($startLevel < $xmlElement['level']) {
                    $phpArray .= '[$level['. $startLevel++ .']]';
                }
                $phpArray .= '[$xmlElement[\'tag\']] = array_key_exists(\'value\', $xmlElement)? $xmlElement[\'value\'] : null;';
                eval($phpArray);
            }
        }
        
        return $resultPhp['TXN'];
    }
    
    
    
    
    
    /**
     * Function is to send post request
     * @param type $data
     * @return type
     * @throws Exception
     */
    private function post($data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->gatewayURL);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($clientURL, CURLOPT_SSL_VERIFYPEER, 0); //Needs to be included if no *.crt is available to verify SSL certificates
        if (defined('CAINFO')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_CAINFO, CAINFO);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }
        
        $result = curl_exec($ch);
        
        if (curl_error($ch)) {
            throw new Exception(curl_error($ch));
        }

        curl_close($ch);
        
        return $result;
    }
    
}
