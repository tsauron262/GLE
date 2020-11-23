<?php

require_once DOL_DOCUMENT_ROOT . '/bimpuserconfig/objects/ListConfig.class.php';

class StatsListConfig extends ListConfig
{

    public static $config_object_name = 'StatsListConfig';
    public static $config_table = 'buc_stats_list_config';
    public static $component_type = 'stats_list';
    public static $has_search = true;
    public static $has_filters = true;
    public static $has_total = true;
    public static $has_pagination = true;
    public static $has_cols = true;
    public static $nbItems = array(
        10  => '10',
        25  => '25',
        50  => '50',
        100 => '100',
        200 => '200'
    );

    public function renderColsInput()
    {
        if (!$this->hasCols()) {
            return '';
        }

        $html = '';

        $list_cols = array();
        $list_name = $this->getData('component_name');
        $object = $this->getObjInstance();

        if ($list_name && is_a($object, 'BimpObject')) {
            $list_cols = $object->getStatsListColsArray($list_name);
        }

        if (count($list_cols)) {
            $values = array();
            if ($this->isLoaded()) {
                $cols = $this->getData('cols');

                if (is_array($cols)) {
                    foreach ($cols as $col_name) {
                        if (isset($list_cols[$col_name])) {
                            $values[$col_name] = $list_cols[$col_name];
                        }
                    }
                }
            } else {
                $values = $list_cols;
            }

            $input = BimpInput::renderInput('select', 'cols_add_value', '', array('options' => $list_cols));
            $content = BimpInput::renderMultipleValuesInput($this, 'cols', $input, $values, '', 0, 1, 1);
            $html .= BimpInput::renderInputContainer('cols', '', $content, '', 0, 1, '', array('values_field' => 'cols'));
        } else {
            $html .= BimpRender::renderAlerts('Aucune option disponible', 'warnings');
        }

        return $html;
    }
    
}
