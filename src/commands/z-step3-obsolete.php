<?php
/******************************************************************************
    @license    GPL
    @history    2025-04, Thierry Graff : Creation
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
        
        foreach (self::$sqlite_conn->query('select * from wd_g5 where is_wd_stored = 1 limit 2', \PDO::FETCH_ASSOC) as $i => $row){
            $slug = $row['g5_slug'];
            echo "\n=========== Processing $slug {$row['g5_occus']} ===========\n";
            $data_wd = json_decode($row['wd_data'], true);
            foreach($data_wd as $id_wd => $candidate){
//if($id_wd != 'Q212070') continue;
//echo "\n<pre>"; print_r($id_wd); echo "</pre>\n"; exit;
                $instance_of_wd = self::compute_id_label_property($candidate['P31']);
                if(!isset($instance_of_wd['Q5'])){
                    continue;
                }
                if(isset($candidate['P1559'])){
                    $name_wd        = self::compute_label_property($candidate['P1559']);
                }
                if(isset($candidate['P1477'])){
                    $birth_name_wd  = self::compute_label_property($candidate['P1477']);
                }
                if(isset($candidate['P734'])){
                    $family_name_wd = self::compute_label_property($candidate['P734']);
                }
                if(isset($candidate['P569'])){
                    $birth_date_wd  = self::compute_label_property($candidate['P569']);
                }
                if(isset($candidate['P19'])){
                    $birth_place_wd = self::compute_id_label_property($candidate['P19']);
                }
                if(isset($candidate['P27'])){
                    $country_wd     = self::compute_id_label_property($candidate['P27']);
                }
                if(isset($candidate['P21'])){
                    $gender_wd      = self::compute_id_label_property($candidate['P21']);
                }
                if(isset($candidate['P106'])){
                    $occus_wd       = self::compute_id_label_property($candidate['P106']);
                }
                
echo "=========== $id_wd ===========\n";
echo $name_wd !== null ? "name_wd = " . self::dump_label_property($name_wd) : "MISSING\n";
echo $family_name_wd !== null ? "family_name_wd =\n" . self::dump_label_property($family_name_wd, '    ') : "MISSING\n";
echo $birth_name_wd !== null ? "birth_name_wd =\n" . self::dump_label_property($birth_name_wd, '    ') : "MISSING\n";
echo $birth_date_wd !== null ? "birth_date_wd =\n" . self::dump_label_property($birth_date_wd, '    ') : "MISSING\n";
echo $birth_place_wd !== null ? "birth_place_wd =\n" . self::dump_id_label_property($birth_place_wd, '    ') : "MISSING\n";
echo $country_wd !== null ? "country_wd =\n" . self::dump_id_label_property($country_wd, '    ') : "MISSING\n";
echo $gender_wd !== null ? "gender_wd =\n" . self::dump_id_label_property($gender_wd, '    ') : "MISSING\n";
echo $occus_wd !== null ? "occus_wd =\n" . self::dump_id_label_property($occus_wd, '    ') : "MISSING\n";
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
