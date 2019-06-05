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
    public static $type_params_def = array(
        'syntaxe'     => array(
            'syntaxe' => array('default' => '<value>')
        ),
        'card'        => array(
            'card'   => array('default' => 'default'),
            'object' => array('type' => 'object')
        ),
        'ref'         => array(
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
            'card'          => array(),
            'modal_view'    => array(),
            'external_link' => array('data_type' => 'bool', 'default' => 1)
        ),
        'array_value' => array(
            'values'    => array('data_type' => 'array'),
            'icon_only' => array('data_type' => 'bool', 'default' => 0)
        ),
        'time'        => array(
            'format' => array('default' => 'H:i:s')
        ),
        'date'        => array(
            'format' => array('default' => 'd / m / Y')
        ),
        'datetime'    => array(
            'format' => array('default' => 'd / m / Y H:i:s')
        ),
        'callback'    => array(
            'method' => array('required' => true, 'default' => '')
        ),
        'password'    => array(
            'hide' => array('data_type' => 'bool', 'default' => 1)
        )
    );

    public function __construct(BimpObject $object, $name, $path, $field_name, &$field_params, $value)
    {
        $this->params_def['type'] = array();
        $this->field_name = $field_name;
        $this->field_params = $field_params;
        $this->value = $value;

        if (in_array($name, array('nom', 'nom_url', 'card', 'ref'))) {
            $this->params_def['type']['default'] = $name;
        } elseif (isset($field_params['values']) && !is_null($field_params['values']) && count($field_params['values'])) {
            $this->params_def['type']['default'] = 'array_value';
        } elseif (isset($field_params['type']) && !is_null($field_params['type'])) {
            switch ($field_params['type']) {
                case 'id_object':
                case 'id_parent':
                    $this->params_def['type']['default'] = 'ref_nom';
                    break;

                case 'html':
                case 'time':
                case 'date':
                case 'datetime':
                case 'money':
                case 'percent':
                case 'qty':
                case 'password':
                    $this->params_def['type']['default'] = $field_params['type'];
                    break;

                case 'bool':
                    $this->params_def['type']['default'] = 'yes_no';
                    break;

                default:
                    $this->params_def['type']['default'] = 'string';
                    break;
            }
        }

        if (!$name) {
            if ($object->config->isDefined($path . '/default')) {
                $name = 'default';
            }
        }
        parent::__construct($object, $name, $path);
    }

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

        if ($this->field_params['type'] === 'items_list') {
            if (!is_array($this->value)) {
                return BimpRender::renderAlerts('Valeurs invalides');
            }
            if (!count($this->value)) {
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
            return $html;
        }

        if (($this->value === '') && ($this->params['type'] !== 'callback')) {
            $html .= '';
        } else {
            if (isset($this->params['type']) && !is_null($this->params['type'])) {
                switch ($this->params['type']) {
                    case 'syntaxe':
                        $syntaxe = isset($this->params['syntaxe']) ? $this->params['syntaxe'] : '<value>';
                        $syntaxe = str_replace('<value>', $this->value, $syntaxe);
                        $html .= $syntaxe;
                        break;

                    case 'nom':
                    case 'ref_nom':
                    case 'nom_url':
                    case 'ref':
                        $cache_key = ($this->no_html ? 'no_html' : 'html');
                        $cache_key .= '_' . $this->object->module . '_' . $this->object->object_name . '_' . $this->field_name . '_' . $this->value;

                        if (isset(self::$cache[$cache_key])) {
                            return self::$cache[$cache_key];
                        }

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
                            switch ($this->params['type']) {
                                case 'ref':
                                case 'nom':
                                case 'ref_nom':
                                    $ref = BimpObject::getInstanceRef($instance);
                                    $nom = BimpObject::getInstanceNom($instance);

                                    if (in_array($this->params['type'], array('ref', 'ref_nom'))) {
                                        $html .= $ref;
                                    }
                                    if (in_array($this->params['type'], array('nom', 'ref_nom'))) {
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
                                        $html .= BimpObject::getInstanceNomUrl($instance);
                                        $html .= BimpRender::renderObjectIcons($instance, (int) $this->params['external_link'], $this->params['modal_view']);
                                        break;
                                    } else {
                                        if (method_exists($instance, "getFullName")) {
                                            global $langs;
                                            $html .= $instance->getFullName($langs);
                                        } elseif (method_exists($instance, "getName"))
                                            $html .= $instance->getName();
                                        break;
                                    }
                            }
                            self::$cache[$cache_key] = $html;
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
                            $html .= $card->renderHtml();
                        } elseif ((int) $this->value) {
                            $html .= $this->object->renderChildUnfoundMsg($this->field_name, $instance);
                        }
                        unset($card);
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
                                $html .= '</span class="check_off"></span>';
                            }
                            break;
                        }

                    case 'yes_no':
                        if ($this->no_html) {
                            if ((int) $this->value) {
                                $html .= 'OUI';
                            } else {
                                $html .= 'NON';
                            }
                        } else {
                            if ((int) $this->value) {
                                $html .= '<span class="success">OUI</span>';
                            } else {
                                $html .= '</span class="danger">NON</span>';
                            }
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
                                if ($this->no_html) {
                                    if (isset($array[$this->value]['label'])) {
                                        $html .= $array[$this->value]['label'];
                                        $check = true;
                                    }
                                } else {
                                    if ($this->params['icon_only'] && isset($array[$this->value]['icon'])) {
                                        if (!isset($array[$this->value]['classes'])) {
                                            $array[$this->value]['classes'] = array();
                                        }
                                        $array[$this->value]['classes'] = array_merge($array[$this->value]['classes'], array(BimpRender::renderIconClass($array[$this->value]['icon']), 'iconLeft', 'bs-popover'));
                                        $html .= '<div style="text-align: center">';
                                        $html .= '<i ' . BimpRender::displayTagAttrs($array[$this->value]);
                                        $html .= ' data-toggle="popover"';
                                        $html .= ' data-trigger="hover"';
                                        $html .= ' data-content="' . $array[$this->value]['label'] . '"';
                                        $html .= ' data-container="body"';
                                        $html .= ' data-placement="top"></i>';
                                        $html .= '</div>';
                                        $check = true;
                                    } else {
                                        $html .= '<span';
                                        $html .= BimpRender::displayTagAttrs($array[$this->value]);
                                        $html .= '>';
                                        if (isset($array[$this->value]['icon'])) {
                                            $html .= '<i class="' . BimpRender::renderIconClass($array[$this->value]['icon']) . ' iconLeft"></i>';
                                        }
                                        if (isset($array[$this->value]['label'])) {
                                            $html .= $array[$this->value]['label'];
                                        }
                                        $html .= '</span>';
                                        $check = true;
                                    }
                                }
                            } else {
                                $html .= $array[$this->value];
                                $check = true;
                            }
                        }

                        if (!$check) {
                            $html .= '<p class="alert alert-warning">valeur non trouvée pour l\'identifiant "' . $this->value . '"</p>';
                        }
                        break;

                    case 'time':
                        if ($this->value !== '00:00:00') {
                            $time = new DateTime($this->value);
                            if ($this->no_html) {
                                $html .= $time->format($this->params['format']);
                            } else {
                                $html .= '<span class="time">' . $time->format($this->params['format']) . '</span>';
                            }
                        }
                        break;

                    case 'date':
                        if ($this->value !== '0000-00-00') {
                            $date = new DateTime($this->value);
                            if ($this->no_html) {
                                $html .= $date->format($this->params['format']);
                            } else {
                                $html .= '<span class="date">' . $date->format($this->params['format']) . '</span>';
                            }
                        }
                        break;

                    case 'datetime':
                        if ($this->value !== '0000-00-00 00:00:00') {
                            $date = new DateTime($this->value);
                            if ($this->no_html) {
                                $html .= $date->format($this->params['format']);
                            } else {
                                $html .= '<span class="datetime">' . $date->format($this->params['format']) . '</span>';
                            }
                        }
                        break;

                    case 'timer':
                        $html .= BimpTools::displayTimefromSeconds($this->value);
                        break;

                    case 'money':
                        if ($this->no_html) {
                            $html .= price($this->value);
                            switch ($this->field_params['currency']) {
                                case 'EUR':
                                    $html .= ' €';
                                    break;
                            }
                        } else {
                            $html .= BimpTools::displayMoneyValue($this->value, $this->field_params['currency']);
                        }
                        break;

                    case 'percent':
                        $html .= $this->value . ' %';
                        break;

                    case 'qty':
                        $html .= (float) $this->value;
                        break;

                    case 'callback':
                        $method = $this->params['method'];
                        if (method_exists($this->object, $method)) {
                            $html .= $this->object->{$method}($this->value);
                        }
                        break;

                    case 'json':
                        $html = BimpRender::renderAlerts('Erreur technique: champ de type JSON non affichable');
                        break;

                    case 'password':
                        if ($this->params['hide']) {
                            $html .= preg_replace('/./', '*', $this->value);
                        } else {
                            $html .= htmlentities($this->value);
                        }
                        break;

                    case 'string':
                    case 'html':
                    default:
                        if ($this->no_html) {
                            $value = BimpTools::replaceBr($this->value);
                            $html .= (string) strip_tags($value);
                        } else {
                            $html .= (string) $this->value;
                        }

                        break;
                }
            }
        }

        return $html;
    }
}
