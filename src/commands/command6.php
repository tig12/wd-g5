<?php
/******************************************************************************
    
    @license    GPL
    @history    2025-05-02 22:53:07+02:00, Thierry Graff : Creation
********************************************************************************/

declare(strict_types=1);

namespace wdg5\commands;

use wdg5\app\Sqlite;

class command6 {
    
    public const array WD_USEFUL_FIELDS = [
        'P2561' => 'name',
        'P1477' => 'birth name',
        'P734'  => 'family name',
        'P735'  => 'given name',
        'P1813' => 'short name',
        'P1449' => 'nickname',
        'P1448' => 'official name',
        //
        'P569'  => 'date of birth',
        'P19'   => 'place of birth',
        //
        'P570'  => 'date of death',
        'P20'   => 'place of death',
        //
        'P106'  => 'occupation',
        //
        'P21'   => 'sex or gender',
    ];
    
    /** Local sqlite database, specific to wd-g5 **/
    private static \PDO $sqlite_conn;
    
    public static function execute(): void {
        
        self::$sqlite_conn = Sqlite::getConnection();
        
        foreach (self::$sqlite_conn->query('select * from wd_g5 where is_wd_stored = 1 limit 1', \PDO::FETCH_ASSOC) as $row){
            
            $g5_person = self::build_g5_person($row);
            
            $data_wd = json_decode($row['wd_data'], true);
            foreach($data_wd as $id_wd => $candidate){
                $wd_person = self::build_wd_person();
            }
        }
    }
    
    private static function build_g5_person(array $row): array {
        $res = [];
        $res['slug'] = $row['g5_slug'];
        $res['name'] = json_decode($row['g5_name']);
        $res['birth'] = json_decode($row['g5_birth']);
        $res['occus'] = json_decode($row['g5_occus']);
        $res['sex'] = $row['g5_sex'];
echo "\n<pre>"; print_r($res); echo "</pre>\n"; exit;
        return $res;
    }
    
    
} // end class
