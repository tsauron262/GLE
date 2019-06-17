<?php

class BimpAssociation
{

    public static $table = 'bimpcore_objects_associations';
    public $db;
    public $object = null;
    public $associate = null;
    public $association = null;
    public $association_path = '';
    public $errors = array();

    public function __construct(BimpObject $object, $association)
    {
        global $db;
        $this->db = new BimpDb($db);
        $this->object = $object;

        if (!$object->config->isDefined('associations/' . $association)) {
            $this->errors[] = 'Erreur de configuration: l\'association "' . $association . '" n\'existe pas pour ' . $object->getLabel('the_plur');
        } else {
            $this->association = $association;
            $this->association_path = 'associations/' . $association;

            $this->associate = $this->object->config->getObject($this->association_path . '/object');
            if (is_null($this->associate)) {
                $this->errors[] = 'Erreur de configuration pour l\'association "' . $association . '". Instance associée non définie';
            }
        }
    }

    // Listings: 

    public function getAssociatesList($id_object = null)
    {
        if (count($this->errors)) {
            return array();
        }

        if (is_null($id_object)) {
            if (!isset($this->object->id) || !$this->object->id) {
                return array();
            }

            $id_object = $this->object->id;
        }

        $sql = BimpTools::getSqlSelect(array('dest_id_object'));
        $sql .= BimpTools::getSqlFrom(self::$table);
        $sql .= BimpTools::getSqlWhere(array(
                    'association'        => $this->association,
                    'src_object_module'  => $this->object->module,
                    'src_object_name'    => $this->object->object_name,
                    'dest_object_module' => $this->object->config->getObjectModule($this->association_path . '/object'),
                    'dest_object_name'   => $this->object->config->getObjectName($this->association_path . '/object'),
                    'src_id_object'      => (int) $id_object
        ));

        $rows = $this->db->executeS($sql, 'array');
        if (is_null($rows)) {
            return array();
        }

        $list = array();
        foreach ($rows as $r) {
            $list[] = (int) $r['dest_id_object'];
        }

        return $list;
    }

    public function getObjectsList($id_associate = null)
    {
        if (count($this->errors)) {
            return array();
        }

        if (is_null($id_associate)) {
            if (!isset($this->associate->id) || !$this->associate->id) {
                return array();
            }

            $id_associate = $this->associate->id;
        }

        $sql = BimpTools::getSqlSelect(array('src_id_object'));
        $sql .= BimpTools::getSqlFrom(self::$table);
        $sql .= BimpTools::getSqlWhere(array(
                    'association'        => $this->association,
                    'src_object_module'  => $this->object->module,
                    'src_object_name'    => $this->object->object_name,
                    'dest_object_module' => $this->object->config->getObjectModule($this->association_path . '/object'),
                    'dest_object_name'   => $this->object->config->getObjectName($this->association_path . '/object'),
                    'dest_id_object'     => (int) $id_associate
        ));

        $rows = $this->db->executeS($sql, 'array');
        if (is_null($rows)) {
            return array();
        }

        $list = array();
        foreach ($rows as $r) {
            $list[] = $r['src_id_object'];
        }

        return $list;
    }

    public function getAssociations()
    {
        if (count($this->errors)) {
            return array();
        }

        $sql = BimpTools::getSqlSelect(array('src_id_object', 'dest_id_object'));
        $sql .= BimpTools::getSqlFrom(self::$table);
        $sql .= BimpTools::getSqlWhere(array(
                    'association'        => $this->association,
                    'src_object_module'  => $this->object->module,
                    'src_object_name'    => $this->object->object_name,
                    'dest_object_module' => $this->object->config->getObjectModule($this->association_path . '/object'),
                    'dest_object_name'   => $this->object->config->getObjectName($this->association_path . '/object')
        ));

        $rows = $this->db->executeS($sql, 'array');
        if (is_null($rows)) {
            $rows = array();
        }

        return $rows;
    }

    public function getAssociationsByObjects()
    {
        if (count($this->errors)) {
            return array();
        }

        $associations = array();

        $rows = $this->getAssociations();

        foreach ($rows as $r) {
            if (!isset($associations[(int) $r['src_id_object']])) {
                $associations[(int) $r['src_id_object']] = array();
            }
            $associations[(int) $r['src_id_object']][] = (int) $r['dest_id_object'];
        }

        return $associations;
    }

    public function getAssociationsByAssociates()
    {
        if (count($this->errors)) {
            return array();
        }

        $associations = array();

        $rows = $this->getAssociations();

        foreach ($rows as $r) {
            if (!isset($associations[(int) $r['src_id_object']])) {
                $associations[(int) $r['src_id_object']] = array();
            }
            $associations[(int) $r['src_id_object']][] = (int) $r['dest_id_object'];
        }

        return $associations;
    }

    // Gestion des enregistrements: 

    public function associationExists($id_object, $id_associate)
    {
        if (count($this->errors)) {
            return false;
        }

        $where = 'a.association = \'' . $this->association . '\'';
        $where .= ' AND a.`src_object_module` = \'' . $this->object->module . '\'';
        $where .= ' AND a.`src_object_name` = \'' . $this->object->object_name . '\'';
        $where .= ' AND a.`src_id_object` = ' . (int) $id_object;
        $where .= ' AND a.`dest_object_module` = \'' . $this->object->config->getObjectModule($this->association_path . '/object') . '\'';
        $where .= ' AND a.`dest_object_name` = \'' . $this->object->config->getObjectName($this->association_path . '/object') . '\'';
        $where .= ' AND a.`dest_id_object` = ' . (int) $id_associate;

        $sql = 'SELECT COUNT(`id`) as nb_rows';
        $sql .= BimpTools::getSqlFrom(self::$table);
        $sql .= ' WHERE ' . $where;

        $result = $this->db->execute($sql);
        if ($result > 0) {
            $obj = $this->db->db->fetch_object($result);
            if ((int) $obj->nb_rows > 0) {
                return true;
            }
        }

        return false;
    }

    public function setObjectAssociations($associations, $id_object = null)
    {
        if (count($this->errors)) {
            return $this->errors;
        }

        $errors = array();

        if (is_null($id_object)) {
            if (!isset($this->object->id) || !$this->object->id) {
                return array('ID ' . $this->object->getLabel('of_the') . ' absent');
            }
            $id_object = $this->object->id;
        }

        if (!$associations) {
            $associations = array();
        }

        $dest_object_module = $this->object->config->getObjectModule($this->association_path . '/object');
        $dest_object_name = $this->object->config->getObjectName($this->association_path . '/object');

        // Suppression des associations existantes: 
        $where = '`association` = \'' . $this->association . '\'';
        $where .= ' AND `src_object_module` = \'' . $this->object->module . '\'';
        $where .= ' AND `src_object_name` = \'' . $this->object->object_name . '\'';
        $where .= ' AND `src_id_object` = ' . (int) $id_object;
        $where .= ' AND `dest_object_module` = \'' . $dest_object_module . '\'';
        $where .= ' AND `dest_object_name` = \'' . $dest_object_name . '\'';

        if ($this->db->delete(self::$table, $where) <= 0) {
            $errors[] = 'Echec de la suppression des associations existantes';
        } else {
            $dest_object_type = $this->object->config->getObjectType($this->association_path . '/object');

            // Enregistrement des nouvelles associations
            foreach ($associations as $id_associate) {
                if ($id_associate === '') {
                    continue;
                }
                if ($this->db->insert(self::$table, array(
                            'association'        => $this->association,
                            'src_object_module'  => $this->object->module,
                            'src_object_name'    => $this->object->object_name,
                            'src_object_type'    => 'bimp_object',
                            'src_id_object'      => (int) $id_object,
                            'dest_object_module' => $dest_object_module,
                            'dest_object_name'   => $dest_object_name,
                            'dest_object_type'   => $dest_object_type,
                            'dest_id_object'     => (int) $id_associate
                        )) <= 0) {
                    $msg = 'Echec de l\'enregistrement de l\'association ' . $this->object->getLabel('of_the') . ' ' . $id_object;
                    $msg .= ' avec ' . BimpObject::getInstanceLabel($this->associate, 'the') . ' ' . $id_associate;
                    $sqlError = $this->db->db->error();
                    if ($sqlError) {
                        $msg .= ' - ' . $sqlError;
                    }
                    $errors[] = $msg;
                }
            }
        }
        return $errors;
    }

    public function setAssociateAssociations($associations, $id_associate = null)
    {
        if (count($this->errors)) {
            return $this->errors;
        }

        $errors = array();

        if (is_null($id_associate)) {
            if (!isset($this->associate->id) || !$this->associate->id) {
                return array('ID ' . BimpObject::getInstanceLabel($this->associate, 'of_the') . ' absent');
            }
            $id_associate = $this->associate->id;
        }

        $dest_object_module = $this->object->config->getObjectModule($this->association_path . '/object');
        $dest_object_name = $this->object->config->getObjectName($this->association_path . '/object');

        // Suppression des associations existantes: 
        $where = '`association` = \'' . $this->association . '\'';
        $where .= ' AND `src_object_module` = \'' . $this->object->module . '\'';
        $where .= ' AND `src_object_name` = \'' . $this->object->object_name . '\'';
        $where .= ' AND `dest_object_module` = \'' . $dest_object_module . '\'';
        $where .= ' AND `dest_object_name` = \'' . $dest_object_name . '\'';
        $where .= ' AND `dest_id_object` = ' . (int) $id_associate;

        if ($this->db->delete(self::$table, $where) <= 0) {
            $errors[] = 'Echec de la suppression des associations existantes';
        } else {
            $dest_object_type = $this->object->config->getObjectType($this->association_path . '/object');

            // Enregistrement des nouvelles associations
            foreach ($associations as $id_object) {
                if ($id_object === '') {
                    continue;
                }
                if ($this->db->insert(self::$table, array(
                            'association'        => $this->association,
                            'src_object_module'  => $this->object->module,
                            'src_object_name'    => $this->object->object_name,
                            'src_object_type'    => 'bimp_object',
                            'src_id_object'      => (int) $id_object,
                            'dest_object_module' => $dest_object_module,
                            'dest_object_name'   => $dest_object_name,
                            'dest_object_type'   => $dest_object_type,
                            'dest_id_object'     => (int) $id_associate
                        )) <= 0) {
                    $msg = 'Echec de l\'enregistrement de l\'association ' . BimpObject::getInstanceLabel($this->associate, 'of_the') . ' ' . $id_associate;
                    $msg .= ' avec ' . $this->object->getLabel('the') . ' ' . $id_object;
                    $sqlError = $this->db->db->error();
                    if ($sqlError) {
                        $msg .= ' - ' . $sqlError;
                    }
                    $errors[] = $msg;
                }
            }
        }

        return $errors;
    }

    public function addObjectAssociation($id_associate, $id_object = null)
    {
        if (count($this->errors)) {
            return $this->errors;
        }

        $errors = array();

        if (is_null($id_object)) {
            if (!isset($this->object->id) || !$this->object->id) {
                return array('ID ' . $this->object->getLabel('of_the') . ' absent');
            }
            $id_object = $this->object->id;
        }

        if (!$this->associationExists($id_object, $id_associate)) {
            $dest_object_module = $this->object->config->getObjectModule($this->association_path . '/object');
            $dest_object_name = $this->object->config->getObjectName($this->association_path . '/object');
            $dest_object_type = $this->object->config->getObjectType($this->association_path . '/object');

            if ($this->db->insert(self::$table, array(
                        'association'        => $this->association,
                        'src_object_module'  => $this->object->module,
                        'src_object_name'    => $this->object->object_name,
                        'src_object_type'    => 'bimp_object',
                        'src_id_object'      => (int) $id_object,
                        'dest_object_module' => $dest_object_module,
                        'dest_object_name'   => $dest_object_name,
                        'dest_object_type'   => $dest_object_type,
                        'dest_id_object'     => (int) $id_associate
                    )) <= 0) {
                $msg = 'Echec de l\'enregistrement de l\'association ' . $this->object->getLabel('of_the') . ' ' . $id_object;
                $msg .= ' avec ' . BimpObject::getInstanceLabel($this->associate, 'the') . ' ' . $id_associate;
                $sqlError = $this->db->db->error();
                if ($sqlError) {
                    $msg .= ' - ' . $sqlError;
                }
                $errors[] = $msg;
            }
        } else {
            $msg = ucfirst(BimpObject::getInstanceLabel($this->associate, 'the')) . ' ' . $id_associate;
            $msg .= ' est déjà associé' . (BimpObject::isInstanceLabelFemale($this->associate) ? 'e' : '');
            $msg .= ' à ' . $this->object->getLabel('the') . ' ' . $id_object;
            $errors[] = $msg;
        }

        return $errors;
    }

    public function addAssociateAssociation($id_object, $id_associate = null)
    {
        if (count($this->errors)) {
            return $this->errors;
        }

        $errors = array();

        if (is_null($id_associate)) {
            if (!isset($this->associate->id) || !$this->associate->id) {
                return array('ID ' . BimpObject::getInstanceLabel($this->associate, 'of_the') . ' absent');
            }
            $id_associate = $this->associate->id;
        }

        if (!$this->associationExists($id_object, $id_associate)) {
            $dest_object_module = $this->object->config->getObjectModule($this->association_path . '/object');
            $dest_object_name = $this->object->config->getObjectName($this->association_path . '/object');
            $dest_object_type = $this->object->config->getObjectType($this->association_path . '/object');

            if ($this->db->insert(self::$table, array(
                        'association'        => $this->association,
                        'src_object_module'  => $this->object->module,
                        'src_object_name'    => $this->object->name,
                        'src_object_type'    => 'bimp_object',
                        'src_id_object'      => (int) $id_object,
                        'dest_object_module' => $dest_object_module,
                        'dest_object_name'   => $dest_object_name,
                        'dest_object_type'   => $dest_object_type,
                        'dest_id_object'     => (int) $id_associate
                    )) <= 0) {
                $msg = 'Echec de l\'enregistrement de l\'association ' . BimpObject::getInstanceLabel($this->associate, 'the') . ' ' . $id_associate;
                $msg .= ' avec ' . $this->object->getLabel('of_the') . ' ' . $id_object;
                $sqlError = $this->db->db->error();
                if ($sqlError) {
                    $msg .= ' - ' . $sqlError;
                }
                $errors[] = $msg;
            }
        }

        return $errors;
    }

    // Suppression des associations: 

    public function deleteAssociation($id_object, $id_associate)
    {
        if (count($this->errors)) {
            return $this->errors;
        }

        $errors = array();

        if (is_null($id_object) || !$id_object) {
            $errors[] = 'ID ' . $this->object->getLabel('of_the') . ' absent';
        }

        if (is_null($id_associate) || !$id_associate) {
            $errors[] = 'ID ' . BimpObject::getInstanceLabel($this->associate, 'of_the') . ' absent';
        }

        if (!count($errors)) {
            $dest_object_module = $this->object->config->getObjectModule($this->association_path . '/object');
            $dest_object_name = $this->object->config->getObjectName($this->association_path . '/object');

            $where = '`association` = \'' . $this->association . '\'';
            $where .= ' AND `src_object_module` = \'' . $this->object->module . '\'';
            $where .= ' AND `src_object_name` = \'' . $this->object->object_name . '\'';
            $where .= ' AND `src_id_object` = ' . (int) $id_object;
            $where .= ' AND `dest_object_module` = \'' . $dest_object_module . '\'';
            $where .= ' AND `dest_object_name` = \'' . $dest_object_name . '\'';
            $where .= ' AND `dest_id_object` = ' . (int) $id_associate;

            if ($this->db->delete(self::$table, $where) <= 0) {
                $msg = 'Echec de la suppression de l\'association ' . $this->object->getLabel('of_the') . ' ' . $id_object;
                $msg .= ' avec ' . BimpObject::getInstanceLabel($this->associate, 'the') . ' ' . $id_associate;
                $sqlError = $this->db->db->error();
                if ($sqlError) {
                    $msg .= ' - ' . $sqlError;
                }
                $errors[] = $msg;
            }
        }

        return $errors;
    }

    public function deleteAllObjectAssociations($id_object)
    {
        if (count($this->errors)) {
            return $this->errors;
        }

        $errors = array();

        if (is_null($id_object) || !$id_object) {
            $errors[] = 'ID ' . $this->object->getLabel('of_the') . ' absent';
        } else {
            $dest_object_module = $this->object->config->getObjectModule($this->association_path . '/object');
            $dest_object_name = $this->object->config->getObjectName($this->association_path . '/object');

            $where = '`association` = \'' . $this->association . '\'';
            $where .= ' AND `src_object_module` = \'' . $this->object->module . '\'';
            $where .= ' AND `src_object_name` = \'' . $this->object->object_name . '\'';
            $where .= ' AND `src_id_object` = ' . (int) $id_object;
            $where .= ' AND `dest_object_module` = \'' . $dest_object_module . '\'';
            $where .= ' AND `dest_object_name` = \'' . $dest_object_name . '\'';

            if ($this->db->delete(self::$table, $where) <= 0) {
                $msg = 'Echec de la suppression des associations ' . $this->object->getLabel('of_the') . ' ' . $id_object;
                $msg .= ' avec ' . BimpObject::getInstanceLabel($this->associate, 'the_plur');
                $sqlError = $this->db->db->error();
                if ($sqlError) {
                    $msg .= ' - ' . $sqlError;
                }
                $errors[] = $msg;
            }
        }

        return $errors;
    }

    public function deleteAllAssociateAssociations($id_associate)
    {
        if (count($this->errors)) {
            return $this->errors;
        }

        $errors = array();

        if (is_null($id_associate) || !$id_associate) {
            $errors[] = 'ID ' . BimpObject::getInstanceLabel($this->associate, 'of_the') . ' absent';
        } else {
            $dest_object_module = $this->object->config->getObjectModule($this->association_path . '/object');
            $dest_object_name = $this->object->config->getObjectName($this->association_path . '/object');

            $where = '`association` = \'' . $this->association . '\'';
            $where .= ' AND `src_object_module` = \'' . $this->object->module . '\'';
            $where .= ' AND `src_object_name` = \'' . $this->object->object_name . '\'';
            $where .= ' AND `dest_object_module` = \'' . $dest_object_module . '\'';
            $where .= ' AND `dest_object_name` = \'' . $dest_object_name . '\'';
            $where .= ' AND `dest_id_object` = ' . (int) $id_associate;

            if ($this->db->delete(self::$table, $where) <= 0) {
                $msg = 'Echec de la suppression des associations ' . BimpObject::getInstanceLabel($this->associate, 'of_the') . ' ' . $id_associate;
                $msg .= ' avec ' . $this->object->getLabel('the_plur');
                $sqlError = $this->db->db->error();
                if ($sqlError) {
                    $msg .= ' - ' . $sqlError;
                }
                $errors[] = $msg;
            }
        }

        return $errors;
    }

    public function deleteAllAssociations()
    {
        if (count($this->errors)) {
            return $this->errors;
        }

        $errors = array();

        $dest_object_module = $this->object->config->getObjectModule($this->association_path . '/object');
        $dest_object_name = $this->object->config->getObjectName($this->association_path . '/object');

        $where = '`association` = \'' . $this->association . '\'';
        $where .= ' AND `src_object_module` = \'' . $this->object->module . '\'';
        $where .= ' AND `src_object_name` = \'' . $this->object->object_name . '\'';
        $where .= ' AND `dest_object_module` = \'' . $dest_object_module . '\'';
        $where .= ' AND `dest_object_name` = \'' . $dest_object_name . '\'';

        if ($this->db->delete(self::$table, $where) <= 0) {
            $msg = 'Echec de la suppression des associations des ' . BimpObject::getInstanceLabel($this->associate, 'name_plur');
            $msg .= ' avec ' . $this->object->getLabel('the_plur');
            $sqlError = $this->db->db->error();
            if ($sqlError) {
                $msg .= ' - ' . $sqlError;
            }
            $errors[] = $msg;
        }

        return $errors;
    }

    public static function deleteAssociationById($id_bimp_association)
    {
        if (!is_null($id_bimp_association) && $id_bimp_association) {
            global $db;
            $bdb = new BimpDb($db);
            if ($bdb->delete(self::$table, '`id` = ' . (int) $id_bimp_association) > 0) {
                return true;
            }
        }
        return false;
    }

    // Rendus HTML 

    public function renderAddAssociateInput($item_display = 'default', $autosave = false, $name_prefix = '', $required = 0)
    {
        if (count($this->errors)) {
            return BimpRender::renderAlerts($this->errors);
        }

        if (!$this->object->config->isDefined('associations/' . $this->association . '/input')) {
            return BimpRender::renderAlerts('Input non défini dans la configuration pour cette association');
        }

        if ($this->object->isLoaded()) {
            $items = $this->getAssociatesList();
        } else {
            $items = array();
        }

        $values = array();
        foreach ($items as $id_item) {
            if ($id_item) {
                $values[$id_item] = $this->object->displayAssociate($this->association, $item_display, $id_item);
            }
        }

        $field_params = array(
            'required' => $required,
            'values'   => $values
        );

        $input = new BC_Input($this->object, 'int', $this->association, 'associations/' . $this->association . '/input', $items, $field_params);
        $input->setParam('auto_save', 1);
        $input->setNamePrefix($name_prefix);
        $input->extraClasses[] = 'no-modified';
        $input->extraData['values_field'] = $name_prefix . $this->association;
        $input->setParam('multiple', 1);

        return $input->renderHtml();
    }

    public function renderAssociatesCheckList($name_prefix = '')
    {
        $associates = $this->getAssociatesList();

        if ($this->object->config->isDefined($this->association_path . '/list')) {
            $items_ids = $this->object->config->get($this->association_path . '/list', array(), false, 'array');
        } else {
            $items_ids = array(); // todo...
        }

        $items = array();
        foreach ($items_ids as $id_item) {
            $items[] = array(
                'value' => $id_item,
                'label' => $this->object->displayAssociate($this->association, 'default', $id_item)
            );
        }
        return BimpInput::renderInput('check_list', $name_prefix . $this->association, $associates, array('items' => $items));
    }

    // Gestion SQL : 

    public function getSqlFilters($id_object = null, $id_associate = null, $alias = '')
    {
        $dest_object_module = $this->object->config->getObjectModule($this->association_path . '/object');
        $dest_object_name = $this->object->config->getObjectName($this->association_path . '/object');

        $filters = array();

        if ($alias) {
            if (!preg_match('/\.$/', $alias)) {
                $alias .= '.';
            }
        }

        $filters[$alias . 'association'] = $this->association;
        $filters[$alias . 'src_object_module'] = $this->object->module;
        $filters[$alias . 'src_object_name'] = $this->object->object_name;
        $filters[$alias . 'dest_object_module'] = $dest_object_module;
        $filters[$alias . 'dest_object_name'] = $dest_object_name;

        if (!is_null($id_object)) {
            $filters[$alias . 'src_id_object'] = (int) $id_object;
        }

        if (!is_null($id_associate)) {
            $filters[$alias . 'dest_id_object'] = (int) $id_associate;
        }
        return $filters;
    }
}
