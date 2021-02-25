<?php

class Bimp_SocRemiseExcept extends BimpObject
{

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

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'facture_dest':
                if (!empty($values)) {
                    $or_filters = array();

                    $key = 'in';
                    if ($excluded) {
                        $key = 'not_in';
                    }
                    $or_filters['a.fk_facture'] = array(
                        $key => $values
                    );

                    $select_lines = 'SELECT DISTINCT fac_line.rowid';
                    $select_lines .= ' FROM ' . MAIN_DB_PREFIX . 'facturedet fac_line';
                    $select_lines .= ' WHERE fac_line.fk_facture ' . ($excluded ? 'NOT ' : '') . 'IN (' . implode(',', $values) . ')';
                    $select_lines .= ' AND fac_line.fk_remise_except = a.rowid';

                    $or_filters['custom_fac_dest_lines'] = array(
                        'custom' => 'a.fk_facture_line IN (' . $select_lines . ')'
                    );

                    if (!$excluded) {
                        $filters['or_facture_dest'] = array(
                            'or' => $or_filters
                        );
                    } else {
                        $filters['and_facture_dest'] = array(
                            'and_fields' => $or_filters
                        );
                    }
                }
                break;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $errors, $excluded);
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
}
