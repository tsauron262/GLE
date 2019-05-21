<?php

class BContract_contratLine extends BimpObject {

    protected function createDolObject(&$errors) {
        global $db;
        $data = $this->getDataArray();
        $contrat = $this->getParentInstance();
        $produit = $this->getInstance('bimpcore', 'Bimp_Product', $data['fk_product']);
        if (!BimpObject::objectLoaded($contrat)) {
            $errors[] = 'L\'id du contrat ' . $contrat->id . ' n\'éxiste pas';
            return 0;
        }
        if ($contrat->dol_object->addLine($data['description'], $produit->getData('price'), $data['qty'], $produit->getData('tva_tx'), 0, 0, $produit->id, $data['remise_percent'], date('Y-m-d'), date('Y-m-d')) <= 0) {
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($contrat));
            return 0;
        }

        return 1;
    }
    
    protected function updateDolObject(&$errors) {
        $data = $this->getDataArray();
        $contrat = $this->getParentInstance();
        
        return 0;
    }

    public function canCreate() {
        $contrat = $this->getParentInstance();
        if ($contrat->getData('statut') > 0) {
            return 0;
        }
        return 1;
    }

    public function canDelete() {
        return $this->canCreate();
    }

    public function canEdit() {
        return $this->canCreate();
    }

}
