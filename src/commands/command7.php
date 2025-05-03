<?php
/******************************************************************************
    
    @license    GPL
    @history    2025-05-02 22:53:07+02:00, Thierry Graff : Creation
********************************************************************************/

declare(strict_types=1);

namespace wdg5\commands;

use wdg5\app\Sqlite;
use wdg5\model\Wikidata;

class command7 {
    
    /** Local sqlite database, specific to wd-g5 **/
    private static \PDO $sqlite_conn;
    
    public static function execute(): void {
        
        self::$sqlite_conn = Sqlite::getConnection();
        
        foreach (self::$sqlite_conn->query('select * from wd_g5 where is_wd_stored = 1 limit 5', \PDO::FETCH_ASSOC) as $row){
            
            $g5_person = self::build_g5_person($row);
echo "\n" . substr($g5_person['birth']['date'], 0, 10) . '    ' . $g5_person['slug'] . ' - ' . implode(', ', $g5_person['occus']) . "\n";
echo "---------------------\n";
            
            $data_wd = json_decode($row['wd_data'], true);
            foreach($data_wd as $id_wd => $candidate){
                
                // try to match only humans !
                if($candidate[Wikidata::PROP_INSTANCE_OF]['values'][0]['id'] != Wikidata::ENTITY_HUMAN){
                    continue;
                }
                $wd_person = self::build_wd_person($candidate, $id_wd);
                
                // if no birth date, useless to try matching
                if(!isset($wd_person['birth']['date'])){
                    continue;
                }
                
                $match = self::match($g5_person, $wd_person);
            }
        }
    }
    
    private static function match(array &$g5_person, &$wd_person): array {
        $res = [];
        $match_names        = self::match_names($g5_person, $wd_person);
        $match_birthdate    = self::match_birthdate($g5_person, $wd_person);
echo "    $match_birthdate\n";
        $match_birthplace   = self::match_birthplace($g5_person, $wd_person);
        $match_occus        = self::match_occus($g5_person, $wd_person);
        $match_sex          = self::match_sex($g5_person, $wd_person);
        return $res;
    }
    
    /** @return A match score, between 0 (no match) and 100 (match certain) **/
    private static function match_names(array &$g5_person, &$wd_person): int {
        return 1;
    }
    
    /** @return A match score, between 0 (no match) and 100 (match certain) **/
    private static function match_birthdate(array &$g5_person, &$wd_person): int {
        $match = 0;
        $g5_value = substr($g5_person['birth']['date'], 0, 10);
        foreach($wd_person['birth']['date'] as $wd_value){
echo $wd_value . '    ' . $wd_person['id-wd'] . "";
            if($g5_value == $wd_value){
                $match = 1;
                break;
            }
            if(levenshtein($g5_value, $wd_value) == 1){
                $match = 0.5;
            }
        }
        return $match;
    }
    
    /** @return A match score, between 0 (no match) and 100 (match certain) **/
    private static function match_birthplace(array &$g5_person, &$wd_person): int {
        return 1;
    }
    
    /** @return A match score, between 0 (no match) and 100 (match certain) **/
    private static function match_occus(array &$g5_person, &$wd_person): int {
        return 1;
    }
    
    /** @return A match score, between 0 (no match) and 100 (match certain) **/
    private static function match_sex(array &$g5_person, &$wd_person): int {
        return 1;
    }
    
    private static function build_g5_person(array &$row): array {
        $res = [];
        $res['slug']    = $row['g5_slug'];
        $res['name']    = json_decode($row['g5_name'], true);
        $res['birth']   = json_decode($row['g5_birth'], true);
        $res['occus']   = json_decode($row['g5_occus'], true);
        $res['sex']     = $row['g5_sex'];
        return $res;
    }
    
    /** 
        Convert wd person structure to g5 person structure.
        One notable difference: each field in g5 is represented by a string,
        and corresponds to an array of values in wd.
    **/
    private static function build_wd_person(array &$candidate, $id_wd): array {
        $res = [];
        $res['id-wd'] = $id_wd;
        $res['name'] = [];
        $res['birth'] = [];
        $res['death'] = [];
        $res['occus'] = [];
        //
        // name
        //
        $res['name']['alter'] = []; // g5 field alter used for PROP_NAME, PROP_SHORT_NAME, PROP_NICKNAME
        if(isset($candidate[Wikidata::PROP_NAME])){
            foreach($candidate[Wikidata::PROP_NAME]['values'] as $value){
                $res['name']['alter'][] = $value['label'];
            }
        }
        if(isset($candidate[Wikidata::PROP_BIRTH_NAME])){
            $res['name']['fame']['official'] = [];
            foreach($candidate[Wikidata::PROP_BIRTH_NAME]['values'] as $value){
                $res['name']['official']['full'][] = $value['label'];
            }
        }
        if(isset($candidate[Wikidata::PROP_FAMILY_NAME])){
            $res['name']['fame']['family'] = [];
            foreach($candidate[Wikidata::PROP_FAMILY_NAME]['values'] as $value){
                $res['name']['family'][] = $value['label'];
            }
        }
        if(isset($candidate[Wikidata::PROP_GIVEN_NAME])){
            $res['name']['fame']['given'] = [];
            foreach($candidate[Wikidata::PROP_GIVEN_NAME]['values'] as $value){
                $res['name']['given'][] = $value['label'];
            }
        }
        if(isset($candidate[Wikidata::PROP_SHORT_NAME])){
            foreach($candidate[Wikidata::PROP_SHORT_NAME]['values'] as $value){
                $res['name']['alter'][] = $value['label'];
            }
        }
        if(isset($candidate[Wikidata::PROP_NICKNAME])){
            foreach($candidate[Wikidata::PROP_NICKNAME]['values'] as $value){
                $res['name']['alter'][] = $value['label'];
            }
        }
        if(isset($candidate[Wikidata::PROP_OFFICIAL_NAME])){
            $res['name']['official']['full'] = [];
            foreach($candidate[Wikidata::PROP_OFFICIAL_NAME]['values'] as $value){
                $res['name']['official']['full'][] = $value['label'];
            }
        }
        if(isset($candidate[Wikidata::PROP_MARRIED_NAME])){
            $res['name']['spouse'] = [];
            foreach($candidate[Wikidata::PROP_MARRIED_NAME]['values'] as $value){
                $res['name']['spouse'][] = $value['label'];
            }
        }
        //
        // Birth
        //
        if(isset($candidate[Wikidata::PROP_DATE_OF_BIRTH])){
            $res['birth']['date'] = [];
            foreach($candidate[Wikidata::PROP_DATE_OF_BIRTH]['values'] as $value){
                $res['birth']['date'][] = substr($value['label'], 0, 10);
            }
        }
        if(isset($candidate[Wikidata::PROP_PLACE_OF_BIRTH])){
            $res['birth']['place'] = [];
            foreach($candidate[Wikidata::PROP_PLACE_OF_BIRTH]['values'] as $value){
                $newPlace = [];
                $newPlace['name'] = $value['label'];
                $newPlace['wd-id'] = $value['id'];      // wd-id: field not existing in g5 model
                $res['birth']['place'][] = $newPlace;
            }
        }
        //
        // Death
        //
        if(isset($candidate[Wikidata::PROP_DATE_OF_DEATH])){
            $res['death']['date'] = [];
            foreach($candidate[Wikidata::PROP_DATE_OF_DEATH]['values'] as $value){
                $res['death']['date'][] = substr($value['label'], 0, 10);
            }
        }
        if(isset($candidate[Wikidata::PROP_PLACE_OF_DEATH])){
            $res['death']['place'] = [];
            foreach($candidate[Wikidata::PROP_PLACE_OF_DEATH]['values'] as $value){
                $newPlace = [];
                $newPlace['name'] = $value['label'];
                $newPlace['wd-id'] = $value['id'];      // wd-id: field not existing in g5 model
                $res['death']['place'][] = $newPlace;
            }
        }
        //
        // Other fields
        //
        if(isset($candidate[Wikidata::PROP_OCCUPATION])){
            $res['occus'] = [];
            foreach($candidate[Wikidata::PROP_OCCUPATION]['values'] as $value){
                // differs from g5, occus contains an array of slugs
                $res['occus'][] = [
                    'wd-id' => $value['id'],
                    'label' => $value['label'],
                ];
            }
        }
        if(isset($candidate[Wikidata::PROP_SEX_OR_GENDER])){
            $res['sex'] = [];
            foreach($candidate[Wikidata::PROP_SEX_OR_GENDER]['values'] as $value){
                $res['sex'][] = $value['id'];
            }
        }
        return $res;
    }
    
    /* 
Array
(
    [slug] => smith-adrian-1936-10-05
    [name] => Array
        (
            [fame] => Array
                (
                    [full] => 
                    [given] => 
                    [family] => 
                )

            [nobl] => 
            [alter] => Array
                (
                )

            [given] => Adrian
            [family] => Smith
            [spouse] => Array
                (
                )

            [official] => Array
                (
                    [given] => 
                    [family] => 
                )

        )

    [birth] => Array
        (
            [lmt] => 
            [tzo] => -06:00
            [date] => 1936-10-05 05:30
            [note] => 
            [place] => Array
                (
                    [c1] => 
                    [c2] => KY
                    [c3] => 
                    [cy] => US
                    [lg] => -88.53333
                    [lat] => 36.7
                    [name] => Farmington
                    [geoid] => 
                )

            [notime] => 
            [date-ut] => 1936-10-05 11:30
        )

    [occus] => Array
        (
            [0] => athletics-competitor
        )

    [sex] => M
)    */
    
} // end class
