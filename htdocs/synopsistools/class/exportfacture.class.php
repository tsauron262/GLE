<?php
require_once (DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");

class exportfacture {
//    var $sep = "    -   ";
//    var $saut = "<br/>";        
    var $sep = "\t";
    var $saut = "\n";        

    public $info = array();

    public function __construct($db, $sortie = 'html') {
        $this->db = $db;
    }

    public function exportFactureSav() {
        if($print)
        echo "Debut export : <br/>";
        $result = $this->db->query("SELECT fact.id as id "
                . "FROM `" . MAIN_DB_PREFIX . "facture` fact, " . MAIN_DB_PREFIX . "element_element el , " . MAIN_DB_PREFIX . "propal prop, " . MAIN_DB_PREFIX . "synopsischrono chrono , " . MAIN_DB_PREFIX . "synopsischrono_chrono_105 chronoT , " . MAIN_DB_PREFIX . "user_extrafields ue , " . MAIN_DB_PREFIX . "societe soc "
                . "WHERE fact.fk_soc = soc.rowid AND fact.fk_statut > 0 AND close_code is null AND (fact.extraparams < 1 || fact.extraparams is NULL) AND fact.total != 0 AND el.targettype = 'facture' AND el.sourcetype = 'propal' AND fk_target = fact.rowid AND prop.rowid = el.fk_source AND prop.fk_statut != 3 AND prop.rowid = chrono.propalid AND chronoT.id = chrono.id AND `fk_object` = IF(chronoT.Technicien > 0, chronoT.Technicien, fact.fk_user_author) "
                . "AND facnumber NOT LIKE '%PROV%' GROUP BY fact.rowid");

        while ($ligne = $this->db->fetch_object($result)) {
            $this->extract($$ligne->id);
        }
        
    }
    
    
    
    function getTxt($tab1, $tab2){
        $sortie = "";
        if(!isset($tab1[0]) || !isset($tab1[0]))
            return 0;
        
        foreach($tab1[0] as $clef => $inut)
            $sortie .= $clef.$this->sep;
        $sortie.= $this->saut;
        foreach($tab2[0] as $clef => $inut)
            $sortie .= $clef.$this->sep;
        $sortie.= $this->saut;
        
        
        foreach($tab1 as $tabT){
            foreach($tabT as $val)
                $sortie .= $val.$this->sep;
            $sortie.= $this->saut;
        }
        foreach($tab2 as $tabT){
            foreach($tabT as $val)
                $sortie .= $val.$this->sep;
            $sortie.= $this->saut;
        }
        
        return $sortie;
    }
    
    

    public function sortie($text, $nom = "temp") {
        global $user;

        $folder1 = (defined('DIR_SYNCH') ? DIR_SYNCH : DOL_DATA_ROOT . "/synopsischrono/export/" ) . "/";
        $folder2 = "extractFactGle/";
        if (!is_dir($folder1))
            mkdir($folder1);
        if (!is_dir($folder1 . $folder2))
            mkdir($folder1 . $folder2);
        $nom = str_replace(" ", "_", $nom); //die($folder . $nom . ".txt");
        $file = $folder2 . $nom . ".txt";
        if (file_put_contents($folder1 . $file, $text)) {
            return 1;
        }
        else {
            echo "<span style='color:red;'>Impossible d'exporté " .$folder1. $file . "</span>";
        }
    }

            
            
    function extract($id){

            $facture = new Facture($this->db);
            $facture->fetch($id);
            $societe = new Societe($this->db);
            $societe->fetch($facture->socid);
            
            
            
            $id8sens = 147;
            
            $tabFact = $tabFactDet = array();
            $tabFact[] = array("E"=>"E", "code_client"=>$societe->code_client, "nom"=>$societe->name, "phone"=>$societe->phone, "address"=>$societe->address, "zip"=>$societe->zip, "town"=>$societe->town, "facnumber"=>$facture->ref, "date"=> dol_print_date($facture->date, "%d-%m-%Y"), "email"=>$societe->email, "total"=>price($facture->total_ht), "total_ttc"=>price($facture->total_ttc), "id8Sens"=>$id8sens);
            $facture->fetch_lines();
            foreach($facture->lines as $line){
                $type = $this->getRef($line);
                $ref = "";
                if($line->fk_product > 0){
                    $prod = new Product($this->db);
                    $prod->fetch($line->fk_product);
                    $ref = $prod->ref;
                }
                
                $tabFactDet[] = array("L"=>"L", "ref"=>$ref, "product_type"=>$type, "qty"=>$line->qty, "subprice"=>price($line->subprice), "description"=>$line->desc, "buy_price_ht"=>price($line->pa_ht), "tva_tx"=>$line->tva_tx, "remise_percent"=>$line->remise_percent);
            
            }
            
            
  header("Content-Disposition: attachment; filename=\"test.txt\"");
            echo $this->getTxt($tabFact, $tabFactDet);
            
            
            
            
//                    $this->db->query("UPDATE " . MAIN_DB_PREFIX . "facture SET extraparams = 1 WHERE rowid = " . $idObj);
            
            
            die;
            
            
            return 1;
    }
    
    
    function getRef($line){
        if ($line->desc == "Acompte")
            $valeur = "GEN-SAV-ACOMPTE";
        elseif ($line->type == 1)
            $valeur = "GEN-SAV-MO";
        elseif ($line->type == 0)
            $valeur = "GEN-SAV-PIECES";
        else
            $valeur = "";

        return $valeur;
    }


   
}
