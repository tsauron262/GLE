<?php

class BDS_ProcessCronOption extends BimpObject
{

    // Getters array: 

    public function getAvailableOptionsArray()
    {
        $options = array();

        $cron = $this->getParentInstance();

        if (BimpObject::objectLoaded($cron)) {
            $current_options = $cron->getOptions(false);

            $operation = $cron->getChildObject('operation');

            if (BimpObject::objectLoaded($operation)) {
                $options_list = $operation->getAssociatesObjects('options');

                foreach ($options_list as $opt) {
                    if (!array_key_exists($opt->getData('name'), $current_options)) {
                        $options[(int) $opt->id] = $opt->getData('label');
                    }
                }
            }
        }

        return $options;
    }

    // Rendus HTML:

    public function renderValueInput()
    {
        $html = '';

        if ((int) $this->getData('id_option')) {
            $option = $this->getChildObject('option');

            if (BimpObject::objectLoaded($option)) {
                $html .= $option->renderOptionInput(0, 'value', $this->getData('value'));
            } else {
                $html .= '<span class="warning">L\'option #' . $this->getData('id_option') . ' n\'existe pas</span><input type="hidden" value="' . $this->getData('value') . '" name="value"/>';
            }
        } else {
            $html .= '<span class="warning">Option non spécifiée</span><input type="hidden" value="' . $this->getData('value') . '" name="value"/>';
        }

        return $html;
    }
}
