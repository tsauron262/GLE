<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcontract/objects/BContract_contrat.class.php';

class BContract_contratLine extends BContract_contrat {

    public function createDolObject(&$errors) {
        $data = $this->getDataArray();
        $contrat = $this->getParentInstance();
        $produit = $this->getInstance('bimpcore', 'Bimp_Product', $data['fk_product']);
        if (!BimpObject::objectLoaded($contrat)) {
            $errors[] = 'L\'id du contrat ' . $contrat->id . ' n\'éxiste pas';
            return 0;
        }
        
        $instance = $this->getParentInstance();
                
        if(is_null($data['desc']) || empty($data['desc'])) {
            $description = $produit->getData('label');
        } else {
            $description = $data['description'];
        }
        
        if ($contrat->dol_object->addLine($description, $produit->getData('price'), 1, $produit->getData('tva_tx'), 0, 0, $produit->id, $data['remise_percent'], $instance->getData('date_start'), $instance->getEndDate()->format('Y-m-d'), 'HT', 0.0, 0, null, 0, Array('fk_contrat' => $contrat->id)) > 0) {
            //$errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($contrat));
            
        }
        
        return 0;
    }
    
    public function deleteDolObject(&$errors) {
        global $user;
        $contrat = $this->getParentInstance();
        if($contrat->dol_object->deleteLine($this->id, $user) > 0) {
            return ['success' => 'Ligne du contrat supprimée avec succès'];
        }
        
    }
    
    public function updateAssociations() {}

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
