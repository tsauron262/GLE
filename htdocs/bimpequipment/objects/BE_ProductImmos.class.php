<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/Bimp_Product.class.php';

class BE_ProductImmos extends Bimp_Product
{

//    public static $place_types = null;
    public static $currentFilters = array();
    public static $places_positions_filters_set = array();
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
                    'cust_noserial'       => array(
                        'custom' => '((a___ef.serialisable != 1 || a___ef.serialisable is NULL) AND a.rowid IN (SELECT DISTINCT pp.id_product FROM ' . MAIN_DB_PREFIX . 'be_package_product pp WHERE pp.id_product = a.rowid))'
                    ),
                    'a___ef.serialisable' => 1
                )
            )
        );

        return $filters;
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, $main_alias = 'a', &$errors = array(), $excluded = false)
    {
        // Tous les boutons Exclure sont désactivés:

        switch ($field_name) {
            case 'place_position':
                if ($main_alias === 'a' && is_array($values) && !empty($values)) {
                    self::$currentFilters['place_position'] = $values;
                }

                $ef_alias = $main_alias . '___ef';

                if (!isset($joins[$ef_alias])) {
                    $joins[$ef_alias] = array(
                        'alias' => $ef_alias,
                        'table' => 'product_extrafields',
                        'on'    => $main_alias . '.rowid = ' . $ef_alias . '.fk_object'
                    );
                }

                $filters[$main_alias . '___or_place_position'] = array(
                    'or' => array(
                        'ppl_position' => array(
                            'custom' => '((' . $ef_alias . '.serialisable = 0 || ' . $ef_alias . '.serialisable IS NULL) AND ' . $this->getPlacePositionSqlFilter($main_alias . '___ppl') . ')'
                        ),
                        'epl_position' => array(
                            'custom' => '(' . $ef_alias . '.serialisable = 1 AND ' . $this->getPlacePositionSqlFilter($main_alias . '___epl') . ')'
                        )
                    )
                );

                if ($main_alias === 'a') {
                    self::$places_positions_filters_set['ppl'] = 1;
                    self::$places_positions_filters_set['epl'] = 1;
                }

                $this->getPlacesJoins($joins, $main_alias);
                break;

            case 'place_type':
                if (is_array($values) && !empty($values)) {
                    if ($main_alias === 'a') {
                        self::$currentFilters['place_type'] = $values;
                    }

                    $ef_alias = $main_alias . '___ef';
                    if (!isset($joins[$ef_alias])) {
                        $joins[$ef_alias] = array(
                            'alias' => $ef_alias,
                            'table' => 'product_extrafields',
                            'on'    => $main_alias . '.rowid = ' . $ef_alias . '.fk_object'
                        );
                    }

                    $filters[$main_alias . '___or_place_type'] = array(
                        'or' => array(
                            'ppl_type' => array(
                                'custom' => '((' . $ef_alias . '.serialisable = 0 || ' . $ef_alias . '.serialisable IS NULL) AND ' . $this->getPlacePositionSqlFilter($main_alias . '___ppl') . ' AND ' . $main_alias . '___ppl.type IN (' . implode(',', $values) . '))'
                            ),
                            'epl_type' => array(
                                'custom' => '(' . $ef_alias . '.serialisable = 1 AND ' . $this->getPlacePositionSqlFilter($main_alias . '___epl') . ' AND ' . $main_alias . '___epl.type IN (' . implode(',', $values) . '))'
                            )
                        )
                    );
                    $this->getPlacesJoins($joins, $main_alias);
                } elseif ($main_alias === 'a') {
                    self::$currentFilters['place_type'] = array();
                }
                break;

            case 'place_id_entrepot':
                if (is_array($values) && !empty($values)) {
                    $ef_alias = $main_alias . '___ef';
                    if (!isset($joins[$ef_alias])) {
                        $joins[$ef_alias] = array(
                            'alias' => $ef_alias,
                            'table' => 'product_extrafields',
                            'on'    => $main_alias . '.rowid = ' . $ef_alias . '.fk_object'
                        );
                    }

                    if ($main_alias === 'a') {
                        self::$currentFilters['place_id_entrepot'] = $values;
                    }

                    $filters[$main_alias . '___or_place_entrepot'] = array(
                        'or' => array(
                            'ppl_entrepot' => array(
                                'custom' => '((' . $ef_alias . '.serialisable = 0 || ' . $ef_alias . '.serialisable IS NULL) AND ' . $this->getPlacePositionSqlFilter($main_alias . '___ppl') . ' AND ' . $main_alias . '___ppl.id_entrepot IN (' . implode(',', $values) . '))'
                            ),
                            'epl_entrepot' => array(
                                'custom' => '(' . $ef_alias . '.serialisable = 1 AND ' . $this->getPlacePositionSqlFilter($main_alias . '___epl') . ' AND ' . $main_alias . '___epl.id_entrepot IN (' . implode(',', $values) . '))'
                            )
                        )
                    );
                    $this->getPlacesJoins($joins, $main_alias);
                } elseif ($main_alias === 'a') {
                    self::$currentFilters['place_id_entrepot'] = array();
                }
                break;

            case 'place_id_user':
                if (is_array($values) && !empty($values)) {
                    $ef_alias = $main_alias . '___ef';
                    if (!isset($joins[$ef_alias])) {
                        $joins[$ef_alias] = array(
                            'alias' => $ef_alias,
                            'table' => 'product_extrafields',
                            'on'    => $main_alias . '.rowid = ' . $ef_alias . '.fk_object'
                        );
                    }

                    if ($main_alias === 'a') {
                        self::$currentFilters['place_id_user'] = $values;
                    }

                    $filters[$main_alias . '___or_place_user'] = array(
                        'or' => array(
                            'ppl_user' => array(
                                'custom' => '((' . $ef_alias . '.serialisable = 0 || ' . $ef_alias . '.serialisable IS NULL) AND ' . $this->getPlacePositionSqlFilter($main_alias . '___ppl') . ' AND ' . $main_alias . '___ppl.id_user IN (' . implode(',', $values) . '))'
                            ),
                            'epl_user' => array(
                                'custom' => '(' . $ef_alias . '.serialisable = 1 AND ' . $this->getPlacePositionSqlFilter($main_alias . '___epl') . ' AND ' . $main_alias . '___epl.id_user IN (' . implode(',', $values) . '))'
                            )
                        )
                    );
                    $this->getPlacesJoins($joins, $main_alias);
                } elseif ($main_alias === 'a') {
                    self::$currentFilters['place_id_user'] = array();
                }
                break;

            case 'place_id_client':
                if (is_array($values) && !empty($values)) {
                    if ($main_alias === 'a') {
                        self::$currentFilters['place_id_client'] = $values;
                    }

                    $ef_alias = $main_alias . '___ef';
                    if (!isset($joins[$ef_alias])) {
                        $joins[$ef_alias] = array(
                            'alias' => $ef_alias,
                            'table' => 'product_extrafields',
                            'on'    => $main_alias . '.rowid = ' . $ef_alias . '.fk_object'
                        );
                    }

                    $filters[$main_alias . '___or_place_client'] = array(
                        'or' => array(
                            'ppl_client' => array(
                                'custom' => '((' . $ef_alias . '.serialisable = 0 || ' . $ef_alias . '.serialisable IS NULL) AND ' . $this->getPlacePositionSqlFilter($main_alias . '___ppl') . ' AND ' . $main_alias . '___ppl.id_client IN (' . implode(',', $values) . '))'
                            ),
                            'epl_client' => array(
                                'custom' => '(' . $ef_alias . '.serialisable = 1 AND ' . $this->getPlacePositionSqlFilter($main_alias . '___epl') . ' AND ' . $main_alias . '___epl.id_client IN (' . implode(',', $values) . '))'
                            )
                        )
                    );
                    $this->getPlacesJoins($joins, $main_alias);
                } elseif ($main_alias === 'a') {
                    self::$currentFilters['place_id_client'] = array();
                }
                break;

            case 'place_name':
                if (is_array($values) && !empty($values)) {
                    if ($main_alias === 'a') {
                        self::$currentFilters['place_name'] = $values;
                    }

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
                        $ef_alias = $main_alias . '___ef';
                        if (!isset($joins[$ef_alias])) {
                            $joins[$ef_alias] = array(
                                'alias' => $ef_alias,
                                'table' => 'product_extrafields',
                                'on'    => $main_alias . '.rowid = ' . $ef_alias . '.fk_object'
                            );
                        }

                        $filters[$main_alias . '___or_place_name'] = array(
                            'or' => array(
                                'ppl_client' => array(
                                    'custom' => '((' . $ef_alias . '.serialisable = 0 || ' . $ef_alias . '.serialisable IS NULL) AND ' . $this->getPlacePositionSqlFilter($main_alias . '___ppl') . ' AND ' . BimpTools::getSqlFilter($main_alias . '___ppl.place_name', array('or_field' => $or_field)) . ')'
                                ),
                                'epl_client' => array(
                                    'custom' => '(' . $ef_alias . '.serialisable = 1 AND ' . $this->getPlacePositionSqlFilter($main_alias . '___epl') . ' AND ' . BimpTools::getSqlFilter($main_alias . '___epl.place_name', array('or_field' => $or_field)) . ')'
                                )
                            )
                        );
                        $this->getPlacesJoins($joins, $main_alias);
                    }
                } elseif ($main_alias === 'a') {
                    self::$currentFilters['place_id_client'] = array();
                }
                break;

            case 'place_date':
                if (is_array($values) && !empty($values)) {
                    if ($main_alias === 'a') {
                        self::$currentFilters['place_date'] = $values;
                    }

                    $or_field = array();
                    foreach ($values as $value) {
                        $or_field[] = BC_Filter::getRangeSqlFilter($value, $errors);
                    }

                    if (!empty($or_field)) {
                        $ef_alias = $main_alias . '___ef';
                        if (!isset($joins[$ef_alias])) {
                            $joins[$ef_alias] = array(
                                'alias' => $ef_alias,
                                'table' => 'product_extrafields',
                                'on'    => $main_alias . '.rowid = ' . $ef_alias . '.fk_object'
                            );
                        }

                        $filters[$main_alias . '___or_place_date'] = array(
                            'or' => array(
                                'ppl_client' => array(
                                    'custom' => '((' . $ef_alias . '.serialisable = 0 || ' . $ef_alias . '.serialisable IS NULL) AND ' . $this->getPlacePositionSqlFilter($main_alias . '___ppl') . ' AND ' . BimpTools::getSqlFilter($main_alias . '___ppl.date', array('or_field' => $or_field)) . ')'
                                ),
                                'epl_client' => array(
                                    'custom' => '(' . $ef_alias . '.serialisable = 1 AND ' . $this->getPlacePositionSqlFilter($main_alias . '___epl') . ' AND ' . BimpTools::getSqlFilter($main_alias . '___epl.date', array('or_field' => $or_field)) . ')'
                                )
                            )
                        );
                        $this->getPlacesJoins($joins, $main_alias);
                    }
                } elseif ($main_alias === 'a') {
                    self::$currentFilters['place_date'] = array();
                }
                break;

            case 'place_date_end':
                if (is_array($values) && !empty($values)) {
                    if ($main_alias === 'a') {
                        self::$currentFilters['place_date_end'] = $values;
                    }

                    $or_field = array();
                    foreach ($values as $value) {
                        $or_field[] = BC_Filter::getRangeSqlFilter($value, $errors);
                    }

                    if (!empty($or_field)) {
                        $ef_alias = $main_alias . '___ef';
                        if (!isset($joins[$ef_alias])) {
                            $joins[$ef_alias] = array(
                                'alias' => $ef_alias,
                                'table' => 'product_extrafields',
                                'on'    => $main_alias . '.rowid = ' . $ef_alias . '.fk_object'
                            );
                        }

                        $this->getPlacesJoins($joins, $main_alias);
                        $this->getNextPlacesJoins($joins, $main_alias);

                        $filters[$main_alias . '___next_place_position'] = array(
                            'or' => array(
                                'ppl_next_position' => array(
                                    'custom' => '((' . $ef_alias . '.serialisable = 0 || ' . $ef_alias . '.serialisable IS NULL) AND ' . $this->getPlacePositionSqlFilter($main_alias . '___ppl') . ' AND ' . $main_alias . '___ppl_next.position = (' . $main_alias . '___ppl.position - 1))'
                                ),
                                'epl_next_position' => array(
                                    'custom' => '(' . $ef_alias . '.serialisable = 1 AND ' . $this->getPlacePositionSqlFilter($main_alias . '___epl') . ' AND ' . $main_alias . '___epl_next.position = (' . $main_alias . '___epl.position - 1))'
                                )
                            )
                        );

                        $filters[$main_alias . '___or_next_place_date'] = array(
                            'or' => array(
                                'ppl_next_date_end' => array(
                                    'custom' => '((' . $ef_alias . '.serialisable = 0 || ' . $ef_alias . '.serialisable IS NULL) AND ' . $this->getPlacePositionSqlFilter($main_alias . '___ppl') . ' AND ' . BimpTools::getSqlFilter($main_alias . '___ppl_next.date', array('or_field' => $or_field)) . ')'
                                ),
                                'epl_next_date_end' => array(
                                    'custom' => '(' . $ef_alias . '.serialisable = 1 AND ' . $this->getPlacePositionSqlFilter($main_alias . '___epl') . ' AND ' . BimpTools::getSqlFilter($main_alias . '___epl_next.date', array('or_field' => $or_field)) . ')'
                                )
                            )
                        );
                    }
                } elseif ($main_alias === 'a') {
                    self::$currentFilters['place_date_end'] = array();
                }
                break;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $main_alias, $errors, $excluded);
    }

    public function getPlacesJoins(&$joins = array(), $main_alias = 'a')
    {
        $pp_alias = $main_alias . '___pp';
        if (!isset($joins[$pp_alias])) {
            $joins[$pp_alias] = array(
                'alias' => $pp_alias,
                'table' => 'be_package_product',
                'on'    => $pp_alias . '.id_product = ' . $main_alias . '.rowid'
            );
        }

        $ppl_alias = $main_alias . '___ppl';
        if (!isset($joins[$ppl_alias])) {
            $joins[$ppl_alias] = array(
                'alias' => $ppl_alias,
                'table' => 'be_package_place',
                'on'    => $ppl_alias . '.id_package = ' . $pp_alias . '.id_package'
            );
        }

        $e_alias = $main_alias . '___e';
        if (!isset($joins[$e_alias])) {
            $joins[$e_alias] = array(
                'alias' => $e_alias,
                'table' => 'be_equipment',
                'on'    => $e_alias . '.id_product = ' . $main_alias . '.rowid'
            );
        }

        $epl_alias = $main_alias . '___epl';
        if (!isset($joins[$epl_alias])) {
            $joins[$epl_alias] = array(
                'alias' => $epl_alias,
                'table' => 'be_equipment_place',
                'on'    => $epl_alias . '.id_equipment = ' . $e_alias . '.id'
            );
        }
    }

    public function getNextPlacesJoins(&$joins = array(), $main_alias = 'a')
    {
        $pp_alias = $main_alias . '___pp';
        if (!isset($joins[$pp_alias])) {
            $joins[$pp_alias] = array(
                'alias' => $pp_alias,
                'table' => 'be_package_product',
                'on'    => $pp_alias . '.id_product = ' . $main_alias . '.rowid'
            );
        }

        $ppl_next_alias = $main_alias . '___ppl_next';
        if (!isset($joins[$ppl_next_alias])) {
            $joins[$ppl_next_alias] = array(
                'alias' => $ppl_next_alias,
                'table' => 'be_package_place',
                'on'    => $ppl_next_alias . '.id_package = ' . $pp_alias . '.id_package'
            );
        }

        $e_alias = $main_alias . '___e';
        if (!isset($joins[$e_alias])) {
            $joins[$e_alias] = array(
                'alias' => $e_alias,
                'table' => 'be_equipment',
                'on'    => $e_alias . '.id_product = ' . $main_alias . '.rowid'
            );
        }

        $epl_next_alias = $main_alias . '___epl_next';
        if (!isset($joins[$epl_next_alias])) {
            $joins[$epl_next_alias] = array(
                'alias' => $epl_next_alias,
                'table' => 'be_equipment_place',
                'on'    => $epl_next_alias . '.id_equipment = ' . $e_alias . '.id'
            );
        }
    }

    public function getPlacePositionSqlFilter($alias)
    {
        if (isset(self::$places_positions_filters_set[$alias]) && (int) self::$places_positions_filters_set[$alias]) {
            return '1';
        }

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

        return '1';
        //return $alias . '.position = 1';
    }

    public function getPlaceFiltersSql($alias)
    {
        $sql = '';

        $sql .= $this->getPlacePositionSqlFilter($alias);

        if (isset(self::$currentFilters['place_type']) && !empty(self::$currentFilters['place_type'])) {
            $sql .= ' AND ' . $alias . '.type IN (' . implode(',', self::$currentFilters['place_type']) . ')';
        }
        if (isset(self::$currentFilters['place_id_entrepot']) && !empty(self::$currentFilters['place_id_entrepot'])) {
            $sql .= ' AND ' . $alias . '.id_entrepot IN (' . implode(',', self::$currentFilters['place_id_entrepot']) . ')';
        }
        if (isset(self::$currentFilters['place_id_user']) && !empty(self::$currentFilters['place_id_user'])) {
            $sql .= ' AND ' . $alias . '.id_user IN (' . implode(',', self::$currentFilters['place_id_user']) . ')';
        }
        if (isset(self::$currentFilters['place_id_client']) && !empty(self::$currentFilters['place_id_client'])) {
            $sql .= ' AND ' . $alias . '.id_client IN (' . implode(',', self::$currentFilters['place_id_client']) . ')';
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
                $sql .= $alias . '.place_name LIKE \'%' . $value . '%\'';
            }
            $sql .= ')';
        }

        foreach (array(
    'place_date',
    'place_date_end'
        ) as $filters_key) {
            if (isset(self::$currentFilters[$filters_key]) && !empty(self::$currentFilters[$filters_key])) {
                $suffixe = ($filters_key === 'place_date_end' ? '_next' : '');

                $fl = true;
                $sql .= ' AND (';
                foreach (self::$currentFilters[$filters_key] as $value) {
                    if (!is_array($value) || ((!isset($value['min']) || $value['min'] === '') && (!isset($value['max']) || $value['max'] === ''))) {
                        continue;
                    }

                    if (preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $value['max'])) {
                        $value['max'] .= ' 23:59:59';
                    }

                    if (!$fl) {
                        $sql .= ' OR ';
                    } else {
                        $fl = false;
                    }

                    if (isset($value['min']) && $value['min'] !== '' && isset($value['max']) && $value['max'] !== '') {
                        $sql .= $alias . $suffixe . '.date BETWEEN \'' . $value['min'] . '\' AND \'' . $value['max'] . '\'';
                    } elseif (isset($value['min']) && $value['min'] !== '') {
                        $sql .= $alias . $suffixe . '.date >= \'' . $value['min'] . '\'';
                    } elseif (isset($value['max']) && $value['max'] !== '') {
                        $sql .= $alias . $suffixe . '.date <= \'' . $value['max'] . '\'';
                    }
                }
                $sql .= ')';
            }
        }

        return $sql;
    }

    public function getPlaceFilters($alias, &$filters = array())
    {
        if (isset(self::$currentFilters['place_position'])) {
            $or_field = array();
            foreach (self::$currentFilters['place_position'] as $value) {
                $or_field[] = $value;
            }
            if (!empty($or_field)) {
                $filters[$alias . '.position'] = array(
                    'or_field' => $or_field
                );
            }
        } else {
//            $filters[$alias . '.position'] = 1;
        }

        if (isset(self::$currentFilters['place_type']) && !empty(self::$currentFilters['place_type'])) {
            $filters[$alias . '.type'] = array(
                'in' => self::$currentFilters['place_type']
            );
        }
        if (isset(self::$currentFilters['place_id_entrepot']) && !empty(self::$currentFilters['place_id_entrepot'])) {
            $filters[$alias . '.id_entrepot'] = array(
                'in' => self::$currentFilters['place_id_entrepot']
            );
        }
        if (isset(self::$currentFilters['place_id_user']) && !empty(self::$currentFilters['place_id_user'])) {
            $filters[$alias . '.id_user'] = array(
                'in' => self::$currentFilters['place_id_user']
            );
        }
        if (isset(self::$currentFilters['place_id_client']) && !empty(self::$currentFilters['place_id_client'])) {
            $filters[$alias . '.id_client'] = array(
                'in' => self::$currentFilters['place_id_client']
            );
        }
        if (isset(self::$currentFilters['place_name']) && !empty(self::$currentFilters['place_name'])) {
            $or_field = array();
            foreach (self::$currentFilters['place_name'] as $value) {
                $or_field[] = array(
                    'part_type' => 'middle',
                    'part'      => $value
                );
            }
            if (!empty($or_field)) {
                $filters[$alias . '.place_name'] = array(
                    'or_field' => $or_field
                );
            }
        }
        if (isset(self::$currentFilters['place_date']) && !empty(self::$currentFilters['place_date'])) {
            $or_field = array();
            foreach (self::$currentFilters['place_date'] as $value) {
                $or_field[] = $value;
            }
            if (!empty($or_field)) {
                $filters[$alias . '.date'] = array(
                    'or_field' => $or_field
                );
            }
        }
        if (isset(self::$currentFilters['place_date_end']) && !empty(self::$currentFilters['place_date_end'])) {
            $or_field = array();
            foreach (self::$currentFilters['place_date_end'] as $value) {
                $or_field[] = $value;
            }
            if (!empty($or_field)) {
                $filters[$alias . '_next.date'] = array(
                    'or_field' => $or_field
                );
            }
        }
    }

    public function getQtySql($id_product, $serialisable = 0)
    {
        if (!$serialisable) {
            $sql = 'SELECT SUM(pp.qty) as qty FROM ' . MAIN_DB_PREFIX . 'be_package_product pp';
            if (!empty(self::$currentFilters)) {
                $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_package_place place ON place.id_package = pp.id_package';
            }
            if (isset(self::$currentFilters['place_date_end']) && !empty(self::$currentFilters['place_date_end'])) {
                $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_package_place place_next ON place_next.id_package = pp.id_package';
            }
            $sql .= ' WHERE pp.id_product = ' . $id_product;
            if (isset(self::$currentFilters['place_date_end']) && !empty(self::$currentFilters['place_date_end'])) {
                $sql .= ' AND place_next.position = (place.position - 1)';
            }
        } else {
            $sql = 'SELECT count(DISTINCT e.id) as qty FROM ' . MAIN_DB_PREFIX . 'be_equipment e';
            if (!empty(self::$currentFilters)) {
                $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_equipment_place place ON place.id_equipment = e.id';
            }
            if (isset(self::$currentFilters['place_date_end']) && !empty(self::$currentFilters['place_date_end'])) {
                $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_equipment_place place_next ON place_next.id_equipment = e.id';
            }
            $sql .= ' WHERE e.id_product = ' . $id_product;
            if (isset(self::$currentFilters['place_date_end']) && !empty(self::$currentFilters['place_date_end'])) {
                $sql .= ' AND place_next.position = (place.position - 1)';
            }
        }

        if (!empty(self::$currentFilters)) {
            $sql .= ' AND ' . $this->getPlaceFiltersSql('place');
        }

        return $sql;
    }

    public function getDetailListFilters(&$filters = array(), &$joins = array())
    {
        if ($this->isLoaded()) {
            $filters['a.id_product'] = (int) $this->id;

            if (!empty(self::$currentFilters)) {
                if ($this->isSerialisable()) {
                    $joins['place'] = array(
                        'table' => 'be_equipment_place',
                        'on'    => 'place.id_equipment = a.id',
                        'alias' => 'place'
                    );

                    if (isset(self::$currentFilters['place_date_end']) && !empty(self::$currentFilters['place_date_end'])) {
                        $joins['place_next'] = array(
                            'table' => 'be_equipment_place',
                            'on'    => 'place_next.id_equipment = a.id',
                            'alias' => 'place_next'
                        );

                        $filters['next_place_position'] = array(
                            'custom' => 'place_next.position = (place.position - 1)'
                        );
                    }
                } else {
                    $joins['place'] = array(
                        'table' => 'be_package_place',
                        'on'    => 'place.id_package = a.id_package',
                        'alias' => 'place'
                    );
                    if (isset(self::$currentFilters['place_date_end']) && !empty(self::$currentFilters['place_date_end'])) {
                        $joins['place_next'] = array(
                            'table' => 'be_package_place',
                            'on'    => 'place_next.id_package = a.id_package',
                            'alias' => 'place_next'
                        );

                        $filters['next_place_position'] = array(
                            'custom' => 'place_next.position = (place.position - 1)'
                        );
                    }
                }

                $this->getPlaceFilters('place', $filters);
            }
        }
    }

    public function getListExtraBtn()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            $onclick = '';
            $filters = array();
            $joins = array();

            $this->getDetailListFilters($filters, $joins);

            if ($this->isSerialisable()) {
                $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');
                $onclick = $equipment->getJsLoadModalList('immos', array(
                    'title'         => 'Détail équipements',
                    'extra_filters' => $filters,
                    'extra_joins'   => $joins
                ));
            } else {
                $pp = BimpObject::getInstance('bimpequipment', 'BE_PackageProduct');
                $onclick = $pp->getJsLoadModalList('immos', array(
                    'title'         => 'Détail équipements',
                    'extra_filters' => $filters,
                    'extra_joins'   => $joins
                ));
            }

            $buttons[] = array(
                'label'   => 'Détail',
                'icon'    => 'fas_bars',
                'onclick' => $onclick
            );

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

                $total_achats = 0;

                $sql = 'SELECT SUM(e.prix_achat) as total_achats FROM ' . MAIN_DB_PREFIX . 'be_equipment e ';
                if (!empty(self::$currentFilters)) {
                    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_equipment_place place ON place.id_equipment = e.id';
                }
                if (isset(self::$currentFilters['place_date_end']) && !empty(self::$currentFilters['place_date_end'])) {
                    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_equipment_place place_next ON place_next.id_equipment = e.id';
                }

                $sql .= ' WHERE e.id_product = ' . (int) $this->id;
                $sql .= ' AND e.prix_achat != 0';

                if (isset(self::$currentFilters['place_date_end']) && !empty(self::$currentFilters['place_date_end'])) {
                    $sql .= ' AND place_next.position = (place.position - 1)';
                }

                if (!empty(self::$currentFilters)) {
                    $sql .= ' AND ' . $this->getPlaceFiltersSql('place');
                }

                $result = $this->db->executeS($sql, 'array');

                if (isset($result[0]['total_achats'])) {
                    $total_achats = (float) $result[0]['total_achats'];
                }

                $sql = 'SELECT COUNT(e.id) as qty FROM ' . MAIN_DB_PREFIX . 'be_equipment e ';
                if (!empty(self::$currentFilters)) {
                    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_equipment_place place ON place.id_equipment = e.id';
                }
                if (isset(self::$currentFilters['place_date_end']) && !empty(self::$currentFilters['place_date_end'])) {
                    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_equipment_place place_next ON place_next.id_equipment = e.id';
                }

                $sql .= ' WHERE e.id_product = ' . (int) $this->id;
                $sql .= ' AND e.prix_achat = 0';

                if (isset(self::$currentFilters['place_date_end']) && !empty(self::$currentFilters['place_date_end'])) {
                    $sql .= ' AND place_next.position = (place.position - 1)';
                }

                if (!empty(self::$currentFilters)) {
                    $sql .= ' AND ' . $this->getPlaceFiltersSql('place');
                }

                $result = $this->db->executeS($sql, 'array');

                $qty_defpa = 0;

                if (isset($result[0]['qty'])) {
                    $qty_defpa = (int) $result[0]['qty'];
                }

                if ($qty_defpa > 0) {
                    $pa_ht = (float) $this->getCurrentPaHt(null, true);
                    $total_achats += ($pa_ht * $qty_defpa);
                }

                $fields = array(
                    'qty'          => $qty,
                    'total_achats' => $total_achats
                );
            } else {
                $sql = $this->getQtySql($this->id, 0);
                $result = $this->db->executeS($sql, 'array');

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
}
