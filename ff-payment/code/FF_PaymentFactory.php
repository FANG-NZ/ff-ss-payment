<?php

/**
 * Helper class for creating payment processors
 */
class FF_PaymentFactory {

    /**
     * Get the factory config for a particular payment method from YAML file.
     *
     * @param String methodName
     * @return Array|Null Configuration options
     */
    public static function get_factory_config($methodName) {
        $factoryConfig = Config::inst()->get('FF_PaymentFactory', $methodName);
        
        if ($factoryConfig) {
            return $factoryConfig;
        } else {
            return null;
        }
    }
    

    /**
     * Get the gateway object that will be used for the given payment method.
     * The gateway class is automatically retrieved based on configuration
     *
     * @param String $methodName
     * @return PaymentGateway
     */
    public static function get_gateway($methodName) {

        // Get the gateway environment setting
        $environment = FF_PaymentGateway::get_environment();

        // Get the custom class configuration if applicable.
        // If not, apply naming convention.
        $methodConfig = self::get_factory_config($methodName);
        $gatewayClassConfig = $methodConfig['gateway_classes'];

        
        
        if (isset($gatewayClassConfig[$environment])) {
            $gatewayClass = $gatewayClassConfig[$environment];
        } 
        else {
            throw new Exception("Payment Gateway class NOT defined.");
        }

        if (class_exists($gatewayClass)) {
            return new $gatewayClass();
        } 
        else {
            throw new Exception("{$gatewayClass} class does not exists.");
        }
    }

    
    
    /**
     * Get the payment object that will be used for the given payment method.
     *
     * The payment class is automatically retrieved based on naming convention
     * if not specified in the yaml config.
     *
     * @param String methodName
     * @return Payment
     */
    public static function get_payment_model($methodName) {

        // Get the custom payment class configuration.
        // If not applicable, take the default model
        $methodConfig = self::get_factory_config($methodName);
        
        if (isset($methodConfig['model'])) {
            $paymentClass = $methodConfig['model'];
        } else {
            throw new Exception("Payment model class NOT defined");
        }

        if (class_exists($paymentClass)) {
            return new $paymentClass();
        } else {
            throw new Exception("{$paymentClass} class does not exists");
        }
        
    }

        
        
    /**
     * Factory function to create payment processor object with associated
     * Payment and PaymentGateway injected into it.
     *
     * @param String $methodName
     * @return PaymentProcessor
     */
    public static function factory($methodName) {
        $supported_methods = FF_PaymentProcessor::get_supported_methods();
        
        if (! in_array($methodName, $supported_methods)) {
            throw new Exception("The method $methodName is not supported");
        }

        //To get method config
        $methodConfig = self::get_factory_config($methodName);
        

        // TODO
        // init payment processor
        if (isset($methodConfig['processor'])) {

            $processorClass = $methodConfig['processor'];
            $processor = new $processorClass();
            
            
            
            $processor->setMethodName($methodName);
            $processor->setPaymentGateway(self::get_gateway($methodName));
            $processor->setPayment(self::get_payment_model($methodName));
            
            return $processor;
        } 
        else {
            throw new Exception("No processor is defined for the method $methodName");
        }
    }
    
}