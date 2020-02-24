<?php

class BContract_echeancier extends BimpObject {

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

    public function isEnRetard() {
        $aujourdui = new DateTime();
        $next = new Datetime($this->getData('next_facture_date'));
        $diff = $aujourdui->diff($next);
        if ($this->getData('next_facture_date') > 0 && $diff->invert == 1 && $diff->d > 0) {
            return 1;
        }
        return 0;
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
        if($parent->getData('statut') == 2)
            return false;
        
        return true;
    }

    public function renderlistEndPeriod() {
        $parent = $this->getParentInstance();
        $start = New DateTime($this->getData('next_facture_date'));
//        $for_return_array_end_date = date('Y-m-d', mktime(0, 0, 0, $start->format('m') + 1, 0, $start->format('Y')));
//        $for_return_array_end_date = $start->add(new DateInterval("P" . $parent->getData('periodicity') . 'M'));
        
        
        $start->add(new DateInterval("P" . $parent->getData('periodicity') . 'M'));
        $start->sub(new dateInterval('P1D'));
        $for_return_array_end_date = $start->format('Y-m-d');
        
        $for_return_array_start_date = date('Y-m-d', mktime(0, 0, 0, $start->format('m'), 1, $start->format('Y')));
        $dateTime_end_date = new DateTime($for_return_array_end_date);
        $reste_periode = $parent->reste_periode();
        $returnedArray = Array();

        
        $reste_periodeEntier = ceil($reste_periode);
        for ($rp = 1; $rp <= $reste_periodeEntier; $rp++) {
            $returnedArray[$dateTime_end_date->format('Y-m-d H:i:s')] = $dateTime_end_date->format('d/m/Y');
            $start->add(new DateInterval("P" . $parent->getData('periodicity') . 'M'));
            $for_return_array_end_date = date('Y-m-d', mktime(0, 0, 0, $start->format('m') + 1, 0, $start->format('Y')));
            $dateTime_end_date = new DateTime($for_return_array_end_date);
            if($parent->getEndDate() < $dateTime_end_date)
                $dateTime_end_date = $parent->getEndDate();
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
        // VOIR POURQUOI LORS DE LA PREMIERE FOIS QUON EST DANS LECHEANCIER LE CLIENT NE SE MET PAS DU TOUS
        $parent = $this->getParentInstance();
        $client = $this->getInstance('bimpcore', 'Bimp_Societe', $parent->getInitData('fk_soc'));

        $linked_propal = $this->db->getValue('element_element', 'fk_source', 'targettype = "contrat" and fk_target = ' . $parent->id);

        $propal = $this->getInstance('bimpcommercial', 'Bimp_Propal', $linked_propal);
        $ef_type = ($propal->getData('ef_type') == "E") ? 'CTE': 'CTC';
        $instance = $this->getInstance('bimpcommercial', 'Bimp_Facture');
        $instance->set('fk_soc', ($parent->getData('fk_soc_facturation')) ? $parent->getData('fk_soc_facturation') : $parent->getData('fk_soc'));
        $instance->set('libelle', 'Facture du contrat N°' . $parent->getData('ref'));
        $instance->set('type', 0);
        $instance->set('fk_account', 1);
        
        if(!$parent->getData('entrepot')) {
            return "La facture ne peut pas être créer car le contrat n'à pas d'entrepot";
        }
        
        $instance->set('entrepot', $parent->getData('entrepot'));
        $instance->set('fk_cond_reglement', ($client->getData('cond_reglement')) ? $client->getData('cond_reglement') : 2);
        $instance->set('fk_mode_reglement', ($parent->getData('moderegl')) ? $parent->getData('moderegl') : 2);
        $instance->set('datef', date('Y-m-d H:i:s'));
        $instance->set('ef_type', $ef_type);
        $instance->set('model_pdf', 'bimpfact');
        $lines = $this->getInstance('bimpcontract', 'BContract_contratLine');
        $desc = "<b><u>Services du contrat :</b></u>" . "<br /><br />";
        foreach ($lines->getList(['fk_contrat' => $parent->id]) as $idLine => $infos) {
            $desc .= $infos['description'] . "<br /><br />";
        }
        $errors = $instance->create($warnings = Array(), true);
        if (!count($errors)) {

            $dateStart = new DateTime($data['date_start']);
            $dateEnd = new DateTime($data['date_end']);


            if ($instance->dol_object->addline("Facturation pour la période du <b>" . $dateStart->format('d/m/Y') . "</b> au <b>" . $dateEnd->format('d/m/Y') . "</b><br /><br />" . $desc, (double) $data['total_ht'], 1, 20, 0, 0, 0, 0, $data['date_start'], $data['date_end'], 0, 0, '', 'HT', 0, 1) > 0) {
                $success = 'Facture créer avec succès';
                addElementElement("contrat", "facture", $parent->id, $instance->id);

                $facture_send = count(getElementElement('contrat', 'facture', $parent->id));
                $total_facture_must = $parent->getData('duree_mois') / $parent->getData('periodicity');

                if ($facture_send == $total_facture_must) {
                    $this->updateField('next_facture_date', null);
                } else {
                    $this->updateField('next_facture_date', $dateEnd->add(new DateInterval('P1D'))->format('Y-m-d H:i:s'));
                }

                if ($this->getData('validate') == 1) {
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
    
    public function actionDelete($data, &$success) {
        $parent = $this->getParentInstance();
        if(count(getElementElement('contrat', 'facture', $parent->id))) {
            $errors = "Vous ne pouvez pas supprimer cet échéancier car il y à une facture dans celui-ci";
        } else {
            if($this->db->delete('bcontract_prelevement', 'id = ' . $this->id)) {
                $success = "Echéancier supprimé avec succès";
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

    public function displayEcheancier($data) {
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
        $can_create_next_facture = $this->canEdit() ? true : false;
        if ($data->factures_send) {
            $current_number_facture = 1;
            foreach ($data->factures_send as $element_element) {
                $facture = $this->getInstance('bimpcommercial', 'Bimp_Facture', $element_element['d']);
//                if($facture->getData('fk_facture_source')) {
//                    $array_avoirs = ['facture' => $facture->id, 'avoir' => $facture->getData('fk_facture_source')];
//                    continue;
//                }
                
                
                if ($facture->getData('fk_statut') == 0) {
                    $can_create_next_facture = false;
                }
                $paye = ($facture->getData('paye') == 1) ? '<b class="success" >Payée</b>' : '<b class="danger" >Impayée</b>';
                $html .= '<tr class="objectListItemRow" >';
                $dateDebut = New DateTime();
                $dateFin = New DateTime();
                $dateDebut->setTimestamp($facture->dol_object->lines[0]->date_start);
                $dateFin->setTimestamp($facture->dol_object->lines[0]->date_end);
                $html .= '<td style="text-align:center" >Du <b>' . $dateDebut->format("d/m/Y") . '</b> au <b>' . $dateFin->format('d/m/Y') . '</b></td>';
                $html .= '<td style="text-align:center"><b>' . price($facture->getData('total')) . ' €</b> </td>'
                        . '<td style="text-align:center"><b>' . price($facture->getData('tva')) . ' € </b></td>'
                        . '<td style="text-align:center"><b>' . price($facture->getData('total_ttc')) . ' €</b> </td>'
                        . '<td style="text-align:center">' . $facture->getNomUrl(1) . '</td>'
                        . '<td style="text-align:center">' . $paye . '</td>'
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
                $html .= '<tr class="objectListItemRow" >';
                $html .= '<td style="text-align:center" >Du <b>' . $dateTime_start_mkTime->format('d/m/Y') . '</b> au <b>' . $dateTime_end_mkTime->format('d/m/Y') . '</b></td>';
                $html .= '<td style="text-align:center">' . price($amount) . ' € </td>'
                        . '<td style="text-align:center">' . price($tva) . ' € </td>'
                        . '<td style="text-align:center">' . price($amount + $tva) . ' € </td>'
                        . '<td style="text-align:center"><b style="color:grey">Période non facturée</b></td>'
                        . '<td style="text-align:center"><b class="important" >Période non facturée</b></td>'
                        . '<td style="text-align:center; margin-right:10%">';
                if ($firstDinamycLine && $can_create_next_facture) {
                    // ICI NE PAS AFFICHER QUAND LA FACTURE EST PAS VALIDER
                    if ($user->rights->facture->creer && $this->canEdit()) {
                        $html .= '<span class="rowButton bs-popover" data-trigger="hover" data-placement="top"  data-content="Facturer la période" onclick="' . $this->getJsActionOnclick("createFacture", array('date_start' => $dateTime_start_mkTime->format('Y-m-d'), 'date_end' => $dateTime_end_mkTime->format('Y-m-d'), 'total_ht' => $amount), array("success_callback" => $callback)) . '")"><i class="fa fa-plus" ></i></span>';
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
                if($this->canEdit()) {
                    $html .= '<div class="btn-group"><button type="button" class="btn btn-default" aria-haspopup="true" aria-expanded="false" onclick="' . $this->getJsLoadModalForm('create_perso', "Créer une facture personalisée ou une facturation de plusieurs périodes") . '"><i class="fa fa-plus-square-o iconLeft"></i>Créer une facture personalisée ou une facturation de plusieurs périodes</button></div>';
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
        $html .= "</table>";


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

    public function displayFactureEmises() {

        $class = 'danger';
        $class_periode = 'danger';
        $parent = $this->getParentInstance();

        if ($parent->getData('duree_mois') > 0) {
            if ($parent->isLoaded()) {
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
