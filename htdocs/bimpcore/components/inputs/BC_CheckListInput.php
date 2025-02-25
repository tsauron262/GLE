<?php

namespace BC_V2;

class BC_CheckListInput extends BC_Input
{

    protected static $definitions = null;
    public static $component_name = 'BC_CheckListInput';

    public static function setAttributes(&$params, &$attributes = array())
    {
        parent::setAttributes($params, $attributes);
        self::addClass($attributes, self::$component_name);
        self::addClass($attributes, 'check_list_container');
    }

    protected static function renderHtml(&$params, $content = '', &$errors = array(), &$debug = array())
    {
        $html = '';

        if (empty($params['items'])) {
            $html = \BimpRender::renderAlerts('Aucun élément diponible', 'warning');
        } else {
            $values = $params['value'];

            if (!is_array($values)) {
                if (is_string($values)) {
                    $values = explode(',', $values);
                } else {
                    $values = array($values);
                }
            }

            $nb_selected = 0;

            if (count($params['items']) > 1) {
                if ($params['search_input']) {
                    $html .= '<div class="check_list_search_input">';
                    $html .= '<span class="searchIcon">' . BimpRender::renderIcon('fas_search', 'iconLeft') . '</span>';
                    $html .= BC_TextInput::render(array(
                                'name'   => $params['name'] . '_search_input',
                                'inline' => 1
                    ));
                    $html .= '</div>';
                }
                if ($params['select_all_buttons']) {
                    $html .= \BimpInput::renderToggleAllCheckboxes('$(this).finParentByClass(\'BC_CheckListInput\')', '.' . $params['name'] . '_check');
                }
            }
            $i = 1;
            foreach ($params['items'] as $idx => $item) {
                $child_selected = false;
                $html .= self::renderItem($params, $item, $idx, $i, $nb_selected, $values, $child_selected);
            }

            if ($params['max'] !== 'none' || $params['max_input_name']) {
                $max = ($params['max'] !== 'none') ? (int) $params['max'] : 0;
                $html .= '<p class="small">Max: <span class="check_list_max_label">' . $max . '</span></p>';

                $html .= '<div class="check_list_max_alert"' . ($nb_selected > $max ? '' : ' style="display: none"') . '>';
                $html .= \BimpRender::renderAlerts('Veuillez désélectionner <span class="check_list_nb_items_to_unselect">' . ($nb_selected - $max) . '</span> élément(s)', 'danger');
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        return parent::renderHtml($params, $html, $errors, $debug);
    }

    protected static function renderItem(&$params, $item, $idx, &$i, &$nb_selected, $values = array(), &$child_selected = false)
    {
        $html = '';

        $item_children = array();

        if (is_array($item)) {
            $item_value = isset($item['value']) ? $item['value'] : $idx;
            $item_label = isset($item['label']) ? $item['label'] : 'n°' . $idx;
            $item_children = isset($item['children']) ? $item['children'] : array();
            $group_selectable = isset($item['selectable']) ? (int) $item['selectable'] : 1;
            $group_open = isset($item['open']) ? (int) $item['open'] : 1;
            $select_all_btn = isset($item['select_all_btn']) ? (int) $item['select_all_btn'] : 1;
        } else {
            $item_value = $idx;
            $item_label = (string) $item;
        }
        $i++;
        $rand = rand(111111, 999999);
        $input_id = $params['name'] . '_' . $i . '_' . $rand;

        if (!empty($item_children)) {
            $children_html = '';

            $has_child_selected = false;
            foreach ($item_children as $child_idx => $child_item) {
                $children_html .= self::renderItem($params, $child_item, $child_idx, $i, $nb_selected, $values, $has_child_selected);
            }
            if ($has_child_selected) {
                $child_selected = true;
            }

            $html .= '<div class="check_list_group ' . ($has_child_selected || $group_open ? 'open' : 'closed') . '">';

            if ($group_selectable) {
                $html .= '<input type="checkbox" name="' . $params['name'] . '[]" value="' . $item_value . '" id="' . $input_id . '"';
                if (in_array($item_value, $values)) {
                    $child_selected = true;
                    $nb_selected++;
                    $html .= ' checked';
                }
                $html .= ' class="' . $params['name'] . '_check check_list_item_input check_list_group_input"/>';
            }

            $html .= '<div class="check_list_group_caption' . ($group_selectable ? ' selectable' : '') . '">';
            $html .= '<span class="check_list_group_title">';
            $html .= $item_label;
            $html .= '</span>';
            $html .= '</div>';

            $html .= '<div class="check_list_group_items">';

            if ($select_all_btn && count($item_children) > 0) {
                $html .= \BimpInput::renderToggleAllCheckboxes('$(this).finParentByClass(\'check_list_group\')', '.' . $params['name'] . '_check');
            }

            $html .= $children_html;

            $html .= '</div>';
            $html .= '</div>';
        } else {
            $html .= '<div class="check_list_item"' . ($params['inline'] ? ' style="display: inline-block; margin-left: 12px"' : '') . '>';
            $html .= '<input type="checkbox" name="' . $params['name'] . '[]" value="' . $item_value . '" id="' . $input_id . '"';
            if (in_array($item_value, $values)) {
                $child_selected = true;
                $nb_selected++;
                $html .= ' checked';
            }
            $html .= ' class="' . $params['name'] . '_check check_list_item_input"';
            if ($params['onchange']) {
                $html .= ' onchange="' . $params['onchange'] . '"';
            }
            $html .= '/>';
            $html .= '<label for="' . $input_id . '">';
            $html .= $item_label;
            $html .= '</label>';
            $html .= '</div>';
        }

        return $html;
    }
}
