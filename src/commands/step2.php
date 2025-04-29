<?php
/******************************************************************************
    Step 2 of matching opengauquelin database with wikidata:
    Requests wikidata and stores the results in the local sqlite database.
    
    Uses https://github.com/freearhey/wikidata
    
    This command can be used several times and will not perform twice the same request.
    
    @license    GPL
    @history    2025-04-27 09:09:47+02:00, Thierry Graff : Creation
********************************************************************************/

declare(strict_types=1);

namespace wdg5\commands;

use wdg5\app\Sqlite;
use Wikidata\Wikidata;

class step2 {
    
    /** Local sqlite database, specific to wd-g5 **/
    private static \PDO $sqlite_conn;
    
    public static function execute(): void {
        
        self::$sqlite_conn = Sqlite::getConnection();
        
        $wikidata = new Wikidata();
        
        $sql_update = self::$sqlite_conn->prepare('update wd_g5 set wd_data=?, is_wd_stored=1 where g5_slug=?');
        
        foreach (self::$sqlite_conn->query('select * from wd_g5 where is_wd_stored = 0', \PDO::FETCH_ASSOC) as $row){
            $slug = $row['g5_slug'];
            echo "Processing $slug\n";
            $search_term = self::computeSearchTerm(json_decode($row['g5_name'], true));
// echo "\n"; print_r(json_decode($row['g5_name'], true)); echo "\n";
// echo "search_term = $search_term\n";
            $wd_search_results = $wikidata->search($search_term);
            $wd_get_results = [];
            foreach($wd_search_results as $id => $candidate){
                echo "    Get $id {$candidate->label} ({$candidate->description})\n";
                $entity = $wikidata->get($id);
echo "\n"; print_r($entity->properties->toArray()); echo "\n";
                $wd_get_results[] = $entity->properties->toArray();
break;
            }
            $sql_update->execute([
                json_encode($wd_get_results),
                $slug,
            ]);

break;
        }
        
    }
    
    
    private static function computeSearchTerm($nameArray): string {
        $given = '';
        if(isset($nameArray['given'])){
            $given = $nameArray['given'];
        }
        else {
            if(isset($nameArray['fame']['given'])){
                $given = $nameArray['fame']['given'];
            }
            else {
                if(isset($nameArray['official']['given'])){
                    $given = $nameArray['official']['given'];
                }
            }
        }
        $family = '';
        if(isset($nameArray['family'])){
            $family = $nameArray['family'];
        }
        else {
            if(isset($nameArray['fame']['family'])){
                $family = $nameArray['fame']['family'];
            }
            else {
                if(isset($nameArray['official']['family'])){
                    $family = $nameArray['official']['family'];
                }
            }
        }
        return $given . ' ' . $family;
    }
    
    
} // end class
