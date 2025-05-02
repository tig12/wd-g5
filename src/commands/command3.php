<?php
/******************************************************************************
    Lists the properties retrieved from wikidata.
    
    Usage to store result: php run-wd-g5.php 3 > /path/to/properties.json
    
    @license    GPL
    @history    2025-05-02 18:28:10+02:00, Thierry Graff : Creation
********************************************************************************/

declare(strict_types=1);

namespace wdg5\commands;

use wdg5\app\Sqlite;

class command3 {
    
    /** Local sqlite database, specific to wd-g5 **/
    private static \PDO $sqlite_conn;
    
    /** 
        Computes the list of properties found in the data retrieved from wikidata.
    **/
    public static function execute(): void {
        
        self::$sqlite_conn = Sqlite::getConnection();
        
        $res = [];
        foreach (self::$sqlite_conn->query('select wd_data from wd_g5 where is_wd_stored = 1', \PDO::FETCH_ASSOC) as $row){
            $data_wd = json_decode($row['wd_data'], true);
            foreach($data_wd as $id_wd => $candidate){
                foreach($candidate as $propId => $propValue){
                    $res[$propValue['id']] = $propValue['label'];
                }
            }
        }
        asort($res);
        echo json_encode($res, JSON_PRETTY_PRINT) . "\n";
    }
    
} // end class
