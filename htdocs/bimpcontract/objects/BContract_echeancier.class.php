<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpInput.php';

class BContract_echeancier extends BimpObject {

    public function display() {
        //$line = $this->db->getRow('contrat_next_facture', 'id_contrat = ' . $this->id); // TODO à voir pour le 
        $parent = $this->getParentInstance();

        //$echeancier = $this->tableau($list);
        //print_r($list);

        $this->calc_period_echeancier();
        $show_echeancier = ($parent->getData('statut') > 0) ? true : false;

        if ($show_echeancier) {
            $html = '';
            $html .= '<table class="noborder objectlistTable" style="border: none; min-width: 480px">';
            $html .= '<thead>'
                    . '<tr class="headerRow">'
                    . '<th class="th_checkboxes" width="40px" style="text-align: center">Période de facturation<br />Début - Fin</th>'
                    . '<th class="th_checkboxes" width="40px" style="text-align: center">Montant TTC</th>'
                    . '<th class="th_checkboxes" width="40px" style="text-align: center">Facture</th>'
                    . '<th class="th_checkboxes" width="40px" style="text-align: center">Numéro de facture</th>'
                    . '</tr></thead>'
                    . '<tbody class="listRows">';
            foreach ($this->tab_echeancier as $lignes) {
                $html .= '<tr class="objectListItemRow" >'
                        . '<td style="text-align:center" >'
                        . 'Du ' . $lignes['date_debut'] . ' au ' . $lignes['date_fin'];
                if ($lignes['date_debut'] > date('Y-m-d')) {
                    // dates a jour
                } else {
                    $html .= BimpRender::renderAlerts("[!] Période de facturation en retard ou en attente de règlement", 'danger', false);
                }

                $html .= '</td>'
                        . '<td style="text-align:center">' . $lignes['montant_ttc'] . ' € </td>'
                        . '<td style="text-align:center">FA14526</td>'
                        . '<td style="text-align:center"><i class="fas fa-plus" onclick=""></i></td>' // TODO à changer
                        . '</tr>';
            }
            $html .= '</tbody>' . '</table>';
            $callback = 'function(result) {if (typeof (result.file_url) !== \'undefined\' && result.file_url) {window.open(result.file_url)}}'; // TODO 
            $html .= '<br /><input class="btn btn-primary saveButton" value="Créer une facture" onclick="' .
                    $this->getJsActionOnclick("create_facture", array(), array(
                        "success_callback" => $callback
                    ))
                    . '"><br /><br />';
        } else {
            $html .= BimpRender::renderAlerts("Vous devez valider le contrat pour afficher votre échéancier");
        }
        //$html .= '<br />'
        // . 'Total du contrat = ';
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
                . '<td style="text-align:center">' . $total_ttc . '</td>'
                . '<td style="text-align:center">' . $total_ttc . '</td>'
                . '</tr>'
                . '</tbody>' . '</table>';


        return $html;
    }

    public function calc_period_echeancier() {
        $parent = $this->getParentInstance();
        $date_contrat = $parent->getData('date_start');
        $total_ttc = $parent->getData('total');

        $date_debut = new DateTime("$date_contrat");
        $date_debut->getTimestamp();
        $date_fin = new DateTime("$date_contrat");
        $date_fin->getTimestamp();
        $date_fin->add(new DateInterval("P" . $parent->getData('periodicity') . "M"));
        $date_fin->sub(new DateInterval("P1D"));
        $nb_period = $parent->getData('duree_mois') / $parent->getData('periodicity');
        $montant_facturer_ttc = $total_ttc / $nb_period;

        for ($i = 1; $i <= $nb_period; $i++) {
            $this->tab_echeancier[] = array('date_debut' => $date_debut->format('Y-m-d'), 'date_fin' => $date_fin->format('Y-m-d'), 'montant_ttc' => $montant_facturer_ttc);
            $date_debut->add(new DateInterval("P" . $parent->getData('periodicity') . "M"));
            $date_fin->add(new DateInterval("P" . $parent->getData('periodicity') . "M"));
        }
//        echo '<pre>';
//        print_r($parent);
//         echo '</pre>';
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
                $montantFacture = number_format($ttc / $nb_period, 2, '.', '');
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

            $facture->addline("prélèvement", $this->getData('next_facture_amount'), 1, 20);
            addElementElement('contrat', 'facture', $parent->id, $facture->id);
        } else {
            return Array('errors' => 'error facture');
        }


        $success = 'Facture créer avec succes d\'un motant de ' . price($this->getData('next_facture_amount')) . ' €';
    }

    public static function cron_create_facture() {
        // recuperer tous les echeancier passer


        foreach ($echeanciers as $echeancier) {
            $echeancier->create_facture(true);
        }
    }

}
