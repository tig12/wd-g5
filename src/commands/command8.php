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
    
    private static \PDOStatement $sql_select;
    private static \PDOStatement $sql_insert;
    private static \PDOStatement $sql_update;
    
    
    public static function execute(): void {
        
        self::init();
        self::computeAllRows();
    }
    
    private static function init(): void {
    
        self::$wikidata = new Wikidata();
        
        self::$occus_sqlite_conn = Sqlite::getConnection(Config::$data['sqlite']['wd-occus']);
        self::$occus_sqlite_conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        self::$sql_select = self::$occus_sqlite_conn->prepare('
            select * from wd_occus where wd_id = ?
        ');
        self::$sql_insert = self::$occus_sqlite_conn->prepare('
            insert into wd_occus(
                wd_id,
                wd_label,
                slug
            ) values(?, ?, ?)
        ');
        self::$sql_update = self::$occus_sqlite_conn->prepare('
            update wd_occus set
                are_parents_computed = 1,
                wd_parents = ?
            where wd_id = ?
        ');
        
    }
        
    private static function computeAllRows(): void {
        try{
            $tmp = self::$occus_sqlite_conn->query('select count(*) from wd_occus where are_parents_computed=0', \PDO::FETCH_ASSOC);
            $N = $tmp->fetch(\PDO::FETCH_ASSOC)['count(*)'];
            self::dump("**************************************************\n");
            self::dump("N = $N\n");
            self::dump("**************************************************\n");
            if($N > 0) {
                $row = self::$occus_sqlite_conn->query('select * from wd_occus where are_parents_computed=0 limit 1', \PDO::FETCH_ASSOC);
                self::computeOneRow($row->fetch(\PDO::FETCH_ASSOC));
                dosleep::execute(0.5);
                self::computeAllRows();         // HERE recursive
            }
        }
        catch(\Exception){
            // do nothing, just to bypass xdebug limitation of 512 recursions
        }
    }
    
    /** 
        Computes the parents of a single row.
    **/
    private static function computeOneRow(array $row): void {
        
        self::dump("=== Processing {$row['wd_id']} : {$row['wd_label']} ===\n");
        
        // Get occupation from wikidata
        $wd_occu = self::$wikidata->get($row['wd_id']); //             HERE, call to wikidata
        $properties = $wd_occu->properties->toArray();
        
        if(!isset($properties[Property::SUBCLASS_OF])){
            self::dump("    - No parents (no P279)\n");
            self::$occus_sqlite_conn->query("update wd_occus set are_parents_computed = 1 where wd_id = '{$row['wd_id']}'");
            return;
        }
        self::dump("    Parents:\n");
        $parents_to_complete = [];  // Parents that will be stored in column wd_parents of current row
        $new_parents = [];          // Parents not already existing in the database
        foreach($properties[Property::SUBCLASS_OF]->values as $wd_parent){
            $parent_id = $wd_parent->id;
            $parent_label = $wd_parent->label;
            $parent_slug = slugify::compute($parent_label);
            $current_parent = [
                'wd_id'     => $parent_id,
                'wd_label' => $parent_label,
                'slug'      => $parent_slug,
            ];
            $parents_to_complete[] = $current_parent;
            self::dump("        $parent_id $parent_label");
            // check if parent is already stored
            self::$sql_select->execute([$row['wd_id']]);
            $parent = self::$sql_select->fetch(\PDO::FETCH_ASSOC);
            if($parent !== false) {
                self::dump(" - Parent already stored\n");
            }
            else {
                $new_parents[] = $current_parent;
                self::dump(" - New parent, not already stored\n");
            }
        }
        
        // $parents_to_complete
        // in current row, add a json-encoded array of parents' slugs
        $slugs = [];
        foreach($parents_to_complete as $parent){
            $slugs[] = $parent['slug'];
        }
        self::dump("    Add parents to current row : " . json_encode($slugs) . "\n");
        self::$sql_update->execute([
            json_encode($slugs),
            $row['wd_id'],
        ]);
        
        // $new_parents
        foreach($new_parents as $parent){
            self::dump("    Storing new occupation {$parent['wd_id']} {$parent['slug']}\n");
            self::$sql_insert->execute([
                $parent['wd_id'],
                $parent['wd_label'],
                $parent['slug'],
            ]);
        }
    }
    
    private static function dump(string $str): void {
        if(self::$dump) {
            echo $str;
        }
    }
    
} // end class
