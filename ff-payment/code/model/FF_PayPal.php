<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FF_PayPal
 *
 * @author Fang
 */
class FF_PayPal extends DataObject {
    
    private static $db = array(
        "Token" => "Varchar(300)",
        "Expires" => "SS_Datetime"
    );
    
    
}
