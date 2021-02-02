<?php

class BContract_echeancier extends BimpObject {
    
    CONST STATUT_EN_COURS = 1;
    CONST STATUT_IS_FINISH = 0;
    
    public $statut_list = [
        self::STATUT_EN_COURS => ['label' => "En cours de facturation", 'classes' => ['success'], 'icon' => 'fas_play'],
        self::STATUT_IS_FINISH=> ['label' => "Facturation terminée", 'classes' => ['danger'], 'icon' => 'fas_times']
    ];
    
    public function cronEcheancier() {
        // recuperer tous les echeancier passer
        $echeanciers = $this->getList();
        $aujourdui = new DateTime();
        foreach ($echeanciers as $echeancier) {
            $next = new Datetime($echeancier['next_facture_date']);
            $diff = $aujourdui->diff($next);
            $msg = null;
            $parent = $this->getInstance('bimpcontract', 'BContract_contrat', $echeancier['id_contrat']);
            if ($this->isEnRetard()) {
                $msg = "Bonjour,<br />L'échéancier du contrat N°" . $parent->getNomUrl() . ' est en retard de facturation de ' . $diff->d . ' Jours<br /> Merci de faire le nécéssaire pour régulariser la facturation';
            }
//            if(!is_null($msg))
//                mailSyn2("Echéancier du contrat N°" . $parent->getData('ref'), 'al.bernard@bimp.fr', 'admin@bimp.fr', $msg);
        }
    }
    
    public function actionStopBill() {
        
        $parent = $this->getParentInstance();
        $errors = $this->updateField("statut", self::STATUT_IS_FINISH);
        
        if(!count($errors)) {
            $success = "Facturation stoppée avec succès.";
            $parent->addLog("Facturation stoppée");
        }
        
        return [
          'success' => $success,
          'errors' => $errors,
          'warnings' => [] 
        ];
        
    }
    
    
    public function displayCommercialContrat() {
        if ($this->isLoaded()) {
            $parent = $this->getParentInstance();

            $commercial = $this->getInstance('bimpcore', 'Bimp_User', $parent->getData('fk_commercial_suivi'));

            return "<a target='_blank' href='".$commercial->getUrl()."'>".$commercial->getData('firstname') . " " . $commercial->getData('lastname') ." </a>";
        }
    }
    
    public function isEnRetard() {
        $parent = $this->getInstance('bimpcontract', 'BContract_contrat', $this->getData('id_contrat'));
        $aujourdui = new DateTime();
        $next = new Datetime($this->getData('next_facture_date'));
        $diff = $aujourdui->diff($next);
        if($parent->getData('facturation_echu') == 1) {
            if($parent->getData('periodicity')) {
                $finPeriode = $next->add(new DateInterval('P'.$parent->getData('periodicity').'M'));
                $finPeriode->sub(new DateInterval('P1D'));
                
                if(strtotime($aujourdui->format('Y-m-d')) >= strtotime($this->getData('next_facture_date')) && 
                        strtotime($aujourdui->format('Y-m-d')) > strtotime($finPeriode->format('Y-m-d'))) {
                    return 1;
                } else {
                    return 0;
                }
            }  
        } else {
            if ($this->getData('next_facture_date') > 0 && $diff->invert == 1 && $diff->d > 0) {
                return 1;
            }
        }
        return 0;
    }
    
    public function isDejaFactured($date_start, $date_end) {
        $parent = $this->getParentInstance();
        $facture = $this->getInstance('bimpcommercial', 'Bimp_Facture');
        $listeFactures = getElementElement("contrat", 'facture', $parent->id);
        if(count($listeFactures) > 0) {
            foreach($listeFactures as $index => $i) {
                $facture->fetch($i['d']);
                if($facture->getData('type') == 0) {
                    $dateDebut = New DateTime();
                    $dateFin = New DateTime();
                    $dateDebut->setTimestamp($facture->dol_object->lines[0]->date_start);
                    $dateFin->setTimestamp($facture->dol_object->lines[0]->date_end);
                    if($dateDebut->format('Y-m-d') == $date_start && $dateFin->format('Y-m-d') == $date_end) {
                        $parent->addLog("Facturation de la même periode stopée automatiquement");
                        return true;
                    }
                }
            }
        }
        return false;
    }
    
    public function getDateNextPeriodRegen() {
        $facture = $this->getInstance("bimpcommercial", 'Bimp_Facture', $this->getLastFactureId());
        $date = new DateTime();
        $date->setTimestamp($facture->dol_object->lines[0]->date_start);
        return $date->format('Y-m-d');
    }
    
    public function renderLastFactureCard($avoir = 0) {
        
        $facture_avoir = null;
        $card = null; 
        $facture = null;
        if($this->getLastFactureId() > 0)
            $facture = $this->getInstance("bimpcommercial", 'Bimp_Facture', $this->getLastFactureId());
        if($avoir) {
            $facture_avoir = $this->getInstance('bimpcommercial', 'Bimp_Facture', $this->getLastFactureAvoirId($facture->id));
        }

        if(is_object($facture_avoir) && $facture_avoir->isLoaded()) {
            $card = New BC_Card($facture_avoir);
        } elseif(!$avoir) {
            if(is_object($facture)) {
                $card = New BC_Card($facture);
            }
        }
        
        if(is_object($card))
            return $card->renderHtml();
        else
            return BimpRender::renderAlerts ("Il n'y à pas de facture à dé-lier", 'warning', false);
    }

    public function actionUnlinkLastFacture($data, &$success) {
        $errors = array();
        //echo '<pre>' . print_r($data);
        if(!$this->isToujoursTheLastFacture($data['id_facture_unlink'])) {
            $errors[] = "Vous ne pouvez plus faire cette action car la facture du formulaire n'est pas la dernière facture de l'échéancier";
        }

        if(!count($errors)) {
            $facture = $this->getInstance('bimpcommercial', 'Bimp_Facture', $data['id_facture_unlink']);
            
            if($data['is_good_avoir'] == 1) {
                $id_avoir = $data['id_avoir_unlink'];
            } else {
                $id_avoir = $data['new_avoir'];
            }
            
            $avoir = $this->getInstance('bimpcommercial', "Bimp_Facture", $id_avoir);
            
            if($avoir->getData('fk_statut') == 0) {
                $errors[] = "L'opération ne peut être faite sur un avoir BROUILLON";
            }
            
            if($facture->getData('fk_statut') == 0) {
                $errors[] = "L'opération ne peut être faite sur une facture BROUILLON";
            }
            
            if($data['id_avoir_unlink'] == 0) {
                $errors[] = "L'opération ne peut pas être faite sans avoir sur la facture";
            }
            
            if($facture->getData('total_ttc') != abs($avoir->getData('total_ttc'))) {
                $errors[] = "La facture et l'avoir ont un montant différent";
            }
            
            if(!count($errors)) {
                $parent = $this->getParentInstance();
                $next_date = new DateTime($data['date_next_facture']);
                delElementElement('contrat', 'facture', $parent->id, $facture->id);
                delElementElement('contrat', 'facture', $parent->id, $avoir->id);
                $this->updateField('next_facture_date', $next_date->format('Y-m-d 00:00:00'));
                $parent->addLog('<br /><strong>Echéancier regénéré</strong><br />Date de prochaine facture: ' . $next_date->format('d/m/Y') . '<br />Facture dé-link: ' . $facture->getRef() . '<br />Avoir dé-link: ' . $avoir->getRef());
            }
            
        }

        return [
            'errors' => $errors,
            'success' => $success,
            'warnnings' => $warnings
        ];
       
    }
    
    public function getLastFactureId() {
        $facture = $this->getInstance('bimpcommercial', 'Bimp_Facture');
        $liste = getElementElement('contrat', 'facture', $this->getParentId());
        $lastId = 0;
        foreach($liste as $index => $i) {
            $facture->fetch($i['d']);
            if($facture->getData('type') == 0) {
                if($facture->id > $lastId) {
                    $lastId = $facture->id;
                }
            }
        }
        return $lastId;
    }
    
    public function getLastFactureAvoirId($id_facture = 0) {
        if($id_facture == 0)
            $facture = $this->getInstance(('bimpcommercial'), 'Bimp_Facture', $this->getLastFactureId());
        else
            $facture = $this->getInstance(('bimpcommercial'), 'Bimp_Facture', $id_facture);
        
        $facture_avoir = $this->getInstance('bimpcommercial', 'Bimp_Facture');
        if($facture_avoir->find(['fk_facture_source' => $facture->id, 'type' => 2],true)) {
            return $facture_avoir->id;
        }
        return 0;
    }
    
    public function isToujoursTheLastFacture($id) {
        if($this->getLastFactureId() == $id) {
            return true;
        } else {
            return false;
        }
    }

    public function isClosDansCombienDeTemps() {
        
        $parent = $this->getParentInstance();
        $aujourdhui = new DateTime();
        $finContrat = $parent->getEndDate();
        $diff = $aujourdhui->diff($finContrat);
        
        return ($diff->invert == 1 || $diff->d == 0) ? 1 : 0;
        
    }

    public function canViewObject($object) {
        if (is_object($object))
            return true;
        return false;
    }

    public function canEdit() {
        
        $parent = $this->getParentInstance();
        if($parent->getData('statut') == 2 || $parent->getData('statut') == -1)
            return false;
        
        return true;
    }

    public function renderlistEndPeriod() {
        $parent = $this->getParentInstance();
        $start = New DateTime($this->getData('next_facture_date'));

        //$start->add(new DateInterval("P" . $parent->getData('periodicity') . 'M'));
        $stop = $start->sub(new dateInterval('P1D'));
        $end_date_contrat = $parent->getEndDate();
        $reste_periodeEntier = ceil($parent->reste_periode());
        for ($rp = 0; $rp <= $reste_periodeEntier; $rp++) {
            $stop->add(new DateInterval('P' . $parent->getData('periodicity') . "M"));
            if($end_date_contrat < $stop)
                $stop = $end_date_contrat;
            $returnedArray[$stop->format('Y-m-d 00:00:00')] = $stop->format('d/m/Y');
        }

        return $returnedArray;
    }

    public function update(&$warnings = array(), $force_update = false) {
        if (BimpTools::getValue('next_facture_date')) {
            $success = "Création de la facture avec succès";
            $parent = $this->getParentInstance();

            if (!empty(BimpTools::getValue('montant_ht'))) {
                $montant = BimpTools::getValue('montant_ht');
            } else {
                $reste_a_payer = $parent->reste_a_payer();
                $reste_periode = $parent->reste_periode();
                $start = new DateTime(BimpTools::getValue('next_facture_date'));
                $end = new DateTime(BimpTools::getValue('fin_periode'));
                $interval = $start->diff($end->add(new dateInterval('P1D')));
                if ($interval->m == 0 && $interval->y == 0) {
                    $nb = 1;
                } else {
                    $nb = (($interval->y*12) + $interval->m) / $parent->getData('periodicity');
                }
                $montant = ($reste_a_payer / $reste_periode) * $nb;
            }

            if ($montant > $parent->reste_a_payer()) {
                return "Vous ne pouvez pas indiquer un montant (".$montant.") suppérieur au reste à payer ".$parent->reste_a_payer();
            } elseif ($montant == 0) {
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
        if ($instance->dol_object->validate($user) > 0) {
            $success = 'Facture <b>' . $facture->ref . '</b> validée avec succès';
        } else {
            $errors = 'La facture <b>' . $facture->ref . '</b> n\'à pas été validé, merci de vérifier les champs obligatoire de la facture';
        }

        return Array(
            'success' => $success,
            'warnings' => $warnings,
            'errors' => $errors,
        );
    }

    public function actionCreateFacture($data, &$success = Array()) {
        $errors = [];
                
        if($this->isDejaFactured($data['date_start'], $data['date_end'])) {
            return  "Contrat déjà facturé pour cette période, merci de refresh la page pour voir cette facture dans l'échéancier";
        }

        $parent = $this->getParentInstance();
        $client = $this->getInstance('bimpcore', 'Bimp_Societe', $parent->getInitData('fk_soc'));

        $linked_propal = $this->db->getValue('element_element', 'fk_source', 'targettype = "contrat" and fk_target = ' . $parent->id);

        $propal = $this->getInstance('bimpcommercial', 'Bimp_Propal', $linked_propal);
        $ef_type = ($propal->getData('ef_type') == "E") ? 'CTE': 'CTC';
        $instance = $this->getInstance('bimpcommercial', 'Bimp_Facture');
        $instance->set('fk_soc', ($parent->getData('fk_soc_facturation')) ? $parent->getData('fk_soc_facturation') : $parent->getData('fk_soc'));
        
        $bill_label = "Facture " . $parent->getPeriodeString();
        $bill_label.= " du contrat N°" . $parent->getData('ref');
        $bill_label.= ' - ' . $parent->getData('label');
        $instance->set('libelle', $bill_label);
        $instance->set('type', 0);
//        $instance->set('fk_account', 1);
        
        if(!$parent->getData('entrepot') && $parent->useEntrepot()) {
            return "La facture ne peut pas être crée car le contrat n'a pas d'entrepôt";
        }
        if($parent->useEntrepot())
            $instance->set('entrepot', $parent->getData('entrepot'));
        $instance->set('fk_cond_reglement', ($client->getData('cond_reglement')) ? $client->getData('cond_reglement') : 2);
        $instance->set('fk_mode_reglement', ($parent->getData('moderegl')) ? $parent->getData('moderegl') : 2);
        $instance->set('datef', date('Y-m-d H:i:s'));
        $instance->set('ef_type', $ef_type);
        $instance->set('model_pdf', 'bimpfact');
        $instance->set('ref_client', $parent->getData('ref_customer'));
            
            
        $errors = $instance->create($warnings = Array(), true);
        $instance->copyContactsFromOrigin($parent);
        
//        $lines_contrat = [];
//        if(!count($errors)) {
//            $dateStart = new DateTime($data['date_start']);
//            $dateEnd = new DateTime($data['date_end']);
//            $lines = $this->getInstance('bimpcontract', 'BContract_contratLine');
//            foreach ($lines->getList(['fk_contrat' => $parent->id]) as $idLine => $infos) {
//                
//                $total_facturable = ($parent->getData('periodicity') * $infos['total_ht']) / $parent->getData('duree_mois');
//                $quantite_facturable = (($total_facturable * $infos['qty']) / $infos['total_ht']);
//                
////                $lines_contrat[$idLine]['product'] = $infos['fk_product'];
////                $lines_contrat[$idLine]['total_ht'] = $infos['total_ht'];
////                $lines_contrat[$idLine]['nb_periode'] = $parent->getData('periodicity');
////                $lines_contrat[$idLine]['duree_mois'] = $parent->getData('duree_mois');
////                $lines_contrat[$idLine]['total_facturable'] = $total_facturable;
////                $lines_contrat[$idLine]['TO_BILLS_qty_facturable'] = $quantite_facturable;
////                $lines_contrat[$idLine]['TO_BILLS_start'] = $data['date_start'];
////                $lines_contrat[$idLine]['TO_BILLS_end'] = $data['date_end'];
////                $lines_contrat[$idLine]['TO_BILLS_desc'] = $infos['description'];
//                
//                if ($instance->dol_object->addline($infos['description'], $infos['total_ht'], $quantite_facturable, 20, 0, 0, 0, 0, $data['date_start'], $data['date_end'], 0, 0, '', 'HT', 0, 1) > 0) {
//                    
//                    addElementElement("contrat", "facture", $parent->id, $instance->id);
//                    $facture_send = count(getElementElement('contrat', 'facture', $parent->id));
//                    $total_facture_must = $parent->getData('duree_mois') / $parent->getData('periodicity');
//                    if ($facture_send == $total_facture_must) {
//                        $this->updateField('next_facture_date', null);
//                    } else {
//                        $this->updateField('next_facture_date', $dateEnd->add(new DateInterval('P1D'))->format('Y-m-d 00:00:00'));
//                    }
//
//                    if ($this->getData('validate') == 1) {
//                        $this->actionValidateFacture(Array('id_facture' => $instance->id));
//                    }
//                    $parent->renderEcheancier();
//                    $instance = null;
//                                        
//                } else {
//                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($instance->dol_object));
//            }
//                
//                /**
//                 *  Exemple : 1 ligne de 150€ HT sur 12 mois facturée en trimestrielle en qty 1
//                 * 
//                 *  TOTAL FACTURABLE 
//                 * 
//                 *  12 => 150
//                 *  3 => ??
//                 * 
//                 *  QUANTITE FACTURABLE
//                 * 
//                 *  150 => 1
//                 *  37.5 => ???
//                 * 
//                 */
//                
//            }
//            
//        }
//        echo '<pre>';
//        print_r($lines_contrat);
//        echo '</pre>';
        $lines = $this->getInstance('bimpcontract', 'BContract_contratLine');
        $desc = "<b><u>Services du contrat :</b></u>" . "<br /><br />";
        foreach ($lines->getList(['fk_contrat' => $parent->id]) as $idLine => $infos) {
            $desc .= $infos['description'] . "<br /><br />";
        }
        $facture_ok = false;
        if (!count($errors)) {
            
            $dateStart = new DateTime($data['date_start']);
            $dateEnd = new DateTime($data['date_end']);
            addElementElement("contrat", "facture", $parent->id, $instance->id);
            if ($instance->dol_object->addline("Facturation pour la période du <b>" . $dateStart->format('d/m/Y') . "</b> au <b>" . $dateEnd->format('d/m/Y') . "</b><br /><br />" . $desc, (double) $data['total_ht'], 1, 20, 0, 0, 0, 0, $data['date_start'], $data['date_end'], 0, 0, '', 'HT', 0, 1, -1, 0, "", 0, 0, null, $data['pa']) > 0) {
                $success = 'Facture créer avec succès';
                $facture_ok = true;

                $facture_send = count(getElementElement('contrat', 'facture', $parent->id));
                $total_facture_must = $parent->getData('duree_mois') / $parent->getData('periodicity');
                
                $this->switch_statut();
                
                if ($facture_send == $total_facture_must) {
                    $this->updateField('next_facture_date', null);
                } else {
                    $this->updateField('next_facture_date', $dateEnd->add(new DateInterval('P1D'))->format('Y-m-d 00:00:00'));
                }

//                if ($this->getData('validate') == 1) {
//                    $this->actionValidateFacture(Array('id_facture' => $instance->id));
//                }
                $parent->renderEcheancier();
                //$instance = null;
            } else {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($instance->dol_object));
            }
        }
        
        if(array_key_exists("origine", $data) && $facture_ok) {
            return $instance->id;
        }

        return Array(
            'success' => $success,
            'warnings' => $warnings,
            'errors' => $errors,
        );
    }
    
    public function actionDelete($data, &$success) {
        $parent = $this->getParentInstance();
        if(count(getElementElement('contrat', 'facture', $parent->id))) {
            $errors = "Vous ne pouvez pas supprimer cet échéancier car il y à une facture dans celui-ci";
        } else {
            if($this->db->delete('bcontract_prelevement', 'id = ' . $this->id)) {
                $success = "Echéancier supprimé avec succès";
                $parent->addLog("Echéancier supprimé");
            } else {
                $errors = 'Une erreur est survenu lors de la suppression de l\'échéancier';
            }
        }
        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'success' => $success
        ];
    }
    
    public function renderHeaderEcheancier() {
        $html .= '<table class="noborder objectlistTable" style="border: none; min-width: 480px">';
        $html .= '<thead>';
        $html .= '<tr class="headerRow">';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Période de facturation<br />Début - Fin</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Montant HT</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Montant TVA</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Montant TTC</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">PA indicatif pour échéance non facturée</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Facture</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">&Eacute;tat du paiement</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Appartenance</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Action facture</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        
        return $html;
    }

    public function displayEcheancier($data, $display, $header = true) {
        global $user;

        if(!$this->isLoaded()) {
            return BimpRender::renderAlerts("Ce contrat ne comporte pas d'échéancier pour le moment", 'info', false);
        }
        
        $html = '';
        if(!$this->canEdit()) {
            $html = BimpRender::renderAlerts("Ce contrat est clos, aucune facture ne peut être emises", 'info', false);
        }

        $instance_facture = $this->getInstance('bimpcommercial', 'Bimp_Facture');
        $parent = $this->getParentInstance();
        $societe = $this->getInstance('bimpcore', "Bimp_Societe", $parent->getData('fk_soc'));
        
        if($header)
            $html .= $this->renderHeaderEcheancier();
        
        $html .= '<tbody class="listRows">';
        $callback = 'function(result) {if (typeof (result.file_url) !== \'undefined\' && result.file_url) {window.open(result.file_url)}}';
        $can_create_next_facture = $this->canEdit() ? true : false;
        
        switch($this->getData('renouvellement')) {
            case 0:
                $displayAppatenance = "<strong>Contrat initial</strong>";
                break;
            default:
                $displayAppatenance = "<strong>Renouvellement N°".$parent->getdata('current_renouvellement')."</strong>";
                break;
        }
        
        if ($data->factures_send) {
            $current_number_facture = 1;
            $acomptes_ht = 0;
            $acomptes_ttc = 0;
            $avoir = [];
            
            foreach($data->factures_send as $e) {
                $fact = $this->getInstance('bimpcommercial', 'Bimp_Facture', $e['d']);
                if($fact->getData('type') != 3 && $fact->getData('type') != 2) {
                    $id_avoir = $this->db->getValue('facture', 'rowid', 'type = 2 AND fk_facture_source = ' . $fact->id);
                    if($id_avoir) {
                        $avoir[$facture->dol_object->lines[0]->date_start] = ["FACTURE" => $fact->id, "AVOIR" => $id_avoir];
                    }
                }
            }
            
            foreach ($data->factures_send as $element_element) {
                $facture = $this->getInstance('bimpcommercial', 'Bimp_Facture', $element_element['d']);                
                
                if($facture->getData('type') != 3 && !$have_avoir && $facture->getData('type') != 2) {
 
                if ($facture->getData('fk_statut') == 0) {
                    $can_create_next_facture = false;
                }
                $paye = ($facture->getData('paye') == 1) ? '<b class="success" >Payée</b>' : '<b class="danger" >Impayé</b>';
                $html .= '<tr class="objectListItemRow" >';
                $dateDebut = New DateTime();
                $dateFin = New DateTime();
                $dateDebut->setTimestamp($facture->dol_object->lines[0]->date_start);
                $dateFin->setTimestamp($facture->dol_object->lines[0]->date_end);
                
                if($this->getData('old_to_new'))
                    $html .= '<td style="text-align:center" ><b>Ancienne facturation</b></td>';
                else
                    $html .= '<td style="text-align:center" >Du <b>' . $dateDebut->format("d/m/Y") . '</b> au <b>' . $dateFin->format('d/m/Y') . '</b></td>';
                
                
                
                $html .= '<td style="text-align:center"><b>' . price($facture->getData('total')) . ' €</b> </td>'
                        . '<td style="text-align:center"><b>' . price($facture->getData('tva')) . ' € </b></td>'
                        . '<td style="text-align:center"><b>' . price($facture->getData('total_ttc')) . ' €</b> </td>'
                        . '<td style="text-align:center"><b></b></td>'
                        . '<td style="text-align:center">' . $facture->getNomUrl(1) . '</td>'
                        . '<td style="text-align:center">' . $paye . '</td>'
                        . '<td style="text-align:center">' . $displayAppatenance . '</td>'
                        . '<td style="text-align:center; margin-right:10%">';
                if ($facture->getData('fk_statut') == 0 && $user->rights->facture->validate && $this->canEdit()) {
                    $html .= '<span class="rowButton bs-popover" data-trigger="hover" data-placement="top"  data-content="Valider la facture" onclick="' . $this->getJsActionOnclick("validateFacture", array('id_facture' => $facture->id), array("success_callback" => $callback)) . '")"><i class="fa fa-check" ></i></span>';
                } else {
                    $html .= '<span class="rowButton bs-popover" data-toggle="popover" data-trigger="hover" data-container="body" data-placement="top" data-content="Afficher la page dans un nouvel onglet" data-html="false" onclick="window.open(\'' . DOL_URL_ROOT . '/bimpcommercial/index.php?fc=facture&amp;id=' . $facture->id . '\');" data-original-title="" title=""><i class="fas fa5-external-link-alt"></i></span>';
                }
                if ($current_number_facture == count($data->factures_send) && $facture->getData('fk_statut') == 0 && $user->rights->facture->supprimer && $this->canEdit()) {
                    $html .= '<span class="rowButton bs-popover" data-trigger="hover" data-placement="top"  data-content="Supprimer la facture" onclick="' . $this->getJsActionOnclick("deleteFacture", array('id_facture' => $facture->id), array("success_callback" => $callback)) . '")"><i class="fa fa-times" ></i></span>';
                }

                $html .= '</td>';
                
                $html .= '</tr>';
                $current_number_facture++;
                } else {
                   // $facture->getSumDiscountsUsed() . "<br />";
                    $acomptes_ht += $facture->getData('total');
                    $acomptes_ttc += $facture->getData('total_ttc');
                }
            }
        }
        if ($this->getData('next_facture_date') != 0) {
            $startedDate = new DateTime($this->getInitData('next_facture_date'));
            $enderDate = new DateTime($this->getInitData('next_facture_date'));
            $enderDate->add(new DateInterval("P" . $data->periodicity . "M"))->sub(new DateInterval("P1D"));
            $firstPassage = true;
            $firstDinamycLine = true;
            
            $reste_periodeEntier = ceil($data->reste_periode);
            for ($i = 1; $i <= $reste_periodeEntier; $i++) {
                $morceauPeriode = (($data->reste_periode - ($i-1)) >= 1)? 1 : (($data->reste_periode - ($i-1)));
                if (!$firstPassage) {
                    $startedDate->add(new DateInterval("P" . $data->periodicity . "M"));
                } 

                $start_no_beggin_month = true;
                if ($startedDate->format('d') == '01') {
                    $start_mktime = date('Y-m-d', mktime(0, 0, 0, $startedDate->format('m'), 1, $startedDate->format('Y')));
                    $end_mktime = date('Y-m-d', mktime(0, 0, 0, $startedDate->format('m') + $data->periodicity, 0, $startedDate->format('Y')));
                    $dateTime_start_mkTime = new DateTime($start_mktime);
                    $dateTime_end_mkTime = new DateTime($end_mktime);
                } else {
                    $start_no_beggin_month = true;
                    $dateTime_start_mkTime = $startedDate;
                    $dateTime_end_mkTime = $enderDate;
                }
                
                
                if($parent->getEndDate() < $dateTime_end_mkTime)
                    $dateTime_end_mkTime = $parent->getEndDate();

                $firstPassage = false;
                $amount = $data->reste_a_payer / $data->reste_periode * $morceauPeriode;
                $tva = $amount * 0.2;
                $nb_periode = ceil($parent->getData('duree_mois') / $parent->getData('periodicity'));
                $pa = $parent->getTotalPa() / $nb_periode; 
                if(!$display) {
                    $none_display = [
                        'total_ht' => $amount,
                        'pa' => $pa,
                        'date_start' => $dateTime_start_mkTime->format('Y-m-d'),
                        'date_end' => $dateTime_end_mkTime->format('Y-m-d'),
                        'origine' => 'cron'
                    ];
                    return $none_display;
                }
                
                $html .= '<tr class="objectListItemRow" >';
                $html .= '<td style="text-align:center" >Du <b>' . $dateTime_start_mkTime->format('d/m/Y') . '</b> au <b>' . $dateTime_end_mkTime->format('d/m/Y') . '</b>';
                
                // Faire ce qu'ilm y à faire pour les avoir à cet endroit
                
                $html .= '</td>';
 
                $html .= '<td style="text-align:center">' . price($amount) . ' € </td>'
                        . '<td style="text-align:center">' . price($tva) . ' € </td>'
                        . '<td style="text-align:center">' . price($amount + $tva) . ' € </td>'
                        . '<td style="text-align:center">' . ($pa) . '€</td>'
                        . '<td style="text-align:center"><b style="color:grey">Période non facturée</b></td>'
                        . '<td style="text-align:center"><b class="important" >Période non facturée</b></td>'
                        . '<td style="text-align:center">' . $displayAppatenance . '</td>'
                        . '<td style="text-align:center; margin-right:10%">';
                if ($firstDinamycLine && $can_create_next_facture) {
                    // ICI NE PAS AFFICHER QUAND LA FACTURE EST PAS VALIDER
                    if ($user->rights->facture->creer && $this->canEdit()) {
                        $html .= '<span class="rowButton bs-popover" data-trigger="hover" data-placement="top"  data-content="Facturer la période" onclick="' . $this->getJsActionOnclick("createFacture", array('date_start' => $dateTime_start_mkTime->format('Y-m-d'), 'date_end' => $dateTime_end_mkTime->format('Y-m-d'), 'total_ht' => $amount, 'pa' => $pa), array("success_callback" => $callback)) . '")"><i class="fa fa-plus" ></i></span>';
                    }
                    $firstDinamycLine = false;
                }
                $html .= '</tr>';
                if ($start_no_beggin_month) {
                    $enderDate->add(new DateInterval("P" . $data->periodicity . "M"));
                }
            }
            $html .= '</tbody>';
            $html .= '</table>';

            if (($parent->is_not_finish() && $user->rights->facture->creer) || ($user->admin || $user->id == 460)) {
                $html .= '<div class="panel-footer">';
                if(($user->admin) && $this->canEdit()) {
                    $html .= '<div class="btn-group"><button type="button" class="btn btn-danger bs-popover" '.BimpRender::renderPopoverData('Supprimer l\'échéancier').' aria-haspopup="true" aria-expanded="false" onclick="' . $this->getJsActionOnclick('delete') . '"><i class="fa fa-times"></i></button></div>';
                }
                if($user->admin) {
                    $html .= '<div class="btn-group"><button type="button" class="btn btn-danger bs-popover" '.BimpRender::renderPopoverData('Refaire l\'échéancier suite à un avoir (ADMIN)').' aria-haspopup="true" aria-expanded="false" onclick="' . $this->getJsActionOnclick('unlinkLastFacture', [], ['form_name' => 'unlinkLastFacture']) . '"><i class="fa fa-times"></i> Refaire l\'échéancier suite à un avoir (ADMIN)</button></div>';
                }
                if($this->canEdit()) {
                    $html .= '<div class="btn-group"><button type="button" class="btn btn-default" aria-haspopup="true" aria-expanded="false" onclick="' . $this->getJsLoadModalForm('create_perso', "Créer une facture personnalisée ou une facturation de plusieurs périodes") . '"><i class="fa fa-plus-square-o iconLeft"></i>Créer une facture personalisée ou une facturation de plusieurs périodes</button></div>';
                }
                $html .= '</div>';
            }
        }

        $html .= "<br/>"
                . "<table style='float:right' class='border' border='1'>"
                . "<tr> <th style='border-right: 1px solid black; border-top: 1px solid white; border-left: 1px solid white; width: 20%'></th>  <th style='background-color:#ed7c1c;color:white;text-align:center'>Montant HT</th> <th style='background-color:#ed7c1c;color:white;text-align:center'>Montant TTC</th> </tr>"
                . "<tr> <th style='background-color:#ed7c1c;color:white;text-align:center'>Contrat</th> <td style='text-align:center'><b>" . price($parent->getTotalContrat()) . " €</b></td> <td style='text-align:center'><b> " . price($parent->getTotalContrat() * 1.20) . " €</b></td> </tr>"
                . "<tr > <th  style='background-color:#ed7c1c;color:white;text-align:center'>Facturé</th> <td style='text-align:center'><b class='important' > " . price($parent->getTotalDejaPayer()) . " € </b></td> <td style='text-align:center'><b class='important'> " . price($parent->getTotalDejaPayer() * 1.20) . " €</b></td> </tr>";
        
        if ($parent->getTotalDejaPayer(true) == $parent->getTotalContrat()) {
            $html .= "<tr > <th  style='background-color:#ed7c1c;color:white;text-align:center'>Payé</th> <td style='text-align:center'><b class='success'> " . price($parent->getTotalDejaPayer(true)) . " € </b></td> <td style='text-align:center'><b class='success'> " . price($parent->getTotalDejaPayer(true) * 1.20) . " €</b></td> </tr>";
        } else {
            $html .= "<tr > <th  style='background-color:#ed7c1c;color:white;text-align:center'>Payé</th> <td style='text-align:center'><b class='danger'> " . price($parent->getTotalDejaPayer(true)) . " € </b></td> <td style='text-align:center'><b class='danger'> " . price($parent->getTotalDejaPayer(true) * 1.20) . " €</b></td> </tr>";
        }
        
        

        if ($parent->getTotalContrat() - $parent->getTotalDejaPayer() == 0) {
            $html .= "<tr > <th  style='background-color:#ed7c1c;color:white;text-align:center'>Reste à facturer</th> <td style='text-align:center'><b class='success'> " . price($parent->getTotalContrat() - $parent->getTotalDejaPayer()) . " € </b></td> <td style='text-align:center'><b class='success'> " . price(($parent->getTotalContrat() - $parent->getTotalDejaPayer()) * 1.20) . " €</b></td> </tr>";
        } else {
            $html .= "<tr > <th  style='background-color:#ed7c1c;color:white;text-align:center'>Reste à facturer</th> <td style='text-align:center'><b class='danger'> " . price($parent->getTotalContrat() - $parent->getTotalDejaPayer()) . " € </b></td> <td style='text-align:center'><b class='danger'> " . price(($parent->getTotalContrat() - $parent->getTotalDejaPayer()) * 1.20) . " €</b></td> </tr>";
        }

        if ($parent->getTotalContrat() - $parent->getTotalDejaPayer(true) == 0) {
            $html .= "<tr> <th style='background-color:#ed7c1c;color:white;text-align:center'>Reste à payer</th> <td style='text-align:center'><b class='success'> 0 € </b></td> <td style='text-align:center'><b class='success'>0 €</b></td> </tr>";
        } else {
            $html .= "<tr> <th style='background-color:#ed7c1c;color:white;text-align:center'>Reste à payer</th> <td style='text-align:center'><b class='danger'> " . price($parent->getTotalContrat() - $parent->getTotalDejaPayer(true)) . " € </b></td> <td style='text-align:center'><b class='danger'> " . price(($parent->getTotalContrat(true) * 1.20) - ($parent->getTotalDejaPayer(true) * 1.20)) . " €</b></td> </tr>";
        }
        if($acomptes_ht != 0 && $acomptes_ttc != 0) {
            
            $html .= "<tr> <th style='background-color:#ed7c1c;color:white;text-align:center'>Dont acompte</th> <td style='text-align:center'><b class='success'> ".price($acomptes_ht)."€ </b></td><td style='text-align:center'><b class='success'>".price($acomptes_ttc)."€</b></td></tr>";
            
        }
        $html .= "</table>";

        
        if($display)
            return $html;
        
        
    }

    public function actionDeleteFacture($data, &$success) {

        global $user;
        $parent = $this->getParentInstance();
        $instance = $this->getInstance('bimpcommercial', 'Bimp_Facture', $data['id_facture']);

        $dateDebutFacture = $instance->dol_object->lines[0]->date_start;
        
        if ($instance->dol_object->delete($user) > 0) {
            $this->onDeleteFacture($dateDebutFacture);
            $success = "Facture " . $instance->getData('facnumber') . ' supprimée avec succès';
        } else {
            $errors = "Facture " . $instance->getData('facnumber') . ' n\'à pas été supprimée';
            ;
        }

        return Array(
            'success' => $success,
            'errors' => $errors,
            'warnings' => $warnings
        );
    }
    
    public function onDeleteFacture($dateDebutFacture) {

        $new_next_date = new DateTime();
        $new_next_date->setTimestamp($dateDebutFacture);
        $this->updateField('next_facture_date', $new_next_date->format('Y-m-d 00:00:00'));
        $this->switch_statut();
    }
    
    
    public function parentGetData($field) {
        $parent = $this->getInstance('bimpcontract', 'BContract_contrat', $this->getData('id_contrat'));
        $returned_info = $parent->getData($field);
    }
    
    public function displayParentInfos($field, $module = null, $bimp_object = null) {

        $parent = $this->getParentInstance();

        $returned_info = $parent->getData($field);

        if (!is_null($module) && !is_null($bimp_object)) {
            $instance = $this->getInstance($module, $bimp_object, $returned_info);
            return $instance->getNomUrl();
        }

        return $returned_info;
    }
    
    public function getListFilter() {
        
        
    }
    
    public function getListExtraButtons()
    {
        global $user;
        $buttons = [];
        
        $parent = $this->getParentInstance();
        
            
            if(($user->rights->bimpcontract->stop_bills_timeline || $user->admin) && $this->getData('statut') == self::STATUT_EN_COURS ) {
                $buttons[] = array(
                    'label'   => 'Stoper la facturation',
                    'icon'    => 'fas_times',
                    'onclick' => $this->getJsActionOnclick('stopBill', array(), array(
                        'confirm_msg' => "Cette action est irréverssible, continuer ?",
                    ))
                );
            }
            
            return $buttons;
        }
    
    public function switch_statut() {
        $parent = $this->getParentInstance();
        $new = self::STATUT_EN_COURS;
        if($this->isTotalyFactured() || $parent->getData('statut') == 2) {
            $new = self::STATUT_IS_FINISH;
        }
        $this->updateField('statut', $new);
        
        return $new;
    }
    
    public function isTotalyFactured() {
        $parent = $this->getParentInstance();
        $nombre_total_facture = $parent->getData('duree_mois') / $parent->getData('periodicity');
        $nombre_fature_send = count(getElementElement('contrat', 'facture', $this->getData('id_contrat')));
        
        if($nombre_fature_send == $nombre_total_facture)
            return 1;
        
        return 0;
    }

    public function displayFactureEmises() {

        $class = 'danger';
        $class_periode = 'danger';
        $parent = $this->getParentInstance();

        if ($parent->getData('duree_mois') > 0) {
            if ($parent->isLoaded()) {
                if($parent->getData('periodicity') > 0) {
                    $reste_periode = $parent->reste_periode();
                    $nombre_total_facture = $parent->getData('duree_mois') / $parent->getData('periodicity');
                    $nombre_fature_send = count(getElementElement('contrat', 'facture', $this->getData('id_contrat')));
                    $review_view = false;
                    $popover_periode = $reste_periode . ' ';
                    $popover_periode .= ($reste_periode > 1) ? 'périodes' : 'période';
                    $popover_periode .= ' encore à facturer';

                    $affichage_nombre_facture_total = $nombre_total_facture;

                    $popover = 'Facture émises (' . $nombre_fature_send . ') / Nombre période (' . $nombre_total_facture . ') ';


                    if (($nombre_fature_send > 0 && $nombre_fature_send < $nombre_total_facture) && (ceil($reste_periode) > 0 && $nombre_fature_send > 0)) {
                        $class = "warning";
                        if ($nombre_fature_send + $reste_periode != $nombre_total_facture) {
                            $review_view = true;
                        }
                    } elseif (($nombre_fature_send == $nombre_total_facture) || ($reste_periode < 1)) {
                        $class = 'success';
                        $affichage_nombre_facture_total = $nombre_fature_send;
                        $popover = 'Facturation terminée';
                    }

                    if (!$review_view)
                        $returned_data = '<b class="' . $class . ' bs-popover" ' . BimpRender::renderPopoverData($popover, 'top') . ' >' . '<i class="fas fa5-file-invoice-dollar iconLeft" ></i>' . $nombre_fature_send . ' / ' . $affichage_nombre_facture_total . '' . '</b>';
                    else
                        $returned_data = '<b class="' . $class . ' bs-popover" ' . BimpRender::renderPopoverData('Factures émises (' . $nombre_fature_send . ') / Nombre de périodes (' . ($reste_periode + 1) . ') au lieu de ' . $nombre_total_facture . ' périodes théorique', 'top') . ' >' . '<i class="fas fa5-file-invoice-dollar iconLeft" ></i>' . $nombre_fature_send . ' / ' . ($reste_periode + 1) . '' . '</b>';

                    if ($reste_periode > 0 && $reste_periode <= $nombre_total_facture) {
                        $class_periode = "warning";
                        $returned_data .= ' <b class="' . $class_periode . ' bs-popover" ' . BimpRender::renderPopoverData($popover_periode, 'top') . ' >' . '<i style="margin-left:30px" class="fas fa-hourglass-half iconLeft"></i>' . $reste_periode . '</b>';
                    }
                } else {
                    $returned_data = "<b class='important'>Aucune periodicitée de facturation</b>";
                }
                
            } else {
                $returned_data = "<b class='danger'>Ce contrat n'existe plus</b>";
            }
        } else {
            $returned_data = '<b class="info" >Ce contrat ne comporte pas d\'échéancier</b>';
        }
        return $returned_data;
    }

    public function displayNextFactureDate() {

        $next = $this->getData('next_facture_date');
        $parent = $this->getParentInstance();
        if ($next != 0 && $parent->getData('duree_mois') > 0) {
            $alert = "";
            $dateTime = new DateTime($next);

            return '<b>' . $dateTime->format('d/m/Y') . '</b>';
        } elseif ($parent->getData('duree_mois') <= 0) {
            return '<b class="info" >Ce contrat n’est pas facturé par un échéancier</b>';
        }

        return '<b class="important" >Echéancier totalement facturé</b>';
    }

    public function displayRetard() {
        $parent = $this->getParentInstance();
        $alert = "<b class='success bs-popover' " . BimpRender::renderPopoverData('Facturation à jour') . ">" . BimpRender::renderIcon('check') . "</b>";
        $next = $this->getData('next_facture_date');
        $dateTime = new DateTime($next);
        $toDay = new DateTime();
        if ($this->isEnRetard()) {
            $popover = BimpRender::renderPopoverData('Retard de facturation de ' . $toDay->diff($dateTime)->d . ' Jours', 'top');
            $alert = '<b class="danger bs-popover" ' . $popover . ' >' . BimpRender::renderIcon('warning') . '</b>';
        }

        if ($parent->getData('duree_mois') > 0)
            return $alert;
        else
            return '';
    }

}
