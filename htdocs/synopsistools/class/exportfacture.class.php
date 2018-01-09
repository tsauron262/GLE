<?php
require_once (DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once (DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once (DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php");

class exportfacture {
//    var $sep = "    -   ";
//    var $saut = "<br/>";        
    var $sep = "\t";
    var $saut = "\n";        

    public $info = array();
    public $type = "";
    public $id8sens = 0;
    public $nbE = 0;
    public $debug = false;
    public $output = "Rien";
    
    private $where = " AND fact.fk_statut > 0 AND close_code is null AND (fact.extraparams < 1 || fact.extraparams is NULL) AND fact.total != 0  AND facnumber NOT LIKE '%PROV%' GROUP BY fact.rowid";

    public function __construct($db, $sortie = 'html') {
        $this->db = $db;
        $this->path = (defined('DIR_SYNCH') ? DIR_SYNCH : DOL_DATA_ROOT . "/synopsischrono/export/" ) . "/extractFactGle/";
    }
    
    
    public function exportTout(){
        $this->exportFactureSav();
        $this->exportFactureReseau();
        $this->getFactDontExport();
        $this->output = $this->nbE." facture(s) exportée(s)";
    }

    public function exportFactureSav() {
        $this->type = "sav";
        $result = $this->db->query("SELECT fact.rowid as id, idtech8sens as id8Sens, Centre "
                . "FROM `" . MAIN_DB_PREFIX . "facture` fact, `" . MAIN_DB_PREFIX . "facture_extrafields` fe, " . MAIN_DB_PREFIX . "element_element el , " . MAIN_DB_PREFIX . "propal prop, " . MAIN_DB_PREFIX . "synopsischrono chrono , " . MAIN_DB_PREFIX . "synopsischrono_chrono_105 chronoT , " . MAIN_DB_PREFIX . "user_extrafields ue , " . MAIN_DB_PREFIX . "societe soc "
                . "WHERE fe.fk_object = fact.rowid AND fe.`type` = 'S' AND el.targettype = 'facture' AND el.sourcetype = 'propal' AND fk_target = fact.rowid AND prop.rowid = el.fk_source AND prop.fk_statut != 3 AND prop.rowid = chrono.propalid AND chronoT.id = chrono.id AND ue.`fk_object` = IF(chronoT.Technicien > 0, chronoT.Technicien, fact.fk_user_author) AND fact.fk_soc = soc.rowid "
                . $this->where);
        

        while ($ligne = $this->db->fetch_object($result)) {
            $this->id8sens = $ligne->id8Sens;
            if ($ligne->id8Sens < 1 && isset($ligne->Centre) && $ligne->Centre != "") {
                require_once(DOL_DOCUMENT_ROOT."/synopsisapple/centre.inc.php");
                global $tabCentre;
                if (isset($tabCentre[$ligne->Centre][3]) && $tabCentre[$ligne->Centre][3] > 0)
                    $this->id8sens = $tabCentre[$ligne->Centre][3];
            }
            $this->extract($ligne->id);
        }
        
    }
    
    

    public function exportFactureReseau() {
        $this->path = DOL_DATA_ROOT . "/test/";
        $this->type = "R";
        $result = $this->db->query("SELECT fact.rowid as id "
                . "FROM `" . MAIN_DB_PREFIX . "facture` fact, `" . MAIN_DB_PREFIX . "facture_extrafields` fe "
                . "WHERE fe.fk_object = fact.rowid AND fe.`type` = 'R' ".$this->where);
        while ($ligne = $this->db->fetch_object($result)) {
            $this->id8sens = 239;
            $this->extract($ligne->id);
        }
        
    }

    public function getFactDontExport() {
        $this->path = DOL_DATA_ROOT . "/test/";
        $result = $this->db->query("SELECT fact.rowid as id, facnumber "
                . "FROM `" . MAIN_DB_PREFIX . "facture` fact "
                . "WHERE 1 ".$this->where);
        $facts = "";
        while ($ligne = $this->db->fetch_object($result)) {
            $facts .= $ligne->facnumber." - ";
        }
        if($facts != "")
            mailSyn2 ("Facture non export", "admin@bimp.fr, jc.cannet@bimp.fr", "BIMP-ERP<admin@bimp.fr>", "Bonjour voici les facture non exporté ".$facts);
        
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
                $sortie .= str_replace(array($this->saut, $this->sep, "\n", "\r"), "  ",$val).$this->sep;
            $sortie.= $this->saut;
        }
        foreach($tab2 as $tabT){
            foreach($tabT as $val)
                $sortie .= str_replace(array($this->saut, $this->sep, "\n", "\r"), "  ",$val).$this->sep;
            $sortie.= $this->saut;
        }
        
        return $sortie;
    }
    
    

            
            
    function extract($id){
        if($this->type == ""){
            $this->error ("Pas de type pour export ".$id);
        }
        elseif($this->id8sens == 0){
            $this->error ("Pas d'id collaborateur 8sens ".$id);
        }
        else{
            $this->exportOk = true;

            $facture = new Facture($this->db);
            $facture->fetch($id);
            $societe = new Societe($this->db);
            $societe->fetch($facture->socid);
            
            
            $tabFact = $tabFactDet = array();
            $tabFact[] = array("E"=>"E", "code_client"=>$societe->code_client, "nom"=>$societe->name, "phone"=>$societe->phone, "address"=>$societe->address, "zip"=>$societe->zip, "town"=>$societe->town, "facnumber"=>$facture->ref, "date"=> dol_print_date($facture->date, "%d-%m-%Y"), "email"=>$societe->email, "total"=>price($facture->total_ht), "total_ttc"=>price($facture->total_ttc), "id8Sens"=>$this->id8sens);
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
            
            
            
            
            if($this->exportOk){
                //header("Content-Disposition: attachment; filename=\"test.txt\"");
                $text = $this->getTxt($tabFact, $tabFactDet);
                if(file_put_contents($this->path. $facture->ref.".txt", $text)){
                    if($this->debug)
                        echo "<br/>Facture ".$facture->getNomUrl(1). " exporté<br/>";
                    $this->db->query("UPDATE " . MAIN_DB_PREFIX . "facture SET extraparams = 1 WHERE rowid = " . $facture->id);
                    $this->nbE++;
                    return 1;
                }
                else
                    $this->error ("Impossible d'exporté le fichier ".$this->path. $facture->ref.".txt");
            }
            return 0;
        }
    }
    
    
    function getRef($line){
        $tabCatProd = array(1202=>"GEN-ABO", 1203=>"GEN-CERTIF", 1204=>"GEN-HEBERG", 1205=>"GEN-LOC",
            1100=>"GEN-TELECOM", 1176=>"GEN-CONSO", 1216=>"GEN-SAV-PIECES", 1079=>"GEN-MAT", 1072=>"GEN-LOG", 1227=>"GEN-MO-EXT", 1156=>"GEN-MO-INT", 1225=>"GEN-MAINT-INT", 1140=>"GEN-MAIN-EXT", 1214=>"GEN-MAT-OCCAS", 1215=>"GEN-ACCES", 1207=>"GEN-PORT", 1217=>"GEN-DEP-INT", 1228=>"GEN-DEP-EXT", 1135=>"GEN-TEXTIL", 1233=>"GEN-ZZ");
        $valeur = "";
        
        if($line->total_ht == 0 && $line->pa_ht == 0)
            return "GEN-DIV-INFO";
        
        
        if($this->type == "sav"){
            if ($line->desc == "Acompte")
                $valeur = "GEN-SAV-ACOMPTE";
            elseif ($line->fk_product_type == 1)
                $valeur = "GEN-SAV-MO";
            elseif ($line->fk_product_type == 0)
                $valeur = "GEN-SAV-PIECES";
        }
        else{
            $idP = $line->fk_product;
            if($idP > 0){
                $catId = 0;
                $sql = $this->db->query("SELECT fk_categorie FROM `".MAIN_DB_PREFIX."categorie_product` WHERE fk_categorie IN (SELECT rowid FROM `".MAIN_DB_PREFIX."categorie` WHERE `fk_parent` = 932 ORDER BY `type` DESC) AND `fk_product` = ".$idP);
                if($this->db->num_rows($sql) > 0)
                    while($ligne = $this->db->fetch_object($sql))
                        $catId = $ligne->fk_categorie;
                if($catId > 0){
                    if(isset($tabCatProd[$catId]))
                        $valeur = $tabCatProd[$catId];
                    else
                        $this->error ("Pas de Code 8sens pour la catégorie ".$catId, 0, $catId);
                }
                else
                    $this->error ("Pas de categorie pour le prod ".$idP, $idP);
            }
            else{
                if ($line->desc == "Acompte")
                    $valeur = "GEN-RES-ACOMPTE";
                elseif ($line->fk_product_type == 1)
                    $valeur = "GEN-DIV-SERV";
                elseif ($line->fk_product_type == 0)
                    $valeur = "GEN-DIV-MAT";
            }
        }
        
        if($valeur == "")
            $this->exportOk = false;

        return $valeur;
    }


    function error($msg, $idProd = 0, $idCat = 0){
        dol_syslog($msg,3, 0, "_extract");
        $to = "";
        
        if($idProd > 0){
            $prod = new Product($this->db);
            $prod->fetch($idProd);
            $msg .= "<br/>".$prod->getNomUrl(1);
            $to = "a.delauzun@bimp.fr, tommy@bimp.fr";
        }
        if($idCat > 0){
            $cat = new Categorie($this->db);
            $cat->fetch($idCat);
            $msg .= $cat->getNomUrl(1);
            $to = "tommy@bimp.fr";
        }
        if($to != "")
            mailSyn2("Produit non catégorisé", $to, "admin@bimp.fr", "Bonjour ceci est un message automatique des export vers 8sens <br/>".$msg);
        if($this->debug)
            echo "<span class='red'>".$msg."</span><br/>";
    }
   
}

