<?php

require_once DOL_DOCUMENT_ROOT . '/bimpuserconfig/objects/ListConfig.class.php';

class StatsListConfig extends ListConfig
{

    public static $config_object_name = 'StatsListConfig';
    public static $config_table = 'buc_stats_list_config';
    public static $component_type = 'stats_list';
    
    public static $nbItems = array(
        10  => '10',
        25  => '25',
        50  => '50',
        100 => '100',
        200 => '200'
    );

}
