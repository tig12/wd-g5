<?php
/******************************************************************************
    
    Constants related to Wikidata entities
    
    @license    GPL
    @history    2025-05-17 20:02:42+02:00, Thierry Graff : Isolate wd entites
    @history    2025-05-03 13:04:49+02:00, Thierry Graff : Creation
********************************************************************************/

declare(strict_types=1);

namespace wdg5\model\wikidata;

class Entity {
    
    public const array ENTITY_NAMES = [
        'Q5'        => 'human',
        'Q6581072'  => 'female',
        'Q6581097'  => 'male',
    ];
    
    public const string HUMAN       = 'Q5';
    public const string FEMALE      = 'Q6581072';
    public const string MALE        = 'Q6581097';
    
} // end class
