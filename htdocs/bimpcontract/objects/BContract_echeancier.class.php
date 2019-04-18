<?php

class BContract_echeancier extends BimpObject {
    
    public function data_echeancier($line){
        $return = Array();
        $debut_echeancier = $this->getData('date_start');
        $duree_echeancier = $this->getData('duree_mois');
        $periodicite_echeancier = $this->getData('periodicity');
        $nb_period_echeancier = $duree_echeancier - $periodicite_echeancier;
        $contract = $this->getInstance('bimpcontract', 'Bcontract_contrat', $id);
        $contract->getData('total_ttc'); 
        $contract->getData('total_ht'); 
        
        return $return;
    }
    
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
                . '<th class="th_checkboxes" width="40px" style="text-align: center">Montant HT</th>'
                . '<th class="th_checkboxes" width="40px" style="text-align: center">Facture</th>'
                . '</tr></thead>'
                . '<tbody class="listRows">'
                . '<tr class="objectListItemRow" >'
                . '<td style="text-align:center"><input type="hidden" name="ref" value="CT04190114">'.$parent->id.'</td>'
                . '<td style="text-align:center"><input type="hidden" name="ref" value="CT04190114">CT04190114</td>'
                . '<td style="text-align:center"><input type="hidden" name="ref" value="CT04190114">CT04190114</td>'
                . '<td style="text-align:center"><input type="hidden" name="ref" value="CT04190114">CT04190114</td>'
                . '</tr>'
                . '</tbody>';
        $html .= '</table>';
       
        
        return $html;
    }
    
     public function tableau($line){
        $return = Array();
        $debut_echeancier = $this->getData('date_start');
        $duree_echeancier = $this->getData('duree_mois');
        $periodicite_echeancier = $this->getData('periodicity');
        $nb_period_echeancier = $duree_echeancier - $periodicite_echeancier;
        $ttc = $this->getData('total_ttc'); 
        $ht = $this->getData('total_ht'); 
        
        
        return $return;
    }
    
}