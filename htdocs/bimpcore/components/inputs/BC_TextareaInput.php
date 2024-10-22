<?php

namespace BC_V2;

class BC_TextareaInput extends BC_Input
{

    protected static $definitions = null;
    public static $component_name = 'BC_TextareaInput';

    public static function setAttributes(&$params, &$attributes = array())
    {
        parent::setAttributes($params, $attributes);
        self::addClass($attributes, self::$component_name);
    }

    protected static function renderHtml(&$params, $content = '', &$errors = array())
    {
        $is_context_private = \BimpCore::isContextPrivate();

        if ($is_context_private && $params['hashtags']) {
            $params['input_classes'][] = 'allow_hashtags';
        }

        if ($params['auto_expand']) {
            $params['input_classes'][] = 'auto_expand ';
        }
        if ($params['note']) {
            $params['input_classes'][] = 'note';
        }
        if ($params['tab_key_as_enter']) {
            $params['input_classes'][] = 'tab_key_as_enter';
        }

        if (isset($params['max_chars']) && $params['max_chars']) {
            $html .= '<p class="smallInfo">Max ' . $params['max_chars'] . ' caractères</p>';
        }

        if ($is_context_private) {
            if ($params['scanner']) {
                $onclick = 'var $parent = $(this).findParentByClass(\'inputContainer\');';
                $onclick .= 'if ($.isOk($parent)) {BIS.openModal($parent.find(\'textarea[name=' . $params['name'] . ']\'));}';
                $html .= \BimpRender::renderRowButton('Scanner code-barres / Qr-Code', 'fas_camera', $onclick);
            }
            if ($params['hashtags']) {
                $html .= '<p class="inputHelp" style="display: inline-block">';
                $html .= 'Vous pouvez utiliser le symbole # pour inclure un lien objet';
                $html .= '</p>';
            }
        }

        $attrs = array(
            'rows' => $params['rows'],
            'name' => $params['name']
        );

        if ($params['max_chars']) {
            $attrs['maxlength'] = $params['max_chars'];
        }

        $html .= '<textarea' . \Bimprender::renderTagAttrs(array(
                    'attr'    => $attrs,
                    'classes' => $params['input_classes'],
                    'styles'  => $params['input_styles'],
                    'data'    => array(
                        'min_rows' => $params['rows']
                    )
                )) . '/>' . $params['value'] . '</textarea>';

        return parent::renderHtml($params, $html, $errors);
    }
}
