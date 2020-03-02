<?php

class BDSObject
{

    public static $table = '';
    public static $parent_object_name = '';
    public static $parent_id_property = '';
    public static $fields = array();
    protected static $labels = array();
    public static $objects = array();
    public static $associations = array();
    public static $list_params = null;
    public static $form_params = null;
    public $id = null;
    public $db = null;

    public function __construct()
    {
        if (!class_exists('BDSDb')) {
            require_once __DIR__ . '/BDSDb.php';
        }
        global $db;
        $this->db = new BDSDb($db);
    }

    public static function getClass()
    {
        return 'BDSObject';
    }

    // Validation des champs:

    public function validateForm()
    {
        if (!isset($this::$fields)) {
            return array();
        }

        $errors = array();
        foreach ($this::$fields as $name => $params) {
            $value = null;
            if (BDS_Tools::isSubmit($name)) {
                if ($params['type'] === 'datetime') {
                    $value = BDS_Tools::getDateTimeFromForm($name);
                } else {
                    $value = BDS_Tools::getValue($name);
                }
            } elseif (isset($this->{$name})) {
                $value = $this->{$name};
            }

            $value_errors = $this->validateValue($name, $value);
            $errors = BimpTools::merge_array($errors, $value_errors);
        }
        return $errors;
    }

    public function validateArray(Array $values)
    {
        if (!isset($this::$fields)) {
            return array();
        }

        $errors = array();
        foreach ($this::$fields as $name => $params) {
            $value = null;
            if (isset($values[$name])) {
                $value = $values[$name];
            } elseif (isset($this->{$name})) {
                $value = $this->{$name};
            }

            $value_errors = $this->validateValue($name, $value);
            $errors = BimpTools::merge_array($errors, $value_errors);
        }
        return $errors;
    }

    public function validateValue($name, $value)
    {
        $errors = array();

        if (!property_exists($this, $name)) {
            $errors[] = 'La propriété "' . $name . '" n\'existe pas';
        } elseif (!isset($this::$fields[$name])) {
            $errors[] = 'Paramètres de validation absents pour la propriété "' . $name . '"';
        } else {
            $validation = $this::$fields[$name];

            if (is_null($value) || ($value === '')) {
                $missing = false;
                if (isset($validation['required']) && $validation['required']) {
                    $missing = true;
                } elseif (isset($validation['required_if'])) {
                    $required_if = explode('=', $validation['required_if']);
                    $property = $required_if[0];
                    if (isset($this->$property)) {
                        if ($this->$property == $required_if[1]) {
                            $missing = true;
                        }
                    }
                }
                if ($missing) {
                    $errors[] = 'Valeur obligatoire manquante : "' . $validation['label'] . '"';
                }
            } else {
                $validate = true;
                if ($validation['type'] === 'datetime') {
                    if (!preg_match('/^\d{4}\-\d{2}\-\d{2} \d{2}:\d{2}(:\d{2})?$/', $value)) {
                        $validate = false;
                        if (!isset($validation['invalid_msg'])) {
                            $validation['invalid_msg'] = 'Format attendu: AAAA-MM-JJ HH:MM:SS';
                        }
                    }
                }

                if (!count($errors) && isset($validation['regexp'])) {
                    if (!preg_match('/' . $validation['regexp'] . '/', $value)) {
                        $validate = false;
                    }
                }
                if (!count($errors) && isset($validation['is_key_array']) && is_array($validation['is_key_array'])) {
                    if (!array_key_exists($value, $validation['is_key_array'])) {
                        $validate = false;
                    }
                }
                if (!count($errors) && isset($validation['in_array']) && is_array($validation['in_array'])) {
                    if (!in_array($value, $validation['in_array'])) {
                        $validate = false;
                    }
                }

                if (!$validate) {
                    $msg = '"' . $validation['label'] . '": valeur invalide';
                    if (isset($validation['invalid_msg'])) {
                        $msg .= ' (' . $validation['invalid_msg'] . ')';
                    }
                    $errors[] = $msg;
                }

                if (!count($errors)) {
                    $this->{$name} = $value;
                }
            }
        }
        return $errors;
    }

    public function validate()
    {
        $errors = array();
        foreach (static::$fields as $name => $params) {
            if (property_exists($this, $name)) {
                $errors = BimpTools::merge_array($errors, $this->validateValue($name, $this->{$name}));
            }
        }
        return $errors;
    }

    // Gestion SQL: 

    public function fetch($id)
    {
        $row = $this->db->getRow(static::$table, '`id` = ' . (int) $id);
        if (!is_null($row)) {
            foreach ($row as $property => $value) {
                if (property_exists($this, $property)) {
                    $this->{$property} = $value;
                }
            }
            return true;
        }
        return false;
    }

    public function update()
    {
        $errors = array();
        if (is_null($this->id) || !$this->id) {
            return array('ID Absent');
        }
        $errors = $this->validate();

        if (!count($errors)) {
            $data = $this->getDataArray();
            $result = $this->db->update(static::$table, $data, '`id` = ' . (int) $this->id);
            if ($result <= 0) {
                $msg = 'Echec de la mise à jour ' . $this->getLabel('of_the');
                $sqlError = $this->db->db->error();
                if ($sqlError) {
                    $msg .= ' - Erreur SQL: ' . $sqlError;
                }
                $errors[] = $msg;
            }
        }

        return $errors;
    }

    public function create()
    {
        $errors = $this->validate();

        if (!count($errors)) {
            $data = $this->getDataArray();
            $result = $this->db->insert(static::$table, $data, true);
            if ($result > 0) {
                $this->id = $result;
            } else {
                $msg = 'Echec de l\'enregistrement ' . $this->getLabel('of_the');
                $sqlError = $this->db->db->error();
                if ($sqlError) {
                    $msg .= ' - Erreur SQL: ' . $sqlError;
                }
                $errors[] = $msg;
            }
        }

        return $errors;
    }

    public function delete()
    {
        if (is_null($this->id) || !$this->id) {
            return array('ID absent');
        }
        $errors = array();
        $result = $this->db->delete(static::$table, '`id` = ' . (int) $this->id);
        if ($result <= 0) {
            $msg = 'Echec de la suppression';
            $sqlError = $this->db->db->error();
            if ($sqlError) {
                $msg .= ' - Erreur SQL: ' . $sqlError;
            }
            $errors[] = $msg;
        } else {
            foreach (static::$objects as $name => $params) {
                if (isset($params['delete']) && $params['delete']) {
                    $class_name = $params['class_name'];
                    if (class_exists($class_name)) {
                        if (method_exists($class_name, 'deleteByParent')) {
                            if (!$class_name::deleteByParent($this->db, $this->id)) {
                                $msg = 'Des erreurs sont survenues lors de la tentative de suppresion ';
                                $msg += 'des ' . $class_name::getLabel('name_plur');
                                $errors[] = $msg;
                            }
                        }
                    }
                }
            }
            foreach (static::$associations as $name => $params) {
                $where = '`' . $params['self_key'] . '` = ' . (int) $this->id;
                $result = $this->db->delete($params['table'], $where);
                if ($result <= 0) {
                    $asso_class = $params['class_name'];
                    $msg = 'Echec de la suppression des associations avec les ' . $asso_class::getLabel('name_plur');
                    $errors[] = $msg;
                }
            }
        }

        return $errors;
    }

    public function getDataArray($include_id = false)
    {
        $data = array();
        foreach (static::$fields as $name => $params) {
            if (property_exists($this, $name)) {
                if (!is_null($this->{$name})) {
                    $data[$name] = $this->{$name};
                }
            }
        }
        if ($include_id) {
            if (!is_null($this->id)) {
                $data['id'] = $this->id;
            } else {
                $data['id'] = 0;
            }
        }
        return $data;
    }

    public static function deleteByParent(BDSDb $bdb, $id_parent)
    {
        if (is_null($id_parent) || !$id_parent) {
            return false;
        }

        if (!static::$parent_id_property) {
            return false;
        }

        $where = '`' . static::$parent_id_property . '` = ' . (int) $id_parent;
        $result = $bdb->delete(static::$table, $where);
        if ($result <= 0) {
            return false;
        }
        return true;
    }

    public static function getListData(BDSDb $bdb, $id_parent = null)
    {
        if (!is_null($id_parent)) {
            $where = '`' . static::$parent_id_property . '` = ' . (int) $id_parent;
        } else {
            $where = '1';
        }
        $rows = $bdb->getRows(static::$table, $where, null, 'array');

        if (!is_null($rows)) {
            return $rows;
        }

        return array();
    }

    public function saveAssociations($association, $list)
    {
        $errors = array();

        if (!isset(static::$associations[$association])) {
            $errors[] = 'le type d\'association "' . $association . '" n\'est pas valide';
        } elseif (is_null($this->id) || !$this->id) {
            $errors[] = 'ID Absent';
        } else {
            $params = static::$associations[$association];
            $result = $this->db->delete($params['table'], '`' . $params['self_key'] . '` = ' . (int) $this->id);
            if ($result <= 0) {
                $msg = 'Echec de la suppression des associations existantes';
                $sqlError = $this->db->db->error();
                if ($sqlError) {
                    $msg .= ' - Erreur SQL: ' . $sqlError;
                }
                $errors[] = $msg;
            } else {
                $class_name = $params['class_name'];
                $label = $class_name::getLabel('the');
                foreach ($list as $id) {
                    $data = array();
                    $data[$params['self_key']] = (int) $this->id;
                    $data[$params['associate_key']] = (int) $id;
                    if (!$this->db->insert($params['table'], $data)) {
                        $msg = 'Echec de l\'enregistrement de l\'association avec ' . $label . ' d\'ID ' . $id;
                        $sqlError = $this->db->db->error();
                        if ($sqlError) {
                            $msg .= ' - Erreur SQL: ' . $sqlError;
                        }
                        $errors[] = $msg;
                    }
                }
            }
        }

        return $errors;
    }

    public function getAssociationList($association)
    {
        $list = array();

        if (!isset(static::$associations[$association])) {
            return $list;
        }

        $params = static::$associations[$association];
        $class_name = $params['class_name'];

        $id_parent = (isset($params['same_parent']) && $params['same_parent']) ? $this->{static::$parent_id_property} : null;
        return $class_name::getListData($this->db, $id_parent);
    }

    public function getAssociatedObjectsIds($association)
    {
        if (is_null($this->id) || !$this->id) {
            return array();
        }

        if (!isset(static::$associations[$association])) {
            return array();
        }

        $params = static::$associations[$association];
        $sql = 'SELECT `' . $params['associate_key'] . '` as id FROM ' . MAIN_DB_PREFIX . $params['table'];
        $sql .= ' WHERE `' . $params['self_key'] . '` = ' . (int) $this->id;

        $rows = $this->db->executeS($sql, 'array');

        if (!is_null($rows)) {
            $ids = array();
            foreach ($rows as $r) {
                if (!in_array($r['id'], $ids)) {
                    $ids[] = $r['id'];
                }
            }
            return $ids;
        }

        return array();
    }

    // Gestion des intitulés (labels) : 

    public static function getLabels()
    {
        $labels = static::$labels;

        if (isset($labels['name'])) {
            $objectName = $labels['name'];
        } else {
            $objectName = 'objet';
        }

        $vowel_first = false;
        if (preg_match('/^[aàâäeéèêëiîïoôöuùûüyŷÿ](.*)$/', $objectName)) {
            $vowel_first = true;
        }

        if (!isset($labels['name_plur'])) {
            if (preg_match('/^.*[ao]u$/', $objectName)) {
                $labels['name_plur'] = $objectName . 'x';
            } elseif (preg_match('/^.*ou$/', $objectName)) {
                $labels['name_plur'] = $objectName . 'x';
            } elseif (!preg_match('/^.*s$/', $objectName)) {
                $labels['name_plur'] = $objectName . 's';
            } else {
                $labels['name_plur'] = $objectName;
            }
        }

        if (isset($labels['isFemale'])) {
            $isFemale = $labels['isFemale'];
        } else {
            $isFemale = false;
        }
        $labels['isFemale'] = $isFemale;

        if (!isset($labels['name'])) {
            $labels['name'] = 'object';
        }

        if (!isset($labels['the'])) {
            if ($vowel_first) {
                $labels['the'] = 'l\'';
            } elseif ($isFemale) {
                $labels['the'] = 'la';
            } else {
                $labels['the'] = 'le';
            }
        }

        if (!isset($labels['a'])) {
            if ($isFemale) {
                $labels['a'] = 'une';
            } else {
                $labels['a'] = 'un';
            }
        }

        if (!isset($labels['this'])) {
            if ($isFemale) {
                $labels['this'] = 'cette';
            } elseif ($vowel_first) {
                $labels['this'] = 'cet';
            } else {
                $labels['this'] = 'ce';
            }
        }

        if (!isset($labels['of_a'])) {
            if ($isFemale) {
                $labels['of_the'] = 'd\'une';
            } else {
                $labels['of_the'] = 'd\'un';
            }
        }

        if (!isset($labels['of_the'])) {
            if ($vowel_first) {
                $labels['of_the'] = 'de l\'';
            } elseif ($isFemale) {
                $labels['of_the'] = 'de la';
            } else {
                $labels['of_the'] = 'du';
            }
        }

        if (!isset($labels['of_this'])) {
            if ($isFemale) {
                $labels['of_this'] = 'de cette';
            } elseif ($vowel_first) {
                $labels['of_this'] = 'de cet';
            } else {
                $labels['of_this'] = 'de ce';
            }
        }

        if (!isset($labels['of_those'])) {
            $labels['of_those'] = 'de ces';
        }

        return $labels;
    }

    public static function getLabel($type = '')
    {
        $labels = static::$labels;

        if (isset($labels['name'])) {
            $objectName = $labels['name'];
        } else {
            $objectName = 'objet';
        }

        $vowel_first = false;
        if (preg_match('/^[aàâäeéèêëiîïoôöuùûüyŷÿ](.*)$/', $objectName)) {
            $vowel_first = true;
        }

        $name_plur = '';

        if (!isset($labels['name_plur'])) {
            if (preg_match('/^.*[ao]u$/', $objectName)) {
                $name_plur = $objectName . 'x';
            } elseif (preg_match('/^.*ou$/', $objectName)) {
                $name_plur = $objectName . 'x';
            } elseif (!preg_match('/^.*s$/', $objectName)) {
                $name_plur = $objectName . 's';
            }
        } else {
            $name_plur = $labels['name_plur'];
        }

        if (isset($labels['isFemale'])) {
            $isFemale = $labels['isFemale'];
        } else {
            $isFemale = false;
        }

        switch ($type) {
            case '':
                return $objectName;

            case 'name_plur':
                return $name_plur;

            case 'the':
                if ($vowel_first) {
                    return 'l\'' . $objectName;
                } elseif ($isFemale) {
                    return 'la ' . $objectName;
                } else {
                    return 'le ' . $objectName;
                }

            case 'a':
                if ($isFemale) {
                    return 'une ' . $objectName;
                } else {
                    return 'un ' . $objectName;
                }

            case 'this':
                if ($isFemale) {
                    return 'cette ' . $objectName;
                } elseif ($vowel_first) {
                    return 'cet ' . $objectName;
                } else {
                    return 'ce ' . $objectName;
                }

            case 'of_a':
                if ($isFemale) {
                    return 'd\'une ' . $objectName;
                } else {
                    return 'd\'un ' . $objectName;
                }

            case 'of_the':
                if ($vowel_first) {
                    return 'de l\'' . $objectName;
                } elseif ($isFemale) {
                    return 'de la ' . $objectName;
                } else {
                    return 'du ' . $objectName;
                }

            case 'of_this':
                if ($isFemale) {
                    return 'de cette ' . $objectName;
                } elseif ($vowel_first) {
                    return 'de cet ' . $objectName;
                } else {
                    return 'de ce ' . $objectName;
                }

            case 'of_those':
                return 'de ces ' . $name_plur;
        }

        return $objectName;
    }

    public static function isLabelFemale()
    {
        if (isset(static::$labels['isFemale'])) {
            return static::$labels['isFemale'];
        }
        return false;
    }

    // Générations HTML: 

    public function renderForm($id_parent = null)
    {
        global $db;
        $form = new Form($db);
        $id_object = (!is_null($this->id) ? $this->id : 0);

        $html = '<form id="' . $this->getClass() . '_form" class="objectForm" method="post">';

        $html .= '<input type="hidden" name="object_name" value="' . $this->getClass() . '"/>';
        if ($id_object) {
            $html .= '<input type="hidden" name="id_object" value="' . $this->id . '" data-default_value="' . $this->id . '"/>';
        }

        $html .= '<table class="border" cellpadding="15" width="100%">';
        $html .= '<tbody>';

        foreach (static::$fields as $name => $params) {
            if ($name === static::$parent_id_property) {
                if (is_null($id_parent)) {
                    $id_parent = 0;
                }
                $html .= '<input type="hidden" name="' . $name . '" value="' . $id_parent . '"/>';
                continue;
            }
            $html .= '<tr';
            if ($params['input'] === 'hidden') {
                $html .= ' style="display: none"';
            }
            if (isset($params['display_if'])) {
                $html .= ' class="display_if" ';
                $html .= ' data-input_name="' . $params['display_if']['input_name'] . '"';
                if (isset($params['display_if']['show_values'])) {
                    $html .= ' data-show_values="' . $params['display_if']['show_values'] . '"';
                }
                if (isset($params['display_if']['hide_values'])) {
                    $html .= ' data-hide_values="' . $params['display_if']['hide_values'] . '"';
                }
            }
            $html .= '>';
            $html .= '<th class="ui-widget-header ui-state-default" width="15%">' . $params['label'] . '</th>';
            $html .= '<td class="ui-widget-content">';
            $value = '';
            if ($id_object) {
                $value = htmlentities($this->{$name});
            } elseif (isset($params['default_value'])) {
                $value = $params['default_value'];
            }

            switch ($params['input']) {
                case 'hidden':
                    $html .= '<input type="hidden" name="' . $name . '" value="' . $value . '"/>';
                    break;

                case 'datetime':
                    if (!$value) {
                        $value = date('Y-m-d H:i:s');
                    }
                    $display_now = (isset($params['display_now']) && $params['display_now']);
                    $DT = new DateTime($value);
                    $html .= $form->select_date($DT->getTimestamp(), $name, 1, 1, 0, "", 1, $display_now, 1);
                    unset($DT);
                    break;

                case 'text':
                    $size = (isset($params['input_size']) ? $params['input_size'] : 60);
                    $html .= '<input type="text" name="' . $name . '" value="' . $value . '" size="' . $size . '"/>';
                    break;

                case 'textarea':
                    $cols = (isset($params['cols']) ? $params['cols'] : 60);
                    $rows = (isset($params['rows']) ? $params['rows'] : 3);
                    $html .= '<textarea cols="' . $cols . '" rows="' . $rows . '" name="' . $name . '">' . $value . '</textarea>';
                    break;

                case 'switch':
                    $html .= '<select class="switch" name="' . $name . '">';
                    $html .= '<option value="1"' . ($value ? ' selected' : '') . '>OUI</option>';
                    $html .= '<option value="0"' . (!$value ? ' selected' : '') . '>NON</option>';
                    $html .= '</select>';
                    break;

                case 'select':
                    $options = array();
                    if (isset($params['options'])) {
                        $method = 'get' . ucfirst($params['options']) . 'QueryArray';
                        if (method_exists($this, $method)) {
                            $options = $this->{$method}($id_parent);
                        }
                    }
                    $html .= '<select name="' . $name . '">';
                    foreach ($options as $option_name => $option_label) {
                        $html .= '<option value="' . $option_name . '"';
                        if ($value == $option_name) {
                            $html .= ' selected';
                        }
                        $html .= '>' . $option_label . '</option>';
                    }
                    $html .= '</select>';
                    break;
            }

            if (isset($params['help'])) {
                $html .= '<p class="inputHelp">' . $params['help'] . '</p>';
            }

            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        $html .= '<div class="ajaxResultContainer" id="' . $this->getClass() . '_formResultContainer">';
        $html .= '</div>';

        $html .= '<div class="formSubmit">';
        $html .= '<span class="butAction" onclick="saveObjectFromForm(\'' . $this->getClass() . '\')">';
        $html .= 'Enregistrer';
        $html .= '</span>';
        $html .= '</div>';
        $html .= '</form>';

        return $html;
    }

    public static function renderCreateForm($id_parent = null)
    {

        $html .= '<table class="noborder" width="100%">';

        $html .= '<tr class="liste_titre">';
        $html .= '<td>';
        $html .= 'Ajout ' . static::getLabel('of_a');
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td>';

        $class_name = static::getClass();
        $object = new $class_name();
        $html .= $object->renderForm($id_parent);

        $html .= '</td/>';
        $html .= '</tr/>';
        $html .= '</table/>';
        $html .= '</div>';

        return $html;
    }

    public function renderEditForm($id_parent = null)
    {
        $html = '<div>';
        $html .= '<table class="noborder" width="100%">';

        $html .= '<tr class="liste_titre">';
        $html .= '<td>';
        if (!is_null($this->id) && $this->id) {
            $html .= 'Edition ' . static::getLabel('of_the') . ' ' . $this->id;
            $html .= '</td>';
            $html .= '</tr>';
            $html .= '<tr>';
            $html .= '<td>';
            $html .= $this->renderForm($id_parent);
        } else {
            $html .= '<p class="alert alert-danger">';
            $html .= static::getLabel('this') . 'n\'a pas été trouvé';
            $html .= '</p>';
        }


        $html .= '</td/>';
        $html .= '</tr/>';
        $html .= '</table/>';
        $html .= '</div>';

        if (!is_null($this->id) && $this->id) {
            if (count(static::$associations)) {
                foreach (static::$associations as $association => $params) {
                    $html .= $this->renderAssociationForm($association);
                }
            }
        }

        return $html;
    }

    public function renderAssociationForm($association)
    {
        if (!isset(static::$associations[$association])) {
            return '';
        }

        $params = static::$associations[$association];
        $class_name = $params['class_name'];
        $list = array();
        $method = 'get' . ucfirst($association) . 'AssociationList';

        if (method_exists($this, $method)) {
            $list = $this->{$method}();
        } else {
            $list = $this->getAssociationList($association);
        }

        $currents = $this->getAssociatedObjectsIds($association);
        $label = $class_name::getLabel('name_plur') . ' associé' . ($class_name::isLabelFemale() ? 'e' : '') . 's';

        $html .= '<div>';

        $html .= '<table class="noborder" width="100%">';

        $html .= '<tr class="liste_titre">';
        $html .= '<td>';
        $html .= ucfirst($label);
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td>';
        if (count($list)) {
            $html .= '<div id="' . $this->getClass() . '_' . $association . '_associations_list">';
            foreach ($list as $object) {
                $html .= '<div class="formRow">';
                $html .= '<input type="checkbox" value="' . $object['id'] . '" id="' . $association . '_' . $object['id'] . '" name="' . $association . '[]"';
                if (in_array($object['id'], $currents)) {
                    $html .= ' checked';
                }
                $html .= '/>';
                $html .= '<label for="' . $association . '_' . $object['id'] . '">';
                if (isset($object['label'])) {
                    $html .= $object['label'];
                } elseif (isset($object['title'])) {
                    $html .= $object['title'];
                } elseif (isset($object['name'])) {
                    $html .= $object['name'];
                } else {
                    $html .= ucfirst($class_name::getLabel('')) . ' n°' . $object['id'];
                }
                $html .= '</label>';
                $html .= '</div>';
            }
            $html .= '</div>';
        } else {
            $html .= '<div class="alert alert-warning">';
            $html .= 'Il n\'y a aucun' . ($class_name::isLabelFemale() ? 'e' : '') . ' ' . $class_name::getLabel() . ' à associer';
            $html .= '</div>';
        }

        $html .= '<div id="' . $this->getClass() . '_' . $association . '_associatonsAjaxResult"></div>';

        $html .= '<div class="formSubmit">';
        $html .= '<span class="butAction" onclick="saveObjectAssociations(' . $this->id . ', \'' . $this->getClass() . '\', \'' . $association . '\', $(this));">';
        $html .= 'Enregistrer les ' . $label;
        $html .= '</span>';
        $html .= '</div>';
        $html .= '</td/>';
        $html .= '</tr/>';

        $html .= '</table/>';

        $html .= '</div>';

        return $html;
    }

    public static function renderList($id_parent = null)
    {
        global $db;
        $form = new Form($db);

        if (isset(static::$list_params)) {
            $params = static::$list_params;
        } else {
            $params = array();
        }

        $html = '<script type="text/javascript">';
        $html .= 'var object_labels = ' . json_encode(static::getLabels());
        $html .= '</script>';

        $html .= '<div id="' . static::getClass() . '_listContainer">';

        $html .= '<table class="noborder" width="100%">';

        $html .= '<tr class="liste_titre">';
        $html .= '<td>';
        if (isset($params['title'])) {
            $html .= $params['title'];
        } else {
            $html .= 'Liste des ' . static::getLabel('name_plur');
        }
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td>';

        $html .= '<div id="' . static::getClass() . '_list_table" class="objectListTable">';
        if (!is_null($id_parent)) {
            $html .= '<input type="hidden" id="' . static::getClass() . '_id_parent" value="' . $id_parent . '"/>';
        }
        $html .= '<table class="noborder" style="border: none" width="100%">';

        $nHeaders = 0;
        if (isset($params['headers'])) {
            $html .= '<thead>';
            $html .= '<tr>';

            if (isset($params['checkboxes']) && $params['checkboxes']) {
                $html .= '<th width="5%" style="text-align: center">';
                $html .= '<input type="checkbox" id="' . static::getClass() . '_checkall" onchange="toggleCheckAll(\'' . static::getClass() . '\', $(this));"/>';
                $html .= '</th>';
                $nHeaders++;
            }

            foreach ($params['headers'] as $header) {
                $html .= '<th' . (isset($header['width']) ? ' width="' . $header['width'] . '%"' : '' ) . '>';
                if (isset($header['label'])) {
                    $html .= $header['label'];
                }
                $html .= '</th>';
                $nHeaders++;
            }

            $html .= '</tr>';
            $html .= '</thead>';
        }

        $html .= '<tbody>';

        $html .= static::renderListRows($id_parent);

        if (isset($params['row_form_inputs'])) {
            $html .= '<tr id="' . static::getClass() . '_listInputsRow" class="inputsRow">';
            if (isset($params['checkboxes']) && $params['checkboxes']) {
                $html .= '<td></td>';
            }
            if (!is_null($id_parent)) {
                $html .= '<td style="display: none">';
                $html .= '<input typê="hidden" class="objectListRowInput" name="' . static::$parent_id_property . '" ';
                $html .= 'value="' . $id_parent . '" data-default_value="' . $id_parent . '"/>';
                $html .= '</td>';
            }
            foreach ($params['row_form_inputs'] as $input) {
                $html .= '<td' . (($input['type'] === 'hidden') ? ' style="display: none"' : '') . '>';
                if ($input['type'] !== 'empty') {
                    if ($input['type'] === 'switch') {
                        $defVal = 1;
                        $html .= '<select id="rowInput_' . static::getClass() . '_' . $input['id'] . '" class="switch objectListRowInput" name="' . $input['name'] . '"';
                        if (isset($input['default_value'])) {
                            $html .= ' data-default_value="' . $input['default_value'] . '"';
                            $defVal = (int) $input['default_value'];
                        }
                        $html .= '>';
                        $html .= '<option value="1"' . (($defVal === 1) ? ' selected' : '') . '>OUI</option>';
                        $html .= '<option value="0"' . (($defVal === 0) ? ' selected' : '') . '>NON</option>';
                        $html .= '</select>';
                    } elseif ($input['type'] === 'datetime') {
                        if (isset($input['default_value'])) {
                            $defVal = $input['default_value'];
                        } else {
                            $defVal = '0000-00-00 00:00';
                        }
                        $DT = new DateTime($defVal);
                        $html .= $form->select_date($DT->getTimestamp(), $input['name'], 1, 1);
                        unset($DT);
                    } else {
                        $html .= '<input type="' . $input['type'] . '" name="' . $input['name'] . '" ';
                        $html .= 'class="objectListRowInput" id="rowInput_' . static::getClass() . '_' . $input['id'] . '"';
                        $html .= 'value="';
                        if (isset($input['default_value'])) {
                            $html .= $input['default_value'] . '" data-default_value="' . $input['default_value'];
                        }
                        $html .= '"/>';
                    }
                }
                $html .= '</td>';
            }
            $html .= '<td>';
            $html .= '<span class="butAction" onclick="addObjectFromListInputsRow(\'' . static::getClass() . '\', $(this))">Ajouter</span>';
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '<div class="ajaxResultContainer" id="' . static::getClass() . '_listResultContainer"></div>';
        $html .= '</div>';
        $html .= '</td>';
        $html .= '</tr>';

        if (isset($params['bulk_actions'])) {
            $html .= '<tr>';
            $html .= '<td style="padding: 15px 10px">';
            foreach ($params['bulk_actions'] as $action) {
                $html .= '<span class="butAction';
                if (isset($action['btn_class'])) {
                    $html .= ' ' . $action['btn_class'];
                }
                $html .= '" onclick="' . $action['onclick'] . '">';
                $html .= $action['label'];
                $html .= '</span>';
            }
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    public static function renderListRows($id_parent = null)
    {
        if (isset(static::$list_params)) {
            $params = static::$list_params;
        } else {
            $params = array();
        }
        global $db;
        $bdb = new BDSDb($db);
        $form = new Form($db);
        $rows = static::getListData($bdb, $id_parent);

        $html = '';

        if (count($rows)) {
            foreach ($rows as $r) {
                $html .= '<tr class="' . static::getClass() . '_row" id="' . static::getClass() . '_row_' . $r['id'] . '">';
                if (!is_null($id_parent)) {
                    $html .= '<td style="display: none">';
                    $html .= '<input type="hidden" class="objecRowEditInput" value="' . $id_parent . '" name="';
                    if (!is_null(static::$parent_id_property)) {
                        $html .= static::$parent_id_property;
                    } else {
                        $html .= 'id_parent';
                    }
                    $html .= '">';
                    $html .= '</td>';
                }

                if (isset($params['checkboxes']) && $params['checkboxes']) {
                    $html .= '<td style="text-align: center">';
                    $html .= '<input type="checkbox" id_="' . static::getClass() . '_check_' . $r['id'] . '"';
                    $html .= ' name="' . static::getClass() . '_check"';
                    $html .= ' class="item_check"';
                    $html .= ' data-id_object="' . $r['id'] . '"';
                    $html .= '</td>';
                }

                if (isset($params['cols'])) {
                    foreach ($params['cols'] as $col) {
                        if (isset($col['params_callback'])) {
                            if (method_exists(static::getClass(), $col['params_callback'])) {
                                static::{$col['params_callback']}($col, $r);
                            }
                        }
                        $html .= '<td' . (isset($col['hidden']) && $col['hidden'] ? ' style="display: none"' : '') . '>';
                        if (isset($col['input'])) {
                            if ($col['input'] === 'text') {
                                $html .= '<input type="text" class="objecRowEditInput" name="' . $col['name'] . '" value="';
                                if (isset($r[$col['name']])) {
                                    $html .= $r[$col['name']];
                                }
                                $html .= '"/>';
                            } elseif ($col['input'] === 'switch') {
                                $val = 0;
                                if (isset($r[$col['name']])) {
                                    $val = (int) $r[$col['name']];
                                }
                                $html .= '<select class="switch objecRowEditInput" name="' . $col['name'] . '">';
                                $html .= '<option value="1"' . (((int) $val !== 0) ? ' selected' : '') . '>OUI</option>';
                                $html .= '<option value="0"' . (((int) $val === 0) ? ' selected' : '') . '>NON</option>';
                                $html .= '</select>';
                            } elseif ($col['input'] === 'datetime') {
                                if (isset($r[$col['name']])) {
                                    $val = $r[$col['name']];
                                } else {
                                    $val = '0000-00-00 00:00';
                                }
                                $DT = new DateTime($val);
                                $html .= $form->select_date($DT->getTimestamp(), $col['name'], 1, 1);
                                unset($DT);
                            } elseif ($col['input'] === 'select') {
                                $html .= '<select class="objecRowEditInput" name="' . $col['name'] . '">';
                                if (isset($col['options'])) {
                                    $options = array();
                                    if (is_array($col['options'])) {
                                        $options = $col['options'];
                                    } elseif (property_exists(static::getClass(), $col['options'])) {
                                        $options = static::${$col['options']};
                                    } else {
                                        $method_name = 'get' . ucfirst($col['options']) . 'QueryArray';
                                        if (method_exists(static::getClass(), $method_name)) {
                                            $options = static::{$method_name}($id_parent);
                                        }
                                    }
                                    foreach ($options as $value => $label) {
                                        $html .= '<option value="' . $value . '">' . $label . '</option>';
                                    }
                                }
                                $html .= '</select>';
                            }
                        } elseif (isset($col['data_type'])) {
                            switch ($col['data_type']) {
                                case 'bool':
                                    if ((int) $r[$col['name']] === 1) {
                                        $html .= '<span class="success">OUI</span>';
                                    } else {
                                        $html .= '</span class="danger">NON</span>';
                                    }
                                    break;

                                case 'array_value':
                                    if (isset($col['array_name'])) {
                                        $array = array();
                                        $array_value = '';
                                        if (property_exists(static::getClass(), $col['array_name'])) {
                                            $array = static::${$col['array_name']};
                                        } else {
                                            $method_name = 'get' . ucfirst($col['array_name']) . 'QueryArray';
                                            if (method_exists(static::getClass(), $method_name)) {
                                                $array = static::{$method_name}($id_parent);
                                            }
                                        }
                                        if (array_key_exists($r[$col['name']], $array)) {
                                            $array_value = $array[$r[$col['name']]];
                                        }
                                    }
                                    if (!$array_value) {
                                        $array_value = $r[$col['name']];
                                    }
                                    $html .= $array_value;
                                    break;

                                case 'datetime':
                                    $date = new DateTime($r[$col['name']]);
                                    $html .= $date->format('d / m / Y H:i');
                                    break;

                                case 'string':
                                default:
                                    $html .= $r[$col['name']];
                                    break;
                            }
                        }
                        $html .= '</td>';
                    }
                }
                $html .= '<td>';
                if (isset($params['update_btn']) && $params['update_btn']) {
                    $html .= '<span class="butAction" onclick="updateObjectFromRow(\'' . static::getClass() . '\', ' . $r['id'] . ', $(this))">';
                    $html .= 'Mettre à jour';
                    $html .= '</span>';
                }
                if (isset($params['edit_btn']) && $params['edit_btn']) {
                    $html .= '<span class="butAction" onclick="openObjectForm(\'' . static::getClass() . '\', ' . $id_parent . ', ' . $r['id'] . ')">';
                    $html .= 'Editer';
                    $html .= '</span>';
                }
                if (isset($params['delete_btn']) && $params['delete_btn']) {
                    $html .= '<span class="butActionDelete" onclick="deleteObjects(\'' . static::getClass() . '\', [' . $r['id'] . '], $(this), ';
                    $html .= '$(\'#' . static::getClass() . '_listResultContainer\'))">';
                    $html .= 'Supprimer';
                    $html .= '</span>';
                }
                $html .= '</td>';
                $html .= '</tr>';
            }
        } else {
            if (isset($params['headers'])) {
                $nHeaders = count($params['headers']);
                if (isset($params['checkboxes']) && $params['checkboxes']) {
                    $nHeaders++;
                }
            } else {
                $nHeaders = 1;
            }
            $html .= '<tr>';
            $html .= '<td  colspan="' . $nHeaders . '" style="text-align: center">';
            $html .= '<p class="alert alert-info">';
            $html .= 'Aucun' . (static::$labels['isFemale'] ? 'e' : '') . ' ' . static::$labels['name'];
            $html .= ' enregistré' . (static::$labels['isFemale'] ? 'e' : '') . ' pour le moment';
            $html .= '</p>';
            $html .= '</td>';
            $html .= '</tr>';
        }

        return $html;
    }

    public static function renderFormAndList()
    {
        $html = '';
        $className = static::getClass();
        $html .= '<div id="' . $className . '_card">';
        $html .= '<div class="objectToolbar">';
        $html .= '<span id="' . $className . '_openFormButton" class="butAction"';
        $html .= 'onclick="openObjectForm(\'' . $className . '\', null)">';
        $html .= 'Ajouter ' . static::getLabel('a') . '</span>';
        $html .= '<span id="' . $className . '_closeFormButton" class="butActionDelete"';
        $html .= 'onclick="closeObjectForm(\'' . $className . '\')"';
        $html .= ' style="display: none">';
        $html .= 'Annuler</span>';
        $html .= '</div>';

        $html .= '<div id="' . $className . '_formContainer" style="display: none"></div>';
        $html .= static::renderList(null);
        $html .= '</div>';

        return $html;
    }

    public function renderObjectsList($object_name)
    {
        if (is_null($this->id) || !$this->id) {
            return '';
        }

        if (isset(static::$objects[$object_name])) {
            $className = static::$objects[$object_name]['class_name'];
            if (!class_exists($className)) {
                require_once __DIR__ . '/' . $className . '.class.php';
            }

            if (class_exists($className)) {
                return $className::renderList($this->id);
            }
        }

        return '';
    }

    public function renderObjectFormAndList($object_name)
    {

        if (is_null($this->id) || !$this->id) {
            return '';
        }

        $html = '';
        if (isset(static::$objects[$object_name])) {
            $className = static::$objects[$object_name]['class_name'];
            if (!class_exists($className)) {
                require_once __DIR__ . '/' . $className . '.class.php';
            }

            if (class_exists($className)) {
                $html .= '<div id="' . $className . '_card">';
                $html .= '<div class="objectToolbar">';
                $html .= '<span id="' . $className . '_openFormButton" class="butAction"';
                $html .= 'onclick="openObjectForm(\'' . $className . '\', ' . $this->id . ')">';
                $html .= 'Ajouter ' . $className::getLabel('a') . '</span>';
                $html .= '<span id="' . $className . '_closeFormButton" class="butActionDelete"';
                $html .= 'onclick="closeObjectForm(\'' . $className . '\')"';
                $html .= ' style="display: none">';
                $html .= 'Annuler</span>';
                $html .= '</div>';

                $html .= '<div id="' . $className . '_formContainer" style="display: none"></div>';
                $html .= $className::renderList($this->id);
                $html .= '</div>';
            } else {
                $html .= '<p class="alert alert-danger">Classe "' . $className . '" absente</p>';
            }
        } else {
            $html .= '<p class="alert alert-danger">Paramètre des objets "' . $object_name . '" absents</p>';
        }

        return $html;
    }
}
