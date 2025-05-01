<?php
/** 
    Unique autoload code to include
    Contains PSR-4 autoload for namespace "g5"
    and inclusion of autoload for vendor code.
    
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
        $classname = str_replace($namespace . '\\', '', $full_classname);
        $classname = str_replace('\\', DS, $classname);
        $filename = $root_dir . DS . $classname . '.php';
        $ok = include_once($filename);
        if(!$ok){
            throw new \Exception("AUTOLOAD FAILS for class $full_classname");
        }
    }
);
