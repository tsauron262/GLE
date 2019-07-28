<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpInput.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcontract/objects/BContract_contrat.class.php';

class BContract_echeancier extends BimpObject {
        
    public function canViewObject($object) {
        if (is_object($object))
            return true;
        return false;
    }
    
    public function canEdit() {
        return false; // ToDo à faire les conditions
    }
    
    public function renderMontantInput() {
        
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
        
        $instance = $this->getInstance('bimpcommercial', 'Bimp_Facture');
        $instance->set('fk_soc', $parent->getInitData('fk_soc'));
        $instance->set('libelle', 'Facture périodique du contrat N°' . $parent->getData('ref'));
        $instance->set('type', 0);
        $instance->set('fk_account', 1);
        $instance->set('entrepot', 50);
        $instance->set('fk_cond_reglement', ($client->getData('cond_reglement')) ? $client->getData('cond_reglement') : 2);
        $instance->set('fk_mode_reglement', ($client->getData('mode_reglement')) ? $client->getData('mode_reglement') : 2);
        $instance->set('datef', $data['date_start']);
        $instance->set('ef_type', 'C');
        $errors = $instance->create($warnings = Array(), true);
        if(!count($errors)) {
              
            $dateStart = new DateTime($data['date_start']);
            $dateEnd = new DateTime($data['date_end']);
            if($instance->dol_object->addline("Facturation pour la période du <b>".$dateStart->format('d / m / Y')."</b> au <b>".$dateEnd->format('d / m / Y')."</b>", (double) $data['total_ht'], 1, 20, 0, 0, 0, 0, $data['date_start'], $data['date_end'], 0, 0, '', 'HT', 0, 1) > 0) {
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
        $instance_facture = $this->getInstance('bimpcommercial', 'Bimp_Facture');
        $parent = $this->getParentInstance();
        // VOIR POURQUOI PREMIERE UTILISATION 
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
        if($data->factures_send) {
            foreach($data->factures_send as $element_element) {
                $facture = $this->getInstance('bimpcommercial', 'Bimp_Facture', $element_element['d']);
                
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
                
                if($facture->getData('fk_statut') == 0) {
                    $html .= '<span class="rowButton bs-popover" data-trigger="hover" data-placement="top"  data-content="Valider la facture" onclick="' . $this->getJsActionOnclick("validateFacture", array('id_facture' => $facture->id), array("success_callback" => $callback)) . '")"><i class="fa fa-check" ></i></span>';
                } else {
                    $html .= '<span class="rowButton bs-popover" data-toggle="popover" data-trigger="hover" data-container="body" data-placement="top" data-content="Afficher la page dans un nouvel onglet" data-html="false" onclick="window.open(\'/pour_contrat/htdocs/bimpcommercial/index.php?fc=facture&amp;id='.$facture->id.'\');" data-original-title="" title=""><i class="fas fa5-external-link-alt"></i></span>';
                }
                
                $html .= '</td>';
                $html .= '</tr>';
                
                
            }
        }
        $startedDate = new DateTime($this->getData('next_facture_date'));
        $enderDate = new DateTime($this->getData('next_facture_date'));
        $enderDate->add(new DateInterval("P" . $data->periodicity . "M"))->sub(new DateInterval("P1D"));
        $firstPassage = true;
        $firstDinamycLine = true;
        
        for($i = 1; $i <= $data->reste_periode; $i++) {
            if(!$firstPassage){
                $startedDate->add(new DateInterval("P" . $data->periodicity . "M"));
            }
            $firstPassage = false;
            $amount  = $data->reste_a_payer / $data->reste_periode;
            $tva = $amount * 0.2;
            $html .= '<tr class="objectListItemRow" >';
            $html .= '<td style="text-align:center" >Du <b>'.$startedDate->format('d / m / Y').'</b> au <b>'.$enderDate->format('d / m / Y').'</b></td>';
                $html .=  '<td style="text-align:center">' . price($amount) . ' € </td>'
                        . '<td style="text-align:center">' . price($tva) . ' € </td>'
                    . '<td style="text-align:center">' . price($amount + $tva) . ' € </td>'
                    . '<td style="text-align:center"><b style="color:grey">Période non facturée</b></td>'
                    . '<td style="text-align:center"><b class="important" >Période non facturée</b></td>'
                    . '<td style="text-align:center; margin-right:10%">';
                if($firstDinamycLine){
                    $html .= '<span class="rowButton bs-popover" data-trigger="hover" data-placement="top"  data-content="Facturer la période" onclick="' . $this->getJsActionOnclick("createFacture", array('date_start' => $startedDate->format('Y-m-d'), 'date_end' => $enderDate->format('Y-m-d'), 'total_ht' => $amount, 'id_client' => $parent->getData('fk_soc')), array("success_callback" => $callback)) . '")"><i class="fa fa-plus" ></i></span>';
                    $firstDinamycLine = false;
                }
            $html .= '</tr>';
            $enderDate = $enderDate->add(new DateInterval("P" . $data->periodicity . "M"));
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '<div class="panel-footer"><div class="btn-group"><button type="button" class="btn btn-default" aria-haspopup="true" aria-expanded="false" onclick="'. $this->getJsLoadModalForm('create_perso', "Créer une facture personalisée") .'"><i class="fa fa-plus-square-o iconLeft"></i>Créer une facture personalisée</button></div></div>';
        return $html;
        
    }

    

    public function display_facture_perso() {
        global $db;
        $parent = $this->getParentInstance();
        $bimp = new BimpDb($db);

        $callback = 'function(result) {if (typeof (result.file_url) !== \'undefined\' && result.file_url) {window.open(result.file_url)}}';

        $html .= '<br /><form action="" method="post">';
        $html .= '<div style="  display: inline-flex" >';
        $html .= BimpInput::renderDatePickerInput('date_debut_select', 0, 0, 0, 'date') . BimpInput::renderDatePickerInput('date_fin_select', 0, 0, 0, 'date');
        $html .= '</div><br>';
        $html .= '<input class="btn btn-primary saveButton" type="submit" name="submit" value="Créee facture personnalisé" /></form>';

        if (isset($_POST['submit'])) {
            //$select_debut = $_POST['date_debut_select'];
            //$select_fin = $_POST['date_fin_select'];
//            echo 'datedebut ' . $select_debut;
//            echo 'datefin ' . $select_fin;
            $select_debut = BimpTools::getPostFieldValue('date_debut_select');
            $select_fin = BimpTools::getPostFieldValue('date_fin_select');
            
            //$value = $this->match_key_with_dates($this->calcul_new_date($select_debut), $this->calcul_new_date($select_fin));
            $nb_period = $parent->getData('duree_mois') / $parent->getData('periodicity');
            $tmp = $nb_period - $value;
            $calc_period = $nb_period - $tmp;
            //echo 'test' . $calc_period;
            
            //$this->actionCreate_facture_perso($converted_date_debut, $converted_date_fin);
     
            //UPDATE NEXT DATE
            //$nextdate = new DateTime("$converted_date_debut");
            //$nextdate->getTimestamp();
            $nextdate->add(new DateInterval("P" . $calc_period . "M"));
            $newdate = $nextdate->format('Y-m-d');
            $udpadeArray = Array('next_facture_date' => $newdate);
            $bimp->update('bcontract_prelevement', $udpadeArray, 'id_contrat = ' . $parent->id);
        }
        return $html;
    }

    public function display_info() {
        $tab_total_fact = $this->get_total_facture();
        foreach ($tab_total_fact['info'] as $line) {
            //echo 'info = ' . $line;
        }
        $html .= "<br/>"
                . "<table style='width:50%;float:right' class='border' border='1'>"
                . "<tr> <th style='border: 1px solid Transparent!important;'></th>  <th style='background-color:#ed7c1c;color:white;text-align:center'>Montant HT</th> <th style='background-color:#ed7c1c;color:white;text-align:center'>Montant TTC</th> </tr>"
                . "<tr> <th style='background-color:#ed7c1c;color:white;text-align:center'>Total contrat</th> <td style='text-align:center'><b>" . price($this->get_total_contrat('ht')) . " €</b></td> <td style='text-align:center'><b> " . price($this->get_total_contrat('ttc')) . " €</b></td> </tr>"
                . "<tr > <th  style='background-color:#ed7c1c;color:white;text-align:center'>Déjà payer</th> <td style='text-align:center'><b>" . price($tab_total_fact['info']['deja_payer_ht']) . " € </b></td> <td style='text-align:center'><b> " . price($tab_total_fact['info']['deja_payer_ttc']) . " €</b></td> </tr>"
                . "<tr> <th style='background-color:#ed7c1c;color:white;text-align:center'>Reste à payer</th> <td style='text-align:center'><b> " . price($tab_total_fact['info']['reste_a_payer_ht']) . " € </b></td> <td style='text-align:center'><b> " . price($tab_total_fact['info']['reste_a_payer_ttc']) . " €</b></td> </tr>"
                . "</table>";

        return $html;
    }
    
    public function actionDelete_facture($data, &$success) {
        global $user, $db;
        $bimp = new BimpDb($db);

        BimpTools::loadDolClass('compta/facture', 'facture');
        $facture = new Facture($db);
        $facture->fetch($data['id_facture']);
        $success = '';

        $facture->delete($user, 0);

        $success = 'Facture ' . $data['id_facture'] . ' supprimer avec succès';
    }

    public function actionCreate_facture($data, &$success) {
        global $user, $db;
        $bimp = new BimpDb($db);
        $success = '';

        BimpTools::loadDolClass('compta/facture', 'facture');
        $facture = new Facture($db);

        $parent = $this->getParentInstance();
        $date_debut = $parent->getData('date_start');
        $period = $this->getData('periodicity');

        $facture->date = $this->getData('next_facture_date');


        $facture->cond_reglement_id = 2;
        $facture->cond_reglement_code = 'RECEP';
        $now = dol_now();
        $arraynow = dol_getdate($now);
        $nownotime = dol_mktime(0, 0, 0, $arraynow['mon'], $arraynow['mday'], $arraynow['year']);
        $facture->date_lim_reglement = $nownotime + 3600 * 24 * 30;
        $facture->date_lim_reglement = $facture->calculate_date_lim_reglement();
        $facture->mode_reglement_id = 0;  // Not forced to show payment mode CHQ + VIR
        $facture->mode_reglement_code = ''; // Not forced to show payment mode CHQ + VIR
        $facture->socid = $parent->getData('fk_soc');
        $facture->array_options['options_type'] = "C";
        $facture->array_options['options_entrepot'] = 50;
        $facture->array_options['options_libelle'] = "Facture N°" . $this->getNbFacture() . " du contrat " . $parent->getData('ref');
        if ($facture->create($user) > 0) {
            $nb_period = $parent->getData('duree_mois') / $parent->getData('periodicity');
            $nb_period_restante = $nb_period - $this->nb_facture;
            $rest = $this->get_total_facture();
            if (dol_print_date($facture->date) === dol_print_date($date_debut)) {
                $facture->addline("Période de facturation : Du <b>" . dol_print_date($facture->date) . "</b> au <b>" . dol_print_date($this->calc_next_date(true)) . "</b>", price($this->get_total_contrat('ht') / $nb_period_restante), 1, 20, 0, 0, 0, 0, $facture->date, $this->calc_next_date(true));
                addElementElement('contrat', 'facture', $parent->id, $facture->id);
            } else {
                $facture->addline("Période de facturation : Du <b>" . dol_print_date($facture->date) . "</b> au <b>" . dol_print_date($this->calc_next_date(true)) . "</b>", price($rest['info']['reste_a_payer_ht'] / $nb_period_restante), 1, 20, 0, 0, 0, 0, $facture->date, $this->calc_next_date(true));
                addElementElement('contrat', 'facture', $parent->id, $facture->id);
            }
        } else {
            return Array('errors' => 'error facture');
        }
        $this->updateLine($parent->id);

        $success = 'Facture ' . $facture->id . ' créer avec succès d\'un montant de ' . price($this->getData('next_facture_amount')) . ' €';
    }

    public function actionCreate_facture_perso($select_debut, $select_fin) {

        global $user, $db;
        $bimp = new BimpDb($db);
        $success = '';

        BimpTools::loadDolClass('compta/facture', 'facture');
        $facture = new Facture($db);
        $parent = $this->getParentInstance();
        $facture->date = $select_debut;
        $facture->socid = $parent->getData('fk_soc');
        $facture->array_options['options_type'] = "C";
        $facture->array_options['options_entrepot'] = 50;
        $facture->array_options['options_libelle'] = "Facture N°" . $this->getNbFacture() . " du contrat " . $parent->getData('ref');
        if ($facture->create($user) > 0) {
            $nb_period = $parent->getData('duree_mois') / $parent->getData('periodicity');
            $facture->addline("Période de facturation : Du <b>" . dol_print_date($select_debut) . "</b> au <b>" . dol_print_date($select_fin) . "</b>", number_format($this->get_total_contrat() / $nb_period, 2, '.', ''), 1, 20, 0, 0, 0, 0, $select_debut, $select_fin);
            addElementElement('contrat', 'facture', $parent->id, $facture->id);
        } else {
            return Array('errors' => 'error facture');
        }
        //$this->updateLine($parent->id);
        
        $success = 'Facture personnalisé ' . $facture->id . ' créer avec succès d\'un montant de ' . price($this->getData('next_facture_amount')) . ' €';
    }

    public function cron_create_facture() {
        // recuperer tous les echeancier passer

        $echeanciers = $this->getList();
        foreach ($echeanciers as $echeancier) {
            $echeancier->create_facture(true);
        }
    }
}
