<?php

class BC_Display extends BimpComponent
{

    public $component_name = 'Affichage';
    public static $type = 'display';
    public static $config_required = false;
    public $field_name = null;
    public $field_params = null;
    public $value = null;
    public $no_html = false;
    protected static $cache = array();
    public static $dates_formats = array(
        'Y-m-d'     => 'AAAA-MM-JJ',
        'd / m / Y' => 'JJ / MM / AAAA',
    );
    public static $times_formats = array(
        'H:i:s' => 'H:min:sec',
        'H:i'   => 'H:min'
    );
    public static $types = array(
        'value'       => 'Valeur brute',
        'syntaxe'     => 'Syntaxe personnalisée',
        'callback'    => 'Affichage personnalisé',
        'array_value' => 'Valeur prédéfinie',
        'int'         => 'Nombre entier',
        'decimal'     => 'Nombre décimal',
        'percent'     => 'Pourcentage',
        'money'       => 'Valeur monétaire',
        'password'    => 'Mot de passe',
        'date'        => 'Date',
        'time'        => 'Heure',
        'datetime'    => 'Date et heure',
        'timer'       => 'Durée',
        'yes_no'      => 'OUI/NON',
        'check'       => 'OUI/NON (icônes)',
        'ref'         => 'Reférence objet',
        'nom'         => 'Nom objet',
        'ref_nom'     => 'Référence et nom objet',
        'nom_url'     => 'Lien objet',
        'card'        => 'Mini-fiche objet',
        'color'       => 'color',
        'json'        => 'Ensemble de sous-données'
    );
    public static $types_per_data_types = array(
        'values'     => array('array_value'/* , 'syntaxe' */),
        'string'     => array('value'/* , 'syntaxe' */),
        'text'       => array('value'),
        'html'       => array('value'),
        'password'   => array('password'/* , 'syntaxe' */),
        'int'        => array('value', 'int'/* , 'syntaxe' */),
        'float'      => array('value', 'decimal'/* , 'syntaxe' */),
        'bool'       => array('value', 'yes_no', 'check'/* , 'syntaxe' */),
        'qty'        => array('value', 'decimal'/* , 'syntaxe' */),
        'money'      => array('value', 'money'/* , 'syntaxe' */),
        'percent'    => array('value', 'percent'/* , 'syntaxe' */),
        'id'         => array('value'/* , 'syntaxe' */),
        'id_parent'  => array('value', 'ref', 'nom', 'nom_url', 'card'/* , 'syntaxe' */),
        'id_object'  => array('value', 'ref', 'nom', 'nom_url', 'card'/* , 'syntaxe' */),
        'items_list' => array('value'),
        'color'      => array('value', 'color'/* , 'syntaxe' */),
        'json'       => array('value', 'json'),
        'date'       => array('value', 'date'),
        'time'       => array('value', 'time'),
        'datetime'   => array('value', 'datetime')
    );
    public static $syntaxe_allowed_data_types = array('string', 'text', 'html', 'password', 'int', 'float', 'bool', 'qty', 'money', 'percent', 'color', 'date', 'time', 'datetime');
    public static $type_params_def = array(
        'syntaxe'     => array(
            'syntaxe'      => array('default' => '<value>'),
            'replacements' => array('data_type' => 'array', 'default' => array('value' => 'Valeur'))
        ),
        'callback'    => array(
            'method' => array('required' => true, 'default' => ''),
            'params' => array('data_type' => 'array', 'default' => array(), 'compile' => 1)
        ),
        'array_value' => array(
            'values'    => array('data_type' => 'array'),
            'value'     => array('data_type' => 'bool', 'default' => 0, 'label' => 'Afficher l\'identifiant'),
            'icon'      => array('data_type' => 'bool', 'default' => 1, 'label' => 'Afficher l\'icône'),
            'icon_only' => array('data_type' => 'bool', 'default' => 0),
            'label'     => array('data_type' => 'bool', 'default' => 1, 'label' => 'Afficher l\'intitulé'),
            'color'     => array('data_type' => 'bool', 'default' => 1, 'label' => 'Couleur')
        ),
        'int'         => array(
            'spaces'     => array('data_type' => 'bool', 'default' => 0, 'label' => 'Espaces entre les milliers'),
            'red_if_neg' => array('data_type' => 'bool', 'default' => 0, 'label' => 'Valeurs négatives en rouge'),
            'truncate'   => array('data_type' => 'bool', 'default' => 0, 'label' => 'Affichage tronqué par multiples de 1000 (ex: 3K pour 3000)')
        ),
        'decimal'     => array(
            'spaces'       => array('data_type' => 'bool', 'default' => 0, 'label' => 'Espaces entre les milliers'),
            'red_if_neg'   => array('data_type' => 'bool', 'default' => 0, 'label' => 'Valeurs négatives en rouge'),
            'truncate'     => array('data_type' => 'bool', 'default' => 0, 'label' => 'Affichage tronqué par multiples de 1000 (ex: 3K pour 3000)'),
            'separator'    => array('default' => ',', 'label' => 'Séparateur décimal'),
            'decimals'     => array('data_type' => 'int', 'default' => 2, 'label' => 'Nombre de décimales max', 'min' => 0, 'max' => 'none'),
            'round_points' => array('data_type' => 'bool', 'default' => 0, 'label' => 'indicateur d\'arrondi (...)'),
        ),
        'percent'     => array(
            'spaces'       => array('data_type' => 'bool', 'default' => 0, 'label' => 'Espaces entre les milliers'),
            'red_if_neg'   => array('data_type' => 'bool', 'default' => 0, 'label' => 'Valeurs négatives en rouge'),
            'truncate'     => array('data_type' => 'bool', 'default' => 0, 'label' => 'Affichage tronqué par multiples de 1000 (ex: 3K pour 3000)'),
            'separator'    => array('default' => ',', 'label' => 'Séparateur décimal'),
            'decimals'     => array('data_type' => 'int', 'default' => 2, 'label' => 'Nombre de décimales max', 'min' => 0, 'max' => 'none'),
            'round_points' => array('data_type' => 'bool', 'default' => 0, 'label' => 'indicateur d\'arrondi (...)'),
            'symbole'      => array('data_type' => 'bool', 'default' => 1, 'label' => 'Afficher le symbole %')
        ),
        'money'       => array(
            'spaces'       => array('data_type' => 'bool', 'default' => 1, 'label' => 'Espaces entre les milliers'),
            'red_if_neg'   => array('data_type' => 'bool', 'default' => 0, 'label' => 'Valeurs négatives en rouge'),
            'truncate'     => array('data_type' => 'bool', 'default' => 0, 'label' => 'Affichage tronqué par multiples de 1000 (ex: 3K pour 3000)'),
            'separator'    => array('default' => ',', 'label' => 'Séparateur décimal'),
            'decimals'     => array('data_type' => 'int', 'default' => 2, 'label' => 'Nombre de décimales max', 'min' => 0, 'max' => 'none'),
            'round_points' => array('data_type' => 'bool', 'default' => 0, 'label' => 'indicateur d\'arrondi (...)'),
            'symbole'      => array('data_type' => 'bool', 'default' => 1, 'label' => 'Afficher le symbole monétaire')
        ),
        'password'    => array(
            'hide' => array('data_type' => 'bool', 'default' => 1, 'label' => 'Masquer les caractères')
        ),
        'date'        => array(
            'format' => array('default' => 'd / m / Y', 'values' => array())
        ),
        'time'        => array(
            'format' => array('default' => 'H:i:s', 'values' => array())
        ),
        'datetime'    => array(
            'format'    => array('default' => 'd / m / Y H:i:s', 'values' => array()),
            'show_hour' => array('data_type' => 'bool', 'default' => 0, 'label' => 'Afficher l\'heure')
        ),
        'yes_no'      => array(
            'yes_label' => array('default' => 'OUI', 'label' => 'Valeur affichée pour OUI'),
            'no_label'  => array('default' => 'NON', 'label' => 'Valeur affichée pour NON')
        ),
        'ref '        => array(
            'object'     => array('type' => 'object'),
            'card'       => array(),
            'modal_view' => array()
        ),
        'nom'         => array(
            'object'     => array('type' => 'object'),
            'card'       => array(),
            'modal_view' => array()
        ),
        'ref_nom'     => array(
            'object'     => array('type' => 'object'),
            'card'       => array(),
            'modal_view' => array()
        ),
        'nom_url'     => array(
            'object'        => array('type' => 'object'),
            'syntaxe'       => array(),
            'with_icon'     => array('data_type' => 'bool', 'default' => null),
            'with_status'   => array('data_type' => 'bool', 'default' => null),
            'card'          => array(),
            'external_link' => array('data_type' => 'bool', 'default' => null),
            'modal_view'    => array()
        ),
        'card'        => array(
            'card'   => array('default' => 'default'),
            'object' => array('type' => 'object')
        ),
        'color'       => array(
            'color' => array('data_type' => 'bool', 'default' => 1, 'label' => 'Afficher un échantillon de la couleur'),
            'code'  => array('data_type' => 'bool', 'default' => 1, 'label' => 'Afficher le code couleur')
        ),
        'json'        => array(
            'foldable' => array('data_type' => 'bool', 'default' => 1, 'label' => 'Sections dépliables'),
            'open'     => array('data_type' => 'bool', 'default' => 1, 'label' => 'Sections dépliables ouvertes par défaut')
        )
    );

    public function __construct(BimpObject $object, $name, $path, $field_name, &$field_params, $value = null, $options = null)
    {
        $this->params_def['type'] = array();
        $this->object = $object;
        $this->name = $name;
        $this->field_name = $field_name;
        $this->field_params = $field_params;
        $this->value = $value;

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        if (!$this->name) {
            $this->name = 'default';
        }

        if (!$this->isObjectValid()) {
            $this->addError('Objet invalide');
        } else {
            if (!$path) {
                if ($object->config->isDefined('fields/' . $field_name . '/displays/' . $name)) {
                    $path = 'fields/' . $field_name . '/displays/' . $name;
                } elseif ($object->config->isDefined('fields/' . $field_name . '/display/' . $name)) {
                    $path = 'fields/' . $field_name . '/display/' . $name;
                }
            } elseif (!$object->config->isDefined($path)) {
                $path = '';
            }

            $this->params = self::getObjectFieldDisplayParams($object, $field_name, $this->name);
            $this->config_path = $path;
        }

        // Pas d'appel au constructeur parent car fonctionnement différent.

        if (is_array($options)) {
            $this->setDisplayOptions($options);
        }

        $current_bc = $prev_bc;
    }

    // Getters: 

    public static function getObjectFieldDefaultDisplayType(BimpObject $object, $field_name, $bc_field = null)
    {
        $type = 'value';

        if (is_a($object, 'BimpObject')) {
            if ($object->field_exists($field_name)) {
                if (is_null($bc_field)) {
                    $bc_field = new BC_Field($object, $field_name);
                }

                if ($bc_field->isOk()) {
                    if ($object->config->isDefined('fields/' . $field_name . '/displays/default')) {
                        return 'default';
                    }

                    $data_type = $bc_field->getParam('type', 'string');

                    if ($data_type === 'items_list') {
                        $data_type = $bc_field->getParam('items_data_type', 'string');
                    }

                    $values_data = $bc_field->getValuesArrayData();

                    if ($values_data['has_values']) {
                        $type = 'array_value';
                    } else {
                        switch ($data_type) {
                            case 'string':
                            case 'text' :
                            case 'html':
                            case 'id':
                                $type = 'value';
                                break;

                            case 'qty':
                                $decimals = BimpTools::getArrayValueFromPath($bc_field->params, 'decimals', 0);

                                if ($decimals > 0) {
                                    $type = 'decimal';
                                } else {
                                    $type = 'int';
                                }
                                break;

                            case 'float':
                                $type = 'decimal';
                                break;

                            case 'bool':
                                $type = 'yes_no';
                                break;

                            case 'id_parent':
                            case 'id_object':
                                $type = 'nom_url';
                                break;

                            case 'password':
                            case 'int':
                            case 'color':
                            case 'json':
                            case 'date':
                            case 'time':
                            case 'datetime':
                            case 'money':
                            case 'percent':
                            case 'items_list':
                                $type = $bc_field->params ['type'];
                                break;
                        }
                    }
                }
            }
        }

        return $type;
    }

    public static function getObjectFieldDisplayTypesArray(BimpObject $object, $field_name, $bc_field = null)
    {
        $types = array();

        if (is_a($object, 'BimpObject')) {
            if (is_null($bc_field) && $object->field_exists($field_name)) {
                $bc_field = new BC_Field($object, $field_name);
            }

            if (!is_null($bc_field)) {
                if ($bc_field->hasValuesArray()) {
                    $data_type = 'values';
                } else {
                    $data_type = $bc_field->getParam('type', 'string');

                    if ($data_type === 'items_list') {
                        $data_type = $bc_field->getParam('items_data_type', 'string');
                    }
                }
            } else {
                $data_type = 'string';
            }

            if ($object->config->isDefined('fields/' . $field_name . '/displays')) {
                $displays = $object->config->get('fields/' . $field_name . '/displays', array(), false, 'array');

                if (!empty($displays)) {
                    foreach ($displays as $display_name => $display_params) {
                        $types[$display_name] = $object->getConf('fields/' . $field_name . '/displays/' . $display_name . '/label', $display_name, true);
                    }
                }
            }

            if (array_key_exists($data_type, self::$types_per_data_types)) {
                foreach (self::$types_per_data_types[$data_type] as $type) {
                    if (!isset($types[$type])) {
                        $types[$type] = self::$types[$type];
                    }
                }
            }

            // Ajustements: 
            foreach ($types as $type => $type_label) {
                $check = true;
                switch ($type) {
                    case 'card':
                        if (is_null($bc_field)) {
                            $check = false;
                        } else {
                            if ($object->config->isDefined('fields/' . $field_name . '/object')) {
                                $child = $bc_field->getLinkedObject();

                                if (is_a($child, 'BimpObject')) {
                                    $cards = BC_Card::getObjectCardsArray($child);
                                    if (empty($cards)) {
                                        $check = false;
                                    }
                                }
                            } else {
                                $check = false;
                            }
                        }
                        break;
                }

                if (!$check) {
                    unset($types[$type]);
                }
            }
        }

        return $types;
    }

    public static function getObjectFieldDisplayParamsDefinitionsByType(BimpObject $object, $field_name, $type = '', $bc_field = null)
    {
        $defs = array();

        if (is_a($object, 'BimpObject')) {
            if (is_null($bc_field) && $object->field_exists($field_name)) {
                $bc_field = new BC_Field($object, $field_name);
            }

            if (!$type && !is_null($bc_field)) {
                $type = self::getObjectFieldDefaultDisplayType($object, $field_name, $bc_field);
            }

            if ($type && isset(self::$type_params_def[$type])) {
                $defs = self::$type_params_def[$type];

                // Ajustement des définitions de paramètres en fonction des paramètre du field et du BimpObject: 
                if (!empty($defs) && !is_null($bc_field)) {
                    $data_type = $bc_field->params['type'];
                    $values_data = $bc_field->getValuesArrayData();

                    switch ($type) {
                        case 'syntaxe':
                            if ($values_data['has_values']) {
                                $defs['syntaxe']['default'] = ($values_data['has_icon'] ? '<icon>' : '') . '<label>';
                                $defs['replacements']['default']['value'] = 'Identifiant';
                                $defs['replacements']['default']['icon'] = 'Icône';
                                $defs['replacements']['default']['label'] = 'Valeur';

                                if ($values_data['has_classes']) {
                                    $defs['color'] = array('data_type' => 'bool', 'default' => 1);
                                }
                            } else {
                                switch ($data_type) {
                                    case 'id_parent':
                                    case 'id_object':
                                        $default_syntaxe = '';
                                        $defs['replacements']['default'] = array();

                                        $child_name = $bc_field->getParam('object', '');
                                        if ($child_name) {
                                            $child = $object->getChildObject($child_name);

                                            if (is_a($child, 'BimpObject')) {
                                                $defs['replacements']['default']['value'] = 'ID ' . $child->getLabel('of_the');
                                                if ($child->config->isDefined('nom_url/syntaxe')) {
                                                    $default_syntaxe = $child->getConf('nom_url/syntaxe');
                                                } else {
                                                    $ref_prop = $child->getRefProperty();
                                                    $name_prop = $child->getNameProperty();

                                                    if ($ref_prop) {
                                                        $defs['replacements']['default'][$ref_prop] = $child->getConf('fields/' . $ref_prop . '/label', $ref_prop, true);
                                                        $default_syntaxe .= ($default_syntaxe ? ' - ' : '<' . $ref_prop . '>');
                                                    }

                                                    if ($name_prop) {
                                                        $defs['replacements']['default'][$name_prop] = $child->getConf('fields/' . $name_prop . '/label', $name_prop, true);
                                                        $default_syntaxe .= ($default_syntaxe ? ' - ' : '<' . $name_prop . '>');
                                                    }

                                                    if (!$default_syntaxe) {
                                                        $defs['replacements']['default']['value'] = 'ID ' . $child->getLabel('of_the');
                                                        $default_syntaxe = '#<value>';
                                                    }
                                                }
                                            } else {
                                                if (method_exists($child, 'getFullName')) {
                                                    $defs['replacements']['default']['full_name'] = 'Nom complet';
                                                    $default_syntaxe = '<full_name>';
                                                } else {
                                                    $defs['replacements']['default']['value'] = 'ID';
                                                    $default_syntaxe .= '#<value>';
                                                }
                                            }
                                        }
                                        break;
                                }
                            }
                            break;

                        case 'array_value':
                            if (!$values_data['has_icon']) {
                                if (isset($defs['icon'])) {
                                    unset($defs['icon']);
                                }
                                if (isset($defs['icon_only'])) {
                                    unset($defs['icon_only']);
                                }
                            }
                            if (!$values_data['has_classes']) {
                                if (isset($defs['color'])) {
                                    unset($defs['color']);
                                }
                            }
                            break;

                        case 'int':
                        case 'decimal':
                        case 'percent':
                        case 'money':
                            $unsigned = $bc_field->getParam('unsigned', 0);
                            $min = $bc_field->getParam('min', 'none');

                            if ($unsigned || ($min !== 'none' && (int) $min >= 0)) {
                                if (isset($defs['red_if_neg'])) {
                                    unset($defs['red_if_neg']);
                                }
                            }
                            break;

                        case 'ref':
                        case 'nom':
                        case 'nom_url':
                        case 'card':
                            // todo...
                            break;

                        case 'list_item':
                            break;
                    }
                }
            }
        }

        return $defs;
    }

    public static function getObjectFieldDisplayParams(BimpObject $object, $field_name, $display_name = '', $bc_field = null, &$defs = array())
    {
        $params = array();

        if (is_a($object, 'BimpObject')) {
            if ($object->field_exists($field_name)) {
                if (is_null($bc_field)) {
                    $bc_field = new BC_Field($object, $field_name);
                }

                if ($bc_field->isOk()) {
                    $config_path = '';
                    $type = '';

                    if ($display_name) {
                        if ($object->config->isDefined('fields/' . $field_name . '/displays/' . $display_name)) {
                            $type = $object->getConf('fields/' . $field_name . '/displays/' . $display_name . '/type', 'value');
                            $config_path = 'fields/' . $field_name . '/displays/' . $display_name;
                        } elseif (array_key_exists($display_name, self::$types)) {
                            $type = $display_name;
                        }
                    }

                    if (!$type) {
                        $type = self::getObjectFieldDefaultDisplayType($object, $field_name, $bc_field);
                    }


                    $defs = self::getObjectFieldDisplayParamsDefinitionsByType($object, $field_name, $type, $bc_field);
                    if (!empty($defs)) {
                        if ($config_path) {
                            $params = self::fetchParamsStatic($object->config, $config_path, $defs);
                        } else {
                            $params = self::getDefaultParams($defs);
                        }
                    }

                    $params['type'] = $type;
                }
            }
        }

        return $params;
    }

    public static function getObjectFieldDisplayOptionsInputs(BimpObject $object, $field_name, $display_name = '', $values = array(), $bc_field = null)
    {
        $options = array();

        if (is_a($object, 'BimpObject')) {
            $defs = array();
            $params = self::getObjectFieldDisplayParams($object, $field_name, $display_name, $bc_field, $defs);

            $type = BimpTools::getArrayValueFromPath($params, 'type', '');

            if (!empty($defs)) {
                $data_type = $bc_field->getParam('type', 'string');
                $defs = self::getObjectFieldDisplayParamsDefinitionsByType($object, $field_name, $type, $bc_field);

                switch ($type) {
                    case 'syntaxe':
                        break;

                    case 'array_value':
                    case 'int':
                    case 'decimal':
                    case 'percent':
                    case 'money':
                    case 'password':
                    case 'yes_no':
                    case 'date':
                    case 'time':
                    case 'datetime':
                    case 'color':
                    case 'json':
                        foreach ($defs as $param_name => $def) {
                            if (BimpTools::getArrayValueFromPath($def, 'type', 'value') === 'value') {
                                $label = BimpTools::getArrayValueFromPath($def, 'label', '');
                                if ($label) {
                                    $param_values = BimpTools::getArrayValueFromPath($def, 'values', array());
                                    $default_value = BimpTools::getArrayValueFromPath($values, $param_name, BimpTools::getArrayValueFromPath($param_name, $param_name, BimpTools::getArrayValueFromPath($def, 'default', null)));
                                    $input = '';

                                    if (!empty($param_values)) {
                                        $input = BimpInput::renderInput('select', $param_name, $default_value, array(
                                                    'options' => $param_values
                                        ));
                                    } else {
                                        $param_data_type = BimpTools::getArrayValueFromPath($def, 'data_type', 'string');
                                        switch ($param_data_type) {
                                            case 'string':
                                                $input = BimpInput::renderInput('text', $param_name, $default_value);
                                                break;

                                            case 'bool':
                                                $input = BimpInput::renderInput('toggle', $param_name, (int) $default_value);
                                                break;

                                            case 'int':
                                                $input = BimpInput::renderInput('qty', $param_name, (int) $default_value, array(
                                                            'data' => array(
                                                                'min' => BimpTools::getArrayValueFromPath($def, 'min', 'none'),
                                                                'max' => BimpTools::getArrayValueFromPath($def, 'max', 'none')
                                                            )
                                                ));
                                                break;
                                        }
                                    }

                                    if ($input) {
                                        $options[] = array(
                                            'label'      => $label,
                                            'input_name' => $param_name,
                                            'input'      => $input
                                        );
                                    }
                                }
                            }
                        }
                        break;

                    case 'ref':
                    case 'nom':
                    case 'ref_nom':
                    case 'nom_url':
                    case 'card':
                        $child_name = $bc_field->getParam('object', '');
                        if ($child_name) {
                            $child = $object->getChildObject($child_name);
                            if (is_a($child, 'BimpObject')) {
                                if ($type === 'card') {
                                    $types = BC_Card::getObjectCardsArray($child);
                                    if (count($types) > 1) {
                                        $value = BimpTools::getArrayValueFromPath($values, 'card', (isset($types['default']) ? 'default' : ''));
                                        $options[] = array(
                                            'label'      => 'Mini-fiche',
                                            'input_name' => 'card',
                                            'input'      => BimpInput::renderInput('select', 'card', $value, array(
                                                'options' => $types
                                            ))
                                        );
                                    }

                                    $options[] = array(
                                        'label'      => 'Boutons',
                                        'input_name' => 'view_btn',
                                        'input'      => BimpInput::renderInput('toggle', 'view_btn', BimpTools::getArrayValueFromPath($values, 'view_btn', 1))
                                    );
                                } else {
                                    // todo...   
                                }
                            }
                        }
                        break;
                }
            }
        }

        return $options;
    }

    // Options d'affichage: 

    public function setDisplayOptions($options)
    {
        if (is_array($options)) {
            $this->params = BimpTools::overrideArray($this->params, $options);
        }
    }

    // Rendus HTML: 

    public function renderHtml()
    {
        $html = parent::renderHtml();

        if (count($this->errors)) {
            return $html;
        }

        if (is_null($this->value)) {
            $this->value = '';
        }

        if ($this->object->isDolObject()) {
            if (!$this->object->dol_field_exists($this->field_name)) {
                return '';
            }
        }

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        if ($this->field_params['type'] === 'items_list' && $this->params['type'] !== 'callback') {
            if (!is_array($this->value)) {
                $current_bc = $prev_bc;
                return BimpRender::renderAlerts('Valeurs invalides');
            }
            if (!count($this->value)) {
                $current_bc = $prev_bc;
                return '';
            }
            $field_params = $this->field_params;
            $field_params['type'] = $this->field_params['items_data_type'];

            $bc_display = new BC_Display($this->object, $this->name, '', $this->field_name, $field_params, '');
            $bc_display->params = $this->params;

            $fl = true;
            foreach ($this->value as $item_value) {
                $bc_display->value = $item_value;
                if (!$fl) {
                    if ($this->no_html) {
                        $html .= "\n";
                    } else {
                        $html .= '<br/>';
                    }
                } else {
                    $fl = false;
                }
                $html .= $bc_display->renderHtml();
            }

            $current_bc = $prev_bc;
            return $html;
        }

        if (($this->value === '') && ($this->params['type'] !== 'callback')) {
            $html .= '';
        } else {
            $type = $this->getParam('type', 'value');
            switch ($type) {
                case 'value':
                default:
                    if ($this->no_html) {
                        $html .= BimpTools::replaceBr($this->value);
                    } else {
                        $html .= str_replace("\n", '<br/>', $this->value);
                    }                    
                    break;

                case 'syntaxe':
                    $syntaxe = $this->getParam('syntaxe', '<value>');
                    $replacements = $this->getParam('replacements', array('value'));

                    if (is_array($replacements)) {
                        foreach ($replacements as $replacement => $replacement_label) {
                            $replace_by = '';
                            if (is_array($this->value)) {
                                if (isset($this->value[$replacement])) {
                                    $replace_by = $this->value[$replacement];
                                }
                            } elseif ($replacement === 'value') {
                                $replace_by = $this->value;
                            }
                            $syntaxe = str_replace('<' . $replacement . '>', $replace_by, $syntaxe);
                        }
                    }

                    $html .= $syntaxe;
                    break;

                case 'callback':
                    $method = $this->params['method'];
                    $params = $this->params['params'];

                    if (method_exists($this->object, $method)) {
                        if (empty($params)) {
                            $current_bc = $prev_bc;
                            return $this->object->{$method}();
                        } else {
                            $current_bc = $prev_bc;
                            return call_user_func_array(array(
                                $this->object, $method
                                    ), $params);
                        }
                    } else {
                        $current_bc = $prev_bc;

                        BimpCore::addlog('Erreur de configuration YML', Bimp_Log::BIMP_LOG_ERREUR, 'config', $this->object, array(
                            'Erreur' => 'Méthode "' . $method . '" absente de l\'objet'
                        ));

                        return BimpRender::renderAlerts('Erreur de configuration: méthode "' . $method . '" absente de l\'objet "' . get_class($this->object) . '"');
                    }
                    break;

                case 'array_value':
                    if (isset($this->params['values']) && !is_null($this->params['values'])) {
                        $array = $this->params['values'];
                    } elseif (isset($this->field_params['values']) && !is_null($this->field_params['values'])) {
                        $array = $this->field_params['values'];
                    } else {
                        $array = array();
                    }

                    $check = false;

                    if (isset($array[$this->value])) {
                        if (is_array($array[$this->value])) {
                            $data = $array[$this->value];

                            if ($this->no_html) {
                                if (isset($data['label'])) {
                                    $html .= $data['label'];
                                    $check = true;
                                }
                            } else {
                                $popover = '';
                                $text = '';
                                $classes = '';

                                $icon_only = (int) $this->getParam('icon_only', ($this->name === 'icon' ? 1 : 0));
                                $display_icon = (isset($data['icon']) ? (int) $this->getParam('icon', 1) : 0);
                                $display_value = (int) $this->getParam('value', 0);
                                $display_label = (int) $this->getParam('label', 1);
                                $color = (isset($data['classes']) ? (int) $this->getParam('color', 1) : 0);

                                if (($icon_only || ($display_icon && !$display_label)) && isset($data['icon'])) {
                                    $popover = BimpTools::getArrayValueFromPath($data, 'label', $this->value);
                                }

                                if ($display_icon && (string) $data['icon']) {
                                    $text .= BimpRender::renderIcon($data['icon'], 'iconLeft');
                                    $check = true;
                                }

                                if ($display_value) {
                                    $text .= $this->value;
                                    $check = true;
                                }

                                if ($display_label && isset($data['label']) && (string) $data['label']) {
                                    if ($display_value) {
                                        $text .= ' - ';
                                    }

                                    $text .= $data['label'];
                                    $check = true;
                                }

                                if ($color) {
                                    if (is_array($data['classes'])) {
                                        $classes = implode(' ', $data['classes']);
                                    } elseif ((string) $data['classes']) {
                                        $classes = $data['classes'];
                                    }
                                }

                                if ($text) {
                                    if ($popover) {
                                        $classes .= ($classes ? ' ' : '') . 'bs-popover';
                                    }
                                    $html .= '<span' . ($classes ? ' class="' . $classes . '"' : '');
                                    if ($popover) {
                                        $html .= BimpRender::renderPopoverData($popover);
                                    }
                                    $html .= '>';
                                    $html .= $text;
                                    $html .= '</span>';
                                }
                            }
                        } else {
                            $html .= $array[$this->value];
                            $check = true;
                        }
                    }

                    if (!$check) {
                        if (!$this->no_html) {
                            $html .= '<p class="alert alert-warning">valeur non trouvée pour l\'identifiant "' . $this->value . '"</p>';
                        } else {
                            $html .= $this->value;
                        }
                    }
                    break;

                case 'int':
                case 'decimal':
                case 'percent':
                case 'money':
                    $spaces = (int) $this->getParam('spaces', ($type === 'money' ? 1 : 0));
                    $red = (int) $this->getParam('red_if_neg', 0);
                    $truncate = (int) $this->getParam('truncate', 0);
                    $sep = (in_array($type, array('decimal', 'percent', 'money')) ? $this->getParam('separator', '.') : '.');
                    $decimals = (int) (in_array($type, array('decimal', 'percent', 'money')) ? $this->getParam('decimals', 2) : 0);
                    $symbole = (int) (in_array($type, array('percent', 'money')) ? $this->getParam('symbole', 1) : 0);
                    $round_points = (int) (in_array($type, array('decimal', 'percent', 'money')) ? $this->getParam('round_points', 1) : 0);

                    switch ($type) {
                        case 'int':
                            $decimals = 0;

                        case 'decimal':
                        case 'percent':
                            $html .= BimpTools::displayFloatValue($this->value, $decimals, $sep, $red, $truncate, $this->no_html, $round_points, $spaces);

                            if ($type === 'percent' && $symbole) {
                                $html .= ' %';
                            }
                            break;

                        case 'money':
                            $html .= BimpTools::displayMoneyValue($this->value, $symbole ? 'EUR' : '', $red, $truncate, $this->no_html, $decimals, $round_points, $sep, $spaces);
                            break;
                    }

                    break;

                case 'password':
                    if ($this->params['hide']) {
                        $html .= preg_replace('/./', '*', $this->value);
                    } else {
                        if ($this->no_html) {
                            $html .= $this->value;
                        } else {
                            $html .= htmlentities($this->value);
                        }
                    }
                    break;

                case 'date':
                    if ($this->value !== '0000-00-00') {
                        $format = $this->getParam('format', 'H:i:s');
                        $date = new DateTime($this->value);
                        if ($this->no_html) {
                            $html .= $date->format($format);
                        } else {
                            $html .= '<span class="date">' . $date->format($format) . '</span>';
                        }
                    }
                    break;

                case 'time':
                    if ($this->value !== '00:00:00') {
                        $format = $this->getParam('format', 'd / m / Y');
                        $time = new DateTime($this->value);
                        if ($this->no_html) {
                            $html .= $time->format($format);
                        } else {
                            $html .= '<span class="time">' . $time->format($format) . '</span>';
                        }
                    }
                    break;

                case 'datetime':
                    if ($this->value !== '0000-00-00 00:00:00') {
                        $format = $this->getParam('format', 'd / m / Y H:i:s');
                        $date = new DateTime($this->value);
                        if ($this->no_html) {
                            $html .= $date->format($format);
                        } else {
                            $html .= BimpTools::printDate($date, 'span', 'datetime', $this->params['format'], ($this->params['show_hour']) ? $this->params['format'] : 'd / m / Y');
                        }
                    }
                    break;

                case 'timer':
                    $html .= BimpTools::displayTimefromSeconds($this->value);
                    break;

                case 'yes_no':
                    $yes = $this->getParam('yes_label', 'OUI');
                    $no = $this->getParam('no_label', 'NON');
                    if ($this->no_html) {
                        if ((int) $this->value) {
                            $html .= $yes;
                        } else {
                            $html .= $no;
                        }
                    } else {
                        if ((int) $this->value) {
                            $html .= '<span class="success">' . $yes . '</span>';
                        } else {
                            $html .= '<span class="danger">' . $no . '</span>';
                        }
                    }
                    break;

                case 'check':
                    if ($this->no_html) {
                        if ((int) $this->value) {
                            $html .= 'OUI';
                        } else {
                            $html .= 'NON';
                        }
                        break;
                    } else {
                        if ((int) $this->value) {
                            $html .= '<span class="check_on"></span>';
                        } else {
                            $html .= '<span class="check_off"></span>';
                        }
                        break;
                    }

                case 'nom':
                case 'ref_nom':
                case 'nom_url':
                case 'ref':
                    if ($this->field_name === $this->object->getParentIdProperty()) {
                        $instance = $this->object->getParentInstance();
                        if (is_a($instance, 'BimpObject') && !$instance->isLoaded() && (int) $this->value) {
                            $instance = BimpCache::getBimpObjectInstance($instance->module, $instance->object_name, (int) $this->value);
                        }
                    } elseif (isset($this->field_params['object'])) {
                        $instance = $this->object->getChildObject($this->field_params['object']);
                        if (is_object($instance) && (int) $this->value && (!BimpObject::objectLoaded($instance) || (int) $instance->id !== (int) $this->value)) {
                            $instance->fetch((int) $this->value);
                        }
                    } else {
                        $instance = null;
                    }

                    if (BimpObject::objectLoaded($instance)) {
                        switch ($type) {
                            case 'ref':
                            case 'nom':
                            case 'ref_nom':
                                $ref = BimpObject::getInstanceRef($instance);
                                $nom = BimpObject::getInstanceNom($instance);

                                if (in_array($type, array('ref', 'ref_nom'))) {
                                    $html .= $ref;
                                }
                                if (in_array($type, array('nom', 'ref_nom'))) {
                                    $html .= ($html ? ' - ' : '') . $nom;
                                }

                                if (!$this->no_html && $this->params['card']) {
                                    $card = new BC_Card($this->object, $this->field_params['object'], $this->params['card']);
                                    if ($card->isOk()) {
                                        $card_content = $card->renderHtml();
                                        $html .= '<span class="objectIcon bs-popover"';
                                        $html .= BimpRender::renderPopoverData($card_content, 'bottom', 'true');
                                        $html .= '>';
                                        $html .= '<i class="fa fa-question-circle"></i>';
                                        $html .= '</span>';
                                    }
                                    unset($card);
                                }
                                if (!$this->no_html && method_exists($instance, 'getNomExtraIcons')) {
                                    $html .= $instance->getNomExtraIcons();
                                }
                                break;

                            case 'nom_url':
                                if (!$this->no_html) {
                                    $params = array();
                                    foreach (BimpConfigDefinitions::$nom_url as $param_name => $defs) {
                                        if (isset($this->params[$param_name]) && !is_null($this->params[$param_name])) {
                                            $params[$param_name] = $this->params[$param_name];
                                        }
                                    }

                                    $html .= BimpObject::getInstanceNomUrl($instance, $params);
                                    break;
                                } else {
                                    if (method_exists($instance, "getFullName")) {
                                        global $langs;
                                        $html .= $instance->getFullName($langs);
                                    } elseif (method_exists($instance, "getName")) {
                                        $html .= $instance->getName();
                                    }
                                    break;
                                }
                        }
                    } elseif ((int) $this->value) {
                        $html .= $this->object->renderChildUnfoundMsg($this->field_name, $instance);
                    }
                    break;

                case 'card':
                    if ($this->field_name === $this->object->getParentIdProperty()) {
                        $instance = $this->object->getParentInstance();
                    } elseif (isset($this->field_params['object'])) {
                        $instance = $this->object->getChildObject($this->field_params['object']);
                        if (is_object($instance) && (int) $this->value && (!BimpObject::objectLoaded($instance) || (int) $instance->id !== (int) $this->value)) {
                            $instance->fetch((int) $this->value);
                        }
                    } else {
                        $instance = null;
                    }

                    if (BimpObject::objectLoaded($instance)) {
                        $card = new BC_Card($this->object, $this->field_params['object'], $this->params['card']);
                        if (isset($this->params['view_btn'])) {
                            $card->params['view_btn'] = (int) $this->params['view_btn'];
                        }
                        $html .= $card->renderHtml();
                    } elseif ((int) $this->value) {
                        $html .= $this->object->renderChildUnfoundMsg($this->field_name, $instance);
                    }
                    unset($card);
                    break;

                case 'color':
                    $html .= '#' . $this->value;
                    break;

                case 'json':
                    if ($this->no_html) {
                        if (is_array($this->value)) {
                            $html .= BimpRender::renderRecursiveArrayContent($this->value, array(
                                        'no_html' => true
                            ));
                        } else {
                            $html .= strip_tags((string) $this->value);
                        }
                    } else {
                        if (is_array($this->value)) {
                            $html .= BimpRender::renderRecursiveArrayContent($this->value, array(
                                        'foldable' => $this->params['foldable'],
                                        'open'     => $this->params['open']
                            ));
                        } else {
                            $html .= (string) $this->value;
                        }
                    }
                    break;
            }
        }

        if ($this->no_html) {
            $html = BimpTools::replaceBr($html);
            $html = (string) strip_tags($html);
        }

        $current_bc = $prev_bc;
        return $html;
    }
}

BC_Display::$type_params_def['date']['format']['values'] = BC_Display::$dates_formats;
BC_Display::$type_params_def['time']['format']['values'] = BC_Display::$times_formats;
