<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FF_PaymentGateway_Response
 *
 * @author Fang
 */
abstract class FF_PaymentGateway_Response {
    
    //To hold the original response input from gateway
    protected $response;
    
    //To hold the data array for response
    protected $response_data;


    public function __construct($response_string) {
        $this->response = $response_string;
        //To convert response into data array
        $this->response_data = $this->convert($this->response);
    }
    
    
    /**
     * Function is to get original response input from gateway
     * @return type
     */
    public function getResponse(){
        return $this->response;
    }
    
    
    /**
     * Function is to get response data
     * @return type
     */
    public function getResponseData(){
        return $this->response_data;
    }

    
    /**
     * Function is to get value according to the array key
     * @param type $key
     * @return type
     */
    public function get($key){
        
        if(
            $this->response_data && 
            is_array($this->response_data) && 
            array_key_exists($key, $this->response_data)
        ){
            return $this->response_data[$key];
        }
        
        return NULL;
    }
    
    



    /**
     * Function is to convert response string into
     * data array
     * MUST BE OVERRIED
     */
    public abstract function convert($response);
    
    
}
