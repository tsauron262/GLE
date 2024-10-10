<?php

namespace BC_V2;

class BC_WidgetData extends BC_ObjectData
{

    public $widget = null;

    public function __construct($widget_or_id_widget)
    {
        if (is_a($widget_or_id_widget, 'BW_Widget')) {
            $this->widget = $widget_or_id_widget;
        } elseif (is_int($widget_or_id_widget)) {
            $this->widget = BimpCache::getBimpObjectInstance('bimpwidget', 'BW_Widget', $widget_or_id_widget);
        }
        
        if (BimpObject::objectLoaded($this->widget)) {
            $object = $this->widget->getObj();
            
        }
    }
}
