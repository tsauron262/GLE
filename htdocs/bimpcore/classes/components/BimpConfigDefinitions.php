<?php

class BimpConfigDefinitions
{

    public static $button = array(
        'label'   => array('required' => true),
        'icon'    => array(),
        'onclick' => array('required' => true)
    );
    public static $search = array(
        'type'             => array('default' => 'field_input'),
        'part_type'        => array('default' => 'beginning'),
        'search_on_key_up' => array('data_type' => 'bool', 'default' => 1)
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
