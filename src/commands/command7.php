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
use wdg5\model\wikidata\Property;
use tiglib\strings\slugify;

class command7 {
    
    /** Connection to wd-occus local sqlite database **/
    private static \PDO $occus_sqlite_conn;
    
    /** Connection to wd-g5 local sqlite database **/
    private static \PDO $wd_g5_sqlite_conn;
    
    public static function execute(): void {
        
        $a = readline('This will delete existing wd-occus sqlite database. Are you sure ? (y/N) '); 
        if(strtolower(trim($a)) != 'y'){
            echo "OK, prgram ends, nothing was modified.\n";
            return;
        }
        
        self::initializeSqlite();
        self::$wd_g5_sqlite_conn = Sqlite::getConnection(Config::$data['sqlite']['wd-g5']);
        
        // Fills wd-occus database with occupations contained in wd-g5 database
        // Repetition of code with command4
        
        // Get the occupations from xd-g5 database
        $g5_occus = [];
        foreach (self::$wd_g5_sqlite_conn->query('select wd_data from wd_g5 where is_wd_stored = 1', \PDO::FETCH_ASSOC) as $row){
            $data_wd = json_decode($row['wd_data'], true);
            foreach($data_wd as $candidate){
                if(!isset($candidate[Property::OCCUPATION])){
                    continue;
                }
                $occus =& $candidate[Property::OCCUPATION]['values'];
                foreach($occus as $occu){
                    if(substr($occu['id'], 0, 1) != 'Q'){
                        // some occupation codes correspond to strange urls
                        // leading to 404 => not handled
                        continue;
                    }
                    $g5_occus[$occu['id']] = $occu['label'];
                }
            }
        }
        
        // Initialize wd-occus database with occupations from wd-g5
        $sqlite_insert = self::$occus_sqlite_conn->prepare('
            insert into wd_occus(
                wd_id,
                wd_label,
                slug
            ) values(?, ?, ?)
        ');
        
        foreach($g5_occus as $id => $label){
            $slug = slugify::compute($label);
            $sqlite_insert->execute([
                $id,
                $label,
                $slug,
            ]);
        }
        echo "Filled wd-occus database with occupations of wd-g5 database\n";
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
