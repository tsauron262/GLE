<?php

class Bimp_SocRemiseExcept extends BimpObject
{

    public static $types = array(
        0 => 'Client',
        1 => 'Fournisseur'
    );

    public function canCreate()
    {
        return 0;
    }

    public function canEdit()
    {
        return 0;
    }

    public function canDelete()
    {
        return 0;
    }

    // Getters params: 

    public function getCustomFilterValueLabel($field_name, $value)
    {
        switch (facture_dest) {
            case 'facture_dest':
                $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $value);
                if (BimpObject::ObjectLoaded($fac)) {
                    return $fac->getRef();
                }
                break;
        }

        return parent::getCustomFilterValueLabel($field_name, $value);
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, $main_alias = 'a', &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'facture_dest':
                if (!empty($values)) {
                    $or_filters = array();

                    $key = 'in';
                    if ($excluded) {
                        $key = 'not_in';
                    }
                    $or_filters[$main_alias . '.fk_facture'] = array(
                        $key => $values
                    );

                    $select_lines = 'SELECT DISTINCT fl.rowid';
                    $select_lines .= ' FROM ' . MAIN_DB_PREFIX . 'facturedet fl';
                    $select_lines .= ' WHERE fl.fk_facture ' . ($excluded ? 'NOT ' : '') . 'IN (' . implode(',', $values) . ')';
                    $select_lines .= ' AND fl.fk_remise_except = ' . $main_alias . '.rowid';

                    $or_filters[$main_alias . '___custom_fac_dest_lines'] = array(
                        'custom' => $main_alias . '.fk_facture_line IN (' . $select_lines . ')'
                    );

                    if (!$excluded) {
                        $filters[$main_alias . '___or_facture_dest'] = array(
                            'or' => $or_filters
                        );
                    } else {
                        $filters[$main_alias . '___and_facture_dest'] = array(
                            'and_fields' => $or_filters
                        );
                    }
                }
                break;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $main_alias, $errors, $excluded);
    }

    // Getters données: 

    public function getFacLineParent()
    {
        if ((int) $this->getData('fk_facture_line')) {
            $id_fac = (int) $this->db->getValue('facturedet', 'fk_facture', 'rowid = ' . (int) $this->getData('fk_facture_line'));

            if ($id_fac) {
                $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_fac);
                if (BimpObject::objectLoaded($fac)) {
                    return $fac;
                }
            }
        }

        return null;
    }

    // Affichages: 

    public function displayDesciption()
    {
        $desc = (string) $this->getData('description');

        if ($desc) {
            $desc = str_replace('(DEPOSIT)', 'Acompte', $desc);
            $desc = str_replace('(EXCESS RECEIVED)', 'Trop perçu', $desc);
            $desc = str_replace('(CREDIT_NOTE)', 'Avoir', $desc);
        }

        return $desc;
    }

    public function displayFacDest()
    {
        $fac = null;
        if ((int) $this->getData('fk_facture')) {
            $fac = $this->getChildObject('facture');
        }

        if (!BimpObject::objectLoaded($fac)) {
            $fac = $this->getFacLineParent();
        }

        if (BimpObject::objectLoaded($fac)) {
            return $fac->getLink();
        }

        return '';
    }

    // Rendus HTML : 

    public function renderHeaderExtraLeft()
    {
        $html = '';

        if ($this->getData('datec') || (int) $this->getData('fk_user')) {
            $html .= '<div class="object_header_infos">';
            $html .= 'Créé';
            if ($this->getData('datec')) {
                $html .= ' le <strong>' . date('d / m / Y', strtotime($this->getData('datec'))) . '</strong>';
            }
            if ((int) $this->getData('fk_user')) {
                $user = $this->getChildObject('user');
                if (BimpObject::objectLoaded($user)) {
                    $html .= ' par ' . $user->getLink();
                }
            }
            $html .= '</div>';
        }

        return $html;
    }
}
