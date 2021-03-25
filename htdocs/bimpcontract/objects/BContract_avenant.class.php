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
        2 => ['label' => 'Actif', 'icon' => 'play', 'classes' => ['important']],
        3 => ['label' => 'Clos', 'icon' => 'times', 'classes' => ['danger']],
        4 => ['label' => 'Abandonné', 'icon' => 'times', 'classes' => ['danger']]
    ];
    
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
            $serials = json_decode($child->getData("serials"));
            foreach($serials as $serial) {
                if(!in_array($serial, $allSerials)) {
                    $allSerials[$serial] = $serial;
                }
            }
        }
        
        return $allSerials;
    }

    public function create(&$warnings = array(), $force_create = false) {
        
        $parent = $this->getParentInstance();
        $errors = [];
        $success = '';
                
        if(!count($errors)) {
            $errors = parent::create($warnings, $force_create);
            if(!count($errors)) {
                $success = "Avenant créer avec succès";
                $number = count($this->getList(['id_contrat' => $_REQUEST['id']]));
                
                $det = $this->getInstance('bimpcontract', 'BContract_avenantdet');
                $laLigne = $this->getInstance('bimpcontract', 'BContract_contratLine');
                if(is_array($parent->dol_object->lines))
                    foreach($parent->dol_object->lines as $line) {
                        $laLigne->fetch($line->id);
                        $det->set('id_avenant', $this->id);
                        $det->set('id_line_contrat', $laLigne->id);
                        $det->set('qty', $laLigne->getData('qty'));
                        $det->set('ht', $laLigne->getData('subprice'));
                        $det->set('remise', $laLigne->getData('remise_percent'));
                        $det->set('description', $laLigne->getData('description'));
                        $det->set('serials_in', $laLigne->getData('serials'));
                        $det->set('id_serv', $line->id);
                        $det->set('in_contrat', 1);
                        $det->create();
                    }
                $this->updateField('number_in_contrat', $number);
                
                $this->updateField('date_end', $parent->displayRealEndDate("Y-m-d"));
            }
        }
        
        return [
            'success' => $success,
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
    
    public function actionValidate() {
        
        $errors = [];
        $warnings = [];
        $success = "";
        $canValidate = (count($this->getChildrenList('avenantdet', ['in_contrat' => 1]))) ? true : false;        
        
        if(!$canValidate)
            $errors[] = "L'avenant ne peut pas être validé sans aucune ligne pour le contrat";

        if(!count($errors)) {
            
            $errors = $this->updateField('statut', 1);
            if(!count($errors)) {
                
                $parent = $this->getParentInstance();

                $this->actionGeneratePdf([], $success);
                
                $success = "Avenant validé avec succès";
                $message = "Bonjour,<br />Une avenant est en attente de signature client sur le contrat " . $parent->dol_object->getNomUrl();
                mailSyn2("[CONTRAT] - Avenant", "contrat@bimp.fr", null, $message);
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
    }
    
    public function actionSigned($data, &$success) {
        $errors = [];
        $warnings = [];
        $success = "";
        $parent = $this->getParentInstance();
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
                        $qty = count(json_decode($i['serials_in']));
                        $id_line = $parent->dol_object->addLine(
                                    $service->getData('description'),
                                    ($this->getTotalCoup(false) / $qty), $qty, 20, 0, 0,
                                    $service->id, $i['remise'], 
                                    $start->format('Y-m-d'), $end->format('Y-m-d'), 'HT',0,0,NULL,$service->getData('cur_pa_ht')
                                );
                        $l = $this->getInstance('bimpcontract', 'BContract_contratLine', $id_line);
                        $l->updateField('serials', $i['serials_in']);
                        $l->updateField('statut', 4);
                    }
                }

                $children = $child->getList(Array('id_avenant' => $this->id));
                foreach($children as $index => $infos) {
                    if($infos['id_line_contrat'] > 0) {
                        $lineContrat = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contratdet', $infos['id_line_contrat']);
                        $new = [
                            'qty' => count(json_decode($infos['serials_in']))
                        ];
                        $errors[] = print_r($new) . "<br />";
                    }
                }

            }
        }
        
        if(!count($errors)) {
            $success = 'Avenant signé avec succès';
            $ref = $parent->getData('ref') . '-AV' . $this->getData('number_in_contrat');
            $msg = "L'avenant N°" . $ref . " à été signé le " . $data['date_signed'];
            mailSyn2("AVENANT CONTRAT", 'contrat@bimp.fr', null, $msg);
        }
            
        
        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'success' => ""
        ];
    }
    
    public function getRefAv() {
        $parent = $this->getInstance('bimpcontract', 'BContract_contrat', $this->getdata('id_contrat'));
        return $parent->getData('ref') . '-AV' . $this->getData('number_in_contrat');
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
        $buttons[] = array(
                'label'   => 'PDF',
                'icon'    => 'fas_file-pdf',
                'onclick' => $this->getJsActionOnclick('generatePdf', array(), array(
                ))
            );
        if($this->getData('statut') == 0) {
            $buttons[] = array(
                'label'   => 'Validé l\'avenant',
                'icon'    => 'fas_check',
                'onclick' => $this->getJsActionOnclick('validate', array(), array(
                ))
            );
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
                'label'   => 'Signer',
                'icon'    => 'fas_signature',
                'onclick' => $this->getJsActionOnclick('signed', array(), array(
                    'form_name' => "signed"
                ))
            );
        }
        if($this->getData('statut') == 1 || $this->getData('statut') == 2) {
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
            return $html;
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
    
    
}
