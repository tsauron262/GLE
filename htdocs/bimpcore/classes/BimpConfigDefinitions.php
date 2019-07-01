<?php

class BimpConfigDefinitions
{

    public static $labels = array(
        'name'      => array('required' => true, 'default' => ''),
        'name_plur' => array(),
        'is_female' => array('data_type' => 'bool', 'default' => 0)
    );
    public static $object_child = array(
        'relation'  => array('default' => 'none'),
        'delete'    => array('data_type' => 'bool', 'default' => 0),
        'instance'  => array('data_type' => 'array'),
        'has_files' => array('data_type' => 'bool', 'default' => 0)
    );
    public static $button = array(
        'id'          => array(),
        'label'       => array('required' => true),
        'icon'        => array(),
        'onclick'     => array(),
        'icon_before' => array(),
        'icon_after'  => array(),
        'classes'     => array('data_type' => 'array'),
        'attr'        => array('data_type' => 'array'),
        'data'        => array('data_type' => 'array'),
        'styles'      => array('data_type' => 'array')
    );
    public static $input = array(
        'type'    => array('default' => 'text'),
        'options' => array('data_type' => 'array', 'default' => array(), 'compile' => true)
    );
    public static $search = array(
        'type'             => array('default' => 'field_input'),
        'part_type'        => array('default' => 'middle'),
        'search_on_key_up' => array('data_type' => 'bool', 'default' => 0),
        'option'           => array(),
        'input'            => array('type' => 'definitions', 'defs_type' => 'input')
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
    public static $single_cell = array(
        'filters' => array('data_type' => 'array', 'compile' => 1),
        'col'     => array('default' => '')
    );
    public static $msgs = array(
        'content' => array('default' => ''),
        'type'    => array('default' => 'info')
    );
    public static $list_filter = array(
        'custom' => array('data_type' => 'bool', 'default' => 0),
        'field'  => array('default' => true),
        'child'  => array(),
        'name'   => array('default' => 'default'),
        'open'   => array('data_type' => 'bool', 'default' => 0),
        'show'   => array('data_type' => 'bool', 'default' => 1)
    );
    public static $icon_button = array(
        'label'   => array('default' => ''),
        'icon'    => array('required' => true),
        'onclick' => array('default' => '')
    );
    public static $group_by_option = array(
        'field' => array('default' => '')
    );

}
