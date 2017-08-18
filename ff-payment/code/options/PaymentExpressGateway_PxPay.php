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
    
    /**
     * @config
     * @var type 
     */
    protected $supportedCurrencies = array(
        'NZD' => 'New Zealand Dollar',
        'USD' => 'United States Dollar',
        'GBP' => 'Great British Pound'
    );
   
    public function process($data, $payment) {
        
    }

}
