<?php
/********************************************************************************
    Holds config.yml informations.
    
    Config values available via Config::$data
    
    @license    GPL - conforms to file LICENCE located in root directory of current repository.
    @history    2025-04-14 18:34:45+02:00, Thierry Graff : Creation (copy from g5 code)
********************************************************************************/
namespace wdg5\app;

class Config {
    
    /** Associative array containing config.yml **/
    public static $data = null;
    
    public static function init(){
        
        //
        // load wd-g5 config
        //
        $filename = dirname(dirname(__DIR__)) . DS . 'config.yml';
        if(!is_file($filename)){
            echo "MISSING CONFIG FILE : $filename.\n";
            echo "Create this file and try again.\n";
            exit;
        }
        self::$data = @yaml_parse(file_get_contents($filename));
        if(self::$data === false){
            echo "INVALID SYNTAX IN CONFIG FILE config.yml.\n";
            echo "Check syntax and try again\n";
            exit;
        }
        
        //
        // load g5 config
        //
        if(!isset(self::$data['g5-config-path'])){
            echo "MISSING KEY 'g5-config-path' IN CONFIG FILE config.yml.\n";
            echo "Fix config.yml and try again\n";
            exit;
        }
        $filename = self::$data['g5-config-path'];
        if(!is_file($filename)){
            echo "UNEXISTING FILE $filename.\n";
            echo "Put a correct value in key 'g5-config-path' of config.yml and try again\n";
            exit;
        }
        
        $g5Data = @yaml_parse(file_get_contents($filename));
        if($g5Data === false){
            echo "INVALID SYNTAX IN g5 CONFIG FILE $filename.\n";
            echo "Check syntax and try again\n";
            exit;
        }
        if(!isset($g5Data['db5']['postgresql'])){
            echo "MISSING KEY ['db5']['postgresql'] IN g5 CONFIG FILE $filename.\n";
            echo "Fix g5 config and try again\n";
            exit;
        }
        self::$data['db5'] = $g5Data['db5'];
        
    }
    
} // end class

