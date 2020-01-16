<?php

class BContract_echeancier extends BimpObject {

    
    public function cronEcheancier() {
        
        
        
    }

    public function canViewObject($object) {
        if (is_object($object))
            return true;
        return false;
    }
    
    public function canEdit() {
        return false; // ToDo à faire les conditions
    }
    
    public function renderlistEndPeriod() {
        $parent = $this->getParentInstance();
        $start = New DateTime($this->getData('next_facture_date'));
        $for_return_array_end_date = date('Y-m-d', mktime(0,0,0, $start->format('m') + 1, 0, $start->format('Y')));
        $for_return_array_start_date = date('Y-m-d', mktime(0,0,0, $start->format('m'), 1, $start->format('Y')));
        $dateTime_end_date = new DateTime($for_return_array_end_date);        
        $reste_periode = $parent->reste_periode();
        $returnedArray = Array();

        for($rp = 1; $rp <= $reste_periode; $rp++) {
            $returnedArray[$dateTime_end_date->format('Y-m-d H:i:s')] = $dateTime_end_date->format('d / m / Y');
            $start->add(new DateInterval("P" . $parent->getData('periodicity') . 'M'));
            $for_return_array_end_date = date('Y-m-d', mktime(0,0,0, $start->format('m') + 1, 0, $start->format('Y')));
            $dateTime_end_date = new DateTime($for_return_array_end_date);
        }

        return $returnedArray;
    }
    
    public function update(&$warnings = array(), $force_update = false) {
        if(BimpTools::getValue('next_facture_date')) {
            $success = "Création de la facture avec succès";
            $parent = $this->getParentInstance();
            
            if(!empty(BimpTools::getValue('montant_ht'))) {
                $montant = BimpTools::getValue('montant_ht');
            } else {
                $reste_a_payer = $parent->reste_a_payer();
                $reste_periode = $parent->reste_periode();
                $start = new DateTime(BimpTools::getValue('next_facture_date'));
                $end = new DateTime(BimpTools::getValue('fin_periode'));
                $interval = $start->diff($end);
                if($interval->m == 0) {
                    $nb = 1;
                } else {
                    $nb = ($interval->m + 1) / $parent->getData('periodicity');
                }
                $montant = ($reste_a_payer / $reste_periode) * $nb;
                
            }
            
            if($montant > $parent->reste_a_payer()) {
                return "Vous ne pouvez pas indiquer un montant suppérieur au reste à payer";
            } elseif($montant == 0) {
                return "Vous ne pouvez pas indiquer un montant égale à 0";
            }
                        
            $this->actionCreateFacture($data = Array('date_start' => BimpTools::getValue('next_facture_date'), 'date_end' => BimpTools::getValue('fin_periode'), 'total_ht' => $montant));
            $new_next_date = new DateTime(BimpTools::getValue('fin_periode'));
            $new_next_date->add(new dateInterval('P1D'));
            $this->updateField('next_facture_date', $new_next_date->format('Y-m-d 00:00:00'));
            $parent->renderEcheancier();
            
        } else {
            return parent::update($warnings, $force_update);
        }
    }
    
    public function actionValidateFacture($data, &$success = Array()) {
        global $user;
  
        $instance = $this->getInstance('bimpcommercial', 'Bimp_Facture', $data['id_facture']);
        if($instance->dol_object->validate($user) > 0) {
            $success = 'Facture <b>'.$facture->ref.'</b> validée avec succès'; 
        } else {
            $errors = 'La facture <b>'.$facture->ref.'</b> n\'à pas été validé, merci de vérifier les champs obligatoire de la facture'; 
        }

        return Array(
            'success' => $success,
            'warnings' => $warnings,
            'errors' => $errors,
        );
    }
    
    public function actionCreateFacture($data, &$success = Array()) {
        // VOIR POURQUOI LORS DE LA PREMIERE FOIS QUON EST DANS LECHEANCIER LE CLIENT NE SE MET PAS DU TOUS
        $parent = $this->getParentInstance();
        $client = $this->getInstance('bimpcore', 'Bimp_Societe', $parent->getInitData('fk_soc'));
        
        $linked_propal = $this->db->getValue('element_element', 'fk_source', 'targettype = "contrat" and fk_target = ' . $parent->id);
        
        $propal = $this->getInstance('bimpcommercial', 'Bimp_Propal', $linked_propal);
        $ef_type = 'CT' . $propal->getData('ef_type');
        $instance = $this->getInstance('bimpcommercial', 'Bimp_Facture');
        $instance->set('fk_soc', $parent->getData('fk_soc'));
        $instance->set('libelle', 'Facture périodique du contrat N°' . $parent->getData('ref'));
        $instance->set('type', 0);
        $instance->set('fk_account', 1);
        $instance->set('entrepot', 50);
        $instance->set('fk_cond_reglement', ($client->getData('cond_reglement')) ? $client->getData('cond_reglement') : 2);
        $instance->set('fk_mode_reglement', ($client->getData('mode_reglement')) ? $client->getData('mode_reglement') : 2);
        $instance->set('datef', date('Y-m-d H:i:s'));
        $instance->set('ef_type', $ef_type);
        $instance->set('model_pdf', 'bimpfact');
        $lines = $this->getInstance('bimpcontract', 'BContract_contratLine');
        $desc = "<b>Services du contrat</b>" . "<br />";
            foreach($lines->getList(['fk_contrat' => $parent->id]) as $idLine => $infos) {
                $desc .= $infos['description'] . "<br />";
            }
        $errors = $instance->create($warnings = Array(), true);
        if(!count($errors)) {
              
            $dateStart = new DateTime($data['date_start']);
            $dateEnd = new DateTime($data['date_end']);
            
            
            if($instance->dol_object->addline("Facturation pour la période du <b>".$dateStart->format('d / m / Y')."</b> au <b>".$dateEnd->format('d / m / Y')."</b><br />" . $desc, (double) $data['total_ht'], 1, 20, 0, 0, 0, 0, $data['date_start'], $data['date_end'], 0, 0, '', 'HT', 0, 1) > 0) {
                $success = 'Facture créer avec succès';
                addElementElement("contrat", "facture", $parent->id, $instance->id);
                $this->updateField('next_facture_date', $dateEnd->add(new DateInterval('P1D'))->format('Y-m-d H:i:s'));
                if($this->getData('validate') == 1) {
                    $this->actionValidateFacture(Array('id_facture' => $instance->id));
                }
                $parent->renderEcheancier();
                $instance = null;
            } else {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($instance->dol_object));
            }
            
            
        }
        
        
        return Array(
            'success' => $success,
            'warnings' => $warnings,
            'errors' => $errors,
        );
    }
    
    public function displayEcheancier($data) {
        global $user;
        $instance_facture = $this->getInstance('bimpcommercial', 'Bimp_Facture');
        $parent = $this->getParentInstance();
        $html = '';
        $html .= '<table class="noborder objectlistTable" style="border: none; min-width: 480px">';
        $html .= '<thead>';
        $html .= '<tr class="headerRow">';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Période de facturation<br />Début - Fin</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Montant HT</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Montant TVA</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Montant TTC</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Facture</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">&Eacute;tat de paiement</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Action facture</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody class="listRows">';
        $callback = 'function(result) {if (typeof (result.file_url) !== \'undefined\' && result.file_url) {window.open(result.file_url)}}';
        $can_create_next_facture = true;
        if($data->factures_send) {
            $current_number_facture = 1;
            foreach($data->factures_send as $element_element) {   
                $facture = $this->getInstance('bimpcommercial', 'Bimp_Facture', $element_element['d']);
                
                if($facture->getData('fk_statut') == 0) {
                    $can_create_next_facture = false;
                }
                $paye = ($facture->getData('paye') == 1) ? '<b class="success" >Payer</b>' : '<b class="danger" >Impayer</b>';
                $html .= '<tr class="objectListItemRow" >';
                $dateDebut = New DateTime();
                $dateFin = New DateTime();
                $dateDebut->setTimestamp($facture->dol_object->lines[0]->date_start);
                $dateFin->setTimestamp($facture->dol_object->lines[0]->date_end);
                $html .= '<td style="text-align:center" >Du <b>'.$dateDebut->format("d / m / Y").'</b> au <b>'.$dateFin->format('d / m / Y').'</b></td>';
                $html .=  '<td style="text-align:center"><b>' . price($facture->getData('total')) . ' €</b> </td>'
                        . '<td style="text-align:center"><b>' . price($facture->getData('tva')) . ' € </b></td>'
                    . '<td style="text-align:center"><b>' . price($facture->getData('total_ttc')) . ' €</b> </td>'
                    . '<td style="text-align:center">' . $facture->getNomUrl(1) . '</td>'
                    . '<td style="text-align:center">'.$paye.'</td>'
                    . '<td style="text-align:center; margin-right:10%">';
                if($facture->getData('fk_statut') == 0 && $user->rights->facture->validate) {
                    $html .= '<span class="rowButton bs-popover" data-trigger="hover" data-placement="top"  data-content="Valider la facture" onclick="' . $this->getJsActionOnclick("validateFacture", array('id_facture' => $facture->id), array("success_callback" => $callback)) . '")"><i class="fa fa-check" ></i></span>';
                } else {
                    $html .= '<span class="rowButton bs-popover" data-toggle="popover" data-trigger="hover" data-container="body" data-placement="top" data-content="Afficher la page dans un nouvel onglet" data-html="false" onclick="window.open(\''.DOL_URL_ROOT.'/bimpcommercial/index.php?fc=facture&amp;id='.$facture->id.'\');" data-original-title="" title=""><i class="fas fa5-external-link-alt"></i></span>';
                }
                if($current_number_facture == count($data->factures_send) && $facture->getData('fk_statut') == 0 && $user->rights->facture->supprimer) {
                    $html .= '<span class="rowButton bs-popover" data-trigger="hover" data-placement="top"  data-content="Supprimer la facture" onclick="' . $this->getJsActionOnclick("deleteFacture", array('id_facture' => $facture->id), array("success_callback" => $callback)) . '")"><i class="fa fa-times" ></i></span>';
                }
                
                $html .= '</td>';
                $html .= '</tr>';
                $current_number_facture++;
                
            }
        }
        $startedDate = new DateTime($this->getInitData('next_facture_date'));
        $enderDate = new DateTime($this->getInitData('next_facture_date'));
        $enderDate->add(new DateInterval("P" . $data->periodicity . "M"))->sub(new DateInterval("P1D"));
        $firstPassage = true;
        $firstDinamycLine = true;

        for($i = 1; $i <= $data->reste_periode; $i++) {
            if(!$firstPassage){
                $startedDate->add(new DateInterval("P" . $data->periodicity . "M"));
            }
            
            $start_mktime = date('Y-m-d', mktime(0,0,0, $startedDate->format('m'), 1, $startedDate->format('Y')));
            $end_mktime =date('Y-m-d', mktime(0,0,0, $startedDate->format('m') + 1, 0, $startedDate->format('Y')));
            
            $dateTime_start_mkTime = new DateTime($start_mktime);
            $dateTime_end_mkTime = new DateTime($end_mktime);
            
            $firstPassage = false;
            $amount  = $data->reste_a_payer / $data->reste_periode;
            $tva = $amount * 0.2;
            $html .= '<tr class="objectListItemRow" >';
            $html .= '<td style="text-align:center" >Du <b>'.$dateTime_start_mkTime->format('d/m/Y').'</b> au <b>'.$dateTime_end_mkTime->format('d/m/Y').'</b></td>';
                $html .=  '<td style="text-align:center">' . price($amount) . ' € </td>'
                        . '<td style="text-align:center">' . price($tva) . ' € </td>'
                    . '<td style="text-align:center">' . price($amount + $tva) . ' € </td>'
                    . '<td style="text-align:center"><b style="color:grey">Période non facturée</b></td>'
                    . '<td style="text-align:center"><b class="important" >Période non facturée</b></td>'
                    . '<td style="text-align:center; margin-right:10%">';
                if($firstDinamycLine && $can_create_next_facture){
                    // ICI NE PAS AFFICHER QUAND LA FACTURE EST PAS VALIDER
                    if($user->rights->facture->creer) {
                        $html .= '<span class="rowButton bs-popover" data-trigger="hover" data-placement="top"  data-content="Facturer la période" onclick="' . $this->getJsActionOnclick("createFacture", array('date_start' => $dateTime_start_mkTime->format('Y-m-d'), 'date_end' => $dateTime_end_mkTime->format('Y-m-d'), 'total_ht' => $amount), array("success_callback" => $callback)) . '")"><i class="fa fa-plus" ></i></span>';
                    }
                    $firstDinamycLine = false;
                }
            $html .= '</tr>';
            $enderDate = $enderDate->add(new DateInterval("P" . $data->periodicity . "M"));
        }
        $html .= '</tbody>';
        $html .= '</table>';
        if($parent->is_not_finish() && $user->rights->facture->creer){
            $html .= '<div class="panel-footer"><div class="btn-group"><button type="button" class="btn btn-default" aria-haspopup="true" aria-expanded="false" onclick="'. $this->getJsLoadModalForm('create_perso', "Créer une facture personalisée ou une facturation de plusieurs périodes") .'"><i class="fa fa-plus-square-o iconLeft"></i>Créer une facture personalisée ou une facturation de plusieurs périodes</button></div></div>';
        }
         $html .= "<br/>"
                . "<table style='float:right' class='border' border='1'>"
                . "<tr> <th style='border-right: 1px solid black; border-top: 1px solid white; border-left: 1px solid white; width: 20%'></th>  <th style='background-color:#ed7c1c;color:white;text-align:center'>Montant HT</th> <th style='background-color:#ed7c1c;color:white;text-align:center'>Montant TTC</th> </tr>"
                . "<tr> <th style='background-color:#ed7c1c;color:white;text-align:center'>Contrat</th> <td style='text-align:center'><b>".price($parent->getTotalContrat())." €</b></td> <td style='text-align:center'><b> ".price($parent->getTotalContrat() * 1.20)." €</b></td> </tr>"
                . "<tr > <th  style='background-color:#ed7c1c;color:white;text-align:center'>Facturé</th> <td style='text-align:center'><b class='important' > ".price($parent->getTotalDejaPayer())." € </b></td> <td style='text-align:center'><b class='important'> ".price($parent->getTotalDejaPayer() * 1.20)." €</b></td> </tr>";
         
         if($parent->getTotalDejaPayer(true) == $parent->getTotalContrat()) {
            $html .= "<tr > <th  style='background-color:#ed7c1c;color:white;text-align:center'>Payé</th> <td style='text-align:center'><b class='success'> ".price($parent->getTotalDejaPayer(true))." € </b></td> <td style='text-align:center'><b class='success'> ".price($parent->getTotalDejaPayer(true) * 1.20)." €</b></td> </tr>";
         } else{
            $html .= "<tr > <th  style='background-color:#ed7c1c;color:white;text-align:center'>Payé</th> <td style='text-align:center'><b class='danger'> ".price($parent->getTotalDejaPayer(true))." € </b></td> <td style='text-align:center'><b class='danger'> ".price($parent->getTotalDejaPayer(true) * 1.20)." €</b></td> </tr>";
         } 
         
         if($parent->getTotalContrat() - $parent->getTotalDejaPayer() == 0) {
            $html .= "<tr > <th  style='background-color:#ed7c1c;color:white;text-align:center'>Reste à facturer</th> <td style='text-align:center'><b class='success'> ".price($parent->getTotalContrat() - $parent->getTotalDejaPayer())." € </b></td> <td style='text-align:center'><b class='success'> ".price(($parent->getTotalContrat() - $parent->getTotalDejaPayer()) * 1.20)." €</b></td> </tr>";
         } else {
            $html .= "<tr > <th  style='background-color:#ed7c1c;color:white;text-align:center'>Reste à facturer</th> <td style='text-align:center'><b class='danger'> ".price($parent->getTotalContrat() - $parent->getTotalDejaPayer())." € </b></td> <td style='text-align:center'><b class='danger'> ".price(($parent->getTotalContrat() - $parent->getTotalDejaPayer()) * 1.20)." €</b></td> </tr>";
         }
         
         if($parent->getTotalContrat() - $parent->getTotalDejaPayer(true) == 0){
            $html .= "<tr> <th style='background-color:#ed7c1c;color:white;text-align:center'>Reste à payer</th> <td style='text-align:center'><b class='success'> 0 € </b></td> <td style='text-align:center'><b class='success'>0 €</b></td> </tr>";
        } else {
            $html .= "<tr> <th style='background-color:#ed7c1c;color:white;text-align:center'>Reste à payer</th> <td style='text-align:center'><b class='danger'> ".price($parent->getTotalContrat() - $parent->getTotalDejaPayer(true))." € </b></td> <td style='text-align:center'><b class='danger'> ".price(($parent->getTotalContrat(true) * 1.20) - ($parent->getTotalDejaPayer(true) * 1.20))." €</b></td> </tr>";
        }
        $html .= "</table>";
        return $html;
    }

    public function actionDeleteFacture($data, &$success) {
        
        global $user;
        $parent = $this->getParentInstance();
        $instance = $this->getInstance('bimpcommercial', 'Bimp_Facture', $data['id_facture']);
        
        $dateDebutFacture = $instance->dol_object->lines[0]->date_start;
        $new_next_date = new DateTime();
        $new_next_date->setTimestamp($dateDebutFacture);

        if($instance->dol_object->delete($user) > 0) {
            $this->updateField('next_facture_date', $new_next_date->format('Y-m-d 00:00:00'));
            $success = "Facture " . $instance->getData('facnumber') . ' supprimée avec succès';
        } else {
            $errors = "Facture " . $instance->getData('facnumber') . ' n\'à pas été supprimée'; ;
        }
        
        return Array(
            'success' => $success,
            'errors' => $errors,
            'warnings' => $warnings
        );
        
      }
    
    public function cron_create_facture() {
        // recuperer tous les echeancier passer

        $echeanciers = $this->getList();
        foreach ($echeanciers as $echeancier) {
            $echeancier->create_facture(true);
        }
    }
}
