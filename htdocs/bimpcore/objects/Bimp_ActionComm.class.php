<?php

class Bimp_ActionComm extends BimpObject
{

    // Getters booléens: 

    public function isCreatable($force_create = false, &$errors = array())
    {
        return 0;
    }

    public function isEditable($force_edit = false, &$errors = array())
    {
        return 0;
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        return 0;
    }

    // Getters array: 

    public function getTypesArray($include_empty = false)
    {
        $cache_key = 'action_comm_types_values_array';

        if (!isset(self::$cache[$cache_key])) {
            $rows = $this->db->getRows('c_actioncomm', '1', null, 'array', array('id', 'icon', 'libelle'), 'position', 'asc');

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][(int) $r['id']] = array('label' => $r['libelle'], 'icon' => $r['icon']);
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    // Getters params: 

    public function getRefProperty()
    {
        return '';
    }

    public function getCustomFilterValueLabel($field_name, $value)
    {
        switch ($field_name) {
            case 'propal':
                if ((int) $value) {
                    return $this->db->getValue('propal', 'ref', 'rowid = ' . (int) $value);
                }
                break;
            case 'commande':
                if ((int) $value) {
                    return $this->db->getValue('commande', 'ref', 'rowid = ' . (int) $value);
                }
                break;
            case 'facture':
                if ((int) $value) {
                    return $this->db->getValue('facture', 'facnumber', 'rowid = ' . (int) $value);
                }
                break;
        }
        return parent::getCustomFilterValueLabel($field_name, $value);
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'propal':
            case 'commande':
            case 'facture':
                $element_type = '';
                switch ($field_name) {
                    case 'propal':
                        $element_type = 'propal';
                        break;
                    case 'commande':
                        $element_type = 'order';
                        break;
                    case 'facture':
                        $element_type = 'facture';
                        break;
                }
                $filters['a.elementtype'] = $element_type;
                $filters['a.fk_element'] = array(
                    ($excluded ? 'not_' : '') . 'in' => $values
                );
                break;
        }
        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $errors);
    }

    // Affichages: 

    public function displayElement()
    {
        $html = '';
        if ((int) $this->getData('fk_element') && $this->getData('elementtype')) {
            $instance = BimpTools::getInstanceByElementType($this->getData('elementtype'), (int) $this->getData('fk_element'));

            if (is_null($instance)) {
                $html .= '<span class="danger">Type "' . $this->getData('elementtype') . '" inconnu</span>';
            } elseif (BimpObject::objectLoaded($instance)) {
                $html .= BimpObject::getInstanceNomUrl($instance);
            } else {
                $html .= BimpTools::ucfirst(BimpObject::getInstanceLabel($instance) . ' #' . $this->getData('fk_element'));
            }
        }

        return $html;
    }

    public function displayState()
    {
        if ($this->isLoaded()) {
            $percent = (float) $this->getData('percent');
            $date = $this->getData('datep');

            if (($percent >= 0 && $percent < 100) || ($percent == -1 && $date > date('Y-m-d H:i:'))) {
                return '<span class="warning">' . BimpRender::renderIcon('fas_exclamation', 'iconLeft') . 'A faire</span>';
            } else {
                return '<span class="success">' . BimpRender::renderIcon('fas_check', 'iconLeft') . 'Fait</span>';
            }
        }

        return '';
    }
    
    public function getListFilters($list = 'default')
    {
        global $user;
        $filters = array();
        
        switch($list) {
            case 'ficheInter': 
                $filters[] = array('name' => 'fk_element','filter' => $_REQUEST['id']);
                $filters[] = array('name' => 'elementtype', 'filter' => 'fichinter');
                break;
        }

        return $filters;
    }
}
