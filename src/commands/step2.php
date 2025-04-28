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
use Wikidata\Wikidata;

class step2 {
    
    private static string $sqlite_path;
    
    public static function execute(): void {
        
        die("step2\n");
        
        $wikidata = new Wikidata();
        $results = $wikidata->search($request['given-name'] . ' ' . $request['last-name']);
echo "\n"; print_r($results); echo "\n";
    }
    
    
    private function initializeSqlite(): void {
        self::$sqlite_path = Config::$data['sqlite'];
        
        if(!is_file(self::$sqlite_path)) {
            throw new \Exception("Local sqlite database doesn't exist - execute step1 first");
        }
    }
    
    
} // end class
