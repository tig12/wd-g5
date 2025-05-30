<?php
/******************************************************************************
    Access to Gauquelin5 database.
    
    @license    GPL - conforms to file LICENCE located in root directory of current repository.
    @history    2019-12-27 05:50:58+01:00, Thierry Graff : Creation
********************************************************************************/
namespace wdg5\app;


class DB5{
    
    private static $dblink = null;
    
    /** Connection to g5 database **/
    public static function getConnection(){
        if(is_null(self::$dblink)) {
            $host = Config::$data['db5']['postgresql']['dbhost'];
            $port = Config::$data['db5']['postgresql']['dbport'];
            $user = Config::$data['db5']['postgresql']['dbuser'];
            $password = Config::$data['db5']['postgresql']['dbpassword'];
            $dbname = Config::$data['db5']['postgresql']['dbname'];
            $schema = Config::$data['db5']['postgresql']['schema'];
            $dsn = "pgsql:host=$host;port=$port;user=$user;password=$password;dbname=$dbname";
            self::$dblink = new \PDO($dsn);
            self::$dblink->exec("SET search_path TO $schema");
//self::$dblink->setAttribute(\PDO::ATTR_AUTOCOMMIT, 0);
            self::$dblink->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
        return self::$dblink;
    }
    
} // end class
