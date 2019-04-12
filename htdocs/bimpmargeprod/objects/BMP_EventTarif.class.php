<?php

class BMP_EventTarif extends BimpObject
{

    public function isParentEditable()
    {
        $event = $this->getParentInstance();
        if (BimpObject::objectLoaded($event)) {
            return (int) $event->isInEditableStatus();
        }

        return 0;
    }

    public function isCreatable($force_create = false)
    {
        return (int) $this->isParentEditable();
    }

    public function isEditable($force_edit = false)
    {
        return (int) $this->isParentEditable();
    }

    public function isDeletable($force_delete = false)
    {
        if (!$this->isLoaded()) {
            return 0;
        }
        $event = $this->getParentInstance();
        if (BimpObject::objectLoaded($event)) {
            $vente_instance = BimpObject::getInstance($this->module, 'BMP_EventBillets');
            $qty = (int) $vente_instance->getListCount(array(
                        'id_event' => (int) $event->id,
                        'id_tarif' => (int) $this->id,
                        'quantity' => array(
                            'operator' => '>',
                            'value'    => 0
                        )
            ));
            if ($qty > 0) {
                return 0;
            }
        }

//        return (int) $this->isParentEditable();
        return 1;
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        if (in_array($field, array('name', 'amount', 'previsionnel'))) {
            return (int) $this->isParentEditable();
        }

        return 1;
    }
}
