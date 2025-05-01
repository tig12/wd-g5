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

use wdg5\app\Sqlite;
use Wikidata\Wikidata;
use tiglib\misc\dosleep;

class step3 {
    
    /** Local sqlite database, specific to wd-g5 **/
    private static \PDO $sqlite_conn;
    
    public static function execute(): void {
        
        self::$sqlite_conn = Sqlite::getConnection();
        
        foreach (self::$sqlite_conn->query('select * from wd_g5 limit 1', \PDO::FETCH_ASSOC) as $i => $row){
            $slug = $row['g5_slug'];
            echo "Processing $slug {$row['g5_occus']}\n";
            $data_wd = json_decode($row['wd_data'], true);
            foreach($data_wd as $id_wd => $candidate){
                $instance_of_wd = self::compute_id_label_property($candidate['P31']);
                if(!isset($instance_of_wd['Q5'])){
                    continue;
                }
                $name_wd        = self::compute_label_property($candidate['P1559']);
                $family_name_wd = self::compute_label_property($candidate['P734']);
                $birth_name_wd  = self::compute_label_property($candidate['P1477']);
                $birth_date_wd  = self::compute_label_property($candidate['P569']);
                $birth_place_wd = self::compute_id_label_property($candidate['P19']);
                $country_wd     = self::compute_id_label_property($candidate['P27']);
                $gender_wd      = self::compute_id_label_property($candidate['P21']);
                $occus_wd       = self::compute_id_label_property($candidate['P106']);
                
echo "=========== id_wd = $id_wd\n";
echo "name_wd = \n" . self::dump_label_property($name_wd, '    ');
echo "family_name_wd = \n" . self::dump_label_property($family_name_wd, '    ');
echo "birth_name_wd = \n" . self::dump_label_property($birth_name_wd, '    ');
echo "birth_date_wd = \n" . self::dump_label_property($birth_date_wd, '    ');
echo "birth_place_wd = \n" . self::dump_id_label_property($birth_place_wd, '    ');
echo "country_wd = \n" . self::dump_id_label_property($country_wd, '    ');
echo "gender_wd = \n" . self::dump_id_label_property($gender_wd, '    ');
echo "occus = \n" . self::dump_id_label_property($occus_wd, '    ');
//break;
            }

        }
        
    }
    
    // Properties with id = label
    // array of strings
    
    private static function compute_label_property(array $property): array {
        $res = [];
        for($i=0; $i < count($property['values']); $i++){
            $res [] = $property['values'][$i]['label'];
        }
        return $res;
    }
    
    private static function dump_label_property(array $property, string $linePrefix = ''): string {
        $res = '';
        foreach($property as $item){
            $res .= $linePrefix . $item . "\n";
        }
        return $res;
    }
    
    // Properties with id, label, qualifier
    // array of arrays wd_id => wd_label
    
    private static function compute_id_label_property(array $property): array {
        $res = [];
        foreach($property['values'] as $item){
            $res[$item['id']] = $item['label'];
        }
        return $res;
    }
    
    private static function dump_id_label_property(array $property, string $linePrefix = ''): string {
        $res = '';
        foreach($property as $id => $label){
            $res .= "$linePrefix $id $label\n";
        }
        return $res;
    }
    
    
} // end class
