<?php

class TestObject extends BimpObject
{

    public $field1;
    public $field2;
    public $field3;
    public static $table = 'bimp_test_object';
    public static $labels = array(
        'name' => 'objet'
    );
    public static $fields = array(
        'field1' => array(
            'label'    => 'CHAMP TEXTE',
            'type'     => 'string',
            'input'    => 'text',
            'required' => true
        ),
        'field2' => array(
            'label'    => 'CHAMP SELECT',
            'type'     => 'string',
            'input'    => 'select',
            'options'  => array(
                '1' => 'Choix 1',
                '2' => 'Choix 2'
            ),
            'required' => true
        ),
        'field3' => array(
            'label'    => 'CHAMP PERSO',
            'type'     => 'string',
            'input'    => 'callback',
            'required' => true
        ),
    );

    public static function getClass()
    {
        return 'TestObject';
    }

    public function getField3Input($value)
    {
        return '<p>Ceci est un champ perso</p>';
    }
}
