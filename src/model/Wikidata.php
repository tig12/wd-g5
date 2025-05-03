<?php
/******************************************************************************
    
    @license    GPL
    @history    2025-05-03 13:04:49+02:00, Thierry Graff : Creation
********************************************************************************/

declare(strict_types=1);

namespace wdg5\model;

class Wikidata {
    
    public const string ENTITY_HUMAN        = 'Q5';
    
    /*
        'P31'   => 'instance of',
        'P2561' => 'name',
        'P1477' => 'birth name',
        'P734'  => 'family name',
        'P735'  => 'given name',
        'P1813' => 'short name',
        'P1449' => 'nickname',
        'P1448' => 'official name',
        'P2562' => 'married name',
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
    */
    
    public const string PROP_INSTANCE_OF     = 'P31';
    
    public const string PROP_NAME            = 'P2561';
    public const string PROP_BIRTH_NAME      = 'P1477';
    public const string PROP_FAMILY_NAME     = 'P734';
    public const string PROP_GIVEN_NAME      = 'P735';
    public const string PROP_SHORT_NAME      = 'P1813';
    public const string PROP_NICKNAME        = 'P1449';
    public const string PROP_OFFICIAL_NAME   = 'P1448';
    public const string PROP_MARRIED_NAME    = 'P2562';
    
    public const string PROP_DATE_OF_BIRTH   = 'P569';
    public const string PROP_PLACE_OF_BIRTH  = 'P19';
    
    public const string PROP_DATE_OF_DEATH   = 'P570';
    public const string PROP_PLACE_OF_DEATH  = 'P20';
    
    public const string PROP_OCCUPATION      = 'P106';
    
    public const string PROP_SEX_OR_GENDER   = 'P21';

    public const array USEFUL_PROPERTIES = [
        self::PROP_NAME,
        self::PROP_BIRTH_NAME,
        self::PROP_FAMILY_NAME,
        self::PROP_GIVEN_NAME,
        self::PROP_SHORT_NAME,
        self::PROP_NICKNAME,
        self::PROP_OFFICIAL_NAME,
        self::PROP_MARRIED_NAME,
        
        self::PROP_DATE_OF_BIRTH,
        self::PROP_PLACE_OF_BIRTH,
        
        self::PROP_DATE_OF_DEATH,
        self::PROP_PLACE_OF_DEATH,
        
        self::PROP_OCCUPATION,
        
        self::PROP_SEX_OR_GENDER,
    ];
    
} // end class
