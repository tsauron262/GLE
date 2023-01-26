<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcontract/objects/BContract_contrat.class.php';

class BContract_avenant extends BContract_contrat {
    
    public static $type = [
        0 => "Ajout d'une ligne au contrat",
        1 => "Suppression d'une ligne du contrat",
        2 => "Changement du lieu d'intervention",
        3 => "Changement d'un numéro de série"
    ];
    
    CONST AVENANT_STATUT_BROUILLON      = 0;
    CONST AVENANT_STATUT_ATTENTE_SIGN   = 1;
    CONST AVENANT_STATUT_ACTIF          = 2;
    CONST AVENANT_STATUT_CLOS           = 3;
    CONST AVENANT_STATUT_ABANDON        = 4;
    CONST AVENANT_STATUT_PROVISOIR      = 5;
   
    public static $statut_list = [
        self::AVENANT_STATUT_BROUILLON      => ['label' => 'Brouillon', 'icon' => 'trash', 'classes' => ['warning']],
        self::AVENANT_STATUT_ATTENTE_SIGN   => ['label' => 'Attente de signature', 'icon' => 'refresh', 'classes' => ['success']],
        self::AVENANT_STATUT_ACTIF          => ['label' => 'Pris en compte', 'icon' => 'play', 'classes' => ['important']],
        self::AVENANT_STATUT_CLOS           => ['label' => 'Clos', 'icon' => 'times', 'classes' => ['danger']],
        self::AVENANT_STATUT_ABANDON        => ['label' => 'Abandonné', 'icon' => 'times', 'classes' => ['danger']],
        self::AVENANT_STATUT_PROVISOIR      => ['label' => 'Activation provisoire', 'icon' => 'retweet', 'classes' => ['important']]
    ];
    
    public static $default_signature_params = array();

    
    public function getTypeAvenantArray() {
        $parent = $this->getParentInstance();
        
        $array = Array(0 => ['label' => 'Avenant de service', 'icon' => 'sign', 'classes' => ['']]);
        
        if($parent->getData('tacite') == 0 || $parent->getData('tacite') == 12) $array[1] = ['label' => 'Avenant de prolongation', 'icon' => 'retweet', 'classes' => ['']];
        
        return $array;
    }
    
    public function getProductPrice() {
        $id_service = BimpTools::getPostFieldValue('id_serv');
        $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_service);
        return $product->getData('price');
    }
    
    public function displayTotalCoup(){
        $total = $this->getTotalCoup();
        $html = "<strong>";

        $class = "warning";
        $icon = "arrow-right";

        if($total > 0) {
            $class = "success";
            $icon = "arrow-up";
        } elseif($total < 0) {
            $class = "danger";
            $icon = "arrow-down";
        }

        $html .= '<strong class="'.$class.'" >' . BimpRender::renderIcon($icon) . ' '.price($total).' € HT</strong>';

        $html .= '</strong>';

        return $html;
    }
    
    public function getTotalCoup($taxe = 0) {
        $total = 0;
        if(stripos($this->displayData('type'), 'prolongation') !== false){//patch cr getData('type') ne fonctionne pas TODO
            $parent = $this->getParentInstance();
            $total += $parent->getAddAmountAvenantProlongation($this->id, $taxe);
        }
        else{
            $children = $this->getChildrenList("avenantdet");
            foreach($children as $id_child) {
                $child = $this->getChildObject('avenantdet', $id_child);
                $total += $child->getCoup(false, $taxe);
            }

            
        }
        return $total;
    }
    
    public function getAllSerialsContrat() {
        $parent = $this->getParentInstance();
        $children = $parent->getChildrenListArray("lines");
        $allSerials = [];
        
        foreach($children as $id => $v) {
            $child = $parent->getChildObject('lines', $id);
            $serials = BimpTools::json_decode_array($child->getData("serials"));
            foreach($serials as $serial) {
                if(!in_array($serial, $allSerials)) {
                    $allSerials[$serial] = $serial;
                }
            }
        }
        
        return $allSerials;
    }
    
    public function update(&$warnings = array(), $force_update = false) {
        
        $errors = [];
        
        if(BimpTools::getPostFieldValue("years", null)) {
            
            $nombre_months = BimpTools::getPostFieldValue('years') * 12;
            $end = new DateTime($this->getData('date_end'));
            $end->add(new DateInterval('P' . $nombre_months . 'M'));
            $end->sub(new DateInterval('P1D'));
            
            $errors = $this->updateField('want_end_date', $end->format('Y-m-d'));
            BimpTools::merge_array($errors, $this->updateField('added_month', $nombre_months));
            
        }
        
        if(BimpTools::getPostFieldValue("month", null)) {
            
            $nombre_months = BimpTools::getPostFieldValue('month');
            $end = new DateTime($this->getData('date_end'));
            $end->add(new DateInterval('P' . $nombre_months . 'M'));
            $end->sub(new DateInterval('P1D'));
            
            $errors = $this->updateField('want_end_date', $end->format('Y-m-d'));
            BimpTools::merge_array($errors, $this->updateField('added_month', $nombre_months));
            
        }
        $errors = BimpTools::merge_array($errors, parent::update($warnings, $force_update));
        
        return $errors;
        
    }
    
    public function getThisAddingAmountForTypeProlongation() {
        
        $return = 0.0;
        
        $parentInstance = $this->getParentInstance();
        $totalContratEnCours = $parentInstance->getCurrentTotal();
        $totalByMonth = $totalContratEnCours / $parentInstance->getData('duree_mois');
        
        $nbMonthAdding = $this->getData('added_month');
        
        $return = $totalByMonth;
        
        return $return;
        
        
    }
    
    public function by($by):bool {
        
        switch($by) {
            
            case 'm':
                return ($this->getData('by_month')) ? 1 : 0;
                break;
            case 'a':
                return (!$this->getData('by_month')) ? 1 : 0;
                break;
            
        }
        
        return 0;
    }

    public function validatePost() {
         $errors = parent::validatePost();
         $parent = $this->getParentInstance();
         
         if($this->getData('statut') != 0) $errors[] = 'Cet avenant n\'est plus au statut brouillon';
         
         $conserne_date_end_avp = false;
         
//         if(BimpTools::getPostFieldValue('type') && BimpTools::getPostFieldValue('type') == 1) $conserne_date_end_avp = true;
         if(BimpTools::getPostFieldValue('years', null)) $conserne_date_end_avp = true;
         if(BimpTools::getPostFieldValue('month', null)) $conserne_date_end_avp = true;
         
         if($conserne_date_end_avp) {            
            if(!$this->getData('by_month'))
                if(BimpTools::getPostFieldValue('years') == 0) $errors[] = 'Vous ne pouvez pas choisir 0 année de prolongation';
            if($this->getData('by_month'))
                if(BimpTools::getPostFieldValue('month') == 0) $errors[] = 'Vous ne pouvez pas choisir 0 mois de prolongation';
        }
         
         return $errors;
    }
    
    public function create(&$warnings = array(), $force_create = false) {
                
        $parent = $this->getParentInstance();
        $errors = [];
        if(!count($errors)) {
            $errors = parent::create($warnings, $force_create);
            if(!count($errors) && $this->getData('type') == 0) {
//                $success = "Avenant créer avec succès";
                
                
                $det = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_avenantdet');
                if(is_array($parent->dol_object->lines) && BimpTools::getPostFieldValue('type') == 0)
                    foreach($parent->dol_object->lines as $line) {
                        $laLigne = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contratLine', $line->id);
                        if($laLigne->getData('renouvellement') == $parent->getData('current_renouvellement')) {
                            $nbSerial = count(BimpTools::json_decode_array($laLigne->getData('serials')));
                            if($nbSerial < 1)
                                $nbSerial = 1;
                            
                            $det->set('id_avenant', $this->id);
                            $det->set('id_line_contrat', $laLigne->id);
                            $det->set('qty', $laLigne->getData('qty'));
                            $det->set('ht', $laLigne->getData('price_ht'));
                            $det->set('remise', $laLigne->getData('remise_percent'));
                            $det->set('description', $laLigne->getData('description'));
                            $det->set('serials_in', $laLigne->getData('serials'));
                            $det->set('id_serv', $line->id);
                            $det->set('in_contrat', 1);
                            $det->create();
                        }
                    }

                $this->updateField('date_end', $parent->displayRealEndDate("Y-m-d"));
            } elseif(!count($errors) && $this->getData('type') == 1) {
                
                if(BimpTools::getPostFieldValue('enMois')) {
                    $months = BimpTools::getPostFieldValue('years');
                    $this->updateField('by_month', 1);
                } else {
                    $months = BimpTools::getPostFieldValue('years') * 12;
                }
                
                $errors = $this->updateField('added_month', $months);
                
                $date_end = new DateTime($parent->displayRealEndDate("Y-m-d"));
                $date_effect = new DateTime($parent->displayRealEndDate("Y-m-d"));
                $date_effect->add(new DateInterval("P1D"));
                
                $date_de_fin = new DateTime($parent->displayRealEndDate("Y-m-d"));
                $date_de_fin->add(new DateInterval('P' . $months . 'M'));
                //$date_de_fin->sub(new DateInterval('P1D'));
                
                
                BimpTools::merge_array($errors, $this->updateField('want_end_date', $date_de_fin->format('Y-m-d')));
                BimpTools::merge_array($errors, $this->updateField('date_effect', $date_effect->format("Y-m-d")));
                BimpTools::merge_array($errors, $this->updateField('date_end', $date_end->format("Y-m-d")));

            }            
            
            $sql = $this->db->db->query('SELECT * FROM `llx_bcontract_avenant` WHERE id_contrat = '.$_REQUEST['id']);
            $number = $this->db->db->num_rows($sql);
            $this->updateField('number_in_contrat', $number);
        }
        
        return $errors;
    }
    
    public function getNbYears() {
        if($this->isLoaded()) {
            return $this->getData('added_month') / 12;
        }
        return 1;
    }
    
    public function getNbMois() {
        if($this->isLoaded()) {
            return $this->getData('added_month');
        }
        
        return 0;
    }
    
    
    public function actionValidate($data, &$success) {
        
        $errors = [];
        $warnings = [];
        $success = "";
        $canValidate = (count($this->getChildrenList('avenantdet', ['in_contrat' => 1]))) ? true : false;        
        
        if($this->getData('type') == 0) {
            if(!$canValidate)
                $errors[] = "L'avenant ne peut pas être validé sans aucune ligne pour le contrat";
        }

        $parent = $this->getParentInstance();
        if($parent->getData('statut') != 11)
            $errors[] = "Vous ne pouvez pas valider l'avenant car  le contrat n'est pas actif";
        
        if(!count($errors)) {
            
            $errors = $this->updateField('statut', 1);
            if(!count($errors)) {
                
                $parent = $this->getParentInstance();
                $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $parent->getData('fk_soc'));
                $this->actionGeneratePdf([], $success);
                
                $success = "Avenant validé avec succès";
                
                $prefix = ($this->getData('type') == 1) ? 'AVP' : 'AV';
                
                $objet      = 'Avenant n°' . $prefix . $this->getData('number_in_contrat') . ' sur le contrat ' . $parent->getData('ref') . ' Client ' . $client->getData('code_client') . ' ' . $client->getName();
                $message    = 'L\'avenant n°' . $prefix . $this->getData('number_in_contrat') . ' sur le contrat ' . $parent->getNomUrl() . ' est en attente de signature';
                
                mailSyn2($objet, "contrat@bimp.fr", null, $message);
            }
                
        }
        
        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'success' => ""
        ];
        
    }
    
    public function actionGeneratePdf($data, &$success = '', $errors = Array(), $warnings = Array()) {
        global $langs;
        $parent = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', $this->getData('id_contrat'));
        //print_r($parent, 1);
        $success = "PDF Avenant généré avec Succes";
        $parent->dol_object->pdf_avenant = $this->id;
        $parent->dol_object->generateDocument('contrat_avenant', $langs);
        
        $file = $parent->getRef().'/'.$this->getRefAv().'_Ex_OLYS.pdf';
        $url = DOL_URL_ROOT.'/document.php?modulepart=contract&file='.$file;
        
        $success_callback = 'window.open(\'' . $url . '\');';

        return array(
            'errors'           => array(),
            'warnings'         => array(),
            'success_callback' => $success_callback
        );
    }
        
    public function actionActivation($data, &$success) {
        $data = (object) $data;
        $errors = Array();
        $warnings = Array();
        
        $success = '';
        
        $haveSignature = $data->haveSignature;
        
        if($this->getData('statut') == self::AVENANT_STATUT_PROVISOIR && !$haveSignature) {
            $errors[] = 'Cet avenant est déjà activé provisoirement';
        }
        
        if(!count($errors)) {
            if($haveSignature) {
                $this->updateField('signed', 1);
                $this->updateField('date_signed', $data->dateSignature);
                if($this->getData('type') == 1) {
                    $this->actionSignedProlongation(Array('date_signed' => $data->dateSignature), $success);
                } else {
                    $this->actionSigned(Array('date_signed' => $data->dateSignature), $success);
                }
            } else {
                $parent = $this->getParentInstance();
                $commercial = $parent->getCommercialClient(true);
                $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $parent->getdata('fk_soc'));
                $sujet = 'AVENANT ' . $this->getRefAv() . ' - ACTIVATION PROVISOIRE - ' . $client->getName();
                $dest = $commercial->getData('email');

                $message = 'Bonjour ' . $commercial->getName() . '<br />';
                $message .= 'L\'avenant N°' . $this->getRefAv() . ' a été activé provisoirement. Vous disposez de 15 jours pour le faire signer par le client, après ce délai, l\'avenant sera abandonné automatiquement. Vous recevrez une alerte par jour, à partir des derniers 5 jours de l\'activation provisoire.';
                $message .= '<br /><br />Client: ' . $client->getLink() . '<br />Contrat: ' . $parent->getLink();
                mailSyn2($sujet, $dest, null, $message);
                $this->updateField('date_activate', date('Y-m-d'));
                $this->updateField('statut', self::AVENANT_STATUT_PROVISOIR);

            }
        }
        
        return Array(
            'errors' => $errors, 
            'warnings' => $warnings, 
            'success' => $success
        );
    }
    
    public function actionSigned($data, &$success) {
        $errors = [];
        $warnings = [];
        $success = "";
        $parent = $this->getParentInstance();
        $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $parent->getData('fk_soc'));
        $errors = $this->updateField('date_signed', $data['date_signed']);
        if(!count($errors)) {
            $errors = $this->updateField('signed', 1);
            if(!count($errors)) {
                $errors = $this->updateField('statut', self::AVENANT_STATUT_ACTIF);
                $child = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_avenantdet');
                $list = $child->getList(['id_line_contrat' => 0, 'id_avenant' => $this->id]);
                $have_new_lines = (count($list) > 0 ? true : false);
                
                $start = new DateTime($parent->getData('date_start'));
                $end = $parent->getEndDate();
                
                if($have_new_lines) {
                    //print_r($list);
                    foreach($list as $nb => $i) {
                        $service = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $i['id_serv']);
                        $qty = count(BimpTools::json_decode_array($i['serials_in']));
                        $ligne_de_l_avenant = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_avenantdet', $i['id']);
//                        $id_line = $parent->dol_object->addLine(
//                                    $service->getData('description'),
//                                    $ligne_de_l_avenant->getCoup(false) / $qty, $qty, 20, 0, 0,
//                                    $service->id, $i['remise'], 
//                                    $start->format('Y-m-d'), $end->format('Y-m-d'), 'HT',0,0,NULL,$service->getData('cur_pa_ht')
//                                );
//                        $l = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contratLine', $id_line);
//                        $l->updateField('serials', $i['serials_in']);
//                        $l->updateField('statut', 4);
//                        $l->updateField('renouvellement', $parent->getData('current_renouvellement'));
                    }
                }

                $children = $child->getList(Array('id_avenant' => $this->id, 'in_contrat' => 1));

                foreach($children as $index => $infos) {
                    if($infos['id_line_contrat'] > 0) {
                        $ligne_du_contrat = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contratLine', $infos['id_line_contrat']);
                        $ligne_de_l_avenant = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_avenantdet', $infos['id']);

                        $added_qty = $ligne_de_l_avenant->getQtyAdded();
                        $coup_ligne_ht = $ligne_de_l_avenant->getCoup(false);
                        $coup_ligne_tva = (20 * $coup_ligne_ht) / 100;
                        $coup_ligne_ttc = $coup_ligne_ht + $coup_ligne_tva;

                        $cout = 0;
                        if($added_qty > 0)
                            $cout = $coup_ligne_ht / $added_qty;
                        $modifs_string = 
                            'Coup 1 : ' . $cout . '<br />' . 
                            'Qty: ' . $ligne_du_contrat->getData('qty') . ' + ' . $added_qty . "<br />" .
                            'HT: ' . $ligne_du_contrat->getdata('total_ht') . " + " . $coup_ligne_ht . "€" . "<br />" .
                            'TVA: ' . $ligne_du_contrat->getData('total_tva') . " + " . $coup_ligne_tva . "€ <br />" .
                            "TTC: " . $ligne_du_contrat->getData('total_ttc') . " + " . $coup_ligne_ttc . "€ <br />";


                        $total_ht = $coup_ligne_ht + $ligne_du_contrat->getData('total_ht');
                        $total_tva = $coup_ligne_tva + $ligne_du_contrat->getData('total_tva');
                        $total_ttc = $coup_ligne_ttc + $ligne_du_contrat->getData('total_ttc');


//                        $dejaChange = false;
//                        if($coup_ligne_ht != 0) {
//                            $dejaChange = true;
//                            $errors = BimpTools::merge_array($errors, $ligne_du_contrat->updateField('qty', $ligne_du_contrat->getData('qty') + $added_qty));
//                            if(!count($errors))
//                                $errors = BimpTools::merge_array($errors, $ligne_du_contrat->updateField('total_ht', $total_ht));
//                            if(!count($errors))
//                                $errors = BimpTools::merge_array($errors, $ligne_du_contrat->updateField('total_tva', $total_tva));
//                            if(!count($errors))
//                                $errors = BimpTools::merge_array($errors, $ligne_du_contrat->updateField('total_ttc', $total_ttc));
//                            if(!count($errors))
//                                $errors = BimpTools::merge_array($errors, $ligne_du_contrat->updateField('serials', $infos['serials_in']));
//                        }

                        $serialsLigne = BimpTools::json_decode_array($ligne_du_contrat->getData('serials'));
                        $newSerials = BimpTools::json_decode_array($infos['serials_in']);
                        if(count(array_diff($serialsLigne, $newSerials)) > 0 && !$dejaChange) {
                            $errors = BimpTools::merge_array($errors, $ligne_du_contrat->updateField('serials', $infos['serials_in']));
                    }
                }
            }
        }
        }
        
        if(!count($errors)) {
            $success = 'Avenant signé avec succès';
            $ref = $parent->getData('ref') . '-AV' . $this->getData('number_in_contrat');
            
            $dateS = new DateTime($data['date_signed']);
            
            $objet      = 'Signature avenant n°' . 'AV' . $this->getData('number_in_contrat') . ' sur le contrat ' . $parent->getData('ref') . ' client ' . $client->getData('code_client') . ' ' . $client->getName();
            $message    = 'L\'avenant n°AV' . $this->getData('number_in_contrat') . ' sur le contrat ' . $parent->getNomUrl() . ' a été signé le ' . $dateS->format('d/m/Y');
            
            mailSyn2($objet, 'contrat@bimp.fr', null, $message);
        }
            
        
        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'success' => ""
        ];
    }

    public function actionSignedProlongation($data, &$success) {
        
        $errors = [];
        $warnings = [];
        
        $parent = $this->getParentInstance();
        $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $parent->getData('fk_soc'));
        
        if(!count($errors)) {
            $errors = $parent->updateField('end_date_contrat', $this->getData('want_end_date'));
            $errors = BimpTools::merge_array($errors, $parent->updateField('date_end_renouvellement', $this->getData('want_end_date')));
            $errors = BimpTools::merge_array($errors, $parent->updateField('duree_mois', ($parent->getData('duree_mois') + $this->getData('added_month'))));
            $errors = BimpTools::merge_array($errors, $this->updateField('statut', 2));
            
            if(!count($errors)) {
                $success = "Avenant signé et pris en compte avec succès";
                $ref = $this->getRefAv();

                $dateS = new DateTime($data['date_signed']);
                $objet      = 'Signature avenant n°' . 'AVP' . $this->getData('number_in_contrat') . ' sur le contrat ' . $parent->getData('ref') . ' client ' . $client->getData('code_client') . ' ' . $client->getName();
                $message    = 'L\'avenant n°AVP' . $this->getData('number_in_contrat') . ' sur le contrat ' . $parent->getNomUrl() . ' a été signé le ' . $dateS->format('d/m/Y');

                
                mailSyn2($objet, 'contrat@bimp.fr', null, $message);
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings, 'success' => $success];
        
    }
    
    public function getRefAv() {
        $parent = $this->getParentInstance();
        $sufix = ($this->getData('type') == 1) ? 'AVP' : 'AV'; 
        return $parent->getData('ref') . '-' . $sufix . $this->getData('number_in_contrat');;
    }
    
    public function getContrat() {
        return $_REQUEST['id'];
    }
    
    public function canDelete() {
        if($this->getData('statut') > 0)
            return 0;
        return 1;
    }
    
    public function canCreate() {
      $parent = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', $this->getdata('id_contrat'));
      
      if($parent->getData('statut') != 11) {
          return 0;
      }
      
      return 1;
    }
    
    public function delete(&$warnings = array(), $force_delete = false) {
        
        $children = $this->getChildrenObjects('avenantdet');
        foreach($children as $nb => $child) {
            $child->delete();
        }
        return parent::delete($warnings, $force_delete);
    }
    
    public function getExtraBtn() {
        $buttons = [];

        //if($this->getData('statut') == 0) {
            $buttons[] = array(
                'label'   => 'PDF',
                'icon'    => 'fas_file-pdf',
                'onclick' => $this->getJsActionOnclick('generatePdf', array(), array(
                ))
            );
        //}
        
        if($this->getData('statut') == 0) {
            $buttons[] = array(
                'label'   => 'Valider l\'avenant',
                'icon'    => 'fas_check',
                'onclick' => $this->getJsActionOnclick('validate', array(), array(
                ))
            );
        }
        
        if($this->getData('statut') == self::AVENANT_STATUT_ATTENTE_SIGN || $this->getData('statut') == self::AVENANT_STATUT_PROVISOIR) {
            $buttons[] = array(
                'label'   => 'Activer l\'avenant',
                'icon'    => 'fas_play',
                'onclick' => $this->getJsActionOnclick('activation', array(), array('form_name' => 'activate'
                ))
            );
        }
        
        if($this->getData('statut') == 1) {
            
            $action = ($this->getData('type') == 1) ? 'signedProlongation' : 'signed';
            
            $buttons[] = array(
                    'label'   => 'Abandonner',
                    'icon'    => 'fas_stop-circle',
                    'onclick' => $this->getJsActionOnclick('abort', array(), array(

                    ))
                );
                $buttons[] = array(
                    'label'   => 'Clore',
                    'icon'    => 'fas_times',
                    'onclick' => $this->getJsActionOnclick('close', array(), array(

                    ))
                );
        }
        
        if($this->getData('type') == 0) {
            if($this->getData('statut') == 0) {
                $buttons[] = array(
                    'label'   => 'Ajouter une ligne à l\'avenant',
                    'icon'    => 'fas_list',
                    'onclick' => $this->getJsActionOnclick('addLine', array(), array(
                        'form_name' => 'addLine'
                    ))
                );
            }
        }

        return $buttons;
    }
    

    
    public function actionAbort($data = [], &$success) {
        $errors = [];
        $warnings = [];
        $success = "";
        
        $errors = $this->updateField('statut', 4);
        if(!count($errors))
            $success = "Avenant abandonné avec succès";
        
        return [
            'success' => $success,
            'warnings' => $warnings,
            'errors' => $errors
        ];
    }
        
    public function actionClose($data, &$success) {
        $errors = [];
        $warnings = [];
        $success = "";
        
        $errors = $this->updateField('statut', 3);
        if(!count($errors))
            $success = "Avenant clos avec succès";
        
        return [
            'success' => $success,
            'warnings' => $warnings,
            'errors' => $errors
        ];
    }
    
    public function getProataDays($display = true) {
        $prorata = 1;
        $end_contrat = new DateTime($this->getData('date_end'));
        $date_ave = new DateTime($this->getData('date_effect'));
        $prorata += $date_ave->diff($end_contrat)->days;
        $html = "<strong>";
        $html .= $prorata . ' Jour.s';
        $html .= "<strong>";
        if($display)
            if($this->getData('type') == 0)
                return $html;
            else
                return 'N/A';
        else
            return $prorata;
    }
    
    public function actionAddLine($data, &$success) {
        $data = (object) $data;
        $errors = [];
        $warnings = [];
        $success = "";
        
        if(!$data->id_serv)
            $errors[] = "Il doit y avoir un service";
        
        if(!count($errors)) {
            if(!$data->ht){
                $p = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $data->id_service);
                $ht = $p->getData('price');
            } else {
                $ht = $data->ht;
            }
            
            $new = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_avenantdet');
            $new->set('id_serv', $data->id_serv);
            $new->set('ht', $ht);
            $new->set('in_contrat', 1);
            $new->set('remise', $data->remise);
            $new->set('id_avenant', $this->id);
            
            $allSerials = [];
            
            if($data->old_serials) {
                foreach($data->old_serials as $serial) {
                    $allSerials[] = $serial;
                }
            }
            
            if($data->serials) {
                $serials = explode("\n", $data->serials);
                foreach($serials as $serial) {
                    if(!in_array($serial, $allSerials)) {
                        $allSerials[] = $serial;
                    }
                }
            }

            if(count($allSerials) > 0) {
                $new->set('serials_in', json_encode($allSerials));
            }
            
            $errors = $new->create();
            
            if(!count($errors)) {
                $success = "La ligne à bien été ajoutée à l'avenant";
            }
            
        }
        
        
        return [
            'success' => $success,
            'warnings' => $warnings,
            'errors' => $errors
        ];
        
    }
    
    public function isLeDernier() {
        
    }
    
    public function isDeletable($force_delete = false, &$errors = array()) {
        return 1;
    }
    
    public function displayDet() {
        
        $html = '';
        
        if($this->getData('type') == 0) {
            $html .= $this->renderChildrenList('avenantdet');
        } else {
            
            $end = new DateTime($this->getData('date_end'));
            $fin = new DateTime($this->getData('want_end_date'));
            
            $html .= '<h3 class="danger" ><u>Avenant de prolongation</u></h3>';
            $html .= BimpRender::renderIcon('calendar danger') . ' Du <strong>'.$end->format('d/m/Y').'</strong> au <strong>'.$fin->format('d/m/Y').'</strong>';
            $html .= '<br /><br />';
            $html .= $this->renderForm('avenantProlongationEdit', true);
        }
        
        
        return $html;
        
    }
    
    // Signature
    
    public function getSignatureDocFileDir($doc_type = '') {

    }
    
    public function getSignatureDocFileName($doc_type = 'contrat', $signed = false) {

    }
    
    public function getSignatureDocFileUrl($doc_type, $forced_context = '', $signed = false) {

    }
    
    public function getSignatureDocRef($doc_type) {

    }
    
    public function getSignatureParams($doc_type) {

    }
    
    public function onSigned($bimpSignature) {

    }
    
    public function onSignatureCancelled($bimpSignature) {

    }
    
    public function isSignatureReopenable($doc_type, &$errors = array()) {

    }
    
    public function onSignatureReopened($bimpSignature) {

    }
        
    
//     * +- Ajouter les champs "id_signature" et "signature_params" dans l'objet (+ définitions de l'objet BimpSignature) 
//     * +- Ajouter le tableau static $default_signature_params
//     * - Ajouter une procédure pour créer la signature (action/fonction etc.)
//     * - Ajouter les méthodes: 
//     *      - getSignatureDocFileDir($doc_type): dossier fichier signé ou à signer
//     *      - getSignatureDocFileName($doc_type, $signed = false): nom fichier signé ou à signer
//     *      - getSignatureDocFileUrl($doc_type, $forced_context = '', $signed = false): URL fichier
//     *      - getSignatureDocRef($doc_type): Reférence document
//     *      - getSignatureParams($doc_type): Paramètres position signature sur PDF
//     *      - onSigned($bimpSignature): Traitement post signature effectuée
//     *      - onSignatureCancelled($bimpSignature): Traitement post signature annulée
//     *      - isSignatureReopenable($doc_type, &$errors = array()): la signature peut-elle être réouverte (suite annulation) 
//     *      - onSignatureReopened($bimpSignature): Traitement post réouverture signature
//     * 
//     * - Gérer l'enregistrement des paramètres de position de la signature sur le PDF au moment de sa génération (Si besoin) / ou régler par défaut pour les PDF fixes
//     * - Intégrer selon le context: marqueur signé (champ booléen ou statut) / indicateur signature dans l'en-tête / etc. 
//     * - Gérer Annulation signature si besoin
//     * - Gérer Duplication / Révision / Etc. 
//     * - Gérer la visualisation du docuement sur l'interface publique (bimpinterfaceclient > docController) 
//     * - Gérer le droit canClientView() pour la visualisation du document sur l'espace public. 
    
}
