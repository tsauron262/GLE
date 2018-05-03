<?php

class BS_SavPret extends BimpObject
{

    // Overrides: 
    
    public function create()
    {
        $sav = $this->getParentInstance();
        if (BimpObject::objectLoaded($sav)) {
            $id_client = (int) $sav->getData('id_client');
            if (!$id_client) {
                return array('Aucun client enregistré pour ce SAV');
            }
            $this->set('id_client', $id_client);
        } else {
            return array('SAV non spécifié');
        }
        $errors = parent::create();

        if ($this->isLoaded()) {
            $this->set('ref', 'PRET' . $this->id);
            $this->update();
        }

        return $errors;
    }
}
