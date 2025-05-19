<?php
/******************************************************************************
    Fills column 'parents' of wd-occus database using "subclass of" (P279),
    and insert new occupations when parents occupations don't exist.
    
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
use tiglib\strings\slugify;

class command8 {
    
    /** Connection to wd-occus local sqlite database **/
    private static \PDO $occus_sqlite_conn;
    
    private static bool $dump = true;
    
    public static function execute(): void {

        self::$wikidata = new Wikidata();
        
        self::$occus_sqlite_conn = Sqlite::getConnection(Config::$data['sqlite']['wd-occus']);
        self::$occus_sqlite_conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Loop on the rows for which parents haven't been computed
        foreach(self::$occus_sqlite_conn->query('select * from wd_occus where are_parents_computed=0', \PDO::FETCH_ASSOC) as $row){
            self::computeParents($row);
        }
        
    }
    
    private static function computeParents(array $row): void {
        
        
///////// current dev stopped here /////////////
        
        
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
            $occu = $wikidata->get($g5_occu_id); //             HERE, call to wikidata
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
                try{
                    $occus_sqlite_conn->beginTransaction();
                    foreach($parents_to_store as $id => $label){
                        $slug = slugify::compute($label);
echo "$id $label $slug\n";
                    }
                    $occus_sqlite_conn->commit();
                }
                catch(\Exception $e){
                    $occus_sqlite_conn->rollback();
                }
            }
            else {
                self::dump("    No parents (no P279)\n");
            }
break;            
//echo json_encode($properties, JSON_PRETTY_PRINT) . "\n"; exit;
//echo "\n<pre>"; print_r($properties); echo "</pre>\n"; exit;
            dosleep::execute(0.5);
        }
        
    }
    
    private static function dump(string $str): void {
        if(self::$dump) {
            echo $str;
        }
    }
    
} // end class
