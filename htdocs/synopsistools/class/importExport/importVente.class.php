<?php

if (!isset($conf)) {
    require_once "../../main.inc.php";
    llxHeader();
    $go = true;
}

require_once DOL_DOCUMENT_ROOT."/synopsistools/importExport/class/import8sens.class.php";

class importVente extends import8sens {
    var $tabResultInt = array();
    var $logOld = false;
    public function __construct($db) {
        $this->last = true;
        parent::__construct($db);
    }
    
    public function go(){
        $this->action = 1;
        $this->path .= "./inventaire/exportVente/";
        parent::go();
        
        if($this->logOld){
            require_once DOL_DOCUMENT_ROOT."/synopsistools/class/export8sens.class.php";
            $export = new export8sens($this->db);
            $tabInter = array();
            foreach($this->tabResultInt['prod'] as $tmp){
                $tabInter[] = array("PlvGArtID"=>"", "PlvGArtCode"=> $tmp['ref'], "PlvQteATran"=> "0", "PlvQteTr"=> "0", "PlvPUNet"=> $tmp['derPrix'], "PlvDate"=> $tmp['derDate']);
            }
            file_put_contents($this->path."old.txt", $export->getTxt($tabInter, array()));
        }
        else{
            $this->action = 3;
            $this->path .= "../exportAchat/";
            parent::go();
            
            
            $this->action = 4;
            $this->path .= "../exportArticle/";
            parent::go();
            
            
            
            
            
            
            $this->action = 2;
            $this->utf8 = false;
            $this->path .= "../exportInvent/";
            parent::go();


            require_once DOL_DOCUMENT_ROOT."/synopsistools/class/export8sens.class.php";
            $export = new export8sens($this->db);
            file_put_contents($this->path."../resultInvent/result.txt", $export->getTxt($this->tabResultInt['inventaire'], array()));
        }
    }
    


    function traiteLn($ln) {
        $this->tabResultInt["total"] ++; 
        if($this->action == 2)
            $this->traiteLnInvent ($ln);
        elseif($this->action == 1)
            $this->traiteLnVente ($ln);
        elseif($this->action == 3)
            $this->traiteLnAchat ($ln);
        elseif($this->action == 4)
            $this->traiteLnArt ($ln);
    }
    
    function addTab($ref){
        if(!isset($this->tabResultInt['prod'][$ref]))
            $this->tabResultInt['prod'][$ref] = array("ref"=>$ref, "tot"=>0, "1an"=>0, "3mois" => 0, "6mois" => 0, "12mois" => 0, "derDate" => 0, "derPrix" => 0, "derDateA" => 0, "prixCat" => 0);
    }


    function traiteLnInvent($ln){
        if($ln['ArtCode'] != "EPS-C11CB27301"){//car premiere ligne non trouvé dans les ventes
            if(isset($this->tabResultInt['prod'][$ln['ArtCode']])){
                $ln = array_merge ($ln, $this->tabResultInt['prod'][$ln['ArtCode']]);
            }
            else{
                echo "<br/>ATTENTION : Article introuvable en achat vente".$ln['ArtCode'];
            }
            $this->tabResultInt['inventaire'][] = $ln;
        }
    }

    function traiteLnVente($ln){
        $this->addTab($ln['PlvGArtCode']);
        $dateP = DateTime::createFromFormat("d/m/Y", $ln['PlvDate']);
        $dateOld = DateTime::createFromFormat("d/m/Y", $this->tabResultInt['prod'][$ln['PlvGArtCode']]["derDate"]);
        
        $qty = $ln['PlvQteATran'];
        $this->tabResultInt['prod'][$ln['PlvGArtCode']]["tot"]+= $qty;
        if($dateP > $dateOld){
            $this->tabResultInt['prod'][$ln['PlvGArtCode']]["derDate"] = $ln['PlvDate'];
            $this->tabResultInt['prod'][$ln['PlvGArtCode']]["derPrix"] = $ln['PlvPUNet'];
        }
        $this->tabResultInt['prod'][$ln['PlvGArtCode']]["ref"] = $ln['PlvGArtCode'];
        
        $datetime2 = new DateTime(date("Y-m-d H:i:s"));
        $interval = $dateP->diff($datetime2);
        $nbmonth= $interval->format('%m'); //Retourne le nombre de mois
        if($nbmonth < 3)
            $this->tabResultInt['prod'][$ln['PlvGArtCode']]["3mois"]+= $qty;
        elseif($nbmonth < 6)
            $this->tabResultInt['prod'][$ln['PlvGArtCode']]["6mois"]+= $qty;
        elseif($nbmonth < 12)
            $this->tabResultInt['prod'][$ln['PlvGArtCode']]["12mois"]+= $qty;
        if($nbmonth < 12)
            $this->tabResultInt['prod'][$ln['PlvGArtCode']]["1an"]+= $qty;
        
        
//        if($ln['PlvGArtCode'] == "MOS-99MO075007"){
//            print_r($ln);
//                    echo $nbmonth;
//        }
    }
    
    function traiteLnAchat($ln){
        $this->addTab($ln['PlaGArtCode']);
        $dateP = DateTime::createFromFormat("d/m/Y", $ln['PlaDate']);
        $dateOld = DateTime::createFromFormat("d/m/Y", $this->tabResultInt['prod'][$ln['PlaGArtCode']]["derDateA"]);
        
        if($dateP > $dateOld){
            $this->tabResultInt['prod'][$ln['PlaGArtCode']]["derDateA"] = $ln['PlaDate'];
        }
        
    }
    function traiteLnArt($ln){
        $this->addTab($ln['ArtCode']);
            $this->tabResultInt['prod'][$ln['ArtCode']]["prixCat"] = $ln['ArtPrixBase'];
        
    }
}


if($go){
    $c = new importVente($db);
    $c->go();
    llxFooter();
}