<?php

namespace BC_V2;

class BC_TextInput extends BC_Input
{

    protected static $definitions = null;
    public static $component_name = 'BC_TextInput';

    public static function setAttributes(&$params, &$attributes = array())
    {
        parent::setAttributes($params, $attributes);
        self::addClass($attributes, self::$component_name);
    }

    protected static function renderHtml(&$params, $content = '', &$errors = array())
    {
        $html = '';
        $is_context_private = \BimpCore::isContextPrivate();
        $has_addons = ($params['addon_left'] || $params['addon_right']);
        $is_qty = self::is_a('BC_QtyInput');

        $value = htmlentities($params['value']);

        if ($is_context_private && $params['hashtags']) {
            $params['input_classes'][] = 'allow_hashtags';
        }

        $attrs = array(
            'type'  => 'text',
            'name'  => $params['name'],
            'value' => $value
        );

        if ($params['placeholder']) {
            $attrs['placeholder'] = $params['placeholder'];
        }

        if ($params['no_autocorrect']) {
            $attrs['autocorrect'] = 'off';
            $attrs['autocapitalize'] = 'none';
        }

        if ($is_qty) {
            $html .= '<div class="qtyInputContainer">';
            $html .= '<span class="qtyDown">';
            $html .= '<i class="fa fa-minus"></i>';
            $html .= '</span>';
        }

        if ($has_addons) {
            $html .= '<div class="inputGroupContainer">';
            $html .= '<div class="input-group">';
            if ($params['addon_left']) {
                $html .= '<span class="input-group-addon">' . $params['addon_left'] . '</span>';
            }
        }

        $html .= '<input ' . \Bimprender::renderTagAttrs(array(
                    'attr'    => $attrs,
                    'classes' => $params['input_classes'],
                    'styles'  => $params['input_styles']
                )) . '/>';

        if ($has_addons) {
            if (isset($params['addon_right']) && $params['addon_right']) {
                $html .= '<span class="input-group-addon">' . $params['addon_right'] . '</span>';
            }
            $html .= '</div>';
            $html .= '</div>';
        }

        if ($is_qty) {
            $html .= '<span class="qtyUp">';
            $html .= '<i class="fa fa-plus"></i>';
            $html .= '</span>';
            $html .= '</div>';
        }

        if ($is_context_private) {
            if ($params['hashtags']) {
                $html .= \BimpRender::renderInfoIcon('Vous pouvez utiliser le symbole # pour inclure un lien objet', 'fas_hashtag');
            }

            if ($params['scanner']) {
                $onclick = 'var $parent = $(this).findParentByClass(\'inputContainer\');';
                $onclick .= 'if ($.isOk($parent)) {BIS.openModal($parent.find(\'input[name=' . $params['name'] . ']\'));}';
                $html .= \BimpRender::renderRowButton('Scanner code-barres / Qr-Code', 'fas_camera', $onclick);
            }
        }

        $html .= $content;

        if (count($params['values'])) {
            $html .= '<div style="margin-top: 15px">';
            $html .= '<select class="input_values ' . $params['name'] . '_input_values"';
            $html .= ' data-field_name="' . $params['name'] . '"';
            $html .= ' data-allow_custom="' . (int) $params['allow_custom'] . '"';
            $html .= '>';
            foreach ($params['values'] as $val => $label) {
                $html .= '<option value="' . $val . '"' . (($val == $value) ? ' selected' : '') . '>' . $label . '</option>';
            }
            $html .= '</select>';
            $html .= '</div>';
        }

        if (count($params['possible_values'])) {
            if (self::is_a('BC_NumberInput')) {
                $html .= '<div class="input_possible_values buttonsContainer" data-field_name="' . $params['name'] . '" data-replace_cur_value="1">';
                $html .= '<span class="small">Remplir automatiquement avec une des valeurs ci-dessous : </span><br/>';
                foreach ($params['possible_values'] as $val) {
                    $html .= '<span class="btn btn-small btn-default input_possible_value">' . $val . '</span>';
                }
                $html .= '</div>';
            } else {
                $html .= '<ul class="input_possible_values" data-field_name="' . $params['name'] . '" data-replace_cur_value="0">';
                foreach ($params['possible_values'] as $val) {
                    $html .= '<li class="input_possible_value">' . $val . '</li>';
                }
                $html .= '</ul>';
            }
        }

        return parent::renderHtml($params, $html, $errors);
    }
}
