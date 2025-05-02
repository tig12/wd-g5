<?php
/******************************************************************************
    Step 1 of matching opengauquelin database with wikidata:
    Builds a local sqlite database containing opengauquelin data to match.
    
    @license    GPL
    @history    2025-04-27 09:05:34+02:00, Thierry Graff : Creation
********************************************************************************/

declare(strict_types=1);

namespace wdg5\commands;

use wdg5\app\Config;
use wdg5\app\Sqlite;
use wdg5\app\DB5;


class command1 {
    
    /** Local sqlite database, specific to wd-g5 **/
    private static \PDO $sqlite_conn;
    
    /** g5 postgresql database **/
    private static \PDO $db5_conn;
    
    public static function execute(): void {
        
        $a = readline('This will delete existing sqlite database. Are you sure ? (y/N) '); 
        if(strtolower(trim($a)) != 'y'){
            echo "OK, prgram ends, nothing was modified.\n";
            return;
        }
        
        self::initializeSqlite();
        self::$db5_conn = DB5::getConnection();
        
        $stmt = self::$db5_conn->prepare('select slug, name, sex, birth, occus from person');
        $stmt->execute();
        $g5_res = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $sqlite_insert = self::$sqlite_conn->prepare('
            insert into wd_g5(
                g5_slug,
                g5_name,
                g5_sex,
                g5_birth,
                g5_occus
            ) values(?, ?, ?, ?, ?)
        ');
        
        echo "Fill sqlite with g5 data...\n";
        $t1 = microtime(true);
        foreach($g5_res as $row) {
            $sqlite_insert->execute([
                $row['slug'],
                $row['name'],
                $row['sex'],
                $row['birth'],
                $row['occus'],
            ]);
        }
        $t2 = microtime(true);
        $dt = round($t2 - $t1, 5);
        echo "Done in $dt s\n";
        
    }
    
    private static function initializeSqlite(): void {
        
        if(!isset(Config::$data['sqlite-path'])){
            throw new \Exception("MISSING KEY 'sqlite-path' IN CONFIG FILE config.yml.\n");
        }
        
        $sqlite_path = Config::$data['sqlite-path'];
        
        $dir = dirname($sqlite_path);
        if(!is_dir($dir)) {
            echo "Created directory $dir\n";
            mkdir($dir, 0777, true);
        }
        
        if(is_file($sqlite_path)){
            unlink($sqlite_path);
        }
        
        self::$sqlite_conn = Sqlite::getConnection();
        $sql = file_get_contents(dirname(dirname(__FILE__)) . DS . 'model' . DS . 'database.sql');
        self::$sqlite_conn->exec($sql);
        echo "Initialized local sqlite database $sqlite_path.\n";
    }
    
} // end class
