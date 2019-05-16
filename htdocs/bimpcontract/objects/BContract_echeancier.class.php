<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpInput.php';

class BContract_echeancier extends BimpObject {
    
    public function canViewObject($object) {
        if(is_object($object)) return true;
        return false;
    }
    
    public function display($display_error = '') {

        if ($display_error) {
            return BimpRender::renderAlerts($display_error);
        } else {
            
        }

        global $db;
        $facture = BimpTools::loadDolClass('compta/facture', 'facture');
        $facture = new Facture($db);

        //$line = $this->db->getRow('contrat_next_facture', 'id_contrat = ' . $this->id); // TODO à voir pour le 
        $parent = $this->getParentInstance();
        $nb_period = $parent->getData('duree_mois') / $parent->getData('periodicity');
        $this->calc_period_echeancier();
        $this->getNbFacture();
        $nb_period_restante = $nb_period - $this->nb_facture;
        $stop_echeancier = ($nb_period_restante == 0) ? true : false;

        $html = '';
        $html .= '<table class="noborder objectlistTable" style="border: none; min-width: 480px">'
                . '<thead>'
                . '<tr class="headerRow">'
                . '<th class="th_checkboxes" width="40px" style="text-align: center">Période de facturation<br />Début - Fin</th>'
                . '<th class="th_checkboxes" width="40px" style="text-align: center">Montant HT</th>'
                . '<th class="th_checkboxes" width="40px" style="text-align: center">Montant TTC</th>'
                . '<th class="th_checkboxes" width="40px" style="text-align: center">Facture</th>'
                . '<th class="th_checkboxes" width="40px" style="text-align: center">&Eacute;tat de paiement</th>'
                . '<th class="th_checkboxes" width="40px" style="text-align: center">Action facture</th>'
                . '</tr></thead>'
                . '<tbody class="listRows">';
        foreach ($this->tab_echeancier as $lignes) {
            $html .= '<tr class="objectListItemRow" >'
                    . '<td style="text-align:center" >'
                    . 'Du ' . dol_print_date($lignes['date_debut']) . ' au ' . dol_print_date($lignes['date_fin']);

            if ($lignes['facture'] > 0) {
                $facture->fetch($lignes['facture']);
            } else {
                $facture = null;
            }

            if ($facture->paye) {
                $paye = '<i class="fa fa-check" style="color:green"><b> Payée</b></i>';
            } elseif (is_object($facture) && $facture->paye == 0) {
                $paye = '<i class="fa fa-close" style="color:red"> <b>Impayée</b></i>';
            } else {
                $paye = '<i class="fa fa-refresh" style="color:orange" > <b>En attente de facturation</b></i>';
            }
            
            // Construction actionButton
            $actionsButtons = '';
            $actionsButtons .= ($this->canViewObject($facture)) ? '<span class="rowButton bs-popover" data-trigger="hover" data-placement="top"  data-content="Vue rapide de la facture" onclick="loadModalView(\'bimpcommercial\', \'Bimp_facture\', '.$facture->id.', \'default\', $(this), \'Facture ' . $facture->ref . '\')"><i class="far fa5-eye"  ></i></span>' : '';
            $actionsButtons .= (is_object($facture) && $facture->statut == 0 && $this->getData('validate') == 0) ? '<span class="rowButton bs-popover" data-trigger="hover" data-placement="top"  data-content="Supprimer la facture" onclick="")"><i class="fa fa-times" ></i></span>' : '';
            $actionsButtons .= (is_object($facture) && $facture->statut == 0 && $this->getData('validate') == 0 ) ?  '<span class="rowButton bs-popover" data-trigger="hover" data-placement="top"  data-content="Valider la facture" onclick="")"><i class="fa fa-check" ></i></span>' : '';
            $actionsButtons .= (!$this->canViewObject($facture)) ? '<span style="cursor: not-allowed" class="rowButton bs-popover" data-trigger="hover" data-placement="top"  data-content="Pas de facture à voir" onclick="")"><i class="far fa-eye-slash"></i></span>' : '';
            
            $html .= '</td>'
                    . '<td style="text-align:center">' . price($lignes['montant_ht']) . ' € </td>'
                    . '<td style="text-align:center">' . price($lignes['montant_ttc']) . ' € </td>'
                    . '<td style="text-align:center">' . (is_object($facture) ? $facture->getNomUrl(1) : "<b style='color:grey'>Pas encore de facture</b>") . '</td>'
                    . '<td style="text-align:center">' . $paye . '</td>'
                    . '<td style="text-align:center; margin-right:10%">'
                    . $actionsButtons
                    . '</td>'
                    . '</tr>';
        } // 
        $html .= '</tbody>' . '</table>';
        $html .= $this->display_info();
        
        $html .= "<table style='width:30%;' class='border' border='1'>"
                . "<tr><th style='background-color:#ed7c1c;color:white;text-align:center'>Date prochaine facture</th>"
                . "<td style='text-align:center'><b>" . dol_print_date($this->getData('next_facture_date')) . "</b></td></tr>"
                . "<tr><th style='background-color:#ed7c1c;color:white;text-align:center'>Montant prochaine facture</th>"
                . "<td style='text-align:center'><b>" . price($this->calc_next_facture_amount_ht()) . " € HT / ". price($this->calc_next_facture_amount_ttc()) . " € TTC </b></td></tr>"
                . "</table><br />";

        if (!$stop_echeancier) {
            $callback = 'function(result) {if (typeof (result.file_url) !== \'undefined\' && result.file_url) {window.open(result.file_url)}}'; // TODO 
            $html .= '<br /><input class="btn btn-primary saveButton" value="Créer une facture" onclick="' .
                    $this->getJsActionOnclick("create_facture", array(), array(
                        "success_callback" => $callback
                    ))
                    . '"><br /><br />';
        }

        $tmp_id_facture = 0;
        foreach ($this->tab_echeancier as $tab => $attr) {
            if ($attr['facture'] > 0) {
                $tmp_id_facture = $attr['facture'];
            }
        }
        if ($parent->getData('statut') > 0 && $parent->getInitData('statut') > 0 && $tmp_id_facture > 0) {
            $facture = new Facture($db);
            $facture->fetch($tmp_id_facture);
            $date_database = new DateTime($this->getData('next_facture_date'));
            $date_database->getTimestamp();
            $date_database->sub(new DateInterval("P" . $parent->getData('periodicity') . "M"));
            $date_facture = new DateTime(date('Y-m-d', $facture->date));
            $date_facture->getTimestamp();

            if ($date_database > $date_facture) {
                //echo 'chidos';
                $this->updateLine($this->getData('id_contrat'), $date_database->format('Y-m-d'));
            }
        }

        // Gestion de la première facture
        if ($tmp_id_facture == 0) {
            $this->updateLine($this->getData('id_contrat'), $parent->getData('date_start'));
        }

//        echo '<pre>';
//        print_r($this->tab_echeancier);
        return $html;
    }

    public function display_info() {
        $tab_total_fact = $this->get_total_facture();
        foreach ($tab_total_fact['info'] as $line) {
            //echo 'info = ' . $line;
        }
        $html .= "<br/>"
                . "<table style='width:50%;float:right' class='border' border='1'>"
                . "<tr> <th style='border: 1px solid Transparent!important;'></th>  <th style='background-color:#ed7c1c;color:white;text-align:center'>HT</th> <th style='background-color:#ed7c1c;color:white;text-align:center'>TTC</th> </tr>"
                . "<tr> <th style='background-color:#ed7c1c;color:white;text-align:center'>Total contrat</th> <td style='text-align:center'><b>" . price($this->get_total_contrat('ht')) . " €</b></td> <td style='text-align:center'><b> " . price($this->get_total_contrat('ttc')) . " €</b></td> </tr>"
                . "<tr > <th  style='background-color:#ed7c1c;color:white;text-align:center'>Déjà payer</th> <td style='text-align:center'><b>" . price($tab_total_fact['info']['deja_payer_ht']) . " € </b></td> <td style='text-align:center'><b> " . price($tab_total_fact['info']['deja_payer_ttc']) . " €</b></td> </tr>"
                . "<tr> <th style='background-color:#ed7c1c;color:white;text-align:center'>Reste à payer</th> <td style='text-align:center'><b> " . price($tab_total_fact['info']['reste_a_payer_ht']) . " € </b></td> <td style='text-align:center'><b> " . price($tab_total_fact['info']['reste_a_payer_ttc']) . " €</b></td> </tr>"
                . "</table>";

        return $html;
    }

    public function display_info_echeancier() {
        $parent = $this->getParentInstance();
        $total_ttc = $parent->getData('total');

        $html = '';
        $html .= '<table class="noborder objectlistTable" style="border: none; min-width: 480px">'
                . '<thead>'
                . '<tr class="headerRow">'
                . '<th class="th_checkboxes" width="40px" style="text-align: center">Total du contrat</th>'
                . '<th class="th_checkboxes" width="40px" style="text-align: center">Total facturé</th>'
                . '</tr></thead>'
                . '<tbody class="listRows">'
                . '<tr class="objectListItemRow" >'
                . '<th style="text-align:center">' . $total_ttc . '</th>'
                . '<th style="text-align:center">' . $total_ttc . '</th>'
                . '</tr>'
                . '</tbody>' . '</table>';


        return $html;
    }

    public function calc_period_echeancier() {
        $parent = $this->getParentInstance();
        $date_contrat = $parent->getData('date_start');
        $total_ht = $this->get_total_contrat('ht');
        $total_ttc = $this->get_total_contrat('ttc');
        $date_debut = new DateTime("$date_contrat");
        $date_debut->getTimestamp();
        $date_fin = new DateTime("$date_contrat");
        $date_fin->getTimestamp();
        $nb_period = $parent->getData('duree_mois') / $parent->getData('periodicity');
        $montant_facturer_ttc = $total_ttc / $nb_period;
        $montant_facturer_ht = $total_ht / $nb_period;

        $list_fact = $this->get_total_facture();
        for ($i = 0; $i < $nb_period; $i++) {
            $this->tab_echeancier[$i] = array('date_debut' => $date_debut->format('Y-m-d'), 'date_fin' => $date_fin->format('Y-m-t'), 'montant_ht' => $montant_facturer_ht, 'montant_ttc' => $montant_facturer_ttc);
            $date_debut->add(new DateInterval("P" . $parent->getData('periodicity') . "M"));
            $date_fin->add(new DateInterval("P" . $parent->getData('periodicity') . "M"));

            if (array_key_exists($i, $list_fact)) {
                $this->tab_echeancier[$i]['facture'] = $list_fact[$i]['id_facture'];
            } else {
                $this->tab_echeancier[$i]['facture'] = 0;
            }
        }
    }

    public function calc_next_facture_amount_ht() {
        $parent = $this->getParentInstance();
        $instance = $this->getInstance('bimpcontract', 'BContract_contrat', $parent->id);
        $nb_period = $instance->getData('duree_mois') / $instance->getData('periodicity');
        $montantFacture = number_format($this->get_total_contrat('ht') / $nb_period, 2, '.', '');

        return $montantFacture;
    }
    
    public function calc_next_facture_amount_ttc() {
        $parent = $this->getParentInstance();
        $instance = $this->getInstance('bimpcontract', 'BContract_contrat', $parent->id);
        $nb_period = $instance->getData('duree_mois') / $instance->getData('periodicity');
        $montantFacture = number_format($this->get_total_contrat('ttc') / $nb_period, 2, '.', '');

        return $montantFacture;
    }

    public function calc_next_date($sub = false) {
        $parent = $this->getParentInstance();
        $date = $this->getData('next_facture_date');
        $periodicity = $parent->getData('periodicity');
        $nextdate = new DateTime("$date");
        $nextdate->getTimestamp();
        $nextdate->add(new DateInterval("P" . $periodicity . "M"));

        if ($sub) {
            $nextdate->sub(new DateInterval("P1D"));
        }

        $newdate = $nextdate->format('Y-m-d');
        return $newdate;
    }

    public function updateLine($id_contrat = null, $new_date = null) {
//      A changer quand j'aurais fait les lignes en enfant
        //die($id_parent . ' <=');
        global $db;
        $parent = $this->getParentInstance();
        $bimp = new BimpDb($db);
        $lines = $bimp->getRows('bcontract_prelevement', 'id_contrat = ' . $id_contrat);
        $linesContrat = $bimp->getRows('contratdet', 'fk_contrat = ' . $id_contrat);

        if ($parent->getData('statut') > 0 && $parent->getInitData('statut') > 0) {
            $updateDate = $this->calc_next_date();
        }


        if (!is_null($new_date)) {
            $updateDate = $new_date;
        }

        foreach ($linesContrat as $line) {
            $ttc += $line->total_ttc;
        }
        if ($lines) {
            $parent = $this->getParentInstance();
            $updateData = Array(
                'next_facture_date' => $updateDate,
                'next_facture_amount' => $this->calc_next_facture_amount_ht()
            );
            $bimp->update('bcontract_prelevement', $updateData, 'id_contrat = ' . $parent->id);
        } else {
            if (!is_null($id_contrat)) {
                $instance = $this->getInstance('bimpcontract', 'BContract_contrat', $id_contrat);
                $insertData = Array(
                    'id_contrat' => $instance->id,
                    'next_facture_date' => $instance->getData('date_start'),
                    'next_facture_amount' => $this->calc_next_facture_amount(),
                    'validate' => 0
                );
                $bimp->insert('bcontract_prelevement', $insertData);
                return true;
            }
        }
    }

    public function getNbFacture() {
        $return = 0;
        if (!$this->tab_echeancier) {
            $this->calc_period_echeancier();
        }
        foreach ($this->tab_echeancier as $periode) {
            if ($periode['facture'] > 0) {
                $return++;
            }
        }
        return $return + 1;
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
        $now=dol_now();
		$arraynow=dol_getdate($now);
		$nownotime=dol_mktime(0, 0, 0, $arraynow['mon'], $arraynow['mday'], $arraynow['year']);
                 $facture->date_lim_reglement = $nownotime+ 3600 * 24 *30;
        $facture->date_lim_reglement=$facture->calculate_date_lim_reglement();
		$facture->mode_reglement_id   = 0;		// Not forced to show payment mode CHQ + VIR
		$facture->mode_reglement_code = '';	// Not forced to show payment mode CHQ + VIR
        $facture->socid = $parent->getData('fk_soc');
        $facture->array_options['options_type'] = "C";
        $facture->array_options['options_entrepot'] = 50;
        $facture->array_options['options_libelle'] = "Facture N°" . $this->getNbFacture() . " du contrat " . $parent->getData('ref');
        if ($facture->create($user) > 0) {
            $nb_period = $parent->getData('duree_mois') / $parent->getData('periodicity');
            $facture->addline("Période de facturation : Du <b>" . dol_print_date($facture->date) . "</b> au <b>" . dol_print_date($this->calc_next_date(true)) . "</b>", number_format($this->get_total_contrat() / $nb_period, 2, '.', ''), 1, 20);
            addElementElement('contrat', 'facture', $parent->id, $facture->id);
        } else {
            return Array('errors' => 'error facture');
        }
        $this->updateLine($parent->id);
        
        $success = 'Facture créer avec succès d\'un montant de ' . price($this->getData('next_facture_amount')) . ' €';   
    }

    public function cron_create_facture() {
        // recuperer tous les echeancier passer

        $echeanciers = $this->getList();
        foreach ($echeanciers as $echeancier) {
            $echeancier->create_facture(true);
        }
    }

    public function get_total_contrat($ttc_or_ht = 'ht') {
        $parent = $this->getParentInstance();
        //print('<pre>');
        //print_r($parent->dol_object->lines); //TODO a changer
        //$lines = $parent->getChildrenListArray('lines');
        $lines = $parent->dol_object->lines;

        foreach ($lines as $line) {
            if ($ttc_or_ht == 'ht') {
                $return += $line->total_ht;
            } else {
                $return += $line->total_ttc;
            }
        }
        return $return;
    }

    public function get_total_facture() {
        global $db;

        $this->deja_payer_ttc = $this->deja_payer_ht = $this->nb_facture = 0;
        $parent = $this->getParentInstance();
        $bimp = new BimpDb($db);
        $lines = $bimp->getRows('element_element', 'sourcetype = "contrat" AND targettype="facture" AND fk_source=' . $parent->id . ' ORDER BY rowid ASC');
        $tab_facture = Array();
        foreach ($lines as $line) {
            $facture = new Facture($db);
            $facture->fetch($line->fk_target);
            $this->deja_payer_ht += $facture->total_ht;
            $this->deja_payer_ttc += $facture->total_ttc;
            $this->nb_facture++;
            $tab_facture[]['id_facture'] = $line->fk_target;
            $tab_facture['info']['deja_payer_ht'] = $this->deja_payer_ht;
            $tab_facture['info']['deja_payer_ttc'] = $this->deja_payer_ttc;
            $tab_facture['info']['reste_a_payer_ht'] = $this->get_total_contrat('ht') - $tab_facture['info']['deja_payer_ht'];
            $tab_facture['info']['reste_a_payer_ttc'] = $this->get_total_contrat('ttc') - $tab_facture['info']['deja_payer_ttc'];
        }
        return $tab_facture;
    }

    //get last facture id/object to get date last fact
    public function get_last_facture() {
        global $db;

        $parent = $this->getParentInstance();
        $bimp = new BimpDb($db);
        $factid = $bimp->getRows('element_element', 'sourcetype = "contrat" AND targettype="facture" AND fk_source=' . $parent->id . ' ORDER BY rowid ASC');
        $tab = Array();
        foreach ($factid as $line) {
            $facture = new Facture($db);
            $facture->fetch($line->fk_target);
            $tab[]['id_facture'] = $line->fk_target;
            $tab[$line->fk_target]['facture'] = $facture;
        }
        $last_id_fact = (end($tab));
        $date_last_facture = $last_id_fact['facture']->date;
        $date_format = date('Y-m-d', $date_last_facture);

        return $date_format;
    }

}
