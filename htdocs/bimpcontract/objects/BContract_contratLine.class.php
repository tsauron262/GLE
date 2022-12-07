<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcontract/objects/BContract_contrat.class.php';

class BContract_contratLine extends BContract_contrat {
    
    CONST LINE_STATUT_INIT = 0;
    CONST LINE_STATUT_OPEN = 4;
    CONST LINE_STATUT_CLOS = 5;
    
    public static $list_statut = [
        self::LINE_STATUT_INIT => ['label' => 'Service non actif', 'classes' => ['warning'], 'icon' => 'refresh'],
        self::LINE_STATUT_OPEN => ['label' => 'Service actif', 'classes' => ['success'], 'icon' => 'check'],
        self::LINE_STATUT_CLOS => ['label' => 'Service clos', 'classes' => ['danger'], 'icon' => 'times']
    ];
    
    public static $type_p = [
        0 => "Produit",
        1 => "Service"
    ];

    public function createDolObject(&$errors = Array(), &$warnings = Array()) {
        $data = $this->getDataArray();
        $contrat = $this->getParentInstance();
        $produit = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $data['fk_product']);
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
            return 1;
        } else {
            return BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($contrat));
        }

        //return 0;
    }
    
    public function validate() {
        if (is_null($this->getData('description')) || empty($this->getData('description'))) {
            $produit = $this->getChildObject('produit');
            $this->set('description', $produit->getData('description'));
        } 
        
        return parent::validate();
    }
    
    public function displayRenouvellementIn() {
        
        $renouvellement = $this->getData('renouvellement');
        $html = "<strong>";
        
        if($renouvellement == 0) {
            $html .= "Contrat initial";
        } else {
            $html .= "Renouvellement N°" . $renouvellement;
        }
        $html .= '</strong>';
        return $html;
        
    }
    
    public function actionAdminActivateService($data, &$success) {
        
        $errors = $warnings = array();
        
        $errors = $this->updateField('statut', 4);
        
        if(!count($errors)) {
            $success = 'Service activé avec succès';
        }

        return ['success' => $success, 'warnings' => $warnings, 'errors' => $errors];
        
    }
    
    public function adminchangeAppartenance(Array $data,string &$success): Array {
        
        $errors = $warnings = array();
        
        return ['success' => $success, 'warnings' => $warnings, 'errors' => $errors];
    }


    public function getListExtraButtons()
    {
        global $user;
        $buttons = [];
        
        $parent = $this->getParentInstance();
        
        if(BimpTools::getContext() != 'public' && $this->getData('renouvellement') == $parent->getData('current_renouvellement')) {
            
            $disabled = 0;
            if($parent->getData('statut') == 0 || ($user->rights->bimpcontract->to_replace_serial && $parent->getData('statut') == 10 )) {
                $buttons[] = array(
                    'label'   => 'Ajouter les numéros de séries',
                    'icon'    => 'fas_plus',
                    'onclick' => $this->getJsActionOnclick('setSerial', array(), array(
                        'form_name' => 'add_serial'
                    ))
                );
            }
            
            if($parent->getData('statut') == 11 && $user->admin && ($this->getData('statut') == 5 || $this->getData('statut') == 0)) {
                $buttons[] = array(
                    'label'   => 'ADMIN - Activer le service',
                    'icon'    => 'fas_play',
                    'onclick' => $this->getJsActionOnclick('adminActivateService', array(), array())
                );
                $buttons[] = array(
                    'label'   => 'ADMIN - Cahnge appartenance',
                    'icon'    => 'fas_play',
                    'onclick' => $this->getJsActionOnclick('adminchangeAppartenance', array(), array('form_name' => 'newAppartenance'))
                );
            }
            
            if(($parent->getData('statut') == 11 || $parent->getData('statut') == 1) && $user->rights->bimpcontract->to_replace_serial) {
//                $buttons[] = array(
//                    'label'   => 'Remplacer un numéro de série',
//                    'icon'    => 'fas_retweet',
//                    'onclick' => $this->getJsActionOnclick('rebaseSerial', array(), array(
//                        'form_name' => 'rebase_serial'
//                    ))
//                );
                $buttons[] = array(
                    'label'   => 'Ajouter les numéros de séries',
                    'icon'    => 'fas_plus',
                    'onclick' => $this->getJsActionOnclick('setSerial', array(), array(
                        'form_name' => 'add_serial'
                    ))
                );
            }
            
            
                  
                    
        }
        
        return $buttons;
        
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

    protected function updateDolObject(&$errors = array(), &$warnings = Array()) {
        global $user;
        $data = $this->getDataArray();
        
        $contrat = $this->getParentInstance();
        $contrat->dol_object->pa_ht = $this->getData('buy_price_ht');
        $endDate =  $contrat->getEndDate() ? $contrat->getEndDate()->format('Y-m-d') : null;
        if($contrat->dol_object->updateline($this->id, $data['description'], $data['subprice'], $data['qty'], $data['remise_percent'], $contrat->getData('date_start'), $endDate, $data['tva_tx'], 0.0, 0.0, '', '', "HT", 0, null, $this->getData('buy_price_ht')) > 0) {
            
            if($data['buy_price_ht'] != $this->getInitData('buy_price_ht')) {
                $contrat->addLog('Modification du prix d\'achat de la ligne #' . $this->id . ' de ' . $this->getInitData('buy_price_ht') . '€HT à ' . $data['buy_price_ht'] . ' €HT');
            }
            
            $success = "Modifier avec succès";
        } else {
            $errors = 'Erreur';
        }
        return [
            'success' => $success,
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    public function canCreate() {
        global $user;
        $contrat = $this->getParentInstance();
        
        if($contrat->getData('statut') == 10 && $user->rights->bimpcontract->to_validate) {
            return 1;
        }
        
        if ($contrat->getData('statut') > 0) {
            return 0;
        }
        return 1;
    }

    public function canDelete() {
        return $this->canCreate();
    }
    
    public function canEditField($field_name) {
        
        global $user;
        $rights = $user->rights->bimpcontract;
        $parent = $this->getParentInstance();
        
        switch($field_name) {
            case 'buy_price_ht': return ($rights->can_change_pa) ? 1 : 0; break;
            case 'description': return ($rights->can_change_desc) ? 1 : 0; break;
            case 'qty' : return ($parent->getData('statut') == 0) ? 1 : 0; break;
            case 'renouvellement': 
            case 'statut': 
                return $user->admin; 
            break;
        }
        
        return 0;
    }

    public function displaySerialsList($textarea = false) {
        $array = BimpTools::json_decode_array($this->getData('serials'));
        $html = '';

        if (!$textarea) {

            if (count($array)) {
                $html .= '<table>';
                $html .= '<thead><th>N° de série</th><th>N° IMEI</th></thead>';
                $html .= '<tbody>';
                foreach ($array as $serial) {
                    $html .= '<tr>';
                    $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment');
                    if (BimpTools::getContext() != 'public' && $equipment->find(['serial' => addslashes($serial)], true)) {
                            $html .= '<td>';
                            $html .= $equipment->getNomUrl(true, true, true);
                            $html .= '</td>';
                            $html .= '<td>';
                            if($equipment->getData('imei')) {
                                $html .= $equipment->getData('imei');
                            }
                            $html .= '</td>';
                        
                    } elseif(BimpTools::getContext() != 'public' && $equipment->find(['serial' => addslashes(substr($serial, 1))], true)) { 
                        $html .= '<td>';
                            $html .= $equipment->getNomUrl(true, true, true);
                            $html .= '</td>';
                            $html .= '<td>';
                            if($equipment->getData('imei')) {
                                $html .= $equipment->getData('imei');
                            }
                            $html .= '</td>';
                    } else {
                        $html .= '<td>';
                        $html .= $serial;
                        $html .= '</td>';
                        $html .= '<td>';
                        if ($equipment->find(['serial' => addslashes($serial)])) {
                            if($equipment->getData('imei')) {
                                $html .= $equipment->getData('imei');
                            }
                        }
                        $html .= '</td>';
                    }
                    //$html .= "<br />";
                    $html .= '</tr>';
                }
                $html .= '</tbody>';
                $html .= '<table>';
            } else {
                $html .= BimpRender::renderAlerts("Il n'y a pas de numéros de série dans cette ligne de service", 'info', false);
            }
        } else {
            if(count($array) > 0) {
                foreach ($array as $serial) {
                    $html .= $serial . "\n";
                }
            }
        }

        return $html;
    }

    public function getArraySerails() {
        
    }

    public function getActionsButtons() {
        global $user;
        $buttons = array();
        
        $parent = $this->getParentInstance();
        
        if($this->getData('fk_contrat') > 0 && $this->getData('renouvellement') == $parent->getData('current_renouvellement')) {
            $parent = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat');
            $parent->find(['rowid' => $this->getData('fk_contrat')]);

            if ($parent->getData('statut') == 0 || 
                    ($parent->getData('statut') == 10 && $user->rights->bimpcontract->to_validate) && 
                    BimpTools::getContext() != 'public') {
                $buttons[] = array(
                    'label' => 'Ajouter/Modifier des numéros de série',
                    'icon' => 'fas_plug',
                    'onclick' => $this->getJsActionOnclick('setSerial', array(), array(
                        'form_name' => 'add_serial'
                    ))
                );
            }
            if ($parent->getData('statut') > 0 && BimpTools::getContext() != 'public' && $user->rights->bimpcontract->to_replace_serial) {
                $buttons[] = array(
                    'label' => 'Remplacer un numéro de série',
                    'icon' => 'fas_retweet',
                    'onclick' => $this->getJsActionOnclick('rebaseSerial', array(), array(
                        'form_name' => 'rebase_serial'
                    ))
                );
            }
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
    
    public function actionRebaseSerial($data, &$success) {
        
        $errors = [];
        $parent = $this->getParentInstance();
        $liste_exist_serials = BimpTools::json_decode_array($this->getData('serials'));
        $old_serial = $liste_exist_serials[$data['old_serial']];
        if(in_array(strtoupper($data['new_serial']), $liste_exist_serials)) {
            return "Le numéro de série <b>".$data['new_serial']."</b> est déjà présent dans le contrat";
        }
        
        unset($liste_exist_serials[array_search($old_serial, $liste_exist_serials)]);
        array_push($liste_exist_serials, $data['new_serial']);
        //print_r($liste_exist_serials); 
        
        foreach($liste_exist_serials as $serial) {
            $toUpdate[] = strip_tags($serial);
        }
        $errors = $this->updateField('serials', $toUpdate);
        if(!count($errors)) {
            $this->addLog("<strong>" . $data['old_serial'] . "</strong> => <strong>" . $data['new_serial'] . "</strong>");
            $success = $data['old_serial'] . " remplacé par " . $data['new_serial'];
        }

        return [
            'success' => $success,
            'errors' => $errors,
            "warnings" => []
        ];
    }
    
    public function addLog($text) {
        $errors = array();

        if ($this->isLoaded($errors) && $this->field_exists('logs')) {
            $logs = (string) $this->getData('logs');
            if ($logs) {
                $logs .= '<br/>';
            }
            global $user, $langs;
            $logs .= ' - <strong> Le ' . date('d / m / Y à H:i') . '</strong> par ' . $user->getFullName($langs) . ': ' . $text;
            $errors = $this->updateField('logs', $logs, null, true);
        }
        return $errors;
    }
    
    // UPDATE `llx_contratdet` SET `serials` = '["AZERTYUI2","AZERTYUI3","AZERTYUI1"]' WHERE `llx_contratdet`.`rowid` = 16010; 
    
    public function getArraySerials() {
        return BimpTools::json_decode_array($this->getData('serials'));
    }
    
    public function actionSetSerial($data, &$success) {
        $to_insert = [];
        $all = explode("\n", $data['serials']);
        $success = "Les numéros de séries ont bien été inscrit dans la ligne de service";
        foreach ($all as $serial) {

            if ($serial) {
                $to_insert[] = strip_tags($serial);
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
