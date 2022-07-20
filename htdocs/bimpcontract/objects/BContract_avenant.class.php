<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcontract/objects/BContract_contrat.class.php';

class BContract_avenant extends BContract_contrat {
    
    public static $type = [
        0 => "Ajout d'une ligne au contrat",
        1 => "Suppression d'une ligne du contrat",
        2 => "Changement du lieu d'intervention",
        3 => "Changement d'un numéro de série"
    ];
   
    public static $statut_list = [
        0 => ['label' => 'Brouillon', 'icon' => 'trash', 'classes' => ['warning']],
        1 => ['label' => 'Attente de signature', 'icon' => 'refresh', 'classes' => ['success']],
        2 => ['label' => 'Pris en compte', 'icon' => 'play', 'classes' => ['important']],
        3 => ['label' => 'Clos', 'icon' => 'times', 'classes' => ['danger']],
        4 => ['label' => 'Abandonné', 'icon' => 'times', 'classes' => ['danger']]
    ];
    
    public function getTypeAvenantArray() {
        $parent = $this->getParentInstance();
        
        $array = Array(0 => ['label' => 'Avenant de service', 'icon' => 'sign', 'classes' => ['']]);
        
        if($parent->getData('tacite') == 0 || $parent->getData('tacite') == 12) $array[1] = ['label' => 'Avenant de prolongation', 'icon' => 'retweet', 'classes' => ['']];
        
        return $array;
    }
    
    public function getProductPrice() {
        $id_service = BimpTools::getPostFieldValue('id_serv');
        $product = $this->getInstance('bimpcore', 'Bimp_Product', $id_service);
        return $product->getData('price');
    }
    
    public function getTotalCoup($display = true) {
        
        $children = $this->getChildrenList("avenantdet");
        $total = 0;
        foreach($children as $id_child) {
            $child = $this->getChildObject('avenantdet', $id_child);
            $total += $child->getCoup(false);
        }
        
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
        
        $html .= '<strong class="'.$class.'" >' . BimpRender::renderIcon($icon) . ' '.price($total).'€</strong>';
        
        $html .= '</strong>';
        
        if($display)
            return $html;
        else
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
        
        if(BimpTools::getPostFieldValue("years")) {
            
            $nombre_months = BimpTools::getPostFieldValue('years') * 12;
            $end = new DateTime($this->getData('date_end'));
            $end->add(new DateInterval('P' . $nombre_months . 'M'));
            $end->sub(new DateInterval('P1D'));
            
            $errors = $this->updateField('want_end_date', $end->format('Y-m-d'));
            BimpTools::merge_array($errors, $this->updateField('added_month', $nombre_months));
            
        }
        
        return $errors;
        
    }

    public function validatePost() {
         $errors = parent::validatePost();
         $parent = $this->getParentInstance();
         
         if($this->getData('statut') != 0) $errors[] = 'Cet avenant n\'est plus au statut brouillon';
         
         $conserne_date_end_avp = false;
         
         if(BimpTools::getPostFieldValue('type') && BimpTools::getPostFieldValue('type') == 1) $conserne_date_end_avp = true;
         if(BimpTools::getPostFieldValue('years')) $conserne_date_end_avp = true;
         
         
         if($conserne_date_end_avp) {            
            if(BimpTools::getPostFieldValue('years') == 0) $errors[] = 'Vous ne pouvez pas choisir 0 année de prolongation';
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
                
                
                $det = $this->getInstance('bimpcontract', 'BContract_avenantdet');
                $laLigne = $this->getInstance('bimpcontract', 'BContract_contratLine');
                if(is_array($parent->dol_object->lines) && BimpTools::getPostFieldValue('type') == 0)
                    foreach($parent->dol_object->lines as $line) {
                        $laLigne->fetch($line->id);
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
                
                $months = BimpTools::getPostFieldValue('years') * 12;
                $errors = $this->updateField('added_month', $months);
                
                $date_de_fin = new DateTime($parent->displayRealEndDate("Y-m-d"));
                $date_de_fin->add(new DateInterval('P' . $months . 'M'));
                //$date_de_fin->sub(new DateInterval('P1D'));
                
                $date_effect = new DateTime($parent->displayRealEndDate("Y-m-d"));
                $date_effect->add(new DateInterval("P1D"));
                
                BimpTools::merge_array($errors, $this->updateField('want_end_date', $date_de_fin->format('Y-m-d')));
                BimpTools::merge_array($errors, $this->updateField('date_effect', $date_effect->format("Y-m-d")));
                BimpTools::merge_array($errors, $this->updateField('date_end', $parent->displayRealEndDate("Y-m-d")));

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
    
    public function actionValidate() {
        
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
        $parent = $this->getInstance('bimpcontract', 'BContract_contrat', $this->getData('id_contrat'));
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
                $errors = $this->updateField('statut', 2);
                $child = $this->getInstance('bimpcontract', 'BContract_avenantdet');
                $list = $child->getList(['id_line_contrat' => 0, 'id_avenant' => $this->id]);
                $have_new_lines = (count($list) > 0 ? true : false);
                
                $start = new DateTime($parent->getData('date_start'));
                $end = $parent->getEndDate();
                
                if($have_new_lines) {
                    //print_r($list);
                    foreach($list as $nb => $i) {
                        $service = BimpObject::getInstance('bimpcore', 'Bimp_Product', $i['id_serv']);
                        $qty = count(BimpTools::json_decode_array($i['serials_in']));
                        $ligne_de_l_avenant = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_avenantdet', $i['id']);
                        $id_line = $parent->dol_object->addLine(
                                    $service->getData('description'),
                                    $ligne_de_l_avenant->getCoup(false) / $qty, $qty, 20, 0, 0,
                                    $service->id, $i['remise'], 
                                    $start->format('Y-m-d'), $end->format('Y-m-d'), 'HT',0,0,NULL,$service->getData('cur_pa_ht')
                                );
                        $l = $this->getInstance('bimpcontract', 'BContract_contratLine', $id_line);
                        $l->updateField('serials', $i['serials_in']);
                        $l->updateField('statut', 4);
                        $l->updateField('renouvellement', $parent->getData('current_renouvellement'));
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


                        $dejaChange = false;
                        if($coup_ligne_ht != 0) {
                            $dejaChange = true;
                            $errors = BimpTools::merge_array($errors, $ligne_du_contrat->updateField('qty', $ligne_du_contrat->getData('qty') + $added_qty));
                            if(!count($errors))
                                $errors = BimpTools::merge_array($errors, $ligne_du_contrat->updateField('total_ht', $total_ht));
                            if(!count($errors))
                                $errors = BimpTools::merge_array($errors, $ligne_du_contrat->updateField('total_tva', $total_tva));
                            if(!count($errors))
                                $errors = BimpTools::merge_array($errors, $ligne_du_contrat->updateField('total_ttc', $total_ttc));
                            if(!count($errors))
                                $errors = BimpTools::merge_array($errors, $ligne_du_contrat->updateField('serials', $infos['serials_in']));
                        }

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
        $parent = $this->getInstance('bimpcontract', 'BContract_contrat', $this->getdata('id_contrat'));
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
      $parent = $this->getInstance('bimpcontract', 'BContract_contrat', $this->getdata('id_contrat'));
      
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
        
        if($this->getData('statut') == 1) {
            
            $action = ($this->getData('type') == 1) ? 'signedProlongation' : 'signed';
            
            $buttons[] = array(
                'label'   => 'Signer',
                'icon'    => 'fas_signature',
                'onclick' => $this->getJsActionOnclick($action, array(), array(
                    'form_name' => "signed"
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
            
            if($this->getData('statut') == 1) {
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
        }

        return $buttons;
    }
    

    
    public function actionAbort($data = [], &$success) {
        $errors = [];
        $warnings = [];
        $success = "";
        
        $errors = $this->updateField('statut', 4);
        if(!count($errors))
            $success = "Avenant abandonner avec succès";
        
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
                $p = $this->getInstance('bimpcore', 'Bimp_Product', $data->id_service);
                $ht = $p->getData('price');
            } else {
                $ht = $data->ht;
            }
            
            $new = $this->getInstance('bimpcontract', 'BContract_avenantdet');
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
    
}
