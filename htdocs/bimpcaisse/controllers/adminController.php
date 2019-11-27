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
        }
        
        
        foreach($tabEntCaisse as $entrepot => $tabCaisses){
            $html .= "<br/><br/>Entrepot : ".$entrepot;
            $tot = 0;
            foreach($tabCaisses as $idCaisse => $nameCaisse){
                $sql2 = $db->query("SELECT * FROM `llx_bc_caisse_session` WHERE `id_caisse` = ".$idCaisse." AND `date_closed` < '".$dateRef." 23:59:59' ORDER BY `date_closed` DESC LIMIT 0,1");
                $montant = 0;$date = "";
                if($db->num_rows($sql2) > 0){
                    $ln = $db->fetch_object($sql2);
                    $montant = $ln->fonds_end;
                    $date = $ln->date_closed;
                }
                $html .= "<br/>Caisse : ".$nameCaisse." | ".dol_print_date($date)." | ".$montant;
                $tot += $montant;
            }
            $html .= "<br/>Total : ".$tot;
            
        }
        
        return array("html"=>$html);
    }
    
    
    function renderFondsFermeture(){
        $onclick = 'bimpModal.loadAjaxContent($(this), "loadFormFondCaisse", {dateRef: $("#dateRef").val()}, "Fond de caisse");';
        $html = BimpRender::renderFreeForm(array(array('label'=>'Date', 'input' => BimpInput::renderInput('date', 'dateRef'))), array('<button class="btn btn-primary" onclick="'.htmlentities($onclick).'">Ok</button>'), 'Fonds de caisse');

        
        
        return $html;
        
    }
}