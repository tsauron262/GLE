<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/Bimp_Product.class.php';

class BE_ProductImmos extends Bimp_Product
{

    public static $place_types = null;

    // Getters array: 

    public static function getPlaceTypesArray()
    {
        if (is_null(self::$place_types)) {
            BimpObject::loadClass('bimpequipment', 'BE_Place');

            self::$place_types = array();

            foreach (BE_Place::$types as $value => $label) {
                if (in_array($value, BE_Place::$immos_types)) {
                    self::$place_types[$value] = $label;
                }
            }
        }

        return self::$place_types;
    }

    // Getters params: 

    public function getQtySql($id_product, $serialisable = 0, $immos_type = null)
    {
        if (is_null($immos_type)) {
            BimpObject::loadClass('bimpequipment', 'BE_Place');

            $immos_type = BE_Place::$immos_types;
        }

        if (!$serialisable) {
            $sql = 'SELECT SUM(pp.qty) as qty FROM ' . MAIN_DB_PREFIX . 'be_package_product pp';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_package_place ppl ON ppl.id_package = pp.id_package';
            $sql .= ' WHERE pp.id_product = ' . $id_product . ' AND ppl.position = 1 AND ppl.type IN (' . implode(',', $immos_type) . ')';
        } else {
            $sql = 'SELECT count(DISTINCT e.id) as qty FROM ' . MAIN_DB_PREFIX . 'be_equipment e';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_equipment_place ep ON ep.id_equipment = e.id';
            $sql .= ' WHERE e.id_product = ' . $id_product . ' AND ep.position = 1 AND ep.type IN (' . implode(',', $immos_type) . ')';
        }
                
        return $sql;
    }

    public function getImmosListFilters()
    {
        $filters = array();

        $filters[] = array(
            'name'   => 'fk_product_type',
            'filter' => 0
        );

        $filters[] = array(
            'name'   => '(' . $this->getQtySql('a.rowid', 0) . ')',
            'filter' => array(
                'operator' => '>',
                'value'    => 0
            )
        );

        return $filters;
    }

    // Overrides BimpObject

    public function fetchExtraFields()
    {
        $fields = array();

        if ($this->isLoaded()) {
            if ($this->isSerialisable()) {
                $result = $this->db->executeS($this->getQtySql($this->id, 1), 'array');

                if (isset($result[0]['qty'])) {
                    $qty = (int) $result[0]['qty'];
                } else {
                    $qty = 0;
                }

                $pa = 0;

                $fields = array(
                    'qty'          => $qty,
                    'total_achats' => ($qty * $pa)
                );
            } else {
                $result = $this->db->executeS($this->getQtySql($this->id, 0), 'array');

                if (isset($result[0]['qty'])) {
                    $qty = (int) $result[0]['qty'];
                } else {
                    $qty = 0;
                }

                $pa = (float) $this->getCurrentPaHt(null, true);

                $fields = array(
                    'qty'          => $qty,
                    'total_achats' => ($qty * $pa)
                );
            }
        }
        return $fields;
    }

    public function getExtraFieldSavedValue($field, $id_object)
    {
        // retourner la valeur actuelle en base pour le champ $field et l'ID objet $id_object (Ici, ne pas tenir compte de $this->id). 
        // Retourner null si pas d'entrée en base. 

        return null;
    }

    public function getExtraFieldFilterKey($field, &$joins, $main_alias = '')
    {
        // Retourner la clé de filtre SQL sous la forme alias_table.nom_champ_db 
        // Implémenter la jointure dans $joins en utilisant l'alias comme clé du tableau (pour éviter que la même jointure soit ajouté plusieurs fois à $joins). 
        // Si $main_alias est défini, l'utiliser comme préfixe de alias_table. Ex: $main_alias .'_'.$alias_table (Bien utiliser l'underscore).  
        // ET: utiliser $main_alias à la place de "a" dans la clause ON. 
//        Ex: 
//        $join_alias = ($main_alias ? $main_alias . '_' : '') . 'xxx';
//        $joins[$join_alias] = array(
//            'alias' => $join_alias,
//            'table' => 'nom_table',
//            'on'    => $join_alias . '.xxx = ' . ($main_alias ? $main_alias : 'a') . '.xxx'
//        );
//        
//        return $join_alias.'.nom_champ_db';

        return '';
    }

    public function insertExtraFields()
    {
        return array();
    }

    public function updateExtraFields()
    {
        return array();
    }

    public function updateExtraField($field_name, $value, $id_object)
    {
        return array();
    }

    public function deleteExtraFields()
    {
        return array();
    }
}
