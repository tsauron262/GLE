<?php

class BimpCorrectif extends BimpObject
{

    // Méthodes statiques: 

    public static function getValue($object, $field)
    {
        $value = 0;

        if (is_object($object) && BimpObject::objectLoaded($object)) {
            $filters = self::getObjectFilters($object);

            if (!empty($filters)) {
                $filters['field'] = $field;

                $instance = BimpCache::findBimpObjectInstance('bimpcore', 'BimpCorrectif', $filters, true);

                if (BimpObject::objectLoaded($instance)) {
                    $value = $instance->getData('value');
                }
            }
        }

        return $value;
    }

    public static function setValue($object, $field, $value, $cumulate_if_exists = false)
    {
        $errors = array();
        $value = (float) $value;

        if (!is_object($object) || !BimpObject::objectLoaded($object)) {
            $errors[] = 'Objet invalidee';
        } else {
            $filters = self::getObjectFilters($object);

            if (empty($filters)) {
                $errors[] = 'Object invalide';
            } else {
                $filters['field'] = $field;

                $instance = BimpCache::findBimpObjectInstance('bimpcore', 'BimpCorrectif', $filters, true);

                if (BimpObject::objectLoaded($instance)) {
                    // Mise à jour de la valeur existante: 
                    if ($cumulate_if_exists) {
                        $value += (float) $instance->getData('value');
                    }

                    $instance->set('value', $value);

                    global $user;

                    if (BimpObject::objectLoaded($user)) {
                        $instance->set('id_user', $user->id);
                    }
                    $instance->set('date', date('Y-m-d H:i:s'));

                    $up_warnings = array();
                    $up_errors = $instance->update($up_warnings);
                    $up_errors = array_merge($up_errors, $up_warnings);
                    if (count($up_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($up_errors, 'Erreurs lors de la mise à jour de la ligne de correction pour le champ "' . $field . '" de l\'objet "' . $filters['obj_name'] . '"');
                    }
                } else {
                    $instance = BimpObject::getInstance('bimpcore', 'BimpCorrectif');

                    $data = $filters;

                    $data['value'] = $value;

                    $create_errors = $instance->validateArray($data);

                    if (!count($create_errors)) {
                        $create_warnings = array();

                        $create_errors = $instance->create($create_warnings);

                        $create_errors = array_merge($create_errors, $create_warnings);
                    }

                    if (count($create_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($up_errors, 'Erreurs lors de l\'ajout de la ligne de correction pour le champ "' . $field . '" de l\'objet "' . $filters['obj_name'] . '"');
                    }
                }
            }
        }

        return $errors;
    }

    protected static function getObjectFilters($object)
    {
        $filters = array();

        if (is_a($object, 'BimpObject')) {
            $filters['obj_type'] = 'bimp_object';
            $filters['obj_module'] = $object->module;
            $filters['obj_name'] = $object->object_name;

            if (BimpObject::objectLoaded($object)) {
                $filters['id_obj'] = (int) $object->id;
            }
        } elseif (is_object($object)) {
            $filters['obj_type'] = 'dol_object';
            $filters['obj_module'] = BimpTools::getObjectFilePath($object);
            $filters['obj_name'] = get_class($object);

            if (BimpObject::objectLoaded($object)) {
                $filters['id_obj'] = (int) $object->id;
            }
        }

        return $filters;
    }

    // Getters: 

    public function getObject()
    {
        $object = null;
        switch ($this->getData('obj_type')) {
            case 'bimp_object':
                $object = BimpCache::getBimpObjectInstance($this->getData('obj_module'), $this->getData('obj_name'), (int) $this->getData('id_obj'));
                break;

            case 'dol_object':
                $className = $this->getData('obj_name');
                if ($className) {
                    if (!class_exists($className)) {
                        $path = $this->getData('obj_module');
                        if ($path && file_exists(DOL_DOCUMENT_ROOT . '/' . $path)) {
                            require_once $path;

                            if (class_exists($className)) {
                                global $db;
                                $object = new $className($db);

                                if (method_exists($object, 'fetch')) {
                                    $object->fetch((int) $this->getData('id_obj'));
                                }
                            }
                        }
                    }
                }
                break;
        }

        if (is_object($object) && !BimpObject::objectLoaded($object)) {
            unset($object);
            $object = null;
        }

        return $object;
    }

    // Affichages: 

    public function displayObject()
    {
        $object = $this->getObject();

        if (BimpObject::objectLoaded($object)) {
            if (is_a($object, 'BimpObject')) {
                return $object->getNomUrl(1, 0, 1);
            } elseif (method_exists($object, 'getNomUrl')) {
                return $object->getNomUrl(1);
            }
        }

        $object_name = $this->getData('obj_name');
        $id_object = (int) $this->getData('id_obj');

        if ($object_name) {
            return 'Objet "' . $object_name . '"' . ($id_object ? ' d\'ID ' . $id_object : '');
        }

        return '<span class="danger">Inconnu</span>';
    }

    // Overrides: 

    public function create(&$warnings = array(), $force_create = false)
    {
        global $user;

        if (BimpObject::objectLoaded($user)) {
            $this->set('id_user', $user->id);
        }

        $this->set('date', date('Y-m-d H:i:s'));

        return parent::create($warnings, $force_create);
    }
}
