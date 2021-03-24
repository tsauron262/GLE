<?php

class BimpCollection
{

    public $object = null;
    public $data = array();

    // Construction: 

    public static function getInstance($module, $object_name)
    {
        return BimpCache::getCollectionInstance($module, $object_name);
    }

    public function __construct($module, $object_name)
    {
        $this->object = BimpObject::getInstance($module, $object_name);
    }

    // Getters:

    public function isObjectInCache($id_object)
    {
        return BimpCache::isBimpObjectInCache($this->object->module, $this->object->object_name, $id_object);
    }

    public function getObjectInstance($id_object)
    {
        // On récupère en priorité l'instance du cache si elle existe: 
        if ($this->isObjectInCache($id_object)) {
            $instance = $this->getObjectCacheInstance($id_object);
            if (BimpObject::objectLoaded($instance)) {
                return $instance;
            }
        }

        if (!isset($this->data[$id_object])) {
            $this->addItems(array($id_object));
        }

        if (isset($this->data[$id_object])) {
            $this->object->setPreloadedData($id_object, $this->data[$id_object]['data']);
            return $this->object;
        }

        // On tente d'instancier directement (pour pallier une éventuelle erreur sql lors de addItems()
        if (!$this->isObjectInCache($id_object)) {
            $instance = $this->getObjectCacheInstance($id_object);

            if (BimpObject::objectLoaded($instance)) {
                return $instance;
            }
        }

        return null;
    }

    public function getObjectCacheInstance($id_object)
    {
        return BimpCache::getBimpObjectInstance($this->object->module, $this->object->object_name, $id_object);
    }

    // Gestion des données: 

    public function addItems($ids_list, $needs = array())
    {
        $needs = BimpTools::overrideArray(array(
                    'link' => 0,
                    'card' => ''
                        ), $needs);

        $errors = array();
        $ids = array();

        foreach ($ids_list as $id) {
            if ($this->isObjectInCache($id)) {
                continue;
            }

            if (!isset($this->data[$id])) {
                $ids[] = $id;
            }
        }

        if (!empty($ids)) {
            $bdb = BimpCache::getBdb();
            $primary = $this->object->getPrimary();
            $table = $this->object->getTable();
            $fields = array();
            $filters = array(
                $primary => array(
                    'in' => $ids
                )
            );

            $joins = array();
            $is_dol_object = $this->object->isDolObject();

            foreach ($this->object->params['fields'] as $field_name) {
                if ($this->object->field_exists($field_name)) {
                    continue;
                }
                $sqlKey = $this->object->getFieldSqlKey($field_name, 'a', null, $filters, $joins);

                if ($sqlKey) {
                    $fields[] = $sqlKey . ($is_dol_object && strpos($field_name, 'ef_') === 0 ? ' as ' . $field_name : '');
                }
            }

            $sql = BimpTools::getSqlSelect($fields);
            $sql .= BimpTools::getSqlFrom($table, $joins);
            $sql .= BimpTools::getSqlWhere($filters);
            $rows = $bdb->executeS($sql, 'array');

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    $id = $r[$primary];

                    $this->data[$id] = array(
                        'data' => array()
                    );

                    foreach ($r as $field_name => $value) {
                        if ($field_name == $primary) {
                            continue;
                        }

                        if ($this->object->field_exists($field_name)) {
                            $this->object->checkFieldValueType($field_name, $value);
                            $this->data[$id]['data'][$field_name] = $value;
                        }
                    }
                }
            } else {
                $errors[] = 'Echec requête SQL - ' . $bdb->err();
            }
        }

        if (count($errors)) {
            BimpCore::addlog('Erreur BimpCollection addItems()', Bimp_Log::BIMP_LOG_URGENT, 'bimpcore', $this->object, array(
                'Erreurs' => $errors
            ));
        }

        // Traitement des sous-enfants affichés dans les cards demandées: 
        if (!(int) BimpCore::getConf('bimpcore_mode_eco', 0)) {
            $cards = array();
            if ($needs['card']) {
                $cards[] = $needs['card'];
            }

            if ($needs['link']) {
                $link_card = BimpTools::getArrayValueFromPath($this->object->params, 'nom_url/card', '');

                if ($link_card && !in_array($link_card, $cards)) {
                    $cards[] = $link_card;
                }
            }

            if (count($cards)) {
                $linked_objects_fields = array();
                foreach ($cards as $card_name) {
                    if ($this->object->config->isDefined('cards/' . $card_name . '/fields')) {
                        $card_fields = $this->object->config->getParams('cards/' . $card_name . '/fields');

                        foreach ($card_fields as $card_field) {
                            if (isset($card_field['field']) && (string) $card_field['field'] && !in_array($card_field['field'], $linked_objects_fields)) {
                                $field_type = $this->object->getConf('fields/' . $card_field['field'] . '/type', 'string');
                                if (in_array($field_type, array('id_object', 'id_parent'))) {
                                    $linked_objects_fields[] = $card_field['field'];
                                }
                            }
                        }
                    }
                }

                if (!empty($linked_objects_fields)) {
                    // Ne surtout pas fournir 'needs' dans $linked_objects_fields, pour éviter une récursivité trop grande voire infinie. 
                    self::fetchObjectLinkedObjects($this->object, $ids, $linked_objects_fields);
                }
            }
        }
        
        return $errors;
    }

    // Méthodes de traitement des items: 

    public function getLink($id_object, $params = array())
    {
        if (!(int) $id_object) {
            return '';
        }

        if (empty($params) && isset($this->data[$id_object]['link'])) {
            return $this->data[$id_object]['link'];
        }

        $instance = $this->getObjectInstance($id_object);

        if (BimpObject::objectLoaded($instance)) {
            $link = $instance->getLink($params);

            if ($link) {
                if (empty($params) && isset($this->data[$id_object])) {
                    $this->data[$id_object]['link'] = $link;
                }
                return $link;
            }
        }

        return '';
    }

    public function getName($id_object, $withGeneric = true)
    {
        if (!(int) $id_object) {
            return '';
        }

        $name_key = 'name' . ($withGeneric ? '_wg' : '');
        if (isset($this->data[$id_object][$name_key])) {
            return $this->data[$id_object][$name_key];
        }

        $instance = $this->getObjectInstance($id_object);

        if (BimpObject::objectLoaded($instance)) {
            $name = $instance->getName($withGeneric);
            if (isset($this->data[$id_object])) {
                $this->data[$id_object][$name_key] = $name;
            }
            return $name;
        }

        return '';
    }

    public function getRef($id_object, $withGeneric = true)
    {
        if (!(int) $id_object) {
            return '';
        }

        $ref_key = 'ref' . ($withGeneric ? '_wg' : '');
        if (isset($this->data[$id_object][$ref_key])) {
            return $this->data[$id_object][$ref_key];
        }

        $instance = $this->getObjectInstance($id_object);

        if (BimpObject::objectLoaded($instance)) {
            $ref = $instance->getRef($withGeneric);
            if (isset($this->data[$id_object])) {
                $this->data[$id_object][$ref_key] = $ref;
            }
            return $ref;
        }

        return '';
    }

    public function getNomExtraIcons($id_object)
    {
        if (!(int) $id_object) {
            return '';
        }

        if (isset($this->data[$id_object]['nom_extra_icons'])) {
            return $this->data[$id_object]['nom_extra_icons'];
        }

        if (!method_exists($this->object, 'getNomExtraIcons')) {
            return '';
        }

        $instance = $this->getObjectInstance($id_object);

        if (BimpObject::objectLoaded($instance) && method_exists($instance, 'getNomExtraIcons')) {
            $icons = $instance->getNomExtraIcons();

            if (isset($this->data[$id_object])) {
                $this->data[$id_object]['nom_extra_icons'] = $icons;
            }

            return $icons;
        }

        return '';
    }

    public function getCardHtml($id_object, $card_name = 'default', $with_buttons = null)
    {
        if (!(int) $id_object) {
            return '';
        }

        $cache_key = 'bimp_object_' . $this->object->module . '_' . $this->object->object_name . '_' . $id_object . '_popover_card_' . $card_name;

        if ($with_buttons) {
            $cache_key .= '_wb';
        }

        if (BimpCache::cacheExists($cache_key)) {
            return BimpCache::$cache[$cache_key];
        }

        $instance = $this->getObjectInstance($id_object);

        if (BimpObject::objectLoaded($instance)) {
            return BimpCache::getBimpObjectCardHtml($instance, $card_name, $with_buttons);
        }

        return '';
    }

    // Méthodes statiques:

    public static function fetchObjectLinkedObjects($object, $list_ids, $fields = null)
    {
        $errors = array();

        if (!is_array($list_ids) || empty($list_ids)) {
            $errors[] = 'Liste d\'IDs vide';
        } elseif (!is_a($object, 'BimpObject')) {
            $errors[] = 'Objet invalide';
        } else {
            if (is_null($fields)) {
                $fields = $object->params['fields'];
            }

            if (!empty($fields)) {
                $children = array();

                // Récupération des objets liés pour les champs indiqués: 
                foreach ($fields as $field) {
                    $field_name = '';
                    $needs = array();

                    if (is_string($field)) {
                        $field_name = $field;
                    } elseif (isset($field['name'])) {
                        $field_name = $field['name'];
                        $needs = BimpTools::getArrayValueFromPath($field, 'needs', array());
                    }

                    if ($field_name && $object->field_exists($field_name) && !isset($children[$field_name])) {
                        $field_type = $object->getConf('fields/' . $field_name . '/type', 'string');
                        if (in_array($field_type, array('id_object', 'id_parent'))) {
                            $child_name = $object->getConf('fields/' . $field_name . '/object', '');

                            if ($child_name) {
                                $child_module = $object->getConf('objects/' . $child_name . '/instance/bimp_object/module', '');
                                $child_object_name = $object->getConf('objects/' . $child_name . '/instance/bimp_object/name', '');

                                if ($child_module && $child_name) {
                                    $children[$field_name] = array(
                                        'module'      => $child_module,
                                        'object_name' => $child_object_name,
                                        'needs'       => $needs,
                                        'list'        => array()
                                    );
                                }
                            }
                        }
                    }
                }

                if (!empty($children)) {
                    // Récupération des IDs pour chaque objet lié:
                    $primary = $object->getPrimary();
                    $return_fields = array();
                    $filters = array(
                        $primary => array(
                            'in' => $list_ids
                        )
                    );
                    $joins = array();

                    foreach ($children as $field_name => $child_data) {
                        $sqlKey = $object->getFieldSqlKey($field_name, 'a', null, $filters, $joins);

                        if ($sqlKey) {
                            $return_fields[] = $sqlKey . (strpos($field_name, 'ef_') === 0 ? ' as ' . $field_name : '');
                        }
                    }

                    if (!empty($return_fields)) {
                        $sql = BimpTools::getSqlSelect($return_fields);
                        $sql .= BimpTools::getSqlFrom($object->getTable(), $joins);
                        $sql .= BimpTools::getSqlWhere($filters);

                        $rows = BimpCache::getBdb()->executeS($sql, 'array');

                        if (is_array($rows)) {
                            foreach ($rows as $r) {
                                foreach ($children as $field_name => $child_data) {
                                    if (isset($r[$field_name]) && (int) $r[$field_name] && !in_array($r[$field], $child_data['list'])) {
                                        $children[$field_name]['list'][] = (int) $r[$field_name];
                                    }
                                }
                            }
                        }
                    }

                    // Ajout des IDs aux collections pour chaque objet lié: 
                    foreach ($children as $field_name => $child_data) {
                        if (!empty($child_data['list'])) {
                            $coll = self::getInstance($child_data['module'], $child_data['object_name']);
                            $coll->addItems($child_data['list'], BimpTools::getArrayValueFromPath($child_data, 'needs', array()));
                        }
                    }
                }
            }
        }

        return $errors;
    }
}
