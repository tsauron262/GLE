<?php

class synopsisexport {

    public $info = array();

    public function __construct($db, $sortie = 'html') {
        $this->db = $db;
        $this->sortie = $sortie;

        if ($this->sortie == "html") {
            $this->sautDeLigne = "</td></tr><tr><td>";
            $this->separateur = "</td><td>";
            $this->sortie = "html";
        } elseif ($this->sortie == "file") {
            $this->sautDeLigne = "\n";
            $this->separateur = "\t";
            $this->sortie = "file";
        }
    }

    public function exportFactureSav() {
        $result = $this->db->query("SELECT code_client, nom, phone, address, zip, town, facnumber, DATE_FORMAT(fact.datec, '%d-%m-%Y') as date, fact.rowid as factid 
, email , total, total_ttc FROM  `llx_facture` fact, llx_societe soc
WHERE fk_soc = soc.rowid AND `extraparams` IS NULL AND fk_statut = 2 AND  close_code is null AND paye = 1 AND extraparams is null");

        while ($ligne = $this->db->fetch_object($result)) {
            $return1 = $return2 = "";
            $return1 .= $this->textTable($ligne, $this->separateur, $this->sautDeLigne, 'E', true);
            $return2 .= $this->textTable($ligne, $this->separateur, $this->sautDeLigne, 'E', false);
            $result2 = $this->db->query("SELECT ref, if(fd.description = 'Acompte', -100, fd.product_type) as product_type, fd.qty, fd.subprice, fd.description, fd.buy_price_ht, fd.tva_tx FROM  `llx_facturedet` fd left join llx_product p ON p.rowid = fd.fk_product WHERE  `fk_facture` =  " . $ligne->factid);

            $i = 0;
            while ($ligne2 = $this->db->fetch_object($result2)) {
                $i++;
                if ($i == 1)
                    $return1 .= $this->textTable($ligne2, $this->separateur, $this->sautDeLigne, "L", true);
                $return2 .= $this->textTable($ligne2, $this->separateur, $this->sautDeLigne, "L", false);
            }
            $return = $return1 . $return2;
//            echo $return;
            $this->sortie($return, $ligne->facnumber, "factureSav", $ligne->factid);

            echo "<br/>Facture : " . $ligne->facnumber . " exporté.<br/>";
        }
    }

    public function exportChronoSav($centre = null, $typeAff = null, $typeAff2 = null, $paye = false) {

        $where = "1";

        if ($centre)
            $where .= " AND centreVal = '" . $centre . "'";
        if ($paye)
            $where .= " AND fact.fk_statut = 2 AND fact.paye = '1'";


//        $partReq1 = "SELECT prod.ref, prod.label, SUM(factdet.qty) as QTE, SUM(factdet.total_ht) as Total_Vendu, SUM(factdet.buy_price_ht) as Total_Achat";
//        $partReqFin = "";
//
//        $partReq1 = "SELECT prod.ref as ref, prod.label, SUM(factdet.qty) as QTE, SUM(factdet.total_ht) as Total_Vendu, SUM(factdet.buy_price_ht) as Total_Achat";
//        $partReqFin = " Group BY factdet.fk_product LIMIT 0,1000";


        
        
        $partReq5 = " FROM  `llx_facture` fact, llx_propal prop, llx_element_element el1, llx_synopsischrono_view_105 chrono, " .
//                "llx_synopsischrono_view_101 chrono2, llx_element_element el2, ".
//                "llx_synopsischrono_view_101 chrono2, ".
                "llx_facturedet factdet left join llx_product prod on factdet.fk_product = prod.rowid
WHERE fact.rowid = el1.fk_target AND prop.rowid = el1.fk_source AND el1.sourcetype='propal' AND el1.targettype='facture'
AND chrono.propalid = prop.rowid AND factdet.fk_facture = fact.Rowid
AND  fact.close_code is null " .
//"AND chrono.id = el2.fk_source AND chrono2.id = el2.fk_target AND el2.sourcetype = 'SAV' AND el2.targettype='productCli' ".
//"AND chrono2.id = (SELECT FIRST(fk_target) FROM llx_element_element WHERE sourcetype = 'SAV' AND chrono.id = fk_source  AND targettype='productCli') ".
                "AND factdet.total_ht != 0 AND ";
        
        
        if ($typeAff2 == "ca") {
            $partReq1 = "SELECT IF(prod.ref is null, factdet.description, prod.ref) as ref, concat(prod.label,concat(' ',factdet.description)), SUM(factdet.qty) as QTE, SUM(factdet.total_ht) as Total_Vendu, SUM(factdet.buy_price_ht) as Total_Achat, SUM(factdet.total_ht - (factdet.buy_price_ht*factdet.qty)) as Total_Marge";
            $partReqFin = " Group BY factdet.fk_product, factdet.description LIMIT 0,10000";
        } elseif ($typeAff2 == "nb") {
            $partReq1 = "SELECT COUNT(DISTINCT(chrono.id)) as NB_PC";
            $partReqFin = " LIMIT 0,10000";
            $partReq5 = " FROM  llx_synopsischrono_view_105 chrono LEFT JOIN llx_propal propal on chrono.propalId = propal.rowid LEFT JOIN  llx_element_element on sourcetype = 'propal' AND targettype = 'facture' AND fk_source = propal.rowid LEFT JOIN llx_facture fact ON fact.rowid = fk_target AND fact.facnumber LIKE 'FA%' WHERE fact.close_code is null AND ";
        } else {
            $partReq1 = "SELECT chrono.ref as refSav, chrono.Centre, propal.total_ht as Total_Propal, if(fact2.total, fact2.total+fact.total, fact.total) as Total_Facture, SUM(buy_price_ht*qty) as Total_Achat, (if(fact2.total, fact2.total+fact.total, fact.total) - SUM(buy_price_ht*qty)) as Total_Marge, fact.datec as Date, fact.paye as Paye";
            $partReqFin = " Group BY chrono.id LIMIT 0,10000";
  
            $partReq5 = " FROM  llx_synopsischrono_view_105 chrono LEFT JOIN llx_propal propal on chrono.propalId = propal.rowid "
                    . " LEFT JOIN  llx_element_element el1 on el1.sourcetype = 'propal' AND el1.targettype = 'facture' AND el1.fk_source = propal.rowid LEFT JOIN llx_facture fact ON fact.rowid = el1.fk_target AND fact.facnumber LIKE 'FA%' LEFT JOIN llx_facturedet ON fk_facture = fact.rowid"
                    . " LEFT JOIN  llx_element_element el2 on  el2.sourcetype = 'propal' AND el2.targettype = 'facture' AND el2.fk_source = propal.rowid LEFT JOIN llx_facture fact2 ON fact2.rowid = el2.fk_target AND fact2.facnumber LIKE 'AC%' WHERE fact.close_code is null AND ";
        }


        
        
        
            

        
        
        
        
        
        
        
        
        if ($typeAff == "parTypeMat") {
            $result = $this->db->query("SELECT description, id FROM llx_synopsischrono_view_101");

            $tabMateriel = array();
            while ($ligne = $this->db->fetch_object($result)) {
                $tabT = explode("(", $ligne->description);
                $description = trim($tabT[0]);
                $tabT = getElementElement("SAV", "productCli", null, $ligne->id);
                if (count($tabT) > 0)
                    $tabMateriel[strtoupper($description)][] = $tabT[0]['s'];
            }
//        print_r($tabMateriel);die;
            ksort($tabMateriel, SORT_STRING);

            $j = 0;
            foreach ($tabMateriel as $titre => $tabChrono) {
                $j++;
//            if($j > 50)
//                break;
                $this->statLigneFacture($titre, $partReq1 . $partReq5 . $where . " AND chrono.id in (" . implode(",", $tabChrono) . ") " . $partReqFin);


//            echo "<br/>Facture : " . $ligne['facnumber'] . " exporté.<br/>";
            }
        } elseif ($typeAff == "parTypeGar") {
            $result = $this->db->query("SELECT Type_garantie as description, id FROM llx_synopsischrono_view_101");

            $tabMateriel = array();
            while ($ligne = $this->db->fetch_object($result)) {
                $tabT = explode("(", $ligne->description);
                $description = trim($tabT[0]);
                $tabT = getElementElement("SAV", "productCli", null, $ligne->id);
                if (count($tabT) > 0)
                    $tabMateriel[strtoupper($description)][] = $tabT[0]['s'];
            }
//        print_r($tabMateriel);die;
            ksort($tabMateriel, SORT_STRING);

            $j = 0;
            foreach ($tabMateriel as $titre => $tabChrono) {
                $j++;
//            if($j > 50)
//                break;
                $this->statLigneFacture($titre, $partReq1 . $partReq5 . $where . " AND chrono.id in (" . implode(",", $tabChrono) . ") " . $partReqFin);


//            echo "<br/>Facture : " . $ligne['facnumber'] . " exporté.<br/>";
            }
        } elseif ($typeAff == "parCentre") {
            $result = $this->db->query("SELECT label, valeur 
FROM  `llx_Synopsis_Process_form_list_members` 
WHERE  `list_refid` =11");

            $tabMateriel = array();
            while ($ligne = $this->db->fetch_object($result)) {
                    $tabMateriel[strtoupper($ligne->label)] = $ligne->valeur;
            }
//        print_r($tabMateriel);die;
            ksort($tabMateriel, SORT_STRING);

            $j = 0;
            foreach ($tabMateriel as $titre => $val) {
                $j++;
//            if($j > 50)
//                break;
                $this->statLigneFacture($titre, $partReq1 . $partReq5 . $where . " AND CentreVal = '" . $val . "' " . $partReqFin);


//            echo "<br/>Facture : " . $ligne['facnumber'] . " exporté.<br/>";
            }
        } else {
            $this->statLigneFacture("Stat", $partReq1 . $partReq5 . $where . $partReqFin);
        }

        $this->sortie("", "statSav");
    }

    private function statLigneFacture($titre, $req) {
        $return1 = $return2 = "";
        $result2 = $this->db->query($req);
//
        if ($this->db->num_rows($result2) > 0 && (1)) {
            $i = 0;

            while ($ligne2 = $this->db->fetch_object($result2)) {
                $i++;
                if ($i == 1) {
                    if (isset($ligne2->NB_PC) && $ligne2->NB_PC == 0 && $this->db->num_rows($result2) == 1)
                        return '';
                    $this->textSortie($titre, "titre");
                    $return1 .= $this->textTable($ligne2, $this->separateur, $this->sautDeLigne, "", true);
                }

                $return2 .= $this->textTable($ligne2, $this->separateur, $this->sautDeLigne, "", false);
                $oldLigne = $ligne2;
            }
            if ($i > 1)
                $return2 .= $this->textTable($oldLigne, $this->separateur, $this->sautDeLigne, "", "Total");

            $this->textSortie($return1 . $return2);
        }
    }

    public function textSortie($text, $type = "tab") {
        if ($this->sortie == 'html' && $type == "tab")
            $this->textSortie .= "<table><tr><td>" . $text . "</td></tr></table>";
        elseif ($this->sortie == 'html' && $type == "titre")
            $this->textSortie .= "<h3>" . $text . "</h3>";
        else
            $this->textSortie .= $text;
    }

    public function sortie($text, $nom = "temp", $type = "n/c", $idObj = null) {
        global $user;
        $text .= $this->textSortie;

        if ($this->sortie == 'file') {
            $folder2 = "exportGle";
            if ($type == "factureSav") {
                if ($idObj > 0)
                    $this->db->query("UPDATE " . MAIN_DB_PREFIX . "facture SET extraparams = 1 WHERE rowid = " . $idObj);
                $folder2 = "extractFactGle";
            }
            $folder2 .= "/";
            $folder1 = (defined('DIR_SYNCH') ? DIR_SYNCH : DOL_DATA_ROOT . "/synopsischrono/export/" ) . "/";
            if (!is_dir($folder1))
                mkdir($folder1);
            if (!is_dir($folder1 . $folder2))
                mkdir($folder1 . $folder2);
            $nom = str_replace(" ", "_", $nom); //die($folder . $nom . ".txt");
            $file = $folder2 . $nom . ".txt";
            file_put_contents($folder1 . $file, $text);
            echo "<a href='" . DOL_URL_ROOT . "/document.php?modulepart=synopsischrono&file=/export/" . $file . "' class='butAction'>Fichier</a>";
        } else {
            echo "<style>"
            . "td{"
            . "border: 1px black solid;"
            . "}"
            . "</style>";
            echo $text;
        }
        $this->textSortie = "";
    }

    private function textTable($ligne, $separateur, $sautDeLigne, $prefLigne = '', $afficheTitre = true) {
        $return = "";
        $tabCacher = array('factid', 'rowid');
        if ($afficheTitre === "Total") {
            $return .= $prefLigne . $separateur;
            foreach ($ligne as $nom => $valeur) {
//            if($nom == 'product_type')
//                $nom = 'ref_prod';


                if (!is_int($nom) && !in_array($nom, $tabCacher))
                    $return .= str_replace(array($sautDeLigne, $separateur, "\n", "\r"), "  ", (isset($this->tabTot[$nom]) ? price($this->tabTot[$nom]) : "TOTAL")) . $separateur;
            }
            $return .= $sautDeLigne;
        }
        elseif ($afficheTitre) {
            $return .= $prefLigne . $separateur;
            foreach ($ligne as $nom => $valeur) {
//            if($nom == 'product_type')
//                $nom = 'ref_prod';


                if (!is_int($nom) && !in_array($nom, $tabCacher))
                    $return .= str_replace(array($sautDeLigne, $separateur, "\n", "\r"), "  ", $nom) . $separateur;
            }
            $return .= $sautDeLigne;
            $this->tabTot = array();
        }
        else {
            $return .= $prefLigne . $separateur;
            foreach ($ligne as $nom => $valeur) {
                if ($nom == 'product_type') {
                    if ($valeur == -100)
                        $valeur = "GEN-SAV-ACOMPTE";
                    elseif ($valeur == 1)
                        $valeur = "GEN-SAV-MO";
                    elseif ($valeur == 0)
                        $valeur = "GEN-SAV-PIECES";
                    else
                        $valeur = "";
                }
                if($nom == "refSav" && $this->sortie == "html")
                    $valeur = "<a href='".DOL_URL_ROOT."/synopsischrono/fiche.php?ref=".$valeur."'>".$valeur."</a>";

                if ((stripos($nom, "_ht") !== false || stripos($nom, "_ttc") !== false || stripos($nom, "Total") !== false
                        ) && is_numeric($valeur)) {
                    $this->tabTot[$nom] += $valeur;
                    $valeur = price($valeur);
                }


                if (!is_int($nom) && !in_array($nom, $tabCacher))
                    $return .= str_replace(array($sautDeLigne, $separateur, "\n", "\r"), "  ", $valeur) . $separateur;
            }



            $return .= $sautDeLigne;
        }
        return $return;
    }

}
