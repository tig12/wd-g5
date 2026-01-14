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

use wdg5\app\Config;
use wdg5\app\Sqlite;
use Wikidata\Wikidata;
use tiglib\misc\dosleep;

class command02 {
    
    /** Local sqlite database, specific to wd-g5 **/
    private static \PDO $sqlite_conn;
    
    public static function execute(): void {
        
        self::$sqlite_conn = Sqlite::getConnection(Config::$data['sqlite']['wd-g5']);
        
        $wikidata = new Wikidata();
        
        $sql_update = self::$sqlite_conn->prepare('
            update wd_g5 set
                wd_data=?,
                wd_data_count=?,
                is_wd_stored=1
            where g5_slug=?
        ');
        
        foreach (self::$sqlite_conn->query("select * from wd_g5 where is_wd_stored = 0 and g5_slug not like 'gauquelin-a%'", \PDO::FETCH_ASSOC) as $row){
            
            $slug = $row['g5_slug'];
            echo "Processing $slug {$row['g5_occus']}";
            
            $search_term = self::computeSearchTerm(json_decode($row['g5_name'], true));
            $wd_search_results = $wikidata->search(query:$search_term, limit:5);            // Here call wkidata
            echo ' => ' . count($wd_search_results) . " candidates\n";
            
            $wd_get_results = [];
            foreach($wd_search_results as $wd_id => $candidate){
                echo "    Get $wd_id {$candidate->label} ({$candidate->description})\n";
                $entity = $wikidata->get($wd_id);
                $wd_get_results[$wd_id] = $entity->properties->toArray();
            }
            $sql_update->execute([
                json_encode($wd_get_results),
                count($wd_get_results),
                $slug,
            ]);
            
            dosleep::execute(0.5);
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
