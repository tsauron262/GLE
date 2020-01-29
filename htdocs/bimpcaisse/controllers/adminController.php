<?php
class adminController extends BimpController{
    
    function ajaxProcessLoadFormFondCaisse(){
        $dateRef = GETPOST('dateRef');
        $html = "";
        global $db;
        
        
        $sql = $db->query("SELECT * FROM `llx_bc_caisse` c, llx_entrepot e WHERE `id_entrepot` = e.rowid ORDER BY e.ref");
        
        $tabEntCaisse = array();
        while ($ln = $db->fetch_object($sql)){
            $tabEntCaisse[$ln->ref][$ln->id] = $ln->name;
            $this->tabEntrepotEnfant[$ln->fk_parent][$ln->rowid] = $ln->ref;
            $this->tabIdEntrepot[$ln->ref] = $ln->rowid;
        }
        
        
        $tabResult = array();
        foreach($tabEntCaisse as $entrepot => $tabCaisses){
            $tot = 0;
            $htmlEnt = "";
            foreach($tabCaisses as $idCaisse => $nameCaisse){
                $sql2 = $db->query("SELECT * FROM `llx_bc_caisse_session` WHERE `id_caisse` = ".$idCaisse." AND `date_closed` < '".$dateRef." 23:59:59' ORDER BY `date_closed` DESC LIMIT 0,1");
                $montant = 0;$date = "";
                if($db->num_rows($sql2) > 0){
                    $ln = $db->fetch_object($sql2);
                    $montant = $ln->fonds_end;
                    $date = $ln->date_closed;
                }
                $htmlEnt .= "<h4>Caisse : ".$nameCaisse." </h4>Au ".dol_print_date($date)." | ".$montant." €";
                $tot += $montant;
            }
            
            $tabResult[$entrepot]= array("html"=>$htmlEnt, 'entrepot'=>$entrepot, "tot" => $tot);
        }
        
        foreach($tabResult as $result){
        }
        
        foreach($tabResult as $result){
            $html .= "<h3>Entrepot ".$result["entrepot"]." : ".$result["tot"]." €</h3>";
            
            $totEnfant = 0;
            $htmlEnfant = $this->getChildInfo($tabResult, $result["entrepot"], $totEnfant);
            
            
            if($htmlEnfant != '')
                $html .= "<h4>".$htmlEnfant. " : ".($result["tot"]+$totEnfant)." €</h4>";
            $html .= $result["html"]."<br/><br/>";
        }
        
        return array("html"=>$html);
    }
    
    function getChildInfo($tabResult, $entrepot, &$tot = 0){
        $html = "";
            if(isset($this->tabEntrepotEnfant[$this->tabIdEntrepot[$entrepot]])){
                foreach($this->tabEntrepotEnfant[$this->tabIdEntrepot[$entrepot]] as $entrepotFille){
                    $tot += $tabResult[$entrepotFille]["tot"];
                    $html .= '+'.$entrepotFille .$this->getChildInfo($tabResult, $entrepotFille, $tot);
                }
            }
            
            return $html;
    }
    
    
    function renderFondsFermeture(){
        $onclick = 'bimpModal.loadAjaxContent($(this), "loadFormFondCaisse", {dateRef: $("input[name=\'dateRef\']").val()}, "Fonds de caisse le "+$("input[name=\'dateRef\']").val()+" au soir");';
        $html = BimpRender::renderFreeForm(array(array('label'=>'Date', 'input' => BimpInput::renderInput('date', 'dateRef'))), array('<button class="btn btn-primary" onclick="'.htmlentities($onclick).'">Ok</button>'), 'Fonds de caisse');

        
        
        return $html;
        
    }
}