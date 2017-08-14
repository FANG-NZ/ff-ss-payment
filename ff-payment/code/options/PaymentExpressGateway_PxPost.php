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
    
    //define the request url
    protected static $url;
    
    /**
     * @config
     * @var string
     */
    protected static $pxpost_username;
    protected static $pxpost_password;
    
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
        
        if(!self::$pxpost_username){
            self::$pxpost_username = $config['authentication']['user_name'];
        }
        
        if(!self::$pxpost_password){
            self::$pxpost_password = $config['authentication']['password'];
        }
        
    }
    
    
    /**
     * OVERRIDE
     * @param type $data
     */
    public function process($data, $payment) {
        $this->onBeforeProcess();
        
        //clear data
        unset($data["Status"]);
        unset($data["CreditCardType"]);
        
        // Main Settings
        //$inputs = array();
        $data['PostUsername'] = self::$pxpost_username;
        $data['PostPassword'] = self::$pxpost_password;
        //$inputs = array();
        
        $data["CardHolderName"] = $data["LastName"] . " " . $data["FirstName"];
        unset($data["FirstName"]);
        unset($data["LastName"]);
        
        $data["DateExpiry"] = $data["MonthExpiry"] . $data["YearExpiry"];
        unset($data["MonthExpiry"]);
        unset($data["YearExpiry"]);
        
        $data["TxnType"] = "Purchase";
        
        // Transaction Creation
        $transaction = "<Txn>";
        foreach ($data as $name => $value) {
            
            if ($name == "Amount") {
                $value = number_format($value, 2, '.', '');
            }
            
            if($name == "Reference"){
                $name = "MerchantReference";
            }
            
            if($name == "Currency"){
                $name = "InputCurrency";
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
            return new PaymentGateway_Success();
        }else{
            return new PaymentGateway_Failure();
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
        curl_setopt($ch, CURLOPT_URL, self::$pxpost_url);
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
