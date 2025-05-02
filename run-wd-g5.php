<?php
declare(strict_types=1);

/********************************************************************************
    CLI (command line interface) of g5-wd program
    
    Unique entry point to use the program
    
    usage : php run-g5-wd.php
    
    and follow error message
    
    @license    GPL
    @copyright  Thierry Graff
    @history    2025-04-14 18:30:24+02:00, Thierry Graff : creation
********************************************************************************/

define('DS', DIRECTORY_SEPARATOR);

require_once __DIR__ . DS . implode(DS, ['src', 'app' , 'init.php']);

use wdg5\commands\step1;
use wdg5\commands\step2;

try{
    
    $usage = "USAGE: php {$argv[0]} <step>\n"
        . "<step> can be:\n"
        . "    1 - Build sqlite database with g5 data to match.\n"
        . "    2 - Retrieve data from wikidata and store them in local sqlite database.\n"
        . "    3 - List properties retrieved from wikidata\n"
        . "    4 - List occupations retrieved from wikidata\n"
        ;
    
    if($argc != 2) {
        die("ERROR - This script requires exacltly one parameter.\n" . $usage);
    }
    
    $possibleSteps = [1, 2, 3, 4];
    $step = $argv[1];
    if(!in_array($step, $possibleSteps)){
        die("ERROR - Invalid value for parameter: $step.\n" . $usage);
    }
    
    //
    // run
    //
    $command = 'wdg5\commands\step' . $step;
    $command::execute();    
    
    /*
    $request = [
        'last-name'     => 'Boussinesq',
        'given-name'    => 'Valentin Joseph',
        'birth-date'    => '1842-03-13',
        'birth-place'   => 'Saint-André-de-Sangonis',
        'birth-country' => 'FR',
    ];
    $request = [
        'last-name'     => 'Becquerel',
        'given-name'    => 'Antoine',
        'birth-date'    => '1852-12-15',
        'birth-place'   => 'paris',
        'birth-country' => 'FR',
    ];
     */
    
}
catch(Exception $e){
    echo 'Exception : ' . $e->getMessage() . "\n";
    echo $e->getFile() . ' - line ' . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
