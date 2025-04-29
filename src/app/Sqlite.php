<?php
/******************************************************************************
    Utilities related to the local sqlite database.
    
    @license    GPL
    @history    2025-04-28 00:04:22+02:00, Thierry Graff : Creation
********************************************************************************/

declare(strict_types=1);

namespace wdg5\app;

use wdg5\app\Config;

class Sqlite {
    
    /** 
        @throws \PDOException
    **/
    public static function getConnection(): ?\PDO {
        $path = Config::$data['sqlite-path'];
        $dir = dirname($path);
        if(!is_dir($dir)) {
            echo "Creating directory $dir\n";
            mkdir($dir, 0777, true);
        }
        $dsn = 'sqlite:' . $path;
        return new \PDO($dsn);
    }
    
} // end class
