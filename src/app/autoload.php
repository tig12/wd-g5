<?php
/** 
    Unique autoload code to include
    Contains PSR-4 autoload for namespace "g5"
    and inclusion of autoload for vendor code.
    
    + specific autoload to associate class "command01" to a php file "src/commands/01xxx.php" (xxx being any string).
    Because files in commands are not named "command01.php" etc. but have more understandable names.
    
    @history    2025-04-14 18:35:20+02:00, Thierry Graff : Creation (adapt from g5 code) 
**/

// autoloads for vendor code
$rootdir = dirname(dirname(__DIR__));
require_once implode(DS, [$rootdir, 'vendor', 'autoload.php']);
require_once implode(DS, [$rootdir, 'vendor', 'tig12', 'tiglib', 'autoload.php']);

/** 
    Autoload for wdg5 namespace
**/
spl_autoload_register(
    function ($full_classname){
        $namespace = 'wdg5';
        if(strpos($full_classname, $namespace) !== 0){
            return; // not managed by this autoload
        }
        $root_dir = dirname(__DIR__); // root dir for this namespace
        //
        // classical psr4 autoload
        //
        $classname = str_replace($namespace . '\\', '', $full_classname);
        $classname = str_replace('\\', DS, $classname);
        $filename = $root_dir . DS . $classname . '.php';
        $ok = @include_once($filename);
        if(!$ok){
            //
            // specific autoload for php files in src/commands
            //
            $number_wanted = substr($classname, -2);
            $candidates = glob($root_dir . DS . 'commands' . DS . '*.php');
            foreach($candidates as $candidate){
                $number_found = substr(basename($candidate), 0, 2);
                if($number_found == $number_wanted) {
                    $ok2 = @include_once($candidate);
                    break;
                }
            }
            if(!$ok2) {
                throw new \Exception("AUTOLOAD FAILS for class $full_classname");
            }
        }
    }
);
