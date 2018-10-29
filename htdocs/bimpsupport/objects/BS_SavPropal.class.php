<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/Bimp_Propal.class.php';

class BS_SavPropal extends Bimp_Propal
{

    public function getLinesListHeaderExtraBtn()
    {
        $buttons = array();
        
        // Remise globale: 
        if ($this->isActionAllowed('setRemiseGlobale') && $this->canSetAction('setRemiseGlobale')) {
            $buttons[] = array(
                'label'       => 'Remise globale',
                'icon_before' => 'percent',
                'classes'     => array('btn', 'btn-default'),
                'attr'        => array(
                    'onclick' => $this->getJsActionOnclick('setRemiseGlobale', array('remise_globale' => (float) $this->getData('remise_globale')), array(
                        'form_name' => 'remise_globale'
                    ))
                )
            );
        }

        return $buttons;
    }
}
