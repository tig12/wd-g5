<?php
/******************************************************************************
    
    Match wikidata to g5

    @license    GPL
    @history    2025-05-02 22:53:07+02:00, Thierry Graff : Creation
********************************************************************************/

declare(strict_types=1);

namespace wdg5\commands;

use wdg5\app\Config;
use wdg5\app\Sqlite;
use wdg5\model\wikidata\Property;
use wdg5\model\wikidata\Entity;

class command9 {
    
    /** Local sqlite database, specific to wd-g5 **/
    private static \PDO $sqlite_conn;
    
    public static function execute(): void {
        
        self::$sqlite_conn = Sqlite::getConnection(Config::$data['sqlite']['wd-g5']);
//        $query = 'select * from wd_g5 where is_wd_stored = 1';
//        $query = 'select * from wd_g5 where is_wd_stored = 1 limit 5';
        $query = "select * from wd_g5 where g5_slug='sommer-raymond-1906-08-31'";
        foreach (self::$sqlite_conn->query($query, \PDO::FETCH_ASSOC) as $row){
            
            $g5_person = self::build_g5_person($row);
echo "\n" . substr($g5_person['birth']['date'], 0, 10) . '    ' . $g5_person['slug'] . ' - ' . implode(', ', $g5_person['occus']) . "\n";
echo "---------------------\n";
            
            $data_wd = json_decode($row['wd_data'], true);
            foreach($data_wd as $id_wd => $candidate){
                
                // try to match only humans !
                if($candidate[Property::INSTANCE_OF]['values'][0]['id'] != Entity::HUMAN){
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
    
    // ============================= Match wd and g5 persons =============================
    
    private static function match(array &$g5_person, &$wd_person): bool {
        $res = false;
        $match_birthdate    = self::match_birthdate($g5_person, $wd_person);
        if($match_birthdate == 0) {
            return false;
        }
        $match_names        = self::match_names($g5_person, $wd_person);
        $match_birthplace   = self::match_birthplace($g5_person, $wd_person);
        $match_occus        = self::match_occus($g5_person, $wd_person);
        $match_sex          = self::match_sex($g5_person, $wd_person);
        return $res;
    }
    
    /** @return A match score, between 0 (no match) and 1 (match certain) **/
    private static function match_birthdate(array &$g5_person, &$wd_person): int {
        $match = 0;
        $g5_value = substr($g5_person['birth']['date'], 0, 10);
        foreach($wd_person['birth']['date'] as $wd_value){
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
    
    /**
        Try t omatch g5 and wd names.
        The matching takes g5 informations as reference
        @return A match score, between 0 (no match) and 1 (match certain)
    **/
    private static function match_names(array &$g5_person, &$wd_person): int {
        $match = 0; // between 0 and 1
        // nb of matches (can be higher than 1)
        $match_family = 0;
        $match_given = 0;
        $match_alter = 0;
        $match_spouse = 0;
        //
        // family
        //
        $g5_families = [];
        if(isset($g5_person['name']['family'])){
            $g5_families[] = $g5_person['name']['family'];
        }
        if(isset($g5_person['name']['official']['family'])){
            $g5_families[] = $g5_person['name']['official']['family'];
        }
        if(isset($g5_person['name']['fame']['family'])){
            $g5_families[] = $g5_person['name']['fame']['family'];
        }
        $g5_families = array_unique($g5_families);
        //
        $wd_families = [];
        if(isset($wd_person['name']['family'])){
            foreach($wd_person['name']['family'] as $wd_family){
                $wd_families[] = $wd_family;
            }
        }
        $wd_families = array_unique($wd_families);
        //
        foreach($g5_families as $g5_family){
            foreach($wd_families as $wd_family){
                if(levenshtein($g5_family, $wd_family) <= 1){
                    $match_family++;
                }
            }
        }
        //
        // given
        //
        $g5_givens = [];
        if(isset($g5_person['name']['given'])){
            $g5_givens[] = $g5_person['name']['given'];
        }
        if(isset($g5_person['name']['official']['given'])){
            $g5_givens[] = $g5_person['name']['official']['given'];
        }
        if(isset($g5_person['name']['fame']['given'])){
            $g5_givens[] = $g5_person['name']['fame']['given'];
        }
        $g5_givens = array_unique($g5_givens);
        //
        $wd_givens = [];
        if(isset($wd_person['name']['given'])){
            foreach($wd_person['name']['given'] as $wd_given){
                $wd_givens[] = $wd_given;
            }
        }
        $wd_givens = array_unique($wd_givens);
        //
        foreach($g5_givens as $g5_given){
            foreach($wd_givens as $wd_given){
                if(levenshtein($g5_given, $wd_given) <= 1){
                    $match_given++;
                }
            }
        }
        //
        // alternative names - for g5, we take also ['fame']['full']
        //
        $g5_alters = [];
        if(isset($g5_person['name']['alter'])){
            foreach($g5_person['name']['alter'] as $g5_alter){
                $g5_alters[] = $g5_alter;
            }
        }
        if(isset($g5_person['name']['fame']['full'])){
            $g5_alters[] = $g5_person['name']['fame']['full'];
        }
        $g5_alters = array_unique($g5_alters);
        //
        $wd_alters = [];
        if(isset($wd_person['name']['alter'])){
            foreach($wd_person['name']['alter'] as $wd_alter){
                $wd_alters[] = $wd_alter;
            }
        }
        //
        foreach($g5_alters as $g5_alter){
            foreach($wd_alters as $wd_alter){
                if(levenshtein($g5_alter, $wd_alter) <= 1){
                    $match_alter++;
                }
            }
        }
        //
        // spouse
        //
        $g5_spouses = [];
        if(isset($g5_person['name']['spouse'])){
            $g5_spouses[] = $g5_person['name']['spouse'];
        }
        $g5_spouses = array_unique($g5_spouses);
        //
        $wd_spouses = [];
        if(isset($wd_person['name']['spouse'])){
            $wd_spouses[] = $wd_person['name']['spouse'];
        }
        //
        foreach($g5_spouses as $g5_spouse){
            foreach($wd_spouses as $wd_spouse){
                if(levenshtein($g5_spouse, $wd_spouse) <= 1){
                    $match_spouse++;
                }
            }
        }
        //
        // result
        //
        $match = ($match_family + $match_given + $match_alter + $match_spouse > 0) ? 1 : 0;
        return $match;
    }
    
    /** @return A match score, between 0 (no match) and 1 (match certain) **/
    private static function match_birthplace(array &$g5_person, &$wd_person): int {
        $match = 0;
        return $match;
    }
    
    /** @return A match score, between 0 (no match) and 1 (match certain) **/
    private static function match_occus(array &$g5_person, &$wd_person): int {
        $match = 0;
        return $match;
    }
    
    /** @return A match score, between 0 (no match) and 1 (match certain) **/
    private static function match_sex(array &$g5_person, &$wd_person): int {
        $match = 0;
        return $match;
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
    
    // ============================= Build wd and g5 persons =============================
    
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
        if(isset($candidate[Property::FAMILY_NAME])){
            $res['name']['fame']['family'] = [];
            foreach($candidate[Property::FAMILY_NAME]['values'] as $value){
                $res['name']['family'][] = $value['label'];
            }
        }
        if(isset($candidate[Property::GIVEN_NAME])){
            $res['name']['fame']['given'] = [];
            foreach($candidate[Property::GIVEN_NAME]['values'] as $value){
                $res['name']['given'][] = $value['label'];
            }
        }
        if(isset($candidate[Property::MARRIED_NAME])){
            $res['name']['spouse'] = [];
            foreach($candidate[Property::MARRIED_NAME]['values'] as $value){
                $res['name']['spouse'][] = $value['label'];
            }
        }
        // g5 field 'official' used for BIRTH_NAME, OFFICIAL_NAME, NAME_IN_NATIVE_LANGUAGE
        $res['name']['official']['full'] = [];
        if(isset($candidate[Property::BIRTH_NAME])){
            foreach($candidate[Property::BIRTH_NAME]['values'] as $value){
                $res['name']['official']['full'][] = $value['label'];
            }
        }
        if(isset($candidate[Property::NAME_IN_NATIVE_LANGUAGE])){
            foreach($candidate[Property::NAME_IN_NATIVE_LANGUAGE]['values'] as $value){
                $res['name']['official']['full'][] = $value['label'];
            }
        }
        if(isset($candidate[Property::OFFICIAL_NAME])){
            foreach($candidate[Property::OFFICIAL_NAME]['values'] as $value){
                $res['name']['official']['full'][] = $value['label'];
            }
        }
        // g5 field 'alter' used for NAME, SHORT_NAME, NICKNAME
        $res['name']['alter'] = [];
        if(isset($candidate[Property::NAME])){
            foreach($candidate[Property::NAME]['values'] as $value){
                $res['name']['alter'][] = $value['label'];
            }
        }
        if(isset($candidate[Property::SHORT_NAME])){
            foreach($candidate[Property::SHORT_NAME]['values'] as $value){
                $res['name']['alter'][] = $value['label'];
            }
        }
        if(isset($candidate[Property::NICKNAME])){
            foreach($candidate[Property::NICKNAME]['values'] as $value){
                $res['name']['alter'][] = $value['label'];
            }
        }
        //
        // Birth
        //
        if(isset($candidate[Property::DATE_OF_BIRTH])){
            $res['birth']['date'] = [];
            foreach($candidate[Property::DATE_OF_BIRTH]['values'] as $value){
                $res['birth']['date'][] = substr($value['label'], 0, 10);
            }
        }
        if(isset($candidate[Property::PLACE_OF_BIRTH])){
            $res['birth']['place'] = [];
            foreach($candidate[Property::PLACE_OF_BIRTH]['values'] as $value){
                $newPlace = [];
                $newPlace['name'] = $value['label'];
                $newPlace['wd-id'] = $value['id'];      // wd-id: field not existing in g5 model
                $res['birth']['place'][] = $newPlace;
            }
        }
        //
        // Death
        //
        if(isset($candidate[Property::DATE_OF_DEATH])){
            $res['death']['date'] = [];
            foreach($candidate[Property::DATE_OF_DEATH]['values'] as $value){
                $res['death']['date'][] = substr($value['label'], 0, 10);
            }
        }
        if(isset($candidate[Property::PLACE_OF_DEATH])){
            $res['death']['place'] = [];
            foreach($candidate[Property::PLACE_OF_DEATH]['values'] as $value){
                $newPlace = [];
                $newPlace['name'] = $value['label'];
                $newPlace['wd-id'] = $value['id'];      // wd-id: field not existing in g5 model
                $res['death']['place'][] = $newPlace;
            }
        }
        //
        // Other fields
        //
        if(isset($candidate[Property::OCCUPATION])){
            $res['occus'] = [];
            foreach($candidate[Property::OCCUPATION]['values'] as $value){
                // differs from g5, occus contains an array of slugs
                $res['occus'][] = [
                    'wd-id' => $value['id'],
                    'label' => $value['label'],
                ];
            }
        }
        if(isset($candidate[Property::SEX_OR_GENDER])){
            $res['sex'] = [];
            foreach($candidate[Property::SEX_OR_GENDER]['values'] as $value){
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
