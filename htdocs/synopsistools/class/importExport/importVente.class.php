<?php

if (!isset($conf)) {
    ini_set("memory_limit", "1024M");
    require_once "../../../main.inc.php";
    llxHeader();
    
    $go = true;
}

require_once DOL_DOCUMENT_ROOT . "/synopsistools/class/importExport/import8sens.class.php";

class importVente extends import8sens {

    var $tabResultInt = array();
    var $logOld = false;
    
    var $dateDepart = "";
    var $dateAchatDef = "";

    public function __construct($db) {
        $this->last = true;
        parent::__construct($db);
        $this->moveFile = false;
    }

    public function go() {
        $this->dateDepart = "31/03/2019";
        
        
        $this->dateAchatDef = "05/10/2014";
        $this->path .= "../inventaire/olys/exportVente/";
        
//        $this->dateAchatDef = "01/01/2016";
//        $this->path .= "../inventaire/comp/exportVente/";
        
        
        
        
        
        $this->action = 1;
        parent::go();

        if ($this->logOld) {
            require_once DOL_DOCUMENT_ROOT . "/synopsistools/class/export8sens.class.php";
            $export = new export8sens($this->db);
            $tabInter = array();
            foreach ($this->tabResultInt['prod'] as $tmp) {
                $tabInter[] = array("PlvGArtID" => "", "PlvGArtCode" => $tmp['ref'], "PlvQteATran" => "0", "PlvQteTr" => "0", "PlvPUNet" => $tmp['derPrix'], "PlvDate" => $tmp['derDate']);
            }
            file_put_contents($this->path . "old.txt", $export->getTxt($tabInter, array()));
        } else {
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


            require_once DOL_DOCUMENT_ROOT . "/synopsistools/class/importExport/export8sens.class.php";
            $export = new export8sens($this->db);
            if (file_put_contents($this->path . "../resultInvent/result.txt", $export->getTxt($this->tabResultInt['inventaire'], array())))
                echo "Ficher " . $this->path . "../resultInvent/result.txt exporté";
            else
                $this->error("Impossible d'éxporté " . $this->path . "../resultInvent/result.txt");
        }
    }

    function traiteLn($ln) {
        $this->tabResultInt["total"] ++;
        if ($this->action == 2)
            $this->traiteLnInvent($ln);
        elseif ($this->action == 1)
            $this->traiteLnVente($ln);
        elseif ($this->action == 3)
            $this->traiteLnAchat($ln);
        elseif ($this->action == 4)
            $this->traiteLnArt($ln);
    }

    function addTab($ref) {
        if (!isset($this->tabResultInt['prod'][$ref]))
            $this->tabResultInt['prod'][$ref] = array("ref" => $ref, "tot" => 0, "1an" => 0, "3mois" => 0, "6mois" => 0, "12mois" => 0, "derDate" => 0, "derPrix" => 0, "derDateA" => $this->dateAchatDef, "prixCat" => "0");
    }

    function traiteLnInvent($ln) {
//        if ($ln['ArtCode'] != "EPS-C11CB27301") {//car premiere ligne non trouvé dans les ventes
            if (isset($this->tabResultInt['prod'][$ln['ArtCode']])) {
                $ln = array_merge($ln, $this->tabResultInt['prod'][$ln['ArtCode']]);
            } else {
                echo "<br/>ATTENTION : Article introuvable en achat vente" . $ln['ArtCode'];
            }
            $this->tabResultInt['inventaire'][] = $ln;
//        }
    }

    function traiteLnVente($ln) {
        $this->addTab($ln['PlvGArtCode']);
        $dateP = DateTime::createFromFormat("d/m/Y", $ln['PlvDate']);
        $dateOld = DateTime::createFromFormat("d/m/Y", $this->tabResultInt['prod'][$ln['PlvGArtCode']]["derDate"]);

        if (substr($ln['PlvCodePcv'], 0, 1) == "F") {
            $qty = $ln['PlvQteUV'];

            $datetime2 = DateTime::createFromFormat("d/m/Y", $this->dateDepart);
            $interval = $dateP->diff($datetime2);
            $nbmonth = $interval->format('%m'); //Retourne le nombre de mois
            $nbyear = $interval->format('%y'); //Retourne le nombre de mois
            
            
            
            if($ln['PlvGArtCode'] == "APP-ME182Z/A"){
                echo "month:".$nbmonth."year:".$nbyear."date:".$ln['PlvDate']."<br/>";
            }
            
            
            if ($dateP <= $datetime2 && $nbyear == 0) {
                $this->tabResultInt['prod'][$ln['PlvGArtCode']]["tot"] += $qty;
                if ($dateP > $dateOld) {
                    $this->tabResultInt['prod'][$ln['PlvGArtCode']]["derDate"] = $ln['PlvDate'];
                    $this->tabResultInt['prod'][$ln['PlvGArtCode']]["derPrix"] = $ln['PlvPUNet'];
                }
                $this->tabResultInt['prod'][$ln['PlvGArtCode']]["ref"] = $ln['PlvGArtCode'];
            
            
                if ($nbmonth < 3 )//&& $dateP !=  DateTime::createFromFormat("d/m/Y", "31/03/2018"))
                    $this->tabResultInt['prod'][$ln['PlvGArtCode']]["3mois"] += $qty;
                elseif ($nbmonth < 6)
                    $this->tabResultInt['prod'][$ln['PlvGArtCode']]["6mois"] += $qty;
                elseif ($nbmonth < 12)
                    $this->tabResultInt['prod'][$ln['PlvGArtCode']]["12mois"] += $qty;
                if ($nbmonth < 12)
                    $this->tabResultInt['prod'][$ln['PlvGArtCode']]["1an"] += $qty;
            }
        }


//        if($ln['PlvGArtCode'] == "MOS-99MO075007"){
//            print_r($ln);
//                    echo $nbmonth;
//        }
    }

    function traiteLnAchat($ln) {
        $this->addTab($ln['PlaGArtCode']);
        $dateP = DateTime::createFromFormat("d/m/Y", $ln['PlaDate']);
        $dateOld = DateTime::createFromFormat("d/m/Y", $this->tabResultInt['prod'][$ln['PlaGArtCode']]["derDateA"]);

        if ($dateP > $dateOld) {
            $this->tabResultInt['prod'][$ln['PlaGArtCode']]["derDateA"] = $ln['PlaDate'];
        }
    }

    function traiteLnArt($ln) {
        $this->addTab($ln['ArtCode']);
        $this->tabResultInt['prod'][$ln['ArtCode']]["prixCat"] = $ln['ArtPrixBase'];
    }

}

if ($go) {
    $c = new importVente($db);
    $c->go();
    llxFooter();
}
