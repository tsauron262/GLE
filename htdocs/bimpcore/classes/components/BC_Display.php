<?php

class BC_Display extends BimpComponent
{

    public static $type = 'display';
    public static $config_required = false;
    public $field_name = null;
    public $field_params = null;
    public $value = null;
    public static $type_params_def = array(
        'syntaxe'     => array(
            'syntaxe' => array('default' => '<value>')
        ),
        'card'        => array(
            'card'   => array('default' => 'default'),
            'object' => array('type' => 'object')
        ),
        'nom'         => array(
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
        )
    );

    public function __construct(BimpObject $object, $name, $path, $field_name, &$field_params, $value)
    {
        $this->params_def['type'] = array();
        $this->field_name = $field_name;
        $this->field_params = $field_params;
        $this->value = $value;

        if (isset($field_params['values']) && !is_null($field_params['values']) && count($field_params['values'])) {
            $this->params_def['type']['default'] = 'array_value';
        } elseif (isset($field_params['type']) && !is_null($field_params['type'])) {
            switch ($field_params['type']) {
                case 'id_object':
                    switch ($name) {
                        case 'nom':
                        case 'nom_url':
                        case 'card':
                            $this->params_def['type']['default'] = $name;
                            break;
                    }
                    break;

                case 'time':
                case 'date':
                case 'datetime':
                case 'money':
                case 'percent':
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

        if ($this->value === '') {
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
                    case 'nom_url':
                        if ($this->field_name === $this->object->getParentIdProperty()) {
                            $instance = $this->object->getParentInstance();
                        } elseif (isset($this->field_params['object'])) {
                            $instance = $this->object->getChildObject($this->field_params['object']);
                        } else {
                            $instance = null;
                        }

                        if (!is_null($instance)) {
                            switch ($this->params['type']) {
                                case 'nom':
                                    $html .= BimpObject::getInstanceNom($instance);
                                    if ($this->params['card']) {
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
                                    break;

                                case 'nom_url':
                                    $html .= BimpObject::getInstanceNomUrl($instance);
                                    if ($this->params['external_link']) {
                                        if (isset($this->field_params['object'])) {
                                            $url = $this->object->getChildObjectUrl($this->field_params['object'], $instance);
                                        } else {
                                            $url = BimpObject::getInstanceUrl($instance);
                                        }
                                        if ($url) {
                                            $html .= '<span class="objectIcon" onclick="window.open(\'' . $url . '\')">';
                                            $html .= '<i class="fa fa-external-link"></i>';
                                            $html .= '</span>';
                                            if (is_null($this->params['modal_view'])) {
                                                $onclick = 'loadModalObjectPage($(this), \'' . $url . '\', \'page_modal\', \'' . addslashes(BimpObject::getInstanceNom($instance)) . '\')';
                                                $html .= '<span class="objectIcon" onclick="' . $onclick . '">';
                                                $html .= '<i class="fa fa-eye"></i>';
                                                $html .= '</span>';
                                            }
                                        }
                                    }
                                    break;
                            }
                        }
                        break;

                    case 'card':
                        $card = new BC_Card($this->object, $this->field_params['object'], $this->params['card']);
                        $html .= $card->renderHtml();
                        unset($card);
                        break;

                    case 'check':
                        if ((int) $this->value) {
                            $html .= '<span class="check_on"></span>';
                        } else {
                            $html .= '</span class="check_off"></span>';
                        }
                        break;

                    case 'yes_no':
                        if ((int) $this->value) {
                            $html .= '<span class="success">OUI</span>';
                        } else {
                            $html .= '</span class="danger">NON</span>';
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
                                if ($this->params['icon_only'] && isset($array[$this->value]['icon'])) {
                                    if (!isset($array[$this->value]['classes'])) {
                                        $array[$this->value]['classes'] = array();
                                    }
                                    $array[$this->value]['classes'] = array_merge($array[$this->value]['classes'], array('fa', 'fa-' . $array[$this->value]['icon'], 'iconLeft', 'bs-popover'));
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
                                        $html .= '<i class="fa fa-' . $array[$this->value]['icon'] . ' iconLeft"></i>';
                                    }
                                    if (isset($array[$this->value]['label'])) {
                                        $html .= $array[$this->value]['label'];
                                    }
                                    $html .= '</span>';
                                    $check = true;
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
                            $html .= '<span class="time">' . $time->format($this->params['format']) . '</span>';
                        }
                        break;

                    case 'date':
                        if ($this->value !== '0000-00-00') {
                            $date = new DateTime($this->value);
                            $html .= '<span class="date">' . $date->format($this->params['format']) . '</span>';
                        }
                        break;

                    case 'datetime':
                        if ($this->value !== '0000-00-00 00:00:00') {
                            $date = new DateTime($this->value);
                            $html .= '<span class="datetime">' . $date->format($this->params['format']) . '</span>';
                        }
                        break;

                    case 'timer':
                        $html .= BimpTools::displayTimefromSeconds($this->value);
                        break;

                    case 'money':
                        $html .= BimpTools::displayMoneyValue($this->value, $this->field_params['currency']);
                        break;

                    case 'percent':
                        $html .= $this->value . ' %';
                        break;

                    case 'callback':
                        $method = $this->params['method'];
                        if (method_exists($this->object, $method)) {
                            $html .= $this->object->{$method}($this->value);
                        }
                        break;

                    case 'string':
                    default:
                        $html .= $this->value;
                        break;
                }
            }
        }

        return $html;
    }
}
