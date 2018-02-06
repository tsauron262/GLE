<?php

class BC_Search extends BimpComponent
{

    public static $type = 'field';
    public $search_value = null;

    public function __construct(BimpObject $object, $name, $search_value)
    {
        $this->params_def['has_serial'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['fields_search'] = array('data_type' => 'array', 'default' => array());
        $this->params_def['joins'] = array('type' => 'definitions', 'defs_type' => 'join', 'multiple' => 1);
        $this->params_def['list_name'] = array('default' => 'default');

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
    }

    public function searchItems()
    {
        $primary = $this->object->getPrimary();

        $filters = array(
            'or_search' => array('or' => array())
        );

        if (!is_array($this->params['fields_search'])) {
            $this->params['fields_search'] = array($this->params['fields_search']);
        }

        foreach ($this->params['fields_search'] as $field) {
            $filters['or_search']['or'][$field] = array(
                'part_type' => 'middle',
                'part'      => $this->search_value
            );
        }

        $items = $this->object->getList($filters, null, 1, $primary, 'ASC', 'array', array(
            $primary
                ), $this->params['joins']);

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
            $list[] = $item[$primary];
        }

        return $list;
    }

    public function renderHtml()
    {
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

        $list->addFieldFilterValue($primary, array(
            'IN' => implode(',', $items)
        ));
        $html .= $list->renderHtml();

        return $html;
    }
}
