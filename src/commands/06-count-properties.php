<?php
/******************************************************************************
    Lists the maximal number of times a single person of wd-g5 database has
    a given property, for properties of Property::USEFUL_PROPERTIES.
    Result : 
        [P2561] => 3
        [P1477] => 5
        [P734] => 4
        [P735] => 10
        [P1813] => 2
        [P1449] => 7
        [P1448] => 1
        [P2562] => 3
        [P569] => 5
        [P19] => 3
        [P570] => 5
        [P20] => 3
        [P106] => 20
        [P21] => 3

    @license    GPL
    @history    2025-05-02 22:53:07+02:00, Thierry Graff : Creation
********************************************************************************/

declare(strict_types=1);

namespace wdg5\commands;

use wdg5\app\Config;
use wdg5\app\Sqlite;
use wdg5\model\wikidata\Property;
use wdg5\model\wikidata\Entity;

class command06 {
    
    /** Local sqlite database, specific to wd-g5 **/
    private static \PDO $sqlite_conn;
    
    public static function execute(): void {
        
        self::$sqlite_conn = Sqlite::getConnection(Config::$data['sqlite']['wd-g5']);
        
        // Contains the max nb of values of properties
        // initialize to 0 for all properies
        $res = array_combine(
            Property::USEFUL_PROPERTIES,
            array_fill(0, count(Property::USEFUL_PROPERTIES), 0)
        );
        foreach (self::$sqlite_conn->query('select * from wd_g5 where is_wd_stored = 1', \PDO::FETCH_ASSOC) as $row){
            $data_wd = json_decode($row['wd_data'], true);
            foreach($data_wd as $id_wd => $candidate){
                if(!isset($candidate[Property::INSTANCE_OF])){
                    continue;
                }
                if($candidate[Property::INSTANCE_OF]['values'][0]['id'] != Entity::HUMAN){
                    continue;
                }
                foreach($candidate as $propId => $propFields){
                    if(!in_array($propId, Property::USEFUL_PROPERTIES)){
                        continue;
                    }
                    if(count($propFields['values']) > $res[$propId]) {
                        $res[$propId] = count($propFields['values']);
                    }
                }
            }
        }
        print_r($res);
    }
    
} // end class
