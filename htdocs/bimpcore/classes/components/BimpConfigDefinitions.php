<?php

class BimpConfigDefinitions
{

    public static $labels = array(
        'name'      => array('required' => true, 'default' => ''),
        'name_plur' => array(),
        'is_female' => array('data_type' => 'bool', 'default' => 0)
    );
    public static $object_child = array(
        'relation' => array('default' => 'none'),
        'delete'   => array('data_type' => 'bool', 'default' => 0),
        'instance' => array('data_type' => 'array')
    );
    public static $button = array(
        'label'   => array('required' => true),
        'icon'    => array(),
        'onclick' => array('required' => true)
    );
    public static $search = array(
        'type'             => array('default' => 'field_input'),
        'part_type'        => array('default' => 'beginning'),
        'search_on_key_up' => array('data_type' => 'bool', 'default' => 1),
        'option'           => array()
    );
    public static $sort_option = array(
        'label'      => array('required' => true),
        'join_field' => array()
    );
    public static $display_if = array(
        'field_name'  => array('required' => true),
        'show_values' => array('data_type' => 'array', 'compile' => 0),
        'hide_values' => array('data_type' => 'array', 'compile' => 0)
    );
    public static $join = array(
        'table' => array(),
        'on'    => array(),
        'alias' => array()
    );

}
