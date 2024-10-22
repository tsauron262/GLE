<?php

namespace BC_V2;

class BC_HtmlInput extends BC_Input
{

    protected static $definitions = null;
    public static $component_name = 'BC_HtmlInput';

    public static function setAttributes(&$params, &$attributes = array())
    {
        parent::setAttributes($params, $attributes);
        self::addClass($attributes, self::$component_name);
    }

    protected static function renderHtml(&$params, $content = '', &$errors = array())
    {
        $html = '';

        if (!class_exists('DolEditor')) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
        }

        $doleditor = new \DolEditor($params['name'], $params['value'], '', 160, 'dolibarr_details', '', false, true, true, ROWS_4, '90%');

        if (\BimpCore::isContextPrivate() && $params['hashtags']) {
            $params['input_classes'][] = 'allow_hashtags';

            $html .= '<p class="inputHelp" style="display: inline-block">';
            $html .= 'Vous pouvez utiliser le symbole # pour inclure un lien objet';
            $html .= '</p>';
        }

        $doleditor->extra_class = implode(' ', $params['input_classes']);

        $html .= $doleditor->Create(1);

        return parent::renderHtml($params, $html, $errors);
    }
}
