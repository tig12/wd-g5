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

try{
    
    // check
    
    $usage = "USAGE: php {$argv[0]} <command>\n"
        . "<command> can be:\n"
        . "    1 - Build sqlite database with g5 data to match.\n"
        . "    2 - Retrieve data from wikidata and store them in local sqlite database.\n"
        . "    3 - List properties retrieved from wikidata\n"
        . "    4 - List occupations retrieved from wikidata\n"
        . "    5 - Check if birth times are always set to '00:00:00'\n"
        . "    6 - Check wikidata properties cardinalities\n"
        . "    7 - Build wd occupation subclass hierarchy\n"
        . "    8 - Match wikidata to g5\n"
        ;
    
    if($argc != 2) {
        die("ERROR - This script requires exacltly one parameter.\n" . $usage);
    }
    
    $possibleCommands = [1, 2, 3, 4, 5, 6, 7, 8];
    $command = $argv[1];
    if(!in_array($command, $possibleCommands)){
        die("ERROR - Invalid value for parameter: $command.\n" . $usage);
    }
    
    // run
    
    $command = 'wdg5\commands\command' . $command;
    $command::execute();    
}
catch(Exception $e){
    echo 'Exception : ' . $e->getMessage() . "\n";
    echo $e->getFile() . ' - line ' . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
