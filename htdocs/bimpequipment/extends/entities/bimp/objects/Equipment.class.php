<?php

require_once DOL_DOCUMENT_ROOT . '/bimpequipment/objects/Equipment.class.php';

class Equipment_ExtEntity extends Equipment
{
    public function getActionsButtons() {
        $buttons =  parent::getActionsButtons();
        
        if($this->as_sapre_actif())
            $buttons[] = array(
                'label'   => 'Echange SPARE',
                'icon'    => 'fas_link',
                'onclick' => $this->getJsActionOnclick('changeSpareMaterial', array('libelle' => "SPARE ".$this->getData('serial')), array('form_name'=>'changeSpare'))
            );
        
        return $buttons;
    }
    
    public function getIdEntrepotSpare(){
        $place = $this->getCurrentPlace();
        if($place && $place->isLoaded() && $place->getData('id_client') > 0){
            $cli = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $place->getData('id_client'));
            return $cli->getData('entrepot_spare');
        }
    }
    
    public function as_client_spare(){
        if($this->getIdEntrepotSpare())
                return true;
        return false;
    }
    
    public function as_client_spare_or_date(){
        if($this->as_client_spare() || $this->getData('date_fin_spare'))
            return true;
        return false;
    }
    
    public function as_sapre_actif(){
        if(!$this->as_client_spare())
            return false;
        if($this->getData('date_fin_spare') && strtotime($this->getData('date_fin_spare')) > dol_now())
            return true;
    }
    
    public function getChangeEquipment($memeRef = true){
        $filtre = array(
                    "places.type"          => 2,
                    "places.position"      => 1,
                    "places.id_entrepot"   => $this->getIdEntrepotSpare()
                );
        if($memeRef)
            $filtre['id_product'] = $this->getData("id_product");
        $list = BimpCache::getBimpObjectObjects('bimpequipment', 'Equipment', 
                $filtre, null, null, array('places' => array(
                        'table' => 'be_equipment_place',
                        'alias' => 'places',
                        'on'    => 'places.id_equipment = a.id'
                    ))
        );
        $result = array();
        foreach($list as $obj)
            $result[$obj->id] = $obj->getRef();
        return $result;
    }
    
    public function actionChangeSpareMaterial($data, &$success){
        $errors = $warnings = array();
        $success_callback = '';
        $success = 'Echange OK';
        
        //inversion des dates de spare
        $newEquipment = BimpCache::getBimpObjectInstance($this->module, $this->object_name, ($data['memeProd']? $data['newEquipment'] : $data['newEquipment2']));
        $newEquipment->updateField('date_fin_spare', $this->getData('date_fin_spare'));
        $this->updateField('date_fin_spare', null);
        
        $commande = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
        $place = $this->getCurrentPlace();
        if($place && $place->isLoaded() && $place->getData('id_client') > 0){
            //creation commande
            $commande->validateArray(array(
                'fk_soc' => $place->getData('id_client'), 
                'entrepot'=> $this->getIdEntrepotSpare(),
                'ef_type'   => 'C',
                'date_commande' => dol_now(),
                'libelle'   => $data['libelle']
                )
            );
            $errors = $commande->create($warnings, true);
            if(!count($errors))
                $success_callback = 'window.open(\'' . $commande->getUrl() . '\');';
                
            //ajout info text
            if($data['infos1'] != '')
                $this->createCommandeLnText($commande, $data['infos1'], $errors, $warnings);
            
            //creation ligne d'envoie
            if(!count($errors)){
                $this->createCommandeLnText($commande, 'Expédition du téléphone SPARE :', $errors, $warnings);
                $line = $commande->getLineInstance();

                $errors = $line->validateArray(array(
                    'id_obj'    => (int) $commande->id,
                    'type'      => ObjectLine::LINE_PRODUCT,
                    'deletable' => 1,
                    'editable'  => 0,
                    'remisable' => 0,
                ));

                if (!count($errors)) {
                    $line->id_product = $this->getData('id_product');
                    $line->qty = 1;

                    $errors = $line->create($warnings, true);
                    
                    
                    if (!count($errors)) {
                        $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
                        $errors = $reservation->validateArray(array(
                            'id_client'    => (int) $place->getData('id_client'),
                            'type'      => BR_Reservation::BR_RESERVATION_COMMANDE,
                            'id_entrepot' => $this->getIdEntrepotSpare(),
                            'id_equipment'  => $newEquipment->id,
                            'id_commande_client' => $commande->id,
                            'id_commande_client_line' => $line->id,
                            'status'        => 200,
                            'date_from'     => date("Y-m-d H:i:s")
                        ));

                        if (!count($errors)) {
                            $errors = $reservation->create($warnings, true);
                        }
                    }
                }
            }
            
            //creation ligne de retour
            if(!count($errors)){
                $this->createCommandeLnText($commande, 'Récupération du téléphone endommagé :', $errors, $warnings);
                $line = $commande->getLineInstance();

                $errors = $line->validateArray(array(
                    'id_obj'    => (int) $commande->id,
                    'type'      => ObjectLine::LINE_PRODUCT,
                    'deletable' => 1,
                    'editable'  => 0,
                    'remisable' => 0,
                    'equipments_returned' => array($this->id=>506)
                ));

                if (!count($errors)) {
                    $line->id_product = $this->getData('id_product');
                    $line->qty = -1;

                    $errors = $line->create($warnings, true);
                    
                    if(!count($errors)){
                        $this->updateField('id_commande_line_return', $line->id);
                    }
                }
            }
            
            //ajout info text
            if($data['infos2'] != '')
                $this->createCommandeLnText($commande, $data['infos2'], $errors, $warnings);
            
            //creation ligne de port
            if(!count($errors)){
                $line = $commande->getLineInstance();

                $errors = $line->validateArray(array(
                    'id_obj'    => (int) $commande->id,
                    'type'      => ObjectLine::LINE_PRODUCT,
                    'deletable' => 1,
                    'editable'  => 0,
                    'remisable' => 0
                ));

                if (!count($errors)) {
                    $line->id_product = 4300;
                    $line->qty = 1;
                    $line->pu_ht = 0;

                    $errors = $line->create($warnings, true);
                }
            }
            
        }
        else
            $errors[] = 'Client introuvable';
        
        
        
        return array('errors' => $errors, 'warnings' => $warnings, 'success_callback' => $success_callback);
    }
    
    public function createCommandeLnText($commande, $text, &$errors, &$warnings){
        $line = $commande->getLineInstance();
        $errors = BimpTools::merge_array($errors, $line->validateArray(array(
            'id_obj'    => (int) $commande->id,
            'type'      => ObjectLine::LINE_TEXT,
            'deletable' => 1,
            'editable'  => 0,
            'remisable' => 0,
        )));

        if (!count($errors)) {
            $line->id_product = null;
            $line->qty = 1;
            $line->desc = $text;

            $errors = BimpTools::merge_array($errors, $line->create($warnings, true));
        }
    }
}
