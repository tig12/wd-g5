<?php
/******************************************************************************
    Check if birth times are always set to '00:00:00'
    (answer = yes)
    
    @license    GPL
    @history    2025-05-02 22:38:51+02:00, Thierry Graff : Creation
********************************************************************************/

declare(strict_types=1);

namespace wdg5\commands;

use wdg5\app\Config;
use wdg5\app\Sqlite;
use wdg5\model\wikidata\Property;

class command5 {
    
    /** Local sqlite database, specific to wd-g5 **/
    private static \PDO $sqlite_conn;
    
    public static function execute(): void {
        
        self::$sqlite_conn = Sqlite::getConnection(Config::$data['sqlite']['wd-g5']);
        
        foreach (self::$sqlite_conn->query('select g5_slug, wd_data from wd_g5 where is_wd_stored = 1', \PDO::FETCH_ASSOC) as $row){
            $data_wd = json_decode($row['wd_data'], true);
            foreach($data_wd as $candidate){
                if(!isset($candidate[Property::DATE_OF_BIRTH])){
                    continue;
                }
                $dates =& $candidate[Property::DATE_OF_BIRTH]['values'];
                foreach($dates as $date){
                    $hour = substr($date['id'], 11, 8);
                    if($hour != '00:00:00' && $hour != 'wikidata'){
                        echo "{$row['g5_slug']} $hour\n";
                    }
                }
            }
        }
        echo "done (no display = no hour != '00:00:00' in wikidata persons)\n";
    }
    
} // end class
