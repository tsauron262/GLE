<?php

class BDS_ReportObjectData extends BimpObject
{

    public function displayObjLabel()
    {
        $html = '';

        $module = $this->getData('obj_module');
        $name = $this->getData('obj_name');

        if ($module && $name) {
            $instance = BimpObject::getInstance($module, $name);

            if (is_a($instance, $name)) {
                $icon = $instance->params['icon'];

                if ($icon) {
                    $html .= BimpRender::renderIcon($icon, 'iconLeft');
                }

                $html .= BimpTools::ucfirst($instance->getLabel('name_plur'));
            } else {
                $html .= 'Objet "' . $name . '"';
            }
        }

        return $html;
    }

    public function displayQtyBadge($field_name)
    {
        if ($this->field_exists($field_name)) {
            $value = (int) $this->getData($field_name);

            $class = 'default';

            if ($value > 0) {
                switch ($field_name) {
                    case 'nbProcessed':
                        $class = 'info';
                        break;

                    case 'nbUpdated':
                    case 'nbCreated':
                    case 'nbActivated':
                        $class = 'success';
                        break;

                    case 'nbIgnored':
                        $class = 'warning';
                        break;

                    case 'nbDeleted':
                    case 'nbDeactivated':
                        $class = 'danger';
                        break;
                }
            }

            return '<span class="badge badge-' . $class . '">' . $value . '</span>';
        }

        return '';
    }
}
