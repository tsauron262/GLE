<?php

class BC_Search extends BimpComponent
{

    public $component_name = 'Recherche';
    public static $type = 'field';
    public $search_value = null;

    public function __construct(BimpObject $object, $name, $search_value)
    {
        $this->params_def['has_serial'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['list_name'] = array('default' => 'default');

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $this->search_value = $search_value;

        if (!$name || $name === 'default') {
            if ($object->config->isDefined('search')) {
                $path = 'search';
            } elseif ($object->config->isDefined('searches/default')) {
                $path = 'searches';
                $name = 'default';
            }
        } else {
            $path = 'searches';
        }

        parent::__construct($object, $name, $path);

        if (is_null($this->search_value) || !$this->search_value) {
            $this->errors[] = 'Aucun terme de recherche spécifié';
        }

        if (!count($this->errors)) {
            if (!$this->object->can("view")) {
                $this->errors[] = 'Vous n\'avez pas la permission de voir ' . $this->object->getLabel('the_plur');
            }
        }

        $current_bc = $prev_bc;
    }

    public function searchItems()
    {
        $list = array();

        if ($this->isOk() && is_a($this->object, 'BimpObject')) {
            $items = $this->object->getSearchResults($this->name, $this->search_value, array(
                'max_results' => 0
            ));

            if (!count($items)) {
                if ((int) $this->params['has_serial']) {
                    if (preg_match('/^[sS](.*)$/', $this->search_value, $matches)) {
                        $this->search_value = $matches[1];
                        return $this->searchItems();
                    }
                }
            }

            $list = array();

            foreach ($items as $item) {
                $list[] = (int) $item['id'];
            }
        }

        return $list;
    }

    public function renderHtml()
    {
        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $html = parent::renderHtml();

        $items = $this->searchItems();

        if (count($items) === 1) {
            if ($this->object->fetch((int) $items[0])) {
                $url = BimpObject::getInstanceUrl($this->object);
                if ($url) {
                    echo '<script>';
                    echo 'window.location = \'' . $url . '\';';
                    echo '</script>';
                    exit;
                }
            }
        }

        $primary = $this->object->getPrimary();

        $title = 'Résulats de recherche ' . $this->object->getLabel('of_plur') . ' pour "' . $this->search_value . '"';

        $list = new BC_ListTable($this->object, $this->params['list_name'], 1, null, $title, 'search');
        $list->params['enable_search'] = 0;
        $list->params['bulk_actions'] = array();
        $list->params['add_form_name'] = '';
        $list->params['positions'] = 0;
        $list->params['checkboxes'] = 0;
        $list->params['add_object_row'] = 0;
        $list->params['n'] = 50;

        $list->addFieldFilterValue($primary, array(
            'IN' => implode(',', $items)
        ));
        $html .= $list->renderHtml();

        $current_bc = $prev_bc;
        return $html;
    }
}
