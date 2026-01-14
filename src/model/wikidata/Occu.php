<?php
/******************************************************************************
    
    Functions related to occupations, as stored in wd-occus sqlite database
    
    @license    GPL
    @history    2025-05-29 00:18:37+02:00, Thierry Graff : Creation
********************************************************************************/

declare(strict_types=1);

namespace wdg5\model\wikidata;

class Occu {
    
    // ******************************************************************************
    // STATIC
    // ******************************************************************************
    
    /** 
        Computes the slugs of the ancestors of an occupation.
        @param  $slug   Slug of the occupation
    **/
    public static function createFromSlug($slug): array {
        $res = [];
        return $res;
    }
    
    
    // ******************************************************************************
    // INSTANCE
    // ******************************************************************************
    
    public array $data;
    
    
    public function __set($name, $value)
    {
        echo "Setting '$name' to '$value'\n";
        $this->data[$name] = $value;
    }
    
    /** 
        Computes the slugs of qll the ancestors of an occupation.
        @param  $slug   Slug of the occupation
    **/
    public function getAllAncestors($slug): array {
        $res = [];
        return $res;
    }
    
} // end class
