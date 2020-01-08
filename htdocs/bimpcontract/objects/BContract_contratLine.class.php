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

        if (is_null($data['desc']) || empty($data['desc'])) {
            $description = $produit->getData('label');
        } else {
            $description = $data['description'];
        }

        if ($contrat->dol_object->addLine($description, $produit->getData('price'), $data['qty'], $produit->getData('tva_tx'), 0, 0, $produit->id, $data['remise_percent'], $instance->getData('date_start'), $instance->getEndDate()->format('Y-m-d'), 'HT', 0.0, 0, null, 0, Array('fk_contrat' => $contrat->id)) > 0) {
            //$errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($contrat));
        }

        return 0;
    }

    public function deleteDolObject(&$errors) {
        global $user;
        $contrat = $this->getParentInstance();
        if ($contrat->dol_object->deleteLine($this->id, $user) > 0) {
            return ['success' => 'Ligne du contrat supprimée avec succès'];
        }
    }

    public function updateAssociations() {
        
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

    public function displaySerialsList($textarea = false) {
        $array = json_decode($this->getData('serials'));
        $html = '';

        if (!$textarea) {

            if (count($array)) {
                foreach ($array as $serial) {
                    $equipment = $this->getInstance('bimpequipment', 'Equipment');
                    if ($equipment->find(['serial' => $serial]) && BimpTools::getContext() == 'private') {
                            $html .= $equipment->getNomUrl(true, true, true);
                        
                    } else {
                        $html .= $serial;
                    }
                    $html .= "<br />";
                }
            } else {
                $html .= BimpRender::renderAlerts("Il n'y à pas de numéros de série dans cette ligne de service", 'info', false);
            }
        } else {
            foreach ($array as $serial) {
                $html .= $serial . "\n";
            }
        }

        return $html;
    }

    public function getArraySerails() {
        
    }

    public function getActionsButtons() {
        $buttons = array();

        $parent = $this->getinstance('bimpcontract', 'BContract_contrat');
        $parent->find(['rowid' => $this->getData('fk_contrat')]);
        // Remise globale: 
        if ($parent->getData('statut') == 0) {
            $buttons[] = array(
                'label' => 'Ajouter des numéros de série',
                'icon' => 'fas_plug',
                'onclick' => $this->getJsActionOnclick('setSerial', array(), array(
                    'form_name' => 'add_serial'
                ))
            );
        }
        if ($parent->getData('statut') == 1 && BimpTools::getContext() != 'public') {
            $buttons[] = array(
                'label' => 'Remplacer un numéro de série',
                'icon' => 'fas_retweet',
                'onclick' => $this->getJsActionOnclick('rebaseSerial', array(), array(
                    'form_name' => 'rebase_serial'
                ))
            );
        }

        return $buttons;
    }

    public function getListFilters() {
        $return[] = array(
            'name' => 'fk_contrat',
            'filter' => $_REQUEST['id']
        );

        return $return;
    }

    public function actionSetSerial($data, &$success) {
        $to_insert = [];
        $all = explode("\n", $data['serials']);
        $success = "Les numéros de séries ont bien été inscrit dans la ligne de service";
        foreach ($all as $serial) {

            if ($serial) {
                $to_insert[] = $serial;
            }
        }

        $errors = $this->updateField('serials', json_encode($to_insert));

        return [
            'success' => $success,
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

}
