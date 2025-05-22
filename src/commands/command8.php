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
    
    private static Wikidata $wikidata;
    
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
        
        self::dump("=== Processing {$row['wd_id']} : {$row['wd_label']} ===\n");
        
        $sqlite_select_occu = self::$occus_sqlite_conn->prepare('
            select * from wd_occus where wd_id = ?
        ');
        $sqlite_insert = self::$occus_sqlite_conn->prepare('
            insert into wd_occus(
                wd_id,
                wd_label,
                slug,
            ) values(?, ?, ?)
        ');
        $sqlite_update = self::$occus_sqlite_conn->prepare('
            update wd_occus set
                are_parents_computed = 1,
                wd_parents = ?
            where wd_id = ?
        ');
        
        // Get occupation from wikidata
        $wd_occu = self::$wikidata->get($row['wd_id']); //             HERE, call to wikidata
        $properties = $wd_occu->properties->toArray();
        
            if(!isset($properties[Property::SUBCLASS_OF])){
                self::dump("    - No parents (no P279)\n");
                return;
            }
            self::dump("    Parents:\n");
            $parents_to_add = [];        // Parents that will be stored in the wd_parents of current row
            $parents_to_store = [];     // Prents not already existing in the database
            $parents_to_process = [];   // parents already stored, but wd_parents not computed yet
            foreach($properties[Property::SUBCLASS_OF]->values as $wd_parent){
                $parent_id = $wd_parent->id;
                $parent_label = $wd_parent->label;
                $parent_slug = slugify::compute($parent_label);
                $current_parent = [
                    'wd_id'     => $parent_id,
                    'wd_=label' => $parent_label,
                    'slug'      => $parent_slug,
                ];
                $parents_to_add[] = $current_parent;
                self::dump("        $parent_id $parent_label");
                // check if parent is already stored
                $sqlite_select_occu->execute([$row['wd_id']]);
                $parent = $sqlite_select_occu->fetch(\PDO::FETCH_ASSOC);
                if($parent !== false) {
                    self::dump(" - Parent already stored");
                    if($parent['are_parents_computed'] == 0){
                        self::dump(" - But not computed yet");
                        $parents_to_process[] = $current_parent;
                    }
                    else {
                        self::dump("\n");
                        continue;
                    }
                    self::dump("\n");
                }
                else {
                    $parents_to_store[] = $current_parent;
                    $parents_to_process[] = $current_parent;
                    self::dump(" - New parent, not already stored\n");
                }
            }
            
            // $parents_to_add
            foreach($parents_to_process as $current_parent){
                dosleep::execute(0.5);
                self::dump("    Processing {$current_parent['label']} {$current_parent['slug']}\n");
            }
            
            // $parents_to_store
            try{
                self::$occus_sqlite_conn->beginTransaction();
                foreach($parents_to_store as $current_parent){
                    self::dump("    Storing {$current_parent['id']} {$current_parent['label']} {$current_parent['slug']}\n");
                }
                self::$occus_sqlite_conn->commit();
            }
            catch(\Exception $e){
                self::$occus_sqlite_conn->rollback();
                throw($e);
                exit;
            }
            
            // $parents_to_process
            foreach($parents_to_process as $current_parent){
                dosleep::execute(0.5);
                self::dump("    Processing {$current_parent['label']} {$current_parent['slug']}\n");
            }
exit;
    }
    
    private static function dump(string $str): void {
        if(self::$dump) {
            echo $str;
        }
    }
    
} // end class
