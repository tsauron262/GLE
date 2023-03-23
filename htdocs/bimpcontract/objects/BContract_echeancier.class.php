<?php

class BContract_echeancier extends BimpObject
{

    CONST STATUT_EN_COURS = 1;
    CONST STATUT_IS_FINISH = 0;

    public $statut_list = [
        self::STATUT_EN_COURS  => ['label' => "En cours de facturation", 'classes' => ['success'], 'icon' => 'fas_play'],
        self::STATUT_IS_FINISH => ['label' => "Facturation terminée", 'classes' => ['danger'], 'icon' => 'fas_times']
    ];

    public function cronEcheancier()
    {
        // recuperer tous les echeancier passer
        $echeanciers = $this->getList();
        $aujourdui = new DateTime();
        foreach ($echeanciers as $echeancier) {
            $next = new Datetime($echeancier['next_facture_date']);
            $diff = $aujourdui->diff($next);
            $msg = null;
            $parent = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', $echeancier['id_contrat']);
            if ($this->isEnRetard()) {
                $msg = "Bonjour,<br />L'échéancier du contrat N°" . $parent->getNomUrl() . ' est en retard de facturation de ' . $diff->d . ' Jours<br /> Merci de faire le nécéssaire pour régulariser la facturation';
            }
//            if(!is_null($msg))
//                mailSyn2("Echéancier du contrat N°" . $parent->getData('ref'), 'al.bernard@bimp.fr', null, $msg);
        }
    }

    public function actionStopBill()
    {

        $parent = $this->getParentInstance();
        $errors = $this->updateField("statut", self::STATUT_IS_FINISH);

        if (!count($errors)) {
            $success = "Facturation stoppée avec succès.";
            $parent->addLog("Facturation stoppée");
        }

        return [
            'success'  => $success,
            'errors'   => $errors,
            'warnings' => []
        ];
    }

    public function displayCommercialContrat()
    {
        if ($this->isLoaded()) {
            $parent = $this->getParentInstance();

            $commercial = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $parent->getData('fk_commercial_suivi'));

            return "<a target='_blank' href='" . $commercial->getUrl() . "'>" . $commercial->getData('firstname') . " " . $commercial->getData('lastname') . " </a>";
        }
    }

    public function isEnRetard()
    {
        $parent = $this->getParentInstance();
        $aujourdui = new DateTime();
        $next = new Datetime($this->getData('next_facture_date'));
        $diff = $aujourdui->diff($next);
        if ($parent->getData('facturation_echu') == 1) {
            if ($parent->getData('periodicity')) {
                $finPeriode = $next->add(new DateInterval('P' . $parent->getData('periodicity') . 'M'));
                $finPeriode->sub(new DateInterval('P1D'));

                if (strtotime($aujourdui->format('Y-m-d')) >= strtotime($this->getData('next_facture_date')) &&
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
        $lastFactureId = $this->getLastFactureId();
        if ($lastFactureId > 0) {
            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $lastFactureId);
            if ($facture->isLoaded()) {
                if ($facture->getData('fk_statut') == 0) {
                    return 1;
                }
            }
        }


        return 0;
    }

    public function isDejaFactured($date_start, $date_end)
    {
        $parent = $this->getParentInstance();
        $correspondance = 0;
        $listeFactures = getElementElement("contrat", 'facture', $parent->id);
        if (count($listeFactures) > 0) {
            foreach ($listeFactures as $index => $i) {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $i['d']);
                if (1) {
                    $dateDebut = New DateTime();
                    $dateFin = New DateTime();
                    if (is_int($facture->dol_object->lines[0]->date_start) && is_int($facture->dol_object->lines[0]->date_end)) {
                        $dateDebut->setTimestamp($facture->dol_object->lines[0]->date_start);
                        $dateFin->setTimestamp($facture->dol_object->lines[0]->date_end);
                        if ($dateDebut->format('Y-m-d') == $date_start && $dateFin->format('Y-m-d') == $date_end) {
                            $correspondance++;
                        }
                    }
                }
            }
        }
        if ($correspondance == 1) {
            $parent->addLog("Facturation de la même periode stopée automatiquement ");
            return true;
        }
        return false;
    }

    public function getDateNextPeriodRegen()
    {
        $facture = BimpCache::getBimpObjectInstance("bimpcommercial", 'Bimp_Facture', $this->getLastFactureId());
        $date = new DateTime();
        $date->setTimestamp($facture->dol_object->lines[0]->date_start);
        return $date->format('Y-m-d');
    }

    public function renderLastFactureCard($avoir = 0)
    {

        $facture_avoir = null;
        $card = null;
        $facture = null;
        if ($this->getLastFactureId() > 0)
            $facture = BimpCache::getBimpObjectInstance("bimpcommercial", 'Bimp_Facture', $this->getLastFactureId());
        if ($avoir) {
            $facture_avoir = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $this->getLastFactureAvoirId($facture->id));
        }

        if (is_object($facture_avoir) && $facture_avoir->isLoaded()) {
            $card = New BC_Card($facture_avoir);
        } elseif (!$avoir) {
            if (is_object($facture)) {
                $card = New BC_Card($facture);
            }
        }

        if (is_object($card))
            return $card->renderHtml();
        else
            return BimpRender::renderAlerts("Il n'y à pas de facture à dé-lier", 'warning', false);
    }

    public function actionUnlinkLastFacture($data, &$success)
    {
        $errors = array();
        //echo '<pre>' . print_r($data);
        if (!$this->isToujoursTheLastFacture($data['id_facture_unlink'])) {
            $errors[] = "Vous ne pouvez plus faire cette action car la facture du formulaire n'est pas la dernière facture de l'échéancier";
        }

        if (!count($errors)) {
            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $data['id_facture_unlink']);

            if ($data['is_good_avoir'] == 1) {
                $id_avoir = $data['id_avoir_unlink'];
            } else {
                $id_avoir = $data['new_avoir'];
            }

            $avoir = BimpCache::getBimpObjectInstance('bimpcommercial', "Bimp_Facture", $id_avoir);

            if ($avoir->getData('fk_statut') == 0) {
                $errors[] = "L'opération ne peut être faite sur un avoir BROUILLON";
            }

            if ($facture->getData('fk_statut') == 0) {
                $errors[] = "L'opération ne peut être faite sur une facture BROUILLON";
            }

            if ($data['id_avoir_unlink'] == 0) {
                $errors[] = "L'opération ne peut pas être faite sans avoir sur la facture";
            }

            if ($facture->getData('total_ttc') != abs($avoir->getData('total_ttc'))) {
                $errors[] = "La facture et l'avoir ont un montant différent";
            }

            if (!count($errors)) {
                $parent = $this->getParentInstance();
                $next_date = new DateTime($data['date_next_facture']);
                delElementElement('contrat', 'facture', $parent->id, $facture->id);
                delElementElement('contrat', 'facture', $parent->id, $avoir->id);
                $this->updateField('next_facture_date', $next_date->format('Y-m-d 00:00:00'));
                $parent->addLog('<br /><strong>Echéancier regénéré</strong><br />Date de prochaine facture: ' . $next_date->format('d/m/Y') . '<br />Facture dé-link: ' . $facture->getRef() . '<br />Avoir dé-link: ' . $avoir->getRef());
            }
        }

        return [
            'errors'    => $errors,
            'success'   => $success,
            'warnnings' => $warnings
        ];
    }

    public function getLastFactureId()
    {
        $liste = getElementElement('contrat', 'facture', $this->getParentId());
        $lastId = 0;
        foreach ($liste as $index => $i) {
            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $i['d']);
            if ($facture->getData('type') == 0) {
                if ($facture->id > $lastId) {
                    $lastId = $facture->id;
                }
            }
        }
        return $lastId;
    }

    public function getLastFactureAvoirId($id_facture = 0)
    {
        if ($id_facture == 0)
            $facture = BimpCache::getBimpObjectInstance(('bimpcommercial'), 'Bimp_Facture', $this->getLastFactureId());
        else
            $facture = BimpCache::getBimpObjectInstance(('bimpcommercial'), 'Bimp_Facture', $id_facture);

        $facture_avoir = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture');
        if ($facture_avoir->find(['fk_facture_source' => $facture->id, 'type' => 2], true)) {
            return $facture_avoir->id;
        }
        return 0;
    }

    public function isToujoursTheLastFacture($id)
    {
        if ($this->getLastFactureId() == $id) {
            return true;
        } else {
            return false;
        }
    }

    public function isClosDansCombienDeTemps()
    {

        $parent = $this->getParentInstance();
        $aujourdhui = new DateTime();
        $finContrat = new DateTime($parent->displayRealEndDate("Y-m-d"));
        $diff = $aujourdhui->diff($finContrat);

        return ($diff->invert == 1 || $diff->d == 0) ? 1 : 0;
    }

    public function canViewObject($object)
    {
        if (is_object($object))
            return true;
        return false;
    }

    public function canEdit()
    {

        $parent = $this->getParentInstance();
        if ($parent->getData('statut') == 2 || $parent->getData('statut') == -1)
            return false;

        return true;
    }

    public function renderlistEndPeriod()
    {
        $parent = $this->getParentInstance();
        $start = New DateTime($this->getData('next_facture_date'));

        //$start->add(new DateInterval("P" . $parent->getData('periodicity') . 'M'));
        $stop = $start->sub(new dateInterval('P1D'));
        $end_date_contrat = new DateTime($parent->displayRealEndDate("Y-m-d"));
        $reste_periodeEntier = ceil($parent->reste_periode());
        for ($rp = 0; $rp <= $reste_periodeEntier; $rp++) {
            $stop->add(new DateInterval('P' . $parent->getData('periodicity') . "M"));
            if ($end_date_contrat < $stop)
                $stop = $end_date_contrat;
            $returnedArray[$stop->format('Y-m-d 00:00:00')] = $stop->format('d/m/Y');
        }

        return $returnedArray;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $errors = [];
        $warnings = [];
        $success = "";
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
                    $nb = (($interval->y * 12) + $interval->m) / $parent->getData('periodicity');
                }
                $montant = ($reste_a_payer / $reste_periode) * $nb;
            }

            if ($montant > $parent->reste_a_payer()) {
                return "Vous ne pouvez pas indiquer un montant (" . $montant . ") suppérieur au reste à payer " . $parent->reste_a_payer();
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

        return [
            'success'  => $success,
            'warnings' => $warnings,
            'errors'   => $errors
        ];
    }

    public function actionValidateFacture($data, &$success = '')
    {
        global $user;

        $instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $data['id_facture']);
        if ($instance->dol_object->validate($user) > 0) {
            $success = 'Facture <b>' . $facture->ref . '</b> validée avec succès';
        } else {
            $errors = 'La facture <b>' . $facture->ref . '</b> n\'à pas été validé, merci de vérifier les champs obligatoire de la facture';
        }

        return Array(
            'success'  => $success,
            'warnings' => $warnings,
            'errors'   => $errors,
        );
    }

    public function actionSolderPeriodeByFactureExterne($data, &$success)
    {
        global $user;
        $obj = json_decode($this->getData('facturesExterne_soldePeriode'));
        $named = $data['date_start'] . '_' . $data['date_end'];
        $obj->$named = Array('ref' => $data['factureExterne'], 'ht' => $data['total_ht'], 'by' => $user->id);
        $errors = $this->updateField('facturesExterne_soldePeriode', json_encode($obj));
        if (!count($errors))
            $success = 'Facture ' . $data['factureExterne'] . ' bien pris en compte pour cette periode';
        return Array('success' => $success, 'warnings' => $warnings, 'errors' => $errors);
    }

    public function getFacturesExterneByPeriode($periode = '')
    {
        $return = [];

        if ($periode != '') {

            $obj = (array) json_decode($this->getData('facturesExterne_soldePeriode'));
            if (array_key_exists($periode, $obj)) {

                $return['ref'] = $obj[$periode]->ref;
                $return['ht'] = $obj[$periode]->ht;
                $return['tva'] = $obj[$periode]->ht * 0.2;
                $return['ttc'] = $return['ht'] + $return['tva'];
            }
        }

        return $return;
    }

    public function actionCreateFacture($data, &$success = '')
    {
        $errors = $warnings = [];

        if ($this->isDejaFactured($data['date_start'], $data['date_end'])) {
            return array('errors' => array("Contrat déjà facturé pour cette période, merci de rafraîchir la page pour voir cette facture dans l'échéancier"));
        }

        $parent = $this->getParentInstance();
        $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $parent->getInitData('fk_soc'));

        $linked_propal = $this->db->getValue('element_element', 'fk_source', 'targettype = "contrat" and fk_target = ' . $parent->id);

        $propal = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', $linked_propal);
        $ef_type = ($propal->getData('ef_type') == "E") ? 'CTE' : 'CTC';
        $instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture');
        $instance->set('fk_soc', ($parent->getData('fk_soc_facturation')) ? $parent->getData('fk_soc_facturation') : $parent->getData('fk_soc'));

        $bill_label = (isset($data['label']) ? $data['label'] . ' ' : '') . "Facture " . $parent->displayPeriode();
        $bill_label .= " du contrat N°" . $parent->getData('ref');
        $bill_label .= ' - ' . $parent->getData('label');
        $instance->set('libelle', $bill_label);
        $instance->set('type', 0);
//        $instance->set('fk_account', 1);

        if (!$parent->getData('entrepot') && $parent->useEntrepot()) {
            return array('errors' => array("La facture ne peut pas être crée car le contrat n'a pas d'entrepôt"));
        }
        if ($parent->useEntrepot())
            $instance->set('entrepot', $parent->getData('entrepot'));
        $instance->set('fk_cond_reglement', ($parent->getData('condregl')) ? $parent->getData('condregl') : 2);
        $instance->set('fk_mode_reglement', ($parent->getData('moderegl')) ? $parent->getData('moderegl') : 2);
        $instance->set('datef', date('Y-m-d H:i:s'));
        $instance->set('ef_type', $ef_type);
        $instance->set('model_pdf', 'bimpfact');
        $instance->set('ref_client', $parent->getData('ref_customer'));
        $instance->set('expertise', $parent->getData('expertise'));

        $errors = $instance->create($warnings, true);
        $instance->copyContactsFromOrigin($parent);

        $lines = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contratLine');
        $desc = "<b><u>Services du contrat :</b></u>" . "<br /><br />";
        foreach ($lines->getList(['fk_contrat' => $parent->id, "renouvellement" => $parent->getData('current_renouvellement')]) as $idLine => $infos) {
            $desc .= $infos['description'] . "<br /><br />";
        }
        $facture_ok = false;
        if (!count($errors)) {

            $dateStart = new DateTime($data['date_start']);
            $dateEnd = new DateTime($data['date_end']);

            $add_desc = "";
            $parent->actionUpdateSyntec();
            // Vérification si le contrat est un renouvellement
            if ($parent->getData('current_renouvellement') > 0) {
                $current_syntec = $parent->getCurrentSyntecFromSyntecFr();
                // Vérification de si il y à un indice syntec à la signature
                if ($parent->getData('syntec') > 0) {

                    $add_desc .= "<br />";
                    $add_desc .= "<b><u>Calcul de l’indice Syntec</u></b><br />";
                    $add_desc .= "Revalorisation annuelle à la date d’effet du contrat<br />";
                    $add_desc .= "Valeur du dernier indice connu à ce jour: <b>$current_syntec </b><br />";
                    $add_desc .= "Valeur de l’indice d’origine: <b>" . $parent->getData('syntec') . "</b><br />";
                    // Prix révisé : (xxx,xx / xxx,xx) X Prix de base
                    $new_price = ($current_syntec / $parent->getData('syntec') * $parent->getTotalBeforeRenouvellement());
                    $surreter_syntec = ($current_syntec / $parent->getData('syntec'));
                    //$add_desc .= "Prix révisé: ($current_syntec / ".$parent->getData('syntec')." ) = $surreter_syntec x " . round($parent->getTotalBeforeRenouvellement(), 2) . " = <b>" . round($new_price, 2) . "€</b>";
                    //$add_desc .= "<Prix révisé = (Prix de base ".$parent->getTotalBeforeRenouvellement()."€ ) X ($current_syntec / ".$parent->getData('syntec')." = ".round($surreter_syntec,6)." ) = ".round($new_price,2)."€";
                    $add_desc .= "Prix révisé = (Prix de base " . $parent->getTotalBeforeRenouvellement() . "€ ) X ($current_syntec / " . $parent->getData('syntec') . ") = " . $parent->getTotalBeforeRenouvellement() . " X " . round($surreter_syntec, 6) . " = " . round($new_price, 2) . "€";
                }
            }

            $description_total = $desc . $add_desc;

            addElementElement("contrat", "facture", $parent->id, $instance->id);

            $new_first_line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine');
            $errors = BimpTools::merge_array($errors, $new_first_line->validateArray(array(
                                'type'   => ObjectLine::LINE_FREE,
                                'id_obj' => (int) $instance->id
            )));
            if (!isset($data['labelLn']))
                $new_first_line->desc = "Facturation pour la période du <b>" . $dateStart->format('d/m/Y') . "</b> au <b>" . $dateEnd->format('d/m/Y') . "</b>";
            else
                $new_first_line->desc = $data['labelLn'];
            $new_first_line->date_from = $data['date_start'];
            $new_first_line->date_to = $data['date_end'];
            $new_first_line->pu_ht = (double) $data['total_ht'];
            $new_first_line->tva_tx = 20;
            $new_first_line->pa_ht = $data['pa'];

            $errors = BimpTools::merge_array($errors, $new_first_line->create($warnings, true));
            $new_first_line->date_from = $data['date_start'];
            $new_first_line->update($warnings);

            if (!count($errors)) {

                $new_line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine');
                $errors = BimpTools::merge_array($errors, $new_line->validateArray(array(
                                    'type'   => ObjectLine::LINE_TEXT,
                                    'id_obj' => (int) $instance->id,
                )));

                $new_line->desc = $description_total;
                $errors = BimpTools::merge_array($errors, $new_line->create($warnings, true));

                $success = 'Facture créer avec succès';
                $facture_ok = true;

                $facture_send = count(getElementElement('contrat', 'facture', $parent->id));
                $total_facture_must = $parent->getData('duree_mois') / $parent->getData('periodicity');

                $this->switch_statut();

                if ($parent->reste_periode() == 0) {
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

        if (array_key_exists("origine", $data) && $facture_ok) {
//            return $instance->id;
        }

        return Array(
            'success'  => $success,
            'warnings' => $warnings,
            'errors'   => $errors,
        );
    }

    public function actionDelete($data, &$success)
    {
        $errors = $warnings = array();
        $parent = $this->getParentInstance();
        if (count(getElementElement('contrat', 'facture', $parent->id))) {
            $errors[] = "Vous ne pouvez pas supprimer cet échéancier car il contient une ou plusieurs factures";
        } else {
            if ($this->db->delete('bcontract_prelevement', 'id = ' . $this->id)) {
                $success = "Echéancier supprimé avec succès";
                $parent->addLog("Echéancier supprimé");
            } else {
                $errors[] = 'Une erreur est survenu lors de la suppression de l\'échéancier';
            }
        }
        return [
            'errors'   => $errors,
            'warnings' => $warnings
        ];
    }

    public function renderHeaderEcheancier()
    {
        $html .= '<table class="noborder objectlistTable" style="border: none; min-width: 480px">';
        $html .= '<thead>';
        $html .= '<tr class="headerRow">';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Période de facturation<br />Début - Fin</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Montant HT</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Montant TVA</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Montant TTC</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">PA</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Facture</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">&Eacute;tat du paiement</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Appartenance</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Action facture</th>';
        $html .= '</tr>';
        $html .= '</thead>';

        return $html;
    }

    public function displayEcheancier($data, $display, $header = true)
    {
        global $user;

        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts("Ce contrat ne comporte pas d'échéancier pour le moment", 'info', false);
        }

        $html = '';
        if (!$this->canEdit()) {
            $html = BimpRender::renderAlerts("Ce contrat est clos, aucune facture ne peut être emise", 'info', false);
        }

        $instance_facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture');
        $parent = $this->getParentInstance();
        $societe = BimpCache::getBimpObjectInstance('bimpcore', "Bimp_Societe", $parent->getData('fk_soc'));

        if ($header)
            $html .= $this->renderHeaderEcheancier();

        $html .= '<tbody class="listRows">';
        $callback = 'function(result) {if (typeof (result.file_url) !== \'undefined\' && result.file_url) {window.open(result.file_url)}}';
        $can_create_next_facture = $this->canEdit() ? true : false;

        switch ($this->getData('renouvellement')) {
            case 0:
                $displayAppatenance = "<strong>Information à venir</strong>";
                break;
            default:
                $displayAppatenance = "<strong>Information à venir</strong>";
                break;
        }

        if ($data->factures_send) {
            $current_number_facture = 1;
            $acomptes_ht = 0;
            $acomptes_ttc = 0;
            $avoir = [];

            foreach ($data->factures_send as $e) {
                $fact = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $e['d']);
                if ($fact->getData('type') != 3 && $fact->getData('type') != 2) {
                    $id_avoir = $this->db->getValue('facture', 'rowid', 'type = 2 AND fk_facture_source = ' . $fact->id);
                    if ($id_avoir) {
                        $avoir[$facture->dol_object->lines[0]->date_start] = ["FACTURE" => $fact->id, "AVOIR" => $id_avoir];
                    }
                }
            }

            foreach ($data->factures_send as $element_element) {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $element_element['d']);

                if ($facture->getData('type') != 3) {

                    // Définition de si c'est une facture de l'échéancier ou non
                    $has_facture_of_echeancier = true;
                    if (!$facture->dol_object->lines[0]->date_start && $has_facture_of_echeancier) {
                        $has_facture_of_echeancier = false;
                    }
                    //foreach ($facture->dol_object->lines as $line) {
                    //    if(!$line->date_start && $has_facture_of_echeancier) {
                    //        $has_facture_of_echeancier = false;
                    //    }
                    //}

                    if ($facture->getData('fk_statut') == 0) {
                        $can_create_next_facture = false;
                    }
                    $paye = ($facture->getData('paye') == 1) ? '<b class="success" >Payée</b>' : '<b class="danger" >Impayé</b>';
                    $html .= '<tr class="objectListItemRow" >';
                    if ($has_facture_of_echeancier) {
                        $dateDebut = New DateTime();
                        $dateFin = New DateTime();
                        $dateDebut->setTimestamp((int) $facture->dol_object->lines[0]->date_start);
                        $dateFin->setTimestamp((int) $facture->dol_object->lines[0]->date_end);
                    }

                    if ($this->getData('old_to_new'))
                        $html .= '<td style="text-align:center" ><b>Ancienne facturation</b></td>';
                    else {
                        if (!$has_facture_of_echeancier) {
                            $html .= '<td style="text-align:center" class="important">Facturation supplémentaire</td>';
                        } else {
                            $html .= '<td style="text-align:center" >Du <b>' . $dateDebut->format("d/m/Y") . '</b> au <b>' . $dateFin->format('d/m/Y') . '</b></td>';
                        }
                    }



                    $html .= '<td style="text-align:center"><b>' . price($facture->getData('total_ht')) . ' €</b> </td>'
                            . '<td style="text-align:center"><b>' . price($facture->getData('total_tva')) . ' € </b></td>'
                            . '<td style="text-align:center"><b>' . price($facture->getData('total_ttc')) . ' €</b> </td>'
                            . '<td style="text-align:center"><b>' . price($facture->getTotalMargeWithReval(array('correction_pa'))) . ' €</b></td>'
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
                    $acomptes_ht += $facture->getData('total_ht');
                    $acomptes_ttc += $facture->getData('total_ttc');
                }
            }
        }
        if ($this->getData('next_facture_date') < "2000-01-01" || (isset($dateFin) && $this->getData('next_facture_date') < $dateFin->add(new DateInterval('P1D'))->format('Y-m-d 00:00:00')))
            $this->updateField('next_facture_date', $dateFin->add(new DateInterval('P1D'))->format('Y-m-d 00:00:00'));
        if ($this->getData('next_facture_date') == 0 && (intval($parent->getTotalContrat()) - intval($parent->getTotalDejaPayer())) > 0) {
            $this->updateField('next_facture_date', $dateFin->add(new DateInterval('P1D'))->format('Y-m-d 00:00:00'));
            die('Echéancier corrigé, rafraichir la page');
        }
        if ($this->getData('next_facture_date') != 0) {
            $startedDate = new DateTime($this->getData('next_facture_date'));
            $enderDate = new DateTime($this->getData('next_facture_date'));
            $enderDate->add(new DateInterval("P" . $data->periodicity . "M"))->sub(new DateInterval("P1D"));
            $firstPassage = true;
            $firstDinamycLine = true;

            $reste_periodeEntier = ceil($data->reste_periode);

            for ($i = 1; $i <= $reste_periodeEntier; $i++) {

                $morceauPeriode = (($data->reste_periode - ($i - 1)) >= 1) ? 1 : (($data->reste_periode - ($i - 1)));
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

                $getEndDate = new DateTime($parent->displayRealEndDate("Y-m-d"));
                if ($getEndDate < $dateTime_end_mkTime)
                    $dateTime_end_mkTime = $getEndDate;
                $amount = 0; //$parent->getAddAmountAvenantProlongation() / ($data->reste_periode / $morceauPeriode);
                $firstPassage = false;
                if ($parent->getData('periodicity') > 0 && $parent->getData('periodicity') != 1200 && $data->reste_periode > 1) {
                    $amount += $data->reste_a_payer / ($data->reste_periode / $morceauPeriode);
                    $tva = $amount * 0.2;
                    $nb_periode = ceil($parent->getData('duree_mois') / $parent->getData('periodicity'));
                    $pa = ($parent->getTotalPa() - $parent->getTotalDejaPayer(false, 'pa')) / ($data->reste_periode * $morceauPeriode);
                } else {
                    $amount += $data->reste_a_payer;
                    $tva = $amount * 0.2;
                    $nb_periode = 1;
                    $pa = ($parent->getTotalPa() - $parent->getTotalDejaPayer(false, 'pa'));
                }

                if (!$display) {
                    $none_display = [
                        'total_ht'   => $amount,
                        'pa'         => $pa,
                        'date_start' => $dateTime_start_mkTime->format('Y-m-d'),
                        'date_end'   => $dateTime_end_mkTime->format('Y-m-d'),
                        'origine'    => 'cron'
                    ];
                    return $none_display;
                }

                $html .= '<tr class="objectListItemRow" >';
                $html .= '<td style="text-align:center" >Du <b>' . $dateTime_start_mkTime->format('d/m/Y') . '</b> au <b>' . $dateTime_end_mkTime->format('d/m/Y') . '</b>';

                // Faire ce qu'ilm y à faire pour les avoir à cet endroit

                $html .= '</td>';

                $infos = $this->getFacturesExterneByPeriode($dateTime_start_mkTime->format('Y-m-d') . '_' . $dateTime_end_mkTime->format('Y-m-d'));

                if (!count($infos)) {
                    $html .= '<td style="text-align:center">' . price($amount) . ' € </td>'
                            . '<td style="text-align:center">' . price($tva) . ' € </td>'
                            . '<td style="text-align:center">' . price($amount + $tva) . ' € </td>'
                            . '<td style="text-align:center">' . ($pa) . '€</td>'
                            . '<td style="text-align:center"><b style="color:grey">Période non facturée</b></td>'
                            . '<td style="text-align:center"><b class="important" >Période non facturée</b></td>'
                            . '<td style="text-align:center">' . $displayAppatenance . '</td>';
                } else {
                    $html .= '<b><td style="text-align:center">' . price($infos['ht']) . ' € </td>'
                            . '<td style="text-align:center">' . price($infos['tva']) . ' € </td>'
                            . '<td style="text-align:center">' . price($infos['ttc']) . ' € </td>'
                            . '<td style="text-align:center">N/C</td>'
                            . '<td style="text-align:center"><b style="color:grey">' . $infos['ref'] . '</b></td>'
                            . '<td style="text-align:center"><b class="danger" >Info inconnue</b></td>'
                            . '<td style="text-align:center">' . $displayAppatenance . '</td></b>';
                }


                $html .= '<td style="text-align:center; margin-right:10%">';
                if ($firstDinamycLine && $can_create_next_facture) {
                    // ICI NE PAS AFFICHER QUAND LA FACTURE EST PAS VALIDER
                    if ($user->rights->facture->creer && $this->canEdit()) {
                        $html .= '<span class="rowButton bs-popover" data-trigger="hover" data-placement="top"  data-content="Facturer la période" onclick="' . $this->getJsActionOnclick("createFacture", array('date_start' => $dateTime_start_mkTime->format('Y-m-d'), 'date_end' => $dateTime_end_mkTime->format('Y-m-d'), 'total_ht' => $amount, 'pa' => $pa), array("success_callback" => $callback)) . '")"><i class="fa fa-plus" ></i></span>';
                        $html .= '<span class="rowButton bs-popover" data-trigger="hover" data-placement="top"  data-content="Solder la periode par une facture externe" onclick="' . $this->getJsActionOnclick("solderPeriodeByFactureExterne", array('date_start' => $dateTime_start_mkTime->format('Y-m-d'), 'date_end' => $dateTime_end_mkTime->format('Y-m-d'), 'total_ht' => $amount, 'pa' => $pa), array("success_callback" => $callback, "form_name" => 'addFactureForSoldPeriode')) . '")"><i class="fa fa-link" ></i></span>';
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

            $html .= '<div class="panel-footer">';
            if (($parent->is_not_finish() && $user->rights->facture->creer) || ($user->admin || $user->id == 460)) {

                if (($user->admin) && $this->canEdit()) {
                    $html .= '<div class="btn-group"><button type="button" class="btn btn-danger bs-popover" ' . BimpRender::renderPopoverData('Supprimer l\'échéancier') . ' aria-haspopup="true" aria-expanded="false" onclick="' . $this->getJsActionOnclick('delete') . '"><i class="fa fa-times"></i></button></div>';
                }
//                if($user->admin || $user->rights->facture->creer) {
//                    $html .= '<div class="btn-group"><button type="button" class="btn btn-primary bs-popover" '.BimpRender::renderPopoverData('Refaire l\'échéancier suite à un avoir').' aria-haspopup="true" aria-expanded="false" onclick="' . $this->getJsActionOnclick('unlinkLastFacture', [], ['form_name' => 'unlinkLastFacture']) . '"><i class="fa fa-times"></i> Refaire l\'échéancier suite à un avoir</button></div>';
//                }
                // 
                $html .= '<div class="btn-group"><button type="button" class="btn bs-popover" ' . BimpRender::renderPopoverData('Générer le PDF') . ' aria-haspopup="true" aria-expanded="false" onclick="' . $this->getJsActionOnclick('generatePdfEcheancier', array(), array('form_name' => 'pdfEcheancier')) . '">Générer le PDF</button></div>';
                $html .= '<div class="btn-group"><button type="button" class="btn bs-popover" ' . BimpRender::renderPopoverData('(ADMIN - Infos périodes)') . ' aria-haspopup="true" aria-expanded="false" onclick="' . $this->getJsLoadModalCustomContent('displayNewEcheancier', 'Echeancier pour ce contrat') . '">New Echeancier</button></div>';

//                        $html .= '<span class="rowButton bs-popover" data-trigger="hover" data-placement="top"  data-content="Facturer la période" onclick="' . $this->getJsActionOnclick("createFacture", array('total_ht' => $parent->getTotalContrat() - $parent->getTotalDejaPayer(), 'pa' => ($parent->getTotalPa() - $parent->getTotalDejaPayer(false, 'pa'))), array("success_callback" => $callback)) . '")"><i class="fa fa-plus" >Facturation suplémentaire</i></span>';
//                if($this->canEdit()) {
                if ($user->admin) {
                    //$html .= '<div class="btn-group"><button type="button" class="btn btn-default" aria-haspopup="true" aria-expanded="false" onclick="' . $this->getJsLoadModalForm('create_perso', "Créer une facture personnalisée ou une facturation de plusieurs périodes") . '"><i class="fa fa-plus-square-o iconLeft"></i>Créer une facture personalisée ou une facturation de plusieurs périodes</button></div>';
                }
//                }
//                if($this->canEdit()) {
//                    $html .= '<div class="btn-group"><button type="button" class="btn btn-default" aria-haspopup="true" aria-expanded="false" onclick="' . $this->getJsLoadModalForm('create_perso', "Créer une facture personnalisée ou une facturation de plusieurs périodes") . '"><i class="fa fa-plus-square-o iconLeft"></i>Créer une facture personalisée ou une facturation de plusieurs périodes</button></div>';
//                }
            }


            if (($user->rights->facture->creer && $reste_periodeEntier == 0 && round($parent->getTotalContrat(), 2) - round($parent->getTotalDejaPayer(), 2) != 0) && $parent->getData('statut') == 11)
                $html .= '<div class="btn-group"><button type="button" class="btn btn-default bs-popover" ' . BimpRender::renderPopoverData('Facturation supplémentaire') . ' aria-haspopup="true" aria-expanded="false" onclick="' . $this->getJsActionOnclick("createFacture", array('labelLn' => 'Facturation supplémentaire', 'label' => 'Complément à', 'total_ht' => $parent->getTotalContrat() - $parent->getTotalDejaPayer(), 'pa' => ($parent->getTotalPa() - $parent->getTotalDejaPayer(false, 'pa'))), array("success_callback" => $callback)) . '"><i class="fa fa-plus"></i> Facturation supplémentaire</button></div>';
            $html .= '</div>';
        }

        $html .= "<br/>"
                . "<table style='float:right' class='border' border='1'>"
                . "<tr> <th style='border-right: 1px solid black; border-top: 1px solid white; border-left: 1px solid white; width: 20%'></th>  <th style='background-color:#ed7c1c;color:white;text-align:center'>Montant HT</th> <th style='background-color:#ed7c1c;color:white;text-align:center'>Montant TTC</th><th style='background-color:#ed7c1c;color:white;text-align:center'>Achat</th> </tr>"
                . "<tr> <th style='background-color:#ed7c1c;color:white;text-align:center'>Contrat</th> <td style='text-align:center'><b>" . price($parent->getTotalContrat()) . " €</b></td> <td style='text-align:center'><b> " . price($parent->getTotalContrat() * 1.20) . " €</b></td><td style='text-align:center'><b class='important'> " . ($parent->getTotalPa()) . " €</b></td></tr>"
                . "<tr > <th  style='background-color:#ed7c1c;color:white;text-align:center'>Facturé</th> <td style='text-align:center'><b class='important' > " . price($parent->getTotalDejaPayer()) . " € </b></td> <td style='text-align:center'><b class='important'> " . price($parent->getTotalDejaPayer() * 1.20) . " €</b></td> <td style='text-align:center'><b class='important'> " . price($parent->getTotalDejaPayer(false, 'pa')) . " €</b></td> </tr>";

        if ($parent->getTotalDejaPayer(true) == $parent->getTotalContrat()) {
            $html .= "<tr > <th  style='background-color:#ed7c1c;color:white;text-align:center'>Payé</th> <td style='text-align:center'><b class='success'> " . price($parent->getTotalDejaPayer(true)) . " € </b></td> <td style='text-align:center'><b class='success'> " . price($parent->getTotalDejaPayer(true) * 1.20) . " €</b></td><td style='text-align:center'><b class='important'> " . price($parent->getTotalDejaPayer(true, 'pa')) . " €</b></td> </tr>";
        } else {
            $html .= "<tr > <th  style='background-color:#ed7c1c;color:white;text-align:center'>Payé</th> <td style='text-align:center'><b class='danger'> " . price($parent->getTotalDejaPayer(true)) . " € </b></td> <td style='text-align:center'><b class='danger'> " . price($parent->getTotalDejaPayer(true) * 1.20) . " €</b></td><td style='text-align:center'><b class='important'> " . price($parent->getTotalDejaPayer(true, 'pa')) . " €</b></td> </tr>";
        }



        if ($parent->getTotalContrat() - $parent->getTotalDejaPayer() == 0) {
            $html .= "<tr > <th  style='background-color:#ed7c1c;color:white;text-align:center'>Reste à facturer</th> <td style='text-align:center'><b class='success'> " . price($parent->getTotalContrat() - $parent->getTotalDejaPayer()) . " € </b></td> <td style='text-align:center'><b class='success'> " . price(($parent->getTotalContrat() - $parent->getTotalDejaPayer()) * 1.20) . " €</b></td><td style='text-align:center'><b class='important'> " . ($parent->getTotalPa() - $parent->getTotalDejaPayer(false, 'pa')) . " €</b></td> </tr>";
        } else {
            $html .= "<tr > <th  style='background-color:#ed7c1c;color:white;text-align:center'>Reste à facturer</th> <td style='text-align:center'><b class='danger'> " . price($parent->getTotalContrat() - $parent->getTotalDejaPayer()) . " € </b></td> <td style='text-align:center'><b class='danger'> " . price(($parent->getTotalContrat() - $parent->getTotalDejaPayer()) * 1.20) . " €</b></td><td style='text-align:center'><b class='important'> " . ($parent->getTotalPa() - $parent->getTotalDejaPayer(false, 'pa')) . " €</b></td>  </tr>";
        }

        if ($parent->getTotalContrat() - $parent->getTotalDejaPayer(true) == 0) {
            $html .= "<tr> <th style='background-color:#ed7c1c;color:white;text-align:center'>Reste à payer</th> <td style='text-align:center'><b class='success'> 0 € </b></td> <td style='text-align:center'><b class='success'>0 €</b></td> </tr>";
        } else {
            $html .= "<tr> <th style='background-color:#ed7c1c;color:white;text-align:center'>Reste à payer</th> <td style='text-align:center'><b class='danger'> " . price($parent->getTotalContrat() - $parent->getTotalDejaPayer(true)) . " € </b></td> <td style='text-align:center'><b class='danger'> " . price(($parent->getTotalContrat(true) * 1.20) - ($parent->getTotalDejaPayer(true) * 1.20)) . " €</b></td><td style='text-align:center'><b class='important'> " . ($parent->getTotalPa() - $parent->getTotalDejaPayer(true, 'pa')) . " €</b></td></tr>";
        }
        if ($acomptes_ht != 0 && $acomptes_ttc != 0) {

            $html .= "<tr> <th style='background-color:#ed7c1c;color:white;text-align:center'>Dont acompte</th> <td style='text-align:center'><b class='success'> " . price($acomptes_ht) . "€ </b></td><td style='text-align:center'><b class='success'>" . price($acomptes_ttc) . "€</b></td></tr>";
        }
        $html .= "<tr></tr><tr></tr>";
        $html .= "<tr> <th style='background-color:#ed7c1c;color:white;text-align:center'>Coût prévisionelle</th> <td style='text-align:center'><b class='success'> " . price($acomptes_ht) . "€ </b></td><td style='text-align:center'><b class='success'>" . price($acomptes_ttc) . "€</b></td></tr>";

        $html .= "</table>";

        if ($display)
            return $html;
    }

    public function displayNewEcheancier()
    {

        $html = '';

        $allPeriodes = $this->getAllPeriodes(true);

        $html .= '<table class="objectlistTable" style="border: none; min-width: 400px" width="100%">';
        $html .= '<thead>';
        $html .= '<tr class="headerRow">';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Période de facturation<br />Début - Fin</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Montant HT</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Montant TVA</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Montant TTC</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">PA</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Facture</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">&Eacute;tat du paiement</th>';
        $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Action facture</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody class="listRows">';

        foreach ($allPeriodes['periodes'] as $periode) {

            $dateDebutDeLaPeriode = new DateTime(str_replace('/', '-', $periode['START']));
            $dateFinDeLaPeriode = new DateTime(str_replace('/', '-', $periode['STOP']));
            $nextFactureDate = new DateTime($this->getData('next_facture_date'));

            $html .= '<tr class=\'bs-popover\' ' . BimpRender::renderPopoverData($periode['DUREE_MOIS'] . ' mois', 'left') . ' >';

            $displayPeriode = '<span>Du <strong>' . $periode['START'] . '</strong> au <strong>' . $periode['STOP'] . '</strong></span>';
            $displayMontantHT = price($periode['PRICE']) . '€';
            $displayMontantTVA = price($periode['TVA']) . '€';
            $displayMontantTTC = price($periode['PRICE'] + $periode['TVA']) . '€';
            $displayMontantPA = price(0) . '€';

            $forDisplayReferenceFacture = 'Periode non facturée';
            $displayEtatPaiment = 'Periode non facturée';
            $classForEtatPaiement = 'important';

            if ($periode['FACTURE'] != '') {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture');
                $facture->find(Array('ref' => $periode['FACTURE']), 1);
                if ($facture->isLoaded()) {
                    $forDisplayReferenceFacture = $facture->getNomUrl();
                    if ($facture->getData('paye')) {
                        $displayEtatPaiment = 'PAY&Eacute;E';
                        $classForEtatPaiement = 'success';
                    } else {
                        $displayEtatPaiment = 'INPAY&Eacute;E';
                        $classForEtatPaiement = 'danger';
                    }
                } else {
                    $displayEtatPaiment = 'Impossible de charger l\'état de paiement pour la facture: ' . $periode['FACTURE'];
                    $classForEtatPaiement = 'warning';
                    $forDisplayReferenceFacture = 'Impossible de charger la facture: ' . $periode['FACTURE'];
                }
            }

            $displayReferenceFacture = '<span style=\'color:grey; font-weight:bold\'>' . $forDisplayReferenceFacture . '</span>';

            $isLaPeriodeDeFacturation = (($dateDebutDeLaPeriode->format('Y-m-d') == $nextFactureDate->format('Y-m-d')) && $periode['FACTURE'] == '') ? true : false;

            $html .= '<td style=\'text-align:center; ' . (($isLaPeriodeDeFacturation) ? 'background-color:lightgrey !important' : '') . '\'>' . $displayPeriode . '</td>';
            $html .= '<td style=\'text-align:center; ' . (($isLaPeriodeDeFacturation) ? 'background-color:lightgrey !important' : '') . '\'>' . $displayMontantHT . '</td>';
            $html .= '<td style=\'text-align:center; ' . (($isLaPeriodeDeFacturation) ? 'background-color:lightgrey !important' : '') . '\'>' . $displayMontantTVA . '</td>';
            $html .= '<td style=\'text-align:center; ' . (($isLaPeriodeDeFacturation) ? 'background-color:lightgrey !important' : '') . '\'>' . $displayMontantTTC . '</td>';
            $html .= '<td style=\'text-align:center; ' . (($isLaPeriodeDeFacturation) ? 'background-color:lightgrey !important' : '') . '\'>' . $displayMontantPA . '</td>';
            $html .= '<td style=\'text-align:center; ' . (($isLaPeriodeDeFacturation) ? 'background-color:lightgrey !important' : '') . '\'>' . $displayReferenceFacture . '</td>';
            $html .= '<td style=\'text-align:center; ' . (($isLaPeriodeDeFacturation) ? 'background-color:lightgrey !important' : '') . '\'><span class=\'' . $classForEtatPaiement . '\' >' . $displayEtatPaiment . '</span></td>';

            if ($isLaPeriodeDeFacturation) {
                $html .= '<td style=\'text-align:center; ' . (($isLaPeriodeDeFacturation) ? 'background-color:lightgrey !important' : '') . '\'>'
                        . '<span class="rowButton bs-popover">' . BimpRender::renderIcon('plus') . '</span>' . '</td>';
            } else {
                $html .= '<td style=\'text-align:center; ' . (($isLaPeriodeDeFacturation) ? 'background-color:lightgrey !important' : '') . '\'></td>';
            }

            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        $html .= '<pre>' . print_r($allPeriodes, 1) . '<pre>';

        return $html;
    }

    public function actionGeneratePdfEcheancier($data, &$success)
    {
        global $langs;
        $parent = $this->getParentInstance();
        $this->dol_object->id_contrat = $this->getData('id_contrat');
        $this->dol_object->afficher_total = $data['total_lines'];
        //$this->afficherContact = (int) $data['afficherContact']; 
        $url = DOL_URL_ROOT . '/document.php?modulepart=' . 'contrat' . '&file=' . $parent->getRef() . '/Echeancier.pdf';
        $errors = $warnings = array();
        $success = "PDF de l'échéancier généré avec succès";
        $success_callback = 'window.open("' . $url . '")';
        $parent->dol_object->generateDocument('echeancierContrat', $langs);
        return array('errors' => $errors, 'warnings' => $warnings, 'success_callback' => $success_callback);
    }

    public function getDureeMoisPeriode($dateStart, $dateStop): int
    {
        $mois = 0;

        $start = new DateTime($dateStart);
        $stop = new DateTime($dateStop);
//        $start->sub(new dateInterval('P1D'));
//        $stop->add(new dateInterval('P1D'));
        $interval = $start->diff($stop);
        $mois = $interval->m;
        $mois += $interval->y * 12;
        if ($interval->d > 26)
            $mois++;



//        echo '<br/><br/>'.$start->format('Y-m-d').' '.$stop->format('Y-m-d');
//        echo '<pre>';
//        print_r($interval);
//        die('<pre>' . $mois);
//        die($dateStart . ' - ' . $dateStop);
        return $mois;
    }

    public function getAmountByMonth()
    {
        $parentInstance = $this->getParentInstance();
        if ($parentInstance->reste_periode() > 0) {
            $reste_a_payer = $parentInstance->reste_a_payer() / $parentInstance->reste_periode() / $parentInstance->getData('periodicity');

            return $reste_a_payer;
        }
        return 0;
    }

    public function addedMonthByAvenant()
    {

        $sql = 'SELECT SUM(added_month) as sum FROM llx_bcontract_avenant WHERE ';
        $sql .= 'id_contrat = ' . $this->getData('id_contrat') . ' ';
        $sql .= 'AND statut IN(2,5) AND type = 1';

        return $this->db->executeS($sql)[0]->sum;
    }

    private $endDateTimeFactured = 0;

    public function getAllFactures()
    {

        $parent = $this->getParentInstance();
        $facturesElementElement = getElementElement('contrat', 'facture', $parent->id);
        $factures = Array();

        if (count($facturesElementElement) > 0) {

            foreach ($facturesElementElement as $index => $data) {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $data['d']);
                $laLigne = $facture->dol_object->lines[0];
                $factures[] = Array(
                    'ref'       => $facture->getRef(),
                    'ht'        => $facture->getData('total_ht'),
                    'tva'       => $facture->getData('total_tva'),
                    'ttc'       => $facture->getData('total_ttc'),
                    'dateStart' => $laLigne->date_start,
                    'dateEnd'   => $laLigne->date_end
                );

                if ($laLigne->date_end > $this->endDateTimeFactured)
                    $this->endDateTimeFactured = $laLigne->date_end;
            }
        }

        return $factures;
    }

    public function periodeIsFactured($debut, $fin, $factures): array
    {

        if (count($factures) > 0) {

            $dateStartFacturation = new DateTime();
            $dateStopFacturation = new DateTime();

            foreach ($factures as $index => $data) {

                $dateStartFacturation->setTimestamp($data['dateStart']);
                $dateStopFacturation->setTimestamp($data['dateEnd']);

                if ($dateStartFacturation->format('d/m/Y') == $debut && $dateStopFacturation->format('d/m/Y') == $fin) {
                    return Array(
                        'dateStart' => $dateStartFacturation->format('Y-m-d'),
                        'dateEnd'   => $dateStopFacturation->format('Y-m-d'),
                        'index'     => $index
                    );
                }
            }
        }

        return Array();
    }

    public function getTotalFactured($factures): float
    {
        $return = 0.0;

        if (count($factures) > 0) {
            foreach ($factures as $index => $data) {

                $return += $data['ht'];
            }
        }

        return $return;
    }

    public function getAllPeriodes($display = false): array
    {

        $periodes = Array();

        $parentInstance = $this->getParentInstance();
        $dateStartEcheancier = new DateTime($parentInstance->getData('date_start'));
        if ($parentInstance->getData('date_end_renouvellement')) {
            $dateStopEcheancier = new DateTime($parentInstance->getData('date_end_renouvellement'));
        } else {
            if ($parentInstance->getData('end_date_contrat')) {
                $dateStopEcheancier = new DateTime($parentInstance->getData('end_date_contrat'));
            } else {
                $dateStopEcheancier = new DateTime($parentInstance->getData('date_start'));
                $dateStopEcheancier->add(new DateInterval('P' . $parentInstance->getData('duree_mois') . 'M'));
            }
            $dateStopEcheancier = $dateStopEcheancier->sub(new DateInterval('P1D'));
        }

        //die('date de fin du contrat ' . $dateStopEcheancier->format('Y-m-d'));

        $diff = $dateStartEcheancier->diff($dateStopEcheancier);

//        $dureeEnMois            = $parentInstance->getData('duree_mois');
//        $dureeEnMois            = $diff->m + ($diff->y * 12) + 1;

        $dureeEnMois = $this->getDureeMoisPeriode($dateStartEcheancier->format('Y-m-d'), $dateStopEcheancier->format('Y-m-d'));

        //die($dureeEnMois . ' kcodsp');
        $periodicity = $parentInstance->getData('periodicity');

        if ($periodicity == 1200) {
            $periodicity = $dureeEnMois;
        }
        $nombrePeriodesAdded = $this->addedMonthByAvenant();
        $dureePeriodeIncomplette = $dureeEnMois % $periodicity;
        $dureePeriodesComplettes = $dureeEnMois - $dureePeriodeIncomplette;
        $nombrePeriodesComplettes = $dureePeriodesComplettes / $periodicity;

        $periodes['infos'] = Array(
            'nombre_periodes'          => $nombrePeriodesComplettes,
            'nombre_periodes_added'    => $nombrePeriodesAdded,
            'periode_incomplette_mois' => $dureePeriodeIncomplette,
            'tarif_au_mois'            => $this->getAmountByMonth(),
            'factures'                 => $this->getAllFactures()
        );

        $i = 1;
        $haveOtherPeriodes = false;
        $alternateStartDate = $dateStartEcheancier;

        $resteAPayer = $parentInstance->reste_a_payer();

        if ($nombrePeriodesComplettes > 0) {
            while ($i <= $nombrePeriodesComplettes) {
                if (!isset($periodes['periodes']) || !count($periodes['periodes'])) {
                    $haveOtherPeriodes = true;
                    $startDate = $dateStartEcheancier->format('d/m/Y');
                    $startDateForPeriode = $dateStartEcheancier->format('Y-m-d');
                    $stopDate = $dateStartEcheancier;
                } else {
                    $startDate = $stopDate->add(new DateInterval('P1D'))->format('d/m/Y');
                    $startDateForPeriode = $stopDate->format('Y-m-d');
                }

                $stopDate = $alternateStartDate->add(new DateInterval('P' . $periodicity . 'M'));
                $stopDate->sub(new DateInterval('P1D'));

                $factured = $this->periodeIsFactured($startDate, $stopDate->format('d/m/Y'), $periodes['infos']['factures']);
                //print_r($factured);
                if (!count($factured)) {
                    $price = $this->getDureeMoisPeriode($startDateForPeriode, $stopDate->format('Y-m-d')) * $periodes['infos']['tarif_au_mois'];
                    $startDateStr = $startDate;
                    $stopDateStr = $stopDate->format('d/m/Y');
                    $factureStr = '';
                } else {
                    $startDate = new DateTime($factured['dateStart']);
                    $startDateStr = $startDate->format('d/m/Y');
                    $stopDate = new DateTime($factured['dateEnd']);
                    $stopDateStr = $stopDate->format('d/m/Y');

                    $resteAPayer = $periodes['infos']['factures'][$factured['index']]['ht'];
                    $factureStr = $periodes['infos']['factures'][$factured['index']]['ref'];
                    $price = $periodes['infos']['factures'][$factured['index']]['ht'];

                    $stopDate = new DateTime($factured['dateEnd']);
                    //$stopDate->add(new DateInterval('P1D'));
                }


                $periodes['periodes'][] = Array(
                    'START'            => $startDateStr,
                    'STOP'             => $stopDateStr,
                    'DATE_FACTURATION' => ($parentInstance->getData('facturation_echu')) ? $stopDateStr : $startDateStr,
                    'HT'               => $resteAPayer,
                    'DUREE_MOIS'       => $this->getDureeMoisPeriode($startDateForPeriode, $stopDate->format('Y-m-d')),
                    'PRICE'            => $price,
                    'TVA'              => $price * 0.2,
                    'FACTURE'          => $factureStr
                );

                $tmpFacture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture');
                if ($tmpFacture->find(Array('ref' => $factureStr), 1)) {
                    if ($tmpFacture->getData('fk_facture_source')) {
                        $factureSource = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $tmpFacture->getData('fk_facture_source'));
                        $periodes['periodes'][] = Array(
                            'START'            => $startDateStr,
                            'STOP'             => $stopDateStr,
                            'DATE_FACTURATION' => ($parentInstance->getData('facturation_echu')) ? $stopDateStr : $startDateStr . ' (' . $factureStr . ')',
                            'HT'               => $resteAPayer,
                            'DUREE_MOIS'       => $this->getDureeMoisPeriode($startDateForPeriode, $stopDate->format('Y-m-d')),
                            'PRICE'            => $factureSource->getData('total_ht'),
                            'TVA'              => $factureSource->getData('total_ht') * 0.2,
                            'FACTURE'          => $factureSource->getRef()
                        );
                    }
                }

                $alternateStartDate = $stopDate;

                $i++;
            }
        }

        if ($dureePeriodeIncomplette > 0) {

            if (!$haveOtherPeriodes) {
                $resteStartDT = $alternateStartDate;
                $resteStart = $alternateStartDate->format('d/m/Y');
                $resteStopDT = $alternateStartDate->add(new DateInterval('P' . $dureePeriodeIncomplette . 'M'))->sub(new DateInterval('P1D'));
                $resteStop = $resteStopDT->format('d/m/Y');
            } else {
                $resteStartDT = $stopDate->add(new DateInterval('P1D'));
                $resteStart = $resteStartDT->format('d/m/Y');
                $resteStopDT = $resteStartDT->add(new DateInterval('P' . $dureePeriodeIncomplette . 'M'));
                $resteStopDT->sub(new DateInterval('P1D'));
                $resteStop = $resteStopDT->format('d/m/Y');
            }

            $price = $dureePeriodeIncomplette * $periodes['infos']['tarif_au_mois'];
            $periodes['periodes'][] = Array(
                'START'            => $resteStart,
                'STOP'             => $resteStop,
                'DATE_FACTURATION' => ($parentInstance->getData('facturation_echu')) ? $resteStop : $resteStart,
                'HT'               => $resteAPayer,
                'DUREE_MOIS'       => $dureePeriodeIncomplette,
                'PRICE'            => $price,
                'TVA'              => $price * 0.2
            );
        }

        //Vérif
        $tot = 0;
        foreach ($periodes['periodes'] as $periode) {
            $tot += $periode['PRICE'];
        }

        if (!$display) {
            if (price($tot) != price($parentInstance->getTotalContrat())) {
                die('PRobléme Technique contrat ' . $parentInstance->id . ' pdf exheancier totP ' . $tot . ' totCt ' . $parentInstance->getTotalContrat());
                BimpCore::addlog('PRobléme Technique contrat ' . $parentInstance->id . ' pdf exheancier totP ' . $tot . ' totCt ' . $parentInstance->getTotalContrat());
                die('PRobléme Technique');
            }
        }

        return $periodes;
    }

    public function actionDeleteFacture($data, &$success)
    {

        global $user;
        $parent = $this->getParentInstance();
        $errors = $warnings = array();
        $instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $data['id_facture']);

        $dateDebutFacture = $instance->dol_object->lines[0]->date_start;

        if ($instance->dol_object->delete($user) > 0) {
            $this->onDeleteFacture($dateDebutFacture);

            $success = "Facture " . $instance->getData('ref') . ' supprimée avec succès';
        } else {
            $errors[] = "Facture " . $instance->getData('ref') . ' n\'à pas été supprimée';
            ;
        }

        return Array(
            'success'  => $success,
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function onDeleteFacture($dateDebutFacture)
    {
        if ($dateDebutFacture) {
            $new_next_date = new DateTime();
            $new_next_date->setTimestamp($dateDebutFacture);
            $this->updateField('next_facture_date', $new_next_date->format('Y-m-d 00:00:00'));
            $this->switch_statut();
        }
    }

    public function parentGetData($field)
    {
        $parent = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', $this->getData('id_contrat'));
        $returned_info = $parent->getData($field);
    }

    public function displayParentInfos($field, $module = null, $bimp_object = null)
    {

        $parent = $this->getParentInstance();

        $returned_info = $parent->getData($field);

        if (!is_null($module) && !is_null($bimp_object)) {
            $instance = BimpCache::getBimpObjectInstance($module, $bimp_object, $returned_info);
            return $instance->getNomUrl();
        }

        return $returned_info;
    }

    public function getListFilter()
    {
        
    }

    public function getListFilterRetard()
    {
        
    }

    public function getListExtraButtons()
    {
        global $user;
        $buttons = [];

        $parent = $this->getParentInstance();

        if (($user->rights->bimpcontract->stop_bills_timeline || $user->admin) && $this->getData('statut') == self::STATUT_EN_COURS) {
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

    public function switch_statut()
    {
        $parent = $this->getParentInstance();
        $new = self::STATUT_EN_COURS;
        if ($this->isTotalyFactured() || $parent->getData('statut') == 2) {
            $new = self::STATUT_IS_FINISH;
        }
        $this->updateField('statut', $new);

        return $new;
    }

    public function isTotalyFactured()
    {
        $parent = $this->getParentInstance();
        $nombre_total_facture = $parent->getData('duree_mois') / $parent->getData('periodicity');
        $nombre_fature_send = count(getElementElement('contrat', 'facture', $this->getData('id_contrat')));

        if ($nombre_fature_send == $nombre_total_facture)
            return 1;

        return 0;
    }

    public function displayFactureEmises()
    {

        $class = 'danger';
        $class_periode = 'danger';
        $parent = $this->getParentInstance();

        if ($parent->getData('duree_mois') > 0) {
            if ($parent->isLoaded()) {
                if ($parent->getData('periodicity') > 0) {
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

    public function displayNextFactureDate()
    {

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

    public function displayRetard()
    {
        $parent = $this->getParentInstance();
        $alert = "<b class='success bs-popover' " . BimpRender::renderPopoverData('Facturation à jour') . ">" . BimpRender::renderIcon('check') . "</b>";
        $next = $this->getData('next_facture_date');
        $dateTime = new DateTime($next);
        $toDay = new DateTime();
        if ($this->isEnRetard()) {
            $popover = BimpRender::renderPopoverData('Retard de facturation', 'top');
            $alert = '<b class="danger bs-popover" ' . $popover . ' >' . BimpRender::renderIcon('warning') . '</b>';
        }

        if ($parent->getData('duree_mois') > 0)
            return $alert;
        else
            return '';
    }
}
