<?php

class FactureContrat extends BimpObject {
    
    
    public function display_montant_ln(){
        if($this->isLoaded()){
            $contrat = $this->getChildObject('contrat');
            $datef = new DateTime();
            $datef->setTimestamp(strtotime($this->getData('datef')));
            
            $debut = new DateTime();
            $fin = new DateTime();
            $Timestamp_debut = strtotime($contrat->getData('date_start'));
//            echo $datef->format('d / m / Y').'<br/>';
            $renouvellement = 0;
            if ($Timestamp_debut > 0 && $contrat->getData('duree_mois') > 0){ 
                $debut->setTimestamp($Timestamp_debut);
                $fin->setTimestamp($Timestamp_debut);
                for($i=0; $i <5; $i++){
                    $fin = $fin->add(new DateInterval("P" . $contrat->getData('duree_mois') . "M"));
                    $fin = $fin->sub(new DateInterval("P1D"));
//                    echo($debut->format('d / m / Y').' '.$fin->format('d / m / Y').' '.$i.'av<br/>');
                    if($datef > $debut && $datef < $fin){
                        $renouvellement = $i;
                        break;
                    }
                    $debut = $debut->add(new DateInterval("P" . $contrat->getData('duree_mois') . "M"));
//                    $fin = $fin->add(new DateInterval("P1D"));
                }
                
            }
            if($renouvellement == $this->getData('renouvellement'))
                return price($this->getData('totalLn') / $contrat->getTotal($renouvellement) * $this->getData('totalFact'));
        }
        return 0;
    }
}
