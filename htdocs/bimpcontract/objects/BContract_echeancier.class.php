<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpInput.php';

class BContract_echeancier extends BimpObject {

    public function display() {
        global $db;
        $facture = BimpTools::loadDolClass('compta/facture', 'facture');
        $facture = new Facture($db);

        //$line = $this->db->getRow('contrat_next_facture', 'id_contrat = ' . $this->id); // TODO à voir pour le 

        $parent = $this->getParentInstance();
        $this->calc_period_echeancier();
        //echo 'test = '. $this->datetime_prochain;
        $nb_period = $parent->getData('duree_mois') / $parent->getData('periodicity');
        $nb_period_restante = $nb_period - $this->nb_facture;
        $stop_echeancier = ($nb_period_restante == 0) ? true : false;
        $show_echeancier = ($parent->getData('statut') > 0) ? true : false;

        if ($show_echeancier) {
            $html = '';
            $html .= '<table class="noborder objectlistTable" style="border: none; min-width: 480px">';
            $html .= '<thead>'
                    . '<tr class="headerRow">'
                    . '<th class="th_checkboxes" width="40px" style="text-align: center">Période de facturation<br />Début - Fin</th>'
                    . '<th class="th_checkboxes" width="40px" style="text-align: center">Montant TTC</th>'
                    . '<th class="th_checkboxes" width="40px" style="text-align: center">Facture</th>'
                    . '<th class="th_checkboxes" width="40px" style="text-align: center">Payer</th>'
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
                $html .= '</td>'
                        . '<td style="text-align:center">' . price($lignes['montant_ttc']) . ' € </td>'
                        . '<td style="text-align:center">' . (is_object($facture) ? $facture->getNomUrl(1) : "<b>Pas encore de facture</b>") . '</td>'
                        . '<td style="text-align:center">'
                        . (($facture->paye > 0) ? '<i class="fa fa-check" style="color:green"> Payée</i>' : '<i class="fa fa-close" style="color:red"> Impayée</i>') . '</td>'
                        . '</tr>';
            }
            $html .= '</tbody>' . '</table>';

            $html .= '<br />';
            $html .= "<table style='width:70%;' class='border'>"
                    . "<tr>"
                    . "<th style='background-color:#ed7c1c;color:white;text-align:center'>Total contrat TTC</th>"
                    . "<td style='text-align:center'><b>" . price($this->get_total_contrat('ttc')) . " € </b> </td>"
                    . "<th style='background-color:#ed7c1c;color:white;text-align:center'>Date prochaine facture</th>"
                    . "<td style='text-align:center'><b>" . dol_print_date($this->getData('next_facture_date')) . "</b></td>"
                    . "<th style='background-color:#ed7c1c;color:white;text-align:center'>Montant prochaine facture</th>"
                    . "<td style='text-align:center'><b>" . price($this->getData('next_facture_amount')) . " € </b></td>"
                    . "</tr>"
                    . "</table>";
            if (!$stop_echeancier) {
                $callback = 'function(result) {if (typeof (result.file_url) !== \'undefined\' && result.file_url) {window.open(result.file_url)}}'; // TODO 
                $html .= '<br /><input class="btn btn-primary saveButton" value="Créer une facture" onclick="' .
                        $this->getJsActionOnclick("create_facture", array(), array(
                            "success_callback" => $callback
                        ))
                        . '"><br /><br />';
            }
        } else {
            $html .= BimpRender::renderAlerts("Vous devez valider le contrat pour afficher l\'échéancier");
        }
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
        $total_ttc = $this->get_total_contrat();
        $date_debut = new DateTime("$date_contrat");
        $date_debut->getTimestamp();
        $date_fin = new DateTime("$date_contrat");
        $date_fin->getTimestamp();
        $nb_period = $parent->getData('duree_mois') / $parent->getData('periodicity');
        $montant_facturer_ttc = $total_ttc / $nb_period;

        $list_fact = $this->get_total_facture();
        for ($i = 0; $i < $nb_period; $i++) {
            $this->tab_echeancier[$i] = array('date_debut' => $date_debut->format('Y-m-d'), 'date_fin' => $date_fin->format('Y-m-t'), 'montant_ttc' => $montant_facturer_ttc);
            if ($i == $this->nb_facture + 1) {
                $this->datetime_prochain = $date_debut->format('Y-m-d');
            }
            $date_debut->add(new DateInterval("P" . $parent->getData('periodicity') . "M"));
            $date_fin->add(new DateInterval("P" . $parent->getData('periodicity') . "M"));

            if (array_key_exists($i, $list_fact)) {
                $this->tab_echeancier[$i]['facture'] = $list_fact[$i]['id_facture'];
            } else {
                $this->tab_echeancier[$i]['facture'] = 0;
            }
        }
    }

    public function updateLine($id_contrat = null) {
//      A changer quand j'aurais fait les lignes en enfant
        //die($id_parent . ' <=');
        global $db;
        $bimp = new BimpDb($db);
        $lines = $bimp->getRows('bcontract_prelevement', 'id_contrat = ' . $id_contrat);
        $linesContrat = $bimp->getRows('contratdet', 'fk_contrat = ' . $id_contrat);
        foreach ($linesContrat as $line) {
            $ttc += $line->total_ttc;
        }
        if ($lines) {
//            $updateData = Array();
//            $bimp->update('bcontract_prelevement', $data);
            // La ligne existe -> à updater
            $parent = $this->getParentInstance();
            die('on crée pas');
        } else {
            if (!is_null($id_contrat)) {
                $instance = $this->getInstance('bimpcontract', 'BContract_contrat', $id_contrat);
                $nb_period = $instance->getData('duree_mois') / $instance->getData('periodicity');
                $montantFacture = number_format($this->get_total_contrat() / $nb_period, 2, '.', '');
                $insertData = Array(
                    'id_contrat' => $instance->id,
                    'next_facture_date' => $instance->getData('date_start'),
                    'next_facture_amount' => $montantFacture,
                    'validate' => 0
                );
                $bimp->insert('bcontract_prelevement', $insertData);
                return true;
            }
        }
    }

    public function calc_end_date_periode_facturation($start, $periodicity) {
        $date = '';

        return $date;
    }

    public function actionCreate_facture($data, &$success) {
        global $user, $db;
        $success = '';

        BimpTools::loadDolClass('compta/facture', 'facture');
        $facture = new Facture($db);

        $parent = $this->getParentInstance();
        $date_debut = $parent->getData('date_start');
        $period = $this->getData('periodicity');

        $this->calc_end_date_periode_facturation($date_debut, $period);

        $facture->date = $this->getData('next_facture_date');
        $facture->socid = $parent->getData('fk_soc');
        $facture->array_options['options_type'] = "C";
        $facture->array_options['options_entrepot'] = 50;
        $facture->array_options['options_libelle'] = "Facturation contrat " . $parent->getData('ref');
        if ($facture->create($user, 0) > 0) {
            $nb_period = $parent->getData('duree_mois') / $parent->getData('periodicity');

            $facture->addline("prélèvement", number_format($this->get_total_contrat() / $nb_period, 2, '.', ''), 1, 20);
            addElementElement('contrat', 'facture', $parent->id, $facture->id);
        } else {
            return Array('errors' => 'error facture');
        }


        $success = 'Facture créer avec succès d\'un montant de ' . price($this->getData('next_facture_amount')) . ' €';
    }

    public static function cron_create_facture() {
        // recuperer tous les echeancier passer

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
        }
        return $tab_facture;
    }

}
