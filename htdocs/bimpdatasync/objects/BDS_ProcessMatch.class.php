<?php

class BDS_ProcessMatch extends BimpObject
{

    public static $types = array(
        'loc_table' => 'Table de données locale',
        'custom'    => 'Correspondances personnalisées'
    );

    // Getters params: 

    public function getNameProperties()
    {
        // Nécessaire pour régler le conflit avec le champ "name"
        return array('label');
    }
    
    public function getListExtraButtons()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            if ($this->getData('type') === 'custom') {
                $instance = BimpObject::getInstance('bimpdatasync', 'BDS_ProcessMatchCustomValues');
                $buttons[] = array(
                    'label'   => 'Valeurs',
                    'icon'    => 'fas_bars',
                    'onclick' => $instance->getJsLoadModalList('process', array(
                        'id_parent' => $this->id
                    ))
                );
            }
        }

        return $buttons;
    }

    // Getters données: 

    public function getMatchedValue($value, $processType = 'import')
    {
        $table = '';
        $field = '';
        $where = '';

        switch ($this->type) {
            case 'loc_table':
                $table = $this->getData('loc_table');
                switch ($processType) {
                    case 'import':
                        $field = $this->getData('field_out');
                        $where = '`' . $this->getData('field_in') . '`';
                        break;

                    case 'export':
                        $field = $this->getData('field_in');
                        $where = '`' . $this->getData('field_out') . '`';
                        break;
                }
                break;

            case 'custom':
                $table = 'bds_process_match_custom_values';
                $where = '`id_match` = ' . (int) $this->id;

                switch ($processType) {
                    case 'import':
                        $field = 'loc_value';
                        $where .= ' AND `ext_value`';
                        break;

                    case 'export':
                        $field = 'ext_value';
                        $where .= ' AND `loc_value`';
                        break;
                }
                break;
        }

        if ($table && $where && $field) {
            $where .= ' = \'' . $value . '\'';
            return $this->db->getValue($table, $field, $where);
        }
        return null;
    }
}
