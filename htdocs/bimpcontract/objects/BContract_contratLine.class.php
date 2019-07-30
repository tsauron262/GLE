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
        
        $instance = $this->getParentInstance();
                
        if ($data['nb_materiel'] > 0) {
            if (!empty($data['serials'])) {
                
                $array_serails = explode(',', $data['serials']);                
                if ($data['nb_materiel'] > count($array_serails)) {
                    $errors[] = $data['nb_materiel'] . " matériels couverts pour " . count($array_serails) . ' numéros de série rentrés';
                    return 0;
                }
            } else {
                $errors[] = "Vous devez rentré " . $data['nb_materiel'] . " numéro de série";
                return 0;
            }
        }
        
        if(is_null($data['desc'])) {
            $description = $produit->getData('label');
        } else {
            $description = $data['description'];
        }
        
        if ($contrat->dol_object->addLine($description, $produit->getData('price'), $data['qty'], $produit->getData('tva_tx'), 0, 0, $produit->id, $data['remise_percent'], $instance->getData('date_start'), $instance->getEndDate()->format('Y-m-d'), 'HT', 0.0, 0, null, 0, Array('serials' => $data['serials'], 'nb_materiel' => $data['nb_materiel'], 'fk_contrat' => $contrat->id)) <= 0) {
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
