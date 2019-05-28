<?php

class BMP_EventCalcMontant extends BimpObject
{

    public function getCalcs_montantsArray()
    {
        $items = array();

        $event = $this->getParentInstance();
        if (!is_null($event)) {
            $current_items = array();
            foreach ($event->getChildrenObjects('calcs_montants') as $calcMontant) {
                $current_items[] = (int) $calcMontant->getData('id_calc_montant');
            }

            $instance = BimpObject::getInstance($this->module, 'BMP_CalcMontant');
            foreach ($instance->getList(array(
                'active' => 1
            )) as $cm) {
                if (!in_array((int) $cm['id'], $current_items) &&
                        !array_key_exists((int) $cm['id'], $items)) {
                    $items[(int) $cm['id']] = $cm['label'];
                }
            }
        }

        return $items;
    }

    public function isEventEditable()
     {
         $event = $this->getParentInstance();
         if (!is_null($event) && $event->isLoaded()) {
             return $event->isEditable();
         }
         
         return 0;
     }
     
    public function isEditable($force_edit = false)
    {
        if (!$this->isEventEditable()) {
            return 0;
        }
        
        if (isset($this->id) && $this->id) {
            $calcMontant = $this->getChildObject('calc_montant');
            if (isset($calcMontant->id) && $calcMontant->id) {
                return (int) $calcMontant->getData('editable');
            }
        }
        return 0;
    }

    public function isDeletable($force_delete = false)
    {
        if (!$this->isEventEditable()) {
            return 0;
        }
        
        if (isset($this->id) && $this->id) {
            $calcMontant = $this->getChildObject('calc_montant');
            if (isset($calcMontant->id) && $calcMontant->id) {
                return ($calcMontant->getData('required') ? 0 : 1);
            }
        }
        return 0;
    }

    public function getCreateFormDisplay()
    {
        if ($this->isEventEditable && count($this->getCalcs_montantsArray())) {
            return 'default';
        }

        return '';
    }

    public function displayName()
    {
        $calcMontant = $this->getChildObject('calc_montant');
        return $calcMontant->getData('label');
    }

    public function displaySource()
    {
        $calcMontant = $this->getChildObject('calc_montant');
        if ((int) $calcMontant->getData('type_source') === 3) {
            return BimpTools::displayMoneyValue((float) $this->getData('source_amount'), 'EUR');
        }
        return $calcMontant->displaySource();
    }

    public function displayTarget()
    {
        $calcMontant = $this->getChildObject('calc_montant');
        return $calcMontant->displayTarget();
    }

    public function getDefaultPercent()
    {
        $id_calc_montant = $this->getData('id_calc_montant');
        if (!is_null($id_calc_montant)) {
            $calc_montant = $this->getChildObject('calc_montant');
            if ($calc_montant->isLoaded()) {
                return (float) $calc_montant->getData('percent');
            }
        }
        return 0;
    }

    public function getAmount($id_coprod = 0)
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        $cm = $this->getChildObject('calc_montant');
        $id_event = (int) $this->getData('id_event');

        if ((int) $cm->getData('type_source') === 3) {
            $source_amount = (float) $this->getData('source_amount');
        } else {
            $source_amount = $cm->getSourceAmount($id_event, $id_coprod);
        }

        $percent = $this->getData('percent');

        if (is_null($percent) || !$source_amount) {
            return 0;
        }

        return (float) ($source_amount * ($percent / 100));
    }

    public function create()
    {
        $id_calc_montant = BimpTools::getValue('id_calc_montant', $this->getData('id_calc_montant'));
        if (!is_null($id_calc_montant) && $id_calc_montant) {
            $this->set('id_calc_montant', $id_calc_montant);
            
            $calc_montant = $this->getChildObject('calc_montant');
            if ($calc_montant->isLoaded()) {
                $this->set('percent', (float) $calc_montant->getData('percent'));
                
                if ((int) $calc_montant->getData('type_source') === 3) {
                    $this->set('source_amount', (float) $calc_montant->getData('source_amount'));
                }
            }
        }

        return parent::create();
    }
}
