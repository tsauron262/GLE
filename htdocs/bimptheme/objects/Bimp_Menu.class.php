<?php

class Bimp_Menu extends BimpObject
{

    public static $elements = null;
    public static $userTypes = array(
        0 => 'Interne',
        1 => 'Externe',
        2 => 'Tous'
    );

    // Getters statiques: 

    public static function getMenuElements($enabled = true, $perms = true)
    {
        
    }
    
    // Traitements: 
    
    public static function laodElements($handler = 'all')
    {
        if (is_null(self::$elements)) {
            $rows = self::getBdb()->getRows('menu', $where, null, 'array');
            
            foreach ($rows as $r) {
                
            }
        }
    }
}
