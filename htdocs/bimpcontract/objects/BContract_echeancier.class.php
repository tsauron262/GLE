<?php

class BContract_echeancier extends BimpObject {

    public function display() {
        //$line = $this->db->getRow('contrat_next_facture', 'id_contrat = ' . $this->id); // TODO à voir pour le 
        $parent = $this->getParentInstance();

        //$echeancier = $this->tableau($list);
        // print_r($list);
        //echo $this->getData('duree_mois');

        $html = '';
        $html .= '<table class="noborder objectlistTable" style="border: none; min-width: 480px" width="100%">';
        $html .= '<thead>'
                . '<tr class="headerRow">'
                . '<th class="th_checkboxes" width="40px" style="text-align: center">Période de facturation</th>'
                . '<th class="th_checkboxes" width="40px" style="text-align: center">Montant TTC</th>'
                . '<th class="th_checkboxes" width="40px" style="text-align: center">Facture</th>'
                . '</tr></thead>'
                . '<tbody class="listRows">'
                . '<tr class="objectListItemRow" >'
                . '<td style="text-align:center"><input type="hidden" name="ref" value="CT04190114">' . $parent->id . '</td>'
                . '<td style="text-align:center"><input type="hidden" name="ref" value="CT04190114">CT04190114</td>'
                . '<td style="text-align:center"><input type="hidden" name="ref" value="CT04190114">CT04190114</td>'
                . '</tr>'
                . '</tbody>';
        $html .= '</table>';


        return $html;
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
}
