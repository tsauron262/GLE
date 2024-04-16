<?php

class ListController extends BimpController
{

    public function renderHtml()
    {
        $module = BimpTools::getValue('module', $this->module, 'aZ09');
        $object_name = BimpTools::getValue('object_name', '', 'aZ09');
        $filters = json_decode(BimpTools::getValue('filters', '', 'json_nohtml'));
        $list_name = BimpTools::getValue('list_name', 'default', 'aZ09');

        if (!$object_name) {
            return BimpRender::renderAlerts('Type d\'objet asbent');
        }

        $instance = BimpObject::getInstance($module, $object_name);

        if (!is_a($instance, $object_name)) {
            return BimpRender::renderAlerts('L\'objet "' . $object_name . '" n\'existe pas');
        }

        $list = new BC_ListTable($instance, $list_name);

        if (is_array($filters)) {
            foreach ($filters as $field => $value) {
                $list->addFieldFilterValue($field, $value);
            }
        }

        return $list->renderHtml();
    }

    public function getPageTitle()
    {
        $module = BimpTools::getValue('module', $this->module, 'aZ09');
        $object_name = BimpTools::getValue('object_name', '', 'aZ09');

        if ($object_name) {
            $instance = BimpObject::getInstance($module, $object_name);

            if (is_a($instance, $object_name)) {
                return 'Liste des ' . $instance->getLabel('name_plur');
            }
        }

        return 'Liste';
    }
}
