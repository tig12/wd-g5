<?php
/******************************************************************************
    Fills local sqlite database wd-occus with the hierarchy of wikidata occupations
    using "subclass of" (P279).
    
    Uses https://github.com/freearhey/wikidata
    
    This command can be used several times and will not perform twice the same request.
    
    @license    GPL
    @history    2025-05-18 08:15:49+02:00, Thierry Graff : Creation
********************************************************************************/

declare(strict_types=1);

namespace wdg5\commands;

use wdg5\app\Config;
use wdg5\app\Sqlite;
use wdg5\model\wikidata\Property;
use Wikidata\Wikidata;
use tiglib\misc\dosleep;

class command8 {
    
    private static bool $dump = true;
    
    public static function execute(): void {
        // Fills wd-occus database with occupations contained in wd-g5 database
        // Repetition of code with command4
        
        // Get all occupations corresponding to persons stored in command2
        
        $wd_g5_sqlite_conn = Sqlite::getConnection(Config::$data['sqlite']['wd-g5']);
        $occus_sqlite_conn = Sqlite::getConnection(Config::$data['sqlite']['wd-occus']);

        $g5_occus = [];
        foreach ($wd_g5_sqlite_conn->query('select wd_data from wd_g5 where is_wd_stored = 1', \PDO::FETCH_ASSOC) as $row){
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
        
        // Fetch from wikidata the parents of these occupations
        // Now work only in wd_occu database
        
        $wikidata = new Wikidata();
        $sqlite_select = $occus_sqlite_conn->prepare('
            select * from wd_occus where wd_id = ?
        ');
        $sqlite_insert = $occus_sqlite_conn->prepare('
            insert into wd_occus(
                wd_id,
                wd_label,
                slug
            ) values(?, ?, ?)
        ');
        
        
        foreach($g5_occus as $g5_occu_id => $g5_occu_label){
            self::dump("=== Processing $g5_occu_id : $g5_occu_label ===\n");
            // check if $id already stored in wd-occus database
            $sqlite_select->execute([$g5_occu_id]);
            $tmp = $sqlite_select->fetchAll();
            if(count($tmp) != 0) {
                self::dump("    Already stored\n");
                continue;
            }
            $occu = $wikidata->get($g5_occu_id);
            $properties = $occu->properties->toArray();
            
            if(isset($properties[Property::SUBCLASS_OF])){
                self::dump("    Parents:\n");
                $parents_to_store = [];
                foreach($properties[Property::SUBCLASS_OF]->values as $wd_parent){
                    $parent_id = $wd_parent->id;
                    $parent_label = $wd_parent->label;
                    self::dump("        $parent_id $parent_label\n");
                    // check if parent is already stored
                    $sqlite_select->execute([$g5_occu_id]);
                    $tmp = $sqlite_select->fetchAll();
                    if(count($tmp) != 0) {
                        self::dump("            Parent already stored\n");
                        continue;
                    }
                    $parents_to_store[$parent_id] = $parent_label;
                }
//                $occus_sqlite_conn->beginTransaction();
                
//                $occus_sqlite_conn->commit();
            }
            else {
                self::dump("    No parents\n");
            }
break;            
//echo json_encode($properties, JSON_PRETTY_PRINT) . "\n"; exit;
//echo "\n<pre>"; print_r($properties); echo "</pre>\n"; exit;
        }
        
    }
    
    private static function dump(string $str): void {
        if(self::$dump) {
            echo $str;
        }
    }
    
} // end class
