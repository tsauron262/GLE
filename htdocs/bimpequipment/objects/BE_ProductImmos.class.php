<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/Bimp_Product.class.php';

class BE_ProductImmos extends Bimp_Product
{

//    public static $place_types = null;
    public static $currentFilters = array();
    public $items = array();

    // Getters array: 

    public static function getPlaceTypesArray()
    {
//        if (is_null(self::$place_types)) {
        BimpObject::loadClass('bimpequipment', 'BE_Place');
        return BE_Place::$types;

//            self::$place_types = array();
//
//            foreach (BE_Place::$types as $value => $label) {
//                if (in_array($value, BE_Place::$immos_types)) {
//                    self::$place_types[$value] = $label;
//                }
//            }
//        }
//
//        return self::$place_types;
    }

    // Getters params: 

    public function getImmosListFilters()
    {
        $filters = array();

        $filters[] = array(
            'name'   => 'fk_product_type',
            'filter' => 0
        );

        $filters[] = array(
            'name'   => 'or_product',
            'filter' => array(
                'or' => array(
                    'cust_noserial'   => array(
                        'custom' => '(ef.serialisable = 0 AND a.rowid IN (SELECT DISTINCT pp.id_product FROM ' . MAIN_DB_PREFIX . 'be_package_product pp WHERE pp.id_product = a.rowid))'
                    ),
                    'ef.serialisable' => 1
                )
            )
        );

        return $filters;
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array())
    {
        switch ($field_name) {
            case 'place_position':
                self::$currentFilters['place_position'] = $values;
                break;

            case 'place_type':
                if (is_array($values) && !empty($values)) {
                    self::$currentFilters['place_type'] = $values;
                    $filters['or_place_type'] = array(
                        'or' => array(
                            'ppl_type' => array(
                                'custom' => '(ef.serialisable = 0 AND ' . $this->getPlacePositionSqlFilter('ppl') . ' AND ppl.type IN (' . implode(',', $values) . '))'
                            ),
                            'epl_type' => array(
                                'custom' => '(ef.serialisable = 1 AND ' . $this->getPlacePositionSqlFilter('epl') . ' AND epl.type IN (' . implode(',', $values) . '))'
                            )
                        )
                    );
                    $this->getPlacesJoins($joins);
                } else {
                    self::$currentFilters['place_type'] = array();
                }
                break;

            case 'place_id_entrepot':
                if (is_array($values) && !empty($values)) {
                    self::$currentFilters['place_id_entrepot'] = $values;
                    $filters['or_place_entrepot'] = array(
                        'or' => array(
                            'ppl_entrepot' => array(
                                'custom' => '(ef.serialisable = 0 AND ' . $this->getPlacePositionSqlFilter('ppl') . ' AND ppl.id_entrepot IN (' . implode(',', $values) . '))'
                            ),
                            'epl_entrepot' => array(
                                'custom' => '(ef.serialisable = 1 AND ' . $this->getPlacePositionSqlFilter('epl') . ' AND epl.id_entrepot IN (' . implode(',', $values) . '))'
                            )
                        )
                    );
                    $this->getPlacesJoins($joins);
                } else {
                    self::$currentFilters['place_id_entrepot'] = array();
                }
                break;

            case 'place_id_user':
                if (is_array($values) && !empty($values)) {
                    self::$currentFilters['place_id_user'] = $values;
                    $filters['or_place_user'] = array(
                        'or' => array(
                            'ppl_user' => array(
                                'custom' => '(ef.serialisable = 0 AND ' . $this->getPlacePositionSqlFilter('ppl') . ' AND ppl.id_user IN (' . implode(',', $values) . '))'
                            ),
                            'epl_user' => array(
                                'custom' => '(ef.serialisable = 1 AND ' . $this->getPlacePositionSqlFilter('epl') . ' AND epl.id_user IN (' . implode(',', $values) . '))'
                            )
                        )
                    );
                    $this->getPlacesJoins($joins);
                } else {
                    self::$currentFilters['place_id_user'] = array();
                }
                break;

            case 'place_id_client':
                if (is_array($values) && !empty($values)) {
                    self::$currentFilters['place_id_client'] = $values;
                    $filters['or_place_client'] = array(
                        'or' => array(
                            'ppl_client' => array(
                                'custom' => '(ef.serialisable = 0 AND ' . $this->getPlacePositionSqlFilter('ppl') . ' AND ppl.id_client IN (' . implode(',', $values) . '))'
                            ),
                            'epl_client' => array(
                                'custom' => '(ef.serialisable = 1 AND ' . $this->getPlacePositionSqlFilter('epl') . ' AND epl.id_client IN (' . implode(',', $values) . '))'
                            )
                        )
                    );
                    $this->getPlacesJoins($joins);
                } else {
                    self::$currentFilters['place_id_client'] = array();
                }
                break;

            case 'place_name':
                if (is_array($values) && !empty($values)) {
                    self::$currentFilters['place_name'] = $values;
                    $or_field = array();
                    foreach ($values as $value) {
                        $value = (string) $value;
                        if ($value !== '') {
                            $or_field[] = array(
                                'part_type' => 'middle',
                                'part'      => $value
                            );
                        }
                    }

                    if (!empty($or_field)) {
                        $filters['or_place_name'] = array(
                            'or' => array(
                                'ppl_client' => array(
                                    'custom' => '(ef.serialisable = 0 AND ' . $this->getPlacePositionSqlFilter('ppl') . ' AND ' . BimpTools::getSqlFilter('ppl.place_name', array('or_field' => $or_field)) . ')'
                                ),
                                'epl_client' => array(
                                    'custom' => '(ef.serialisable = 1 AND ' . $this->getPlacePositionSqlFilter('epl') . ' AND ' . BimpTools::getSqlFilter('ppl.place_name', array('or_field' => $or_field)) . ')'
                                )
                            )
                        );
                        $this->getPlacesJoins($joins);
                    }
                } else {
                    self::$currentFilters['place_id_client'] = array();
                }
                break;
        }
    }

    public function getPlacesJoins(&$joins = array())
    {
        if (!isset($joins['pp'])) {
            $joins['pp'] = array(
                'alias' => 'pp',
                'table' => 'be_package_product',
                'on'    => 'pp.id_product = a.rowid'
            );
        }
        if (!isset($joins['ppl'])) {
            $joins['ppl'] = array(
                'alias' => 'ppl',
                'table' => 'be_package_place',
                'on'    => 'ppl.id_package = pp.id_package'
            );
        }
        if (!isset($joins['e'])) {
            $joins['e'] = array(
                'alias' => 'e',
                'table' => 'be_equipment',
                'on'    => 'e.id_product = a.rowid'
            );
        }
        if (!isset($joins['epl'])) {
            $joins['epl'] = array(
                'alias' => 'epl',
                'table' => 'be_equipment_place',
                'on'    => 'epl.id_equipment = e.id'
            );
        }
    }

    public function getPlacePositionSqlFilter($alias)
    {
        if (isset(self::$currentFilters['place_position'])) {
            $or_field = array();

            foreach (self::$currentFilters['place_position'] as $value) {
                if (is_array($value)) {
                    if (isset($value['min']) || isset($value['max'])) {
                        if ($value['min'] !== '' && $value['max'] === '') {
                            $or_field[] = array(
                                'operator' => '>=',
                                'value'    => $value['min']
                            );
                        } elseif ($value['max'] !== '' && $value['min'] === '') {
                            $or_field[] = array(
                                'operator' => '<=',
                                'value'    => $value['max']
                            );
                        } else {
                            $or_field[] = array(
                                'min' => $value['min'],
                                'max' => $value['max']
                            );
                        }
                    }
                }
            }
            if (!empty($or_field)) {
                return BimpTools::getSqlFilter($alias . '.position', array('or_field' => $or_field));
            }
        }

        return $alias . '.position = 1';
    }

    public function getQtySql($id_product, $serialisable = 0)
    {
        if (!$serialisable) {
            $sql = 'SELECT SUM(pp.qty) as qty FROM ' . MAIN_DB_PREFIX . 'be_package_product pp';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_package_place place ON place.id_package = pp.id_package';
            $sql .= ' WHERE pp.id_product = ' . $id_product;
        } else {
            $sql = 'SELECT count(DISTINCT e.id) as qty FROM ' . MAIN_DB_PREFIX . 'be_equipment e';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_equipment_place place ON place.id_equipment = e.id';
            $sql .= ' WHERE e.id_product = ' . $id_product;
        }

        $sql .= ' AND ' . $this->getPlacePositionSqlFilter('place');

        if (isset(self::$currentFilters['place_type']) && !empty(self::$currentFilters['place_type'])) {
            $sql .= ' AND place.type IN (' . implode(',', self::$currentFilters['place_type']) . ')';
        }
        if (isset(self::$currentFilters['place_id_entrepot']) && !empty(self::$currentFilters['place_id_entrepot'])) {
            $sql .= ' AND place.id_entrepot IN (' . implode(',', self::$currentFilters['place_id_entrepot']) . ')';
        }
        if (isset(self::$currentFilters['place_id_user']) && !empty(self::$currentFilters['place_id_user'])) {
            $sql .= ' AND place.id_user IN (' . implode(',', self::$currentFilters['place_id_user']) . ')';
        }
        if (isset(self::$currentFilters['place_id_client']) && !empty(self::$currentFilters['place_id_client'])) {
            $sql .= ' AND place.id_client IN (' . implode(',', self::$currentFilters['place_id_client']) . ')';
        }
        if (isset(self::$currentFilters['place_name']) && !empty(self::$currentFilters['place_name'])) {
            $fl = true;
            $sql .= ' AND (';
            foreach (self::$currentFilters['place_name'] as $value) {
                if (!$fl) {
                    $sql .= ' OR ';
                } else {
                    $fl = false;
                }
                $sql .= 'place.place_name LIKE \'%' . $value . '%\'';
            }
            $sql .= ')';
        }

        return $sql;
    }

    public function getListExtraBtn()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            $onclick = '';
            $filters = array();
            $joins = array();
            
//            $this->getDetailListFilters($filters, $joins);
            
//            if ($this->isSerialisable()) {
//                $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');
//                $onclick = $equipment->getJsLoadModalList('immos', array(
//                    'title'         => 'Détail équipements',
//                    'extra_filters' => $filters,
//                    'extra_joins'   => $joins
//                ));
//            } else {
//                $pp = BimpObject::getInstance('bimpequipment', 'BE_PackageProduct');
//                $onclick = $pp->getJsLoadModalList('immos', array(
//                    'title'         => 'Détail équipements',
//                    'extra_filters' => $filters,
//                    'extra_joins'   => $joins
//                ));
//            }
//
//            $buttons[] = array(
//                'label'   => 'Détail',
//                'icon'    => 'fas_bars',
//                'onclick' => $onclick
//            );

            $this->module = 'bimpcore';
            $this->object_name = 'Bimp_Product';

            $buttons[] = array(
                'label'   => 'Vue rapide',
                'icon'    => 'fas_eye',
                'onclick' => $this->getJsLoadModalView('default')
            );

            $url = DOL_URL_ROOT . '/bimpcore/index.php?fc=product&id=' . $this->id;
            $buttons[] = array(
                'label'   => 'Afficher dans un nouvel onglet',
                'icon'    => 'fas_external-link-alt',
                'onclick' => 'window.open(\'' . $url . '\')'
            );

            $this->module = 'bimpequipment';
            $this->object_name = 'BE_ProductImmos';
        }

        return $buttons;
    }
    
    public function getDetailListFilters(&$filters = array(), &$joins = array())
    {
        if ($this->isLoaded()) {
            if ($this->isSerialisable()) {
                $joins['epl'] = array(
                    'table' => 'be_equipment_place',
                    'on' => 'epl.id_equipment = a.id',
                    'alias' => 'epl'
                );
            } else {
                
            }
        }
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

                // todo... 
                $pa = 0;

                $fields = array(
                    'qty'          => $qty,
                    'total_achats' => ($qty * $pa)
                );
            } else {
//                echo $this->getQtySql($this->id, 0); exit;
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
