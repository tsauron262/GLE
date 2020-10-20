<?php

require_once DOL_DOCUMENT_ROOT.'/bimpuserconfig/objects/BCUserConfig.class.php';

class FiltersConfig extends BCUserConfig {
    public static $config_object_name = 'FiltersConfig';
    public static $config_table = 'buc_filters_config';
}