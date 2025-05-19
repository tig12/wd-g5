<?php
/******************************************************************************
    Creates a local sqlite database (key sqlite.wd-occus of config.yml) 
    to contain the hierarchy of wikidata occupations.
    
    Initializes wd-occus database with the occupation codes contained in wd-g5 local database
    (so step 1 and 2 must have been executed before).
    
    @license    GPL
    @history    2025-05-18 08:02:59+02:00, Thierry Graff : Creation
********************************************************************************/

declare(strict_types=1);

namespace wdg5\commands;

use wdg5\app\Config;
use wdg5\app\Sqlite;

class command7 {
    
    /** Connection to wd-occus local sqlite database **/
    private static \PDO $occus_sqlite_conn;
    
    public static function execute(): void {
        
        $a = readline('This will delete existing wd-occus sqlite database. Are you sure ? (y/N) '); 
        if(strtolower(trim($a)) != 'y'){
            echo "OK, prgram ends, nothing was modified.\n";
            return;
        }
        
        self::initializeSqlite();
        
    }
    
    private static function initializeSqlite(): void {
        
        if(!isset(Config::$data['sqlite']['wd-occus'])){
            throw new \Exception("MISSING KEY ['sqlite']['wd-occus'] IN CONFIG FILE config.yml.\n");
        }
        
        $sqlite_path = Config::$data['sqlite']['wd-occus'];
        
        $dir = dirname($sqlite_path);
        if(!is_dir($dir)) {
            echo "Created directory $dir\n";
            mkdir($dir, 0777, true);
        }
        
        if(is_file($sqlite_path)){
            unlink($sqlite_path);
        }
        
        self::$occus_sqlite_conn = Sqlite::getConnection($sqlite_path);
        $sql = file_get_contents(dirname(dirname(__FILE__)) . DS . 'model' . DS . 'wd-occus.sql');
        self::$occus_sqlite_conn->exec($sql);
        echo "Initialized local sqlite database $sqlite_path.\n";
    }
    
} // end class
